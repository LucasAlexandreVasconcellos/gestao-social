<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\NotificacaoRepositoryInterface;
use App\Core\Database;
use App\Models\Notificacao;
use PDO;

final class NotificacaoRepositoryDb implements NotificacaoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Notificacao
    {
        $stmt = $this->db->prepare("
            SELECT * FROM gestao_social.notificacoes
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM gestao_social.notificacoes WHERE 1=1";
        $params = [];

        if (!empty($filters['entidadeTipo'])) {
            $sql .= " AND entidade_tipo = :entidadeTipo";
            $params['entidadeTipo'] = $filters['entidadeTipo'];
        }
        if (!empty($filters['entidadeId'])) {
            $sql .= " AND entidade_id = :entidadeId";
            $params['entidadeId'] = (int)$filters['entidadeId'];
        }
        if (!empty($filters['canal'])) {
            $sql .= " AND canal = :canal";
            $params['canal'] = $filters['canal'];
        }

        $sql .= " ORDER BY data_envio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($row);
        }
        return $results;
    }

    public function save(Notificacao $notificacao): Notificacao
    {
        if ($notificacao->id <= 0) {
            $stmt = $this->db->prepare("
                INSERT INTO gestao_social.notificacoes (
                    entidade_tipo, entidade_id, destinatario_nome, destinatario_contato,
                    canal, tipo_mensagem, mensagem, status, resposta_provedor,
                    usuario_id, data_envio
                ) VALUES (
                    :entidade_tipo, :entidade_id, :destinatario_nome, :destinatario_contato,
                    :canal, :tipo_mensagem, :mensagem, :status, :resposta_provedor,
                    :usuario_id, :data_envio
                ) RETURNING id
            ");
            $stmt->execute([
                'entidade_tipo' => $notificacao->entidadeTipo,
                'entidade_id' => $notificacao->entidadeId,
                'destinatario_nome' => $notificacao->destinatarioNome,
                'destinatario_contato' => $notificacao->destinatarioContato,
                'canal' => $notificacao->canal,
                'tipo_mensagem' => $notificacao->tipoMensagem,
                'mensagem' => $notificacao->mensagem,
                'status' => $notificacao->status,
                'resposta_provedor' => $notificacao->respostaProvedor,
                'usuario_id' => $notificacao->usuarioId,
                'data_envio' => $notificacao->dataEnvio ?: date('Y-m-d H:i:s')
            ]);
            $notificacao->id = (int)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare("
                UPDATE gestao_social.notificacoes SET
                    status = :status,
                    resposta_provedor = :resposta_provedor
                WHERE id = :id
            ");
            $stmt->execute([
                'status' => $notificacao->status,
                'resposta_provedor' => $notificacao->respostaProvedor,
                'id' => $notificacao->id
            ]);
        }

        return $notificacao;
    }

    private function hydrate(array $row): Notificacao
    {
        return new Notificacao(
            id: (int)$row['id'],
            entidadeTipo: $row['entidade_tipo'],
            entidadeId: isset($row['entidade_id']) ? (int)$row['entidade_id'] : null,
            destinatarioNome: $row['destinatario_nome'],
            destinatarioContato: $row['destinatario_contato'],
            canal: $row['canal'],
            tipoMensagem: $row['tipo_mensagem'] ?? 'geral',
            mensagem: $row['mensagem'],
            status: $row['status'] ?? 'enviado',
            respostaProvedor: $row['resposta_provedor'] ?? null,
            usuarioId: isset($row['usuario_id']) ? (int)$row['usuario_id'] : null,
            dataEnvio: $row['data_envio'] ?? date('Y-m-d H:i:s')
        );
    }
}
