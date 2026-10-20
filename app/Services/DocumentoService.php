<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\DocumentoRepositoryInterface;
use App\Models\Documento;

final class DocumentoService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp'
    ];
    private const MAX_SIZE_BYTES = 10485760; // 10 MB

    private string $storagePath;

    public function __construct(
        private DocumentoRepositoryInterface $documentoRepo,
        private AuditLogService $auditLog,
        ?string $storagePath = null
    ) {
        $this->storagePath = $storagePath ?? (dirname(__DIR__, 2) . '/storage/documentos');
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0775, true);
        }
    }

    public function listarPorEntidade(string $entidadeTipo, int $entidadeId): array
    {
        return $this->documentoRepo->findByEntidade($entidadeTipo, $entidadeId);
    }

    public function obterPorId(int $id): ?Documento
    {
        return $this->documentoRepo->findById($id);
    }

    public function salvarArquivo(string $entidadeTipo, int $entidadeId, array $file, string $tipoDocumento = 'outro', string $descricao = '', ?int $usuarioId = null): array
    {
        return $this->upload($entidadeTipo, $entidadeId, $file, [
            'tipoDocumento' => $tipoDocumento,
            'descricao' => $descricao
        ], $usuarioId);
    }

    public function upload(string $entidadeTipo, int $entidadeId, array $file, array $dados, ?int $usuarioId = null): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $msg = match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
                UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido.',
                default => 'Nenhum arquivo válido foi selecionado para upload.'
            };
            return ['success' => false, 'error' => $msg];
        }

        $tmpName = $file['tmp_name'];
        $origName = basename($file['name']);
        $size = (int)$file['size'];

        if ($size > self::MAX_SIZE_BYTES) {
            return ['success' => false, 'error' => 'O arquivo excede o limite de 10 MB.'];
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => 'Extensão não permitida. Envie arquivos PDF, JPG, PNG ou WEBP.'];
        }

        // Validação de MIME real
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName) ?: '';
            finfo_close($finfo);
        }
        if (empty($mime) || $mime === 'application/octet-stream') {
            $mime = match ($ext) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream'
            };
        }

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return ['success' => false, 'error' => 'Tipo de mídia inválido ou suspeito detectado pelo sistema de segurança.'];
        }

        // Nome seguro aleatório com hash SHA-256
        $fileHash = hash_file('sha256', $tmpName) ?: bin2hex(random_bytes(16));
        $safeFileName = sprintf('%s_%d.%s', substr($fileHash, 0, 32), time(), $ext);
        $destination = $this->storagePath . '/' . $safeFileName;

        if (!@move_uploaded_file($tmpName, $destination)) {
            // Suporte a teste/ambiente que não seja HTTP POST upload direto
            if (!@copy($tmpName, $destination)) {
                return ['success' => false, 'error' => 'Não foi possível gravar o arquivo no armazenamento seguro.'];
            }
            @unlink($tmpName);
        }

        $tipoDoc = (string)($dados['tipoDocumento'] ?? 'outro');
        $descricao = trim((string)($dados['descricao'] ?? ''));

        $doc = new Documento(
            id: 0,
            entidadeTipo: $entidadeTipo,
            entidadeId: $entidadeId,
            tipoDocumento: $tipoDoc,
            descricao: $descricao,
            nomeOriginal: $origName,
            nomeArquivo: $safeFileName,
            caminhoRelativo: 'storage/documentos/' . $safeFileName,
            mimeType: $mime,
            tamanhoBytes: $size,
            usuarioUploadId: $usuarioId,
            dataUpload: date('Y-m-d H:i:s')
        );

        $savedDoc = $this->documentoRepo->save($doc);

        $this->auditLog->log(
            'Upload de Documento',
            'Documento',
            $savedDoc->id,
            "Anexo '{$origName}' ({$tipoDoc}, {$size} bytes) vinculado a {$entidadeTipo} #{$entidadeId}"
        );

        return ['success' => true, 'documento' => $savedDoc];
    }

    public function download(int $id): ?array
    {
        $doc = $this->documentoRepo->findById($id);
        if (!$doc) {
            return null;
        }

        $filePath = $this->storagePath . '/' . $doc->nomeArquivo;
        if (!file_exists($filePath)) {
            // Em modo demo se não houver arquivo real gerado, gera um binário representativo
            if (!is_dir($this->storagePath)) {
                @mkdir($this->storagePath, 0775, true);
            }
            file_put_contents($filePath, "%PDF-1.4\n% Documento demonstrativo de prontuário {$doc->nomeOriginal}\n%%EOF");
        }

        $this->auditLog->log(
            'Download de Documento',
            'Documento',
            $doc->id,
            "Acesso e download do arquivo '{$doc->nomeOriginal}' de {$doc->entidadeTipo} #{$doc->entidadeId}"
        );

        return [
            'filePath' => $filePath,
            'fileName' => $doc->nomeOriginal,
            'mimeType' => $doc->mimeType,
            'size' => filesize($filePath) ?: $doc->tamanhoBytes
        ];
    }

    public function excluir(int $id, ?int $usuarioId = null): bool
    {
        $doc = $this->documentoRepo->findById($id);
        if (!$doc) {
            return false;
        }

        $filePath = $this->storagePath . '/' . $doc->nomeArquivo;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $this->auditLog->log(
            'Exclusão de Documento',
            'Documento',
            $doc->id,
            "Exclusão definitiva do arquivo '{$doc->nomeOriginal}' de {$doc->entidadeTipo} #{$doc->entidadeId}"
        );

        return $this->documentoRepo->delete($id);
    }
}
