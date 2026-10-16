<?php
use App\Helpers\FormatHelper;

/** @var string $tipo */
/** @var string $titulo */
/** @var array $dados */
/** @var array $bairros */
/** @var array $filters */
/** @var bool $anonimizado */

$anonimizado = $anonimizado ?? true;

// Prepara query strings preservando filtros e alternando modo de anonimização
$queryAnonimizado = array_merge($filters ?? [], ['tipo' => $tipo, 'anonimizado' => 1]);
$queryNominal = array_merge($filters ?? [], ['tipo' => $tipo, 'anonimizado' => 0]);
?>

<!-- Cabeçalho de Navegação e Ações -->
<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <div style="margin-bottom: 8px;">
      <a href="/relatorios" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
        <span>Voltar ao Painel de Relatórios</span>
      </a>
    </div>
    <h1 style="font-size: 22px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;"><?= $e($titulo) ?></h1>
    <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
      Dados consolidados para planejamento técnico, monitoramento socioassistencial e prestação de contas institucional.
    </p>
  </div>

  <div class="page-actions no-print" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
    <button onclick="window.print()" class="btn btn-secondary btn-sm" title="Imprimir este relatório">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      <span>Imprimir</span>
    </button>

    <?php if ($tipo !== 'faixa_etaria'): ?>
      <a href="/relatorios/exportar?<?= http_build_query($anonimizado ? $queryAnonimizado : $queryNominal) ?>" class="btn btn-primary btn-sm" title="Baixar relatório em formato CSV com codificação UTF-8">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        <span>Exportar CSV <?= $anonimizado ? '(LGPD)' : '(Nominal)' ?></span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Barra de Governança LGPD e Alternância de Mascaramento -->
<div class="card mb-4 no-print" style="background-color: var(--bg-surface); border-left: 4px solid var(--primary);">
  <div style="padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 10px;">
      <div style="color: var(--primary);">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          <path d="m9 12 2 2 4-4"></path>
        </svg>
      </div>
      <div>
        <div style="font-size: 13px; font-weight: 700; color: var(--text-main);">
          <?php if ($tipo === 'familias'): ?>
            Classificação: Dados Pessoais Sensíveis (Art. 11 da LGPD) • Base Legal: Art. 7º, III da Lei 13.709/2018
          <?php elseif ($tipo === 'faixa_etaria'): ?>
            Classificação: Dados Estatísticos Agregados (Art. 12 c/c Art. 14 da LGPD - Proteção ao Menor)
          <?php elseif ($tipo === 'entregas'): ?>
            Classificação: Prestação de Contas Pública & Transparência Social (Art. 7º, III da LGPD)
          <?php else: ?>
            Classificação: Dados Patrimoniais & Logísticos de Almoxarifado (Livre de Dados Pessoais)
          <?php endif; ?>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
          Acesso auditado por usuário e timestamp no <code>AuditLogService</code>. Destinação restrita à finalidade técnica socioassistencial.
        </div>
      </div>
    </div>

    <!-- Alternador de Privacidade para Relatórios com Dados Pessoais -->
    <?php if ($tipo === 'familias' || $tipo === 'entregas'): ?>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 12px; font-weight: 600; color: var(--text-muted);">Modo de Exibição:</span>
        <?php if ($anonimizado): ?>
          <span class="badge badge-success" style="padding: 5px 10px;">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Anonimizado (Iniciais LGPD)
          </span>
          <a href="/relatorios/visualizar?<?= http_build_query($queryNominal) ?>" class="btn btn-outline btn-sm" title="Exibir nomes completos (requer privilégio e registra na auditoria)" onclick="return confirm('ATENÇÃO: A exibição de dados nominais é auditada pelo sistema. Confirma a visualização dos nomes completos?');">
            Ver Nomes Completos
          </a>
        <?php else: ?>
          <span class="badge badge-warning" style="padding: 5px 10px;">
            ⚠️ Nominal / Identificado
          </span>
          <a href="/relatorios/visualizar?<?= http_build_query($queryAnonimizado) ?>" class="btn btn-secondary btn-sm" title="Ocultar nomes pessoais para apresentações e relatórios externos">
            Ativar Mascaramento LGPD
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Filtros específicos para Relatórios com parâmetros -->
<?php if ($tipo === 'familias'): ?>
  <div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
      <form method="GET" action="/relatorios/visualizar" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="tipo" value="familias">
        <input type="hidden" name="anonimizado" value="<?= $anonimizado ? '1' : '0' ?>">

        <div class="form-group" style="margin-bottom: 0; min-width: 220px; flex: 1;">
          <label class="form-label" for="bairro">Bairro Territorial</label>
          <select name="bairro" id="bairro" class="form-control">
            <option value="">Todos os bairros</option>
            <?php foreach ($bairros as $b): ?>
              <option value="<?= $e($b) ?>" <?= ($filters['bairro'] ?? '') === $b ? 'selected' : '' ?>><?= $e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 200px; flex: 1;">
          <label class="form-label" for="situacao">Situação Cadastral</label>
          <select name="situacao" id="situacao" class="form-control">
            <option value="">Todas as situações</option>
            <option value="Ativa" <?= ($filters['situacao'] ?? '') === 'Ativa' ? 'selected' : '' ?>>Ativa</option>
            <option value="Em Análise" <?= ($filters['situacao'] ?? '') === 'Em Análise' ? 'selected' : '' ?>>Em Análise</option>
            <option value="Suspensa" <?= ($filters['situacao'] ?? '') === 'Suspensa' ? 'selected' : '' ?>>Suspensa</option>
            <option value="Encerrada" <?= ($filters['situacao'] ?? '') === 'Encerrada' ? 'selected' : '' ?>>Encerrada</option>
          </select>
        </div>

        <button type="submit" class="btn btn-secondary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          <span>Filtrar</span>
        </button>

        <?php if (!empty($filters['bairro']) || !empty($filters['situacao'])): ?>
          <a href="/relatorios/visualizar?tipo=familias&anonimizado=<?= $anonimizado ? '1' : '0' ?>" class="btn btn-outline btn-sm" style="align-self: center;">
            Limpar Filtros
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>

<?php elseif ($tipo === 'entregas'): ?>
  <div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
      <form method="GET" action="/relatorios/visualizar" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="tipo" value="entregas">
        <input type="hidden" name="anonimizado" value="<?= $anonimizado ? '1' : '0' ?>">

        <div class="form-group" style="margin-bottom: 0; min-width: 220px; flex: 1;">
          <label class="form-label" for="situacao">Situação da Provisão / Entrega</label>
          <select name="situacao" id="situacao" class="form-control">
            <option value="">Todas as situações</option>
            <option value="Efetivada" <?= ($filters['situacao'] ?? '') === 'Efetivada' ? 'selected' : '' ?>>Efetivada</option>
            <option value="Preparada" <?= ($filters['situacao'] ?? '') === 'Preparada' ? 'selected' : '' ?>>Preparada</option>
            <option value="Cancelada" <?= ($filters['situacao'] ?? '') === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
          </select>
        </div>

        <button type="submit" class="btn btn-secondary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          <span>Filtrar</span>
        </button>

        <?php if (!empty($filters['situacao'])): ?>
          <a href="/relatorios/visualizar?tipo=entregas&anonimizado=<?= $anonimizado ? '1' : '0' ?>" class="btn btn-outline btn-sm" style="align-self: center;">
            Limpar Filtros
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- ========================================================================
     RELATÓRIO 1: Famílias por Bairro e Vulnerabilidade
     ======================================================================== -->
<?php if ($tipo === 'familias'): ?>
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <h3 class="card-title">
        <svg width="18" height="18" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
        <span>Núcleos Familiares Listados (Total: <?= count($dados) ?>)</span>
      </h3>
      <div style="display: flex; gap: 8px; align-items: center;">
        <span class="badge badge-info"><?= !empty($filters['bairro']) ? $e($filters['bairro']) : 'Todos os Bairros' ?></span>
        <?php if ($anonimizado): ?>
          <span class="badge badge-success" title="Iniciais dos nomes preservadas">LGPD Anonimizado</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body p-0">
      <?php if (empty($dados)): ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          <h3>Nenhum núcleo familiar localizado</h3>
          <p>Tente ajustar os filtros de bairro ou situação cadastral para expandir os resultados.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 100px;">Código</th>
                <th><?= $anonimizado ? 'Responsável Familiar (Anonimizado LGPD)' : 'Responsável Familiar' ?></th>
                <th>Bairro</th>
                <th>Situação</th>
                <th style="text-align: center;">Membros</th>
                <th>Renda Total</th>
                <th>Renda per Capita</th>
                <th>Grau de Vulnerabilidade</th>
                <th>Moradia</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dados as $item): ?>
                <tr>
                  <td>
                    <span style="font-family: monospace; font-weight: 700; color: var(--primary);">
                      <?= $e($item['codigo']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($anonimizado): ?>
                      <span style="font-family: monospace; font-weight: 600; color: var(--text-main);">
                        <?= FormatHelper::anonymizeName($item['responsavel']) ?>
                      </span>
                    <?php else: ?>
                      <strong><?= $e($item['responsavel']) ?></strong>
                    <?php endif; ?>
                  </td>
                  <td><?= $e($item['bairro']) ?></td>
                  <td><?= FormatHelper::badgeStatus($item['situacao']) ?></td>
                  <td style="text-align: center;"><strong><?= $item['integrantesQtd'] ?></strong></td>
                  <td><?= FormatHelper::money($item['rendaFamiliar']) ?></td>
                  <td><strong><?= FormatHelper::money($item['rendaPerCapita']) ?></strong></td>
                  <td><?= FormatHelper::badgeVulnerability($item['vulnerabilidade']) ?></td>
                  <td><span class="badge badge-secondary"><?= $e($item['moradia']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

<!-- ========================================================================
     RELATÓRIO 2: Pessoas Atendidas por Faixa Etária
     ======================================================================== -->
<?php elseif ($tipo === 'faixa_etaria'): ?>
  <?php
    $totalPessoas = array_sum($dados);
  ?>
  <div class="metrics-grid mb-4">
    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-title">Total de Beneficiários</span>
        <div class="metric-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        </div>
      </div>
      <div class="metric-value"><?= $totalPessoas ?></div>
      <div class="metric-subtitle">Indivíduos vinculados nos núcleos familiares</div>
    </div>

    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-title">Infância & Menores</span>
        <div class="metric-icon" style="background-color: #f0fdf4; color: var(--success);">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
      </div>
      <div class="metric-value">
        <?= ($dados['0 a 6 anos (Primeira Infância)'] ?? 0) + ($dados['7 a 14 anos (Crianças)'] ?? 0) + ($dados['15 a 17 anos (Adolescentes)'] ?? 0) ?>
      </div>
      <div class="metric-subtitle">
        Público prioritário de 0 a 17 anos (Art. 14 LGPD)
      </div>
    </div>

    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-title">Idosos Cadastrados</span>
        <div class="metric-icon" style="background-color: #fffbeb; color: var(--warning);">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
        </div>
      </div>
      <div class="metric-value"><?= $dados['60 anos ou mais (Idosos)'] ?? 0 ?></div>
      <div class="metric-subtitle">Beneficiários com 60 anos ou mais</div>
    </div>
  </div>

  <div class="card mb-4" style="background-color: #f0fdf4; border-left: 4px solid var(--success);">
    <div style="padding: 14px 18px; display: flex; align-items: center; gap: 12px;">
      <div style="color: var(--success);">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      </div>
      <div style="font-size: 13px; color: #166534; line-height: 1.4;">
        <strong>Salvaguarda aos Menores de Idade (Art. 14 da LGPD):</strong> Os registros de crianças e adolescentes são agregados em faixas etárias estritamente quantitativas e percentuais para fins de planejamento socioeducativo (creches, SCFV e contra-turno escolar), resguardando integralmente o sigilo de dados identificadores de menores.
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Distribuição Percentual por Ciclo Vital</h3>
    </div>
    <div class="card-body">
      <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($dados as $faixa => $qtd): 
          $pct = $totalPessoas > 0 ? round(($qtd / $totalPessoas) * 100, 1) : 0;
        ?>
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <span style="font-weight: 600; font-size: 14px; color: var(--text-main);">
                <?= $e($faixa) ?>
              </span>
              <span style="font-size: 13px; color: var(--text-muted);">
                <strong style="color: var(--text-main);"><?= $qtd ?></strong> pessoa(s) (<?= $pct ?>%)
              </span>
            </div>
            <div style="background: var(--bg-subtle); height: 14px; border-radius: 9999px; overflow: hidden; border: 1px solid var(--border-color);">
              <div style="background: linear-gradient(90deg, var(--primary) 0%, #0d9488 100%); height: 100%; width: <?= $pct ?>%; border-radius: 9999px; transition: width 0.4s ease;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

<!-- ========================================================================
     RELATÓRIO 3: Entregas de Benefícios e Mantimentos
     ======================================================================== -->
<?php elseif ($tipo === 'entregas'): ?>
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <h3 class="card-title">
        <svg width="18" height="18" fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect><line x1="12" y1="22" x2="12" y2="7"></line></svg>
        <span>Histórico Consolidado de Provisões Distribuídas (Total: <?= count($dados) ?>)</span>
      </h3>
      <span class="badge badge-info">Prestação de Contas Auditável</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($dados)): ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          <h3>Nenhuma entrega localizada</h3>
          <p>Nenhuma distribuição de benefício corresponde aos filtros selecionados.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 80px;">ID</th>
                <th>Data da Entrega</th>
                <th><?= $anonimizado ? 'Família / Beneficiário (Anonimizado LGPD)' : 'Família / Beneficiário' ?></th>
                <th>Bairro</th>
                <th>Tipo de Provisão</th>
                <th>Situação</th>
                <th>Itens Discriminados</th>
                <th>Técnico Responsável</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dados as $b): ?>
                <tr>
                  <td><strong style="color: var(--primary);">#<?= $b['id'] ?></strong></td>
                  <td><?= FormatHelper::date($b['data']) ?></td>
                  <td>
                    <?php if ($anonimizado): ?>
                      <span style="font-family: monospace; font-weight: 600; color: var(--text-main);">
                        <?= FormatHelper::anonymizeName($b['familiaNome']) ?>
                      </span><br>
                      <small class="text-muted"><?= $e($b['familiaCodigo']) ?></small>
                    <?php else: ?>
                      <strong><?= $e($b['familiaNome']) ?></strong><br>
                      <small class="text-muted"><?= $e($b['familiaCodigo']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?= $e($b['bairro']) ?></td>
                  <td><strong><?= $e($b['tipo']) ?></strong></td>
                  <td><?= FormatHelper::badgeStatus($b['situacao']) ?></td>
                  <td><?= $b['itensQtd'] ?> item(ns)</td>
                  <td><?= $e($b['responsavel']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

<!-- ========================================================================
     RELATÓRIO 4: Posição de Estoque e Validades
     ======================================================================== -->
<?php elseif ($tipo === 'estoque'): ?>
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <h3 class="card-title">
        <svg width="18" height="18" fill="none" stroke="var(--secondary)" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        <span>Inventário Físico do Almoxarifado e Prazos de Validade (Total: <?= count($dados) ?>)</span>
      </h3>
      <span class="badge badge-secondary">Dados Patrimoniais</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($dados)): ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          <h3>Nenhum produto cadastrado no catálogo</h3>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 100px;">Código</th>
                <th>Produto / Descrição</th>
                <th>Categoria</th>
                <th>Unidade</th>
                <th style="text-align: right;">Saldo Atual</th>
                <th style="text-align: right;">Estoque Mínimo</th>
                <th>Status de Reposição</th>
                <th>Próximo Vencimento</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dados as $p): ?>
                <tr>
                  <td><strong style="font-family: monospace; color: var(--primary);"><?= $e($p['codigo']) ?></strong></td>
                  <td><strong><?= $e($p['nome']) ?></strong></td>
                  <td><span class="badge badge-secondary"><?= $e($p['categoria']) ?></span></td>
                  <td><?= $e($p['unidade']) ?></td>
                  <td style="text-align: right; font-weight: 700;"><?= $p['estoqueAtual'] ?></td>
                  <td style="text-align: right; color: var(--text-muted);"><?= $p['estoqueMinimo'] ?></td>
                  <td>
                    <?php if ($p['statusEstoque'] === 'CRÍTICO'): ?>
                      <span class="badge badge-danger">⚠️ Reposição Urgente</span>
                    <?php else: ?>
                      <span class="badge badge-success">✓ Normal</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($p['vencimento']) && $p['vencimento'] !== 'N/A'): ?>
                      <?= FormatHelper::date($p['vencimento']) ?>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
