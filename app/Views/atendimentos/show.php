<?php
use App\Helpers\FormatHelper;
use App\Models\Atendimento;
use App\Models\Familia;

/** @var Atendimento $atendimento */
/** @var Familia|null $familia */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/atendimentos" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Atendimentos</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Atendimento #<?= $atendimento->id ?> — <?= htmlspecialchars($atendimento->tipo, ENT_QUOTES, 'UTF-8') ?></h2>
      <?= FormatHelper::badgeStatus($atendimento->situacao) ?>
    </div>
  </div>

  <div style="display: flex; gap: 8px;">
    <?php if ($familia): ?>
      <a href="/familias/<?= $familia->id ?>" class="btn btn-secondary btn-sm">Ver Ficha da Família</a>
    <?php endif; ?>
    <button type="button" onclick="document.getElementById('modal-notificacao').style.display='flex'" class="btn btn-primary btn-sm no-print">📲 Notificar Cidadão</button>
    <button onclick="window.print()" class="btn btn-outline btn-sm no-print">Imprimir Registro</button>
  </div>
</div>

<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">Detalhes do Atendimento</div>
    <span style="font-size: 13px; color: var(--text-muted);">Realizado em <?= FormatHelper::date($atendimento->dataAtendimento) ?></span>
  </div>
  <div class="card-body">
    <div class="info-list" style="margin-bottom: 20px;">
      <div class="info-item">
        <label>Família Atendida</label>
        <span><?= htmlspecialchars($familia ? "{$familia->codigo} — {$familia->pessoaReferenciaNome}" : "Família #{$atendimento->familiaId}", ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Profissional Responsável</label>
        <span><?= htmlspecialchars($atendimento->profissionalResponsavel, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Retorno Previsto</label>
        <span><?= FormatHelper::date($atendimento->retornoPrevisto) ?></span>
      </div>
      <div class="info-item">
        <label>Data de Conclusão</label>
        <span><?= FormatHelper::date($atendimento->dataConclusao) ?></span>
      </div>
    </div>

    <div style="margin-bottom: 20px;">
      <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Descrição da Demanda e Escuta</label>
      <div style="background-color: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md); font-size: 14px; margin-top: 4px; line-height: 1.6;">
        <?= nl2br(htmlspecialchars($atendimento->descricao, ENT_QUOTES, 'UTF-8')) ?>
      </div>
    </div>

    <?php if (!empty($atendimento->providencias)): ?>
      <div style="margin-bottom: 10px;">
        <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Providências Adotadas</label>
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px; border-radius: var(--radius-md); font-size: 14px; margin-top: 4px; line-height: 1.6; color: #166534;">
          <?= nl2br(htmlspecialchars($atendimento->providencias, ENT_QUOTES, 'UTF-8')) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Módulo de Encaminhamentos Associados -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
      <span>Encaminhamentos para Rede Pública e Parceiros</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Serviço de Destino</th>
          <th>Motivo da Articulação</th>
          <th>Data</th>
          <th>Responsável</th>
          <th>Situação</th>
          <th>Resultado / Retorno da Rede</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($atendimento->encaminhamentos)): ?>
          <tr><td colspan="6" class="empty-state">Nenhum encaminhamento registrado para este atendimento.</td></tr>
        <?php else: ?>
          <?php foreach ($atendimento->encaminhamentos as $enc): ?>
            <tr>
              <td><strong><?= htmlspecialchars($enc->servicoDestino, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($enc->motivo, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::date($enc->data) ?></td>
              <td style="font-size: 13px;"><?= htmlspecialchars($enc->responsavel, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::badgeStatus($enc->situacao) ?></td>
              <td style="font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($enc->resultado ?? 'Aguardando retorno da rede', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Formulário Inline para Novo Encaminhamento -->
  <div class="card-body" style="background-color: var(--bg-subtle); border-top: 1px solid var(--border-color);">
    <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 12px;">+ Adicionar Encaminhamento a Este Atendimento</h4>
    <form action="/atendimentos/<?= $atendimento->id ?>/encaminhamentos" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid">
        <div class="form-group col-3">
          <label class="form-label" for="servicoDestino">Serviço de Destino <span class="required">*</span></label>
          <input type="text" id="servicoDestino" name="servicoDestino" class="form-control" required placeholder="Ex: CRAS, CREAS, UBS, SINE">
        </div>

        <div class="form-group col-5">
          <label class="form-label" for="motivo">Motivo do Encaminhamento <span class="required">*</span></label>
          <input type="text" id="motivo" name="motivo" class="form-control" required placeholder="Ex: CadÚnico, Benefício Eventual, Consulta Especializada">
        </div>

        <div class="form-group col-2">
          <label class="form-label" for="encData">Data <span class="required">*</span></label>
          <input type="date" id="encData" name="data" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group col-2">
          <label class="form-label" for="encResp">Responsável</label>
          <input type="text" id="encResp" name="responsavel" class="form-control" value="<?= htmlspecialchars($atendimento->profissionalResponsavel, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-10">
          <label class="form-label" for="encResultado">Resultado / Retorno (Se já disponível)</label>
          <input type="text" id="encResultado" name="resultado" class="form-control" placeholder="Parecer da instituição de destino ou providência...">
        </div>

        <div class="form-group col-2" style="display: flex; align-items: flex-end;">
          <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Envio de Notificação ao Cidadão -->
<div id="modal-notificacao" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: #ffffff; width: 100%; max-width: 520px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden;">
    <div style="background: var(--primary); color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 16px; font-weight: 700; margin: 0;">📲 Disparar Notificação ao Cidadão</h3>
      <button type="button" onclick="document.getElementById('modal-notificacao').style.display='none'" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
    </div>
    <form action="/notificacoes/enviar" method="POST" style="padding: 20px;">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>
      <input type="hidden" name="entidadeTipo" value="atendimento">
      <input type="hidden" name="entidadeId" value="<?= $atendimento->id ?>">
      <input type="hidden" name="redirect" value="/atendimentos/<?= $atendimento->id ?>">

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Canal de Envio *</label>
        <select name="canal" class="form-select" required>
          <option value="whatsapp">WhatsApp (Oficial)</option>
          <option value="sms">SMS Convencional</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Destinatário *</label>
        <input type="text" name="destinatarioNome" class="form-control" required value="<?= htmlspecialchars($familia ? $familia->pessoaReferenciaNome : 'Cidadão', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Telefone / WhatsApp com DDD *</label>
        <input type="text" name="destinatarioContato" class="form-control mask-phone" required placeholder="(00) 00000-0000" value="<?= htmlspecialchars($familia->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Tipo de Notificação</label>
        <select name="tipoMensagem" class="form-select">
          <option value="lembrete_atendimento">Lembrete de Atendimento / Retorno</option>
          <option value="personalizado">Comunicado Personalizado</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label">Texto da Mensagem *</label>
        <textarea name="mensagem" class="form-control" rows="4" required style="font-size: 13px;"><?= htmlspecialchars(
          "Olá, " . ($familia ? $familia->pessoaReferenciaNome : 'Cidadão') . "! Informamos sobre o andamento do seu atendimento #" . $atendimento->id . " realizado na Assistência Social. Data do atendimento: " . \App\Helpers\FormatHelper::date($atendimento->dataAtendimento) . ($atendimento->retornoPrevisto ? ". Retorno previsto: " . \App\Helpers\FormatHelper::date($atendimento->retornoPrevisto) : "") . ". Em caso de dúvidas, contate nossa equipe.",
          ENT_QUOTES,
          'UTF-8'
        ) ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" onclick="document.getElementById('modal-notificacao').style.display='none'" class="btn btn-outline btn-sm">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm">Enviar Mensagem</button>
      </div>
    </form>
  </div>
</div>

