<?php
use App\Helpers\IconHelper;
?>
<div class="card">
  <div class="empty-state">
    <div class="empty-state-icon" style="color: var(--danger);"><?= IconHelper::svg('x-circle', 24) ?></div>
    <h4>500 — Erro Interno no Processamento</h4>
    <p class="mb-3">A solicitação não pôde ser concluída com segurança.</p>
    <div class="mono mb-3" style="background-color: var(--bg-subtle); border: 1px solid var(--border); padding: 12px; border-radius: var(--radius-md); font-size: 0.82rem; max-width: 600px; margin: 0 auto 20px; text-align: left;">
      <?= $e($error ?? 'Erro interno no servidor.') ?>
    </div>
    <a href="/dashboard" class="btn btn-primary">Voltar ao Painel Gerencial</a>
  </div>
</div>
