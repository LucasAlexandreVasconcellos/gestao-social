<?php
use App\Models\Usuario;

/** @var Usuario $user */
/** @var array|null $setup */
/** @var string|null $success */
/** @var string|null $error */
/** @var string|null $info */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/dashboard" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar ao Painel</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main);">Autenticação em Dois Fatores (2FA / TOTP)</h2>
      <?php if ($user->doisFatoresAtivo): ?>
        <span class="badge badge-success" style="font-size: 13px; padding: 4px 10px;">Ativo e Protegido</span>
      <?php else: ?>
        <span class="badge badge-secondary" style="font-size: 13px; padding: 4px 10px;">Desativado</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert-success" style="margin-bottom: 20px;">
    <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger" style="margin-bottom: 20px;">
    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($info)): ?>
  <div class="alert alert-info" style="margin-bottom: 20px;">
    <span><?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      <span>Status da Proteção de Acesso</span>
    </div>
  </div>
  <div class="card-body">
    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
      A <strong>Autenticação em Dois Fatores (TOTP - RFC 6238)</strong> adiciona uma camada indispensável de segurança à sua conta institucional.
      Ao fazer login, além da sua senha pessoal, será solicitado um código temporário de 6 dígitos gerado em tempo real no seu smartphone.
    </p>

    <?php if ($user->doisFatoresAtivo): ?>
      <div style="background-color: rgba(16, 185, 129, 0.08); border: 1px solid #10b981; border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
          <div style="background-color: #10b981; color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            ✓
          </div>
          <div>
            <h4 style="font-size: 15px; font-weight: 700; color: #065f46; margin-bottom: 4px;">Sua conta está blindada com 2FA</h4>
            <p style="font-size: 13px; color: #047857; margin-bottom: 8px;">
              Cada tentativa de login requer autorização pelo seu aplicativo autenticador. Códigos de recuperação ativos restantes: <strong><?= count($user->doisFatoresBackupCodes) ?></strong>.
            </p>
          </div>
        </div>
      </div>

      <div style="border-top: 1px solid var(--border-color); padding-top: 16px;">
        <form action="/meu-perfil/2fa/desativar" method="POST" data-confirm="Tem certeza de que deseja desativar a autenticação em dois fatores? Sua conta ficará protegida apenas pela senha tradicional.">
          <?= \App\Core\View::render('partials/csrf', [], null) ?>
          <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);">
            Desativar Autenticação em 2 Fatores
          </button>
        </form>
      </div>

    <?php else: ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 16px;">
        
        <!-- Passo 1: QR Code -->
        <div style="background-color: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
          <h4 style="font-size: 14px; font-weight: 700; color: var(--primary); margin-bottom: 12px; text-transform: uppercase;">
            Passo 1: Escanear QR Code
          </h4>
          <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
            Abra seu app autenticador (Google Authenticator, Microsoft Authenticator ou FreeOTP) e aponte a câmera para o QR Code abaixo:
          </p>

          <div style="background: #ffffff; padding: 12px; border-radius: var(--radius-sm); display: inline-block; box-shadow: var(--shadow-sm); margin-bottom: 14px;">
            <?= $setup['svg'] ?? '' ?>
          </div>

          <div style="font-size: 11px; color: var(--text-muted);">
            Não consegue escanear? Digite a chave manualmente:
            <div style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--text-main); margin-top: 4px; letter-spacing: 2px;">
              <?= chunk_split($setup['secret'] ?? '', 4, ' ') ?>
            </div>
          </div>
        </div>

        <!-- Passo 2 & 3: Códigos de Backup e Ativação -->
        <div>
          <div style="background-color: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px;">
            <h4 style="font-size: 14px; font-weight: 700; color: var(--primary); margin-bottom: 8px; text-transform: uppercase;">
              Passo 2: Salvar Códigos de Backup
            </h4>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
              Guarde estes códigos em local seguro. Se você perder seu celular, poderá usá-los para recuperar o acesso à sua conta (cada código funciona 1 vez):
            </p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #fff; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-family: monospace; font-size: 13px; font-weight: 700; text-align: center;">
              <?php foreach ($setup['backup_codes'] ?? [] as $code): ?>
                <div style="padding: 4px; background: var(--bg-subtle); border-radius: 4px;"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="background-color: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <h4 style="font-size: 14px; font-weight: 700; color: var(--primary); margin-bottom: 8px; text-transform: uppercase;">
              Passo 3: Confirmar Código do App
            </h4>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
              Digite o código de 6 dígitos exibido no seu aplicativo para validar e ativar:
            </p>

            <form action="/meu-perfil/2fa/confirmar" method="POST">
              <?= \App\Core\View::render('partials/csrf', [], null) ?>
              <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="code" class="form-control" placeholder="000 000" maxlength="8" required autofocus autocomplete="one-time-code" inputmode="numeric"
                       style="font-size: 18px; font-weight: 800; text-align: center; letter-spacing: 4px; max-width: 180px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                  Confirmar e Ativar 2FA
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>

    <?php endif; ?>
  </div>
</div>
