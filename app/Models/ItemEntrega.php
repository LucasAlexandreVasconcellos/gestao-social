<?php

declare(strict_types=1);

namespace App\Models;

final class ItemEntrega
{
    public function __construct(
        public int $id,
        public int $entregaId,
        public int $produtoId,
        public string $nomeProduto,
        public float $quantidade,
        public string $unidadeMedida,
        public ?string $lote = null
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
