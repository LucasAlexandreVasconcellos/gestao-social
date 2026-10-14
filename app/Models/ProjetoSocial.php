<?php

declare(strict_types=1);

namespace App\Models;

final class ProjetoSocial
{
    public function __construct(
        public int $id,
        public string $nome,
        public string $tipo, // Campanha, Evento, Oficina, Projeto Social, Distribuição Coletiva
        public string $publicoAlvo,
        public string $dataInicio,
        public ?string $dataEncerramento,
        public string $local,
        public string $responsavel,
        public float $orcamentoPrevisto,
        public float $valorRealizado,
        public int $metaBeneficiarios,
        public string $situacao = 'Em Andamento', // Planejada, Em Andamento, Concluída, Cancelada
        public ?string $descricao = null,
        public array $participantes = [] // Array de ParticipanteProjeto
    ) {}

    public function getQuantidadeInscritos(): int
    {
        return count($this->participantes);
    }

    public function getQuantidadeAtendidos(): int
    {
        return count(array_filter($this->participantes, fn($p) => ($p instanceof ParticipanteProjeto ? $p->situacaoParticipacao : $p['situacaoParticipacao'] ?? '') === 'Efetivamente Atendido'));
    }

    public function toArray(): array
    {
        $arr = get_object_vars($this);
        $arr['quantidadeInscritos'] = $this->getQuantidadeInscritos();
        $arr['quantidadeAtendidos'] = $this->getQuantidadeAtendidos();
        $arr['participantes'] = array_map(fn($p) => $p instanceof ParticipanteProjeto ? $p->toArray() : $p, $this->participantes);
        return $arr;
    }
}
