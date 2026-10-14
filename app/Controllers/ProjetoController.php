<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\FamiliaService;
use App\Services\PessoaService;
use App\Services\ProjetoService;

final class ProjetoController
{
    public function __construct(
        private ProjetoService $projetoService,
        private FamiliaService $familiaService,
        private PessoaService $pessoaService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'tipo' => $request->query('tipo'),
            'situacao' => $request->query('situacao')
        ];

        $projetos = $this->projetoService->listar($filters);

        return View::render('projetos/index', [
            'projetos' => $projetos,
            'filters' => $filters
        ]);
    }

    public function create(Request $request): string
    {
        return View::render('projetos/create', [
            'old' => Session::flash('old_input') ?? ['dataInicio' => date('Y-m-d')],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->projetoService->salvar($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', reset($result['errors']));
            Response::redirect('/projetos/novo');
            return;
        }

        Session::flash('success', "Ação social {$result['projeto']->nome} cadastrada com sucesso!");
        Response::redirect("/projetos/{$result['projeto']->id}");
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $projeto = $this->projetoService->obterPorId($id);

        if (!$projeto) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        $familias = $this->familiaService->listar();
        $pessoas = $this->pessoaService->listar();

        return View::render('projetos/show', [
            'projeto' => $projeto,
            'familias' => $familias,
            'pessoas' => $pessoas
        ]);
    }

    public function edit(Request $request): string
    {
        $id = (int)$request->param('id');
        $projeto = $this->projetoService->obterPorId($id);

        if (!$projeto) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        return View::render('projetos/edit', [
            'projeto' => $projeto,
            'old' => Session::flash('old_input') ?? $projeto->toArray(),
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->projetoService->salvar($dados, $id);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', reset($result['errors']));
            Response::redirect("/projetos/{$id}/editar");
            return;
        }

        Session::flash('success', "Ação {$result['projeto']->nome} atualizada com sucesso!");
        Response::redirect("/projetos/{$id}");
    }

    public function inscrever(Request $request): void
    {
        $id = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->projetoService->inscreverParticipante($id, $dados);

        if (!$result['success']) {
            Session::flash('error', reset($result['errors']));
            Response::redirect("/projetos/{$id}#participantes");
            return;
        }

        Session::flash('success', "Participante {$result['participante']->nomePessoa} inscrito com sucesso!");
        Response::redirect("/projetos/{$id}#participantes");
    }

    public function atualizarPresenca(Request $request): void
    {
        $projetoId = (int)$request->param('id');
        $participanteId = (int)$request->post('participanteId');
        $status = (string)$request->post('situacaoParticipacao');

        $this->projetoService->atualizarStatusParticipante($participanteId, $status);

        Session::flash('success', "Status de presença atualizado para [{$status}].");
        Response::redirect("/projetos/{$projetoId}#participantes");
    }
}
