<?php
/** @var string $error */
?>
<div class="card">
  <div class="empty-state">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--danger);"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
    <h2>500 — Ocorreu um Erro no Processamento</h2>
    <p style="margin: 8px 0 20px; color: var(--text-muted);">A solicitação não pôde ser concluída. Detalhe técnico da exceção:</p>
    <div style="background-color: var(--bg-subtle); padding: 12px; border-radius: var(--radius-md); font-family: monospace; font-size: 13px; max-width: 600px; margin: 0 auto 20px; text-align: left;">
      <?= htmlspecialchars($error ?? 'Erro interno', ENT_QUOTES, 'UTF-8') ?>
    </div>
    <a href="/dashboard" class="btn btn-primary">Voltar ao Painel Gerencial</a>
  </div>
</div>
