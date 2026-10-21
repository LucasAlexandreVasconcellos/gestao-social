<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\DocumentoRepositoryInterface;
use App\Core\Database;
use App\Models\Documento;
use PDO;

final class DocumentoRepositoryDb implements DocumentoRepositoryInterface
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

    public function findById(int $id): ?Documento
    {
        $stmt = $this->db->prepare("
            SELECT * FROM gestao_social.documentos
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEntidade(string $entidadeTipo, int $entidadeId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM gestao_social.documentos
            WHERE entidade_tipo = :tipo AND entidade_id = :entidade_id
            ORDER BY data_upload DESC
        ");
        $stmt->execute([
            'tipo' => $entidadeTipo,
            'entidade_id' => $entidadeId
        ]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($row);
        }
        return $results;
    }

    public function save(Documento $documento): Documento
    {
        if ($documento->id <= 0) {
            $stmt = $this->db->prepare("
                INSERT INTO gestao_social.documentos (
                    entidade_tipo, entidade_id, tipo_documento, descricao,
                    nome_original, nome_arquivo, caminho_relativo, mime_type,
                    tamanho_bytes, usuario_upload_id, data_upload
                ) VALUES (
                    :entidade_tipo, :entidade_id, :tipo_documento, :descricao,
                    :nome_original, :nome_arquivo, :caminho_relativo, :mime_type,
                    :tamanho_bytes, :usuario_upload_id, :data_upload
                ) RETURNING id
            ");
            $stmt->execute([
                'entidade_tipo' => $documento->entidadeTipo,
                'entidade_id' => $documento->entidadeId,
                'tipo_documento' => $documento->tipoDocumento,
                'descricao' => $documento->descricao,
                'nome_original' => $documento->nomeOriginal,
                'nome_arquivo' => $documento->nomeArquivo,
                'caminho_relativo' => $documento->caminhoRelativo,
                'mime_type' => $documento->mimeType,
                'tamanho_bytes' => $documento->tamanhoBytes,
                'usuario_upload_id' => $documento->usuarioUploadId,
                'data_upload' => $documento->dataUpload ?: date('Y-m-d H:i:s')
            ]);
            $documento->id = (int)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare("
                UPDATE gestao_social.documentos SET
                    tipo_documento = :tipo_documento,
                    descricao = :descricao
                WHERE id = :id
            ");
            $stmt->execute([
                'tipo_documento' => $documento->tipoDocumento,
                'descricao' => $documento->descricao,
                'id' => $documento->id
            ]);
        }

        return $documento;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM gestao_social.documentos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function hydrate(array $row): Documento
    {
        return new Documento(
            id: (int)$row['id'],
            entidadeTipo: $row['entidade_tipo'],
            entidadeId: (int)$row['entidade_id'],
            tipoDocumento: $row['tipo_documento'],
            descricao: $row['descricao'] ?? '',
            nomeOriginal: $row['nome_original'],
            nomeArquivo: $row['nome_arquivo'],
            caminhoRelativo: $row['caminho_relativo'],
            mimeType: $row['mime_type'],
            tamanhoBytes: (int)$row['tamanho_bytes'],
            usuarioUploadId: isset($row['usuario_upload_id']) ? (int)$row['usuario_upload_id'] : null,
            dataUpload: $row['data_upload']
        );
    }
}
