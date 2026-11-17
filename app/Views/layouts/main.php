<?php
use App\Core\Session;
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;

$currentUri = strtok($_SERVER['REQUEST_URI'] ?? '/dashboard', '?');
$currentUser = Session::get('user');
$flashSuccess = Session::flash('success');
$flashError = Session::flash('error');
$flashInfo = Session::flash('info');

$orgTitle = $orgName ?? 'Secretaria Municipal de Assistência Social';
$unitTitle = $unitName ?? 'Gestão Socioassistencial — SUAS';

$isActive = function (string $prefix) use ($currentUri): string {
    if ($prefix === '/dashboard') {
        return $currentUri === '/dashboard' ? 'active' : '';
    }
    return str_starts_with($currentUri, $prefix) ? 'active' : '';
};

$iniciais = 'US';
if (!empty($currentUser['nome'])) {
    $partes = preg_split('/\s+/', trim((string)$currentUser['nome']));
    $iniciais = mb_strtoupper(mb_substr($partes[0] ?? 'U', 0, 1) . mb_substr($partes[1] ?? ($partes[0] ?? ''), 0, 1), 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $e($pageTitle ?? 'Gestão Social — Plataforma Socioassistencial') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="app-wrapper app-container">
  <div id="sidebar-backdrop" class="sidebar-backdrop"></div>

  <!-- Botão único fixo no canto superior esquerdo: ancora a marca e recolhe/expande a barra lateral -->
  <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" title="Recolher ou expandir menu lateral" aria-label="Recolher ou expandir menu lateral" aria-expanded="true">
    <span class="sidebar-toggle-mark">
      <?= IconHelper::svg('building', 19) ?>
    </span>
    <span class="sidebar-toggle-hint" aria-hidden="true">
      <span class="sidebar-toggle-arrow"><?= IconHelper::svg('chevrons-left', 14) ?></span>
      <span class="hint-label-close">Recolher</span>
      <span class="hint-label-open">Abrir menu</span>
    </span>
  </button>

  <aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-text">
        <span class="sidebar-brand-title">Gestão Social</span>
        <span class="sidebar-brand-sub" title="<?= $e($unitTitle) ?>"><?= $e($unitTitle) ?></span>
      </div>
    </div>

    <nav class="sidebar-nav" aria-label="Menu principal">
      <?php if ($can('dashboard.view')): ?>
        <a href="/dashboard" class="sidebar-link <?= $isActive('/dashboard') ?>">
          <?= IconHelper::svg('dashboard', 17) ?>
          <span>Painel Gerencial</span>
        </a>
      <?php endif; ?>

      <?php if ($can('familias.view') || $can('pessoas.view') || $can('avaliacoes.view') || $can('atendimentos.view')): ?>
        <div class="sidebar-section-label">Atendimento & Cadastro</div>
      <?php endif; ?>

      <?php if ($can('familias.view')): ?>
        <a href="/familias" class="sidebar-link <?= $isActive('/familias') ?>">
          <?= IconHelper::svg('home', 17) ?>
          <span>Famílias</span>
        </a>
      <?php endif; ?>

      <?php if ($can('pessoas.view')): ?>
        <a href="/pessoas" class="sidebar-link <?= $isActive('/pessoas') ?>">
          <?= IconHelper::svg('users', 17) ?>
          <span>Pessoas / Integrantes</span>
        </a>
      <?php endif; ?>

      <?php if ($can('avaliacoes.view')): ?>
        <a href="/avaliacoes" class="sidebar-link <?= $isActive('/avaliacoes') ?>">
          <?= IconHelper::svg('clipboard', 17) ?>
          <span>Avaliação Social</span>
        </a>
      <?php endif; ?>

      <?php if ($can('atendimentos.view')): ?>
        <a href="/atendimentos" class="sidebar-link <?= $isActive('/atendimentos') ?>">
          <?= IconHelper::svg('activity', 17) ?>
          <span>Atendimentos</span>
        </a>
      <?php endif; ?>

      <?php if ($can('beneficios.view') || $can('estoque.view') || $can('doacoes.view') || $can('projetos.view')): ?>
        <div class="sidebar-section-label">Operação & Logística</div>
      <?php endif; ?>

      <?php if ($can('beneficios.view')): ?>
        <a href="/beneficios" class="sidebar-link <?= $isActive('/beneficios') ?>">
          <?= IconHelper::svg('gift', 17) ?>
          <span>Entrega de Benefícios</span>
        </a>
      <?php endif; ?>

      <?php if ($can('estoque.view')): ?>
        <a href="/estoque" class="sidebar-link <?= $isActive('/estoque') ?>">
          <?= IconHelper::svg('box', 17) ?>
          <span>Estoque e Produtos</span>
        </a>
      <?php endif; ?>

      <?php if ($can('doacoes.view')): ?>
        <a href="/doacoes" class="sidebar-link <?= $isActive('/doacoes') ?>">
          <?= IconHelper::svg('heart', 17) ?>
          <span>Doações</span>
        </a>
      <?php endif; ?>

      <?php if ($can('projetos.view')): ?>
        <a href="/projetos" class="sidebar-link <?= $isActive('/projetos') ?>">
          <?= IconHelper::svg('calendar', 17) ?>
          <span>Campanhas e Projetos</span>
        </a>
      <?php endif; ?>

      <?php if ($can('relatorios.view') || $can('notificacoes.view') || $can('usuarios.view') || $can('auditoria.view') || $can('lgpd.manage')): ?>
        <div class="sidebar-section-label">Governança & Controle</div>
      <?php endif; ?>

      <?php if ($can('relatorios.view')): ?>
        <a href="/relatorios" class="sidebar-link <?= $isActive('/relatorios') ?>">
          <?= IconHelper::svg('bar-chart', 17) ?>
          <span>Relatórios & BI</span>
        </a>
      <?php endif; ?>

      <?php if ($can('notificacoes.view')): ?>
        <a href="/notificacoes/historico" class="sidebar-link <?= $isActive('/notificacoes') ?>">
          <?= IconHelper::svg('bell', 17) ?>
          <span>Comunicações</span>
        </a>
      <?php endif; ?>

      <?php if ($can('usuarios.view')): ?>
        <a href="/usuarios" class="sidebar-link <?= $isActive('/usuarios') ?>">
          <?= IconHelper::svg('lock', 17) ?>
          <span>Usuários e Perfis</span>
        </a>
      <?php endif; ?>

      <?php if ($can('auditoria.view')): ?>
        <a href="/auditoria" class="sidebar-link <?= $currentUri === '/auditoria' ? 'active' : '' ?>">
          <?= IconHelper::svg('file-text', 17) ?>
          <span>Trilha de Auditoria</span>
        </a>
      <?php endif; ?>

      <?php if ($can('lgpd.manage')): ?>
        <a href="/auditoria/lgpd" class="sidebar-link <?= str_starts_with($currentUri, '/auditoria/lgpd') ? 'active' : '' ?>">
          <?= IconHelper::svg('shield-check', 17) ?>
          <span>Governança LGPD</span>
        </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <a href="/meu-perfil/senha" class="sidebar-user-link">
        <?= IconHelper::svg('key', 15) ?>
        <span>Alterar Minha Senha</span>
      </a>
      <a href="/meu-perfil/2fa" class="sidebar-user-link">
        <?= IconHelper::svg('shield', 15) ?>
        <span>Segurança 2FA</span>
      </a>
      <a href="/validar" class="sidebar-user-link">
        <?= IconHelper::svg('qr-code', 15) ?>
        <span>Validador QR Público</span>
      </a>
    </div>
  </aside>

  <div class="app-main">
    <header class="app-header">
      <div class="header-left">
        <div class="header-org-meta">
          <span class="header-org-name"><?= $e($orgTitle) ?></span>
          <span class="header-org-unit"><?= $e($unitTitle) ?></span>
        </div>
      </div>

      <div class="header-right">
        <?php if ($currentUser): ?>
          <div class="user-pill">
            <span class="user-avatar-chip"><?= $e($iniciais) ?></span>
            <span><?= $e($currentUser['nome']) ?></span>
            <span class="user-role-tag"><?= $e($currentUser['perfil']) ?></span>
          </div>
          <form method="POST" action="/logout" style="display:inline;">
            <?= CsrfHelper::input() ?>
            <button type="submit" class="btn btn-secondary btn-sm" title="Encerrar sessão">
              <?= IconHelper::svg('log-out', 15) ?>
              <span>Sair</span>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <main class="app-content">
      <?php if ($flashSuccess): ?>
        <div class="alert alert-success" role="status">
          <?= IconHelper::svg('check-circle', 18) ?>
          <div><?= $e($flashSuccess) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($flashError): ?>
        <div class="alert alert-danger" role="alert">
          <?= IconHelper::svg('x-circle', 18) ?>
          <div><?= $e($flashError) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($flashInfo): ?>
        <div class="alert alert-info" role="status">
          <?= IconHelper::svg('info', 18) ?>
          <div><?= $e($flashInfo) ?></div>
        </div>
      <?php endif; ?>

      <?= $content ?>
    </main>
  </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
