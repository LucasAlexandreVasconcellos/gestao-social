<?php
use App\Helpers\CsrfHelper;
use App\Helpers\IconHelper;
use App\Models\Usuario;

/** @var Usuario $user */
/** @var array|null $setup */
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Autenticação em Dois Fatores (2FA / TOTP)</h1>
    <p class="page-subtitle">Proteção adicional para acesso funcional conforme diretrizes RFC 6238.</p>
  </div>
  <div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">
      <?= IconHelper::svg('arrow-left', 15) ?>
      <span>Voltar ao Painel</span>
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('shield', 18) ?>
      <span>Status da Proteção de Acesso</span>
    </h2>
    <?php if ($user->doisFatoresAtivo): ?>
      <span class="badge badge-success">Ativo e Protegido</span>
    <?php else: ?>
      <span class="badge badge-secondary">Desativado</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <p class="text-muted mb-4">
      A <strong>Autenticação em Dois Fatores (TOTP - RFC 6238)</strong> exige um código temporário de 6 dígitos gerado pelo aplicativo autenticador do seu dispositivo móvel além da sua senha institucional.
    </p>

    <?php if ($user->doisFatoresAtivo): ?>
      <div class="alert alert-success">
        <?= IconHelper::svg('shield-check', 20) ?>
        <div>
          <strong>Sua conta está protegida com 2FA.</strong>
          <div class="mt-1">Códigos de recuperação de uso único restantes: <strong class="tabular-nums"><?= count($user->doisFatoresBackupCodes) ?></strong>.</div>
        </div>
      </div>

      <form action="/meu-perfil/2fa/desativar" method="POST" class="mt-4" style="max-width: 460px; padding-top: 16px; border-top: 1px solid var(--border);" data-confirm="Tem certeza de que deseja desativar a autenticação em dois fatores?">
        <?= CsrfHelper::input() ?>
        <div class="form-group">
          <label class="form-label" for="senhaAtual">Confirme sua senha atual para desativar o 2FA <span class="required">*</span></label>
          <input type="password" id="senhaAtual" name="senhaAtual" class="form-control" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-danger">
          <?= IconHelper::svg('x-circle', 15) ?>
          <span>Desativar Autenticação em 2 Fatores</span>
        </button>
      </form>
    <?php else: ?>
      <div class="grid-2">
        <div style="background: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border); text-align: center;">
          <h3 class="detail-label mb-2">Passo 1: Escanear QR Code</h3>
          <p class="text-muted mb-3" style="font-size: 0.82rem;">Aponte seu aplicativo autenticador (Google Authenticator, Microsoft Authenticator ou FreeOTP):</p>
          <div style="background: #ffffff; padding: 12px; border-radius: var(--radius-sm); display: inline-block; border: 1px solid var(--border); margin-bottom: 14px;">
            <?= $setup['svg'] ?? '' ?>
          </div>
          <div class="text-muted" style="font-size: 0.78rem;">
            Chave manual Base32:
            <div class="mono fw-bold mt-1" style="color: var(--text-main); letter-spacing: 0.12em;">
              <?= $e(chunk_split($setup['secret'] ?? '', 4, ' ')) ?>
            </div>
          </div>
        </div>

        <div>
          <div style="background: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 18px;">
            <h3 class="detail-label mb-2">Passo 2: Salvar Códigos de Backup</h3>
            <p class="text-muted mb-3" style="font-size: 0.82rem;">Guarde estes códigos em local seguro para uso emergencial (1 uso por código):</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #fff; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border); text-align: center;" class="mono fw-bold">
              <?php foreach ($setup['backup_codes'] ?? [] as $code): ?>
                <div style="padding: 5px; background: var(--bg-subtle); border-radius: 4px;"><?= $e($code) ?></div>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="background: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <h3 class="detail-label mb-2">Passo 3: Confirmar Código TOTP</h3>
            <form action="/meu-perfil/2fa/confirmar" method="POST">
              <?= CsrfHelper::input() ?>
              <div class="d-flex gap-2 align-center">
                <input type="text" name="code" class="form-control mono text-center" placeholder="000000" maxlength="6" required autofocus autocomplete="one-time-code" inputmode="numeric" style="font-size: 1.2rem; font-weight: 700; letter-spacing: 0.25em; max-width: 170px;">
                <button type="submit" class="btn btn-primary">
                  <?= IconHelper::svg('check-circle', 16) ?>
                  <span>Ativar 2FA</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
