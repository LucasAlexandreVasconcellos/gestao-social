<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\DocumentoRepositoryInterface;
use App\Core\Session;
use App\Models\Documento;

final class DocumentoRepositoryDemo implements DocumentoRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_documentos';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $initialDocs = [
                1 => new Documento(
                    id: 1,
                    entidadeTipo: 'familia',
                    entidadeId: 1,
                    tipoDocumento: 'comprovante_residencia',
                    descricao: 'Conta de Energia Elétrica (Comprovante de Endereço)',
                    nomeOriginal: 'conta_energia_janeiro_2026.pdf',
                    nomeArquivo: 'demo_doc_1.pdf',
                    caminhoRelativo: 'storage/documentos/demo_doc_1.pdf',
                    mimeType: 'application/pdf',
                    tamanhoBytes: 245760,
                    usuarioUploadId: 1,
                    dataUpload: date('Y-m-d H:i:s', strtotime('-10 days'))
                ),
                2 => new Documento(
                    id: 2,
                    entidadeTipo: 'pessoa',
                    entidadeId: 1,
                    tipoDocumento: 'comprovante_renda',
                    descricao: 'Carteira de Trabalho Digital / Extrato CNIS',
                    nomeOriginal: 'extrato_cnis_trabalho.pdf',
                    nomeArquivo: 'demo_doc_2.pdf',
                    caminhoRelativo: 'storage/documentos/demo_doc_2.pdf',
                    mimeType: 'application/pdf',
                    tamanhoBytes: 512000,
                    usuarioUploadId: 1,
                    dataUpload: date('Y-m-d H:i:s', strtotime('-5 days'))
                ),
            ];
            Session::set(self::SESSION_KEY, $initialDocs);
        }
    }

    public function findById(int $id): ?Documento
    {
        $docs = Session::get(self::SESSION_KEY, []);
        return $docs[$id] ?? null;
    }

    public function findByEntidade(string $entidadeTipo, int $entidadeId): array
    {
        $docs = Session::get(self::SESSION_KEY, []);
        $results = [];
        foreach ($docs as $doc) {
            if ($doc->entidadeTipo === $entidadeTipo && $doc->entidadeId === $entidadeId) {
                $results[] = $doc;
            }
        }
        usort($results, fn($a, $b) => strcmp($b->dataUpload, $a->dataUpload));
        return $results;
    }

    public function save(Documento $documento): Documento
    {
        $docs = Session::get(self::SESSION_KEY, []);
        if ($documento->id <= 0) {
            $nextId = empty($docs) ? 1 : max(array_keys($docs)) + 1;
            $documento->id = $nextId;
        }
        $docs[$documento->id] = $documento;
        Session::set(self::SESSION_KEY, $docs);
        return $documento;
    }

    public function delete(int $id): bool
    {
        $docs = Session::get(self::SESSION_KEY, []);
        if (isset($docs[$id])) {
            unset($docs[$id]);
            Session::set(self::SESSION_KEY, $docs);
            return true;
        }
        return false;
    }
}
