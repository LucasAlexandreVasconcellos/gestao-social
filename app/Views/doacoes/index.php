<?php
use App\Helpers\FormatHelper;

/** @var array $doacoes */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Doações Recebidas</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Registro de doadores parceiros, separação de doações financeiras e de produtos/mantimentos.</p>
  </div>
  <a href="/doacoes/novo" class="btn btn-primary">+ Receber Doação</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/doacoes" method="GET" class="filter-bar">
      <div class="filter-input">
        <input type="text" name="search" class="form-control" placeholder="Buscar por doador ou descrição..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div style="min-width: 180px;">
        <select name="tipo" class="form-select">
          <option value="">Todos os Tipos de Doação</option>
          <option value="Produtos" <?= ($filters['tipo'] ?? '') === 'Produtos' ? 'selected' : '' ?>>Doação de Produtos / Alimentos</option>
          <option value="Financeira" <?= ($filters['tipo'] ?? '') === 'Financeira' ? 'selected' : '' ?>>Doação Financeira</option>
          <option value="Serviços/Outros" <?= ($filters['tipo'] ?? '') === 'Serviços/Outros' ? 'selected' : '' ?>>Serviços / Outros</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['tipo']) || !empty($filters['search'])): ?>
        <a href="/doacoes" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Doador / Parceiro</th>
          <th>Tipo de Doador</th>
          <th>Tipo de Doação</th>
          <th>Itens / Valor</th>
          <th>Comprovante</th>
          <th>Responsável</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($doacoes)): ?>
          <tr><td colspan="8" class="empty-state">Nenhuma doação registrada até o momento.</td></tr>
        <?php else: ?>
          <?php foreach ($doacoes as $d): ?>
            <tr>
              <td><?= FormatHelper::date($d->dataRecebimento) ?></td>
              <td>
                <a href="/doacoes/<?= $d->id ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  <?= htmlspecialchars($d->nomeDoador, ENT_QUOTES, 'UTF-8') ?>
                </a>
              </td>
              <td><?= htmlspecialchars($d->tipoDoador, ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge <?= $d->tipoDoacao === 'Produtos' ? 'badge-info' : 'badge-success' ?>">
                  <?= htmlspecialchars($d->tipoDoacao, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td>
                <?php if ($d->tipoDoacao === 'Financeira'): ?>
                  <strong style="color: var(--success);"><?= FormatHelper::money($d->valorEstimado) ?></strong>
                <?php else: ?>
                  <span><?= count($d->itens) ?> item(ns) de produto</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($d->comprovante ?? 'REC-DOA-' . $d->id, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($d->responsavel, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/doacoes/<?= $d->id ?>" class="btn btn-secondary btn-sm">Ver Recibo</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
