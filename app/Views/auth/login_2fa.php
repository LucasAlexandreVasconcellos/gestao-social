<?php
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;
?>
<div class="auth-shell compact">
  <div class="auth-form-panel">
    <div class="text-center mb-4">
      <div class="stat-icon-pill" style="width: 48px; height: 48px; margin: 0 auto 12px;">
        <?= IconHelper::svg('shield-check', 24) ?>
      </div>
      <h2 style="font-size: 1.3rem; font-weight: 700; color: var(--text-main);">Verificação em Dois Fatores</h2>
      <p class="text-muted" style="font-size: 0.85rem; margin-top: 4px;">
        <?= $e($pendingUser ? $pendingUser->nome : 'Conta autenticada') ?>
      </p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= IconHelper::svg('x-circle', 18) ?>
        <div><?= $e($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login/2fa">
      <?= CsrfHelper::input() ?>
      <input type="hidden" name="is_backup" value="<?= $isBackup ? '1' : '0' ?>">

      <?php if ($isBackup): ?>
        <div class="alert alert-info mb-3">
          <?= IconHelper::svg('key', 18) ?>
          <div>Digite um dos <strong>códigos de recuperação</strong> de uso único fornecidos na configuração do seu 2FA.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="code">Código de Backup <span class="required">*</span></label>
          <input type="text" id="code" name="code" class="form-control mono text-center"
                 placeholder="ABCD-1234" required autofocus autocomplete="off"
                 style="font-size: 1.15rem; letter-spacing: 0.14em; text-transform: uppercase;">
        </div>
      <?php else: ?>
        <p class="text-muted mb-3" style="font-size: 0.86rem; text-align: center;">
          Informe o código de 6 dígitos gerado pelo seu aplicativo autenticador (Google Authenticator, Authy ou compatível).
        </p>
        <div class="form-group">
          <label class="form-label" for="code">Código TOTP (6 dígitos) <span class="required">*</span></label>
          <input type="text" id="code" name="code" class="form-control mono text-center"
                 placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                 required autofocus autocomplete="one-time-code"
                 style="font-size: 1.45rem; letter-spacing: 0.32em; font-weight: 700;">
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-block" style="padding: 10px;">
        <?= IconHelper::svg('check-circle', 16) ?>
        <span>Confirmar Autenticação</span>
      </button>
    </form>

    <div class="mt-4" style="padding-top: 14px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px; align-items: center;">
      <?php if ($isBackup): ?>
        <a href="/login/2fa" style="font-size: 0.84rem; font-weight: 600;">Voltar ao código TOTP de 6 dígitos</a>
      <?php else: ?>
        <a href="/login/2fa?backup=1" style="font-size: 0.84rem; font-weight: 600;">Sem acesso ao aplicativo? Usar código de backup</a>
      <?php endif; ?>

      <form method="POST" action="/login/2fa/cancelar">
        <?= CsrfHelper::input() ?>
        <button type="submit" style="background: none; border: none; color: var(--text-muted); font-size: 0.82rem; cursor: pointer; text-decoration: underline;">
          Cancelar e voltar ao login
        </button>
      </form>
    </div>
  </div>
</div>
