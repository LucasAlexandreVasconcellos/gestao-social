<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Usuario;

final class RoleMiddleware
{
    public function handle(Request $request): bool
    {
        $userData = Session::get('user');
        if (!$userData) {
            Response::redirect('/login');
            return false;
        }

        $user = new Usuario(
            $userData['id'],
            $userData['nome'],
            $userData['email'],
            $userData['perfil'],
            $userData['ativo'] ?? true
        );

        if (!$user->ativo) {
            Session::destroy();
            Session::flash('error', 'Acesso negado: Sua conta de usuário foi inativada.');
            Response::redirect('/login');
            return false;
        }

        // Se a requisição for de alteração (POST) e o usuário for do perfil 'Consulta', bloqueia a mutação
        if ($request->isPost() && $user->perfil === Usuario::PERFIL_CONSULTA) {
            Session::flash('error', 'Acesso negado: Usuários com perfil [Consulta] possuem acesso estritamente somente leitura.');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
            Response::redirect($referer);
            return false;
        }

        return true;
    }
}
