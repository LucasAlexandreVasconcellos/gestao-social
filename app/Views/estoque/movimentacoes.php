<?php
use App\Helpers\FormatHelper;

/** @var array $movimentacoes */
/** @var array $produtos */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/estoque" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Saldo de Estoque</a>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Movimentações do Almoxarifado</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Trilha completa de entradas por doação/compra, saídas para benefícios, ajustes e perdas.</p>
  </div>
  <a href="/estoque/movimentacoes/novo" class="btn btn-primary">+ Nova Movimentação</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/estoque/movimentacoes" method="GET" class="filter-bar">
      <div style="min-width: 220px;">
        <select name="produtoId" class="form-select">
          <option value="">Todos os Produtos</option>
          <?php foreach ($produtos as $p): ?>
            <option value="<?= $p->id ?>" <?= ((int)($filters['produtoId'] ?? 0)) === $p->id ? 'selected' : '' ?>>
              <?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="min-width: 180px;">
        <select name="tipo" class="form-select">
          <option value="">Todos os Tipos</option>
          <option value="Entrada" <?= ($filters['tipo'] ?? '') === 'Entrada' ? 'selected' : '' ?>>Entrada (Doação/Compra)</option>
          <option value="Saída" <?= ($filters['tipo'] ?? '') === 'Saída' ? 'selected' : '' ?>>Saída (Benefício/Consumo)</option>
          <option value="Ajuste" <?= ($filters['tipo'] ?? '') === 'Ajuste' ? 'selected' : '' ?>>Ajuste de Balanço</option>
          <option value="Perda" <?= ($filters['tipo'] ?? '') === 'Perda' ? 'selected' : '' ?>>Perda / Vencido / Avaria</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['produtoId']) || !empty($filters['tipo'])): ?>
        <a href="/estoque/movimentacoes" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Tipo</th>
          <th>Produto</th>
          <th>Quantidade</th>
          <th>Lote / Validade</th>
          <th>Origem / Destino</th>
          <th>Justificativa</th>
          <th>Responsável</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movimentacoes)): ?>
          <tr><td colspan="8" class="empty-state">Nenhuma movimentação de estoque registrada.</td></tr>
        <?php else: ?>
          <?php foreach ($movimentacoes as $m): ?>
            <?php
              $badgeClass = match ($m->tipo) {
                'Entrada' => 'badge-success',
                'Saída' => 'badge-info',
                'Perda' => 'badge-danger',
                default => 'badge-warning',
              };
            ?>
            <tr>
              <td><?= FormatHelper::date($m->data) ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($m->tipo, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><strong><?= htmlspecialchars($m->produtoNome, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td style="font-weight: 700;"><?= $m->quantidade ?></td>
              <td>
                <?= htmlspecialchars($m->lote ?? 'Geral', ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($m->dataValidade)): ?>
                  <div style="font-size: 11px; color: var(--text-muted);">Val: <?= FormatHelper::date($m->dataValidade) ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size: 13px;"><?= htmlspecialchars($m->origemDestino ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 13px; max-width: 200px;"><?= htmlspecialchars($m->justificativa, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($m->responsavel, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
