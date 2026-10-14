<?php

declare(strict_types=1);

namespace App\Models;

final class MovimentacaoEstoque
{
    public function __construct(
        public int $id,
        public string $data,
        public int $produtoId,
        public string $produtoNome,
        public string $tipo, // Entrada, Saída, Ajuste, Perda
        public float $quantidade,
        public ?string $lote = null,
        public ?string $dataValidade = null,
        public ?string $origemDestino = null,
        public string $justificativa = '',
        public string $responsavel = ''
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
