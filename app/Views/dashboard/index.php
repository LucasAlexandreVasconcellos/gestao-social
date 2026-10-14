<?php
use App\Helpers\FormatHelper;

/** @var int $totalFamilias */
/** @var int $familiasAtivas */
/** @var int $familiasAnalise */
/** @var int $casosVulnerabilidadePrioritaria */
/** @var int $familiasCriticas */
/** @var int $totalPessoas */
/** @var int $atendimentosPendentes */
/** @var int $beneficiosEntregues */
/** @var int $produtosEstoqueBaixo */
/** @var int $produtosVencendo */
/** @var float $doacoesValor */
/** @var int $projetosAtivos */
/** @var int $reavaliacoesPendentes */
/** @var array $alertasVencimento */
/** @var array $alertasEstoque */
/** @var array $ultimosAtendimentos */
/** @var array $ultimasEntregas */
?>

<!-- Cabeçalho de Ações Rápidas -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main);">Painel de Indicadores Gerenciais</h2>
    <p style="font-size: 13px; color: var(--text-muted);">Acompanhamento integrado de famílias, vulnerabilidades, estoque e benefícios assistenciais.</p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="/familias/novo" class="btn btn-primary btn-sm">+ Cadastrar Família</a>
    <a href="/atendimentos/novo" class="btn btn-secondary btn-sm">+ Registrar Atendimento</a>
    <a href="/beneficios/novo" class="btn btn-secondary btn-sm">+ Nova Entrega</a>
    <a href="/doacoes/novo" class="btn btn-secondary btn-sm">+ Receber Doação</a>
  </div>
</div>

<!-- Alertas Críticos Prioritários -->
<?php if ($produtosEstoqueBaixo > 0 || $reavaliacoesPendentes > 0 || $familiasCriticas > 0): ?>
  <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--radius-lg); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="background-color: var(--danger); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">!</div>
      <div>
        <div style="font-weight: 700; color: #9f1239; font-size: 14px;">Atenção Operacional & Social</div>
        <div style="font-size: 13px; color: #881337;">
          Existem <strong><?= $familiasCriticas ?> casos de extrema vulnerabilidade</strong>,
          <strong><?= $reavaliacoesPendentes ?> reavaliações sociais pendentes</strong> e
          <strong><?= $produtosEstoqueBaixo ?> itens de estoque abaixo do mínimo de segurança</strong>.
        </div>
      </div>
    </div>
    <div style="display: flex; gap: 8px;">
      <a href="/avaliacoes" class="btn btn-danger btn-sm">Ver Avaliações</a>
      <a href="/estoque?baixo=1" class="btn btn-secondary btn-sm">Ver Estoque Baixo</a>
    </div>
  </div>
<?php endif; ?>

<!-- Grid de Indicadores Gerenciais (Módulo 1) -->
<div class="metrics-grid">
  <!-- Famílias Cadastradas -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Famílias Cadastradas</span>
      <div class="metric-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
      </div>
    </div>
    <div class="metric-value"><?= $totalFamilias ?></div>
    <div class="metric-subtitle">
      <span style="color: var(--success); font-weight: 600;"><?= $familiasAtivas ?> Ativas</span> • 
      <span style="color: var(--warning); font-weight: 600;"><?= $familiasAnalise ?> Em Análise</span>
    </div>
  </div>

  <!-- Pessoas Atendidas -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Pessoas / Beneficiários</span>
      <div class="metric-icon" style="background-color: #f0fdf4; color: var(--success);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      </div>
    </div>
    <div class="metric-value"><?= $totalPessoas ?></div>
    <div class="metric-subtitle">Integrantes vinculados nos núcleos</div>
  </div>

  <!-- Alta Prioridade Social -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Prioridade Social</span>
      <div class="metric-icon" style="background-color: var(--danger-bg); color: var(--danger);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
      </div>
    </div>
    <div class="metric-value" style="color: var(--danger);"><?= $casosVulnerabilidadePrioritaria ?></div>
    <div class="metric-subtitle">Vulnerabilidade Crítica ou Alta</div>
  </div>

  <!-- Atendimentos Pendentes -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Atendimentos Pendentes</span>
      <div class="metric-icon" style="background-color: var(--warning-bg); color: var(--warning);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
      </div>
    </div>
    <div class="metric-value"><?= $atendimentosPendentes ?></div>
    <div class="metric-subtitle">Aguardando providência ou visita</div>
  </div>

  <!-- Benefícios Entregues -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Entregas de Benefícios</span>
      <div class="metric-icon" style="background-color: #fdf4ff; color: #a855f7;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect></svg>
      </div>
    </div>
    <div class="metric-value"><?= $beneficiosEntregues ?></div>
    <div class="metric-subtitle">Distribuições efetivadas este ano</div>
  </div>

  <!-- Estoque em Alerta -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Estoque Crítico</span>
      <div class="metric-icon" style="background-color: var(--warning-bg); color: var(--warning);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path></svg>
      </div>
    </div>
    <div class="metric-value" style="color: <?= $produtosEstoqueBaixo > 0 ? 'var(--danger)' : 'var(--text-main)' ?>;"><?= $produtosEstoqueBaixo ?></div>
    <div class="metric-subtitle"><?= $produtosVencendo ?> itens com validade próxima</div>
  </div>

  <!-- Doações Financeiras -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Doações Financeiras</span>
      <div class="metric-icon" style="background-color: #f0fdf4; color: var(--success);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
      </div>
    </div>
    <div class="metric-value"><?= FormatHelper::money($doacoesValor) ?></div>
    <div class="metric-subtitle">Recursos diretos captados</div>
  </div>

  <!-- Projetos e Oficinas -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Projetos & Ações</span>
      <div class="metric-icon" style="background-color: #e0f2fe; color: var(--info);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
      </div>
    </div>
    <div class="metric-value"><?= $projetosAtivos ?></div>
    <div class="metric-subtitle">Campanhas/Oficinas em andamento</div>
  </div>
</div>

<!-- Painéis de Acompanhamento Recente -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
  <!-- Últimos Atendimentos -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <span>Atendimentos Recentes</span>
      </div>
      <a href="/atendimentos" class="btn btn-secondary btn-sm">Ver Todos</a>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Profissional</th>
            <th>Situação</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ultimosAtendimentos)): ?>
            <tr><td colspan="4" class="empty-state">Nenhum atendimento registrado.</td></tr>
          <?php else: ?>
            <?php foreach ($ultimosAtendimentos as $at): ?>
              <tr>
                <td><?= FormatHelper::date($at->dataAtendimento) ?></td>
                <td><a href="/atendimentos/<?= $at->id ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;"><?= htmlspecialchars($at->tipo, ENT_QUOTES, 'UTF-8') ?></a></td>
                <td style="font-size: 13px;"><?= htmlspecialchars($at->profissionalResponsavel, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= FormatHelper::badgeStatus($at->situacao) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Últimas Entregas de Benefícios -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect></svg>
        <span>Distribuição de Benefícios</span>
      </div>
      <a href="/beneficios" class="btn btn-secondary btn-sm">Ver Todas</a>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Benefício</th>
            <th>Itens</th>
            <th>Situação</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ultimasEntregas)): ?>
            <tr><td colspan="4" class="empty-state">Nenhuma entrega registrada.</td></tr>
          <?php else: ?>
            <?php foreach ($ultimasEntregas as $be): ?>
              <tr>
                <td><?= FormatHelper::date($be->dataEntrega) ?></td>
                <td><a href="/beneficios/<?= $be->id ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;"><?= htmlspecialchars($be->tipoBeneficio, ENT_QUOTES, 'UTF-8') ?></a></td>
                <td><?= count($be->itens) ?> item(ns)</td>
                <td><?= FormatHelper::badgeStatus($be->situacao) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tabela de Alertas de Vencimento e Estoque Mínimo -->
<?php if (!empty($alertasVencimento) || !empty($alertasEstoque)): ?>
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path></svg>
      <span>Alertas de Almoxarifado (Estoque Baixo & Vencimentos Próximos)</span>
    </div>
    <a href="/estoque" class="btn btn-secondary btn-sm">Gerenciar Almoxarifado</a>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Código</th>
          <th>Produto</th>
          <th>Categoria</th>
          <th>Saldo Atual</th>
          <th>Estoque Mínimo</th>
          <th>Validade</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_merge($alertasEstoque, $alertasVencimento) as $prod): ?>
          <tr>
            <td><strong><?= htmlspecialchars($prod->codigo, ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($prod->nome, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($prod->categoria, ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-weight: 700; color: <?= $prod->isEstoqueBaixo() ? 'var(--danger)' : 'var(--text-main)' ?>;">
              <?= $prod->estoqueAtual ?> <?= htmlspecialchars($prod->unidadeMedida, ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td><?= $prod->estoqueMinimo ?> <?= htmlspecialchars($prod->unidadeMedida, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= FormatHelper::date($prod->proximoVencimento) ?></td>
            <td>
              <?php if ($prod->isEstoqueBaixo()): ?>
                <span class="badge badge-danger">Estoque Baixo</span>
              <?php endif; ?>
              <?php if (!empty($prod->proximoVencimento) && strtotime($prod->proximoVencimento) <= time() + (30 * 86400)): ?>
                <span class="badge badge-warning">Validade Próxima</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
