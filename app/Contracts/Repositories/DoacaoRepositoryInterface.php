<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Doacao;
use App\Models\ItemDoacao;

interface DoacaoRepositoryInterface
{
    /**
     * @return Doacao[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?Doacao;

    public function save(Doacao $doacao): Doacao;

    public function addItem(ItemDoacao $item): ItemDoacao;

    public function sumValorFinanceiro(string $mesAno = ''): float;

    public function countTotal(array $filters = []): int;
}
