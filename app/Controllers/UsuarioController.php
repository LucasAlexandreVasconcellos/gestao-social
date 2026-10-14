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
        $usuarios = $this->authService->getAllUsers();
        $currentUser = $this->authService->getCurrentUser();

        return View::render('usuarios/index', [
            'usuarios' => $usuarios,
            'currentUser' => $currentUser
        ]);
    }

    public function auditoria(Request $request): string
    {
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
