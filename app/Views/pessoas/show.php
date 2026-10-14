<?php
use App\Helpers\FormatHelper;
use App\Models\Pessoa;

/** @var Pessoa $pessoa */
/** @var array $historicoVinculos */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/pessoas" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Pessoas</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($pessoa->nomeCompleto, ENT_QUOTES, 'UTF-8') ?></h2>
      <?= FormatHelper::badgeStatus($pessoa->situacaoCadastro) ?>
    </div>
  </div>

  <div style="display: flex; gap: 8px;">
    <a href="/pessoas/<?= $pessoa->id ?>/editar" class="btn btn-primary btn-sm">Editar Cadastro</a>
  </div>
</div>

<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">Dados Cadastrais & Pessoais</div>
  </div>
  <div class="card-body">
    <div class="info-list">
      <div class="info-item">
        <label>Nome Social</label>
        <span><?= htmlspecialchars($pessoa->nomeSocial ?? 'Não informado', ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Data de Nascimento</label>
        <span><?= FormatHelper::date($pessoa->dataNascimento) ?> (<?= !empty($pessoa->dataNascimento) ? (new \DateTime())->diff(new \DateTime($pessoa->dataNascimento))->y : 0 ?> anos)</span>
      </div>
      <div class="info-item">
        <label>CPF</label>
        <span><?= FormatHelper::cpf($pessoa->cpf) ?></span>
      </div>
      <div class="info-item">
        <label>Telefone / WhatsApp</label>
        <span><?= FormatHelper::phone($pessoa->telefone) ?></span>
      </div>
      <div class="info-item">
        <label>Escolaridade</label>
        <span><?= htmlspecialchars($pessoa->escolaridade, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Ocupação</label>
        <span><?= htmlspecialchars($pessoa->ocupacao ?? 'Não informada', ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="info-item">
        <label>Situação Ocupacional</label>
        <span><?= htmlspecialchars($pessoa->situacaoOcupacional, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>

    <?php if (!empty($pessoa->observacoes)): ?>
      <div style="background-color: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md); margin-top: 20px;">
        <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Observações</label>
        <p style="font-size: 13px; margin-top: 4px;"><?= nl2br(htmlspecialchars($pessoa->observacoes, ENT_QUOTES, 'UTF-8')) ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Histórico Completo de Vínculos Familiares (Atendendo Requisito Fundamental do Módulo 3) -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
      <span>Histórico de Vínculos com Núcleos Familiares</span>
    </div>
    <span style="font-size: 12px; color: var(--text-muted);">Comprova a participação histórica da pessoa em diferentes famílias ao longo do tempo</span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Código Família</th>
          <th>Responsável da Família</th>
          <th>Parentesco</th>
          <th>Responsável Familiar?</th>
          <th>Período do Vínculo</th>
          <th>Status do Vínculo</th>
          <th>Observações</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historicoVinculos)): ?>
          <tr><td colspan="8" class="empty-state">Esta pessoa ainda não possui registros de vínculos familiares ativos ou históricos.</td></tr>
        <?php else: ?>
          <?php foreach ($historicoVinculos as $item): ?>
            <?php
              $v = $item['vinculo'];
              $fam = $item['familia'];
            ?>
            <tr style="<?= !$v->ativo ? 'opacity: 0.7; background-color: #fafafa;' : '' ?>">
              <td><strong><?= htmlspecialchars($fam ? $fam->codigo : 'FAM-#' . $v->familiaId, ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($fam ? $fam->pessoaReferenciaNome : 'Desconhecido', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($v->parentesco, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $v->isResponsavel ? '<span class="badge badge-info">Sim</span>' : 'Não' ?></td>
              <td>
                De <?= FormatHelper::date($v->dataInicio) ?> 
                <?= $v->dataTermino ? ' até ' . FormatHelper::date($v->dataTermino) : ' (Atual)' ?>
              </td>
              <td>
                <?php if ($v->ativo): ?>
                  <span class="badge badge-success">Vínculo Ativo</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Vínculo Anterior (Encerrado)</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 12px;"><?= htmlspecialchars($v->observacoes ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align: right;">
                <?php if ($fam): ?>
                  <a href="/familias/<?= $fam->id ?>" class="btn btn-secondary btn-sm">Ver Família</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
