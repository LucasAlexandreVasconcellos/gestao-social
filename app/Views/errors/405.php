<?php
/** @var string $allowed */
?>
<div class="card">
  <div class="empty-state">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
    <h2>405 — Método Não Permitido</h2>
    <p style="margin: 8px 0 20px;">O método HTTP utilizado não é compatível com esta rota. Métodos permitidos: <code><?= htmlspecialchars($allowed ?? 'GET', ENT_QUOTES, 'UTF-8') ?></code></p>
    <a href="/dashboard" class="btn btn-primary">Voltar ao Painel</a>
  </div>
</div>
