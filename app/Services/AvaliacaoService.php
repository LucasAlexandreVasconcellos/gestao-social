<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Models\AvaliacaoSocial;
use App\Validators\FormValidator;

final class AvaliacaoService
{
    public function __construct(
        private AvaliacaoRepositoryInterface $avaliacaoRepo,
        private FamiliaRepositoryInterface $familiaRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->avaliacaoRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?AvaliacaoSocial
    {
        return $this->avaliacaoRepo->findById($id);
    }

    public function obterHistoricoFamilia(int $familiaId): array
    {
        return $this->avaliacaoRepo->findByFamiliaId($familiaId);
    }

    public function obterReavaliacoesPendentes(int $dias = 30): array
    {
        return $this->avaliacaoRepo->getReavaliacoesPendentes($dias);
    }

    /**
     * Calcula a sugestão preliminar indicativa de vulnerabilidade sem substituir
     * o julgamento técnico soberano da assistente social.
     */
    public function calcularSugestaoVulnerabilidade(
        float $rendaPerCapita,
        string $situacaoAlimentar,
        string $condicoesMoradia,
        string $situacaoEmprego,
        bool $temCriancas,
        bool $temIdosos,
        bool $temPcd,
        array $necessidades
    ): string {
        $pontos = 0;

        // Renda per capita (Salário Mínimo referência 2026: R$ 1.500)
        if ($rendaPerCapita <= 0) {
            $pontos += 4;
        } elseif ($rendaPerCapita <= 375.00) { // Menor que 1/4 SM (extrema pobreza)
            $pontos += 3;
        } elseif ($rendaPerCapita <= 750.00) { // Menor que 1/2 SM (pobreza)
            $pontos += 2;
        }

        // Situação alimentar
        if ($situacaoAlimentar === 'Insegurança Grave') {
            $pontos += 4;
        } elseif ($situacaoAlimentar === 'Insegurança Moderada') {
            $pontos += 2;
        } elseif ($situacaoAlimentar === 'Insegurança Leve') {
            $pontos += 1;
        }

        // Condições de moradia
        if ($condicoesMoradia === 'Área de Risco' || $condicoesMoradia === 'Precária') {
            $pontos += 2;
        }

        // Emprego
        if ($situacaoEmprego === 'Desemprego total') {
            $pontos += 2;
        }

        // Composição sensível
        if ($temCriancas) $pontos += 1;
        if ($temIdosos) $pontos += 1;
        if ($temPcd) $pontos += 1;

        // Necessidades
        $pontos += count($necessidades);

        if ($pontos >= 9) {
            return 'Crítica';
        }
        if ($pontos >= 6) {
            return 'Alta';
        }
        if ($pontos >= 3) {
            return 'Média';
        }
        return 'Baixa';
    }

    public function registrar(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('familiaId', 'Família')
            ->numeric('familiaId', 'Família')
            ->required('responsavelAvaliacao', 'Responsável pela Avaliação')
            ->required('dataAvaliacao', 'Data da Avaliação')
            ->date('dataAvaliacao', 'Data da Avaliação')
            ->required('condicoesMoradia', 'Condições de Moradia')
            ->required('situacaoAlimentar', 'Situação Alimentar')
            ->required('situacaoEmprego', 'Situação de Emprego')
            ->required('nivelVulnerabilidade', 'Nível de Vulnerabilidade')
            ->inList('nivelVulnerabilidade', ['Baixa', 'Média', 'Alta', 'Crítica'], 'Nível de Vulnerabilidade')
            ->required('parecerSocial', 'Parecer Social')
            ->minLength('parecerSocial', 10, 'Parecer Social');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $familiaId = (int)$dados['familiaId'];
        $familia = $this->familiaRepo->findById($familiaId);
        if (!$familia) {
            return ['success' => false, 'errors' => ['familiaId' => 'Família selecionada não existe.']];
        }

        $integrantes = $this->familiaRepo->getIntegrantes($familiaId, true);
        $qtdIntegrantes = max(1, count($integrantes));
        $rendaPerCapita = $familia->rendaFamiliar > 0 ? round($familia->rendaFamiliar / $qtdIntegrantes, 2) : 0.0;

        $necessidades = $dados['necessidadesPrioritarias'] ?? [];
        if (!is_array($necessidades)) {
            $necessidades = [];
        }

        $avaliacao = new AvaliacaoSocial(
            0,
            $familiaId,
            trim($dados['dataAvaliacao']),
            trim($dados['responsavelAvaliacao']),
            $familia->rendaFamiliar,
            $rendaPerCapita,
            $qtdIntegrantes,
            trim($dados['condicoesMoradia']),
            trim($dados['situacaoAlimentar']),
            trim($dados['situacaoEmprego']),
            !empty($dados['temCriancas']),
            !empty($dados['temIdosos']),
            !empty($dados['temPcd']),
            $necessidades,
            !empty($dados['necessidadeAcompanhamento']),
            trim($dados['nivelVulnerabilidade']),
            trim($dados['justificativaClassificacao'] ?? '') ?: null,
            trim($dados['parecerSocial']),
            trim($dados['dataPrevistaReavaliacao'] ?? '') ?: null
        );

        $saved = $this->avaliacaoRepo->save($avaliacao);

        $this->auditLog->log('Criar', 'AvaliacaoSocial', $saved->id, "Avaliação social registrada para família {$familia->codigo}. Classificação: {$saved->nivelVulnerabilidade}");

        return ['success' => true, 'avaliacao' => $saved];
    }
}
