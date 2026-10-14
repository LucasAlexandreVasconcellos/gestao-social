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
        public string $escolaridade, // Fundamental Incompleto, Fundamental Completo, Médio, Superior, etc.
        public ?string $ocupacao,
        public string $situacaoOcupacional, // Empregado, Desempregado, Autônomo, Aposentado, etc.
        public ?string $observacoes = null,
        public string $situacaoCadastro = 'Ativo' // Ativo, Inativo
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
