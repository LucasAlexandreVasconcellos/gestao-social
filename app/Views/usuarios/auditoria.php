<div class="page-header">
    <div class="page-title">
        <h1>Trilha de Auditoria e Segurança (LGPD)</h1>
        <p>Rastreabilidade de operações sensíveis, alterações cadastrais e movimentações no sistema</p>
    </div>
</div>

<div class="card mb-4" style="border-left: 4px solid var(--primary-color);">
    <div style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
        <span style="font-size: 1.5rem;">🔒</span>
        <div>
            <h4 style="margin: 0 0 0.25rem 0; font-size: 0.95rem;">Conformidade com a LGPD e Princípio do Menor Privilégio</h4>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                Todas as operações que inserem, modificam ou cancelam dados de famílias e pessoas em situação de vulnerabilidade são auditadas automaticamente pelo <code>AuditLogService</code>.
                <br>
                <strong>Sanitização Ativa:</strong> As descrições são submetidas a filtros automáticos de higienização que barram senhas, chaves e tokens nos logs operacionais. Na integração futura com o banco, esta tabela será gravada em modo append-only com garantia de imutabilidade relacional.
            </p>
        </div>
    </div>
</div>

<!-- Filtros de Auditoria -->
<div class="card mb-4">
    <form method="GET" action="/auditoria" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
            <label class="form-label" for="entidade">Entidade Auditada</label>
            <select name="entidade" id="entidade" class="form-control">
                <option value="">Todas as entidades</option>
                <option value="Familia" <?= ($filters['entidade'] ?? '') === 'Familia' ? 'selected' : '' ?>>Família</option>
                <option value="Pessoa" <?= ($filters['entidade'] ?? '') === 'Pessoa' ? 'selected' : '' ?>>Pessoa</option>
                <option value="AvaliacaoSocial" <?= ($filters['entidade'] ?? '') === 'AvaliacaoSocial' ? 'selected' : '' ?>>Avaliação Social</option>
                <option value="Atendimento" <?= ($filters['entidade'] ?? '') === 'Atendimento' ? 'selected' : '' ?>>Atendimento</option>
                <option value="BeneficioEntrega" <?= ($filters['entidade'] ?? '') === 'BeneficioEntrega' ? 'selected' : '' ?>>Entrega de Benefício</option>
                <option value="Estoque" <?= ($filters['entidade'] ?? '') === 'Estoque' ? 'selected' : '' ?>>Estoque / Movimentação</option>
                <option value="Doacao" <?= ($filters['entidade'] ?? '') === 'Doacao' ? 'selected' : '' ?>>Doação</option>
                <option value="ProjetoSocial" <?= ($filters['entidade'] ?? '') === 'ProjetoSocial' ? 'selected' : '' ?>>Projeto Social</option>
                <option value="Relatorio" <?= ($filters['entidade'] ?? '') === 'Relatorio' ? 'selected' : '' ?>>Relatórios e Indicadores (LGPD)</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
            <label class="form-label" for="operacao">Tipo de Operação</label>
            <select name="operacao" id="operacao" class="form-control">
                <option value="">Todas as operações</option>
                <option value="CRIACAO" <?= ($filters['operacao'] ?? '') === 'CRIACAO' ? 'selected' : '' ?>>Criação</option>
                <option value="EDICAO" <?= ($filters['operacao'] ?? '') === 'EDICAO' ? 'selected' : '' ?>>Edição</option>
                <option value="ATENDIMENTO" <?= ($filters['operacao'] ?? '') === 'ATENDIMENTO' ? 'selected' : '' ?>>Atendimento</option>
                <option value="ENTREGA" <?= ($filters['operacao'] ?? '') === 'ENTREGA' ? 'selected' : '' ?>>Entrega Efetivada</option>
                <option value="MOVIMENTACAO" <?= ($filters['operacao'] ?? '') === 'MOVIMENTACAO' ? 'selected' : '' ?>>Movimentação de Estoque</option>
                <option value="DESVINCULACAO" <?= ($filters['operacao'] ?? '') === 'DESVINCULACAO' ? 'selected' : '' ?>>Desvinculação Histórica</option>
                <option value="CONSULTA_RELATORIO" <?= ($filters['operacao'] ?? '') === 'CONSULTA_RELATORIO' ? 'selected' : '' ?>>Consulta de Relatório</option>
                <option value="EXPORTACAO_CSV" <?= ($filters['operacao'] ?? '') === 'EXPORTACAO_CSV' ? 'selected' : '' ?>>Exportação CSV (LGPD)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar Logs</button>
        <?php if (!empty($filters['entidade']) || !empty($filters['operacao'])): ?>
            <a href="/auditoria" class="btn btn-sm btn-secondary" style="align-self: center;">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabela de Logs de Auditoria -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Registros Recentes de Atividade (Total: <?= count($registros) ?>)</h3>
        <span class="badge badge-secondary">Fila FIFO em Memória</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($registros)): ?>
            <div class="empty-state">
                <p>Nenhum registro de auditoria encontrado com os filtros informados.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 160px;">Data e Hora</th>
                            <th>Operador / Usuário</th>
                            <th>Operação</th>
                            <th>Entidade</th>
                            <th>ID Ref.</th>
                            <th>Detalhes Operacionais</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $log): ?>
                            <tr>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.85rem;">
                                        <?= \App\Helpers\FormatHelper::date($log->dataHora, true) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= $e($log->usuarioNome) ?></strong><br>
                                    <small class="text-muted">UID #<?= $log->usuarioId ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match ($log->operacao) {
                                            'CRIACAO' => 'badge-success',
                                            'EDICAO' => 'badge-info',
                                            'ENTREGA' => 'badge-primary',
                                            'DESVINCULACAO' => 'badge-warning',
                                            'CANCELAMENTO' => 'badge-danger',
                                            'CONSULTA_RELATORIO' => 'badge-info',
                                            'EXPORTACAO_CSV' => 'badge-warning',
                                            default => 'badge-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $e($log->operacao) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= $e($log->entidade) ?></span>
                                </td>
                                <td>#<?= $e((string)$log->registroId) ?></td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary); max-width: 400px; word-break: break-word;">
                                    <?= $e($log->detalhes) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
