<?php
use App\Helpers\FormatHelper;

/** @var array $familias */
/** @var array $filters */
/** @var array $bairros */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Cadastro de Famílias</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Mapeamento, acompanhamento e histórico das famílias em vulnerabilidade social.</p>
  </div>
  <a href="/familias/novo" class="btn btn-primary">+ Nova Família</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom: 8px;">
    <!-- Barra de Filtros e Busca -->
    <form action="/familias" method="GET" class="filter-bar">
      <div class="filter-input">
        <input type="text" name="search" class="form-control" placeholder="Buscar por código, nome da referência ou bairro..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div style="min-width: 160px;">
        <select name="situacao" class="form-select">
          <option value="">Todas as Situações</option>
          <option value="Em Análise" <?= ($filters['situacao'] ?? '') === 'Em Análise' ? 'selected' : '' ?>>Em Análise</option>
          <option value="Ativa" <?= ($filters['situacao'] ?? '') === 'Ativa' ? 'selected' : '' ?>>Ativa</option>
          <option value="Suspensa" <?= ($filters['situacao'] ?? '') === 'Suspensa' ? 'selected' : '' ?>>Suspensa</option>
          <option value="Encerrada" <?= ($filters['situacao'] ?? '') === 'Encerrada' ? 'selected' : '' ?>>Encerrada</option>
        </select>
      </div>

      <div style="min-width: 160px;">
        <select name="bairro" class="form-select">
          <option value="">Todos os Bairros</option>
          <?php foreach ($bairros as $bairro): ?>
            <option value="<?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['bairro'] ?? '') === $bairro ? 'selected' : '' ?>>
              <?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary">Filtrar</button>
      <?php if (!empty($filters['search']) || !empty($filters['situacao']) || !empty($filters['bairro'])): ?>
        <a href="/familias" class="btn btn-outline">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Código</th>
          <th>Responsável Familiar</th>
          <th>Bairro / Município</th>
          <th>Renda Familiar</th>
          <th>Moradia</th>
          <th>Situação</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($familias)): ?>
          <tr>
            <td colspan="7" class="empty-state">
              <h3>Nenhuma família encontrada</h3>
              <p>Não encontramos famílias correspondentes aos filtros informados.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($familias as $f): ?>
            <tr>
              <td><strong><?= htmlspecialchars($f->codigo, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td>
                <a href="/familias/<?= $f->id ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                  <?= htmlspecialchars($f->pessoaReferenciaNome, ENT_QUOTES, 'UTF-8') ?>
                </a>
              </td>
              <td><?= htmlspecialchars($f->bairro, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($f->uf, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::money($f->rendaFamiliar) ?></td>
              <td><?= htmlspecialchars($f->situacaoMoradia, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::badgeStatus($f->situacaoCadastral) ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="/familias/<?= $f->id ?>" class="btn btn-secondary btn-sm">Ver Ficha</a>
                <a href="/familias/<?= $f->id ?>/editar" class="btn btn-outline btn-sm">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
