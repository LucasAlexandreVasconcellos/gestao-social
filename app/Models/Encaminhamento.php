<?php

declare(strict_types=1);

namespace App\Models;

final class Encaminhamento
{
    public function __construct(
        public int $id,
        public int $atendimentoId,
        public string $servicoDestino, // Ex: CRAS, CREAS, Posto de Saúde, Conselho Tutelar, CAPS, SINE
        public string $motivo,
        public string $responsavel,
        public string $data,
        public string $situacao = 'Pendente', // Pendente, Em Acompanhamento, Concluído, Sem Resposta
        public ?string $resultado = null
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
