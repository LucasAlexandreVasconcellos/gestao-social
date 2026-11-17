<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Contracts\Repositories\ProjetoRepositoryInterface;
use App\Core\Request;
use App\Core\View;
use App\Services\AuthService;

final class DashboardController
{
    public function __construct(
        private FamiliaRepositoryInterface $familiaRepo,
        private PessoaRepositoryInterface $pessoaRepo,
        private AvaliacaoRepositoryInterface $avaliacaoRepo,
        private AtendimentoRepositoryInterface $atendimentoRepo,
        private BeneficioRepositoryInterface $beneficioRepo,
        private EstoqueRepositoryInterface $estoqueRepo,
        private DoacaoRepositoryInterface $doacaoRepo,
        private ProjetoRepositoryInterface $projetoRepo,
        private AuthService $authService
    ) {}

    public function index(Request $request): string
    {
        $totalFamilias = $this->familiaRepo->countTotal();
        $familiasAtivas = $this->familiaRepo->countBySituacao('Ativa');
        $familiasAnalise = $this->familiaRepo->countBySituacao('Em Análise');

        $familiasCriticas = $this->avaliacaoRepo->countByVulnerabilidade('Crítica');
        $familiasAltas = $this->avaliacaoRepo->countByVulnerabilidade('Alta');
        $familiasMedias = $this->avaliacaoRepo->countByVulnerabilidade('Média');
        $familiasBaixas = $this->avaliacaoRepo->countByVulnerabilidade('Baixa');

        $totalPessoas = $this->pessoaRepo->countTotal();
        $atendimentosPendentes = $this->atendimentoRepo->countPendentes();
        $beneficiosEntregues = $this->beneficioRepo->countEfetivadas();
        $produtosEstoqueBaixo = $this->estoqueRepo->countProdutosCriticos();
        $produtosVencendo = $this->estoqueRepo->countProdutosVencendo();
        $doacoesValor = $this->doacaoRepo->sumValorFinanceiro();
        $projetosAtivos = $this->projetoRepo->countEmAndamento();
        $reavaliacoesPendentes = count($this->avaliacaoRepo->getReavaliacoesPendentes(30));

        // Distribuição territorial por bairro para visualização gráfica
        $familiasTodas = $this->familiaRepo->findAll();
        $porBairro = [];
        foreach ($familiasTodas as $f) {
            $b = trim($f->bairro) ?: 'Não informado';
            $porBairro[$b] = ($porBairro[$b] ?? 0) + 1;
        }
        arsort($porBairro);
        $topBairros = array_slice($porBairro, 0, 5, true);

        $alertasVencimento = $this->estoqueRepo->getProdutosProximoVencimento(45);
        $alertasEstoque = $this->estoqueRepo->getProdutosEstoqueBaixo();
        $ultimosAtendimentos = array_slice($this->atendimentoRepo->findAll(), 0, 5);
        $ultimasEntregas = array_slice($this->beneficioRepo->findAll(), 0, 5);

        return View::render('dashboard/index', [
            'currentUser' => $this->authService->getCurrentUser(),
            'totalFamilias' => $totalFamilias,
            'familiasAtivas' => $familiasAtivas,
            'familiasAnalise' => $familiasAnalise,
            'casosVulnerabilidadePrioritaria' => $familiasCriticas + $familiasAltas,
            'familiasCriticas' => $familiasCriticas,
            'distribuicaoVulnerabilidade' => [
                'Crítica' => $familiasCriticas,
                'Alta' => $familiasAltas,
                'Média' => $familiasMedias,
                'Baixa' => $familiasBaixas,
            ],
            'topBairros' => $topBairros,
            'totalPessoas' => $totalPessoas,
            'atendimentosPendentes' => $atendimentosPendentes,
            'beneficiosEntregues' => $beneficiosEntregues,
            'produtosEstoqueBaixo' => $produtosEstoqueBaixo,
            'produtosVencendo' => $produtosVencendo,
            'doacoesValor' => $doacoesValor,
            'projetosAtivos' => $projetosAtivos,
            'reavaliacoesPendentes' => $reavaliacoesPendentes,
            'alertasVencimento' => $alertasVencimento,
            'alertasEstoque' => $alertasEstoque,
            'ultimosAtendimentos' => $ultimosAtendimentos,
            'ultimasEntregas' => $ultimasEntregas
        ]);
    }
}
