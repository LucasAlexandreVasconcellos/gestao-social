<?php
use App\Models\Produto;

/** @var array $produtos */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/doacoes" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Doações</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Registrar Doação Recebida</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Cadastro de mantimentos doados ou captação de recursos financeiros para apoio institucional.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/doacoes" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <!-- 1. Identificação do Doador -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>1. Identificação do Doador / Empresa Parceira</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-3">
          <label class="form-label" for="tipoDoador">Tipo de Doador <span class="required">*</span></label>
          <select id="tipoDoador" name="tipoDoador" class="form-select" required>
            <option value="Pessoa Física">Pessoa Física</option>
            <option value="Pessoa Jurídica" selected>Pessoa Jurídica (Empresa/Parceiro)</option>
            <option value="Anônimo">Anônimo</option>
          </select>
        </div>

        <div class="form-group col-5">
          <label class="form-label" for="nomeDoador">Nome do Doador ou Razão Social <span class="required">*</span></label>
          <input type="text" id="nomeDoador" name="nomeDoador" class="form-control" required placeholder="Nome completo ou empresa" value="<?= htmlspecialchars($old['nomeDoador'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nomeDoador'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nomeDoador'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="documento">CPF / CNPJ (Se informado)</label>
          <input type="text" id="documento" name="documento" class="form-control" placeholder="00.000.000/0001-00">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="telefone">Telefone</label>
          <input type="text" id="telefone" name="telefone" class="form-control mask-phone" placeholder="(11) 90000-0000">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="email">E-mail para Agradecimento/Recibo</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="contato@empresa.com">
        </div>

        <div class="form-group col-2">
          <label class="form-label" for="dataRecebimento">Data de Entrada <span class="required">*</span></label>
          <input type="date" id="dataRecebimento" name="dataRecebimento" class="form-control" required value="<?= htmlspecialchars($old['dataRecebimento'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="responsavel">Recebido por <span class="required">*</span></label>
          <input type="text" id="responsavel" name="responsavel" class="form-control" required value="<?= htmlspecialchars($old['responsavel'] ?? 'Fernanda Coordenadora', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <!-- 2. Natureza da Doação -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>
        <span>2. Natureza e Conteúdo da Doação</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-4">
          <label class="form-label" for="tipoDoacao">Tipo de Doação <span class="required">*</span></label>
          <select id="tipoDoacao" name="tipoDoacao" class="form-select" required onchange="toggleDoacaoCampos(this.value)">
            <option value="Produtos" selected>Produtos / Alimentos / Mantimentos</option>
            <option value="Financeira">Doação Financeira (PIX / Transferência / Cheque)</option>
            <option value="Serviços/Outros">Serviços / Outros Bens</option>
          </select>
        </div>

        <div class="form-group col-4" id="campo-valor">
          <label class="form-label" for="valorEstimado">Valor Estimado ou Financeiro (R$)</label>
          <input type="text" id="valorEstimado" name="valorEstimado" class="form-control" placeholder="0,00">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="comprovante">Nº do Comprovante / Termo de Doação</label>
          <input type="text" id="comprovante" name="comprovante" class="form-control" placeholder="Ex: COMP-2026-09" value="COMP-<?= date('Y') ?>-<?= rand(10, 99) ?>">
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="descricao">Descrição / Finalidade da Doação</label>
          <input type="text" id="descricao" name="descricao" class="form-control" placeholder="Ex: Doação de campanha natalina / Apoio ao programa alimentar / Doação livre">
        </div>
      </div>

      <!-- Itens de Produtos (Aparece para Doações de Produtos) -->
      <div id="secao-itens-produtos" style="background-color: var(--bg-subtle); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <div>
            <h4 style="font-size: 13px; font-weight: 700; color: var(--text-main);">Produtos Doados para o Almoxarifado</h4>
            <span style="font-size: 11px; color: var(--text-muted);">As doações de produtos alimentam o estoque e geram movimentação de entrada.</span>
          </div>
        </div>

        <div id="itens-doacao-container" style="display: flex; flex-direction: column; gap: 12px;">
          <div class="item-row" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 220px;">
              <label class="form-label" style="font-size: 12px;">Vincular a Produto do Almoxarifado</label>
              <select name="item_produto_id[]" class="form-select" onchange="autoPreencheProduto(this)">
                <option value="">Item avulso (digite o nome ao lado)</option>
                <?php foreach ($produtos as $p): ?>
                  <option value="<?= $p->id ?>" data-nome="<?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?>" data-unidade="<?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="flex: 2; min-width: 200px;">
              <label class="form-label" style="font-size: 12px;">Nome / Descrição do Item</label>
              <input type="text" name="item_nome[]" class="form-control item-nome-input" placeholder="Ex: Cesta Básica ou Pacote de Arroz">
            </div>

            <div style="width: 100px;">
              <label class="form-label" style="font-size: 12px;">Quantidade</label>
              <input type="number" step="0.5" min="0.5" name="item_qtd[]" class="form-control" value="1">
            </div>

            <div style="width: 110px;">
              <label class="form-label" style="font-size: 12px;">Unidade</label>
              <input type="text" name="item_unidade[]" class="form-control item-unidade-input" value="Unidade">
            </div>

            <div style="width: 130px;">
              <label class="form-label" style="font-size: 12px;">Lote</label>
              <input type="text" name="item_lote[]" class="form-control" placeholder="Lote">
            </div>

            <div style="width: 140px;">
              <label class="form-label" style="font-size: 12px;">Validade</label>
              <input type="date" name="item_validade[]" class="form-control">
            </div>

            <div style="padding-bottom: 2px;">
              <button type="button" class="btn btn-outline btn-remove-item" style="color: var(--danger); border-color: #fca5a5;">Remover</button>
            </div>
          </div>
        </div>

        <button type="button" id="btn-add-doacao-item" class="btn btn-secondary btn-sm" style="margin-top: 12px;">
          + Adicionar Outro Item de Doação
        </button>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/doacoes" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Registrar Doação</button>
      </div>
    </form>
  </div>
</div>

<template id="item-doacao-row-template">
  <div class="item-row" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
    <div style="flex: 2; min-width: 220px;">
      <select name="item_produto_id[]" class="form-select" onchange="autoPreencheProduto(this)">
        <option value="">Item avulso (digite o nome ao lado)</option>
        <?php foreach ($produtos as $p): ?>
          <option value="<?= $p->id ?>" data-nome="<?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?>" data-unidade="<?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="flex: 2; min-width: 200px;">
      <input type="text" name="item_nome[]" class="form-control item-nome-input" placeholder="Ex: Cesta Básica">
    </div>

    <div style="width: 100px;">
      <input type="number" step="0.5" min="0.5" name="item_qtd[]" class="form-control" value="1">
    </div>

    <div style="width: 110px;">
      <input type="text" name="item_unidade[]" class="form-control item-unidade-input" value="Unidade">
    </div>

    <div style="width: 130px;">
      <input type="text" name="item_lote[]" class="form-control" placeholder="Lote">
    </div>

    <div style="width: 140px;">
      <input type="date" name="item_validade[]" class="form-control">
    </div>

    <div style="padding-bottom: 2px;">
      <button type="button" class="btn btn-outline btn-remove-item" style="color: var(--danger); border-color: #fca5a5;">Remover</button>
    </div>
  </div>
</template>

<script>
function toggleDoacaoCampos(tipo) {
  const secaoItens = document.getElementById('secao-itens-produtos');
  if (secaoItens) {
    secaoItens.style.display = (tipo === 'Produtos') ? 'block' : 'none';
  }
}

function autoPreencheProduto(selectElem) {
  const opt = selectElem.options[selectElem.selectedIndex];
  const row = selectElem.closest('.item-row');
  if (opt && opt.dataset.nome && row) {
    const nomeInput = row.querySelector('.item-nome-input');
    const unInput = row.querySelector('.item-unidade-input');
    if (nomeInput) nomeInput.value = opt.dataset.nome;
    if (unInput) unInput.value = opt.dataset.unidade;
  }
}
</script>
