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

<!-- Documentos e Laudos Pessoais Anexados (LGPD Seguro) -->
<div class="card" style="margin-top: 24px;">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
      <span>Documentos & Anexos Pessoais (<?= count($documentos ?? []) ?>)</span>
    </div>
    <span style="font-size: 12px; color: var(--text-muted);">Armazenamento criptografado fora do diretório público com controle estrito de RBAC</span>
  </div>
  <div class="card-body">
    <!-- Formulário de Anexação Rápida -->
    <div style="background-color: var(--bg-subtle); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
      <h4 style="font-size: 13px; font-weight: 700; color: var(--primary); margin-bottom: 12px; text-transform: uppercase;">+ Anexar Documento Pessoal</h4>
      <form action="/pessoas/<?= $pessoa->id ?>/documentos" method="POST" enctype="multipart/form-data">
        <?= \App\Core\View::render('partials/csrf', [], null) ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 12px;">
          <div>
            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Tipo de Documento *</label>
            <select name="tipoDocumento" class="form-control" required style="font-size: 13px;">
              <option value="rg_cnh">RG / CNH / Documento com Foto</option>
              <option value="cpf">Cartão / Comprovante de CPF</option>
              <option value="certidao">Certidão de Nascimento / Casamento</option>
              <option value="carteira_trabalho">Carteira de Trabalho / CNIS</option>
              <option value="laudo_medico_pcd">Laudo Médico / Atestado</option>
              <option value="outro">Outro Documento Oficial</option>
            </select>
          </div>
          <div>
            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Descrição / Observação</label>
            <input type="text" name="descricao" class="form-control" placeholder="Ex: Cópia autenticada da certidão de nascimento" style="font-size: 13px;">
          </div>
          <div>
            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Arquivo (PDF, JPG, PNG - até 10MB) *</label>
            <input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required style="font-size: 12px;">
          </div>
        </div>
        <div style="text-align: right;">
          <button type="submit" class="btn btn-primary btn-sm">Salvar Anexo</button>
        </div>
      </form>
    </div>

    <!-- Tabela de Documentos Cadastrados -->
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Tipo de Documento</th>
            <th>Nome do Arquivo / Descrição</th>
            <th>Tamanho</th>
            <th>Data do Envio</th>
            <th style="text-align: right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($documentos)): ?>
            <tr><td colspan="5" class="empty-state">Nenhum documento anexado a esta pessoa até o momento.</td></tr>
          <?php else: ?>
            <?php foreach ($documentos as $doc): ?>
              <tr>
                <td>
                  <?php
                  $tipoLabels = [
                    'rg_cnh' => ['label' => 'RG / CNH', 'class' => 'badge-info'],
                    'cpf' => ['label' => 'CPF', 'class' => 'badge-info'],
                    'certidao' => ['label' => 'Certidão', 'class' => 'badge-secondary'],
                    'carteira_trabalho' => ['label' => 'CTPS / Renda', 'class' => 'badge-success'],
                    'laudo_medico_pcd' => ['label' => 'Laudo PCD/Médico', 'class' => 'badge-warning'],
                    'outro' => ['label' => 'Outro', 'class' => 'badge-secondary']
                  ];
                  $t = $tipoLabels[$doc->tipoDocumento] ?? ['label' => $doc->tipoDocumento, 'class' => 'badge-secondary'];
                  ?>
                  <span class="badge <?= $t['class'] ?>"><?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--text-main); font-size: 13px;">
                    <?= htmlspecialchars($doc->nomeOriginal, ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <?php if (!empty($doc->descricao)): ?>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($doc->descricao, ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>
                </td>
                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                  <?= number_format($doc->tamanhoBytes / 1024, 1, ',', '.') ?> KB
                </td>
                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                  <?= FormatHelper::datetime($doc->dataUpload) ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <a href="/documentos/<?= $doc->id ?>/download" class="btn btn-outline btn-sm" title="Download Seguro">⬇ Baixar</a>
                  <form action="/documentos/<?= $doc->id ?>/excluir" method="POST" style="display: inline;" data-confirm="Deseja realmente excluir permanentemente este documento? Esta ação será registrada na trilha de auditoria.">
                    <?= \App\Core\View::render('partials/csrf', [], null) ?>
                    <input type="hidden" name="redirect" value="/pessoas/<?= $pessoa->id ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); margin-left: 4px;" title="Excluir Anexo">🗑 Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
