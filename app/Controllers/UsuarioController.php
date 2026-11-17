<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\PaginationHelper;
use App\Models\Usuario;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\LgpdService;

final class UsuarioController
{
    public function __construct(
        private AuthService $authService,
        private AuditLogService $auditLogService,
        private LgpdService $lgpdService
    ) {}

    public function index(Request $request): string
    {
        $currentUser = $this->authService->getCurrentUser();
        $usuarios = $this->authService->getAllUsers();
        $page = max(1, (int)$request->query('page', 1));
        $pag = PaginationHelper::paginate($usuarios, $page, 15);

        return View::render('usuarios/index', [
            'usuarios' => $pag['items'],
            'pagination' => $pag,
            'currentUser' => $currentUser,
            'perfisDisponiveis' => Usuario::PERFIS_DISPONIVEIS
        ]);
    }

    public function create(Request $request): string
    {
        return View::render('usuarios/create', [
            'perfisDisponiveis' => Usuario::PERFIS_DISPONIVEIS,
            'errors' => Session::flash('errors') ?? [],
            'old' => Session::flash('old') ?? []
        ]);
    }

    public function store(Request $request): void
    {
        $dados = $request->allPost();
        $result = $this->authService->createUser($dados);

        if (!$result['success']) {
            Session::flash('errors', $result['errors'] ?? []);
            Session::flash('old', $dados);
            Session::flash('error', 'Revise os campos obrigatórios e os critérios da senha.');
            Response::redirect('/usuarios/novo');
            return;
        }

        Session::flash('success', "Usuário '{$result['usuario']->nome}' cadastrado com perfil [{$result['usuario']->perfil}].");
        Response::redirect('/usuarios');
    }

    public function edit(Request $request): string
    {
        $id = (int)$request->param('id');
        $usuario = $this->authService->findUserById($id);
        if (!$usuario) {
            Session::flash('error', 'Usuário não localizado.');
            Response::redirect('/usuarios');
            return '';
        }

        return View::render('usuarios/edit', [
            'usuario' => $usuario,
            'currentUser' => $this->authService->getCurrentUser(),
            'perfisDisponiveis' => Usuario::PERFIS_DISPONIVEIS,
            'errors' => Session::flash('errors') ?? [],
            'old' => Session::flash('old') ?? []
        ]);
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $currentUser = $this->authService->getCurrentUser();
        $dados = $request->allPost();
        $result = $this->authService->updateUser($id, $dados, $currentUser ? $currentUser->id : 0);

        if (!$result['success']) {
            Session::flash('errors', $result['errors'] ?? []);
            Session::flash('old', $dados);
            Session::flash('error', $result['errors']['geral'] ?? 'Não foi possível atualizar o usuário.');
            Response::redirect("/usuarios/{$id}/editar");
            return;
        }

        Session::flash('success', "Cadastro do usuário '{$result['usuario']->nome}' atualizado com sucesso.");
        Response::redirect('/usuarios');
    }

    public function resetPassword(Request $request): void
    {
        $id = (int)$request->param('id');
        $nova = (string)$request->post('novaSenha', '');
        $confirma = (string)$request->post('confirmarSenha', '');

        $result = $this->authService->adminResetPassword($id, $nova, $confirma);
        if (!$result['success']) {
            $errMsg = implode(' ', array_values($result['errors'] ?? ['Falha ao redefinir senha.']));
            Session::flash('error', $errMsg);
            Response::redirect("/usuarios/{$id}/editar");
            return;
        }

        Session::flash('success', 'Senha temporária redefinida com sucesso. Será exigida a troca obrigatória no próximo acesso.');
        Response::redirect("/usuarios/{$id}/editar");
    }

    public function toggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $currentUser = $this->authService->getCurrentUser();

        $res = $this->authService->toggleUserStatus($id, $currentUser ? $currentUser->id : 0);
        if (!$res['success']) {
            Session::flash('error', $res['error'] ?? 'Operação não permitida.');
        } else {
            $label = $res['ativo'] ? 'reativado' : 'inativado';
            Session::flash('success', "Usuário {$label} com sucesso.");
        }

        Response::redirect('/usuarios');
    }

    public function auditoria(Request $request): string
    {
        $filters = [
            'entidade' => $request->query('entidade'),
            'operacao' => $request->query('operacao')
        ];

        $registros = $this->auditLogService->listarTodos(array_filter($filters));
        $page = max(1, (int)$request->query('page', 1));
        $pag = PaginationHelper::paginate($registros, $page, 25);

        return View::render('usuarios/auditoria', [
            'registros' => $pag['items'],
            'pagination' => $pag,
            'filters' => $filters
        ]);
    }

    public function lgpdIndex(Request $request): string
    {
        $currentUser = $this->authService->getCurrentUser();
        $inativos = $this->lgpdService->buscarInativos(5);
        $solicitacoes = $this->lgpdService->listarSolicitacoes();

        return View::render('usuarios/lgpd_anonimizacao', [
            'currentUser' => $currentUser,
            'inativos' => $inativos,
            'solicitacoes' => $solicitacoes,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function anonimizar(Request $request): void
    {
        $currentUser = $this->authService->getCurrentUser();
        $pessoaId = (int)$request->post('pessoaId');
        $motivo = trim((string)$request->post('motivo', 'Solicitação expressa do titular (Art. 18 LGPD)'));
        $expurgarDocumentos = (bool)$request->post('expurgarDocumentos', true);

        if ($pessoaId <= 0) {
            Session::flash('error', 'Informe o ID ou código do titular a ser anonimizado.');
            Response::redirect('/auditoria/lgpd');
            return;
        }

        $result = $this->lgpdService->anonimizarPessoa($pessoaId, $motivo, $currentUser ? $currentUser->id : 0, $expurgarDocumentos);

        if (!$result['success']) {
            Session::flash('error', $result['error'] ?? 'Erro ao processar anonimização.');
        } else {
            Session::flash('success', "Titular anonimizado com sucesso! Protocolo oficial gerado: {$result['protocolo']}.");
        }

        Response::redirect('/auditoria/lgpd');
    }
}
