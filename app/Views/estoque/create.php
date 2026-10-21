<?php
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/estoque" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Estoque</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Cadastrar Produto no Almoxarifado</h2>
</div>

<div class="card">
  <div class="card-body">
    <form action="/estoque" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="form-group col-3">
          <label class="form-label" for="codigo">Código Interno</label>
          <input type="text" id="codigo" name="codigo" class="form-control" placeholder="Gerado automaticamente se vazio" value="<?= htmlspecialchars($old['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="nome">Nome do Produto <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" class="form-control" required placeholder="Ex: Arroz Agulhinha Tipo 1 5kg" value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nome'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nome'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="categoria">Categoria <span class="required">*</span></label>
          <select id="categoria" name="categoria" class="form-select" required>
            <option value="Alimentos">Alimentos</option>
            <option value="Higiene e Limpeza">Higiene e Limpeza</option>
            <option value="Vestuário">Vestuário</option>
            <option value="Medicamentos/Primeiros Socorros">Medicamentos</option>
            <option value="Outros">Outros</option>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="unidadeMedida">Unidade de Medida <span class="required">*</span></label>
          <select id="unidadeMedida" name="unidadeMedida" class="form-select" required>
            <option value="Unidade">Unidade (un)</option>
            <option value="Pacote">Pacote (pct)</option>
            <option value="Kg">Quilograma (kg)</option>
            <option value="Litro">Litro (l)</option>
            <option value="Fardo">Fardo</option>
            <option value="Caixa">Caixa (cx)</option>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="estoqueAtual">Estoque Inicial</label>
          <input type="number" step="0.5" id="estoqueAtual" name="estoqueAtual" class="form-control" value="<?= htmlspecialchars((string)($old['estoqueAtual'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="estoqueMinimo">Estoque Mínimo de Segurança</label>
          <input type="number" step="0.5" id="estoqueMinimo" name="estoqueMinimo" class="form-control" value="<?= htmlspecialchars((string)($old['estoqueMinimo'] ?? '10'), ENT_QUOTES, 'UTF-8') ?>">
          <span class="form-help">Dispara alerta visual quando atingido.</span>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="proximoVencimento">Validade Estimada (Lote Inicial)</label>
          <input type="date" id="proximoVencimento" name="proximoVencimento" class="form-control" value="<?= htmlspecialchars($old['proximoVencimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/estoque" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Produto</button>
      </div>
    </form>
  </div>
</div>
