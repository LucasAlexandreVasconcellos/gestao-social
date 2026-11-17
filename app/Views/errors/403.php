<?php
/** @var string|null $message */
?>
<div class="card">
  <div class="empty-state" style="padding: 56px 24px;">
    <div style="width: 56px; height: 56px; border-radius: 14px; background: var(--danger-soft); color: var(--danger); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
      <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        <line x1="9" y1="9" x2="15" y2="15"></line>
        <line x1="15" y1="9" x2="9" y2="15"></line>
      </svg>
    </div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">403 — Acesso Restrito por Perfil</h2>
    <p style="margin: 10px auto 24px; max-width: 520px; color: var(--text-muted); font-size: 14px; line-height: 1.6;">
      <?= htmlspecialchars($message ?? 'Você não possui permissão para acessar este módulo ou executar esta ação.', ENT_QUOTES, 'UTF-8') ?>
    </p>
    <div style="display: flex; gap: 10px; justify-content: center;">
      <a href="/dashboard" class="btn btn-primary">Voltar ao Painel Gerencial</a>
    </div>
  </div>
</div>
