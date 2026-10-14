<?php

declare(strict_types=1);

namespace App\Models;

final class ParticipanteProjeto
{
    public function __construct(
        public int $id,
        public int $projetoId,
        public ?int $familiaId,
        public ?int $pessoaId,
        public string $nomePessoa,
        public string $situacaoParticipacao = 'Inscrito', // Inscrito, Convocado, Efetivamente Atendido, Ausente
        public ?string $dataPresenca = null,
        public ?string $observacoes = null
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
