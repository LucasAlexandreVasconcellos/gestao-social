<?php
use App\Helpers\FormatHelper;
use App\Models\Doacao;

/** @var Doacao $doacao */
?>
<div class="no-print" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/doacoes" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Doações</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Comprovante de Doação #<?= $doacao->id ?></h2>
      <span class="badge <?= $doacao->tipoDoacao === 'Produtos' ? 'badge-info' : 'badge-success' ?>"><?= htmlspecialchars($doacao->tipoDoacao, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <div style="display: flex; gap: 8px;">
    <a href="/doacoes/<?= $doacao->id ?>/recibo-pdf?download=1" class="btn btn-primary btn-sm" target="_blank" title="Baixar Recibo Oficial de Doação em PDF">📄 Emitir Recibo Oficial (PDF)</a>
    <button onclick="window.print()" class="btn btn-outline btn-sm">Imprimir na Tela</button>
  </div>
</div>

<div class="card" style="padding: 24px;">
  <div style="text-align: center; border-bottom: 2px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
    <h2 style="font-size: 18px; font-weight: 700; text-transform: uppercase; color: var(--primary);">Entidade de Assistência Social e Acolhimento</h2>
    <div style="font-size: 13px; color: var(--text-muted);">Termo Institucional de Recebimento de Doação</div>
    <div style="font-size: 14px; font-weight: 700; margin-top: 6px; color: var(--text-main);">COMPROVANTE Nº: <?= htmlspecialchars($doacao->comprovante ?? 'COMP-' . $doacao->id, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="info-list" style="margin-bottom: 24px;">
    <div class="info-item">
      <label>Doador / Parceiro</label>
      <span><?= htmlspecialchars($doacao->nomeDoador, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Tipo de Doador</label>
      <span><?= htmlspecialchars($doacao->tipoDoador, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>CPF / CNPJ</label>
      <span><?= htmlspecialchars($doacao->documento ?? 'Não informado', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Data de Recebimento</label>
      <span><?= FormatHelper::date($doacao->dataRecebimento) ?></span>
    </div>
    <div class="info-item">
      <label>Tipo de Doação</label>
      <span><?= htmlspecialchars($doacao->tipoDoacao, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="info-item">
      <label>Valor Estimado / Real</label>
      <span style="font-weight: 700; color: var(--success);"><?= FormatHelper::money($doacao->valorEstimado) ?></span>
    </div>
    <div class="info-item">
      <label>Responsável pelo Recebimento</label>
      <span><?= htmlspecialchars($doacao->responsavel, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <?php if (!empty($doacao->descricao)): ?>
    <div style="background-color: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md); margin-bottom: 24px;">
      <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Descrição / Finalidade</label>
      <p style="font-size: 13px; margin-top: 4px;"><?= nl2br(htmlspecialchars($doacao->descricao, ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
  <?php endif; ?>

  <?php if (!empty($doacao->itens)): ?>
    <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
      Itens de Mantimentos e Produtos Recebidos
    </h3>
    <div class="table-responsive" style="margin-bottom: 30px;">
      <table class="table">
        <thead>
          <tr>
            <th>Descrição do Produto</th>
            <th>Quantidade</th>
            <th>Unidade</th>
            <th>Lote</th>
            <th>Validade</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($doacao->itens as $it): ?>
            <tr>
              <td><strong><?= htmlspecialchars($it->nomeProduto, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= $it->quantidade ?></td>
              <td><?= htmlspecialchars($it->unidadeMedida, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($it->lote ?? 'Lote Padrão', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= FormatHelper::date($it->dataValidade) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Assinaturas -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 50px; padding-top: 20px;">
    <div style="text-align: center;">
      <div style="border-top: 1px solid #000; margin-bottom: 6px;"></div>
      <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($doacao->nomeDoador, ENT_QUOTES, 'UTF-8') ?></div>
      <div style="font-size: 11px; color: var(--text-muted);">Assinatura do Doador / Representante</div>
    </div>

    <div style="text-align: center;">
      <div style="border-top: 1px solid #000; margin-bottom: 6px;"></div>
      <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($doacao->responsavel, ENT_QUOTES, 'UTF-8') ?></div>
      <div style="font-size: 11px; color: var(--text-muted);">Responsável pelo Recebimento na Entidade</div>
    </div>
  </div>
</div>
