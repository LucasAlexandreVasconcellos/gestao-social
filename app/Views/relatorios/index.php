<?php
/** @var array $indicadores */
?>
<!-- Cabeçalho da Página -->
<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
      <h1 style="font-size: 22px; font-weight: 700; color: var(--text-main);">Relatórios Gerenciais, Inteligência & Governança LGPD</h1>
      <span class="badge badge-info" style="font-weight: 600;">Lei nº 13.709/2018</span>
    </div>
    <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
      Painel integrado de vigilância socioassistencial, diagnóstico territorial e prestação de contas com privacidade por padrão (Privacy by Design).
    </p>
  </div>
  <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="/auditoria?entidade=Relatorio" class="btn btn-secondary btn-sm" title="Consultar trilha de logs e acessos aos relatórios">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      <span>Trilha de Auditoria LGPD</span>
    </a>
    <button onclick="window.print()" class="btn btn-secondary btn-sm" title="Imprimir painel gerencial">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      <span>Imprimir</span>
    </button>
  </div>
</div>

<!-- Grid de Indicadores Gerenciais Rápidos (Módulo Executivo) -->
<div class="metrics-grid">
  <!-- Card 1: Núcleos Sob Acompanhamento -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Núcleos Familiares</span>
      <div class="metric-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      </div>
    </div>
    <div class="metric-value"><?= $indicadores['totalFamilias'] ?? 0 ?></div>
    <div class="metric-subtitle">
      <span style="color: var(--success); font-weight: 600;"><?= $indicadores['familiasAtivas'] ?? 0 ?> ativas</span> • 
      <span style="color: var(--danger); font-weight: 600;"><?= $indicadores['familiasVulnerabilidadeAlta'] ?? 0 ?> alta/crítica</span>
    </div>
  </div>

  <!-- Card 2: Beneficiários & Ciclos de Vida -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Cidadãos Mapeados</span>
      <div class="metric-icon" style="background-color: #f0fdf4; color: var(--success);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      </div>
    </div>
    <div class="metric-value"><?= $indicadores['totalPessoas'] ?? 0 ?></div>
    <div class="metric-subtitle">
      <span><?= $indicadores['criancasInfancia'] ?? 0 ?> crianças (0-14)</span> • 
      <span><?= $indicadores['idosos'] ?? 0 ?> idosos (60+)</span>
    </div>
  </div>

  <!-- Card 3: Provisões & Entregas -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Provisões Entregues</span>
      <div class="metric-icon" style="background-color: #fffbeb; color: var(--warning);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect><line x1="12" y1="22" x2="12" y2="7"></line></svg>
      </div>
    </div>
    <div class="metric-value"><?= $indicadores['totalEntregas'] ?? 0 ?></div>
    <div class="metric-subtitle">
      <span style="color: var(--success); font-weight: 600;"><?= $indicadores['entregasEfetivadas'] ?? 0 ?> efetivadas</span> com baixa rastreada
    </div>
  </div>

  <!-- Card 4: Almoxarifado Assistencial -->
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-title">Estoque Assistencial</span>
      <div class="metric-icon" style="background-color: #f1f5f9; color: var(--secondary);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
      </div>
    </div>
    <div class="metric-value"><?= $indicadores['totalEstoque'] ?? 0 ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-muted);">itens</span></div>
    <div class="metric-subtitle">
      <?php if (($indicadores['estoqueCritico'] ?? 0) > 0): ?>
        <span style="color: var(--danger); font-weight: 600;">⚠️ <?= $indicadores['estoqueCritico'] ?> itens com estoque baixo</span>
      <?php else: ?>
        <span style="color: var(--success); font-weight: 600;">✓ Níveis normais de segurança</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Painel de Governança e Enquadramento Legal da LGPD -->
<div class="lgpd-banner no-print">
  <div class="lgpd-banner-header">
    <div class="lgpd-banner-title">
      <svg width="22" height="22" fill="none" stroke="#38bdf8" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        <path d="m9 12 2 2 4-4"></path>
      </svg>
      <span>Governança de Dados, Sigilo Socioassistencial & LGPD (Lei 13.709/2018)</span>
    </div>
    <span class="badge" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">
      Conformidade LGPD Ativa
    </span>
  </div>

  <p style="margin: 0 0 14px 0; font-size: 13px; color: #e2e8f0; line-height: 1.5;">
    O processamento de relatórios neste sistema obedece rigorosamente aos princípios da <strong>Finalidade</strong>, <strong>Adequação</strong> e <strong>Minimização de Dados</strong>. Os dados socioassistenciais constituem informações sensíveis (Art. 11 da LGPD) e seu manuseio destina-se unicamente ao atendimento da população vulnerável e prestação de contas aos órgãos de fiscalização social (CMAS, Ministério Público e Conselhos Tutelares).
  </p>

  <div class="lgpd-banner-grid">
    <div class="lgpd-banner-item">
      <strong>Base Legal Aplicada</strong>
      Art. 7º, III e Art. 11, II, 'b' — Execução de Políticas Públicas pelo Poder Público e Entidades do Terceiro Setor.
    </div>
    <div class="lgpd-banner-item">
      <strong>Privacy by Default (Anonimização)</strong>
      Relatórios nominais possuem proteção com opção de anonimização (pseudonimização) nativa para auditorias externas.
    </div>
    <div class="lgpd-banner-item">
      <strong>Rastreabilidade Total</strong>
      Todos os acessos e downloads em CSV geram logs imutáveis vinculados ao seu usuário no <code>AuditLogService</code>.
    </div>
    <div class="lgpd-banner-item">
      <strong>Proteção a Crianças (Art. 14)</strong>
      Dados demográficos de menores de idade são agrupados estatisticamente, prevenindo qualquer exposição indevida.
    </div>
  </div>
</div>

<!-- ========================================================================
     SEÇÃO 1: Vigilância Socioassistencial e Proteção Básica
     ======================================================================== -->
<section class="report-section">
  <div class="report-section-header">
    <div class="report-section-title">
      <svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      <span>Vigilância Socioassistencial & Vulnerabilidade Territorial</span>
    </div>
    <span class="report-section-desc">Diagnósticos territoriais para concessão de benefícios eventuais e busca ativa</span>
  </div>

  <div class="reports-grid">
    <!-- Relatório 1: Famílias -->
    <div class="report-card">
      <div>
        <div class="report-card-top">
          <div class="report-icon-box report-icon-indigo">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </div>
          <span class="badge-lgpd badge-lgpd-sensitive" title="Contém dados pessoais sensíveis protegidos pelo Art. 11 da LGPD">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Dado Pessoal Sensível (Art. 11)
          </span>
        </div>

        <h3 class="report-card-title">Famílias por Bairro e Grau de Vulnerabilidade</h3>
        <p class="report-card-desc">
          Mapeamento detalhado dos núcleos familiares cadastrados com situação cadastral ativa/em análise, contagem de integrantes dependentes, renda familiar bruta, cálculo de renda per capita e classificação do grau de vulnerabilidade social.
        </p>

        <div class="report-meta-tags">
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            <span><strong>Filtros:</strong> Bairro geográfico e Situação cadastral</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span><strong>Finalidade:</strong> Concessão de benefícios eventuais e busca ativa</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span><strong>Segurança:</strong> Exportação padrão pseudonimizada (iniciais protegidas)</span>
          </div>
        </div>
      </div>

      <div class="report-card-actions">
        <a href="/relatorios/visualizar?tipo=familias&anonimizado=1" class="btn btn-primary btn-sm" title="Abrir relatório na tela com dados protegidos">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          <span>Visualizar Relatório</span>
        </a>
        <a href="/relatorios/exportar?tipo=familias&anonimizado=1" class="btn btn-secondary btn-sm" title="Baixar CSV anonimizado para auditoria ou conselho (Conformidade LGPD)">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          <span>CSV Anonimizado</span>
        </a>
        <a href="/relatorios/exportar?tipo=familias&anonimizado=0" class="btn btn-outline btn-sm" style="color: var(--danger);" title="Exportação com nomes nominais — Requer justificativa de uso e registro de auditoria" onclick="return confirm('ATENÇÃO LGPD: Você está prestes a exportar dados pessoais nominais não-anonimizados. Seu usuário e horário serão registrados na Trilha de Auditoria. Deseja prosseguir?');">
          <span>CSV Nominal</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ========================================================================
     SEÇÃO 2: Demografia, Ciclos de Vida e Proteção à Criança/Adolescente
     ======================================================================== -->
<section class="report-section">
  <div class="report-section-header">
    <div class="report-section-title">
      <svg width="20" height="20" fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
      <span>Demografia, Ciclos de Vida & Proteção à Criança e ao Adolescente</span>
    </div>
    <span class="report-section-desc">Dados estritamente agregados para dimensionamento de serviços de convivência e creches</span>
  </div>

  <div class="reports-grid">
    <!-- Relatório 2: Faixa Etária -->
    <div class="report-card">
      <div>
        <div class="report-card-top">
          <div class="report-icon-box report-icon-emerald">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <span class="badge-lgpd badge-lgpd-anon" title="Dados consolidados e anonimizados por desenho conforme Art. 12 da LGPD">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            Dado Estatístico Agregado (Art. 12/14)
          </span>
        </div>

        <h3 class="report-card-title">Pessoas Atendidas por Faixa Etária e Ciclo de Vida</h3>
        <p class="report-card-desc">
          Estratificação quantitativa e percentual dos beneficiários nos ciclos vitais: Primeira Infância (0 a 6 anos), Crianças (7 a 14), Adolescentes (15 a 17), Juventude (18 a 29), Adultos e Idosos (+60 anos) para fundamentação de programas continuados.
        </p>

        <div class="report-meta-tags">
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span><strong>Conformidade Art. 14 LGPD:</strong> Preserva o melhor interesse do menor</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span><strong>Privacidade Nativa:</strong> Nenhum dado nominal é exibido ou retido</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            <span><strong>Aplicação:</strong> SCFV, cursos de capacitação e oficinas para a terceira idade</span>
          </div>
        </div>
      </div>

      <div class="report-card-actions">
        <a href="/relatorios/visualizar?tipo=faixa_etaria" class="btn btn-primary btn-sm" style="width: 100%;">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          <span>Visualizar Gráficos e Distribuição</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ========================================================================
     SEÇÃO 3: Suprimentos, Recursos e Prestação de Contas
     ======================================================================== -->
<section class="report-section">
  <div class="report-section-header">
    <div class="report-section-title">
      <svg width="20" height="20" fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect width="20" height="5" x="2" y="7"></rect><line x1="12" y1="22" x2="12" y2="7"></line></svg>
      <span>Suprimentos, Recursos Materiais & Prestação de Contas</span>
    </div>
    <span class="report-section-desc">Transparência ativa perante conselhos fiscais, doadores e controle de estoques</span>
  </div>

  <div class="reports-grid">
    <!-- Relatório 3: Entregas -->
    <div class="report-card">
      <div>
        <div class="report-card-top">
          <div class="report-icon-box report-icon-amber">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="20 12 20 22 4 22 4 12"></polyline>
              <rect width="20" height="5" x="2" y="7"></rect>
              <line x1="12" y1="22" x2="12" y2="7"></line>
            </svg>
          </div>
          <span class="badge-lgpd badge-lgpd-public" title="Finalidade institucional de prestação de contas com dever de transparência">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            Prestação de Contas (Art. 7º, III)
          </span>
        </div>

        <h3 class="report-card-title">Benefícios e Mantimentos Distribuídos</h3>
        <p class="report-card-desc">
          Histórico consolidado de concessões de provisões (cestas de alimentos, leite especial, fraldas geriátricas/infantis, cobertores e kits de higiene), com discriminação de itens entregues, data, bairro e operador técnico responsável.
        </p>

        <div class="report-meta-tags">
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            <span><strong>Filtros:</strong> Situação da entrega (Efetivada, Preparada, Cancelada)</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span><strong>Destinação:</strong> Comprovação fiscal ao CMAS e comitês de auditoria</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
            <span><strong>Rastreabilidade:</strong> Vinculado à baixa contábil e física no almoxarifado</span>
          </div>
        </div>
      </div>

      <div class="report-card-actions">
        <a href="/relatorios/visualizar?tipo=entregas&anonimizado=1" class="btn btn-primary btn-sm" title="Visualizar histórico de entregas com nomes mascarados">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          <span>Visualizar Entregas</span>
        </a>
        <a href="/relatorios/exportar?tipo=entregas&anonimizado=1" class="btn btn-secondary btn-sm" title="Baixar CSV para prestação de contas com nomes mascarados (LGPD)">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          <span>CSV Anonimizado</span>
        </a>
        <a href="/relatorios/exportar?tipo=entregas&anonimizado=0" class="btn btn-outline btn-sm" style="color: var(--danger);" title="Exportar com identificação completa dos recebedores (auditoria interna)" onclick="return confirm('ATENÇÃO LGPD: Você está exportando a lista nominal de beneficiários. Esta ação é auditada e deve ser restrita ao uso institucional legal. Deseja continuar?');">
          <span>CSV Nominal</span>
        </a>
      </div>
    </div>

    <!-- Relatório 4: Posição de Estoque -->
    <div class="report-card">
      <div>
        <div class="report-card-top">
          <div class="report-icon-box report-icon-slate">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
              <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
          </div>
          <span class="badge-lgpd badge-lgpd-ops" title="Dados patrimoniais institucionais livres de dados pessoais">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-9"></path><path d="M14 17H5"></path><circle cx="17" cy="17" r="3"></circle><circle cx="7" cy="7" r="3"></circle></svg>
            Dado Patrimonial / Operacional
          </span>
        </div>

        <h3 class="report-card-title">Posição Física do Estoque e Validades de Lotes</h3>
        <p class="report-card-desc">
          Diagnóstico analítico dos saldos disponíveis no almoxarifado, status de cobertura em relação ao estoque mínimo de segurança, identificação de lotes em quarentena ou vencendo no mês para planejamento proativo de arrecadações.
        </p>

        <div class="report-meta-tags">
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span><strong>Privacidade:</strong> Não envolve dados pessoais (isento de restrições LGPD)</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span><strong>Prevenção:</strong> Alertas imediatos de itens vencendo em até 30 dias</span>
          </div>
          <div class="report-meta-tag">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            <span><strong>Controle:</strong> Alimentícios, higiene, vestuário e produtos de apoio</span>
          </div>
        </div>
      </div>

      <div class="report-card-actions">
        <a href="/relatorios/visualizar?tipo=estoque" class="btn btn-primary btn-sm">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          <span>Visualizar Estoque</span>
        </a>
        <a href="/relatorios/exportar?tipo=estoque" class="btn btn-secondary btn-sm" title="Exportar planilha de inventário completa">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          <span>Exportar CSV</span>
        </a>
      </div>
    </div>
  </div>
</section>
