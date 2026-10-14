<?php
use App\Helpers\FormatHelper;

/** @var array $beneficios */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Entrega de Benefícios e Mantimentos</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Controle de concessão de cestas de alimentos, kits de higiene e auxílios assistenciais.</p>
  </div>
  <a href="/beneficios/novo" class="btn btn-primary">+ Nova Entrega</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/beneficios" method="GET" class="filter-bar">
      <div style="min-width: 180px;">
        <select name="situacao" class="form-select">
          <option value="">Todas as Situações</option>
          <option value="Efetivada" <?= ($filters['situacao'] ?? '') === 'Efetivada' ? 'selected' : '' ?>>Efetivada</option>
          <option value="Preparada" <?= ($filters['situacao'] ?? '') === 'Preparada' ? 'selected' : '' ?>>Preparada (Pendente Retirada)</option>
          <option value="Cancelada" <?= ($filters['situacao'] ?? '') === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
        </select>
      </div>

      <div style="min-width: 200px;">
        <select name="tipo" class="form-select">
          <option value="">Todos os Tipos</option>
          <option value="Cesta Básica" <?= ($filters['tipo'] ?? '') === 'Cesta Básica' ? 'selected' : '' ?>>Cesta Básica</option>
          <option value="Kit Higiene" <?= ($filters['tipo'] ?? '') === 'Kit Higiene' ? 'selected' : '' ?>>Kit Higiene</option>
          <option value="Kit Enxoval" <?= ($filters['tipo'] ?? '') === 'Kit Enxoval' ? 'selected' : '' ?>>Kit Enxoval</option>
          <option value="Roupas/Agasalhos" <?= ($filters['tipo'] ?? '') === 'Roupas/Agasalhos' ? 'selected' : '' ?>>Roupas / Agasalhos</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['situacao']) || !empty($filters['tipo'])): ?>
        <a href="/beneficios" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID / Recibo</th>
          <th>Data Entrega</th>
          <th>Família Beneficiada</th>
          <th>Tipo de Benefício</th>
          <th>Itens Concedidos</th>
          <th>Responsável pela Entrega</th>
          <th>Situação</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($beneficios)): ?>
          <tr><td colspan="8" class="empty-state">Nenhuma entrega de benefício encontrada.</td></tr>
        <?php else: ?>
          <?php foreach ($beneficios as $b): ?>
            <tr>
              <td><strong><?= htmlspecialchars($b->comprovante ?? 'REC-#' . $b->id, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= FormatHelper::date($b->dataEntrega) ?></td>
              <td>
                <a href="/familias/<?= $b->familiaId ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  Família #<?= $b->familiaId ?>
                </a>
              </td>
              <td><?= htmlspecialchars($b->tipoBeneficio, ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if (empty($b->itens)): ?>
                  <span style="color: var(--text-muted); font-size: 12px;">Nenhum item adicionado</span>
                <?php else: ?>
                  <span style="font-size: 13px; font-weight: 600;"><?= count($b->itens) ?> item(ns)</span>
                  <div style="font-size: 11px; color: var(--text-muted);">
                    <?= htmlspecialchars($b->itens[0]->nomeProduto, ENT_QUOTES, 'UTF-8') ?><?= count($b->itens) > 1 ? ' e outros...' : '' ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-size: 13px;"><?= htmlspecialchars($b->responsavelDistribuicao, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::badgeStatus($b->situacao) ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/beneficios/<?= $b->id ?>" class="btn btn-secondary btn-sm">Ver Recibo</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
