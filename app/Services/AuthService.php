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

    public function loginDemo(string $email): bool
    {
        $user = $this->usuarioRepo->findByEmail($email);
        if (!$user || !$user->ativo) {
            return false;
        }

        Session::set('user', $user->toArray());
        $this->auditLog->log('Login Demonstrativo', 'Usuario', $user->id, "Acesso concedido para {$user->nome} ({$user->perfil})");
        return true;
    }

    public function logout(): void
    {
        $user = $this->getCurrentUser();
        if ($user) {
            $this->auditLog->log('Logout', 'Usuario', $user->id, "Encerramento de sessão");
        }
        Session::remove('user');
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
