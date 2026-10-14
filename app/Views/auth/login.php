<?php
/** @var array $usuarios */
/** @var string|null $error */
?>
<div class="card" style="box-shadow: var(--shadow-lg);">
  <div style="background-color: var(--primary); color: #ffffff; padding: 24px; text-align: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
    <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom: 8px;">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
      <circle cx="9" cy="7" r="4"></circle>
      <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
      <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
    </svg>
    <h2 style="font-size: 20px; font-weight: 700;">Gestão Social & Assistencial</h2>
    <p style="font-size: 13px; opacity: 0.85; margin-top: 4px;">Acesso ao Painel Institucional</p>
  </div>

  <div class="card-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <form action="/login" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label" for="email">E-mail Institucional</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="exemplo@gestaosocial.org" required value="admin@gestaosocial.org">
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" for="password">Senha de Acesso</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" value="demo123">
        <span class="form-help">Ambiente demonstrativo com autenticação pré-configurada.</span>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
        Entrar no Sistema
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
      <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px; text-align: center;">
        Acesso Rápido por Perfil (Modo Demo)
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
        <?php foreach ($usuarios as $u): ?>
          <form action="/login" method="POST" style="margin: 0;">
            <?= \App\Core\View::render('partials/csrf', [], null) ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars($u->email, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: flex-start; font-size: 11px; padding: 6px 8px;">
              <span><?= htmlspecialchars($u->perfil, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="background-color: var(--bg-subtle); padding: 12px; text-align: center; font-size: 11px; color: var(--text-muted); border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
    Plataforma de Demonstração em PHP • Banco de dados não conectado nesta etapa
  </div>
</div>
