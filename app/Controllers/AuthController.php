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
                if ($this->authService->hasPending2fa()) {
                    Response::redirect('/login/2fa');
                    return;
                }
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

        if ($this->authService->hasPending2fa()) {
            Response::redirect('/login/2fa');
            return;
        }

        Session::flash('success', 'Bem-vindo à Plataforma de Gestão Social e Assistencial!');
        Response::redirect('/dashboard');
    }

    public function login2fa(Request $request): string
    {
        if (!$this->authService->hasPending2fa()) {
            Response::redirect('/login');
            return '';
        }

        $pendingUser = $this->authService->getPending2faUser();
        $isBackup = (bool)$request->query('backup', false);

        return View::render('auth/login_2fa', [
            'pendingUser' => $pendingUser,
            'isBackup' => $isBackup,
            'error' => Session::flash('error')
        ], 'layouts/auth');
    }

    public function verify2fa(Request $request): void
    {
        if (!$this->authService->hasPending2fa()) {
            Response::redirect('/login');
            return;
        }

        $code = trim((string)$request->post('code', ''));
        $isBackup = (bool)$request->post('is_backup', false);

        if (empty($code)) {
            Session::flash('error', 'Por favor informe o código.');
            Response::redirect('/login/2fa' . ($isBackup ? '?backup=1' : ''));
            return;
        }

        $valid = $this->authService->verify2faLogin($code, $isBackup);

        if ($valid) {
            Session::flash('success', 'Autenticação em dois fatores confirmada com sucesso!');
            Response::redirect('/dashboard');
            return;
        }

        Session::flash('error', 'Código de verificação incorreto ou expirado. Tente novamente.');
        Response::redirect('/login/2fa' . ($isBackup ? '?backup=1' : ''));
    }

    public function cancel2fa(Request $request): void
    {
        $this->authService->cancelPending2fa();
        Session::flash('info', 'Autenticação cancelada.');
        Response::redirect('/login');
    }

    public function twoFactorProfile(Request $request): string
    {
        $user = $this->authService->getCurrentUserFull();
        if (!$user) {
            Response::redirect('/login');
            return '';
        }

        $setup = null;
        if (!$user->doisFatoresAtivo) {
            $setup = $this->authService->prepareTwoFactorSetup($user->id);
        }

        return View::render('usuarios/dois_fatores', [
            'user' => $user,
            'setup' => $setup,
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
            'info' => Session::flash('info')
        ]);
    }

    public function confirmTwoFactor(Request $request): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $code = trim((string)$request->post('code', ''));
        if (empty($code)) {
            Session::flash('error', 'Informe o código numérico de 6 dígitos.');
            Response::redirect('/meu-perfil/2fa');
            return;
        }

        $ok = $this->authService->confirmTwoFactorSetup($user->id, $code);

        if ($ok) {
            Session::flash('success', 'Segundo Fator de Autenticação (2FA / TOTP) ativado com sucesso!');
        } else {
            Session::flash('error', 'Código TOTP incorreto. Verifique o relógio do seu smartphone e digite os 6 dígitos gerados pelo autenticador.');
        }

        Response::redirect('/meu-perfil/2fa');
    }

    public function disableTwoFactor(Request $request): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $this->authService->disableTwoFactor($user->id);
        Session::flash('info', 'Autenticação em dois fatores desativada com sucesso.');
        Response::redirect('/meu-perfil/2fa');
    }

    public function logout(Request $request): void
    {
        $this->authService->logout();
        Session::flash('info', 'Sessão encerrada com segurança.');
        Response::redirect('/login');
    }
}
