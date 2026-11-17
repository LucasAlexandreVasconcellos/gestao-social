<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request, ?string $permission = null): bool
    {
        if (!Session::has('user')) {
            Session::flash('error', 'Por favor, autentique-se para acessar o sistema.');
            Response::redirect('/login');
            return false;
        }

        // Verifica expiração por inatividade de sessão
        if (!Session::touchAndCheckIdleTimeout()) {
            Session::flash('warning', 'Sua sessão expirou por inatividade. Autentique-se novamente.');
            Response::redirect('/login');
            return false;
        }

        $user = Session::get('user');
        if (!is_array($user) || empty($user['id'])) {
            Session::destroy();
            Response::redirect('/login');
            return false;
        }

        // Força troca de senha obrigatória no primeiro acesso / após reset administrativo
        $uri = $request->getUri();
        $isAllowedDuringPasswordReset = in_array($uri, ['/meu-perfil/senha', '/logout'], true);
        if (!empty($user['trocarSenhaObrigatorio']) && !$isAllowedDuringPasswordReset) {
            Session::flash('warning', 'Por segurança institucional, defina uma nova senha pessoal antes de continuar.');
            Response::redirect('/meu-perfil/senha');
            return false;
        }

        if (Database::isConnected()) {
            $identificador = !empty($user['login']) ? $user['login'] : ($user['email'] ?? $user['nome'] ?? null);
            Database::setAuditContext((int)$user['id'], $identificador);
        }

        return true;
    }
}
