<?php
use App\Helpers\FormatHelper;

/** @var array $atendimentos */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Atendimentos e Acompanhamento</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Registro de escutas qualificadas, visitas domiciliares, orientações e encaminhamentos à rede.</p>
  </div>
  <a href="/atendimentos/novo" class="btn btn-primary">+ Novo Atendimento</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/atendimentos" method="GET" class="filter-bar">
      <div style="min-width: 200px;">
        <select name="tipo" class="form-select">
          <option value="">Todos os Tipos de Atendimento</option>
          <option value="Atendimento Social" <?= ($filters['tipo'] ?? '') === 'Atendimento Social' ? 'selected' : '' ?>>Atendimento Social</option>
          <option value="Atendimento Emergencial" <?= ($filters['tipo'] ?? '') === 'Atendimento Emergencial' ? 'selected' : '' ?>>Atendimento Emergencial</option>
          <option value="Orientação" <?= ($filters['tipo'] ?? '') === 'Orientação' ? 'selected' : '' ?>>Orientação</option>
          <option value="Visita Domiciliar" <?= ($filters['tipo'] ?? '') === 'Visita Domiciliar' ? 'selected' : '' ?>>Visita Domiciliar</option>
        </select>
      </div>

      <div style="min-width: 180px;">
        <select name="situacao" class="form-select">
          <option value="">Todas as Situações</option>
          <option value="Pendente" <?= ($filters['situacao'] ?? '') === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
          <option value="Em Andamento" <?= ($filters['situacao'] ?? '') === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
          <option value="Concluído" <?= ($filters['situacao'] ?? '') === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
          <option value="Cancelado" <?= ($filters['situacao'] ?? '') === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['tipo']) || !empty($filters['situacao'])): ?>
        <a href="/atendimentos" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Família</th>
          <th>Tipo</th>
          <th>Descrição</th>
          <th>Profissional</th>
          <th>Retorno Previsto</th>
          <th>Encaminhamentos</th>
          <th>Situação</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($atendimentos)): ?>
          <tr><td colspan="9" class="empty-state">Nenhum atendimento encontrado.</td></tr>
        <?php else: ?>
          <?php foreach ($atendimentos as $at): ?>
            <tr>
              <td><?= FormatHelper::date($at->dataAtendimento) ?></td>
              <td>
                <a href="/familias/<?= $at->familiaId ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  Família #<?= $at->familiaId ?>
                </a>
              </td>
              <td><strong><?= htmlspecialchars($at->tipo, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td style="font-size: 13px; max-width: 250px;"><?= htmlspecialchars(mb_strimwidth($at->descricao, 0, 70, '...'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($at->profissionalResponsavel, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::date($at->retornoPrevisto) ?></td>
              <td>
                <?php if (empty($at->encaminhamentos)): ?>
                  <span style="color: var(--text-muted); font-size: 12px;">Nenhum</span>
                <?php else: ?>
                  <span class="badge badge-info"><?= count($at->encaminhamentos) ?> encaminhamento(s)</span>
                <?php endif; ?>
              </td>
              <td><?= FormatHelper::badgeStatus($at->situacao) ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/atendimentos/<?= $at->id ?>" class="btn btn-secondary btn-sm">Visualizar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
