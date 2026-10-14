<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\EstoqueService;

final class EstoqueController
{
    public function __construct(
        private EstoqueService $estoqueService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'search' => $request->query('search'),
            'categoria' => $request->query('categoria'),
            'estoqueBaixo' => $request->query('baixo') === '1'
        ];

        $produtos = $this->estoqueService->listarProdutos($filters);
        $produtosCriticos = $this->estoqueService->obterProdutosCriticos();
        $produtosVencendo = $this->estoqueService->obterProdutosVencendo(30);

        return View::render('estoque/index', [
            'produtos' => $produtos,
            'filters' => $filters,
            'produtosCriticos' => $produtosCriticos,
            'produtosVencendo' => $produtosVencendo
        ]);
    }

    public function create(Request $request): string
    {
        return View::render('estoque/create', [
            'old' => Session::flash('old_input') ?? [],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->estoqueService->salvarProduto($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário de produto.');
            Response::redirect('/estoque/novo');
            return;
        }

        Session::flash('success', "Produto {$result['produto']->nome} cadastrado com sucesso!");
        Response::redirect('/estoque');
    }

    public function edit(Request $request): string
    {
        $id = (int)$request->param('id');
        $produto = $this->estoqueService->obterProdutoPorId($id);

        if (!$produto) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        return View::render('estoque/edit', [
            'produto' => $produto,
            'old' => Session::flash('old_input') ?? $produto->toArray(),
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->estoqueService->salvarProduto($dados, $id);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário.');
            Response::redirect("/estoque/{$id}/editar");
            return;
        }

        Session::flash('success', "Produto {$result['produto']->nome} atualizado com sucesso!");
        Response::redirect('/estoque');
    }

    public function movimentacoes(Request $request): string
    {
        $filters = [
            'produtoId' => $request->query('produtoId'),
            'tipo' => $request->query('tipo')
        ];

        $movimentacoes = $this->estoqueService->listarMovimentacoes($filters);
        $produtos = $this->estoqueService->listarProdutos();

        return View::render('estoque/movimentacoes', [
            'movimentacoes' => $movimentacoes,
            'produtos' => $produtos,
            'filters' => $filters
        ]);
    }

    public function novaMovimentacao(Request $request): string
    {
        $produtoId = (int)$request->query('produtoId', 0);
        $produtos = $this->estoqueService->listarProdutos();

        return View::render('estoque/nova_movimentacao', [
            'produtos' => $produtos,
            'produtoId' => $produtoId,
            'old' => Session::flash('old_input') ?? ['produtoId' => $produtoId, 'data' => date('Y-m-d')],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function storeMovimentacao(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->estoqueService->registrarMovimentacao($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', reset($result['errors']));
            $prodId = $dados['produtoId'] ?? '';
            Response::redirect('/estoque/movimentacoes/novo' . ($prodId ? "?produtoId={$prodId}" : ''));
            return;
        }

        Session::flash('success', "Movimentação de estoque ({$result['movimentacao']->tipo}) registrada com sucesso!");
        Response::redirect('/estoque/movimentacoes');
    }
}
