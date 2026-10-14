<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Contracts\Repositories\ProjetoRepositoryInterface;

final class RelatorioService
{
    public function __construct(
        private FamiliaRepositoryInterface $familiaRepo,
        private PessoaRepositoryInterface $pessoaRepo,
        private AvaliacaoRepositoryInterface $avaliacaoRepo,
        private AtendimentoRepositoryInterface $atendimentoRepo,
        private BeneficioRepositoryInterface $beneficioRepo,
        private EstoqueRepositoryInterface $estoqueRepo,
        private DoacaoRepositoryInterface $doacaoRepo,
        private ProjetoRepositoryInterface $projetoRepo
    ) {}

    public function relatorioFamilias(array $filters = []): array
    {
        $familias = $this->familiaRepo->findAll($filters);
        $dados = [];

        foreach ($familias as $f) {
            $integrantes = $this->familiaRepo->getIntegrantes($f->id, true);
            $ultimaAvaliacao = $this->avaliacaoRepo->getUltimaAvaliacao($f->id);

            $dados[] = [
                'codigo' => $f->codigo,
                'responsavel' => $f->pessoaReferenciaNome,
                'bairro' => $f->bairro,
                'situacao' => $f->situacaoCadastral,
                'integrantesQtd' => count($integrantes),
                'rendaFamiliar' => $f->rendaFamiliar,
                'rendaPerCapita' => $ultimaAvaliacao ? $ultimaAvaliacao->rendaPerCapita : (count($integrantes) > 0 ? round($f->rendaFamiliar / count($integrantes), 2) : 0.0),
                'vulnerabilidade' => $ultimaAvaliacao ? $ultimaAvaliacao->nivelVulnerabilidade : 'Não avaliada',
                'moradia' => $f->situacaoMoradia
            ];
        }

        return $dados;
    }

    public function relatorioPessoasFaixaEtaria(): array
    {
        $pessoas = $this->pessoaRepo->findAll();
        $faixas = [
            '0 a 6 anos (Primeira Infância)' => 0,
            '7 a 14 anos (Crianças)' => 0,
            '15 a 17 anos (Adolescentes)' => 0,
            '18 a 29 anos (Jovens)' => 0,
            '30 a 59 anos (Adultos)' => 0,
            '60 anos ou mais (Idosos)' => 0,
            'Não informada' => 0
        ];

        $hoje = new \DateTime();

        foreach ($pessoas as $p) {
            if (empty($p->dataNascimento)) {
                $faixas['Não informada']++;
                continue;
            }

            try {
                $nasc = new \DateTime($p->dataNascimento);
                $idade = $hoje->diff($nasc)->y;

                if ($idade <= 6) {
                    $faixas['0 a 6 anos (Primeira Infância)']++;
                } elseif ($idade <= 14) {
                    $faixas['7 a 14 anos (Crianças)']++;
                } elseif ($idade <= 17) {
                    $faixas['15 a 17 anos (Adolescentes)']++;
                } elseif ($idade <= 29) {
                    $faixas['18 a 29 anos (Jovens)']++;
                } elseif ($idade <= 59) {
                    $faixas['30 a 59 anos (Adultos)']++;
                } else {
                    $faixas['60 anos ou mais (Idosos)']++;
                }
            } catch (\Exception) {
                $faixas['Não informada']++;
            }
        }

        return $faixas;
    }

    public function relatorioEntregasBeneficios(array $filters = []): array
    {
        $beneficios = $this->beneficioRepo->findAll($filters);
        $dados = [];

        foreach ($beneficios as $b) {
            $familia = $this->familiaRepo->findById($b->familiaId);
            $dados[] = [
                'id' => $b->id,
                'data' => $b->dataEntrega,
                'familiaCodigo' => $familia ? $familia->codigo : 'N/A',
                'familiaNome' => $familia ? $familia->pessoaReferenciaNome : 'N/A',
                'bairro' => $familia ? $familia->bairro : 'N/A',
                'tipo' => $b->tipoBeneficio,
                'situacao' => $b->situacao,
                'itensQtd' => count($b->itens),
                'responsavel' => $b->responsavelDistribuicao
            ];
        }

        return $dados;
    }

    public function relatorioEstoque(): array
    {
        $produtos = $this->estoqueRepo->findAllProdutos();
        $dados = [];

        foreach ($produtos as $p) {
            $dados[] = [
                'codigo' => $p->codigo,
                'nome' => $p->nome,
                'categoria' => $p->categoria,
                'unidade' => $p->unidadeMedida,
                'estoqueAtual' => $p->estoqueAtual,
                'estoqueMinimo' => $p->estoqueMinimo,
                'statusEstoque' => $p->isEstoqueBaixo() ? 'CRÍTICO' : 'NORMAL',
                'vencimento' => $p->proximoVencimento ?? 'N/A'
            ];
        }

        return $dados;
    }

    public function exportarCsv(array $cabecalhos, array $linhas, string $filename = 'relatorio.csv'): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para compatibilidade perfeita com Excel no Windows
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, $cabecalhos, ';');

        foreach ($linhas as $linha) {
            fputcsv($output, $linha, ';');
        }

        fclose($output);
        exit;
    }
}
