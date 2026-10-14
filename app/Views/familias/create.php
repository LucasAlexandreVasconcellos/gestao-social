<?php
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/familias" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Famílias</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Novo Cadastro de Família</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Preencha os dados do responsável familiar, endereço e indicadores socioeconômicos iniciais.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/familias" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <!-- 1. Dados do Responsável Familiar -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>1. Responsável Familiar (Pessoa de Referência)</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-8">
          <label class="form-label" for="pessoaReferenciaNome">Nome Completo do Responsável <span class="required">*</span></label>
          <input type="text" id="pessoaReferenciaNome" name="pessoaReferenciaNome" class="form-control" required value="<?= htmlspecialchars($old['pessoaReferenciaNome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['pessoaReferenciaNome'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['pessoaReferenciaNome'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacaoCadastral">Situação do Cadastro <span class="required">*</span></label>
          <select id="situacaoCadastral" name="situacaoCadastral" class="form-select" required>
            <option value="Em Análise" <?= ($old['situacaoCadastral'] ?? 'Em Análise') === 'Em Análise' ? 'selected' : '' ?>>Em Análise</option>
            <option value="Ativa" <?= ($old['situacaoCadastral'] ?? '') === 'Ativa' ? 'selected' : '' ?>>Ativa</option>
            <option value="Suspensa" <?= ($old['situacaoCadastral'] ?? '') === 'Suspensa' ? 'selected' : '' ?>>Suspensa</option>
            <option value="Encerrada" <?= ($old['situacaoCadastral'] ?? '') === 'Encerrada' ? 'selected' : '' ?>>Encerrada</option>
          </select>
        </div>
      </div>

      <!-- 2. Endereço e Localização -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
        <span>2. Endereço e Localização da Moradia</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-3">
          <label class="form-label" for="cep">CEP</label>
          <input type="text" id="cep" name="cep" class="form-control mask-cep" placeholder="00000-000" value="<?= htmlspecialchars($old['cep'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['cep'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['cep'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-7">
          <label class="form-label" for="logradouro">Logradouro (Rua, Av., Travessa) <span class="required">*</span></label>
          <input type="text" id="logradouro" name="logradouro" class="form-control" required value="<?= htmlspecialchars($old['logradouro'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['logradouro'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['logradouro'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-2">
          <label class="form-label" for="numero">Número <span class="required">*</span></label>
          <input type="text" id="numero" name="numero" class="form-control" required value="<?= htmlspecialchars($old['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="complemento">Complemento (Casa B, Fundos)</label>
          <input type="text" id="complemento" name="complemento" class="form-control" value="<?= htmlspecialchars($old['complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="bairro">Bairro / Comunidade <span class="required">*</span></label>
          <input type="text" id="bairro" name="bairro" class="form-control" required value="<?= htmlspecialchars($old['bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="municipio">Município <span class="required">*</span></label>
          <input type="text" id="municipio" name="municipio" class="form-control" required value="<?= htmlspecialchars($old['municipio'] ?? 'São Paulo', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-1">
          <label class="form-label" for="uf">UF <span class="required">*</span></label>
          <input type="text" id="uf" name="uf" class="form-control" maxlength="2" required value="<?= htmlspecialchars($old['uf'] ?? 'SP', ENT_QUOTES, 'UTF-8') ?>" style="text-transform: uppercase;">
        </div>
      </div>

      <!-- 3. Situação Socioeconômica e Moradia -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        <span>3. Condição Socioeconômica e Tipo de Moradia</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-4">
          <label class="form-label" for="rendaFamiliar">Renda Familiar Mensal (R$)</label>
          <input type="text" id="rendaFamiliar" name="rendaFamiliar" class="form-control" placeholder="0,00" value="<?= htmlspecialchars((string)($old['rendaFamiliar'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
          <span class="form-help">Soma de rendas fixas e eventuais de todos os membros.</span>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="despesasMensais">Despesas Mensais Estimadas (R$)</label>
          <input type="text" id="despesasMensais" name="despesasMensais" class="form-control" placeholder="0,00" value="<?= htmlspecialchars((string)($old['despesasMensais'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
          <span class="form-help">Estimativa com alimentação, aluguel, gás e remédios.</span>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacaoMoradia">Situação de Moradia <span class="required">*</span></label>
          <select id="situacaoMoradia" name="situacaoMoradia" class="form-select" required>
            <option value="Própria" <?= ($old['situacaoMoradia'] ?? '') === 'Própria' ? 'selected' : '' ?>>Própria</option>
            <option value="Alugada" <?= ($old['situacaoMoradia'] ?? '') === 'Alugada' ? 'selected' : '' ?>>Alugada</option>
            <option value="Cedida" <?= ($old['situacaoMoradia'] ?? 'Cedida') === 'Cedida' ? 'selected' : '' ?>>Cedida (Parentes/Conhecidos)</option>
            <option value="Ocupação" <?= ($old['situacaoMoradia'] ?? '') === 'Ocupação' ? 'selected' : '' ?>>Ocupação / Assentamento</option>
            <option value="Outro" <?= ($old['situacaoMoradia'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
          </select>
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="observacoes">Observações Iniciais</label>
          <textarea id="observacoes" name="observacoes" class="form-control" rows="3" placeholder="Informações relevantes sobre a situação inicial da família..."><?= htmlspecialchars($old['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/familias" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Cadastro da Família</button>
      </div>
    </form>
  </div>
</div>
