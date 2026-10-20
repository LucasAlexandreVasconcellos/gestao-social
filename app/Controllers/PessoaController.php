<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\DocumentoService;
use App\Services\PessoaService;

final class PessoaController
{
    public function __construct(
        private PessoaService $pessoaService,
        private DocumentoService $documentoService
    ) {}

    public function index(Request $request): string
    {
        $filters = ['search' => $request->query('search')];
        $pessoas = $this->pessoaService->listar($filters);

        return View::render('pessoas/index', [
            'pessoas' => $pessoas,
            'filters' => $filters
        ]);
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $pessoa = $this->pessoaService->obterPorId($id);

        if (!$pessoa) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        $historicoVinculos = $this->pessoaService->obterHistoricoVinculos($id);
        $documentos = $this->documentoService->listarPorEntidade('pessoa', $id);

        return View::render('pessoas/show', [
            'pessoa' => $pessoa,
            'historicoVinculos' => $historicoVinculos,
            'documentos' => $documentos
        ]);
    }

    public function create(Request $request): string
    {
        return View::render('pessoas/create', [
            'old' => Session::flash('old_input') ?? [],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->pessoaService->salvar($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário.');
            Response::redirect('/pessoas/novo');
            return;
        }

        Session::flash('success', "Pessoa {$result['pessoa']->nomeCompleto} cadastrada com sucesso!");
        Response::redirect("/pessoas/{$result['pessoa']->id}");
    }

    public function edit(Request $request): string
    {
        $id = (int)$request->param('id');
        $pessoa = $this->pessoaService->obterPorId($id);

        if (!$pessoa) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        return View::render('pessoas/edit', [
            'pessoa' => $pessoa,
            'old' => Session::flash('old_input') ?? $pessoa->toArray(),
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->pessoaService->salvar($dados, $id);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário.');
            Response::redirect("/pessoas/{$id}/editar");
            return;
        }

        Session::flash('success', "Dados de {$result['pessoa']->nomeCompleto} atualizados com sucesso!");
        Response::redirect("/pessoas/{$id}");
    }
}
