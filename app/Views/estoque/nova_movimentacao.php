<?php
use App\Models\Produto;

/** @var array $produtos */
/** @var int $produtoId */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/estoque/movimentacoes" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Movimentações</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Lançar Movimentação de Estoque</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Registro de entrada de compras/doações avulsas, saídas, ajustes de inventário ou descarte por perda.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/estoque/movimentacoes" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="form-group col-6">
          <label class="form-label" for="produtoId">Produto <span class="required">*</span></label>
          <select id="produtoId" name="produtoId" class="form-select" required>
            <option value="">Selecione o produto...</option>
            <?php foreach ($produtos as $p): ?>
              <option value="<?= $p->id ?>" <?= ((int)($old['produtoId'] ?? $produtoId)) === $p->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?> (Saldo atual: <?= $p->estoqueAtual ?> <?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['produtoId'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['produtoId'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="tipo">Tipo de Movimentação <span class="required">*</span></label>
          <select id="tipo" name="tipo" class="form-select" required>
            <option value="Entrada">Entrada (Doação recebida / Compra)</option>
            <option value="Saída">Saída (Distribuição / Consumo)</option>
            <option value="Ajuste">Ajuste de Balanço (Acerto de Saldo)</option>
            <option value="Perda">Perda (Vencido / Avariado / Descarte)</option>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="quantidade">Quantidade <span class="required">*</span></label>
          <input type="number" step="0.5" min="0.01" id="quantidade" name="quantidade" class="form-control" required value="<?= htmlspecialchars((string)($old['quantidade'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['quantidade'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['quantidade'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="data">Data da Operação <span class="required">*</span></label>
          <input type="date" id="data" name="data" class="form-control" required value="<?= htmlspecialchars($old['data'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="lote">Lote do Produto</label>
          <input type="text" id="lote" name="lote" class="form-control" placeholder="Ex: LOTE-2026-05" value="<?= htmlspecialchars($old['lote'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataValidade">Data de Validade (Se houver)</label>
          <input type="date" id="dataValidade" name="dataValidade" class="form-control" value="<?= htmlspecialchars($old['dataValidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="responsavel">Responsável pelo Lançamento <span class="required">*</span></label>
          <input type="text" id="responsavel" name="responsavel" class="form-control" required value="<?= htmlspecialchars($old['responsavel'] ?? 'Robson Almoxarife', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="origemDestino">Origem ou Destino</label>
          <input type="text" id="origemDestino" name="origemDestino" class="form-control" placeholder="Ex: Fornecedor Alimentos S/A / Doação Cooperativa / Setor de Acolhimento" value="<?= htmlspecialchars($old['origemDestino'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="justificativa">Justificativa da Operação <span class="required">*</span></label>
          <input type="text" id="justificativa" name="justificativa" class="form-control" required placeholder="Motivo do lançamento no almoxarifado..." value="<?= htmlspecialchars($old['justificativa'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['justificativa'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['justificativa'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/estoque/movimentacoes" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Registrar Movimentação</button>
      </div>
    </form>
  </div>
</div>
