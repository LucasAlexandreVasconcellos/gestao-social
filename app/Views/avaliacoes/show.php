<?php
use App\Helpers\FormatHelper;
use App\Models\AvaliacaoSocial;
use App\Models\Familia;

/** @var AvaliacaoSocial $avaliacao */
/** @var Familia|null $familia */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/avaliacoes" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Avaliações</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Avaliação Socioassistencial #<?= $avaliacao->id ?></h2>
      <?= FormatHelper::badgeVulnerability($avaliacao->nivelVulnerabilidade) ?>
    </div>
  </div>

  <div style="display: flex; gap: 8px;">
    <?php if ($familia): ?>
      <a href="/familias/<?= $familia->id ?>" class="btn btn-secondary btn-sm">Ver Ficha da Família</a>
    <?php endif; ?>
    <button onclick="window.print()" class="btn btn-outline btn-sm no-print">Imprimir Parecer</button>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Resumo do Diagnóstico Técnico</div>
    <span style="font-size: 13px; color: var(--text-muted);">Realizada em <?= FormatHelper::date($avaliacao->dataAvaliacao) ?></span>
  </div>
  <div class="card-body">
    <div class="info-list" style="margin-bottom: 24px;">
      <div class="info-item">
        <label>Família Avaliada</label>
        <span><?= htmlspecialchars($familia ? "{$familia->codigo} — {$familia->pessoaReferenciaNome}" : "Família #{$avaliacao->familiaId}", ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Responsável Técnico</label>
        <span><?= htmlspecialchars($avaliacao->responsavelAvaliacao, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Renda Per Capita</label>
        <span><?= FormatHelper::money($avaliacao->rendaPerCapita) ?> (<?= $avaliacao->quantidadeIntegrantes ?> membros)</span>
      </div>
      <div class="info-item">
        <label>Situação Alimentar</label>
        <span><?= htmlspecialchars($avaliacao->situacaoAlimentar, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Condições de Moradia</label>
        <span><?= htmlspecialchars($avaliacao->condicoesMoradia, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Situação de Emprego</label>
        <span><?= htmlspecialchars($avaliacao->situacaoEmprego, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Próxima Reavaliação</label>
        <span style="color: var(--primary);"><?= FormatHelper::date($avaliacao->dataPrevistaReavaliacao) ?></span>
      </div>
    </div>

    <!-- Indicadores de Composição -->
    <div style="background-color: var(--bg-subtle); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
      <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Prioridades e Composição do Núcleo</label>
      <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px;">
        <span class="badge <?= $avaliacao->temCriancas ? 'badge-info' : 'badge-secondary' ?>">Crianças/Adolescentes: <?= $avaliacao->temCriancas ? 'Sim' : 'Não' ?></span>
        <span class="badge <?= $avaliacao->temIdosos ? 'badge-info' : 'badge-secondary' ?>">Idosos Dependentes: <?= $avaliacao->temIdosos ? 'Sim' : 'Não' ?></span>
        <span class="badge <?= $avaliacao->temPcd ? 'badge-info' : 'badge-secondary' ?>">PCD / Doença Crônica: <?= $avaliacao->temPcd ? 'Sim' : 'Não' ?></span>
        <span class="badge <?= $avaliacao->necessidadeAcompanhamento ? 'badge-warning' : 'badge-secondary' ?>">Acompanhamento Contínuo: <?= $avaliacao->necessidadeAcompanhamento ? 'Sim' : 'Não' ?></span>
      </div>

      <?php if (!empty($avaliacao->necessidadesPrioritarias)): ?>
        <div style="margin-top: 12px; font-size: 13px;">
          <strong>Carências Prioritárias:</strong>
          <?= implode(', ', array_map(fn($item) => htmlspecialchars(ucfirst((string)$item), ENT_QUOTES, 'UTF-8'), $avaliacao->necessidadesPrioritarias)) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Parecer Técnico -->
    <div style="border-left: 4px solid var(--primary); padding-left: 16px;">
      <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Parecer Técnico Social Fundamentado:</h4>
      <p style="font-size: 14px; line-height: 1.6; color: var(--text-main); white-space: pre-line;"><?= htmlspecialchars($avaliacao->parecerSocial, ENT_QUOTES, 'UTF-8') ?></p>
      
      <?php if (!empty($avaliacao->justificativaClassificacao)): ?>
        <div style="margin-top: 12px; font-size: 12px; color: var(--text-muted);">
          <strong>Justificativa da Classificação:</strong> <?= htmlspecialchars($avaliacao->justificativaClassificacao, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
