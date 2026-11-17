<?php
use App\Helpers\FormatHelper;
use App\Helpers\IconHelper;

$dist = $distribuicaoVulnerabilidade ?? ['Crítica' => 0, 'Alta' => 0, 'Média' => 0, 'Baixa' => 0];
$totalAvaliacoesDist = max(1, array_sum($dist));
$maxBairroCount = !empty($topBairros) ? max(1, max($topBairros)) : 1;
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Painel Gerencial Socioassistencial</h1>
    <p class="page-subtitle">Vigilância territorial, atendimentos, estoque estratégico e concessões em tempo real.</p>
  </div>
  <div class="page-actions">
    <?php if ($can('atendimentos.create')): ?>
      <a href="/atendimentos/novo" class="btn btn-primary">
        <?= IconHelper::svg('plus', 15) ?>
        <span>Registrar Atendimento</span>
      </a>
    <?php endif; ?>
    <?php if ($can('familias.create')): ?>
      <a href="/familias/novo" class="btn btn-secondary">
        <?= IconHelper::svg('home', 15) ?>
        <span>Nova Família</span>
      </a>
    <?php endif; ?>
    <?php if ($can('relatorios.view')): ?>
      <a href="/relatorios" class="btn btn-secondary">
        <?= IconHelper::svg('bar-chart', 15) ?>
        <span>Relatórios BI</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Grade de KPIs Operacionais -->
<div class="stats-grid">
  <?php if ($can('familias.view')): ?>
    <div class="stat-card">
      <div class="stat-card-top">
        <span class="stat-title">Famílias Cadastradas</span>
        <span class="stat-icon-pill"><?= IconHelper::svg('home', 17) ?></span>
      </div>
      <div class="stat-value"><?= (int)$totalFamilias ?></div>
      <div class="stat-desc"><?= (int)$familiasAtivas ?> ativas • <?= (int)$familiasAnalise ?> em análise</div>
    </div>
  <?php endif; ?>

  <?php if ($can('avaliacoes.view')): ?>
    <div class="stat-card danger">
      <div class="stat-card-top">
        <span class="stat-title">Vulnerabilidade Prioritária</span>
        <span class="stat-icon-pill"><?= IconHelper::svg('alert-triangle', 17) ?></span>
      </div>
      <div class="stat-value"><?= (int)$casosVulnerabilidadePrioritaria ?></div>
      <div class="stat-desc"><?= (int)$familiasCriticas ?> críticas • <?= (int)$reavaliacoesPendentes ?> reavaliações vencendo</div>
    </div>
  <?php endif; ?>

  <?php if ($can('atendimentos.view')): ?>
    <div class="stat-card warning">
      <div class="stat-card-top">
        <span class="stat-title">Atendimentos em Aberto</span>
        <span class="stat-icon-pill"><?= IconHelper::svg('activity', 17) ?></span>
      </div>
      <div class="stat-value"><?= (int)$atendimentosPendentes ?></div>
      <div class="stat-desc"><?= (int)$totalPessoas ?> cidadãos acompanhados</div>
    </div>
  <?php endif; ?>

  <?php if ($can('beneficios.view')): ?>
    <div class="stat-card success">
      <div class="stat-card-top">
        <span class="stat-title">Benefícios Efetivados</span>
        <span class="stat-icon-pill"><?= IconHelper::svg('gift', 17) ?></span>
      </div>
      <div class="stat-value"><?= (int)$beneficiosEntregues ?></div>
      <div class="stat-desc"><?= (int)$projetosAtivos ?> ações coletivas em andamento</div>
    </div>
  <?php endif; ?>

  <?php if ($can('estoque.view')): ?>
    <div class="stat-card warning">
      <div class="stat-card-top">
        <span class="stat-title">Alertas de Suprimentos</span>
        <span class="stat-icon-pill"><?= IconHelper::svg('box', 17) ?></span>
      </div>
      <div class="stat-value"><?= (int)($produtosEstoqueBaixo + $produtosVencendo) ?></div>
      <div class="stat-desc"><?= (int)$produtosEstoqueBaixo ?> abaixo do mínimo • <?= (int)$produtosVencendo ?> vencendo</div>
    </div>
  <?php endif; ?>

  <?php if ($can('doacoes.view')): ?>
    <div class="stat-card info">
      <div class="stat-card-top">
        <span class="stat-title">Captação Financeira</span>
        <span class="stat-icon-pill"><?= IconHelper::svg('heart', 17) ?></span>
      </div>
      <div class="stat-value" style="font-size: 1.5rem;"><?= $e(FormatHelper::money((float)$doacoesValor)) ?></div>
      <div class="stat-desc">Total consolidado em doações financeiras</div>
    </div>
  <?php endif; ?>
</div>

<!-- Painel Gráfico de Vigilância Socioassistencial e Territorial -->
<div class="grid-2 mb-4">
  <?php if ($can('avaliacoes.view')): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">
          <?= IconHelper::svg('clipboard', 17) ?>
          <span>Estratificação de Vulnerabilidade Social</span>
        </h2>
        <span class="text-muted" style="font-size: 0.78rem;">Base: avaliações registradas</span>
      </div>
      <div class="card-body">
        <div class="chart-bars">
          <?php
          $niveisMeta = [
              'Crítica' => 'critica',
              'Alta' => 'alta',
              'Média' => 'media',
              'Baixa' => 'baixa',
          ];
          foreach ($niveisMeta as $label => $cls):
              $qtd = (int)($dist[$label] ?? 0);
              $pct = (int)round(($qtd / $totalAvaliacoesDist) * 100);
          ?>
            <div class="chart-bar-row">
              <div class="chart-bar-meta">
                <span class="chart-bar-label">Risco <?= $e($label) ?></span>
                <span class="chart-bar-count"><?= $qtd ?> caso(s) (<?= $pct ?>%)</span>
              </div>
              <div class="chart-bar-track">
                <div class="chart-bar-fill <?= $cls ?>" style="width: <?= $pct ?>%;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($can('familias.view')): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">
          <?= IconHelper::svg('home', 17) ?>
          <span>Concentração Territorial por Bairro</span>
        </h2>
        <span class="text-muted" style="font-size: 0.78rem;">Top 5 territórios</span>
      </div>
      <div class="card-body">
        <?php if (empty($topBairros)): ?>
          <p class="text-muted">Sem registros territoriais cadastrados até o momento.</p>
        <?php else: ?>
          <div class="chart-bars">
            <?php foreach ($topBairros as $bairro => $qtdBairro):
              $pctBairro = (int)round(($qtdBairro / $maxBairroCount) * 100);
            ?>
              <div class="chart-bar-row">
                <div class="chart-bar-meta">
                  <span class="chart-bar-label"><?= $e($bairro) ?></span>
                  <span class="chart-bar-count"><?= (int)$qtdBairro ?> família(s)</span>
                </div>
                <div class="chart-bar-track">
                  <div class="chart-bar-fill" style="width: <?= $pctBairro ?>%;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if ($can('estoque.view') && (!empty($alertasEstoque) || !empty($alertasVencimento))): ?>
  <div class="card mb-4">
    <div class="card-header">
      <h2 class="card-title">
        <?= IconHelper::svg('alert-triangle', 17) ?>
        <span>Vigilância Logística (Estoque Crítico & Validade FEFO)</span>
      </h2>
      <a href="/estoque" class="btn btn-secondary btn-sm">Gerenciar Almoxarifado</a>
    </div>
    <div class="card-body">
      <div class="grid-2">
        <div>
          <h3 class="detail-label mb-2">Itens Abaixo do Mínimo</h3>
          <?php if (empty($alertasEstoque)): ?>
            <p class="text-muted">Todos os itens estão acima do estoque mínimo configurado.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>Item</th><th>Saldo</th><th>Mínimo</th></tr></thead>
                <tbody>
                  <?php foreach ($alertasEstoque as $p): ?>
                    <tr>
                      <td><strong><?= $e($p->nome) ?></strong></td>
                      <td><span class="badge badge-danger tabular-nums"><?= $e((string)$p->estoqueAtual) ?> <?= $e($p->unidadeMedida) ?></span></td>
                      <td class="tabular-nums"><?= $e((string)$p->estoqueMinimo) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <h3 class="detail-label mb-2">Lotes Próximos do Vencimento (45 dias)</h3>
          <?php if (empty($alertasVencimento)): ?>
            <p class="text-muted">Nenhum lote com vencimento próximo nos próximos 45 dias.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>Item</th><th>Vencimento</th><th>Saldo</th></tr></thead>
                <tbody>
                  <?php foreach ($alertasVencimento as $p): ?>
                    <tr>
                      <td><strong><?= $e($p->nome) ?></strong></td>
                      <td><span class="badge badge-warning tabular-nums"><?= $e(FormatHelper::date($p->proximoVencimento)) ?></span></td>
                      <td class="tabular-nums"><?= $e((string)$p->estoqueAtual) ?> <?= $e($p->unidadeMedida) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="grid-2">
  <?php if ($can('atendimentos.view')): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">
          <?= IconHelper::svg('activity', 17) ?>
          <span>Atendimentos Recentes</span>
        </h2>
        <a href="/atendimentos" class="btn btn-secondary btn-sm">Ver todos</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Data</th><th>Tipo</th><th>Técnico</th><th>Situação</th></tr>
          </thead>
          <tbody>
            <?php if (empty($ultimosAtendimentos)): ?>
              <tr><td colspan="4" class="text-center text-muted">Nenhum atendimento registrado.</td></tr>
            <?php else: ?>
              <?php foreach ($ultimosAtendimentos as $at): ?>
                <tr>
                  <td class="tabular-nums"><?= $e(FormatHelper::date($at->dataAtendimento)) ?></td>
                  <td><a href="/atendimentos/<?= (int)$at->id ?>"><strong><?= $e($at->tipo) ?></strong></a></td>
                  <td><?= $e($at->profissionalResponsavel) ?></td>
                  <td>
                    <span class="badge <?= $at->situacao === 'Concluído' ? 'badge-success' : 'badge-warning' ?>">
                      <?= $e($at->situacao) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($can('beneficios.view')): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">
          <?= IconHelper::svg('gift', 17) ?>
          <span>Entregas Recentes de Benefícios</span>
        </h2>
        <a href="/beneficios" class="btn btn-secondary btn-sm">Ver todas</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Data</th><th>Benefício</th><th>Responsável</th><th>Situação</th></tr>
          </thead>
          <tbody>
            <?php if (empty($ultimasEntregas)): ?>
              <tr><td colspan="4" class="text-center text-muted">Nenhuma entrega registrada.</td></tr>
            <?php else: ?>
              <?php foreach ($ultimasEntregas as $b): ?>
                <tr>
                  <td class="tabular-nums"><?= $e(FormatHelper::date($b->dataEntrega)) ?></td>
                  <td><a href="/beneficios/<?= (int)$b->id ?>"><strong><?= $e($b->tipoBeneficio) ?></strong></a></td>
                  <td><?= $e($b->responsavelDistribuicao) ?></td>
                  <td>
                    <span class="badge <?= $b->situacao === 'Efetivada' ? 'badge-success' : ($b->situacao === 'Cancelada' ? 'badge-danger' : 'badge-info') ?>">
                      <?= $e($b->situacao) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
