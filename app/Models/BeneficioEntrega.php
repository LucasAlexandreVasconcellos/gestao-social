<?php

declare(strict_types=1);

namespace App\Models;

final class BeneficioEntrega
{
    public function __construct(
        public int $id,
        public int $familiaId,
        public string $dataEntrega,
        public string $tipoBeneficio, // Cesta Básica, Kit Higiene, Kit Enxoval, Roupas/Agasalhos, Auxílio Emergencial
        public string $responsavelDistribuicao,
        public ?int $campanhaId = null,
        public array $itens = [], // Array de ItemEntrega
        public ?string $comprovante = null,
        public ?string $observacoes = null,
        public string $situacao = 'Efetivada' // Preparada, Efetivada, Cancelada
    ) {}

    public function toArray(): array
    {
        $arr = get_object_vars($this);
        $arr['itens'] = array_map(fn($it) => $it instanceof ItemEntrega ? $it->toArray() : $it, $this->itens);
        return $arr;
    }
}
