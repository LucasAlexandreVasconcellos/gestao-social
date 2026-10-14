<?php

declare(strict_types=1);

namespace App\Models;

final class Atendimento
{
    public function __construct(
        public int $id,
        public int $familiaId,
        public ?int $pessoaId,
        public string $tipo, // Atendimento Social, Atendimento Emergencial, Orientação, Visita Domiciliar
        public string $dataAtendimento,
        public string $profissionalResponsavel,
        public string $descricao,
        public string $providencias,
        public ?string $retornoPrevisto,
        public string $situacao = 'Concluído', // Pendente, Em Andamento, Concluído, Cancelado
        public ?string $dataConclusao = null,
        public array $encaminhamentos = [] // Array de Encaminhamento
    ) {}

    public function toArray(): array
    {
        $arr = get_object_vars($this);
        $arr['encaminhamentos'] = array_map(fn($e) => $e instanceof Encaminhamento ? $e->toArray() : $e, $this->encaminhamentos);
        return $arr;
    }
}
