<?php
use App\Helpers\FormatHelper;

/** @var array $produtos */
/** @var array $filters */
/** @var array $produtosCriticos */
/** @var array $produtosVencendo */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Almoxarifado & Controle de Estoque</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Gestão de produtos, saldos disponíveis, estoque mínimo de segurança e lotes com validade.</p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="/estoque/movimentacoes" class="btn btn-secondary">Histórico de Movimentações</a>
    <a href="/estoque/movimentacoes/novo" class="btn btn-secondary">+ Movimentar Estoque</a>
    <a href="/estoque/novo" class="btn btn-primary">+ Novo Produto</a>
  </div>
</div>

<!-- Alertas do Almoxarifado -->
<?php if (!empty($produtosCriticos) || !empty($produtosVencendo)): ?>
  <div style="background-color: var(--warning-bg); border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px; color: #92400e;">
    <strong>Avisos de Abastecimento:</strong>
    <span style="font-size: 13px; margin-left: 6px;">
      <?= count($produtosCriticos) ?> produto(s) abaixo do estoque mínimo • <?= count($produtosVencendo) ?> produto(s) com validade nos próximos 30 dias.
    </span>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/estoque" method="GET" class="filter-bar">
      <div class="filter-input">
        <input type="text" name="search" class="form-control" placeholder="Buscar por código, produto ou categoria..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div style="min-width: 180px;">
        <select name="categoria" class="form-select">
          <option value="">Todas as Categorias</option>
          <option value="Alimentos" <?= ($filters['categoria'] ?? '') === 'Alimentos' ? 'selected' : '' ?>>Alimentos</option>
          <option value="Higiene e Limpeza" <?= ($filters['categoria'] ?? '') === 'Higiene e Limpeza' ? 'selected' : '' ?>>Higiene e Limpeza</option>
          <option value="Vestuário" <?= ($filters['categoria'] ?? '') === 'Vestuário' ? 'selected' : '' ?>>Vestuário</option>
          <option value="Medicamentos/Primeiros Socorros" <?= ($filters['categoria'] ?? '') === 'Medicamentos/Primeiros Socorros' ? 'selected' : '' ?>>Medicamentos</option>
        </select>
      </div>

      <div style="display: flex; align-items: center; gap: 6px;">
        <label style="font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
          <input type="checkbox" name="baixo" value="1" <?= !empty($filters['estoqueBaixo']) ? 'checked' : '' ?>>
          <span>Apenas Estoque Baixo</span>
        </label>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['search']) || !empty($filters['categoria']) || !empty($filters['estoqueBaixo'])): ?>
        <a href="/estoque" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Código</th>
          <th>Nome do Produto</th>
          <th>Categoria</th>
          <th>Saldo Atual</th>
          <th>Estoque Mínimo</th>
          <th>Próximo Vencimento</th>
          <th>Status</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($produtos)): ?>
          <tr><td colspan="8" class="empty-state">Nenhum produto cadastrado no almoxarifado.</td></tr>
        <?php else: ?>
          <?php foreach ($produtos as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p->codigo, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($p->categoria, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight: 700; color: <?= $p->isEstoqueBaixo() ? 'var(--danger)' : 'var(--text-main)' ?>;">
                <?= $p->estoqueAtual ?> <?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td><?= $p->estoqueMinimo ?> <?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::date($p->proximoVencimento) ?></td>
              <td>
                <?php if ($p->isEstoqueBaixo()): ?>
                  <span class="badge badge-danger">Abaixo do Mínimo</span>
                <?php else: ?>
                  <span class="badge badge-success">Regular</span>
                <?php endif; ?>

                <?php if (!empty($p->proximoVencimento) && strtotime($p->proximoVencimento) <= time() + (30 * 86400)): ?>
                  <span class="badge badge-warning" style="margin-left: 4px;">Vencimento Próximo</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/estoque/movimentacoes/novo?produtoId=<?= $p->id ?>" class="btn btn-secondary btn-sm">Movimentar</a>
                <a href="/estoque/<?= $p->id ?>/editar" class="btn btn-outline btn-sm">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
