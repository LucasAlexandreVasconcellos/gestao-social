<?php
use App\Helpers\IconHelper;
?>
<div class="card">
  <div class="empty-state">
    <div class="empty-state-icon"><?= IconHelper::svg('alert-triangle', 24) ?></div>
    <h4>405 — Método Não Permitido</h4>
    <p class="mb-3">O método HTTP utilizado não é compatível com esta rota. Métodos permitidos: <span class="mono"><?= $e($allowed ?? 'GET') ?></span></p>
    <a href="/dashboard" class="btn btn-primary">Voltar ao Painel</a>
  </div>
</div>
