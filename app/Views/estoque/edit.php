<?php
use App\Models\Produto;

/** @var Produto $produto */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/estoque" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Estoque</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Editar Produto <?= htmlspecialchars($produto->codigo, ENT_QUOTES, 'UTF-8') ?></h2>
</div>

<div class="card">
  <div class="card-body">
    <form action="/estoque/<?= $produto->id ?>" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="form-group col-3">
          <label class="form-label">Código Interno</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($produto->codigo, ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="nome">Nome do Produto <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" class="form-control" required value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nome'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nome'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="categoria">Categoria <span class="required">*</span></label>
          <select id="categoria" name="categoria" class="form-select" required>
            <?php foreach (['Alimentos', 'Higiene e Limpeza', 'Vestuário', 'Medicamentos/Primeiros Socorros', 'Outros'] as $cat): ?>
              <option value="<?= $cat ?>" <?= ($old['categoria'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="unidadeMedida">Unidade de Medida <span class="required">*</span></label>
          <select id="unidadeMedida" name="unidadeMedida" class="form-select" required>
            <?php foreach (['Unidade', 'Pacote', 'Kg', 'Litro', 'Fardo', 'Caixa'] as $un): ?>
              <option value="<?= $un ?>" <?= ($old['unidadeMedida'] ?? '') === $un ? 'selected' : '' ?>><?= $un ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label">Saldo Atual (Controlado por Movimentações)</label>
          <input type="text" class="form-control" value="<?= $produto->estoqueAtual ?> <?= htmlspecialchars($produto->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="estoqueMinimo">Estoque Mínimo</label>
          <input type="number" step="0.5" id="estoqueMinimo" name="estoqueMinimo" class="form-control" value="<?= htmlspecialchars((string)($old['estoqueMinimo'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="proximoVencimento">Próximo Vencimento</label>
          <input type="date" id="proximoVencimento" name="proximoVencimento" class="form-control" value="<?= htmlspecialchars($old['proximoVencimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/estoque" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>
