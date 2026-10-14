<div class="page-header">
    <div class="page-title">
        <h1>Relatórios Gerenciais e Indicadores</h1>
        <p>Geração de relatórios operacionais, diagnósticos socioassistenciais e exportação de dados</p>
    </div>
</div>

<div class="card mb-4" style="border-left: 4px solid var(--primary-color);">
    <div style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
        <span style="font-size: 1.5rem;">📊</span>
        <div>
            <h4 style="margin: 0 0 0.25rem 0; font-size: 0.95rem;">Módulo de Inteligência Socioassistencial e Prestação de Contas</h4>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                Os relatórios agregam dados consolidados para subsidiar decisões técnicas da equipe social e comprovação perante conselhos e órgãos fiscalizadores.
                <span class="badge badge-warning" style="margin-left: 0.5rem;">Modo Demonstrativo</span>
                Na versão integrada ao banco de dados, os relatórios suportarão consultas agregadas otimizadas por índices e paginação avançada.
            </p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--spacing-lg);">
    
    <!-- Relatório 1: Famílias -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spacing-md);">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    👨‍👩‍👧‍👦
                </div>
                <span class="badge badge-info">Filtros por Bairro</span>
            </div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Famílias por Bairro e Vulnerabilidade</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: var(--spacing-lg);">
                Lista detalhada de núcleos familiares cadastrados com situação cadastral, quantidade de dependentes, renda familiar total, renda per capita calculada e grau de vulnerabilidade social.
            </p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm); border-top: 1px solid var(--border-color); padding-top: var(--spacing-md);">
            <a href="/relatorios/view?tipo=familias" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">Visualizar</a>
            <a href="/relatorios/export?tipo=familias" class="btn btn-secondary btn-sm" title="Exportar arquivo CSV com codificação UTF-8">📥 CSV</a>
        </div>
    </div>

    <!-- Relatório 2: Pessoas por Faixa Etária -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spacing-md);">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    🎂
                </div>
                <span class="badge badge-success">Perfil Demográfico</span>
            </div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Pessoas Atendidas por Faixa Etária</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: var(--spacing-lg);">
                Distribuição quantitativa e percentual dos beneficiários em primeira infância (0 a 6), crianças, adolescentes, jovens, adultos e idosos (+60 anos) para dimensionamento de programas específicos.
            </p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm); border-top: 1px solid var(--border-color); padding-top: var(--spacing-md);">
            <a href="/relatorios/view?tipo=faixa_etaria" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">Visualizar</a>
        </div>
    </div>

    <!-- Relatório 3: Entregas de Benefícios -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spacing-md);">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: #ffedd5; color: #c2410c; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    📦
                </div>
                <span class="badge badge-warning">Prestação de Contas</span>
            </div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Benefícios e Mantimentos Distribuídos</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: var(--spacing-lg);">
                Histórico consolidado de provisões distribuídas (cestas básicas, fraldas, cobertores, kits de higiene), com identificação da família receptora, bairro, quantidade de itens e responsável pela liberação.
            </p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm); border-top: 1px solid var(--border-color); padding-top: var(--spacing-md);">
            <a href="/relatorios/view?tipo=entregas" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">Visualizar</a>
            <a href="/relatorios/export?tipo=entregas" class="btn btn-secondary btn-sm" title="Exportar arquivo CSV com codificação UTF-8">📥 CSV</a>
        </div>
    </div>

    <!-- Relatório 4: Posição de Estoque -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spacing-md);">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: #f1f5f9; color: #334155; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    🏷️
                </div>
                <span class="badge badge-secondary">Inventário</span>
            </div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Posição de Estoque e Validades</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: var(--spacing-lg);">
                Diagnóstico de saldos físicos disponíveis no almoxarifado, status de reposição mínima, lotes em quarentena ou vencendo no mês para planejamento de novas campanhas de arrecadação.
            </p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm); border-top: 1px solid var(--border-color); padding-top: var(--spacing-md);">
            <a href="/relatorios/view?tipo=estoque" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">Visualizar</a>
            <a href="/relatorios/export?tipo=estoque" class="btn btn-secondary btn-sm" title="Exportar arquivo CSV com codificação UTF-8">📥 CSV</a>
        </div>
    </div>

</div>
