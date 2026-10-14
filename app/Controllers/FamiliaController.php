<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\FamiliaService;

final class FamiliaController
{
    public function __construct(
        private FamiliaService $familiaService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'search' => $request->query('search'),
            'situacao' => $request->query('situacao'),
            'bairro' => $request->query('bairro')
        ];

        $familias = $this->familiaService->listar($filters);
        $bairros = $this->familiaService->getBairros();

        return View::render('familias/index', [
            'familias' => $familias,
            'filters' => $filters,
            'bairros' => $bairros
        ]);
    }

    public function create(Request $request): string
    {
        return View::render('familias/create', [
            'old' => Session::flash('old_input') ?? [],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->familiaService->criar($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário.');
            Response::redirect('/familias/novo');
            return;
        }

        Session::flash('success', "Família {$result['familia']->codigo} cadastrada com sucesso!");
        Response::redirect("/familias/{$result['familia']->id}");
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $ficha = $this->familiaService->obterFichaCompleta($id);

        if (!$ficha) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        return View::render('familias/show', [
            'familia' => $ficha['familia'],
            'integrantes' => $ficha['integrantes'],
            'avaliacoes' => $ficha['avaliacoes'],
            'ultimaAvaliacao' => $ficha['ultimaAvaliacao'],
            'atendimentos' => $ficha['atendimentos'],
            'beneficios' => $ficha['beneficios']
        ]);
    }

    public function edit(Request $request): string
    {
        $id = (int)$request->param('id');
        $familia = $this->familiaService->obterPorId($id);

        if (!$familia) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        return View::render('familias/edit', [
            'familia' => $familia,
            'old' => Session::flash('old_input') ?? $familia->toArray(),
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->familiaService->atualizar($id, $dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário.');
            Response::redirect("/familias/{$id}/editar");
            return;
        }

        Session::flash('success', "Dados da família {$result['familia']->codigo} atualizados com sucesso!");
        Response::redirect("/familias/{$id}");
    }

    public function addIntegrante(Request $request): void
    {
        $familiaId = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->familiaService->vincularIntegrante($familiaId, $dados);

        if (!$result['success']) {
            Session::flash('error', reset($result['errors']));
            Response::redirect("/familias/{$familiaId}#integrantes");
            return;
        }

        Session::flash('success', 'Novo integrante vinculado com sucesso ao núcleo familiar!');
        Response::redirect("/familias/{$familiaId}#integrantes");
    }

    public function removeIntegrante(Request $request): void
    {
        $familiaId = (int)$request->param('id');
        $vinculoId = (int)$request->post('vinculoId');
        $motivo = (string)$request->post('motivo', 'Desvinculação informada pela família');

        $this->familiaService->desvincularIntegrante($vinculoId, $motivo);

        Session::flash('success', 'Integrante desvinculado da família (histórico preservado no cadastro da pessoa).');
        Response::redirect("/familias/{$familiaId}#integrantes");
    }
}
