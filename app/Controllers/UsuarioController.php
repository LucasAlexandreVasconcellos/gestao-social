<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditLogService;
use App\Services\AuthService;

final class UsuarioController
{
    public function __construct(
        private AuthService $authService,
        private AuditLogService $auditLogService,
        private \App\Services\LgpdService $lgpdService
    ) {}

    public function index(Request $request): string
    {
        $currentUser = $this->authService->getCurrentUser();
        if (!$currentUser || !in_array($currentUser->perfil, [\App\Models\Usuario::PERFIL_ADMIN, \App\Models\Usuario::PERFIL_COORDENACAO], true)) {
            Session::flash('error', 'Acesso negado: Visualização do quadro de usuários e permissões é restrita à Administração e Coordenação.');
            Response::redirect('/dashboard');
        }

        $usuarios = $this->authService->getAllUsers();

        return View::render('usuarios/index', [
            'usuarios' => $usuarios,
            'currentUser' => $currentUser
        ]);
    }

    public function auditoria(Request $request): string
    {
        $currentUser = $this->authService->getCurrentUser();
        if (!$currentUser || !in_array($currentUser->perfil, [\App\Models\Usuario::PERFIL_ADMIN, \App\Models\Usuario::PERFIL_COORDENACAO], true)) {
            Session::flash('error', 'Acesso negado: Visualização da trilha de auditoria é restrita à Administração e Coordenação.');
            Response::redirect('/dashboard');
        }

        $filters = [
            'entidade' => $request->query('entidade'),
            'operacao' => $request->query('operacao')
        ];

        $registros = $this->auditLogService->listarTodos($filters);

        return View::render('usuarios/auditoria', [
            'registros' => $registros,
            'filters' => $filters
        ]);
    }

    public function switchRole(Request $request): void
    {
        $currentUser = $this->authService->getCurrentUser();
        $isDemo = !\App\Core\Database::isConnected() || (getenv('APP_DEMO_MODE') === 'true');

        // Em ambiente de produção real (banco conectado), alternar perfil exige perfil de Administrador
        if (!$isDemo && (!$currentUser || $currentUser->perfil !== \App\Models\Usuario::PERFIL_ADMIN)) {
            Session::flash('error', 'Acesso negado: A alternância rápida de perfis está restrita ao Administrador no ambiente com banco de dados.');
            Response::redirect('/dashboard');
            return;
        }

        $userId = (int)$request->post('userId');
        $result = $this->authService->switchRole($userId);

        if ($result) {
            $user = $this->authService->getCurrentUser();
            Session::flash('info', "Perfil alternado para: {$user->nome} ({$user->perfil}) com sucesso.");
        } else {
            Session::flash('error', 'Usuário selecionado não encontrado.');
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        Response::redirect($referer);
    }

    public function lgpdIndex(Request $request): string
    {
        $currentUser = $this->authService->getCurrentUser();
        if (!$currentUser || !in_array($currentUser->perfil, [\App\Models\Usuario::PERFIL_ADMIN, \App\Models\Usuario::PERFIL_COORDENACAO], true)) {
            Session::flash('error', 'Acesso negado: O painel de governança LGPD é restrito à Administração e Coordenação.');
            Response::redirect('/dashboard');
        }

        $inativos = $this->lgpdService->buscarInativos(5);
        $solicitacoes = $this->lgpdService->listarSolicitacoes();

        return View::render('usuarios/lgpd_anonimizacao', [
            'currentUser' => $currentUser,
            'inativos' => $inativos,
            'solicitacoes' => $solicitacoes,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function anonimizar(Request $request): void
    {
        $currentUser = $this->authService->getCurrentUser();
        if (!$currentUser || !in_array($currentUser->perfil, [\App\Models\Usuario::PERFIL_ADMIN, \App\Models\Usuario::PERFIL_COORDENACAO], true)) {
            Session::flash('error', 'Acesso negado para operação de anonimização.');
            Response::redirect('/dashboard');
            return;
        }

        $pessoaId = (int)$request->post('pessoaId');
        $motivo = trim((string)$request->post('motivo', 'Solicitação expressa do titular (Art. 18 LGPD)'));
        $expurgarDocumentos = (bool)$request->post('expurgarDocumentos', true);

        if ($pessoaId <= 0) {
            Session::flash('error', 'Informe o ID ou código do titular a ser anonimizado.');
            Response::redirect('/auditoria/lgpd');
            return;
        }

        $result = $this->lgpdService->anonimizarPessoa($pessoaId, $motivo, $currentUser->id, $expurgarDocumentos);

        if (!$result['success']) {
            Session::flash('error', $result['error'] ?? 'Erro ao processar anonimização.');
        } else {
            Session::flash('success', "Titular anonimizado com sucesso! Protocolo oficial gerado: {$result['protocolo']}.");
        }

        Response::redirect('/auditoria/lgpd');
    }
}
