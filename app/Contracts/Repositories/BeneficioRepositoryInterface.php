<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BeneficioEntrega;
use App\Models\ItemEntrega;

interface BeneficioRepositoryInterface
{
    /**
     * @return BeneficioEntrega[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?BeneficioEntrega;

    /**
     * @return BeneficioEntrega[]
     */
    public function findByFamiliaId(int $familiaId): array;

    public function save(BeneficioEntrega $entrega): BeneficioEntrega;

    public function update(BeneficioEntrega $entrega): bool;

    public function addItem(ItemEntrega $item): ItemEntrega;

    public function countEfetivadas(string $mesAno = ''): int;

    public function countTotal(array $filters = []): int;
}
