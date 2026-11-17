<?php
use App\Helpers\IconHelper;
?>
<div class="card">
  <div class="empty-state">
    <div class="empty-state-icon"><?= IconHelper::svg('info', 24) ?></div>
    <h4>404 — Página Não Encontrada</h4>
    <p class="mb-3">A rota solicitada (<span class="mono"><?= $e($uri ?? '') ?></span>) não foi encontrada ou não está disponível.</p>
    <a href="/dashboard" class="btn btn-primary">Voltar ao Painel Gerencial</a>
  </div>
</div>
