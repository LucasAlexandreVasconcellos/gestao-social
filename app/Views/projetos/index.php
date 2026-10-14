<?php
use App\Helpers\FormatHelper;

/** @var array $projetos */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Projetos Sociais & Campanhas</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Oficinas profissionalizantes, campanhas temáticas, eventos e ações coletivas de acolhimento.</p>
  </div>
  <a href="/projetos/novo" class="btn btn-primary">+ Novo Projeto / Ação</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/projetos" method="GET" class="filter-bar">
      <div style="min-width: 200px;">
        <select name="tipo" class="form-select">
          <option value="">Todos os Tipos de Ação</option>
          <option value="Campanha" <?= ($filters['tipo'] ?? '') === 'Campanha' ? 'selected' : '' ?>>Campanhas Solidárias</option>
          <option value="Oficina" <?= ($filters['tipo'] ?? '') === 'Oficina' ? 'selected' : '' ?>>Oficinas de Capacitação</option>
          <option value="Evento" <?= ($filters['tipo'] ?? '') === 'Evento' ? 'selected' : '' ?>>Eventos Comunitários</option>
          <option value="Distribuição Coletiva" <?= ($filters['tipo'] ?? '') === 'Distribuição Coletiva' ? 'selected' : '' ?>>Distribuições Coletivas</option>
          <option value="Projeto Social" <?= ($filters['tipo'] ?? '') === 'Projeto Social' ? 'selected' : '' ?>>Projetos Continuados</option>
        </select>
      </div>

      <div style="min-width: 180px;">
        <select name="situacao" class="form-select">
          <option value="">Todas as Situações</option>
          <option value="Planejada" <?= ($filters['situacao'] ?? '') === 'Planejada' ? 'selected' : '' ?>>Planejada</option>
          <option value="Em Andamento" <?= ($filters['situacao'] ?? '') === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
          <option value="Concluída" <?= ($filters['situacao'] ?? '') === 'Concluída' ? 'selected' : '' ?>>Concluída</option>
          <option value="Cancelada" <?= ($filters['situacao'] ?? '') === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['tipo']) || !empty($filters['situacao'])): ?>
        <a href="/projetos" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Nome da Ação / Projeto</th>
          <th>Tipo</th>
          <th>Público-Alvo</th>
          <th>Período</th>
          <th>Meta de Atendidos</th>
          <th>Orçamento Previsto</th>
          <th>Situação</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($projetos)): ?>
          <tr><td colspan="8" class="empty-state">Nenhum projeto ou campanha social encontrado.</td></tr>
        <?php else: ?>
          <?php foreach ($projetos as $proj): ?>
            <tr>
              <td>
                <a href="/projetos/<?= $proj->id ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  <?= htmlspecialchars($proj->nome, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($proj->local, ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td><span class="badge badge-info"><?= htmlspecialchars($proj->tipo, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-size: 13px; max-width: 200px;"><?= htmlspecialchars($proj->publicoAlvo, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::date($proj->dataInicio) ?><?= $proj->dataEncerramento ? ' a ' . FormatHelper::date($proj->dataEncerramento) : '' ?></td>
              <td>
                <strong><?= $proj->getQuantidadeAtendidos() ?></strong> / <?= $proj->metaBeneficiarios ?> beneficiários
                <div style="font-size: 11px; color: var(--text-muted);"><?= $proj->getQuantidadeInscritos() ?> inscritos</div>
              </td>
              <td><?= FormatHelper::money($proj->orcamentoPrevisto) ?></td>
              <td><?= FormatHelper::badgeStatus($proj->situacao) ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/projetos/<?= $proj->id ?>" class="btn btn-secondary btn-sm">Ver Painel</a>
                <a href="/projetos/<?= $proj->id ?>/editar" class="btn btn-outline btn-sm">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
