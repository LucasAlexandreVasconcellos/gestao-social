<?php

declare(strict_types=1);

namespace App\Models;

final class Produto
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nome,
        public string $categoria, // Alimentos, Higiene e Limpeza, Vestuário, Medicamentos/Primeiros Socorros, Outros
        public string $unidadeMedida, // Unidade, Kg, Litro, Pacote, Fardo, Caixa
        public float $estoqueAtual,
        public float $estoqueMinimo,
        public ?string $proximoVencimento = null,
        public bool $ativo = true
    ) {}

    public function isEstoqueBaixo(): bool
    {
        return $this->estoqueAtual <= $this->estoqueMinimo;
    }

    public function toArray(): array
    {
        $arr = get_object_vars($this);
        $arr['isEstoqueBaixo'] = $this->isEstoqueBaixo();
        return $arr;
    }
}
