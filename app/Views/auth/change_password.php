<?php
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Alteração de Senha Pessoal</h1>
    <p class="page-subtitle">Política institucional de credenciais fortes e renovação de acesso.</p>
  </div>
  <?php if (empty($obrigatoria)): ?>
    <div class="page-actions">
      <a href="/dashboard" class="btn btn-secondary">
        <?= IconHelper::svg('arrow-left', 15) ?>
        <span>Voltar ao Painel</span>
      </a>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($obrigatoria)): ?>
  <div class="alert alert-warning">
    <?= IconHelper::svg('alert-triangle', 18) ?>
    <div>
      <strong>Troca de Senha Obrigatória:</strong> Para liberar o acesso completo às rotas operacionais, defina uma nova senha pessoal exclusiva que atenda aos critérios de segurança abaixo.
    </div>
  </div>
<?php endif; ?>

<div class="card" style="max-width: 640px;">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('key', 18) ?>
      <?php $nomeUsuario = is_object($user) ? ($user->nome ?? '') : ($user['nome'] ?? ''); ?>
      <span>Definir Nova Senha — <?= $e($nomeUsuario) ?></span>
    </h2>
  </div>
  <div class="card-body">
    <form method="POST" action="/meu-perfil/senha">
      <?= CsrfHelper::input() ?>

      <div class="form-group">
        <label class="form-label" for="senhaAtual">Senha Atual / Temporária <span class="required">*</span></label>
        <input type="password" id="senhaAtual" name="senhaAtual" class="form-control <?= !empty($errors['senhaAtual']) ? 'is-invalid' : '' ?>" required autocomplete="current-password">
        <?php if (!empty($errors['senhaAtual'])): ?>
          <div class="form-error"><?= $e($errors['senhaAtual']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="novaSenha">Nova Senha Pessoal <span class="required">*</span></label>
        <input type="password" id="novaSenha" name="novaSenha" class="form-control <?= !empty($errors['novaSenha']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
        <?php if (!empty($errors['novaSenha'])): ?>
          <div class="form-error"><?= $e($errors['novaSenha']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirmarSenha">Confirmar Nova Senha <span class="required">*</span></label>
        <input type="password" id="confirmarSenha" name="confirmarSenha" class="form-control <?= !empty($errors['confirmarSenha']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
        <?php if (!empty($errors['confirmarSenha'])): ?>
          <div class="form-error"><?= $e($errors['confirmarSenha']) ?></div>
        <?php endif; ?>

        <!-- Checklist em destaque logo abaixo dos campos de senha -->
        <div class="password-rules-panel" id="password-rules-panel" aria-live="polite">
          <div class="password-rules-title">Recomendações e requisitos obrigatórios da senha</div>
          <ul class="password-rules-list">
            <li class="password-rule-item" data-rule="length">
              <span class="password-rule-dot"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span>Mínimo de 8 dígitos</span>
            </li>
            <li class="password-rule-item" data-rule="upper">
              <span class="password-rule-dot"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span>Letra maiúscula (A–Z)</span>
            </li>
            <li class="password-rule-item" data-rule="lower">
              <span class="password-rule-dot"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span>Letra minúscula (a–z)</span>
            </li>
            <li class="password-rule-item" data-rule="digit">
              <span class="password-rule-dot"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span>Número (0–9)</span>
            </li>
            <li class="password-rule-item" data-rule="special">
              <span class="password-rule-dot"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span>Caractere especial (!@#$%&amp;*)</span>
            </li>
            <li class="password-rule-item" data-rule="match">
              <span class="password-rule-dot"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span>Confirmação de senha idêntica</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= IconHelper::svg('check-circle', 16) ?>
          <span>Atualizar Senha</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var nova = document.getElementById('novaSenha');
  var confirma = document.getElementById('confirmarSenha');
  var panel = document.getElementById('password-rules-panel');
  if (!nova || !confirma || !panel) return;

  var rules = {
    length: function (v) { return v.length >= 8; },
    upper: function (v) { return /[A-Z]/.test(v); },
    lower: function (v) { return /[a-z]/.test(v); },
    digit: function (v) { return /\d/.test(v); },
    special: function (v) { return /[^A-Za-z0-9]/.test(v); },
    match: function (v, c) { return v.length > 0 && v === c; }
  };

  function updateChecklist() {
    var v = nova.value || '';
    var c = confirma.value || '';
    panel.querySelectorAll('[data-rule]').forEach(function (el) {
      var key = el.getAttribute('data-rule');
      var ok = rules[key] ? rules[key](v, c) : false;
      el.classList.toggle('is-met', ok);
    });
  }

  nova.addEventListener('input', updateChecklist);
  confirma.addEventListener('input', updateChecklist);
  updateChecklist();
});
</script>
