<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class CsrfMiddleware
{
    public function handle(Request $request): bool
    {
        if ($request->isPost()) {
            $token = $request->csrfToken();
            if (!Session::validateCsrf($token)) {
                http_response_code(403);
                Session::flash('error', 'Falha na validação de segurança (Token CSRF inválido ou expirado). Por favor tente novamente.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                Response::redirect($referer);
                return false;
            }
        }

        return true;
    }
}
