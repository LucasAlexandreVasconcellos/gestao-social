<?php

declare(strict_types=1);

namespace App\Models;

final class Doacao
{
    public function __construct(
        public int $id,
        public string $tipoDoador, // Pessoa Física, Pessoa Jurídica, Anônimo
        public string $nomeDoador,
        public ?string $documento = null, // CPF ou CNPJ
        public ?string $telefone = null,
        public ?string $email = null,
        public string $tipoDoacao, // Produtos, Financeira, Serviços/Outros
        public string $dataRecebimento,
        public ?float $valorEstimado = null,
        public string $descricao = '',
        public ?string $comprovante = null,
        public string $responsavel = '',
        public array $itens = [] // Array de ItemDoacao
    ) {}

    public function toArray(): array
    {
        $arr = get_object_vars($this);
        $arr['itens'] = array_map(fn($it) => $it instanceof ItemDoacao ? $it->toArray() : $it, $this->itens);
        return $arr;
    }
}
