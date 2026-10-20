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
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

/** @var Router $router */

// Rotas Públicas / Autenticação
$router->get('/', function () {
    Response::redirect('/dashboard');
});

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'authenticate']);
$router->get('/login/2fa', [AuthController::class, 'login2fa']);
$router->post('/login/2fa', [AuthController::class, 'verify2fa']);
$router->post('/login/2fa/cancelar', [AuthController::class, 'cancel2fa']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

// Middlewares padrão para rotas autenticadas
$authGroup = [AuthMiddleware::class, RoleMiddleware::class, CsrfMiddleware::class];

// Módulo 1 — Painel Gerencial
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Módulo 2 — Cadastro de Famílias
$router->get('/familias', [FamiliaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/familias/novo', [FamiliaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/familias', [FamiliaController::class, 'store'], $authGroup);
$router->get('/familias/{id}', [FamiliaController::class, 'show'], [AuthMiddleware::class]);
$router->get('/familias/{id}/editar', [FamiliaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/familias/{id}', [FamiliaController::class, 'update'], $authGroup);
$router->post('/familias/{id}/integrantes', [FamiliaController::class, 'addIntegrante'], $authGroup);
$router->post('/familias/{id}/desvincular-integrante', [FamiliaController::class, 'removeIntegrante'], $authGroup);
$router->get('/familias/{id}/prontuario-pdf', [FamiliaController::class, 'prontuarioPdf'], [AuthMiddleware::class]);
$router->post('/familias/{id}/documentos', [DocumentoController::class, 'uploadFamilia'], $authGroup);

// Módulo 3 — Cadastro de Pessoas e Integrantes
$router->get('/pessoas', [PessoaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/pessoas/novo', [PessoaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/pessoas', [PessoaController::class, 'store'], $authGroup);
$router->get('/pessoas/{id}', [PessoaController::class, 'show'], [AuthMiddleware::class]);
$router->get('/pessoas/{id}/editar', [PessoaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/pessoas/{id}', [PessoaController::class, 'update'], $authGroup);
$router->post('/pessoas/{id}/documentos', [DocumentoController::class, 'uploadPessoa'], $authGroup);

// Rotas Globais de Documentos e Laudos
$router->get('/documentos/{id}/download', [DocumentoController::class, 'download'], [AuthMiddleware::class]);
$router->post('/documentos/{id}/excluir', [DocumentoController::class, 'excluir'], $authGroup);

// Módulo 4 — Avaliação Socioassistencial
$router->get('/avaliacoes', [AvaliacaoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/avaliacoes/novo', [AvaliacaoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/avaliacoes', [AvaliacaoController::class, 'store'], $authGroup);
$router->get('/avaliacoes/{id}', [AvaliacaoController::class, 'show'], [AuthMiddleware::class]);

// Módulo 5 — Atendimentos e Acompanhamento
$router->get('/atendimentos', [AtendimentoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/atendimentos/novo', [AtendimentoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/atendimentos', [AtendimentoController::class, 'store'], $authGroup);
$router->get('/atendimentos/{id}', [AtendimentoController::class, 'show'], [AuthMiddleware::class]);
$router->post('/atendimentos/{id}/encaminhamentos', [AtendimentoController::class, 'addEncaminhamento'], $authGroup);

// Módulo 6 — Entrega de Benefícios e Mantimentos
$router->get('/beneficios', [BeneficioController::class, 'index'], [AuthMiddleware::class]);
$router->get('/beneficios/novo', [BeneficioController::class, 'create'], [AuthMiddleware::class]);
$router->post('/beneficios', [BeneficioController::class, 'store'], $authGroup);
$router->get('/beneficios/{id}', [BeneficioController::class, 'show'], [AuthMiddleware::class]);
$router->get('/beneficios/{id}/termo-pdf', [BeneficioController::class, 'termoPdf'], [AuthMiddleware::class]);
$router->post('/beneficios/{id}/efetivar', [BeneficioController::class, 'efetivar'], $authGroup);
$router->post('/beneficios/{id}/cancelar', [BeneficioController::class, 'cancelar'], $authGroup);

// Módulo 7 — Estoque e Produtos
$router->get('/estoque', [EstoqueController::class, 'index'], [AuthMiddleware::class]);
$router->get('/estoque/novo', [EstoqueController::class, 'create'], [AuthMiddleware::class]);
$router->post('/estoque', [EstoqueController::class, 'store'], $authGroup);
$router->get('/estoque/{id}/editar', [EstoqueController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/estoque/{id}', [EstoqueController::class, 'update'], $authGroup);
$router->get('/estoque/movimentacoes', [EstoqueController::class, 'movimentacoes'], [AuthMiddleware::class]);
$router->get('/estoque/movimentacoes/novo', [EstoqueController::class, 'novaMovimentacao'], [AuthMiddleware::class]);
$router->post('/estoque/movimentacoes', [EstoqueController::class, 'storeMovimentacao'], $authGroup);

// Módulo 8 — Doações
$router->get('/doacoes', [DoacaoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/doacoes/novo', [DoacaoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/doacoes', [DoacaoController::class, 'store'], $authGroup);
$router->get('/doacoes/{id}', [DoacaoController::class, 'show'], [AuthMiddleware::class]);
$router->get('/doacoes/{id}/recibo-pdf', [DoacaoController::class, 'reciboPdf'], [AuthMiddleware::class]);

// Módulo 9 — Campanhas e Projetos Sociais
$router->get('/projetos', [ProjetoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/projetos/novo', [ProjetoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/projetos', [ProjetoController::class, 'store'], $authGroup);
$router->get('/projetos/{id}', [ProjetoController::class, 'show'], [AuthMiddleware::class]);
$router->get('/projetos/{id}/editar', [ProjetoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/projetos/{id}', [ProjetoController::class, 'update'], $authGroup);
$router->post('/projetos/{id}/inscrever', [ProjetoController::class, 'inscrever'], $authGroup);
$router->post('/projetos/{id}/presenca', [ProjetoController::class, 'atualizarPresenca'], $authGroup);

// Módulo 10 — Relatórios
$router->get('/relatorios', [RelatorioController::class, 'index'], [AuthMiddleware::class]);
$router->get('/relatorios/visualizar', [RelatorioController::class, 'view'], [AuthMiddleware::class]);
$router->get('/relatorios/view', [RelatorioController::class, 'view'], [AuthMiddleware::class]);
$router->get('/relatorios/exportar', [RelatorioController::class, 'export'], [AuthMiddleware::class]);
$router->get('/relatorios/export', [RelatorioController::class, 'export'], [AuthMiddleware::class]);


// Comunicações & Notificações (WhatsApp / SMS)
$router->post('/notificacoes/enviar', [NotificacaoController::class, 'enviar'], $authGroup);
$router->get('/notificacoes/historico', [NotificacaoController::class, 'historico'], [AuthMiddleware::class]);

// Módulo 11 — Usuários, Permissões e Auditoria
$router->get('/usuarios', [UsuarioController::class, 'index'], [AuthMiddleware::class]);
$router->post('/usuarios/alternar-perfil', [UsuarioController::class, 'switchRole'], $authGroup);
$router->post('/usuarios/switch-role', [UsuarioController::class, 'switchRole'], $authGroup);
$router->get('/auditoria', [UsuarioController::class, 'auditoria'], [AuthMiddleware::class]);
$router->get('/auditoria/lgpd', [UsuarioController::class, 'lgpdIndex'], [AuthMiddleware::class]);
$router->post('/auditoria/lgpd/anonimizar', [UsuarioController::class, 'anonimizar'], $authGroup);
$router->get('/meu-perfil/2fa', [AuthController::class, 'twoFactorProfile'], [AuthMiddleware::class]);
$router->post('/meu-perfil/2fa/confirmar', [AuthController::class, 'confirmTwoFactor'], $authGroup);
$router->post('/meu-perfil/2fa/desativar', [AuthController::class, 'disableTwoFactor'], $authGroup);

