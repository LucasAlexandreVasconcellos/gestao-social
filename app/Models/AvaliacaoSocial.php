<?php

declare(strict_types=1);

namespace App\Models;

final class AvaliacaoSocial
{
    public function __construct(
        public int $id,
        public int $familiaId,
        public string $dataAvaliacao,
        public string $responsavelAvaliacao,
        public float $rendaFamiliar,
        public float $rendaPerCapita,
        public int $quantidadeIntegrantes,
        public string $condicoesMoradia, // Boa, Regular, Precária, Área de Risco
        public string $situacaoAlimentar, // Segura, Insegurança Leve, Insegurança Moderada, Insegurança Grave
        public string $situacaoEmprego, // Renda estável, Informal/Instável, Desemprego total
        public bool $temCriancas,
        public bool $temIdosos,
        public bool $temPcd,
        public array $necessidadesPrioritarias, // ['alimentacao', 'saude', 'habitacao', 'vestuario', 'higiene', 'educacao', 'acessibilidade']
        public bool $necessidadeAcompanhamento,
        public string $nivelVulnerabilidade, // Baixa, Média, Alta, Crítica
        public ?string $justificativaClassificacao,
        public string $parecerSocial,
        public ?string $dataPrevistaReavaliacao = null
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
