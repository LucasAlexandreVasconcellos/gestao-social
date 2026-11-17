<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Usuario;

final class RoleMiddleware
{
    public function handle(Request $request, ?string $permission = null): bool
    {
        $userData = Session::get('user');
        if (!$userData) {
            Response::redirect('/login');
            return false;
        }

        $user = new Usuario(
            (int)$userData['id'],
            (string)$userData['nome'],
            (string)$userData['email'],
            (string)$userData['perfil'],
            (bool)($userData['ativo'] ?? true)
        );

        if (!$user->ativo) {
            Session::destroy();
            Session::flash('error', 'Acesso negado: Sua conta de usuário foi inativada.');
            Response::redirect('/login');
            return false;
        }

        // Bloqueio universal de mutações para perfil estritamente somente leitura
        if ($request->isPost() && $user->perfil === Usuario::PERFIL_CONSULTA) {
            http_response_code(403);
            echo View::render('errors/403', [
                'message' => 'Usuários com perfil Consulta possuem acesso estritamente somente leitura.'
            ], 'layouts/main');
            return false;
        }

        // Verificação estrita da permissão exigida pela rota (GET e POST)
        if ($permission !== null && !$user->can($permission)) {
            http_response_code(403);
            echo View::render('errors/403', [
                'message' => "Seu perfil ({$user->perfil}) não possui autorização para executar esta operação ({$permission})."
            ], 'layouts/main');
            return false;
        }

        return true;
    }
}
