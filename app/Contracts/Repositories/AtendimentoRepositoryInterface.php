<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Atendimento;
use App\Models\Encaminhamento;

interface AtendimentoRepositoryInterface
{
    /**
     * @return Atendimento[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?Atendimento;

    /**
     * @return Atendimento[]
     */
    public function findByFamiliaId(int $familiaId): array;

    public function save(Atendimento $atendimento): Atendimento;

    public function update(Atendimento $atendimento): bool;

    public function addEncaminhamento(Encaminhamento $encaminhamento): Encaminhamento;

    /**
     * @return Encaminhamento[]
     */
    public function getEncaminhamentos(int $atendimentoId): array;

    public function countPendentes(): int;

    public function countTotal(array $filters = []): int;
}
