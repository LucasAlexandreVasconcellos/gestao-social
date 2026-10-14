<div class="page-header">
    <div class="page-title">
        <h1>Perfis de Acesso e Controle de Usuários</h1>
        <p>Gerenciamento de credenciais, controle de acesso baseado em papéis (RBAC) e matriz de permissões</p>
    </div>
</div>

<div class="card mb-4" style="border-left: 4px solid var(--primary-color);">
    <div style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
        <span style="font-size: 1.5rem;">🛡️</span>
        <div>
            <h4 style="margin: 0 0 0.25rem 0; font-size: 0.95rem;">Controle de Acesso em Nível de Servidor e Preparação para o Banco de Dados</h4>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                As permissões são validadas estritamente no back-end (via métodos <code>Usuario::can()</code> e <code>RoleMiddleware</code>), impedindo requisições não autorizadas mesmo que interfaces sejam forçadas.
                <br>
                <strong>Integração Futura:</strong> O responsável pelo banco de dados implementará a persistência real na tabela <code>usuarios</code>, utilizando <code>password_hash()</code> e <code>password_verify()</code> com salt seguro. Os usuários abaixo são demonstrativos e servem para testes completos dos fluxos.
            </p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Usuários e Perfis Cadastrados</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Completo</th>
                        <th>E-mail</th>
                        <th>Perfil Institucional</th>
                        <th>Status</th>
                        <th>Último Acesso</th>
                        <th style="text-align: right;">Simular Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr style="<?= $currentUser && $currentUser->id === $u->id ? 'background: #eff6ff; font-weight: 500;' : '' ?>">
                            <td>#<?= $u->id ?></td>
                            <td>
                                <?= $e($u->nome) ?>
                                <?php if ($currentUser && $currentUser->id === $u->id): ?>
                                    <span class="badge badge-primary" style="margin-left: 0.5rem;">Sessão Atual</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e($u->email) ?></td>
                            <td>
                                <span class="badge badge-info"><?= $e($u->perfil) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-success">Ativo</span>
                            </td>
                            <td><?= $u->ultimoAcesso ? \App\Helpers\FormatHelper::date($u->ultimoAcesso, true) : 'Demonstração' ?></td>
                            <td style="text-align: right;">
                                <?php if (!$currentUser || $currentUser->id !== $u->id): ?>
                                    <form method="POST" action="/usuarios/switch-role" style="display: inline;">
                                        <?= \App\Core\View::renderPartial('partials/csrf') ?>
                                        <input type="hidden" name="userId" value="<?= $u->id ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Alternar sessão para este usuário e testar suas permissões">
                                            Alternar para este perfil
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--primary-color);">✓ Em uso</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Matriz de Permissões RBAC -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Matriz de Permissões por Perfil (RBAC)</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-bordered" style="font-size: 0.85rem;">
                <thead>
                    <tr style="background: var(--bg-tertiary);">
                        <th>Módulo / Funcionalidade</th>
                        <th style="text-align: center;">Administrador</th>
                        <th style="text-align: center;">Coordenação</th>
                        <th style="text-align: center;">Assistente Social</th>
                        <th style="text-align: center;">Estoque</th>
                        <th style="text-align: center;">Atendimento</th>
                        <th style="text-align: center;">Consulta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Famílias:</strong> Visualização e Ficha Consolidada</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                    </tr>
                    <tr>
                        <td><strong>Famílias:</strong> Cadastrar e Editar</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Pessoas e Vínculos:</strong> Gestão de Integrantes</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Avaliações Sociais:</strong> Visualização Geral</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Avaliações Sociais:</strong> Parecer Técnico e Dados Sensíveis</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Atendimentos e Visitas:</strong> Registro e Encaminhamentos</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Benefícios:</strong> Criação e Preparação</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Benefícios:</strong> Efetivação e Baixa de Estoque</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Estoque e Almoxarifado:</strong> Entradas, Saídas e Ajustes</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Doações:</strong> Cadastro de Doadores e Entradas</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Projetos e Oficinas:</strong> Gestão e Frequência</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                    <tr>
                        <td><strong>Relatórios Gerenciais:</strong> Visualização e CSV</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                    </tr>
                    <tr>
                        <td><strong>Trilha de Auditoria:</strong> Consulta aos Logs Operacionais</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--success-color);">✓</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                        <td style="text-align: center; color: var(--text-muted);">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
