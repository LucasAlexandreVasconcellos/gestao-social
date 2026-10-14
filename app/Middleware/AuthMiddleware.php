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

        return true;
    }
}
