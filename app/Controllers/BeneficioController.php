<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\BeneficioService;
use App\Services\EstoqueService;
use App\Services\FamiliaService;
use App\Services\ProjetoService;

final class BeneficioController
{
    public function __construct(
        private BeneficioService $beneficioService,
        private FamiliaService $familiaService,
        private EstoqueService $estoqueService,
        private ProjetoService $projetoService
    ) {}

    public function index(Request $request): string
    {
        $filters = [
            'familiaId' => $request->query('familiaId'),
            'situacao' => $request->query('situacao'),
            'tipo' => $request->query('tipo')
        ];

        $beneficios = $this->beneficioService->listar($filters);

        return View::render('beneficios/index', [
            'beneficios' => $beneficios,
            'filters' => $filters
        ]);
    }

    public function create(Request $request): string
    {
        $familiaId = (int)$request->query('familiaId', 0);
        $familias = $this->familiaService->listar();
        $familiaSelecionada = $familiaId > 0 ? $this->familiaService->obterPorId($familiaId) : null;
        $produtos = $this->estoqueService->listarProdutos();
        $projetos = $this->projetoService->listar();

        return View::render('beneficios/create', [
            'familias' => $familias,
            'familiaSelecionada' => $familiaSelecionada,
            'produtos' => $produtos,
            'projetos' => $projetos,
            'old' => Session::flash('old_input') ?? ['familiaId' => $familiaId, 'dataEntrega' => date('Y-m-d')],
            'errors' => Session::flash('form_errors') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();

        // Tratamento da lista dinâmica de itens enviados pelo formulário
        $itens = [];
        if (!empty($dados['produto_id']) && is_array($dados['produto_id'])) {
            foreach ($dados['produto_id'] as $idx => $prodId) {
                if (!empty($prodId) && !empty($dados['quantidade'][$idx])) {
                    $itens[] = [
                        'produtoId' => (int)$prodId,
                        'quantidade' => $dados['quantidade'][$idx],
                        'lote' => $dados['lote'][$idx] ?? null
                    ];
                }
            }
        }
        $dados['itens'] = $itens;

        $result = $this->beneficioService->registrarEntrega($dados);

        if (!$result['success']) {
            Session::flash('form_errors', $result['errors']);
            Session::flash('old_input', $dados);
            Session::flash('error', reset($result['errors']));
            $famId = $dados['familiaId'] ?? '';
            Response::redirect('/beneficios/novo' . ($famId ? "?familiaId={$famId}" : ''));
            return;
        }

        Session::flash('success', "Entrega de benefício registrada com sucesso!");
        Response::redirect("/beneficios/{$result['entrega']->id}");
    }

    public function show(Request $request): string
    {
        $id = (int)$request->param('id');
        $entrega = $this->beneficioService->obterPorId($id);

        if (!$entrega) {
            http_response_code(404);
            return View::render('errors/404', ['uri' => $request->getUri()]);
        }

        $familia = $this->familiaService->obterPorId($entrega->familiaId);

        return View::render('beneficios/show', [
            'entrega' => $entrega,
            'familia' => $familia
        ]);
    }

    public function efetivar(Request $request): void
    {
        $id = (int)$request->param('id');
        $result = $this->beneficioService->efetivarEntrega($id);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Entrega efetivada com sucesso e estoque atualizado!');
        }

        Response::redirect("/beneficios/{$id}");
    }

    public function cancelar(Request $request): void
    {
        $id = (int)$request->param('id');
        $motivo = (string)$request->post('motivo', 'Cancelamento administrativo');

        $result = $this->beneficioService->cancelarEntrega($id, $motivo);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('warning', 'Entrega cancelada com sucesso.');
        }

        Response::redirect("/beneficios/{$id}");
    }
}
