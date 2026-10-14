<?php
use App\Helpers\FormatHelper;

/** @var array $pessoas */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Pessoas e Integrantes Cadastrados</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Cadastro individual de beneficiários e histórico de vínculos familiares.</p>
  </div>
  <a href="/pessoas/novo" class="btn btn-primary">+ Nova Pessoa</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <form action="/pessoas" method="GET" class="filter-bar">
      <div class="filter-input">
        <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou ocupação..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Pesquisar</button>
      <?php if (!empty($filters['search'])): ?>
        <a href="/pessoas" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Nome Completo</th>
          <th>Data Nasc. / Idade</th>
          <th>CPF</th>
          <th>Telefone</th>
          <th>Escolaridade</th>
          <th>Ocupação</th>
          <th>Situação</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pessoas)): ?>
          <tr><td colspan="8" class="empty-state">Nenhuma pessoa encontrada.</td></tr>
        <?php else: ?>
          <?php foreach ($pessoas as $p): ?>
            <tr>
              <td>
                <a href="/pessoas/<?= $p->id ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  <?= htmlspecialchars($p->nomeCompleto, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php if ($p->nomeSocial): ?>
                  <div style="font-size: 11px; color: var(--text-muted);">(Nome Social: <?= htmlspecialchars($p->nomeSocial, ENT_QUOTES, 'UTF-8') ?>)</div>
                <?php endif; ?>
              </td>
              <td>
                <?= FormatHelper::date($p->dataNascimento) ?>
                <?php if (!empty($p->dataNascimento)): ?>
                  <span style="font-size: 11px; color: var(--text-muted);">(<?= (new \DateTime())->diff(new \DateTime($p->dataNascimento))->y ?> anos)</span>
                <?php endif; ?>
              </td>
              <td><?= FormatHelper::cpf($p->cpf) ?></td>
              <td><?= FormatHelper::phone($p->telefone) ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($p->escolaridade, ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($p->ocupacao ?? 'Não informada', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::badgeStatus($p->situacaoCadastro) ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/pessoas/<?= $p->id ?>" class="btn btn-secondary btn-sm">Histórico</a>
                <a href="/pessoas/<?= $p->id ?>/editar" class="btn btn-outline btn-sm">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
