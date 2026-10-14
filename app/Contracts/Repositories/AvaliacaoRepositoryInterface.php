<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\AvaliacaoSocial;

interface AvaliacaoRepositoryInterface
{
    /**
     * @return AvaliacaoSocial[]
     */
    public function findAll(array $filters = []): array;

    public function findById(int $id): ?AvaliacaoSocial;

    /**
     * @return AvaliacaoSocial[]
     */
    public function findByFamiliaId(int $familiaId): array;

    public function getUltimaAvaliacao(int $familiaId): ?AvaliacaoSocial;

    public function save(AvaliacaoSocial $avaliacao): AvaliacaoSocial;

    /**
     * Retorna avaliações cuja data de reavaliação esteja vencida ou próxima
     * @return AvaliacaoSocial[]
     */
    public function getReavaliacoesPendentes(int $diasAntecedencia = 30): array;

    public function countByVulnerabilidade(string $nivel): int;
}
