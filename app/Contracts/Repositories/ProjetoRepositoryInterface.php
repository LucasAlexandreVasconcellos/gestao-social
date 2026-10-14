<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\ProjetoSocial;
use App\Models\ParticipanteProjeto;

interface ProjetoRepositoryInterface
{
    /**
     * @return ProjetoSocial[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?ProjetoSocial;

    public function save(ProjetoSocial $projeto): ProjetoSocial;

    public function update(ProjetoSocial $projeto): bool;

    public function addParticipante(ParticipanteProjeto $participante): ParticipanteProjeto;

    public function updateParticipante(ParticipanteProjeto $participante): bool;

    /**
     * @return ParticipanteProjeto[]
     */
    public function getParticipantes(int $projetoId): array;

    public function countEmAndamento(): int;

    public function countTotal(array $filters = []): int;
}
