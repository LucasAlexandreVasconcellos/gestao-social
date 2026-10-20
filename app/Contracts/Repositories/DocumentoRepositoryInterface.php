<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Documento;

interface DocumentoRepositoryInterface
{
    public function findById(int $id): ?Documento;

    /**
     * @return Documento[]
     */
    public function findByEntidade(string $entidadeTipo, int $entidadeId): array;

    public function save(Documento $documento): Documento;

    public function delete(int $id): bool;
}
