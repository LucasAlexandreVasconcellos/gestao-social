<?php

declare(strict_types=1);

namespace App\Models;

final class Documento
{
    public function __construct(
        public int $id,
        public string $entidadeTipo,
        public int $entidadeId,
        public string $tipoDocumento,
        public string $descricao,
        public string $nomeOriginal,
        public string $nomeArquivo,
        public string $caminhoRelativo,
        public string $mimeType,
        public int $tamanhoBytes,
        public ?int $usuarioUploadId,
        public string $dataUpload,
        public string $hashSha256 = ''
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
