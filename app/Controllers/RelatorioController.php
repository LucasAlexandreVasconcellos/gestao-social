<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Services\FamiliaService;
use App\Services\RelatorioService;

final class RelatorioController
{
    public function __construct(
        private RelatorioService $relatorioService,
        private FamiliaService $familiaService
    ) {}

    public function index(Request $request): string
    {
        return View::render('relatorios/index');
    }

    public function view(Request $request): string
    {
        $tipo = $request->query('tipo', 'familias');
        $bairros = $this->familiaService->getBairros();

        $dados = [];
        $titulo = 'Relatório';

        switch ($tipo) {
            case 'familias':
                $titulo = 'Relatório de Famílias por Bairro e Vulnerabilidade';
                $dados = $this->relatorioService->relatorioFamilias([
                    'bairro' => $request->query('bairro'),
                    'situacao' => $request->query('situacao')
                ]);
                break;

            case 'faixa_etaria':
                $titulo = 'Relatório de Pessoas Atendidas por Faixa Etária';
                $dados = $this->relatorioService->relatorioPessoasFaixaEtaria();
                break;

            case 'entregas':
                $titulo = 'Relatório de Benefícios e Mantimentos Distribuídos';
                $dados = $this->relatorioService->relatorioEntregasBeneficios([
                    'situacao' => $request->query('situacao')
                ]);
                break;

            case 'estoque':
                $titulo = 'Relatório de Posição de Estoque e Validades';
                $dados = $this->relatorioService->relatorioEstoque();
                break;
        }

        return View::render('relatorios/view', [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'dados' => $dados,
            'bairros' => $bairros,
            'filters' => $request->allQuery()
        ]);
    }

    public function export(Request $request): void
    {
        $tipo = $request->query('tipo', 'familias');

        if ($tipo === 'familias') {
            $dados = $this->relatorioService->relatorioFamilias($request->allQuery());
            $cabecalhos = ['Código', 'Responsável Familiar', 'Bairro', 'Situação Cadastral', 'Qtd Integrantes', 'Renda Familiar', 'Renda Per Capita', 'Nível Vulnerabilidade', 'Moradia'];
            $linhas = array_map(fn($d) => [
                $d['codigo'],
                $d['responsavel'],
                $d['bairro'],
                $d['situacao'],
                $d['integrantesQtd'],
                number_format($d['rendaFamiliar'], 2, ',', '.'),
                number_format($d['rendaPerCapita'], 2, ',', '.'),
                $d['vulnerabilidade'],
                $d['moradia']
            ], $dados);
            $this->relatorioService->exportarCsv($cabecalhos, $linhas, 'relatorio_familias_' . date('Ymd_His') . '.csv');
        } elseif ($tipo === 'entregas') {
            $dados = $this->relatorioService->relatorioEntregasBeneficios($request->allQuery());
            $cabecalhos = ['ID', 'Data Entrega', 'Código Família', 'Responsável Familiar', 'Bairro', 'Benefício', 'Situação', 'Qtd Itens', 'Responsável'];
            $linhas = array_map(fn($d) => [
                $d['id'],
                $d['data'],
                $d['familiaCodigo'],
                $d['familiaNome'],
                $d['bairro'],
                $d['tipo'],
                $d['situacao'],
                $d['itensQtd'],
                $d['responsavel']
            ], $dados);
            $this->relatorioService->exportarCsv($cabecalhos, $linhas, 'relatorio_entregas_' . date('Ymd_His') . '.csv');
        } elseif ($tipo === 'estoque') {
            $dados = $this->relatorioService->relatorioEstoque();
            $cabecalhos = ['Código', 'Produto', 'Categoria', 'Unidade', 'Estoque Atual', 'Estoque Mínimo', 'Status', 'Validade'];
            $linhas = array_map(fn($d) => [
                $d['codigo'],
                $d['nome'],
                $d['categoria'],
                $d['unidade'],
                $d['estoqueAtual'],
                $d['estoqueMinimo'],
                $d['statusEstoque'],
                $d['vencimento']
            ], $dados);
            $this->relatorioService->exportarCsv($cabecalhos, $linhas, 'relatorio_estoque_' . date('Ymd_His') . '.csv');
        }
    }
}
