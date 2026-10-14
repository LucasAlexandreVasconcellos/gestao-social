<div class="page-header">
    <div class="page-title">
        <a href="/relatorios" class="btn btn-secondary btn-sm mb-2" style="display: inline-flex; align-items: center; gap: 0.25rem;">
            ← Voltar para Relatórios
        </a>
        <h1><?= $e($titulo) ?></h1>
        <p>Dados consolidados para planejamento e prestação de contas institucional</p>
    </div>
    <div class="page-actions" style="display: flex; gap: var(--spacing-sm);">
        <button onclick="window.print()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.25rem;">
            🖨️ Imprimir
        </button>
        <?php if ($tipo !== 'faixa_etaria'): ?>
            <a href="/relatorios/export?tipo=<?= $e($tipo) ?>&<?= http_build_query($filters ?? []) ?>" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.25rem;">
                📥 Exportar CSV
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros específicos para Relatórios com parâmetros -->
<?php if ($tipo === 'familias'): ?>
    <div class="card mb-4 no-print">
        <form method="GET" action="/relatorios/view" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tipo" value="familias">
            <div class="form-group" style="margin-bottom: 0; min-width: 220px;">
                <label class="form-label" for="bairro">Bairro</label>
                <select name="bairro" id="bairro" class="form-control">
                    <option value="">Todos os bairros</option>
                    <?php foreach ($bairros as $b): ?>
                        <option value="<?= $e($b) ?>" <?= ($filters['bairro'] ?? '') === $b ? 'selected' : '' ?>><?= $e($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label class="form-label" for="situacao">Situação Cadastral</label>
                <select name="situacao" id="situacao" class="form-control">
                    <option value="">Todas as situações</option>
                    <option value="Ativa" <?= ($filters['situacao'] ?? '') === 'Ativa' ? 'selected' : '' ?>>Ativa</option>
                    <option value="Em Análise" <?= ($filters['situacao'] ?? '') === 'Em Análise' ? 'selected' : '' ?>>Em Análise</option>
                    <option value="Suspensa" <?= ($filters['situacao'] ?? '') === 'Suspensa' ? 'selected' : '' ?>>Suspensa</option>
                    <option value="Encerrada" <?= ($filters['situacao'] ?? '') === 'Encerrada' ? 'selected' : '' ?>>Encerrada</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <?php if (!empty($filters['bairro']) || !empty($filters['situacao'])): ?>
                <a href="/relatorios/view?tipo=familias" class="btn btn-sm btn-secondary" style="align-self: center;">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
<?php elseif ($tipo === 'entregas'): ?>
    <div class="card mb-4 no-print">
        <form method="GET" action="/relatorios/view" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tipo" value="entregas">
            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label class="form-label" for="situacao">Situação da Entrega</label>
                <select name="situacao" id="situacao" class="form-control">
                    <option value="">Todas</option>
                    <option value="Efetivada" <?= ($filters['situacao'] ?? '') === 'Efetivada' ? 'selected' : '' ?>>Efetivada</option>
                    <option value="Preparada" <?= ($filters['situacao'] ?? '') === 'Preparada' ? 'selected' : '' ?>>Preparada</option>
                    <option value="Cancelada" <?= ($filters['situacao'] ?? '') === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
    </div>
<?php endif; ?>

<!-- Conteúdo de acordo com o Tipo -->
<?php if ($tipo === 'familias'): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Núcleos Familiares Listados (Total: <?= count($dados) ?>)</h3>
            <span class="badge badge-info"><?= !empty($filters['bairro']) ? $e($filters['bairro']) : 'Todos os Bairros' ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($dados)): ?>
                <div class="empty-state">
                    <p>Nenhuma família localizada com os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Responsável Familiar</th>
                                <th>Bairro</th>
                                <th>Situação</th>
                                <th>Membros</th>
                                <th>Renda Total</th>
                                <th>Renda per Capita</th>
                                <th>Vulnerabilidade</th>
                                <th>Moradia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $item): ?>
                                <tr>
                                    <td><strong><?= $e($item['codigo']) ?></strong></td>
                                    <td><?= $e($item['responsavel']) ?></td>
                                    <td><?= $e($item['bairro']) ?></td>
                                    <td><?= \App\Helpers\FormatHelper::situacaoCadastralBadge($item['situacao']) ?></td>
                                    <td><?= $item['integrantesQtd'] ?></td>
                                    <td><?= \App\Helpers\FormatHelper::money($item['rendaFamiliar']) ?></td>
                                    <td><strong><?= \App\Helpers\FormatHelper::money($item['rendaPerCapita']) ?></strong></td>
                                    <td><?= \App\Helpers\FormatHelper::vulnerabilidadeBadge($item['vulnerabilidade']) ?></td>
                                    <td><?= $e($item['moradia']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($tipo === 'faixa_etaria'): ?>
    <?php
        $totalPessoas = array_sum($dados);
    ?>
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <span class="stat-label">Total de Pessoas Mapeadas</span>
            <span class="stat-value"><?= $totalPessoas ?></span>
            <span class="stat-desc">Integrantes em todos os núcleos</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Primeira Infância e Crianças</span>
            <span class="stat-value"><?= ($dados['0 a 6 anos (Primeira Infância)'] ?? 0) + ($dados['7 a 14 anos (Crianças)'] ?? 0) ?></span>
            <span class="stat-desc">Público prioritário (0 a 14 anos)</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Idosos Cadastrados</span>
            <span class="stat-value"><?= $dados['60 anos ou mais (Idosos)'] ?? 0 ?></span>
            <span class="stat-desc">Beneficiários com 60+ anos</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Distribuição por Faixa Etária</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: var(--spacing-lg);">
                <?php foreach ($dados as $faixa => $qtd): 
                    $pct = $totalPessoas > 0 ? round(($qtd / $totalPessoas) * 100, 1) : 0;
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                            <span style="font-weight: 500; font-size: 0.9rem;"><?= $e($faixa) ?></span>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                <strong><?= $qtd ?></strong> pessoa(s) (<?= $pct ?>%)
                            </span>
                        </div>
                        <div style="background: var(--bg-tertiary); height: 12px; border-radius: 6px; overflow: hidden;">
                            <div style="background: var(--primary-color); height: 100%; width: <?= $pct ?>%; border-radius: 6px; transition: width 0.4s ease;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php elseif ($tipo === 'entregas'): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Histórico de Entregas Registradas (Total: <?= count($dados) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($dados)): ?>
                <div class="empty-state">
                    <p>Nenhuma entrega encontrada para o filtro selecionado.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Família / Beneficiário</th>
                                <th>Bairro</th>
                                <th>Benefício</th>
                                <th>Situação</th>
                                <th>Qtd Itens</th>
                                <th>Responsável</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $b): ?>
                                <tr>
                                    <td>#<?= $b['id'] ?></td>
                                    <td><?= \App\Helpers\FormatHelper::date($b['data']) ?></td>
                                    <td>
                                        <strong><?= $e($b['familiaNome']) ?></strong><br>
                                        <small class="text-muted"><?= $e($b['familiaCodigo']) ?></small>
                                    </td>
                                    <td><?= $e($b['bairro']) ?></td>
                                    <td><?= $e($b['tipo']) ?></td>
                                    <td><?= \App\Helpers\FormatHelper::situacaoCadastralBadge($b['situacao']) ?></td>
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

<?php elseif ($tipo === 'estoque'): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Posição Física do Estoque e Alertas de Validade (Total: <?= count($dados) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($dados)): ?>
                <div class="empty-state">
                    <p>Nenhum produto cadastrado no catálogo.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Unidade</th>
                                <th>Saldo Atual</th>
                                <th>Estoque Mínimo</th>
                                <th>Status de Reposição</th>
                                <th>Próximo Vencimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $p): ?>
                                <tr>
                                    <td><strong><?= $e($p['codigo']) ?></strong></td>
                                    <td><?= $e($p['nome']) ?></td>
                                    <td><span class="badge badge-secondary"><?= $e($p['categoria']) ?></span></td>
                                    <td><?= $e($p['unidade']) ?></td>
                                    <td><strong><?= $p['estoqueAtual'] ?></strong></td>
                                    <td><?= $p['estoqueMinimo'] ?></td>
                                    <td>
                                        <?php if ($p['statusEstoque'] === 'CRÍTICO'): ?>
                                            <span class="badge badge-danger">⚠️ Reposição Urgente</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">✓ Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= \App\Helpers\FormatHelper::date($p['vencimento']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
