<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Core\Session;
use App\Models\AvaliacaoSocial;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Simula o armazenamento e consulta de avaliações socioassistenciais.
 */
final class AvaliacaoRepositoryDemo implements AvaliacaoRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_avaliacoes';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $initial = [
                1 => new AvaliacaoSocial(
                    1, 1, '2026-01-15', 'Ana Paula Silva (CRESS 12345/SP)', 700.00, 350.00, 2,
                    'Regular', 'Insegurança Leve', 'Informal/Instável', true, false, false,
                    ['alimentacao', 'vestuario'], true, 'Alta', 'Renda per capita inferior a 1/2 salário mínimo e presença de criança em idade escolar.',
                    'Família monoparental chefiada por mulher diarista com trabalho esporádico. Indicada inclusão em programas de transferência de renda e entrega mensal de cesta de alimentos.',
                    '2026-07-15'
                ),
                2 => new AvaliacaoSocial(
                    2, 2, '2026-02-20', 'Marcos Vinicius (CRESS 54321/SP)', 1412.00, 706.00, 2,
                    'Boa', 'Segura', 'Informal/Instável', false, false, false,
                    ['habitacao'], false, 'Média', 'Renda no limite de subsistência, aluguel compromete 60% do orçamento.',
                    'Núcleo familiar estável no momento, porém vulnerável a oscilações no comércio ambulante. Acompanhamento trimestral.',
                    '2026-08-20'
                ),
                3 => new AvaliacaoSocial(
                    3, 3, '2026-03-05', 'Ana Paula Silva (CRESS 12345/SP)', 350.00, 175.00, 2,
                    'Precária', 'Insegurança Moderada', 'Desemprego total', true, false, false,
                    ['alimentacao', 'saude', 'habitacao', 'higiene'], true, 'Crítica', 'Desemprego total, gestação de risco e moradia em área de risco.',
                    'Situação de extrema urgência. Necessidade imediata de cesta básica, kit gestante/enxoval e encaminhamento prioritário para a UBS e CRAS.',
                    '2026-04-05' // Próxima reavaliação em curto prazo
                ),
                4 => new AvaliacaoSocial(
                    4, 5, '2026-03-02', 'Marcos Vinicius (CRESS 54321/SP)', 0.00, 0.00, 3,
                    'Precária', 'Insegurança Grave', 'Desemprego total', true, false, false,
                    ['alimentacao', 'higiene', 'vestuario', 'educacao'], true, 'Crítica', 'Ausência completa de renda monetária, 2 crianças pequenas.',
                    'Família em extrema vulnerabilidade. Mãe relata escassez severa de alimentos há dias. Encaminhamento de urgência para benefício alimentar emergencial.',
                    '2026-04-02'
                )
            ];

            Session::set(self::SESSION_KEY, $initial);
        }
    }

    public function findAll(array $filters = []): array
    {
        $avaliacoes = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['familiaId'])) {
            $famId = (int)$filters['familiaId'];
            $avaliacoes = array_filter($avaliacoes, fn(AvaliacaoSocial $a) => $a->familiaId === $famId);
        }

        if (!empty($filters['nivelVulnerabilidade'])) {
            $nivel = $filters['nivelVulnerabilidade'];
            $avaliacoes = array_filter($avaliacoes, fn(AvaliacaoSocial $a) => $a->nivelVulnerabilidade === $nivel);
        }

        // Ordenar por data decrescente
        usort($avaliacoes, fn(AvaliacaoSocial $a, AvaliacaoSocial $b) => strcmp($b->dataAvaliacao, $a->dataAvaliacao));

        return array_values($avaliacoes);
    }

    public function findById(int $id): ?AvaliacaoSocial
    {
        $avaliacoes = Session::get(self::SESSION_KEY, []);
        return $avaliacoes[$id] ?? null;
    }

    public function findByFamiliaId(int $familiaId): array
    {
        return $this->findAll(['familiaId' => $familiaId]);
    }

    public function getUltimaAvaliacao(int $familiaId): ?AvaliacaoSocial
    {
        $avaliacoes = $this->findByFamiliaId($familiaId);
        return !empty($avaliacoes) ? $avaliacoes[0] : null;
    }

    public function save(AvaliacaoSocial $avaliacao): AvaliacaoSocial
    {
        $avaliacoes = Session::get(self::SESSION_KEY, []);
        if ($avaliacao->id <= 0) {
            $maxId = empty($avaliacoes) ? 0 : max(array_keys($avaliacoes));
            $avaliacao->id = $maxId + 1;
        }

        $avaliacoes[$avaliacao->id] = $avaliacao;
        Session::set(self::SESSION_KEY, $avaliacoes);
        return $avaliacao;
    }

    public function getReavaliacoesPendentes(int $diasAntecedencia = 30): array
    {
        $avaliacoes = Session::get(self::SESSION_KEY, []);
        $limiteTimestamp = time() + ($diasAntecedencia * 86400);

        $pendentes = array_filter($avaliacoes, function (AvaliacaoSocial $a) use ($limiteTimestamp) {
            if (empty($a->dataPrevistaReavaliacao)) {
                return false;
            }
            $prevTimestamp = strtotime($a->dataPrevistaReavaliacao);
            return $prevTimestamp && $prevTimestamp <= $limiteTimestamp;
        });

        return array_values($pendentes);
    }

    public function countByVulnerabilidade(string $nivel): int
    {
        $avaliacoes = Session::get(self::SESSION_KEY, []);
        $count = 0;
        foreach ($avaliacoes as $a) {
            if (strcasecmp($a->nivelVulnerabilidade, $nivel) === 0) {
                $count++;
            }
        }
        return $count;
    }
}
