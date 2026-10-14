<?php
use App\Models\Familia;
use App\Models\Produto;
use App\Models\ProjetoSocial;

/** @var array $familias */
/** @var Familia|null $familiaSelecionada */
/** @var array $produtos */
/** @var array $projetos */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/beneficios" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Benefícios</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Registrar Concessão de Benefício</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Lançamento de distribuição de mantimentos, cestas e kits para famílias acompanhadas.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/beneficios" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <!-- 1. Identificação Geral da Entrega -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect></svg>
        <span>1. Dados da Concessão</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-6">
          <label class="form-label" for="familiaId">Família Beneficiada <span class="required">*</span></label>
          <select id="familiaId" name="familiaId" class="form-select" required>
            <option value="">Selecione uma família...</option>
            <?php foreach ($familias as $f): ?>
              <option value="<?= $f->id ?>" <?= ((int)($old['familiaId'] ?? 0)) === $f->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($f->codigo, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($f->pessoaReferenciaNome, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($f->bairro, ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['familiaId'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['familiaId'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="tipoBeneficio">Tipo de Benefício <span class="required">*</span></label>
          <select id="tipoBeneficio" name="tipoBeneficio" class="form-select" required>
            <option value="Cesta Básica">Cesta Básica de Alimentos</option>
            <option value="Kit Higiene">Kit Higiene e Limpeza</option>
            <option value="Kit Enxoval">Kit Gestante / Enxoval</option>
            <option value="Roupas/Agasalhos">Roupas / Agasalhos / Cobertor</option>
            <option value="Auxílio Emergencial">Auxílio Alimentar Emergencial</option>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataEntrega">Data da Entrega <span class="required">*</span></label>
          <input type="date" id="dataEntrega" name="dataEntrega" class="form-control" required value="<?= htmlspecialchars($old['dataEntrega'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="responsavelDistribuicao">Responsável pela Distribuição <span class="required">*</span></label>
          <input type="text" id="responsavelDistribuicao" name="responsavelDistribuicao" class="form-control" required placeholder="Nome do almoxarife ou técnico" value="<?= htmlspecialchars($old['responsavelDistribuicao'] ?? 'Robson Almoxarife', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="campanhaId">Campanha ou Ação Vinculada (Opcional)</label>
          <select id="campanhaId" name="campanhaId" class="form-select">
            <option value="">Distribuição regular / Avulsa</option>
            <?php foreach ($projetos as $proj): ?>
              <option value="<?= $proj->id ?>"><?= htmlspecialchars($proj->nome, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacao">Situação Inicial da Entrega <span class="required">*</span></label>
          <select id="situacao" name="situacao" class="form-select" required>
            <option value="Efetivada" selected>Efetivada (Já entregue com baixa no estoque)</option>
            <option value="Preparada">Preparada (Aguardando retirada)</option>
          </select>
          <span class="form-help">Entregas efetivadas realizam baixa automática no saldo do estoque.</span>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="comprovante">Nº do Comprovante / Recibo</label>
          <input type="text" id="comprovante" name="comprovante" class="form-control" placeholder="Ex: REC-2026-099" value="REC-<?= date('Y') ?>-<?= rand(100, 999) ?>">
        </div>

        <div class="form-group col-8">
          <label class="form-label" for="observacoes">Observações da Concessão</label>
          <input type="text" id="observacoes" name="observacoes" class="form-control" placeholder="Assinatura colhida na sede, doação específica, etc.">
        </div>
      </div>

      <!-- 2. Itens Concedidos e Quantidades -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path></svg>
        <span>2. Itens e Produtos Concedidos (Obrigatório para Efetivação)</span>
      </div>

      <?php if (!empty($errors['itens'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 16px;">
          <span><?= htmlspecialchars($errors['itens'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      <?php endif; ?>

      <div style="background-color: var(--bg-subtle); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
        <div id="itens-entrega-container" style="display: flex; flex-direction: column; gap: 12px;">
          <!-- Linha Inicial Padrão de Item -->
          <div class="item-row" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 220px;">
              <label class="form-label" style="font-size: 12px;">Produto em Estoque</label>
              <select name="produto_id[]" class="form-select" required>
                <option value="">Selecione o produto...</option>
                <?php foreach ($produtos as $p): ?>
                  <option value="<?= $p->id ?>">
                    <?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?> (Saldo: <?= $p->estoqueAtual ?> <?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="width: 120px;">
              <label class="form-label" style="font-size: 12px;">Quantidade</label>
              <input type="number" step="0.5" min="1" name="quantidade[]" class="form-control" value="1" required>
            </div>

            <div style="width: 160px;">
              <label class="form-label" style="font-size: 12px;">Lote (Opcional)</label>
              <input type="text" name="lote[]" class="form-control" placeholder="LOTE-01">
            </div>

            <div style="padding-bottom: 2px;">
              <button type="button" class="btn btn-outline btn-remove-item" style="color: var(--danger); border-color: #fca5a5;">Remover</button>
            </div>
          </div>
        </div>

        <button type="button" id="btn-add-item" class="btn btn-secondary btn-sm" style="margin-top: 12px;">
          + Adicionar Outro Produto
        </button>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/beneficios" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Entrega</button>
      </div>
    </form>
  </div>
</div>

<!-- Template HTML para Clonação Dinâmica de Linha de Item -->
<template id="item-row-template">
  <div class="item-row" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
    <div style="flex: 2; min-width: 220px;">
      <select name="produto_id[]" class="form-select" required>
        <option value="">Selecione o produto...</option>
        <?php foreach ($produtos as $p): ?>
          <option value="<?= $p->id ?>">
            <?= htmlspecialchars($p->nome, ENT_QUOTES, 'UTF-8') ?> (Saldo: <?= $p->estoqueAtual ?> <?= htmlspecialchars($p->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width: 120px;">
      <input type="number" step="0.5" min="1" name="quantidade[]" class="form-control" value="1" required>
    </div>

    <div style="width: 160px;">
      <input type="text" name="lote[]" class="form-control" placeholder="LOTE-01">
    </div>

    <div style="padding-bottom: 2px;">
      <button type="button" class="btn btn-outline btn-remove-item" style="color: var(--danger); border-color: #fca5a5;">Remover</button>
    </div>
  </div>
</template>
