<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Core\Session;
use App\Models\Usuario;

final class AuthService
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarioRepo,
        private AuditLogService $auditLog
    ) {}

    public function getCurrentUser(): ?Usuario
    {
        $userData = Session::get('user');
        if (!$userData) {
            return null;
        }

        return new Usuario(
            $userData['id'],
            $userData['nome'],
            $userData['email'],
            $userData['perfil'],
            $userData['ativo'] ?? true,
            $userData['ultimoAcesso'] ?? null
        );
    }

    public function check(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    public function user(): ?Usuario
    {
        return $this->getCurrentUser();
    }

    public function switchRole(int $userId): bool
    {
        $user = $this->usuarioRepo->findById($userId);
        if (!$user) {
            return false;
        }

        Session::set('user', $user->toArray());
        $this->auditLog->log('Troca de Perfil Demo', 'Usuario', $user->id, "Sessão alternada para o perfil [{$user->perfil}]");
        return true;
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->usuarioRepo->findByEmail($email);
        if (!$user || !$user->ativo) {
            return false;
        }

        // Se o modelo contiver senhaHash (persistência real ou demo com hash)
        if (!empty($user->senhaHash)) {
            if (!password_verify($password, $user->senhaHash)) {
                return false;
            }
        } else {
            // Repositório demo legado: aceita demo123 ou senhas padrão
            $isDemoRepo = defined(get_class($this->usuarioRepo) . '::IS_DEMO');
            if ($isDemoRepo && !empty($password) && $password !== 'demo123' && $password !== 'admin123') {
                return false;
            }
        }

        // Regenera ID da sessão (OWASP A07 - Prevenção de Session Fixation)
        Session::regenerate(true);
        Session::set('user', $user->toArray());

        // Configura o contexto de auditoria no banco de dados se conectado
        if (\App\Core\Database::isConnected()) {
            \App\Core\Database::setAuditContext($user->id, $user->email);
        }

        $this->auditLog->log('Login', 'Usuario', $user->id, "Acesso autenticado para {$user->nome} ({$user->perfil})");
        return true;
    }

    public function loginDemo(string $email): bool
    {
        $user = $this->usuarioRepo->findByEmail($email);
        if (!$user || !$user->ativo) {
            return false;
        }

        Session::regenerate(true);
        Session::set('user', $user->toArray());

        if (\App\Core\Database::isConnected()) {
            \App\Core\Database::setAuditContext($user->id, $user->email);
        }

        $this->auditLog->log('Login Demonstrativo', 'Usuario', $user->id, "Acesso concedido para {$user->nome} ({$user->perfil})");
        return true;
    }

    public function logout(): void
    {
        $user = $this->getCurrentUser();
        if ($user) {
            $this->auditLog->log('Logout', 'Usuario', $user->id, "Encerramento de sessão");
        }
        Session::destroy();
    }

    public function checkPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user->can($permission);
    }

    public function getAllUsers(): array
    {
        return $this->usuarioRepo->findAll();
    }
}
