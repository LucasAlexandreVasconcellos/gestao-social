<?php

declare(strict_types=1);

namespace App\Models;

final class ItemDoacao
{
    public function __construct(
        public int $id,
        public int $doacaoId,
        public ?int $produtoId,
        public string $nomeProduto,
        public float $quantidade,
        public string $unidadeMedida,
        public ?string $lote = null,
        public ?string $dataValidade = null
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
