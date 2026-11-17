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
        return View::render('auth/login', [
            'error' => Session::flash('error'),
            'info' => Session::flash('info'),
        ], 'layouts/auth');
    }

    public function authenticate(Request $request): void
    {
        $email = trim((string)$request->post('email', ''));
        $password = (string)$request->post('password', '');

        if (empty($email)) {
            Session::flash('error', 'Por favor informe o e-mail ou usuário de acesso.');
            Response::redirect('/login');
            return;
        }

        if (empty($password)) {
            Session::flash('error', 'Por favor informe a senha de acesso.');
            Response::redirect('/login');
            return;
        }

        $success = $this->authService->login($email, $password);

        if (!$success) {
            Session::flash('error', $this->authService->getLastLoginError() ?? 'Credenciais inválidas ou usuário inativo no sistema.');
            Response::redirect('/login');
            return;
        }

        if ($this->authService->hasPending2fa()) {
            Response::redirect('/login/2fa');
            return;
        }

        $user = $this->authService->getCurrentUser();
        if ($user && $user->trocarSenhaObrigatorio) {
            Session::flash('info', 'Por política de segurança institucional, defina uma nova senha pessoal para continuar.');
            Response::redirect('/meu-perfil/senha');
            return;
        }

        Session::flash('success', 'Sessão autenticada na Plataforma de Gestão Socioassistencial.');
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
            $user = $this->authService->getCurrentUser();
            if ($user && $user->trocarSenhaObrigatorio) {
                Session::flash('info', 'Autenticação 2FA validada. Defina uma nova senha pessoal para concluir o primeiro acesso.');
                Response::redirect('/meu-perfil/senha');
                return;
            }
            Session::flash('success', 'Autenticação em dois fatores confirmada com sucesso!');
            Response::redirect('/dashboard');
            return;
        }

        if (!$this->authService->hasPending2fa()) {
            Session::flash('error', 'Número máximo de tentativas de 2FA excedido. Realize o login novamente.');
            Response::redirect('/login');
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

    public function changePasswordForm(Request $request): string
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            Response::redirect('/login');
            return '';
        }

        return View::render('auth/change_password', [
            'user' => $user,
            'obrigatoria' => $user->trocarSenhaObrigatorio,
            'errors' => Session::flash('errors') ?? [],
            'error' => Session::flash('error'),
            'info' => Session::flash('info'),
            'success' => Session::flash('success')
        ]);
    }

    public function changePassword(Request $request): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $current = (string)$request->post('senhaAtual', '');
        $new = (string)$request->post('novaSenha', '');
        $confirm = (string)$request->post('confirmarSenha', '');

        $result = $this->authService->changePassword($user->id, $current, $new, $confirm, true);

        if (!$result['success']) {
            Session::flash('errors', $result['errors'] ?? []);
            Session::flash('error', 'Verifique os critérios de segurança da nova senha.');
            Response::redirect('/meu-perfil/senha');
            return;
        }

        Session::flash('success', 'Senha alterada com sucesso!');
        Response::redirect('/dashboard');
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
            Session::flash('error', 'Código TOTP incorreto. Verifique o relógio do seu dispositivo e digite os 6 dígitos gerados pelo autenticador.');
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

        $senhaAtual = (string)$request->post('senhaAtual', '');
        if ($senhaAtual === '') {
            Session::flash('error', 'Informe sua senha atual para confirmar a desativação do 2FA.');
            Response::redirect('/meu-perfil/2fa');
            return;
        }

        $res = $this->authService->disableTwoFactor($user->id, $senhaAtual);
        if (!$res['success']) {
            Session::flash('error', $res['error'] ?? 'Não foi possível desativar o 2FA.');
        } else {
            Session::flash('info', 'Autenticação em dois fatores desativada com sucesso.');
        }
        Response::redirect('/meu-perfil/2fa');
    }

    public function logout(Request $request): void
    {
        $this->authService->logout();
        Session::flash('info', 'Sessão encerrada com segurança.');
        Response::redirect('/login');
    }
}
