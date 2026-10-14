<?php
/** @var string $content */
/** @var array|null $user */
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$isActive = fn(string $path) => str_starts_with($currentUri, $path) ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestão Social e Assistencial</title>
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/print.css" media="print">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar Institucional -->
    <aside class="app-sidebar">
      <div class="sidebar-brand">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span>Gestão Social</span>
      </div>

      <nav class="sidebar-menu">
        <div class="menu-category">Geral</div>
        <a href="/dashboard" class="nav-link <?= $isActive('/dashboard') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>
          <span>Painel Gerencial</span>
        </a>

        <div class="menu-category">Atendimento & Famílias</div>
        <a href="/familias" class="nav-link <?= $isActive('/familias') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          <span>Famílias</span>
        </a>
        <a href="/pessoas" class="nav-link <?= $isActive('/pessoas') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span>Pessoas / Integrantes</span>
        </a>
        <a href="/avaliacoes" class="nav-link <?= $isActive('/avaliacoes') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          <span>Avaliações Sociais</span>
        </a>
        <a href="/atendimentos" class="nav-link <?= $isActive('/atendimentos') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          <span>Atendimentos & Visitas</span>
        </a>

        <div class="menu-category">Benefícios & Recursos</div>
        <a href="/beneficios" class="nav-link <?= $isActive('/beneficios') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
          <span>Entregas de Benefícios</span>
        </a>
        <a href="/estoque" class="nav-link <?= $isActive('/estoque') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
          <span>Estoque & Produtos</span>
        </a>
        <a href="/doacoes" class="nav-link <?= $isActive('/doacoes') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>
          <span>Doações Recebidas</span>
        </a>

        <div class="menu-category">Projetos & Análises</div>
        <a href="/projetos" class="nav-link <?= $isActive('/projetos') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
          <span>Projetos & Campanhas</span>
        </a>
        <a href="/relatorios" class="nav-link <?= $isActive('/relatorios') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          <span>Relatórios Gerenciais</span>
        </a>

        <div class="menu-category">Segurança & Governança</div>
        <a href="/usuarios" class="nav-link <?= $isActive('/usuarios') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          <span>Controle de Acesso</span>
        </a>
        <a href="/auditoria" class="nav-link <?= $isActive('/auditoria') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          <span>Trilha de Auditoria</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div>Entidade Assistencial</div>
        <div style="font-size: 11px; opacity: 0.7;">Versão 1.0 (Arquitetura PHP)</div>
      </div>
    </aside>

    <!-- Área Principal -->
    <div class="app-main">
      <!-- Topbar Header -->
      <header class="app-header no-print">
        <div class="header-title">
          <button id="sidebar-toggle" style="background:none;border:none;cursor:pointer;display:inline-flex;" aria-label="Menu">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
          </button>
          <h1>Sistema de Gestão Social e Assistencial</h1>
        </div>

        <div class="header-actions">
          <!-- Alternador Rápido de Perfis Demonstrativos -->
          <form action="/usuarios/alternar-perfil" method="POST" style="margin: 0;">
            <?= \App\Core\View::render('partials/csrf', [], null) ?>
            <div class="role-switcher" title="Alterne entre os perfis demonstrativos para inspecionar permissões RBAC">
              <label for="role-select" style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Perfil Demo:</label>
              <select id="role-select" name="userId" onchange="this.form.submit()">
                <option value="1" <?= ($user['id'] ?? 1) === 1 ? 'selected' : '' ?>>Administrador</option>
                <option value="2" <?= ($user['id'] ?? 1) === 2 ? 'selected' : '' ?>>Coordenação</option>
                <option value="3" <?= ($user['id'] ?? 1) === 3 ? 'selected' : '' ?>>Assistente Social</option>
                <option value="4" <?= ($user['id'] ?? 1) === 4 ? 'selected' : '' ?>>Estoque</option>
                <option value="5" <?= ($user['id'] ?? 1) === 5 ? 'selected' : '' ?>>Atendimento</option>
                <option value="6" <?= ($user['id'] ?? 1) === 6 ? 'selected' : '' ?>>Consulta</option>
              </select>
            </div>
          </form>

          <div style="font-size: 13px; text-align: right;">
            <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($user['nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($user['perfil'] ?? 'Perfil', ENT_QUOTES, 'UTF-8') ?></div>
          </div>

          <a href="/logout" class="btn btn-secondary btn-sm" title="Sair do sistema">Sair</a>
        </div>
      </header>

      <!-- Banner Demonstrativo Obrigatório -->
      <div class="demo-banner no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="demo-badge">Modo Demonstração</span>
          <span><strong>Aviso de Arquitetura:</strong> A aplicação está executando com repositórios em memória. O banco de dados físico será conectado futuramente pelo responsável conforme o contrato em <code>docs/DatabaseIntegration.md</code>.</span>
        </div>
        <div style="white-space: nowrap;">
          <a href="/auditoria" style="color: inherit; text-decoration: underline; font-weight: 600;">Ver Auditoria</a>
        </div>
      </div>

      <!-- Área de Conteúdo -->
      <main class="app-content">
        <!-- Mensagens Flash -->
        <?php if (\App\Core\Session::hasFlash('success')): ?>
          <div class="alert alert-success">
            <span><?= htmlspecialchars(\App\Core\Session::flash('success'), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endif; ?>

        <?php if (\App\Core\Session::hasFlash('error')): ?>
          <div class="alert alert-danger">
            <span><?= htmlspecialchars(\App\Core\Session::flash('error'), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endif; ?>

        <?php if (\App\Core\Session::hasFlash('warning')): ?>
          <div class="alert alert-warning">
            <span><?= htmlspecialchars(\App\Core\Session::flash('warning'), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endif; ?>

        <?php if (\App\Core\Session::hasFlash('info')): ?>
          <div class="alert alert-info">
            <span><?= htmlspecialchars(\App\Core\Session::flash('info'), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endif; ?>

        <!-- Conteúdo do Template Filho -->
        <?= $content ?>
      </main>
    </div>
  </div>

  <script src="/assets/js/app.js"></script>
</body>
</html>
