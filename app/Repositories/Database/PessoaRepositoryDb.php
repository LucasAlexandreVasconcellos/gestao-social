<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Core\Database;
use App\Models\Pessoa;
use App\Models\VinculoFamiliar;
use PDO;

final class PessoaRepositoryDb implements PessoaRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM gestao_social.pessoas WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $term = '%' . mb_strtolower(trim($filters['search']), 'UTF-8') . '%';
            $cleanDoc = preg_replace('/\D/', '', $filters['search']);
            if (!empty($cleanDoc)) {
                $sql .= " AND (LOWER(nome_completo) LIKE :term OR cpf LIKE :doc OR telefone LIKE :doc)";
                $params['term'] = $term;
                $params['doc'] = '%' . $cleanDoc . '%';
            } else {
                $sql .= " AND (LOWER(nome_completo) LIKE :term OR LOWER(COALESCE(nome_social, '')) LIKE :term)";
                $params['term'] = $term;
            }
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY nome_completo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapRowToModel($row), $rows);
    }

    public function findById(int $id): ?Pessoa
    {
        $stmt = $this->db->prepare("SELECT * FROM gestao_social.pessoas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function findByCpf(string $cpf): ?Pessoa
    {
        $clean = preg_replace('/\D/', '', $cpf);
        if (empty($clean)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM gestao_social.pessoas WHERE cpf = :cpf");
        $stmt->execute(['cpf' => $clean]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function search(string $term): array
    {
        return $this->findAll(['search' => $term]);
    }

    public function save(Pessoa $pessoa): Pessoa
    {
        $cleanCpf = !empty($pessoa->cpf) ? preg_replace('/\D/', '', $pessoa->cpf) : null;
        $cleanTel = !empty($pessoa->telefone) ? preg_replace('/\D/', '', $pessoa->telefone) : null;
        if (empty($cleanTel) || strlen($cleanTel) < 10) {
            $cleanTel = null;
        }

        $sql = "INSERT INTO gestao_social.pessoas (
                    nome_completo, nome_social, data_nascimento, cpf, telefone,
                    escolaridade, ocupacao, situacao_ocupacional, observacoes, status
                ) VALUES (
                    :nome, :nome_social, :nasc, :cpf, :telefone,
                    :escolaridade, :ocupacao, :ocupacao_status, :obs, :status
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nome' => $pessoa->nomeCompleto,
            'nome_social' => $pessoa->nomeSocial,
            'nasc' => !empty($pessoa->dataNascimento) ? $pessoa->dataNascimento : null,
            'cpf' => $cleanCpf,
            'telefone' => $cleanTel,
            'escolaridade' => $pessoa->escolaridade ?: 'Não informado',
            'ocupacao' => $pessoa->ocupacao,
            'ocupacao_status' => $this->normalizeOcupacao($pessoa->situacaoOcupacional),
            'obs' => $pessoa->observacoes,
            'status' => $pessoa->situacaoCadastro ?: 'Ativo',
        ]);

        $pessoa->id = (int)$stmt->fetchColumn();
        return $pessoa;
    }

    public function update(Pessoa $pessoa): bool
    {
        $cleanCpf = !empty($pessoa->cpf) ? preg_replace('/\D/', '', $pessoa->cpf) : null;
        $cleanTel = !empty($pessoa->telefone) ? preg_replace('/\D/', '', $pessoa->telefone) : null;
        if (empty($cleanTel) || strlen($cleanTel) < 10) {
            $cleanTel = null;
        }

        $sql = "UPDATE gestao_social.pessoas SET
                    nome_completo = :nome,
                    nome_social = :nome_social,
                    data_nascimento = :nasc,
                    cpf = :cpf,
                    telefone = :telefone,
                    escolaridade = :escolaridade,
                    ocupacao = :ocupacao,
                    situacao_ocupacional = :ocupacao_status,
                    observacoes = :obs,
                    status = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $pessoa->id,
            'nome' => $pessoa->nomeCompleto,
            'nome_social' => $pessoa->nomeSocial,
            'nasc' => !empty($pessoa->dataNascimento) ? $pessoa->dataNascimento : null,
            'cpf' => $cleanCpf,
            'telefone' => $cleanTel,
            'escolaridade' => $pessoa->escolaridade ?: 'Não informado',
            'ocupacao' => $pessoa->ocupacao,
            'ocupacao_status' => $this->normalizeOcupacao($pessoa->situacaoOcupacional),
            'obs' => $pessoa->observacoes,
            'status' => $pessoa->situacaoCadastro ?: 'Ativo',
        ]);
    }

    public function getHistoricoVinculosFamiliares(int $pessoaId): array
    {
        $sql = "SELECT fi.*, p.nome_completo, p.cpf
                  FROM gestao_social.familia_integrantes fi
                  JOIN gestao_social.pessoas p ON p.id = fi.pessoa_id
                 WHERE fi.pessoa_id = :pessoa_id
                 ORDER BY fi.data_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pessoa_id' => $pessoaId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vinculos = [];
        foreach ($rows as $row) {
            $vinculos[] = new VinculoFamiliar(
                (int)$row['id'],
                (int)$row['familia_id'],
                (int)$row['pessoa_id'],
                $row['parentesco'],
                (bool)$row['responsavel_familiar'],
                $row['data_inicio'],
                $row['data_fim'],
                $row['data_fim'] === null,
                $row['observacoes']
            );
        }

        return $vinculos;
    }

    public function countTotal(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM gestao_social.pessoas");
        return (int)$stmt->fetchColumn();
    }

    private function mapRowToModel(array $row): Pessoa
    {
        $cpf = $row['cpf'] ?? null;
        if ($cpf && strlen($cpf) === 11) {
            $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }

        return new Pessoa(
            (int)$row['id'],
            $row['nome_completo'],
            $row['nome_social'],
            $row['data_nascimento'] ?? '',
            $cpf,
            $row['telefone'],
            $row['escolaridade'] ?? 'Não informado',
            $row['ocupacao'],
            $row['situacao_ocupacional'] ?? 'Desempregado',
            $row['observacoes'],
            $row['status'] ?? 'Ativo'
        );
    }

    private function normalizeOcupacao(string $ocupacao): string
    {
        $o = mb_strtolower(trim($ocupacao), 'UTF-8');
        if (str_contains($o, 'empregado') && !str_contains($o, 'desempregado')) return 'Empregado';
        if (str_contains($o, 'aposentado')) return 'Aposentado';
        if (str_contains($o, 'autônomo') || str_contains($o, 'autonomo')) return 'Autônomo';
        if (str_contains($o, 'informal')) return 'Informal';
        if (str_contains($o, 'lar')) return 'Do lar';
        if (str_contains($o, 'estudante')) return 'Estudante';
        if (str_contains($o, 'desempregado')) return 'Desempregado';
        return 'Outra';
    }
}
