<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\AuditoriaRepositoryInterface;
use App\Core\Database;
use App\Models\RegistroAuditoria;
use PDO;

final class AuditoriaRepositoryDb implements AuditoriaRepositoryInterface
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

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT a.*, COALESCE(u.nome, a.usuario_identificador) AS usuario_nome
                  FROM gestao_social.auditoria a
                  LEFT JOIN gestao_social.usuarios u ON u.id = a.usuario_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['entidade'])) {
            $sql .= " AND a.tabela_afetada LIKE :entidade";
            $params['entidade'] = '%' . $filters['entidade'] . '%';
        }

        if (!empty($filters['operacao'])) {
            $sql .= " AND a.operacao = :operacao";
            $params['operacao'] = $filters['operacao'];
        }

        if (!empty($filters['usuarioId'])) {
            $sql .= " AND a.usuario_id = :usuario_id";
            $params['usuario_id'] = (int)$filters['usuarioId'];
        }

        $sql .= " ORDER BY a.data_hora DESC, a.id DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapRowToModel($row), $rows);
    }

    public function save(RegistroAuditoria $registro): RegistroAuditoria
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $detalhesJson = json_encode(['detalhes' => $registro->detalhes], JSON_UNESCAPED_UNICODE);

        $sql = "INSERT INTO gestao_social.auditoria (
                    usuario_id, usuario_identificador, data_hora, operacao,
                    tabela_afetada, registro_id, dados_novos, ip_origem
                ) VALUES (
                    :usuario_id, :identificador, :data_hora, :operacao,
                    :tabela, :registro_id, :dados_novos::jsonb, :ip
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'usuario_id' => $registro->usuarioId ?: null,
            'identificador' => $registro->usuarioNome ?: 'Sistema',
            'data_hora' => $registro->dataHora ?: date('Y-m-d H:i:s'),
            'operacao' => $registro->operacao,
            'tabela' => 'gestao_social.' . strtolower($registro->entidade),
            'registro_id' => is_numeric($registro->registroId) ? (int)$registro->registroId : null,
            'dados_novos' => $detalhesJson,
            'ip' => $ip,
        ]);

        $registro->id = (int)$stmt->fetchColumn();
        return $registro;
    }

    private function mapRowToModel(array $row): RegistroAuditoria
    {
        $detalhes = '';
        if (!empty($row['dados_novos'])) {
            $data = is_string($row['dados_novos']) ? json_decode($row['dados_novos'], true) : $row['dados_novos'];
            if (isset($data['detalhes'])) {
                $detalhes = (string)$data['detalhes'];
            }
        }

        // Simplifica nome da tabela afetada
        $tabela = str_replace('gestao_social.', '', (string)$row['tabela_afetada']);
        $entidade = ucfirst($tabela);

        return new RegistroAuditoria(
            (int)$row['id'],
            (int)($row['usuario_id'] ?? 0),
            $row['usuario_nome'] ?? $row['usuario_identificador'] ?? 'Usuário',
            substr((string)$row['data_hora'], 0, 19),
            $row['operacao'],
            $entidade,
            $row['registro_id'] !== null ? (int)$row['registro_id'] : 'N/A',
            $detalhes
        );
    }
}
