<?php
use App\Helpers\FormatHelper;

/** @var array $avaliacoes */
/** @var array $filters */
/** @var array $reavaliacoesPendentes */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Avaliações Socioassistenciais</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Diagnóstico de vulnerabilidades sociais, priorização de carências e pareceres técnicos.</p>
  </div>
  <a href="/avaliacoes/novo" class="btn btn-primary">+ Nova Avaliação Social</a>
</div>

<!-- Alerta de Reavaliações Pendentes -->
<?php if (!empty($reavaliacoesPendentes)): ?>
  <div style="background-color: var(--warning-bg); border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px; color: #92400e;">
    <strong><?= count($reavaliacoesPendentes) ?> Reavaliação(ões) Social(is) com prazo vencendo ou vencido:</strong>
    <ul style="margin: 6px 0 0 20px; font-size: 13px;">
      <?php foreach ($reavaliacoesPendentes as $rev): ?>
        <li>Família #<?= $rev->familiaId ?> — Data Prevista: <strong><?= FormatHelper::date($rev->dataPrevistaReavaliacao) ?></strong> (Classificação atual: <?= $rev->nivelVulnerabilidade ?>)</li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/avaliacoes" method="GET" class="filter-bar">
      <div style="min-width: 220px;">
        <select name="nivel" class="form-select">
          <option value="">Todos os Níveis de Vulnerabilidade</option>
          <option value="Crítica" <?= ($filters['nivelVulnerabilidade'] ?? '') === 'Crítica' ? 'selected' : '' ?>>Crítica</option>
          <option value="Alta" <?= ($filters['nivelVulnerabilidade'] ?? '') === 'Alta' ? 'selected' : '' ?>>Alta</option>
          <option value="Média" <?= ($filters['nivelVulnerabilidade'] ?? '') === 'Média' ? 'selected' : '' ?>>Média</option>
          <option value="Baixa" <?= ($filters['nivelVulnerabilidade'] ?? '') === 'Baixa' ? 'selected' : '' ?>>Baixa</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['nivelVulnerabilidade'])): ?>
        <a href="/avaliacoes" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Data Avaliação</th>
          <th>Família</th>
          <th>Nível de Vulnerabilidade</th>
          <th>Renda Per Capita</th>
          <th>Situação Alimentar</th>
          <th>Moradia</th>
          <th>Responsável Técnico</th>
          <th>Próxima Reavaliação</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($avaliacoes)): ?>
          <tr><td colspan="9" class="empty-state">Nenhuma avaliação socioassistencial encontrada.</td></tr>
        <?php else: ?>
          <?php foreach ($avaliacoes as $av): ?>
            <tr>
              <td><?= FormatHelper::date($av->dataAvaliacao) ?></td>
              <td>
                <a href="/familias/<?= $av->familiaId ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  Família #<?= $av->familiaId ?>
                </a>
              </td>
              <td><?= FormatHelper::badgeVulnerability($av->nivelVulnerabilidade) ?></td>
              <td><?= FormatHelper::money($av->rendaPerCapita) ?></td>
              <td><?= htmlspecialchars($av->situacaoAlimentar, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($av->condicoesMoradia, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($av->responsavelAvaliacao, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::date($av->dataPrevistaReavaliacao) ?></td>
              <td style="text-align: right;">
                <a href="/avaliacoes/<?= $av->id ?>" class="btn btn-secondary btn-sm">Ver Parecer</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
