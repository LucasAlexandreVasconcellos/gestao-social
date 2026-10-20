<?php
use App\Helpers\FormatHelper;
use App\Models\BeneficioEntrega;
use App\Models\Familia;

/** @var BeneficioEntrega $entrega */
/** @var Familia|null $familia */
?>
<div class="no-print" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/beneficios" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Benefícios</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Comprovante de Entrega de Benefício</h2>
      <?= FormatHelper::badgeStatus($entrega->situacao) ?>
    </div>
  </div>

  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="/beneficios/<?= $entrega->id ?>/termo-pdf?download=1" class="btn btn-primary btn-sm" target="_blank" title="Baixar Termo Oficial de Concessão em PDF">📄 Emitir Termo Oficial (PDF)</a>
    <button type="button" onclick="document.getElementById('modal-notificacao-beneficio').style.display='flex'" class="btn btn-outline btn-sm">📲 Notificar WhatsApp</button>
    <button onclick="window.print()" class="btn btn-outline btn-sm">Imprimir na Tela</button>

    <?php if ($entrega->situacao === 'Preparada'): ?>
      <form action="/beneficios/<?= $entrega->id ?>/efetivar" method="POST" style="display: inline;" data-confirm="Deseja confirmar a efetivação da entrega? O estoque dos itens correspondentes será baixado.">
        <?= \App\Core\View::render('partials/csrf', [], null) ?>
        <button type="submit" class="btn btn-success btn-sm">Efetivar Entrega</button>
      </form>
    <?php endif; ?>

    <?php if ($entrega->situacao !== 'Cancelada'): ?>
      <form action="/beneficios/<?= $entrega->id ?>/cancelar" method="POST" style="display: inline;" data-confirm="Deseja realmente cancelar este registro de entrega?">
        <?= \App\Core\View::render('partials/csrf', [], null) ?>
        <input type="hidden" name="motivo" value="Cancelamento solicitado administrativamente">
        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);">Cancelar Entrega</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- Recibo Imprimível -->
<div class="card" style="padding: 24px;">
  <!-- Cabeçalho Institucional de Impressão -->
  <div style="text-align: center; border-bottom: 2px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
    <h2 style="font-size: 18px; font-weight: 700; text-transform: uppercase; color: var(--primary);">Entidade de Assistência Social e Acolhimento</h2>
    <div style="font-size: 13px; color: var(--text-muted);">Comprovante Oficial de Distribuição de Mantimentos e Benefícios Assistenciais</div>
    <div style="font-size: 14px; font-weight: 700; margin-top: 6px; color: var(--text-main);">RECIBO Nº: <?= htmlspecialchars($entrega->comprovante ?? 'REC-' . $entrega->id, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="info-list" style="margin-bottom: 24px;">
    <div class="info-item">
      <label>Data da Entrega</label>
      <span><?= FormatHelper::date($entrega->dataEntrega) ?></span>
    </div>
    <div class="info-item">
      <label>Tipo de Benefício</label>
      <span><?= htmlspecialchars($entrega->tipoBeneficio, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Família Beneficiada</label>
      <span><?= htmlspecialchars($familia ? "{$familia->codigo} — {$familia->pessoaReferenciaNome}" : "Família #{$entrega->familiaId}", ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Bairro / Endereço</label>
      <span><?= htmlspecialchars($familia ? "{$familia->logradouro}, {$familia->numero} - {$familia->bairro}" : 'Endereço não informado', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Responsável pela Distribuição</label>
      <span><?= htmlspecialchars($entrega->responsavelDistribuicao, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Situação da Concessão</label>
      <span><?= htmlspecialchars($entrega->situacao, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
    Itens e Produtos Entregues
  </h3>

  <div class="table-responsive" style="margin-bottom: 30px;">
    <table class="table">
      <thead>
        <tr>
          <th>Item / Descrição do Produto</th>
          <th>Quantidade</th>
          <th>Unidade</th>
          <th>Lote</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($entrega->itens)): ?>
          <tr><td colspan="4" class="empty-state">Nenhum item cadastrado nesta entrega.</td></tr>
        <?php else: ?>
          <?php foreach ($entrega->itens as $it): ?>
            <tr>
              <td><strong><?= htmlspecialchars($it->nomeProduto, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= $it->quantidade ?></td>
              <td><?= htmlspecialchars($it->unidadeMedida, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($it->lote ?? 'Geral', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($entrega->observacoes)): ?>
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 30px;">
      <strong>Observações:</strong> <?= htmlspecialchars($entrega->observacoes, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <!-- Linhas de Assinatura para Comprovante Físico -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; padding-top: 20px;">
    <div style="text-align: center;">
      <div style="border-top: 1px solid #000; margin-bottom: 6px;"></div>
      <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($familia?->pessoaReferenciaNome ?? 'Beneficiário Responsável', ENT_QUOTES, 'UTF-8') ?></div>
      <div style="font-size: 11px; color: var(--text-muted);">Assinatura do Recebedor</div>
    </div>

    <div style="text-align: center;">
      <div style="border-top: 1px solid #000; margin-bottom: 6px;"></div>
      <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($entrega->responsavelDistribuicao, ENT_QUOTES, 'UTF-8') ?></div>
      <div style="font-size: 11px; color: var(--text-muted);">Assinatura do Responsável pela Entidade</div>
    </div>
  </div>
</div>

<!-- Modal de Envio de Notificação de Benefício -->
<div id="modal-notificacao-beneficio" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: #ffffff; width: 100%; max-width: 520px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden;">
    <div style="background: var(--primary); color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 16px; font-weight: 700; margin: 0;">📲 Notificar Retirada de Benefício</h3>
      <button type="button" onclick="document.getElementById('modal-notificacao-beneficio').style.display='none'" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
    </div>
    <form action="/notificacoes/enviar" method="POST" style="padding: 20px;">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>
      <input type="hidden" name="entidadeTipo" value="beneficio">
      <input type="hidden" name="entidadeId" value="<?= $entrega->id ?>">
      <input type="hidden" name="redirect" value="/beneficios/<?= $entrega->id ?>">

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Canal de Envio *</label>
        <select name="canal" class="form-select" required>
          <option value="whatsapp">WhatsApp (Oficial)</option>
          <option value="sms">SMS Convencional</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Beneficiário *</label>
        <input type="text" name="destinatarioNome" class="form-control" required value="<?= htmlspecialchars($familia ? $familia->pessoaReferenciaNome : 'Beneficiário', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Telefone / WhatsApp com DDD *</label>
        <input type="text" name="destinatarioContato" class="form-control mask-phone" required placeholder="(00) 00000-0000" value="<?= htmlspecialchars($familia->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Tipo de Notificação</label>
        <select name="tipoMensagem" class="form-select">
          <option value="aviso_beneficio">Aviso de Liberação / Retirada</option>
          <option value="personalizado">Comunicado Geral</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label">Texto da Mensagem *</label>
        <textarea name="mensagem" class="form-control" rows="4" required style="font-size: 13px;"><?= htmlspecialchars(
          "Olá, " . ($familia ? $familia->pessoaReferenciaNome : 'Beneficiário') . "! Seu benefício (" . $entrega->tipoBeneficio . ") cadastrado sob o registro #" . $entrega->id . " está pronto para retirada na sede do Centro Social. Favor comparecer com documento de identificação com foto.",
          ENT_QUOTES,
          'UTF-8'
        ) ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" onclick="document.getElementById('modal-notificacao-beneficio').style.display='none'" class="btn btn-outline btn-sm">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm">Enviar Mensagem</button>
      </div>
    </form>
  </div>
</div>
