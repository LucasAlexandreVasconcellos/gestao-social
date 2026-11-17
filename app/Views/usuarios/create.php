<?php
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Novo Usuário Institucional</h1>
    <p class="page-subtitle">Provisionamento de conta com senha forte e definição de perfil RBAC.</p>
  </div>
  <div class="page-actions">
    <a href="/usuarios" class="btn btn-secondary">
      <?= IconHelper::svg('arrow-left', 15) ?>
      <span>Voltar à Lista</span>
    </a>
  </div>
</div>

<div class="card" style="max-width: 780px;">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('user', 17) ?>
      <span>Dados da Credencial Funcional</span>
    </h2>
  </div>
  <div class="card-body">
    <form method="POST" action="/usuarios">
      <?= CsrfHelper::input() ?>

      <div class="form-row">
        <div class="col-6 form-group">
          <label class="form-label" for="nome">Nome Completo <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>" value="<?= $e($old['nome'] ?? '') ?>" required>
          <?php if (!empty($errors['nome'])): ?><div class="form-error"><?= $e($errors['nome']) ?></div><?php endif; ?>
        </div>

        <div class="col-6 form-group">
          <label class="form-label" for="email">E-mail Institucional <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" value="<?= $e($old['email'] ?? '') ?>" required>
          <?php if (!empty($errors['email'])): ?><div class="form-error"><?= $e($errors['email']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="col-6 form-group">
          <label class="form-label" for="login">Login (Opcional — gerado do e-mail se vazio)</label>
          <input type="text" id="login" name="login" class="form-control mono <?= !empty($errors['login']) ? 'is-invalid' : '' ?>" value="<?= $e($old['login'] ?? '') ?>" placeholder="ex: maria.silva">
          <?php if (!empty($errors['login'])): ?><div class="form-error"><?= $e($errors['login']) ?></div><?php endif; ?>
        </div>

        <div class="col-6 form-group">
          <label class="form-label" for="perfil">Perfil de Acesso (RBAC) <span class="required">*</span></label>
          <select id="perfil" name="perfil" class="form-control <?= !empty($errors['perfil']) ? 'is-invalid' : '' ?>" required>
            <?php foreach ($perfisDisponiveis as $p): ?>
              <option value="<?= $e($p) ?>" <?= ($old['perfil'] ?? 'Atendimento') === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['perfil'])): ?><div class="form-error"><?= $e($errors['perfil']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="col-6 form-group">
          <label class="form-label" for="senha">Senha Inicial Temporária <span class="required">*</span></label>
          <input type="password" id="senha" name="senha" class="form-control <?= !empty($errors['senha']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
          <div class="form-help">Mínimo 8 dígitos (maiúscula, minúscula, número e caractere especial).</div>
          <?php if (!empty($errors['senha'])): ?><div class="form-error"><?= $e($errors['senha']) ?></div><?php endif; ?>
        </div>

        <div class="col-6 form-group">
          <label class="form-label" for="confirmarSenha">Confirmar Senha Inicial <span class="required">*</span></label>
          <input type="password" id="confirmarSenha" name="confirmarSenha" class="form-control <?= !empty($errors['confirmarSenha']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
          <?php if (!empty($errors['confirmarSenha'])): ?><div class="form-error"><?= $e($errors['confirmarSenha']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="form-row mt-2">
        <div class="col-6 form-group">
          <label class="d-flex align-center gap-2" style="cursor: pointer;">
            <input type="checkbox" name="ativo" value="1" <?= !isset($old['ativo']) || !empty($old['ativo']) ? 'checked' : '' ?>>
            <span>Conta ativa imediatamente</span>
          </label>
        </div>
        <div class="col-6 form-group">
          <label class="d-flex align-center gap-2" style="cursor: pointer;">
            <input type="checkbox" name="trocarSenhaObrigatorio" value="1" <?= !isset($old['trocarSenhaObrigatorio']) || !empty($old['trocarSenhaObrigatorio']) ? 'checked' : '' ?>>
            <span>Exigir troca obrigatória de senha no 1º acesso</span>
          </label>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <?= IconHelper::svg('check-circle', 16) ?>
          <span>Cadastrar Usuário</span>
        </button>
        <a href="/usuarios" class="btn btn-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>

