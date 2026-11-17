<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\SecurityHelper;
use App\Services\AuthService;
use App\Services\DocumentoService;

final class DocumentoController
{
    public function __construct(
        private DocumentoService $documentoService,
        private AuthService $authService
    ) {}

    public function uploadFamilia(Request $request): void
    {
        $familiaId = (int)$request->param('id');
        $file = $_FILES['documento'] ?? [];
        $dados = [
            'tipoDocumento' => $request->post('tipoDocumento'),
            'descricao' => $request->post('descricao')
        ];
        $user = $this->authService->getCurrentUser();

        if (($dados['tipoDocumento'] ?? '') === 'laudo_medico_pcd' && (!$user || !$user->can('avaliacoes.sensitive'))) {
            Session::flash('error', 'Acesso negado: apenas a equipe técnica autorizada pode anexar laudos médicos/PCD.');
            Response::redirect("/familias/{$familiaId}#documentos");
            return;
        }

        $result = $this->documentoService->upload('familia', $familiaId, $file, $dados, $user ? $user->id : null);

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
        $user = $this->authService->getCurrentUser();

        if (($dados['tipoDocumento'] ?? '') === 'laudo_medico_pcd' && (!$user || !$user->can('avaliacoes.sensitive'))) {
            Session::flash('error', 'Acesso negado: apenas a equipe técnica autorizada pode anexar laudos médicos/PCD.');
            Response::redirect("/pessoas/{$pessoaId}#documentos");
            return;
        }

        $result = $this->documentoService->upload('pessoa', $pessoaId, $file, $dados, $user ? $user->id : null);

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
        $doc = $this->documentoService->obterPorId($id);
        $user = $this->authService->getCurrentUser();

        if (!$doc) {
            http_response_code(404);
            echo View::render('errors/404');
            exit;
        }

        if (!$this->documentoService->canAccessDocumento($doc, $user)) {
            http_response_code(403);
            echo View::render('errors/403', ['permission' => 'documentos.view / avaliacoes.sensitive']);
            exit;
        }

        $docData = $this->documentoService->download($id);
        if (!$docData || !file_exists($docData['filePath'])) {
            http_response_code(404);
            echo View::render('errors/404');
            exit;
        }

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $docData['mimeType']);
            header('Content-Disposition: attachment; filename="' . rawurlencode($docData['fileName']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: private, no-store');
            header('Content-Length: ' . $docData['size']);
        }

        readfile($docData['filePath']);
        exit;
    }

    public function excluir(Request $request): void
    {
        $id = (int)$request->param('id');
        $redirectUrl = SecurityHelper::safeRedirectPath((string)$request->post('redirect', '/dashboard'));
        $user = $this->authService->getCurrentUser();
        $doc = $this->documentoService->obterPorId($id);

        if (!$doc || !$this->documentoService->canAccessDocumento($doc, $user)) {
            Session::flash('error', 'Acesso negado ou documento inexistente.');
            Response::redirect($redirectUrl);
            return;
        }

        $success = $this->documentoService->excluir($id, $user ? $user->id : null);

        if ($success) {
            Session::flash('success', 'Documento removido com sucesso.');
        } else {
            Session::flash('error', 'Não foi possível excluir o documento.');
        }

        Response::redirect($redirectUrl);
    }
}
