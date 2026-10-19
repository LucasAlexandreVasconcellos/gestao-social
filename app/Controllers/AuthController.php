<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(Request $request): string
    {
        $usuarios = $this->authService->getAllUsers();

        return View::render('auth/login', [
            'usuarios' => $usuarios,
            'error' => Session::flash('error')
        ], 'layouts/auth');
    }

    public function authenticate(Request $request): void
    {
        $email = trim((string)$request->post('email', ''));
        $password = (string)$request->post('password', '');

        if (empty($email)) {
            Session::flash('error', 'Por favor informe o e-mail de acesso.');
            Response::redirect('/login');
            return;
        }

        // Se o login foi acionado via botão de acesso rápido por perfil (sem campo de senha)
        if ($password === '') {
            $success = $this->authService->loginDemo($email);
            if ($success) {
                Session::flash('success', 'Bem-vindo à Plataforma de Gestão Social e Assistencial!');
                Response::redirect('/dashboard');
                return;
            }
        }

        $success = $this->authService->login($email, $password);

        if (!$success) {
            Session::flash('error', 'Credenciais inválidas ou usuário inativo no sistema.');
            Response::redirect('/login');
            return;
        }

        Session::flash('success', 'Bem-vindo à Plataforma de Gestão Social e Assistencial!');
        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        $this->authService->logout();
        Session::flash('info', 'Sessão encerrada com segurança.');
        Response::redirect('/login');
    }
}
