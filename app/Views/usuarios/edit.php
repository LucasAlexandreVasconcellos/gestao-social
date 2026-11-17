<?php
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Editar Usuário #<?= (int)$usuario->id ?></h1>
    <p class="page-subtitle">Atualização cadastral, revisão de perfil RBAC e redefinição administrativa de senha.</p>
  </div>
  <div class="page-actions">
    <a href="/usuarios" class="btn btn-secondary">
      <?= IconHelper::svg('arrow-left', 15) ?>
      <span>Voltar à Lista</span>
    </a>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <?= IconHelper::svg('user', 17) ?>
        <span>Dados Funcionais e Perfil</span>
      </h2>
    </div>
    <div class="card-body">
      <form method="POST" action="/usuarios/<?= (int)$usuario->id ?>">
        <?= CsrfHelper::input() ?>

        <div class="form-group">
          <label class="form-label" for="nome">Nome Completo <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>" value="<?= $e($old['nome'] ?? $usuario->nome) ?>" required>
          <?php if (!empty($errors['nome'])): ?><div class="form-error"><?= $e($errors['nome']) ?></div><?php endif; ?>
        </div>

        <div class="form-row">
          <div class="col-6 form-group">
            <label class="form-label" for="email">E-mail Institucional <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" value="<?= $e($old['email'] ?? $usuario->email) ?>" required>
            <?php if (!empty($errors['email'])): ?><div class="form-error"><?= $e($errors['email']) ?></div><?php endif; ?>
          </div>

          <div class="col-6 form-group">
            <label class="form-label" for="login">Login</label>
            <input type="text" id="login" name="login" class="form-control mono <?= !empty($errors['login']) ? 'is-invalid' : '' ?>" value="<?= $e($old['login'] ?? $usuario->login) ?>">
            <?php if (!empty($errors['login'])): ?><div class="form-error"><?= $e($errors['login']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="perfil">Perfil Institucional <span class="required">*</span></label>
          <select id="perfil" name="perfil" class="form-control <?= !empty($errors['perfil']) ? 'is-invalid' : '' ?>" required>
            <?php $selPerfil = $old['perfil'] ?? $usuario->perfil; ?>
            <?php foreach ($perfisDisponiveis as $p): ?>
              <option value="<?= $e($p) ?>" <?= $selPerfil === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="d-flex align-center gap-2" style="cursor: pointer;">
            <?php $isAtivo = isset($old['nome']) ? !empty($old['ativo']) : $usuario->ativo; ?>
            <input type="checkbox" name="ativo" value="1" <?= $isAtivo ? 'checked' : '' ?> <?= ($currentUser && $currentUser->id === $usuario->id) ? 'onclick="return false;"' : '' ?>>
            <span>Conta ativa para autenticação no sistema</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary mt-2">
          <?= IconHelper::svg('check-circle', 16) ?>
          <span>Salvar Alterações</span>
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <?= IconHelper::svg('key', 17) ?>
        <span>Redefinição Administrativa de Senha</span>
      </h2>
    </div>
    <div class="card-body">
      <div class="alert alert-warning">
        <?= IconHelper::svg('alert-triangle', 18) ?>
        <div>Ao definir uma nova senha temporária, eventuais bloqueios por tentativas inválidas serão zerados e o servidor será obrigado a definir uma nova senha pessoal no próximo acesso.</div>
      </div>

      <form method="POST" action="/usuarios/<?= (int)$usuario->id ?>/resetar-senha">
        <?= CsrfHelper::input() ?>

        <div class="form-group">
          <label class="form-label" for="novaSenha">Nova Senha Temporária <span class="required">*</span></label>
          <input type="password" id="novaSenha" name="novaSenha" class="form-control" required minlength="8" autocomplete="new-password">
          <div class="form-help">Mínimo 8 dígitos (maiúscula, minúscula, número e caractere especial).</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirmarSenha">Confirmar Senha Temporária <span class="required">*</span></label>
          <input type="password" id="confirmarSenha" name="confirmarSenha" class="form-control" required minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-warning">
          <?= IconHelper::svg('key', 15) ?>
          <span>Aplicar Senha Temporária</span>
        </button>
      </form>
    </div>
  </div>
</div>

