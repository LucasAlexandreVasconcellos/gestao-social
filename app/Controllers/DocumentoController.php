<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\DocumentoService;

final class DocumentoController
{
    public function __construct(
        private DocumentoService $documentoService
    ) {}

    public function uploadFamilia(Request $request): void
    {
        $familiaId = (int)$request->param('id');
        $file = $_FILES['documento'] ?? [];
        $dados = [
            'tipoDocumento' => $request->post('tipoDocumento'),
            'descricao' => $request->post('descricao')
        ];
        $user = Session::get('user');

        $result = $this->documentoService->upload('familia', $familiaId, $file, $dados, $user['id'] ?? null);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', "Documento '{$result['documento']->nomeOriginal}' anexado com sucesso ao prontuário!");
        }

        Response::redirect("/familias/{$familiaId}#documentos");
    }

    public function uploadPessoa(Request $request): void
    {
        $pessoaId = (int)$request->param('id');
        $file = $_FILES['documento'] ?? [];
        $dados = [
            'tipoDocumento' => $request->post('tipoDocumento'),
            'descricao' => $request->post('descricao')
        ];
        $user = Session::get('user');

        $result = $this->documentoService->upload('pessoa', $pessoaId, $file, $dados, $user['id'] ?? null);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', "Documento '{$result['documento']->nomeOriginal}' anexado com sucesso!");
        }

        Response::redirect("/pessoas/{$pessoaId}#documentos");
    }

    public function download(Request $request): void
    {
        $id = (int)$request->param('id');
        $docData = $this->documentoService->download($id);

        if (!$docData || !file_exists($docData['filePath'])) {
            http_response_code(404);
            echo "Documento não encontrado ou indisponível no servidor.";
            exit;
        }

        if (!headers_sent()) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $docData['mimeType']);
            header('Content-Disposition: attachment; filename="' . rawurlencode($docData['fileName']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $docData['size']);
        }

        readfile($docData['filePath']);
        exit;
    }

    public function excluir(Request $request): void
    {
        $id = (int)$request->param('id');
        $redirectUrl = (string)$request->post('redirect', '/dashboard');
        $user = Session::get('user');

        $success = $this->documentoService->excluir($id, $user['id'] ?? null);

        if ($success) {
            Session::flash('success', 'Documento removido com sucesso.');
        } else {
            Session::flash('error', 'Não foi possível excluir o documento.');
        }

        Response::redirect($redirectUrl);
    }
}
