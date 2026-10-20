<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Notificacao;

interface NotificacaoRepositoryInterface
{
    public function findById(int $id): ?Notificacao;

    /**
     * @return Notificacao[]
     */
    public function findAll(array $filters = []): array;

    public function save(Notificacao $notificacao): Notificacao;
}
