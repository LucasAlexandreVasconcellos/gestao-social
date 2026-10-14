<?php

declare(strict_types=1);

namespace App\Models;

final class VinculoFamiliar
{
    public function __construct(
        public int $id,
        public int $familiaId,
        public int $pessoaId,
        public string $parentesco, // Responsável, Cônjuge/Companheiro, Filho(a), Enteado(a), Pai/Mãe, Irmão(ã), Avô/Avó, Outro
        public bool $isResponsavel,
        public string $dataInicio,
        public ?string $dataTermino = null,
        public bool $ativo = true,
        public ?string $observacoes = null,
        public ?Pessoa $pessoa = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'familiaId' => $this->familiaId,
            'pessoaId' => $this->pessoaId,
            'parentesco' => $this->parentesco,
            'isResponsavel' => $this->isResponsavel,
            'dataInicio' => $this->dataInicio,
            'dataTermino' => $this->dataTermino,
            'ativo' => $this->ativo,
            'observacoes' => $this->observacoes,
            'pessoa' => $this->pessoa?->toArray()
        ];
    }
}
