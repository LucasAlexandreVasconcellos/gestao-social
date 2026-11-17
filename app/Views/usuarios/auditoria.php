<?php
use App\Core\View;
use App\Helpers\FormatHelper;
use App\Helpers\IconHelper;
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Trilha de Auditoria e Segurança (LGPD)</h1>
    <p class="page-subtitle">Registro append-only de operações sensíveis, alterações cadastrais e exportações.</p>
  </div>
  <?php if ($can('lgpd.manage')): ?>
    <div class="page-actions">
      <a href="/auditoria/lgpd" class="btn btn-primary">
        <?= IconHelper::svg('shield-check', 16) ?>
        <span>Governança & Anonimização LGPD</span>
      </a>
    </div>
  <?php endif; ?>
</div>

<div class="alert alert-info">
  <?= IconHelper::svg('lock', 18) ?>
  <div>
    <strong>Imutabilidade Relacional e Sanitização Ativa:</strong> Todas as operações sensíveis são registradas pelo <span class="mono">AuditLogService</span> e por triggers no PostgreSQL sem permissão de <span class="mono">UPDATE/DELETE/TRUNCATE</span> para o papel da aplicação. Senhas, chaves e tokens são filtrados automaticamente antes da gravação.
  </div>
</div>

<form method="GET" action="/auditoria" class="filter-bar">
  <div class="filter-group">
    <label class="form-label" for="entidade">Entidade Auditada</label>
    <select name="entidade" id="entidade" class="form-control">
      <option value="">Todas as entidades</option>
      <?php
      $entidades = [
          'Familia' => 'Família',
          'Pessoa' => 'Pessoa',
          'AvaliacaoSocial' => 'Avaliação Social',
          'Atendimento' => 'Atendimento',
          'BeneficioEntrega' => 'Entrega de Benefício',
          'Produto' => 'Produto / Estoque',
          'MovimentacaoEstoque' => 'Movimentação de Estoque',
          'Doacao' => 'Doação',
          'ProjetoSocial' => 'Projeto Social',
          'Documento' => 'Documento / Anexo',
          'Notificacao' => 'Notificação',
          'Usuario' => 'Usuário / Autenticação',
          'Relatorio' => 'Relatórios e Indicadores (LGPD)',
      ];
      foreach ($entidades as $k => $v):
      ?>
        <option value="<?= $e($k) ?>" <?= ($filters['entidade'] ?? '') === $k ? 'selected' : '' ?>><?= $e($v) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-group">
    <label class="form-label" for="operacao">Tipo de Operação</label>
    <select name="operacao" id="operacao" class="form-control">
      <option value="">Todas as operações</option>
      <option value="Criar" <?= ($filters['operacao'] ?? '') === 'Criar' ? 'selected' : '' ?>>Criação</option>
      <option value="Editar" <?= ($filters['operacao'] ?? '') === 'Editar' ? 'selected' : '' ?>>Edição</option>
      <option value="Login" <?= ($filters['operacao'] ?? '') === 'Login' ? 'selected' : '' ?>>Autenticação / Login</option>
      <option value="CONSULTA_RELATORIO" <?= ($filters['operacao'] ?? '') === 'CONSULTA_RELATORIO' ? 'selected' : '' ?>>Consulta de Relatório</option>
      <option value="EXPORTACAO_CSV" <?= ($filters['operacao'] ?? '') === 'EXPORTACAO_CSV' ? 'selected' : '' ?>>Exportação CSV (LGPD)</option>
      <option value="Anonimização" <?= ($filters['operacao'] ?? '') === 'Anonimização' ? 'selected' : '' ?>>Anonimização LGPD</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">
    <?= IconHelper::svg('search', 15) ?>
    <span>Filtrar Logs</span>
  </button>
  <?php if (!empty($filters['entidade']) || !empty($filters['operacao'])): ?>
    <a href="/auditoria" class="btn btn-secondary">Limpar</a>
  <?php endif; ?>
</form>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('file-text', 17) ?>
      <span>Registros de Atividade</span>
    </h2>
    <span class="badge badge-secondary tabular-nums"><?= (int)($pagination['total'] ?? count($registros)) ?> registro(s)</span>
  </div>
  <?php if (empty($registros)): ?>
    <div class="empty-state">
      <div class="empty-state-icon"><?= IconHelper::svg('file-text', 22) ?></div>
      <h4>Nenhum log encontrado</h4>
      <p>Nenhum registro de auditoria corresponde aos filtros selecionados.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Data e Hora</th>
            <th>Operador / Usuário</th>
            <th>Operação</th>
            <th>Entidade</th>
            <th>Ref.</th>
            <th>Detalhes Operacionais</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $log): ?>
            <tr>
              <td class="mono" style="white-space: nowrap; font-size: 0.81rem;">
                <?= $e(FormatHelper::datetime($log->dataHora)) ?>
              </td>
              <td>
                <strong><?= $e($log->usuarioNome) ?></strong>
                <div class="text-muted mono" style="font-size: 0.74rem;">UID #<?= (int)$log->usuarioId ?></div>
              </td>
              <td><span class="badge badge-info"><?= $e($log->operacao) ?></span></td>
              <td class="mono" style="font-size: 0.82rem;"><?= $e($log->entidade) ?></td>
              <td class="mono"><?= $log->entidadeId ? '#' . $e((string)$log->entidadeId) : '—' ?></td>
              <td style="font-size: 0.84rem; color: var(--text-secondary);"><?= $e($log->detalhes) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= View::renderPartial('partials/pagination', ['pagination' => $pagination ?? null, 'basePath' => '/auditoria', 'query' => $filters ?? []]) ?>
  <?php endif; ?>
</div>
