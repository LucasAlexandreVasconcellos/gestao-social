<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Core\Session;
use App\Models\Usuario;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * Exclusivo para execução isolada da suíte de testes automatizados via CLI.
 */
final class UsuarioRepositoryDemo implements UsuarioRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_usuarios';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $demoHash = password_hash('demo123', PASSWORD_BCRYPT);
            $initial = [
                1 => new Usuario(1, 'Administrador do Sistema', 'admin@gestaosocial.org', Usuario::PERFIL_ADMIN, true, '2026-03-14 09:30:00', $demoHash, false, null, [], 'admin'),
                2 => new Usuario(2, 'Fernanda Coordenadora', 'coordenacao@gestaosocial.org', Usuario::PERFIL_COORDENACAO, true, '2026-03-14 08:45:00', $demoHash, false, null, [], 'coordenacao'),
                3 => new Usuario(3, 'Ana Paula Silva (CRESS 12345)', 'social@gestaosocial.org', Usuario::PERFIL_ASSISTENTE_SOCIAL, true, '2026-03-14 10:15:00', $demoHash, false, null, [], 'social'),
                4 => new Usuario(4, 'Robson Almoxarife', 'estoque@gestaosocial.org', Usuario::PERFIL_ESTOQUE, true, '2026-03-13 16:20:00', $demoHash, false, null, [], 'estoque'),
                5 => new Usuario(5, 'Juliana Recepção', 'atendimento@gestaosocial.org', Usuario::PERFIL_ATENDIMENTO, true, '2026-03-14 11:00:00', $demoHash, false, null, [], 'atendimento'),
                6 => new Usuario(6, 'Carlos Auditor Externo', 'consulta@gestaosocial.org', Usuario::PERFIL_CONSULTA, true, '2026-03-10 14:00:00', $demoHash, false, null, [], 'consulta'),
            ];

            Session::set(self::SESSION_KEY, $initial);
        }
    }

    public function findAll(): array
    {
        return array_values(Session::get(self::SESSION_KEY, []));
    }

    public function findById(int $id): ?Usuario
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        return $usuarios[$id] ?? null;
    }

    public function findByEmail(string $email): ?Usuario
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        $clean = trim($email);
        foreach ($usuarios as $u) {
            if (strcasecmp($u->email, $clean) === 0 || ($u->login !== '' && strcasecmp($u->login, $clean) === 0)) {
                return $u;
            }
        }
        return null;
    }

    public function save(Usuario $usuario): Usuario
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if ($usuario->id <= 0) {
            $maxId = empty($usuarios) ? 0 : max(array_keys($usuarios));
            $usuario->id = $maxId + 1;
        }
        if ($usuario->login === '') {
            $usuario->login = explode('@', strtolower($usuario->email))[0] ?: 'user' . $usuario->id;
        }

        $usuarios[$usuario->id] = $usuario;
        Session::set(self::SESSION_KEY, $usuarios);
        return $usuario;
    }

    public function update(Usuario $usuario): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$usuario->id])) {
            return false;
        }

        $usuarios[$usuario->id] = $usuario;
        Session::set(self::SESSION_KEY, $usuarios);
        return true;
    }

    public function updatePassword(int $userId, string $passwordHash, bool $forceChange = false): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return false;
        }
        $usuarios[$userId]->senhaHash = $passwordHash;
        $usuarios[$userId]->trocarSenhaObrigatorio = $forceChange;
        $usuarios[$userId]->tentativasLogin = 0;
        $usuarios[$userId]->bloqueadoAte = null;
        Session::set(self::SESSION_KEY, $usuarios);
        return true;
    }

    public function toggleActive(int $userId, bool $active): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return false;
        }
        $usuarios[$userId]->ativo = $active;
        Session::set(self::SESSION_KEY, $usuarios);
        return true;
    }

    public function recordFailedLogin(int $userId, int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return;
        }
        $usuarios[$userId]->tentativasLogin++;
        if ($usuarios[$userId]->tentativasLogin >= $maxAttempts) {
            $usuarios[$userId]->bloqueadoAte = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
        }
        Session::set(self::SESSION_KEY, $usuarios);
    }

    public function resetLoginAttempts(int $userId): void
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return;
        }
        $usuarios[$userId]->tentativasLogin = 0;
        $usuarios[$userId]->bloqueadoAte = null;
        Session::set(self::SESSION_KEY, $usuarios);
    }

    public function updateLastAccess(int $userId): void
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return;
        }
        $usuarios[$userId]->ultimoAcesso = date('Y-m-d H:i:s');
        Session::set(self::SESSION_KEY, $usuarios);
    }

    public function setTwoFactorSecret(int $userId, string $secret, array $backupCodes): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return false;
        }

        $usuarios[$userId]->doisFatoresSecret = $secret;
        $usuarios[$userId]->doisFatoresBackupCodes = $backupCodes;
        Session::set(self::SESSION_KEY, $usuarios);
        return true;
    }

    public function enableTwoFactor(int $userId): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return false;
        }

        $usuarios[$userId]->doisFatoresAtivo = true;
        Session::set(self::SESSION_KEY, $usuarios);
        return true;
    }

    public function disableTwoFactor(int $userId): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return false;
        }

        $usuarios[$userId]->doisFatoresAtivo = false;
        $usuarios[$userId]->doisFatoresSecret = null;
        $usuarios[$userId]->doisFatoresBackupCodes = [];
        Session::set(self::SESSION_KEY, $usuarios);
        return true;
    }

    public function consumeBackupCode(int $userId, string $code): bool
    {
        $usuarios = Session::get(self::SESSION_KEY, []);
        if (!isset($usuarios[$userId])) {
            return false;
        }

        $cleanTarget = strtoupper(str_replace(['-', ' '], '', trim($code)));
        $codes = $usuarios[$userId]->doisFatoresBackupCodes ?? [];

        foreach ($codes as $idx => $storedCode) {
            $cleanStored = strtoupper(str_replace(['-', ' '], '', trim($storedCode)));
            if ($cleanStored === $cleanTarget) {
                unset($codes[$idx]);
                $usuarios[$userId]->doisFatoresBackupCodes = array_values($codes);
                Session::set(self::SESSION_KEY, $usuarios);
                return true;
            }
        }

        return false;
    }
}
