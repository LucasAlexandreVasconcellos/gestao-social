<?php

declare(strict_types=1);

namespace App\Models;

final class Documento
{
    public function __construct(
        public int $id,
        public string $entidadeTipo,     // familia, pessoa, avaliacao
        public int $entidadeId,
        public string $tipoDocumento,    // comprovante_residencia, comprovante_renda, laudo_medico_pcd, termo_guarda, certidao, outro
        public string $descricao,
        public string $nomeOriginal,
        public string $nomeArquivo,
        public string $caminhoRelativo,
        public string $mimeType,
        public int $tamanhoBytes,
        public ?int $usuarioUploadId,
        public string $dataUpload
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
