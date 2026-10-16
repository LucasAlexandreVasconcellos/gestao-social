<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\ProjetoRepositoryInterface;
use App\Core\Database;
use App\Models\ParticipanteProjeto;
use App\Models\ProjetoSocial;
use PDO;

final class ProjetoRepositoryDb implements ProjetoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT a.*, u.nome AS responsavel_nome
                  FROM gestao_social.acoes_sociais a
                  JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['tipo'])) {
            $sql .= " AND a.tipo = :tipo";
            $params['tipo'] = $this->normalizeTipo($filters['tipo']);
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND a.status = :status";
            $params['status'] = $this->normalizeStatus($filters['situacao']);
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(a.nome) LIKE :search OR LOWER(COALESCE(a.descricao, '')) LIKE :search)";
            $params['search'] = '%' . mb_strtolower(trim($filters['search']), 'UTF-8') . '%';
        }

        $sql .= " ORDER BY a.data_inicio DESC, a.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $projetos = [];
        foreach ($rows as $row) {
            $projeto = $this->mapRowToModel($row);
            $projeto->participantes = $this->getParticipantes((int)$row['id']);
            $projetos[] = $projeto;
        }

        return $projetos;
    }

    public function findById(int $id): ?ProjetoSocial
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nome AS responsavel_nome
              FROM gestao_social.acoes_sociais a
              JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
             WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $projeto = $this->mapRowToModel($row);
        $projeto->participantes = $this->getParticipantes($id);
        return $projeto;
    }

    public function save(ProjetoSocial $projeto): ProjetoSocial
    {
        $responsavelId = $this->resolveResponsavelId($projeto->responsavel);

        $sql = "INSERT INTO gestao_social.acoes_sociais (
                    nome, tipo, publico_alvo, descricao, local,
                    responsavel_id, data_inicio, data_fim,
                    orcamento_previsto, valor_realizado, meta_beneficiarios, status
                ) VALUES (
                    :nome, :tipo, :publico, :descricao, :local,
                    :responsavel_id, :inicio, :fim,
                    :orcamento, :realizado, :meta, :status
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nome' => $projeto->nome,
            'tipo' => $this->normalizeTipo($projeto->tipo),
            'publico' => $this->normalizePublico($projeto->publicoAlvo),
            'descricao' => $projeto->descricao,
            'local' => $projeto->local,
            'responsavel_id' => $responsavelId,
            'inicio' => $projeto->dataInicio ?: date('Y-m-d'),
            'fim' => $projeto->dataEncerramento ?: null,
            'orcamento' => $projeto->orcamentoPrevisto,
            'realizado' => $projeto->valorRealizado,
            'meta' => $projeto->metaBeneficiarios,
            'status' => $this->normalizeStatus($projeto->situacao),
        ]);

        $projeto->id = (int)$stmt->fetchColumn();
        return $projeto;
    }

    public function update(ProjetoSocial $projeto): bool
    {
        $sql = "UPDATE gestao_social.acoes_sociais SET
                    nome = :nome,
                    tipo = :tipo,
                    publico_alvo = :publico,
                    descricao = :descricao,
                    local = :local,
                    data_inicio = :inicio,
                    data_fim = :fim,
                    orcamento_previsto = :orcamento,
                    valor_realizado = :realizado,
                    meta_beneficiarios = :meta,
                    status = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $projeto->id,
            'nome' => $projeto->nome,
            'tipo' => $this->normalizeTipo($projeto->tipo),
            'publico' => $this->normalizePublico($projeto->publicoAlvo),
            'descricao' => $projeto->descricao,
            'local' => $projeto->local,
            'inicio' => $projeto->dataInicio,
            'fim' => $projeto->dataEncerramento ?: null,
            'orcamento' => $projeto->orcamentoPrevisto,
            'realizado' => $projeto->valorRealizado,
            'meta' => $projeto->metaBeneficiarios,
            'status' => $this->normalizeStatus($projeto->situacao),
        ]);
    }

    public function addParticipante(ParticipanteProjeto $participante): ParticipanteProjeto
    {
        // Se familia_id não veio mas pessoa_id sim, tenta buscar a família da pessoa
        $familiaId = $participante->familiaId;
        if (empty($familiaId) && !empty($participante->pessoaId)) {
            $stmtFam = $this->db->prepare("SELECT familia_id FROM gestao_social.familia_integrantes WHERE pessoa_id = :p_id ORDER BY id DESC LIMIT 1");
            $stmtFam->execute(['p_id' => $participante->pessoaId]);
            $familiaId = $stmtFam->fetchColumn() ? (int)$stmtFam->fetchColumn() : null;
        }

        // Se ainda não tiver familia_id, vincula à primeira família ativa para satisfazer a FK NOT NULL
        if (empty($familiaId)) {
            $stmtFirst = $this->db->query("SELECT id FROM gestao_social.familias WHERE status = 'Ativa' LIMIT 1");
            $familiaId = (int)($stmtFirst->fetchColumn() ?: 1);
        }

        $sql = "INSERT INTO gestao_social.acao_beneficiarios (
                    acao_id, familia_id, pessoa_id, data_participacao, situacao, observacoes
                ) VALUES (
                    :acao_id, :familia_id, :pessoa_id, CURRENT_TIMESTAMP, :situacao, :obs
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'acao_id' => $participante->projetoId,
            'familia_id' => $familiaId,
            'pessoa_id' => $participante->pessoaId ?: null,
            'situacao' => $this->normalizeParticipacao($participante->situacaoParticipacao),
            'obs' => $participante->observacoes,
        ]);

        $participante->id = (int)$stmt->fetchColumn();
        return $participante;
    }

    public function updateParticipante(ParticipanteProjeto $participante): bool
    {
        $sql = "UPDATE gestao_social.acao_beneficiarios SET
                    situacao = :situacao,
                    observacoes = :obs
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $participante->id,
            'situacao' => $this->normalizeParticipacao($participante->situacaoParticipacao),
            'obs' => $participante->observacoes,
        ]);
    }

    public function getParticipantes(int $projetoId): array
    {
        $sql = "SELECT ab.*, 
                       p.nome_completo AS pessoa_nome, 
                       f.nome_referencia AS familia_nome
                  FROM gestao_social.acao_beneficiarios ab
                  LEFT JOIN gestao_social.pessoas p ON p.id = ab.pessoa_id
                  LEFT JOIN gestao_social.familias f ON f.id = ab.familia_id
                 WHERE ab.acao_id = :acao_id
                 ORDER BY ab.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['acao_id' => $projetoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $participantes = [];
        foreach ($rows as $row) {
            $nome = $row['pessoa_nome'] ?? $row['familia_nome'] ?? 'Participante';
            $situacao = $this->denormalizeParticipacao($row['situacao']);

            $participantes[] = new ParticipanteProjeto(
                (int)$row['id'],
                (int)$row['acao_id'],
                $row['familia_id'] !== null ? (int)$row['familia_id'] : null,
                $row['pessoa_id'] !== null ? (int)$row['pessoa_id'] : null,
                $nome,
                $situacao,
                $row['situacao'] === 'Atendido' ? substr((string)$row['data_participacao'], 0, 10) : null,
                $row['observacoes']
            );
        }

        return $participantes;
    }

    public function countEmAndamento(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM gestao_social.acoes_sociais WHERE status = 'Em andamento'");
        return (int)$stmt->fetchColumn();
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }

    private function mapRowToModel(array $row): ProjetoSocial
    {
        return new ProjetoSocial(
            (int)$row['id'],
            $row['nome'],
            $row['tipo'],
            $row['publico_alvo'],
            substr((string)$row['data_inicio'], 0, 10),
            $row['data_fim'] ? substr((string)$row['data_fim'], 0, 10) : null,
            $row['local'] ?? '',
            $row['responsavel_nome'] ?? 'Coordenação',
            (float)($row['orcamento_previsto'] ?? 0.0),
            (float)($row['valor_realizado'] ?? 0.0),
            (int)($row['meta_beneficiarios'] ?? 0),
            $this->denormalizeStatus($row['status']),
            $row['descricao'] ?? '',
            []
        );
    }

    private function normalizeStatus(string $status): string
    {
        return match (mb_strtolower(trim($status), 'UTF-8')) {
            'em andamento' => 'Em andamento',
            'planejada' => 'Planejada',
            'concluída', 'concluida' => 'Concluída',
            'cancelada' => 'Cancelada',
            default => 'Em andamento'
        };
    }

    private function denormalizeStatus(string $status): string
    {
        return match ($status) {
            'Em andamento' => 'Em Andamento',
            'Planejada' => 'Planejada',
            'Concluída' => 'Concluída',
            'Cancelada' => 'Cancelada',
            default => $status
        };
    }

    private function normalizeTipo(string $tipo): string
    {
        $t = mb_strtolower(trim($tipo), 'UTF-8');
        if (str_contains($t, 'campanha')) return 'Campanha';
        if (str_contains($t, 'evento')) return 'Evento';
        if (str_contains($t, 'mutir')) return 'Mutirão';
        if (str_contains($t, 'distribui')) return 'Distribuição Coletiva';
        return 'Projeto';
    }

    private function normalizePublico(string $publico): string
    {
        $p = mb_strtolower(trim($publico), 'UTF-8');
        if (str_contains($p, 'fam')) return 'Famílias';
        if (str_contains($p, 'crian') || str_contains($p, 'jov')) return 'Crianças e Adolescentes';
        if (str_contains($p, 'idos')) return 'Idosos';
        if (str_contains($p, 'defici') || str_contains($p, 'pcd')) return 'Pessoas com Deficiência';
        return 'Geral';
    }

    private function normalizeParticipacao(string $situacao): string
    {
        return match (mb_strtolower(trim($situacao), 'UTF-8')) {
            'convocado' => 'Convocado',
            'efetivamente atendido', 'atendido' => 'Atendido',
            'ausente' => 'Ausente',
            default => 'Inscrito'
        };
    }

    private function denormalizeParticipacao(string $situacao): string
    {
        return match ($situacao) {
            'Atendido' => 'Efetivamente Atendido',
            'Convocado' => 'Convocado',
            'Ausente' => 'Ausente',
            default => 'Inscrito'
        };
    }

    private function resolveResponsavelId(string $nomeOuIdentificador): int
    {
        $stmt = $this->db->prepare("SELECT id FROM gestao_social.usuarios WHERE LOWER(nome) LIKE :busca OR LOWER(login) = :login LIMIT 1");
        $stmt->execute([
            'busca' => '%' . mb_strtolower(trim($nomeOuIdentificador), 'UTF-8') . '%',
            'login' => mb_strtolower(trim($nomeOuIdentificador), 'UTF-8')
        ]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $stmtDefault = $this->db->query("
            SELECT u.id 
              FROM gestao_social.usuarios u
              JOIN gestao_social.perfis p ON p.id = u.perfil_id
             WHERE p.nome IN ('Coordenação', 'Administrador')
             ORDER BY u.id ASC
             LIMIT 1
        ");
        $defaultId = $stmtDefault->fetchColumn();

        return $defaultId ? (int)$defaultId : 1;
    }
}
