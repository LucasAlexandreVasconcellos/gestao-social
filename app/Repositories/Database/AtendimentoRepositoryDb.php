<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Core\Database;
use App\Models\Atendimento;
use App\Models\Encaminhamento;
use PDO;

final class AtendimentoRepositoryDb implements AtendimentoRepositoryInterface
{
    private ?PDO $dbInstance = null;

    public function __construct(?PDO $db = null)
    {
        $this->dbInstance = $db;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'db') {
            return $this->dbInstance ??= Database::getConnection();
        }
        return null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT a.*, u.nome AS responsavel_nome
                  FROM gestao_social.atendimentos a
                  JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['familiaId'])) {
            $sql .= " AND a.familia_id = :familia_id";
            $params['familia_id'] = (int)$filters['familiaId'];
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND a.status = :status";
            $params['status'] = $this->normalizeStatus($filters['situacao']);
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND a.tipo_atendimento = :tipo";
            $params['tipo'] = $this->normalizeTipo($filters['tipo']);
        }

        $sql .= " ORDER BY a.data_atendimento DESC, a.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $atendimentos = [];
        foreach ($rows as $row) {
            $atendimentos[] = $this->mapRowToModel($row);
        }

        return $atendimentos;
    }

    public function findById(int $id): ?Atendimento
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nome AS responsavel_nome
              FROM gestao_social.atendimentos a
              JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
             WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $atendimento = $this->mapRowToModel($row);
        $atendimento->encaminhamentos = $this->getEncaminhamentos($id);
        return $atendimento;
    }

    public function findByFamiliaId(int $familiaId): array
    {
        return $this->findAll(['familiaId' => $familiaId]);
    }

    public function save(Atendimento $atendimento): Atendimento
    {
        $responsavelId = $this->resolveResponsavelId($atendimento->profissionalResponsavel);

        $sql = "INSERT INTO gestao_social.atendimentos (
                    familia_id, pessoa_id, responsavel_id, data_atendimento,
                    tipo_atendimento, descricao, providencias, retorno_previsto,
                    status, data_conclusao
                ) VALUES (
                    :familia_id, :pessoa_id, :responsavel_id, :data_atend,
                    :tipo, :descricao, :providencias, :retorno,
                    :status, :data_conclusao
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'familia_id' => $atendimento->familiaId,
            'pessoa_id' => $atendimento->pessoaId ?: null,
            'responsavel_id' => $responsavelId,
            'data_atend' => $atendimento->dataAtendimento ?: date('Y-m-d H:i:s'),
            'tipo' => $this->normalizeTipo($atendimento->tipo),
            'descricao' => $atendimento->descricao ?: 'Atendimento socioassistencial realizado.',
            'providencias' => $atendimento->providencias,
            'retorno' => $atendimento->retornoPrevisto ?: null,
            'status' => $this->normalizeStatus($atendimento->situacao),
            'data_conclusao' => $atendimento->dataConclusao ?: null,
        ]);

        $atendimento->id = (int)$stmt->fetchColumn();
        return $atendimento;
    }

    public function update(Atendimento $atendimento): bool
    {
        $sql = "UPDATE gestao_social.atendimentos SET
                    tipo_atendimento = :tipo,
                    descricao = :descricao,
                    providencias = :providencias,
                    retorno_previsto = :retorno,
                    status = :status,
                    data_conclusao = :data_conclusao
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $atendimento->id,
            'tipo' => $this->normalizeTipo($atendimento->tipo),
            'descricao' => $atendimento->descricao,
            'providencias' => $atendimento->providencias,
            'retorno' => $atendimento->retornoPrevisto ?: null,
            'status' => $this->normalizeStatus($atendimento->situacao),
            'data_conclusao' => $atendimento->dataConclusao ?: null,
        ]);
    }

    public function addEncaminhamento(Encaminhamento $encaminhamento): Encaminhamento
    {
        $responsavelId = $this->resolveResponsavelId($encaminhamento->responsavel);

        $sql = "INSERT INTO gestao_social.encaminhamentos (
                    atendimento_id, responsavel_id, servico_destino, motivo,
                    data_encaminhamento, status, data_retorno, resultado
                ) VALUES (
                    :atendimento_id, :responsavel_id, :destino, :motivo,
                    :data_enc, :status, :retorno, :resultado
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'atendimento_id' => $encaminhamento->atendimentoId,
            'responsavel_id' => $responsavelId,
            'destino' => $encaminhamento->servicoDestino,
            'motivo' => $encaminhamento->motivo,
            'data_enc' => $encaminhamento->data ?: date('Y-m-d'),
            'status' => $this->normalizeEncStatus($encaminhamento->situacao),
            'retorno' => null,
            'resultado' => $encaminhamento->resultado,
        ]);

        $encaminhamento->id = (int)$stmt->fetchColumn();
        return $encaminhamento;
    }

    public function getEncaminhamentos(int $atendimentoId): array
    {
        $sql = "SELECT e.*, u.nome AS responsavel_nome
                  FROM gestao_social.encaminhamentos e
                  JOIN gestao_social.usuarios u ON u.id = e.responsavel_id
                 WHERE e.atendimento_id = :atendimento_id
                 ORDER BY e.data_encaminhamento DESC, e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['atendimento_id' => $atendimentoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $encs = [];
        foreach ($rows as $row) {
            $encs[] = new Encaminhamento(
                (int)$row['id'],
                (int)$row['atendimento_id'],
                $row['servico_destino'],
                $row['motivo'],
                $row['responsavel_nome'] ?? 'Assistente Social',
                substr((string)$row['data_encaminhamento'], 0, 10),
                $row['status'],
                $row['resultado']
            );
        }

        return $encs;
    }

    public function countPendentes(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM gestao_social.atendimentos WHERE status IN ('Aberto', 'Em acompanhamento')");
        return (int)$stmt->fetchColumn();
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }

    private function mapRowToModel(array $row): Atendimento
    {
        return new Atendimento(
            (int)$row['id'],
            (int)$row['familia_id'],
            $row['pessoa_id'] !== null ? (int)$row['pessoa_id'] : null,
            $row['tipo_atendimento'],
            substr((string)$row['data_atendimento'], 0, 10),
            $row['responsavel_nome'] ?? 'Assistente Social',
            $row['descricao'] ?? '',
            $row['providencias'] ?? '',
            $row['retorno_previsto'],
            $this->denormalizeStatus($row['status']),
            $row['data_conclusao'] ? substr((string)$row['data_conclusao'], 0, 10) : null,
            []
        );
    }

    private function normalizeStatus(string $status): string
    {
        return match (mb_strtolower(trim($status), 'UTF-8')) {
            'pendente', 'aberto' => 'Aberto',
            'em andamento', 'em acompanhamento' => 'Em acompanhamento',
            'concluído', 'concluido' => 'Concluído',
            'cancelado', 'cancelada' => 'Cancelado',
            default => 'Aberto'
        };
    }

    private function denormalizeStatus(string $status): string
    {
        return match ($status) {
            'Aberto' => 'Pendente',
            'Em acompanhamento' => 'Em Andamento',
            'Concluído' => 'Concluído',
            'Cancelado' => 'Cancelado',
            default => $status
        };
    }

    private function normalizeTipo(string $tipo): string
    {
        $t = mb_strtolower(trim($tipo), 'UTF-8');
        if (str_contains($t, 'visita')) return 'Visita Domiciliar';
        if (str_contains($t, 'emergencial')) return 'Atendimento Emergencial';
        if (str_contains($t, 'orientação') || str_contains($t, 'orientacao')) return 'Orientação';
        if (str_contains($t, 'reunião') || str_contains($t, 'reuniao') || str_contains($t, 'acompanhamento')) return 'Reunião de Acompanhamento';
        return 'Acolhida/Entrevista';
    }

    private function normalizeEncStatus(string $status): string
    {
        return match (mb_strtolower(trim($status), 'UTF-8')) {
            'em andamento', 'em acompanhamento' => 'Em andamento',
            'retorno recebido' => 'Retorno recebido',
            'concluído', 'concluido' => 'Concluído',
            default => 'Encaminhado'
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
             WHERE p.nome IN ('Assistente Social', 'Atendimento / Recepção', 'Administrador')
             ORDER BY u.id ASC
             LIMIT 1
        ");
        $defaultId = $stmtDefault->fetchColumn();

        return $defaultId ? (int)$defaultId : 1;
    }
}
