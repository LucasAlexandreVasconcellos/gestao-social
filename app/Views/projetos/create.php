<?php
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/projetos" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Projetos</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Cadastrar Ação ou Projeto Social</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Definição de campanhas de arrecadação, eventos, oficinas de capacitação e metas de impacto.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/projetos" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="form-group col-8">
          <label class="form-label" for="nome">Nome do Projeto / Campanha <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" class="form-control" required placeholder="Ex: Oficina de Panificação Solidária / Campanha do Agasalho" value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nome'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nome'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="tipo">Tipo de Ação <span class="required">*</span></label>
          <select id="tipo" name="tipo" class="form-select" required>
            <option value="Campanha">Campanha de Arrecadação / Doação</option>
            <option value="Oficina" selected>Oficina de Capacitação / Geração de Renda</option>
            <option value="Evento">Evento Comunitário / Mutirão</option>
            <option value="Distribuição Coletiva">Distribuição Coletiva de Mantimentos</option>
            <option value="Projeto Social">Projeto Continuado</option>
          </select>
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="publicoAlvo">Público-Alvo / Perfil dos Participantes <span class="required">*</span></label>
          <input type="text" id="publicoAlvo" name="publicoAlvo" class="form-control" required placeholder="Ex: Mulheres chefes de família em extrema pobreza" value="<?= htmlspecialchars($old['publicoAlvo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="local">Local da Ação <span class="required">*</span></label>
          <input type="text" id="local" name="local" class="form-control" required placeholder="Ex: Sede Comunitária / Cozinha Escola / Praça Central" value="<?= htmlspecialchars($old['local'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataInicio">Data de Início <span class="required">*</span></label>
          <input type="date" id="dataInicio" name="dataInicio" class="form-control" required value="<?= htmlspecialchars($old['dataInicio'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataEncerramento">Previsão de Encerramento</label>
          <input type="date" id="dataEncerramento" name="dataEncerramento" class="form-control" value="<?= htmlspecialchars($old['dataEncerramento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="metaBeneficiarios">Meta de Pessoas / Famílias</label>
          <input type="number" min="1" id="metaBeneficiarios" name="metaBeneficiarios" class="form-control" value="<?= htmlspecialchars((string)($old['metaBeneficiarios'] ?? '50'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="responsavel">Coordenador / Responsável <span class="required">*</span></label>
          <input type="text" id="responsavel" name="responsavel" class="form-control" required value="<?= htmlspecialchars($old['responsavel'] ?? 'Fernanda Coordenadora', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="orcamentoPrevisto">Orçamento Previsto (R$)</label>
          <input type="text" id="orcamentoPrevisto" name="orcamentoPrevisto" class="form-control" placeholder="0,00" value="<?= htmlspecialchars((string)($old['orcamentoPrevisto'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="valorRealizado">Valor Realizado até o Momento (R$)</label>
          <input type="text" id="valorRealizado" name="valorRealizado" class="form-control" placeholder="0,00" value="<?= htmlspecialchars((string)($old['valorRealizado'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacao">Situação Inicial</label>
          <select id="situacao" name="situacao" class="form-select">
            <option value="Planejada" selected>Planejada</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="Concluída">Concluída</option>
          </select>
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="descricao">Objetivos e Metodologia</label>
          <textarea id="descricao" name="descricao" class="form-control" rows="3" placeholder="Descrição dos objetivos pedagógicos, materiais necessários, grade horária..."><?= htmlspecialchars($old['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/projetos" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Projeto</button>
      </div>
    </form>
  </div>
</div>
