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
        private AuditLogService $auditLogService
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
}
