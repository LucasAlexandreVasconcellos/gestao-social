<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AvaliacaoService;
use App\Services\FamiliaService;

final class AvaliacaoController
{
    public function __construct(
        private AvaliacaoService $avaliacaoService,
        private FamiliaService $familiaService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'familiaId' => $request->query('familiaId'),
            'nivelVulnerabilidade' => $request->query('nivel')
        ];

        $avaliacoes = $this->avaliacaoService->listar($filters);
        $reavaliacoesPendentes = $this->avaliacaoService->obterReavaliacoesPendentes(30);

        return View::render('avaliacoes/index', [
            'avaliacoes' => $avaliacoes,
            'filters' => $filters,
            'reavaliacoesPendentes' => $reavaliacoesPendentes
        ]);
    }

    public function create(Request $request): string
    {
        $familiaId = (int)$request->query('familiaId', 0);
        $familias = $this->familiaService->listar();
        $familiaSelecionada = $familiaId > 0 ? $this->familiaService->obterPorId($familiaId) : null;

        return View::render('avaliacoes/create', [
            'familias' => $familias,
            'familiaSelecionada' => $familiaSelecionada,
            'old' => Session::flash('old_input') ?? ['familiaId' => $familiaId],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->avaliacaoService->registrar($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', 'Por favor, preencha todos os campos obrigatórios da avaliação.');
            $famId = $dados['familiaId'] ?? '';
            Response::redirect('/avaliacoes/novo' . ($famId ? "?familiaId={$famId}" : ''));
            return;
        }

        Session::flash('success', "Avaliação socioassistencial registrada com sucesso! Nível: {$result['avaliacao']->nivelVulnerabilidade}");
        Response::redirect("/familias/{$result['avaliacao']->familiaId}");
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $avaliacao = $this->avaliacaoService->obterPorId($id);

        if (!$avaliacao) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        $familia = $this->familiaService->obterPorId($avaliacao->familiaId);

        return View::render('avaliacoes/show', [
            'avaliacao' => $avaliacao,
            'familia' => $familia
        ]);
    }
}
