<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\RegistroAuditoria;

interface AuditoriaRepositoryInterface
{
    /**
     * @return RegistroAuditoria[]
     */
    public function findAll(array $filters = [], int $limit = 100): array;

    public function save(RegistroAuditoria $registro): RegistroAuditoria;
}
