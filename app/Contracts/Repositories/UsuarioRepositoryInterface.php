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
}
