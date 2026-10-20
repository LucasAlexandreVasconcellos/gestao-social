<?php
use App\Models\Usuario;

/** @var Usuario|null $pendingUser */
/** @var bool $isBackup */
/** @var string|null $error */
?>
<div class="card" style="box-shadow: var(--shadow-lg);">
  <div style="background-color: var(--primary); color: #ffffff; padding: 24px; text-align: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom: 8px;">
      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
    </svg>
    <h2 style="font-size: 20px; font-weight: 700;">Verificação em Duas Etapas</h2>
    <p style="font-size: 13px; opacity: 0.9; margin-top: 4px;">Proteção de Acesso em Nível Institucional (RFC 6238)</p>
  </div>

  <div class="card-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 20px;">
      <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">
        Olá, <strong><?= htmlspecialchars($pendingUser?->nome ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></strong>.<br>
        <?php if ($isBackup): ?>
          Digite um dos seus <strong>Códigos de Recuperação (Backup)</strong> de uso único para acessar.
        <?php else: ?>
          Abra seu aplicativo autenticador (Google Authenticator, Microsoft Authenticator, FreeOTP) e insira o código de 6 dígitos.
        <?php endif; ?>
      </p>
    </div>

    <form action="/login/2fa" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>
      <input type="hidden" name="is_backup" value="<?= $isBackup ? '1' : '0' ?>">

      <div class="form-group" style="margin-bottom: 24px; text-align: center;">
        <?php if ($isBackup): ?>
          <label class="form-label" for="code" style="text-align: center;">Código de Backup (Ex: ABCD-1234)</label>
          <input type="text" id="code" name="code" class="form-control" placeholder="XXXX-XXXX" required autofocus
                 style="font-size: 22px; font-weight: 700; text-align: center; letter-spacing: 4px; text-transform: uppercase;">
        <?php else: ?>
          <label class="form-label" for="code" style="text-align: center;">Código Numérico do Aplicativo</label>
          <input type="text" id="code" name="code" class="form-control" placeholder="000 000" maxlength="8" required autofocus autocomplete="one-time-code" inputmode="numeric"
                 style="font-size: 26px; font-weight: 800; text-align: center; letter-spacing: 8px;">
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-weight: 600;">
        Verificar e Acessar Painel
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); text-align: center; display: flex; flex-direction: column; gap: 10px;">
      <?php if ($isBackup): ?>
        <a href="/login/2fa" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600;">
          ← Voltar para código do Aplicativo Autenticador (TOTP)
        </a>
      <?php else: ?>
        <a href="/login/2fa?backup=1" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">
          Não está com o smartphone? <strong>Usar código de backup</strong>
        </a>
      <?php endif; ?>

      <form action="/login/2fa/cancelar" method="POST" style="margin-top: 4px;">
        <?= \App\Core\View::render('partials/csrf', [], null) ?>
        <button type="submit" class="btn btn-outline btn-sm" style="font-size: 12px; color: var(--text-muted);">
          Cancelar e Voltar ao Início
        </button>
      </form>
    </div>
  </div>
</div>
