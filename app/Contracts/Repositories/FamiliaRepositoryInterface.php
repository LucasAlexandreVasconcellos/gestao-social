<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Familia;
use App\Models\VinculoFamiliar;

interface FamiliaRepositoryInterface
{
    /**
     * @return Familia[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?Familia;

    public function findByCodigo(string $codigo): ?Familia;

    public function save(Familia $familia): Familia;

    public function update(Familia $familia): bool;

    /**
     * @return VinculoFamiliar[]
     */
    public function getIntegrantes(int $familiaId, bool $apenasAtivos = true): array;

    public function addIntegrante(VinculoFamiliar $vinculo): VinculoFamiliar;

    public function updateIntegrante(VinculoFamiliar $vinculo): bool;

    public function countTotal(array $filters = []): int;

    public function countBySituacao(string $situacao): int;

    public function getBairros(): array;
}
