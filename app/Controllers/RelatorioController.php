<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Helpers\FormatHelper;
use App\Services\AuditLogService;
use App\Services\FamiliaService;
use App\Services\RelatorioService;

final class RelatorioController
{
    public function __construct(
        private RelatorioService $relatorioService,
        private FamiliaService $familiaService,
        private AuditLogService $auditLogService
    ) {}

    public function index(Request $request): string
    {
        $indicadores = $this->relatorioService->obterIndicadoresGerais();

        $this->auditLogService->log(
            'CONSULTA_RELATORIO',
            'Relatorio',
            'painel_gerencial',
            'Acesso ao painel gerencial de inteligência socioassistencial e governança LGPD.'
        );

        return View::render('relatorios/index', [
            'indicadores' => $indicadores
        ]);
    }

    public function view(Request $request): string
    {
        $tipo = $request->query('tipo', 'familias');
        $bairros = $this->familiaService->getBairros();
        // Por padrão no modo visualização, preserva a privacidade de nomes sensíveis (Privacy by Default)
        $anonimizado = (bool)$request->query('anonimizado', 1);

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

        $modoDesc = $anonimizado ? 'Anonimizado / Pseudonimizado (LGPD)' : 'Nominal / Identificado (Sensível)';
        $this->auditLogService->log(
            'CONSULTA_RELATORIO',
            'Relatorio',
            $tipo,
            "Visualização detalhada do relatório [{$tipo}] em modo {$modoDesc} com filtros: " . json_encode($request->allQuery(), JSON_UNESCAPED_UNICODE)
        );

        return View::render('relatorios/view', [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'dados' => $dados,
            'bairros' => $bairros,
            'filters' => $request->allQuery(),
            'anonimizado' => $anonimizado
        ]);
    }

    public function export(Request $request): void
    {
        $tipo = $request->query('tipo', 'familias');
        // Suporte ao parâmetro anonimizado (padrão = 1 para conformidade LGPD)
        $anonimizado = (bool)$request->query('anonimizado', 1);

        $modoDesc = $anonimizado ? 'Anonimizado / Pseudonimizado (LGPD Art. 13)' : 'Nominal Identificado (Uso Técnico Restrito)';
        $this->auditLogService->log(
            'EXPORTACAO_CSV',
            'Relatorio',
            $tipo,
            "Download de arquivo CSV do relatório [{$tipo}] em modo {$modoDesc}."
        );

        if ($tipo === 'familias') {
            $dados = $this->relatorioService->relatorioFamilias($request->allQuery());
            $cabecalhos = [
                'Código',
                $anonimizado ? 'Responsável Familiar (Anonimizado LGPD)' : 'Responsável Familiar',
                'Bairro',
                'Situação Cadastral',
                'Qtd Integrantes',
                'Renda Familiar (R$)',
                'Renda Per Capita (R$)',
                'Nível Vulnerabilidade',
                'Moradia'
            ];
            $linhas = array_map(function($d) use ($anonimizado) {
                $resp = $anonimizado 
                    ? FormatHelper::anonymizeName($d['responsavel']) . ' (' . $d['codigo'] . ')'
                    : $d['responsavel'];

                return [
                    $d['codigo'],
                    $resp,
                    $d['bairro'],
                    $d['situacao'],
                    $d['integrantesQtd'],
                    number_format($d['rendaFamiliar'], 2, ',', '.'),
                    number_format($d['rendaPerCapita'], 2, ',', '.'),
                    $d['vulnerabilidade'],
                    $d['moradia']
                ];
            }, $dados);

            $filename = 'relatorio_familias_' . ($anonimizado ? 'lgpd_anonimizado_' : 'nominal_') . date('Ymd_His') . '.csv';
            $this->relatorioService->exportarCsv($cabecalhos, $linhas, $filename);

        } elseif ($tipo === 'entregas') {
            $dados = $this->relatorioService->relatorioEntregasBeneficios($request->allQuery());
            $cabecalhos = [
                'ID',
                'Data Entrega',
                'Código Família',
                $anonimizado ? 'Família Receptora (Anonimizada LGPD)' : 'Família Receptora',
                'Bairro',
                'Benefício',
                'Situação',
                'Qtd Itens',
                'Responsável Operacional'
            ];
            $linhas = array_map(function($d) use ($anonimizado) {
                $resp = $anonimizado
                    ? FormatHelper::anonymizeName($d['familiaNome']) . ' (' . $d['familiaCodigo'] . ')'
                    : $d['familiaNome'];

                return [
                    $d['id'],
                    $d['data'],
                    $d['familiaCodigo'],
                    $resp,
                    $d['bairro'],
                    $d['tipo'],
                    $d['situacao'],
                    $d['itensQtd'],
                    $d['responsavel']
                ];
            }, $dados);

            $filename = 'relatorio_entregas_' . ($anonimizado ? 'lgpd_anonimizado_' : 'nominal_') . date('Ymd_His') . '.csv';
            $this->relatorioService->exportarCsv($cabecalhos, $linhas, $filename);

        } elseif ($tipo === 'estoque') {
            $dados = $this->relatorioService->relatorioEstoque();
            $cabecalhos = ['Código', 'Produto', 'Categoria', 'Unidade', 'Estoque Atual', 'Estoque Mínimo', 'Status de Reposição', 'Próximo Vencimento'];
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

            $filename = 'relatorio_estoque_' . date('Ymd_His') . '.csv';
            $this->relatorioService->exportarCsv($cabecalhos, $linhas, $filename);
        }
    }
}
