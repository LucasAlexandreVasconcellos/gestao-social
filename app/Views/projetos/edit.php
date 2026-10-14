<?php
use App\Models\ProjetoSocial;

/** @var ProjetoSocial $projeto */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/projetos/<?= $projeto->id ?>" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Painel da Ação</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Editar Ação / Projeto</h2>
</div>

<div class="card">
  <div class="card-body">
    <form action="/projetos/<?= $projeto->id ?>" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="form-group col-8">
          <label class="form-label" for="nome">Nome do Projeto <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" class="form-control" required value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nome'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nome'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="tipo">Tipo de Ação <span class="required">*</span></label>
          <select id="tipo" name="tipo" class="form-select" required>
            <?php foreach (['Campanha', 'Oficina', 'Evento', 'Distribuição Coletiva', 'Projeto Social'] as $t): ?>
              <option value="<?= $t ?>" <?= ($old['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="publicoAlvo">Público-Alvo <span class="required">*</span></label>
          <input type="text" id="publicoAlvo" name="publicoAlvo" class="form-control" required value="<?= htmlspecialchars($old['publicoAlvo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="local">Local da Ação <span class="required">*</span></label>
          <input type="text" id="local" name="local" class="form-control" required value="<?= htmlspecialchars($old['local'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataInicio">Data de Início <span class="required">*</span></label>
          <input type="date" id="dataInicio" name="dataInicio" class="form-control" required value="<?= htmlspecialchars($old['dataInicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataEncerramento">Data de Encerramento</label>
          <input type="date" id="dataEncerramento" name="dataEncerramento" class="form-control" value="<?= htmlspecialchars($old['dataEncerramento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="metaBeneficiarios">Meta de Beneficiários</label>
          <input type="number" min="1" id="metaBeneficiarios" name="metaBeneficiarios" class="form-control" value="<?= htmlspecialchars((string)($old['metaBeneficiarios'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="situacao">Situação</label>
          <select id="situacao" name="situacao" class="form-select">
            <?php foreach (['Planejada', 'Em Andamento', 'Concluída', 'Cancelada'] as $sit): ?>
              <option value="<?= $sit ?>" <?= ($old['situacao'] ?? '') === $sit ? 'selected' : '' ?>><?= $sit ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="orcamentoPrevisto">Orçamento Previsto (R$)</label>
          <input type="text" id="orcamentoPrevisto" name="orcamentoPrevisto" class="form-control" value="<?= htmlspecialchars((string)($old['orcamentoPrevisto'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="valorRealizado">Valor Realizado (R$)</label>
          <input type="text" id="valorRealizado" name="valorRealizado" class="form-control" value="<?= htmlspecialchars((string)($old['valorRealizado'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="responsavel">Responsável</label>
          <input type="text" id="responsavel" name="responsavel" class="form-control" value="<?= htmlspecialchars($old['responsavel'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="descricao">Objetivos e Metodologia</label>
          <textarea id="descricao" name="descricao" class="form-control" rows="3"><?= htmlspecialchars($old['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/projetos/<?= $projeto->id ?>" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>
