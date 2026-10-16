<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Core\Database;
use App\Models\Familia;
use App\Models\Pessoa;
use App\Models\VinculoFamiliar;
use PDO;

final class FamiliaRepositoryDb implements FamiliaRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT f.* FROM gestao_social.familias f WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(f.nome_referencia) LIKE :search OR LOWER(f.codigo_familia) LIKE :search OR LOWER(f.bairro) LIKE :search)";
            $params['search'] = '%' . mb_strtolower(trim($filters['search']), 'UTF-8') . '%';
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND f.status = :situacao";
            $params['situacao'] = $filters['situacao'];
        }

        if (!empty($filters['bairro'])) {
            $sql .= " AND f.bairro = :bairro";
            $params['bairro'] = $filters['bairro'];
        }

        $sql .= " ORDER BY f.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapRowToModel($row), $rows);
    }

    public function findById(int $id): ?Familia
    {
        $stmt = $this->db->prepare("SELECT * FROM gestao_social.familias WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function findByCodigo(string $codigo): ?Familia
    {
        $stmt = $this->db->prepare("SELECT * FROM gestao_social.familias WHERE LOWER(codigo_familia) = LOWER(:codigo)");
        $stmt->execute(['codigo' => trim($codigo)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function save(Familia $familia): Familia
    {
        if (empty($familia->codigo)) {
            // Gera próximo código sequencial se ainda não possuir
            $stmtSeq = $this->db->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM gestao_social.familias");
            $nextId = (int)$stmtSeq->fetchColumn();
            $familia->codigo = sprintf('FAM-%03d', $nextId);
        }

        $cleanCep = preg_replace('/[^0-9]/', '', $familia->cep);
        if (empty($cleanCep) || strlen($cleanCep) !== 8) {
            $cleanCep = null;
        }

        $sql = "INSERT INTO gestao_social.familias (
                    codigo_familia, nome_referencia, cep, logradouro, numero, complemento,
                    bairro, municipio, uf, renda_familiar, despesas_mensais, situacao_moradia, status
                ) VALUES (
                    :codigo, :nome, :cep, :logradouro, :numero, :complemento,
                    :bairro, :municipio, :uf, :renda, :despesas, :moradia, :status
                ) RETURNING id, data_cadastro";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'codigo' => $familia->codigo,
            'nome' => $familia->pessoaReferenciaNome,
            'cep' => $cleanCep,
            'logradouro' => $familia->logradouro,
            'numero' => $familia->numero,
            'complemento' => $familia->complemento,
            'bairro' => $familia->bairro,
            'municipio' => $familia->municipio,
            'uf' => strtoupper($familia->uf),
            'renda' => $familia->rendaFamiliar,
            'despesas' => $familia->despesasMensais,
            'moradia' => $this->normalizeMoradia($familia->situacaoMoradia),
            'status' => $this->normalizeStatus($familia->situacaoCadastral),
        ]);

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $familia->id = (int)$res['id'];
        $familia->dataCadastro = substr((string)$res['data_cadastro'], 0, 10);

        return $familia;
    }

    public function update(Familia $familia): bool
    {
        $cleanCep = preg_replace('/[^0-9]/', '', $familia->cep);
        if (empty($cleanCep) || strlen($cleanCep) !== 8) {
            $cleanCep = null;
        }

        $sql = "UPDATE gestao_social.familias SET
                    nome_referencia = :nome,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    municipio = :municipio,
                    uf = :uf,
                    renda_familiar = :renda,
                    despesas_mensais = :despesas,
                    situacao_moradia = :moradia,
                    status = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $familia->id,
            'nome' => $familia->pessoaReferenciaNome,
            'cep' => $cleanCep,
            'logradouro' => $familia->logradouro,
            'numero' => $familia->numero,
            'complemento' => $familia->complemento,
            'bairro' => $familia->bairro,
            'municipio' => $familia->municipio,
            'uf' => strtoupper($familia->uf),
            'renda' => $familia->rendaFamiliar,
            'despesas' => $familia->despesasMensais,
            'moradia' => $this->normalizeMoradia($familia->situacaoMoradia),
            'status' => $this->normalizeStatus($familia->situacaoCadastral),
        ]);
    }

    public function getIntegrantes(int $familiaId, bool $apenasAtivos = true): array
    {
        $sql = "SELECT fi.*, 
                       p.nome_completo, p.nome_social, p.data_nascimento, p.cpf, 
                       p.telefone, p.escolaridade, p.ocupacao, p.situacao_ocupacional, 
                       p.observacoes AS pessoa_obs, p.status AS pessoa_status
                  FROM gestao_social.familia_integrantes fi
                  JOIN gestao_social.pessoas p ON p.id = fi.pessoa_id
                 WHERE fi.familia_id = :familia_id";

        if ($apenasAtivos) {
            $sql .= " AND fi.data_fim IS NULL";
        }

        $sql .= " ORDER BY fi.responsavel_familiar DESC, fi.data_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['familia_id' => $familiaId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vinculos = [];
        foreach ($rows as $row) {
            $pessoa = new Pessoa(
                (int)$row['pessoa_id'],
                $row['nome_completo'],
                $row['nome_social'],
                $row['data_nascimento'] ?? '',
                $row['cpf'],
                $row['telefone'],
                $row['escolaridade'] ?? 'Não informado',
                $row['ocupacao'],
                $row['situacao_ocupacional'] ?? 'Desempregado',
                $row['pessoa_obs'],
                $row['pessoa_status'] ?? 'Ativo'
            );

            $vinculos[] = new VinculoFamiliar(
                (int)$row['id'],
                (int)$row['familia_id'],
                (int)$row['pessoa_id'],
                $row['parentesco'],
                (bool)$row['responsavel_familiar'],
                $row['data_inicio'],
                $row['data_fim'],
                $row['data_fim'] === null,
                $row['observacoes'],
                $pessoa
            );
        }

        return $vinculos;
    }

    public function addIntegrante(VinculoFamiliar $vinculo): VinculoFamiliar
    {
        $sql = "INSERT INTO gestao_social.familia_integrantes (
                    familia_id, pessoa_id, parentesco, responsavel_familiar, data_inicio, data_fim, observacoes
                ) VALUES (
                    :familia_id, :pessoa_id, :parentesco, :is_responsavel, :data_inicio, :data_fim, :observacoes
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'familia_id' => $vinculo->familiaId,
            'pessoa_id' => $vinculo->pessoaId,
            'parentesco' => $this->normalizeParentesco($vinculo->parentesco),
            'is_responsavel' => $vinculo->isResponsavel ? 'true' : 'false',
            'data_inicio' => $vinculo->dataInicio ?: date('Y-m-d'),
            'data_fim' => $vinculo->ativo ? null : ($vinculo->dataTermino ?: date('Y-m-d')),
            'observacoes' => $vinculo->observacoes,
        ]);

        $vinculo->id = (int)$stmt->fetchColumn();
        return $vinculo;
    }

    public function updateIntegrante(VinculoFamiliar $vinculo): bool
    {
        $sql = "UPDATE gestao_social.familia_integrantes SET
                    parentesco = :parentesco,
                    responsavel_familiar = :is_responsavel,
                    data_inicio = :data_inicio,
                    data_fim = :data_fim,
                    observacoes = :observacoes
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $vinculo->id,
            'parentesco' => $this->normalizeParentesco($vinculo->parentesco),
            'is_responsavel' => $vinculo->isResponsavel ? 'true' : 'false',
            'data_inicio' => $vinculo->dataInicio,
            'data_fim' => $vinculo->ativo ? null : ($vinculo->dataTermino ?: date('Y-m-d')),
            'observacoes' => $vinculo->observacoes,
        ]);
    }

    public function countTotal(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM gestao_social.familias f WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(f.nome_referencia) LIKE :search OR LOWER(f.codigo_familia) LIKE :search)";
            $params['search'] = '%' . mb_strtolower(trim($filters['search']), 'UTF-8') . '%';
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND f.status = :situacao";
            $params['situacao'] = $filters['situacao'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function countBySituacao(string $situacao): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM gestao_social.familias WHERE status = :status");
        $stmt->execute(['status' => $this->normalizeStatus($situacao)]);
        return (int)$stmt->fetchColumn();
    }

    public function getBairros(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT bairro FROM gestao_social.familias WHERE bairro IS NOT NULL AND bairro <> '' ORDER BY bairro ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function mapRowToModel(array $row): Familia
    {
        $cep = (string)($row['cep'] ?? '');
        if (strlen($cep) === 8) {
            $cep = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }

        return new Familia(
            (int)$row['id'],
            $row['codigo_familia'],
            $row['nome_referencia'],
            null,
            $cep,
            $row['logradouro'],
            $row['numero'],
            $row['complemento'],
            $row['bairro'],
            $row['municipio'],
            $row['uf'],
            (float)($row['renda_familiar'] ?? 0.0),
            (float)($row['despesas_mensais'] ?? 0.0),
            $row['situacao_moradia'],
            substr((string)$row['data_cadastro'], 0, 10),
            $row['status'],
            null,
            []
        );
    }

    private function normalizeStatus(string $status): string
    {
        return match (mb_strtolower(trim($status), 'UTF-8')) {
            'em análise', 'em analise' => 'Em análise',
            'ativa', 'ativo' => 'Ativa',
            'suspensa', 'suspenso' => 'Suspensa',
            'encerrada', 'encerrado' => 'Encerrada',
            default => 'Ativa'
        };
    }

    private function normalizeMoradia(string $moradia): string
    {
        $m = mb_strtolower(trim($moradia), 'UTF-8');
        if (str_contains($m, 'própria') || str_contains($m, 'propria')) return 'Própria';
        if (str_contains($m, 'alugada')) return 'Alugada';
        if (str_contains($m, 'cedida')) return 'Cedida';
        if (str_contains($m, 'ocupação') || str_contains($m, 'ocupacao')) return 'Ocupação';
        if (str_contains($m, 'precária') || str_contains($m, 'precaria')) return 'Precária';
        if (str_contains($m, 'rua')) return 'Situação de rua';
        return 'Outra';
    }

    private function normalizeParentesco(string $parentesco): string
    {
        $p = mb_strtolower(trim($parentesco), 'UTF-8');
        if (str_contains($p, 'responsável') || str_contains($p, 'responsavel')) return 'Responsável Familiar';
        if (str_contains($p, 'cônjuge') || str_contains($p, 'companheiro')) return 'Cônjuge/Companheiro(a)';
        if (str_contains($p, 'filho')) return 'Filho(a)';
        if (str_contains($p, 'enteado')) return 'Enteado(a)';
        if (str_contains($p, 'neto')) return 'Neto(a)';
        if (str_contains($p, 'pai') || str_contains($p, 'mãe') || str_contains($p, 'mae')) return 'Pai/Mãe';
        if (str_contains($p, 'avô') || str_contains($p, 'avó')) return 'Avô/Avó';
        if (str_contains($p, 'irmão') || str_contains($p, 'irmã')) return 'Irmão/Irmã';
        return 'Outro';
    }
}
