<?php

declare(strict_types=1);

use App\Controllers\AtendimentoController;
use App\Controllers\AuthController;
use App\Controllers\AvaliacaoController;
use App\Controllers\BeneficioController;
use App\Controllers\DashboardController;
use App\Controllers\DoacaoController;
use App\Controllers\DocumentoController;
use App\Controllers\EstoqueController;
use App\Controllers\FamiliaController;
use App\Controllers\NotificacaoController;
use App\Controllers\PessoaController;
use App\Controllers\ProjetoController;
use App\Controllers\RelatorioController;
use App\Controllers\UsuarioController;
use App\Controllers\ValidacaoController;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

/** @var Router $router */

// Rotas Públicas / Autenticação / Validação Documental
$router->get('/', function () {
    Response::redirect('/dashboard');
});

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'authenticate'], [CsrfMiddleware::class]);
$router->get('/login/2fa', [AuthController::class, 'login2fa']);
$router->post('/login/2fa', [AuthController::class, 'verify2fa'], [CsrfMiddleware::class]);
$router->post('/login/2fa/cancelar', [AuthController::class, 'cancel2fa'], [CsrfMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class]);

// Portal Público de Validação de Autenticidade Documental (QR Code dos PDFs)
$router->get('/validar', [ValidacaoController::class, 'index']);
$router->get('/validar/{tipo}/{codigo}', [ValidacaoController::class, 'verificar']);

// Middlewares padrão para rotas autenticadas (GET e POST protegidos por RBAC)
$authRead = [AuthMiddleware::class, RoleMiddleware::class];
$authWrite = [AuthMiddleware::class, RoleMiddleware::class, CsrfMiddleware::class];

// Módulo 1 — Painel Gerencial
$router->get('/dashboard', [DashboardController::class, 'index'], $authRead, 'dashboard.view');

// Perfil Pessoal (Senha obrigatória e 2FA)
$router->get('/meu-perfil/senha', [AuthController::class, 'changePasswordForm'], [AuthMiddleware::class]);
$router->post('/meu-perfil/senha', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/meu-perfil/2fa', [AuthController::class, 'twoFactorProfile'], [AuthMiddleware::class]);
$router->post('/meu-perfil/2fa/confirmar', [AuthController::class, 'confirmTwoFactor'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/meu-perfil/2fa/desativar', [AuthController::class, 'disableTwoFactor'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Módulo 2 — Cadastro de Famílias
$router->get('/familias', [FamiliaController::class, 'index'], $authRead, 'familias.view');
$router->get('/familias/novo', [FamiliaController::class, 'create'], $authRead, 'familias.create');
$router->post('/familias', [FamiliaController::class, 'store'], $authWrite, 'familias.create');
$router->get('/familias/{id}', [FamiliaController::class, 'show'], $authRead, 'familias.view');
$router->get('/familias/{id}/editar', [FamiliaController::class, 'edit'], $authRead, 'familias.edit');
$router->post('/familias/{id}', [FamiliaController::class, 'update'], $authWrite, 'familias.edit');
$router->post('/familias/{id}/integrantes', [FamiliaController::class, 'addIntegrante'], $authWrite, 'familias.integrantes');
$router->post('/familias/{id}/desvincular-integrante', [FamiliaController::class, 'removeIntegrante'], $authWrite, 'familias.integrantes');
$router->get('/familias/{id}/prontuario-pdf', [FamiliaController::class, 'prontuarioPdf'], $authRead, 'familias.pdf');
$router->post('/familias/{id}/documentos', [DocumentoController::class, 'uploadFamilia'], $authWrite, 'documentos.upload');

// Módulo 3 — Cadastro de Pessoas e Integrantes
$router->get('/pessoas', [PessoaController::class, 'index'], $authRead, 'pessoas.view');
$router->get('/pessoas/novo', [PessoaController::class, 'create'], $authRead, 'pessoas.create');
$router->post('/pessoas', [PessoaController::class, 'store'], $authWrite, 'pessoas.create');
$router->get('/pessoas/{id}', [PessoaController::class, 'show'], $authRead, 'pessoas.view');
$router->get('/pessoas/{id}/editar', [PessoaController::class, 'edit'], $authRead, 'pessoas.edit');
$router->post('/pessoas/{id}', [PessoaController::class, 'update'], $authWrite, 'pessoas.edit');
$router->post('/pessoas/{id}/documentos', [DocumentoController::class, 'uploadPessoa'], $authWrite, 'documentos.upload');

// Rotas Globais de Documentos e Laudos (com proteção RBAC e anti-IDOR adicional no Controller)
$router->get('/documentos/{id}/download', [DocumentoController::class, 'download'], $authRead, 'documentos.view');
$router->post('/documentos/{id}/excluir', [DocumentoController::class, 'excluir'], $authWrite, 'documentos.delete');

// Módulo 4 — Avaliação Socioassistencial
$router->get('/avaliacoes', [AvaliacaoController::class, 'index'], $authRead, 'avaliacoes.view');
$router->get('/avaliacoes/novo', [AvaliacaoController::class, 'create'], $authRead, 'avaliacoes.create');
$router->post('/avaliacoes', [AvaliacaoController::class, 'store'], $authWrite, 'avaliacoes.create');
$router->get('/avaliacoes/{id}', [AvaliacaoController::class, 'show'], $authRead, 'avaliacoes.sensitive');

// Módulo 5 — Atendimentos e Acompanhamento
$router->get('/atendimentos', [AtendimentoController::class, 'index'], $authRead, 'atendimentos.view');
$router->get('/atendimentos/novo', [AtendimentoController::class, 'create'], $authRead, 'atendimentos.create');
$router->post('/atendimentos', [AtendimentoController::class, 'store'], $authWrite, 'atendimentos.create');
$router->get('/atendimentos/{id}', [AtendimentoController::class, 'show'], $authRead, 'atendimentos.view');
$router->post('/atendimentos/{id}/encaminhamentos', [AtendimentoController::class, 'addEncaminhamento'], $authWrite, 'atendimentos.encaminhar');

// Módulo 6 — Entrega de Benefícios e Mantimentos
$router->get('/beneficios', [BeneficioController::class, 'index'], $authRead, 'beneficios.view');
$router->get('/beneficios/novo', [BeneficioController::class, 'create'], $authRead, 'beneficios.create');
$router->post('/beneficios', [BeneficioController::class, 'store'], $authWrite, 'beneficios.create');
$router->get('/beneficios/{id}', [BeneficioController::class, 'show'], $authRead, 'beneficios.view');
$router->get('/beneficios/{id}/termo-pdf', [BeneficioController::class, 'termoPdf'], $authRead, 'beneficios.pdf');
$router->post('/beneficios/{id}/efetivar', [BeneficioController::class, 'efetivar'], $authWrite, 'beneficios.efetivar');
$router->post('/beneficios/{id}/cancelar', [BeneficioController::class, 'cancelar'], $authWrite, 'beneficios.cancelar');

// Módulo 7 — Estoque e Produtos
$router->get('/estoque', [EstoqueController::class, 'index'], $authRead, 'estoque.view');
$router->get('/estoque/novo', [EstoqueController::class, 'create'], $authRead, 'estoque.create');
$router->post('/estoque', [EstoqueController::class, 'store'], $authWrite, 'estoque.create');
$router->get('/estoque/{id}/editar', [EstoqueController::class, 'edit'], $authRead, 'estoque.edit');
$router->post('/estoque/{id}', [EstoqueController::class, 'update'], $authWrite, 'estoque.edit');
$router->get('/estoque/movimentacoes', [EstoqueController::class, 'movimentacoes'], $authRead, 'estoque.view');
$router->get('/estoque/movimentacoes/novo', [EstoqueController::class, 'novaMovimentacao'], $authRead, 'estoque.move');
$router->post('/estoque/movimentacoes', [EstoqueController::class, 'storeMovimentacao'], $authWrite, 'estoque.move');

// Módulo 8 — Doações
$router->get('/doacoes', [DoacaoController::class, 'index'], $authRead, 'doacoes.view');
$router->get('/doacoes/novo', [DoacaoController::class, 'create'], $authRead, 'doacoes.create');
$router->post('/doacoes', [DoacaoController::class, 'store'], $authWrite, 'doacoes.create');
$router->get('/doacoes/{id}', [DoacaoController::class, 'show'], $authRead, 'doacoes.view');
$router->get('/doacoes/{id}/recibo-pdf', [DoacaoController::class, 'reciboPdf'], $authRead, 'doacoes.pdf');

// Módulo 9 — Campanhas e Projetos Sociais
$router->get('/projetos', [ProjetoController::class, 'index'], $authRead, 'projetos.view');
$router->get('/projetos/novo', [ProjetoController::class, 'create'], $authRead, 'projetos.create');
$router->post('/projetos', [ProjetoController::class, 'store'], $authWrite, 'projetos.create');
$router->get('/projetos/{id}', [ProjetoController::class, 'show'], $authRead, 'projetos.view');
$router->get('/projetos/{id}/editar', [ProjetoController::class, 'edit'], $authRead, 'projetos.edit');
$router->post('/projetos/{id}', [ProjetoController::class, 'update'], $authWrite, 'projetos.edit');
$router->post('/projetos/{id}/inscrever', [ProjetoController::class, 'inscrever'], $authWrite, 'projetos.participantes');
$router->post('/projetos/{id}/presenca', [ProjetoController::class, 'atualizarPresenca'], $authWrite, 'projetos.participantes');

// Módulo 10 — Relatórios
$router->get('/relatorios', [RelatorioController::class, 'index'], $authRead, 'relatorios.view');
$router->get('/relatorios/visualizar', [RelatorioController::class, 'view'], $authRead, 'relatorios.view');
$router->get('/relatorios/view', [RelatorioController::class, 'view'], $authRead, 'relatorios.view');
$router->get('/relatorios/exportar', [RelatorioController::class, 'export'], $authRead, 'relatorios.export');
$router->get('/relatorios/export', [RelatorioController::class, 'export'], $authRead, 'relatorios.export');

// Comunicações & Notificações (WhatsApp / SMS)
$router->post('/notificacoes/enviar', [NotificacaoController::class, 'enviar'], $authWrite, 'notificacoes.send');
$router->get('/notificacoes/historico', [NotificacaoController::class, 'historico'], $authRead, 'notificacoes.view');

// Módulo 11 — Gestão de Usuários, Permissões, Auditoria e LGPD
$router->get('/usuarios', [UsuarioController::class, 'index'], $authRead, 'usuarios.view');
$router->get('/usuarios/novo', [UsuarioController::class, 'create'], $authRead, 'usuarios.manage');
$router->post('/usuarios', [UsuarioController::class, 'store'], $authWrite, 'usuarios.manage');
$router->get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'], $authRead, 'usuarios.manage');
$router->post('/usuarios/{id}', [UsuarioController::class, 'update'], $authWrite, 'usuarios.manage');
$router->post('/usuarios/{id}/resetar-senha', [UsuarioController::class, 'resetPassword'], $authWrite, 'usuarios.manage');
$router->post('/usuarios/{id}/alternar-status', [UsuarioController::class, 'toggleStatus'], $authWrite, 'usuarios.manage');

$router->get('/auditoria', [UsuarioController::class, 'auditoria'], $authRead, 'auditoria.view');
$router->get('/auditoria/lgpd', [UsuarioController::class, 'lgpdIndex'], $authRead, 'lgpd.manage');
$router->post('/auditoria/lgpd/anonimizar', [UsuarioController::class, 'anonimizar'], $authWrite, 'lgpd.manage');
