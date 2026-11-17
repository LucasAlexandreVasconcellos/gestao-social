<?php
use App\Core\Session;
use App\Helpers\IconHelper;

$isLoggedIn = !empty(Session::get('user')['id']);
$backUrl = $isLoggedIn ? '/dashboard' : '/login';
$backLabel = $isLoggedIn ? 'Voltar ao Painel' : 'Ir para o Login';
?>
<div class="auth-shell compact" style="max-width: 660px;">
  <div class="auth-form-panel">
    <div class="d-flex justify-between align-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-center gap-2">
        <div class="stat-icon-pill">
          <?= IconHelper::svg('qr-code', 20) ?>
        </div>
        <div>
          <h1 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Validação de Autenticidade Documental</h1>
          <div class="text-muted" style="font-size: 0.8rem;">Conferência pública de termos, prontuários e recibos emitidos pelo SUAS</div>
        </div>
      </div>
      <a href="<?= $e($backUrl) ?>" class="btn btn-secondary btn-sm">
        <?= IconHelper::svg('arrow-left', 14) ?>
        <span><?= $e($backLabel) ?></span>
      </a>
    </div>

    <form method="GET" action="/validar" class="mb-4" style="padding: 16px; background: var(--bg-subtle); border: 1px solid var(--border); border-radius: var(--radius-md);">
      <div class="form-row">
        <div class="col-4 form-group">
          <label class="form-label" for="tipo">Documento <span class="required">*</span></label>
          <select id="tipo" name="tipo" class="form-control" required>
            <option value="">Selecione...</option>
            <option value="beneficio" <?= ($tipo ?? '') === 'beneficio' ? 'selected' : '' ?>>Termo de Benefício</option>
            <option value="prontuario" <?= ($tipo ?? '') === 'prontuario' ? 'selected' : '' ?>>Prontuário Familiar</option>
            <option value="doacao" <?= ($tipo ?? '') === 'doacao' ? 'selected' : '' ?>>Recibo de Doação</option>
          </select>
        </div>
        <div class="col-4 form-group">
          <label class="form-label" for="codigo">Protocolo <span class="required">*</span></label>
          <input type="text" id="codigo" name="codigo" class="form-control mono" placeholder="BEN-2026-0001" value="<?= $e($codigo ?? '') ?>" required>
        </div>
        <div class="col-4 form-group">
          <label class="form-label" for="h">Código HMAC (16 hex) <span class="required">*</span></label>
          <input type="text" id="h" name="h" class="form-control mono" placeholder="16 caracteres hex" value="<?= $e($hash ?? '') ?>" maxlength="16" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">
        <?= IconHelper::svg('search', 15) ?>
        <span>Verificar Assinatura</span>
      </button>
    </form>

    <?php if ($resultado !== null): ?>
      <?php if (!empty($resultado['valido'])): ?>
        <div class="alert alert-success">
          <?= IconHelper::svg('shield-check', 22) ?>
          <div>
            <strong style="font-size: 0.96rem;">Documento Autêntico e Íntegro</strong>
            <div class="mt-2" style="font-size: 0.85rem; line-height: 1.6;">
              <div><strong>Órgão Emissor:</strong> <?= $e($resultado['orgaoEmissor']) ?></div>
              <div><strong>Tipo Documental:</strong> <?= $e($resultado['tipoDocumento']) ?></div>
              <div><strong>Protocolo:</strong> <span class="mono"><?= $e($resultado['protocolo']) ?></span></div>
              <div><strong>Data de Emissão:</strong> <?= $e($resultado['dataEmissao']) ?></div>
              <div><strong>Resumo Público (LGPD):</strong> <?= $e($resultado['resumoPublico']) ?></div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-danger">
          <?= IconHelper::svg('x-circle', 22) ?>
          <div>
            <strong>Falha na Verificação de Autenticidade</strong>
            <div class="mt-1"><?= $e($resultado['motivo'] ?? 'Documento não validado.') ?></div>
          </div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="alert alert-info mb-0">
        <?= IconHelper::svg('info', 18) ?>
        <div>Leia o QR Code impresso no documento oficial ou preencha os campos acima. Por conformidade com a LGPD, este portal público não exibe dados pessoais nominais nem CPFs.</div>
      </div>
    <?php endif; ?>

    <div class="mt-4 d-flex justify-between align-center flex-wrap gap-2" style="padding-top: 14px; border-top: 1px solid var(--border);">
      <a href="<?= $e($backUrl) ?>" class="btn btn-secondary btn-sm">
        <?= IconHelper::svg('arrow-left', 14) ?>
        <span><?= $isLoggedIn ? 'Sair do Validador e Voltar ao Painel' : 'Sair e Voltar para o Login' ?></span>
      </a>
      <span class="text-muted" style="font-size: 0.76rem;">Verificação criptográfica HMAC-SHA256</span>
    </div>
  </div>
</div>
