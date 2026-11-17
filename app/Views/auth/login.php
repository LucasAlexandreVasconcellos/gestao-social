<?php
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;

$orgTitle = $orgName ?? 'Secretaria Municipal de Assistência Social';
$unitTitle = $unitName ?? 'Gestão Socioassistencial — SUAS';
?>
<div class="auth-shell">
  <div class="auth-civic-panel">
    <div>
      <div class="auth-civic-seal">
        <?= IconHelper::svg('building', 22) ?>
      </div>
      <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: #93B4DA; font-weight: 700; margin-bottom: 6px;">
        Plataforma Governamental SUAS
      </div>
      <h1><?= $e($orgTitle) ?></h1>
      <p><?= $e($unitTitle) ?> — Prontuário familiar unificado, concessão auditável de benefícios e governança LGPD.</p>
    </div>

    <ul class="auth-civic-pillars">
      <li>
        <?= IconHelper::svg('shield-check', 16) ?>
        <span>Controle de acesso por perfil funcional (RBAC estrito)</span>
      </li>
      <li>
        <?= IconHelper::svg('file-text', 16) ?>
        <span>Trilha de auditoria imutável em PostgreSQL</span>
      </li>
      <li>
        <?= IconHelper::svg('qr-code', 16) ?>
        <span>Termos e recibos PDF assinados por HMAC-SHA256</span>
      </li>
    </ul>
  </div>

  <div class="auth-form-panel">
    <div class="mb-4">
      <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--text-main);">Acesso Institucional</h2>
      <p class="text-muted" style="font-size: 0.86rem; margin-top: 4px;">Entre com suas credenciais funcionais autorizadas.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= IconHelper::svg('x-circle', 18) ?>
        <div><?= $e($error) ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
      <div class="alert alert-info" role="status">
        <?= IconHelper::svg('info', 18) ?>
        <div><?= $e($info) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login" autocomplete="on">
      <?= CsrfHelper::input() ?>
      <div class="form-group">
        <label class="form-label" for="email">E-mail institucional ou usuário <span class="required">*</span></label>
        <input type="text" id="email" name="email" class="form-control" placeholder="servidor@orgao.gov.br" required autofocus autocomplete="username">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Senha de acesso <span class="required">*</span></label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Digite sua senha" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="padding: 10px 16px; margin-top: 8px;">
        <?= IconHelper::svg('lock', 16) ?>
        <span>Entrar no Sistema</span>
      </button>
    </form>

    <div class="mt-4 pt-3" style="border-top: 1px solid var(--border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
      <a href="/validar" style="font-size: 0.83rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
        <?= IconHelper::svg('qr-code', 15) ?>
        <span>Validar Documento por QR Code</span>
      </a>
      <span class="text-muted" style="font-size: 0.76rem;">Acesso monitorado e auditado</span>
    </div>
  </div>
</div>
