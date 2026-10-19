<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (!Session::has('user')) {
            Session::flash('error', 'Por favor, autentique-se para acessar o sistema.');
            Response::redirect('/login');
            return false;
        }

        if (\App\Core\Database::isConnected()) {
            $user = Session::get('user');
            if (!empty($user['id'])) {
                \App\Core\Database::setAuditContext((int)$user['id'], $user['email'] ?? $user['nome'] ?? null);
            }
        }

        return true;
    }
}
