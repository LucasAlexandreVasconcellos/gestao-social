<?php
use App\Core\View;
use App\Helpers\CsrfHelper;
use App\Helpers\FormatHelper;
use App\Helpers\IconHelper;
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Usuários e Perfis de Acesso (RBAC)</h1>
    <p class="page-subtitle">Gestão de contas institucionais, atribuição de perfis, bloqueios e controle de acesso em nível de servidor.</p>
  </div>
  <?php if ($can('usuarios.manage')): ?>
    <div class="page-actions">
      <a href="/usuarios/novo" class="btn btn-primary">
        <?= IconHelper::svg('plus', 15) ?>
        <span>Novo Usuário</span>
      </a>
    </div>
  <?php endif; ?>
</div>

<div class="alert alert-info">
  <?= IconHelper::svg('shield-check', 18) ?>
  <div>
    <strong>Controle Estrito de Permissões:</strong> Todas as rotas (leitura e escrita) validam a matriz RBAC no servidor via <span class="mono">Usuario::can()</span> e <span class="mono">RoleMiddleware</span>. Contas criadas pela administração exigem redefinição pessoal no primeiro acesso.
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('users', 17) ?>
      <span>Quadro Funcional Cadastrado</span>
    </h2>
    <span class="badge badge-secondary tabular-nums"><?= count($usuarios) ?> na página</span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome / Login</th>
          <th>E-mail Institucional</th>
          <th>Perfil</th>
          <th>Segurança</th>
          <th>Status</th>
          <th>Último Acesso</th>
          <?php if ($can('usuarios.manage')): ?>
            <th class="text-right">Ações</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td class="mono">#<?= (int)$u->id ?></td>
            <td>
              <strong><?= $e($u->nome) ?></strong>
              <?php if ($currentUser && $currentUser->id === $u->id): ?>
                <span class="badge badge-info" style="margin-left: 6px;">Sua Conta</span>
              <?php endif; ?>
              <?php if (!empty($u->login)): ?>
                <div class="text-muted mono" style="font-size: 0.76rem;"><?= $e($u->login) ?></div>
              <?php endif; ?>
            </td>
            <td><?= $e($u->email) ?></td>
            <td><span class="badge badge-info"><?= $e($u->perfil) ?></span></td>
            <td>
              <?php if ($u->doisFatoresAtivo): ?>
                <span class="badge badge-success">2FA Ativo</span>
              <?php else: ?>
                <span class="badge badge-secondary">Senha</span>
              <?php endif; ?>
              <?php if ($u->trocarSenhaObrigatorio): ?>
                <span class="badge badge-warning">Troca Pendente</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u->isLocked()): ?>
                <span class="badge badge-danger">Bloqueado</span>
              <?php elseif ($u->ativo): ?>
                <span class="badge badge-success">Ativo</span>
              <?php else: ?>
                <span class="badge badge-secondary">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="tabular-nums"><?= $u->ultimoAcesso ? $e(FormatHelper::datetime($u->ultimoAcesso)) : 'Nunca acessou' ?></td>
            <?php if ($can('usuarios.manage')): ?>
              <td class="text-right">
                <div class="d-flex justify-between gap-2" style="justify-content: flex-end;">
                  <a href="/usuarios/<?= (int)$u->id ?>/editar" class="btn btn-secondary btn-xs">
                    <?= IconHelper::svg('edit', 13) ?>
                    <span>Editar</span>
                  </a>
                  <?php if (!$currentUser || $currentUser->id !== $u->id): ?>
                    <form method="POST" action="/usuarios/<?= (int)$u->id ?>/alternar-status" style="display:inline;" data-confirm="Confirmar alteração de status ativo/inativo para <?= $e($u->nome) ?>?">
                      <?= CsrfHelper::input() ?>
                      <button type="submit" class="btn <?= $u->ativo ? 'btn-warning' : 'btn-success' ?> btn-xs">
                        <?= $u->ativo ? 'Inativar' : 'Ativar' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= View::renderPartial('partials/pagination', ['pagination' => $pagination ?? null, 'basePath' => '/usuarios', 'query' => []]) ?>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('lock', 17) ?>
      <span>Matriz de Permissões por Perfil (RBAC)</span>
    </h2>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Módulo / Permissão</th>
          <th class="text-center">Administrador</th>
          <th class="text-center">Coordenação</th>
          <th class="text-center">Assistente Social</th>
          <th class="text-center">Estoque</th>
          <th class="text-center">Atendimento</th>
          <th class="text-center">Consulta</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $matriz = [
            ['Painel Gerencial & Relatórios Agregados (Anonimizados)', true, true, true, true, true, true],
            ['Famílias & Pessoas: Visualização e Cadastro', true, true, true, false, true, false],
            ['Avaliações Sociais & Laudos Médicos/PCD (Dados Sensíveis)', true, true, true, false, false, false],
            ['Atendimentos & Encaminhamentos Técnicos', true, true, true, false, true, false],
            ['Entrega de Benefícios (Preparar / Efetivar)', true, true, true, true, false, false],
            ['Estoque, Inventário & Movimentações FEFO', true, true, false, true, false, false],
            ['Doações & Parcerias Institucionais', true, true, false, true, false, false],
            ['Relatórios Nominais Não-Anonimizados (LGPD)', true, true, true, false, false, false],
            ['Gestão de Usuários, Auditoria & Purga LGPD', true, false, false, false, false, false],
        ];
        foreach ($matriz as $row):
        ?>
          <tr>
            <td><strong><?= $e($row[0]) ?></strong></td>
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <td class="text-center">
                <?php if ($row[$i]): ?>
                  <span class="badge badge-success">Sim</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Não</span>
                <?php endif; ?>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
