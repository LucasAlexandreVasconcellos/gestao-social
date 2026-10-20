<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Usuario;

interface UsuarioRepositoryInterface
{
    /**
     * @return Usuario[]
     */
    public function findAll(): array;

    public function findById(int $id): ?Usuario;

    public function findByEmail(string $email): ?Usuario;

    public function save(Usuario $usuario): Usuario;

    public function update(Usuario $usuario): bool;

    public function setTwoFactorSecret(int $userId, string $secret, array $backupCodes): bool;

    public function enableTwoFactor(int $userId): bool;

    public function disableTwoFactor(int $userId): bool;

    public function consumeBackupCode(int $userId, string $code): bool;
}
