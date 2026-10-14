<?php
use App\Helpers\FormatHelper;
use App\Models\ProjetoSocial;

/** @var ProjetoSocial $projeto */
/** @var array $familias */
/** @var array $pessoas */

$atendidos = $projeto->getQuantidadeAtendidos();
$inscritos = $projeto->getQuantidadeInscritos();
$progresso = $projeto->metaBeneficiarios > 0 ? min(100, round(($atendidos / $projeto->metaBeneficiarios) * 100)) : 0;
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/projetos" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Projetos</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($projeto->nome, ENT_QUOTES, 'UTF-8') ?></h2>
      <?= FormatHelper::badgeStatus($projeto->situacao) ?>
    </div>
  </div>

  <div style="display: flex; gap: 8px;">
    <a href="/projetos/<?= $projeto->id ?>/editar" class="btn btn-secondary btn-sm">Editar Ação</a>
    <button onclick="window.print()" class="btn btn-outline btn-sm no-print">Imprimir Relatório da Ação</button>
  </div>
</div>

<!-- Cards de Progresso e Métricas -->
<div class="metrics-grid" style="margin-bottom: 24px;">
  <div class="metric-card">
    <div class="metric-title">Meta de Atendidos</div>
    <div class="metric-value"><?= $atendidos ?> / <?= $projeto->metaBeneficiarios ?></div>
    <div class="metric-subtitle">
      <div style="background-color: var(--border-color); height: 6px; border-radius: 9999px; margin-top: 6px; overflow: hidden;">
        <div style="background-color: var(--primary); height: 100%; width: <?= $progresso ?>%;"></div>
      </div>
      <div style="margin-top: 4px;"><?= $progresso ?>% da meta atingida (<?= $inscritos ?> inscritos)</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-title">Orçamento Financeiro</div>
    <div class="metric-value" style="font-size: 22px;"><?= FormatHelper::money($projeto->valorRealizado) ?></div>
    <div class="metric-subtitle">Previsto: <?= FormatHelper::money($projeto->orcamentoPrevisto) ?></div>
  </div>

  <div class="metric-card">
    <div class="metric-title">Período de Execução</div>
    <div class="metric-value" style="font-size: 18px;"><?= FormatHelper::date($projeto->dataInicio) ?></div>
    <div class="metric-subtitle">Até <?= FormatHelper::date($projeto->dataEncerramento) ?></div>
  </div>

  <div class="metric-card">
    <div class="metric-title">Coordenação / Local</div>
    <div class="metric-value" style="font-size: 16px;"><?= htmlspecialchars($projeto->responsavel, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="metric-subtitle"><?= htmlspecialchars($projeto->local, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
</div>

<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">Informações e Público-Alvo</div>
  </div>
  <div class="card-body">
    <div class="info-list" style="margin-bottom: 16px;">
      <div class="info-item">
        <label>Tipo de Ação</label>
        <span><?= htmlspecialchars($projeto->tipo, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Público-Alvo Prioritário</label>
        <span><?= htmlspecialchars($projeto->publicoAlvo, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Local de Realização</label>
        <span><?= htmlspecialchars($projeto->local, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>

    <?php if (!empty($projeto->descricao)): ?>
      <div style="background-color: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md);">
        <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Objetivos e Metodologia</label>
        <p style="font-size: 13px; margin-top: 4px; line-height: 1.6;"><?= nl2br(htmlspecialchars($projeto->descricao, ENT_QUOTES, 'UTF-8')) ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Gestão de Participantes (Inscritos, Convocados, Efetivamente Atendidos, Ausentes) -->
<div id="participantes" class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
      <span>Controle de Participantes & Presença Efetiva</span>
    </div>
    <span style="font-size: 12px; color: var(--text-muted);"><?= count($projeto->participantes) ?> registro(s)</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Beneficiário / Participante</th>
          <th>Família Vinculada</th>
          <th>Situação de Participação</th>
          <th>Data de Presença</th>
          <th>Observações</th>
          <th style="text-align: right;" class="no-print">Atualizar Presença</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($projeto->participantes)): ?>
          <tr><td colspan="6" class="empty-state">Nenhum participante inscrito nesta ação até o momento.</td></tr>
        <?php else: ?>
          <?php foreach ($projeto->participantes as $part): ?>
            <?php
              $statusBadge = match ($part->situacaoParticipacao) {
                'Efetivamente Atendido' => 'badge-success',
                'Convocado' => 'badge-info',
                'Ausente' => 'badge-danger',
                default => 'badge-warning',
              };
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($part->nomePessoa, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= $part->familiaId ? "Família #{$part->familiaId}" : 'Avulso' ?></td>
              <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($part->situacaoParticipacao, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= FormatHelper::date($part->dataPresenca) ?></td>
              <td style="font-size: 12px;"><?= htmlspecialchars($part->observacoes ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align: right;" class="no-print">
                <form action="/projetos/<?= $projeto->id ?>/presenca" method="POST" style="display: flex; gap: 4px; justify-content: flex-end; align-items: center; margin: 0;">
                  <?= \App\Core\View::render('partials/csrf', [], null) ?>
                  <input type="hidden" name="participanteId" value="<?= $part->id ?>">
                  <select name="situacaoParticipacao" class="form-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="this.form.submit()">
                    <option value="Inscrito" <?= $part->situacaoParticipacao === 'Inscrito' ? 'selected' : '' ?>>Inscrito</option>
                    <option value="Convocado" <?= $part->situacaoParticipacao === 'Convocado' ? 'selected' : '' ?>>Convocado</option>
                    <option value="Efetivamente Atendido" <?= $part->situacaoParticipacao === 'Efetivamente Atendido' ? 'selected' : '' ?>>Efetivamente Atendido</option>
                    <option value="Ausente" <?= $part->situacaoParticipacao === 'Ausente' ? 'selected' : '' ?>>Ausente</option>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Inscrição de Novo Participante -->
  <div class="card-body no-print" style="background-color: var(--bg-subtle); border-top: 1px solid var(--border-color);">
    <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 12px;">+ Inscrever Participante no Projeto</h4>
    <form action="/projetos/<?= $projeto->id ?>/inscrever" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid">
        <div class="form-group col-5">
          <label class="form-label" for="partNome">Nome do Participante <span class="required">*</span></label>
          <input type="text" id="partNome" name="nomePessoa" class="form-control" required placeholder="Digite o nome completo do participante">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="partFam">Vincular a Família Cadastrada (Opcional)</label>
          <select id="partFam" name="familiaId" class="form-select">
            <option value="">Participante Avulso / Não Vinculado</option>
            <?php foreach ($familias as $f): ?>
              <option value="<?= $f->id ?>"><?= htmlspecialchars($f->codigo, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($f->pessoaReferenciaNome, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-3" style="display: flex; align-items: flex-end;">
          <button type="submit" class="btn btn-primary" style="width: 100%;">Inscrever</button>
        </div>
      </div>
    </form>
  </div>
</div>
