<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\DoacaoService;
use App\Services\EstoqueService;
use App\Services\PdfService;

final class DoacaoController
{
    public function __construct(
        private DoacaoService $doacaoService,
        private EstoqueService $estoqueService,
        private PdfService $pdfService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'tipo' => $request->query('tipo'),
            'search' => $request->query('search')
        ];

        $doacoes = $this->doacaoService->listar($filters);

        return View::render('doacoes/index', [
            'doacoes' => $doacoes,
            'filters' => $filters
        ]);
    }

    public function create(Request $request): string
    {
        $produtos = $this->estoqueService->listarProdutos();

        return View::render('doacoes/create', [
            'produtos' => $produtos,
            'old' => Session::flash('old_input') ?? ['dataRecebimento' => date('Y-m-d')],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();

        // Tratamento dos itens doados
        $itens = [];
        if (!empty($dados['item_nome']) && is_array($dados['item_nome'])) {
            foreach ($dados['item_nome'] as $idx => $nome) {
                if (!empty($nome) && !empty($dados['item_qtd'][$idx])) {
                    $itens[] = [
                        'produtoId' => !empty($dados['item_produto_id'][$idx]) ? (int)$dados['item_produto_id'][$idx] : null,
                        'nomeProduto' => trim($nome),
                        'quantidade' => $dados['item_qtd'][$idx],
                        'unidadeMedida' => $dados['item_unidade'][$idx] ?? 'Unidade',
                        'lote' => $dados['item_lote'][$idx] ?? null,
                        'dataValidade' => $dados['item_validade'][$idx] ?? null
                    ];
                }
            }
        }
        $dados['itens'] = $itens;

        $result = $this->doacaoService->registrar($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', reset($result['errors']));
            Response::redirect('/doacoes/novo');
            return;
        }

        Session::flash('success', "Doação de {$result['doacao']->nomeDoador} registrada com sucesso!");
        Response::redirect("/doacoes/{$result['doacao']->id}");
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $doacao = $this->doacaoService->obterPorId($id);

        if (!$doacao) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        return View::render('doacoes/show', [
            'doacao' => $doacao
        ]);
    }

    public function reciboPdf(Request $request): void
    {
        $id = (int)$request->param('id');
        $pdf = $this->pdfService->gerarReciboDoacao($id);
        $filename = "recibo_doacao_{$id}.pdf";
        if ($request->query('download') === '1') {
            $pdf->download($filename);
        } else {
            $pdf->inline($filename);
        }
    }
}
