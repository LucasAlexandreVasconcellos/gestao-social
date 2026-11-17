<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\PaginationHelper;
use App\Services\AtendimentoService;
use App\Services\FamiliaService;

final class AtendimentoController
{
    public function __construct(
        private AtendimentoService $atendimentoService,
        private FamiliaService $familiaService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'familiaId' => $request->query('familiaId'),
            'situacao' => $request->query('situacao'),
            'tipo' => $request->query('tipo')
        ];

        $atendimentos = $this->atendimentoService->listar($filters);
        $page = max(1, (int)$request->query('page', 1));
        $pag = PaginationHelper::paginate($atendimentos, $page, 15);

        return View::render('atendimentos/index', [
            'atendimentos' => $pag['items'],
            'pagination' => $pag,
            'filters' => $filters
        ]);
    }

    public function create(Request $request): string
    {
        $familiaId = (int)$request->query('familiaId', 0);
        $familias = $this->familiaService->listar();
        $familiaSelecionada = $familiaId > 0 ? $this->familiaService->obterPorId($familiaId) : null;
        $integrantes = $familiaId > 0 ? $this->familiaService->obterFichaCompleta($familiaId)['integrantes'] ?? [] : [];
        $user = Session::get('user');

        return View::render('atendimentos/create', [
            'familias' => $familias,
            'familiaSelecionada' => $familiaSelecionada,
            'integrantes' => $integrantes,
            'old' => Session::flash('old_input') ?? [
                'familiaId' => $familiaId,
                'dataAtendimento' => date('Y-m-d'),
                'profissionalResponsavel' => $user['nome'] ?? '',
            ],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->atendimentoService->registrar($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, corrija os erros apontados no formulário de atendimento.');
            $famId = $dados['familiaId'] ?? '';
            Response::redirect('/atendimentos/novo' . ($famId ? "?familiaId={$famId}" : ''));
            return;
        }

        Session::flash('success', "Atendimento {$result['atendimento']->tipo} registrado com sucesso!");
        Response::redirect("/familias/{$result['atendimento']->familiaId}");
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $atendimento = $this->atendimentoService->obterPorId($id);

        if (!$atendimento) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        $familia = $this->familiaService->obterPorId($atendimento->familiaId);

        return View::render('atendimentos/show', [
            'atendimento' => $atendimento,
            'familia' => $familia
        ]);
    }

    public function addEncaminhamento(Request $request): void
    {
        $id = (int)$request->param('id');
        $dados = $request->allPost();

        $result = $this->atendimentoService->adicionarEncaminhamento($id, $dados);

        if (!$result['success']) {
            Session::flash('error', reset($result['errors']));
            Response::redirect("/atendimentos/{$id}");
            return;
        }

        Session::flash('success', "Encaminhamento para {$result['encaminhamento']->servicoDestino} registrado com sucesso!");
        Response::redirect("/atendimentos/{$id}");
    }
}
