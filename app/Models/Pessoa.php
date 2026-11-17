<?php

declare(strict_types=1);

namespace App\Models;

final class Pessoa
{
    public function __construct(
        public int $id,
        public string $nomeCompleto,
        public ?string $nomeSocial,
        public string $dataNascimento,
        public ?string $cpf,
        public ?string $telefone,
        public string $escolaridade,
        public ?string $ocupacao,
        public string $situacaoOcupacional,
        public ?string $observacoes = null,
        public string $situacaoCadastro = 'Ativo',
        public ?string $dataCadastro = null
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
