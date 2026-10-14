<?php
use App\Models\Familia;

/** @var array $familias */
/** @var Familia|null $familiaSelecionada */
/** @var array $integrantes */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/atendimentos" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Atendimentos</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Registrar Atendimento Social</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Registro de acolhimento presencial, atendimento emergencial, orientação socioassistencial ou visita domiciliar.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/atendimentos" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <span>1. Dados do Atendimento</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-6">
          <label class="form-label" for="familiaId">Família Atendida <span class="required">*</span></label>
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
          <label class="form-label" for="tipo">Tipo de Ação Social <span class="required">*</span></label>
          <select id="tipo" name="tipo" class="form-select" required>
            <option value="Atendimento Social">Atendimento Social Regular</option>
            <option value="Atendimento Emergencial">Atendimento Emergencial</option>
            <option value="Visita Domiciliar">Visita Domiciliar</option>
            <option value="Orientação">Orientação / Encaminhamento</option>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataAtendimento">Data do Atendimento <span class="required">*</span></label>
          <input type="date" id="dataAtendimento" name="dataAtendimento" class="form-control" required value="<?= htmlspecialchars($old['dataAtendimento'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="profissionalResponsavel">Profissional Responsável <span class="required">*</span></label>
          <input type="text" id="profissionalResponsavel" name="profissionalResponsavel" class="form-control" required placeholder="Nome do assistente ou técnico" value="<?= htmlspecialchars($old['profissionalResponsavel'] ?? 'Ana Paula Silva (Assistente Social)', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="situacao">Situação do Atendimento</label>
          <select id="situacao" name="situacao" class="form-select">
            <option value="Concluído" selected>Concluído</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="Pendente">Pendente</option>
          </select>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="retornoPrevisto">Data de Retorno Previsto</label>
          <input type="date" id="retornoPrevisto" name="retornoPrevisto" class="form-control" value="<?= htmlspecialchars($old['retornoPrevisto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="descricao">Descrição da Demanda e Escuta Qualificada <span class="required">*</span></label>
          <textarea id="descricao" name="descricao" class="form-control" rows="4" required placeholder="Relato dos fatos expostos pelo beneficiário, situação apresentada..."><?= htmlspecialchars($old['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <?php if (!empty($errors['descricao'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="providencias">Providências e Orientações Adotadas</label>
          <textarea id="providencias" name="providencias" class="form-control" rows="3" placeholder="Ações tomadas no ato, agendamentos, concessões provisórias..."><?= htmlspecialchars($old['providencias'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <!-- 2. Encaminhamento para a Rede Externa (Opcional Inicial) -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
        <span>2. Encaminhamento para Rede Socioassistencial ou Parceiros (Se houver)</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-4">
          <label class="form-label" for="encaminhamentoDestino">Serviço de Destino</label>
          <input type="text" id="encaminhamentoDestino" name="encaminhamentoDestino" class="form-control" placeholder="Ex: CRAS, CREAS, UBS, Conselho Tutelar, SINE">
        </div>

        <div class="form-group col-8">
          <label class="form-label" for="encaminhamentoMotivo">Motivo do Encaminhamento</label>
          <input type="text" id="encaminhamentoMotivo" name="encaminhamentoMotivo" class="form-control" placeholder="Ex: Inclusão no CadÚnico / Tratamento médico prioritário / Vaga de creche">
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/atendimentos" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Registrar Atendimento</button>
      </div>
    </form>
  </div>
</div>
