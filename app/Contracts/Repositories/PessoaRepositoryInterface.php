<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Pessoa;
use App\Models\VinculoFamiliar;

interface PessoaRepositoryInterface
{
    /**
     * @return Pessoa[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?Pessoa;

    public function findByCpf(string $cpf): ?Pessoa;

    public function search(string $term): array;

    public function save(Pessoa $pessoa): Pessoa;

    public function update(Pessoa $pessoa): bool;

    /**
     * Retorna o histórico de todas as famílias a que a pessoa já esteve vinculada
     * @return VinculoFamiliar[]
     */
    public function getHistoricoVinculosFamiliares(int $pessoaId): array;

    public function countTotal(): int;
}
