<?php
use App\Helpers\CsrfHelper;
use App\Helpers\FormatHelper;
use App\Helpers\IconHelper;
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Governança LGPD — Eliminação & Anonimização</h1>
    <p class="page-subtitle">Execução controlada de direitos do titular e retenção de dados (Art. 16 e Art. 18 — Lei 13.709/2018).</p>
  </div>
  <div class="page-actions">
    <a href="/auditoria" class="btn btn-secondary">
      <?= IconHelper::svg('arrow-left', 15) ?>
      <span>Voltar para Auditoria</span>
    </a>
  </div>
</div>

<div class="alert alert-info">
  <?= IconHelper::svg('shield-check', 18) ?>
  <div>
    <strong>Conformidade Legal:</strong> A anonimização expurga de forma irreversível os identificadores pessoais (nome, CPF, telefone, nome social, referências em notificações e anexos) mantendo métricas agregadas sem PII para prestação de contas aos órgãos de controle.
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('lock', 17) ?>
      <span>Anonimização Pontual por Solicitação do Titular (Art. 18, VI)</span>
    </h2>
  </div>
  <div class="card-body">
    <form action="/auditoria/lgpd/anonimizar" method="POST" data-confirm="ATENÇÃO: A anonimização é IRREVERSÍVEL. Nome, CPF, telefone e anexos serão permanentemente expurgados. Deseja continuar?">
      <?= CsrfHelper::input() ?>
      <div class="form-row">
        <div class="col-3 form-group">
          <label class="form-label" for="pessoaId">ID da Pessoa <span class="required">*</span></label>
          <input type="number" id="pessoaId" name="pessoaId" class="form-control mono" required min="1" placeholder="Ex: 1">
        </div>

        <div class="col-6 form-group">
          <label class="form-label" for="motivo">Fundamento Legal / Motivo <span class="required">*</span></label>
          <select id="motivo" name="motivo" class="form-control" required>
            <option value="Exercício do Direito pelo Titular (Art. 18, VI da LGPD)">Exercício do Direito pelo Titular (Art. 18, VI da LGPD)</option>
            <option value="Revogação Expressa de Consentimento">Revogação Expressa de Consentimento</option>
            <option value="Término da Finalidade do Tratamento dos Dados">Término da Finalidade do Tratamento dos Dados</option>
            <option value="Decisão Administrativa da ANPD ou Ordem Judicial">Decisão Administrativa da ANPD ou Ordem Judicial</option>
            <option value="Rotina Legal de Purga por Inatividade Superior a 5 Anos">Rotina Legal de Purga por Inatividade Superior a 5 Anos</option>
          </select>
        </div>

        <div class="col-3 form-group" style="display: flex; align-items: flex-end;">
          <label class="d-flex align-center gap-2" style="cursor: pointer; font-size: 0.84rem; margin-bottom: 8px;">
            <input type="checkbox" name="expurgarDocumentos" value="1" checked>
            <span>Expurgar documentos anexos</span>
          </label>
        </div>
      </div>

      <div class="text-right mt-2">
        <button type="submit" class="btn btn-danger">
          <?= IconHelper::svg('trash', 15) ?>
          <span>Executar Anonimização Permanente</span>
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('alert-triangle', 17) ?>
      <span>Diagnóstico de Retenção — Cadastros Inativos há mais de 5 Anos (Art. 16)</span>
    </h2>
    <span class="badge badge-secondary tabular-nums"><?= count($inativos) ?> elegível(is)</span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID / Titular</th>
          <th>CPF</th>
          <th>Última Movimentação ou Cadastro</th>
          <th>Inatividade</th>
          <th class="text-right">Ação</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($inativos)): ?>
          <tr><td colspan="5" class="text-center text-muted">Nenhum cadastro elegível por inatividade superior a 5 anos encontrado.</td></tr>
        <?php else: ?>
          <?php foreach ($inativos as $item): ?>
            <?php $p = $item['pessoa']; ?>
            <tr>
              <td><strong>#<?= (int)$p->id ?> — <?= $e($p->nomeCompleto) ?></strong></td>
              <td class="mono"><?= $e(FormatHelper::cpf($p->cpf)) ?></td>
              <td><?= $e((string)$item['ultimoAcesso']) ?></td>
              <td><span class="badge badge-warning tabular-nums"><?= (int)$item['anosInativo'] ?> ano(s)</span></td>
              <td class="text-right">
                <form action="/auditoria/lgpd/anonimizar" method="POST" style="display:inline;" data-confirm="Confirmar anonimização do cadastro inativo #<?= (int)$p->id ?>?">
                  <?= CsrfHelper::input() ?>
                  <input type="hidden" name="pessoaId" value="<?= (int)$p->id ?>">
                  <input type="hidden" name="motivo" value="Rotina Legal de Purga por Inatividade Superior a 5 Anos (Art. 16, I da LGPD)">
                  <input type="hidden" name="expurgarDocumentos" value="1">
                  <button type="submit" class="btn btn-danger btn-xs">Anonimizar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= IconHelper::svg('file-text', 17) ?>
      <span>Protocolos Oficiais de Anonimização Emitidos</span>
    </h2>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Protocolo</th>
          <th>ID Titular</th>
          <th>Motivo / Base Legal</th>
          <th>Anexos Purgados</th>
          <th>Data / Hora</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($solicitacoes)): ?>
          <tr><td colspan="6" class="text-center text-muted">Nenhum protocolo de anonimização emitido até o momento.</td></tr>
        <?php else: ?>
          <?php foreach ($solicitacoes as $s): ?>
            <tr>
              <td class="mono fw-bold" style="color: var(--primary);"><?= $e($s['protocolo'] ?? '-') ?></td>
              <td class="mono">#<?= $e((string)($s['pessoa_id'] ?? $s['pessoaId'] ?? '-')) ?></td>
              <td><?= $e($s['motivo'] ?? '-') ?></td>
              <td><span class="badge badge-secondary tabular-nums"><?= $e((string)($s['documentos_expurgados'] ?? 0)) ?> anexo(s)</span></td>
              <td class="mono" style="font-size: 0.81rem;"><?= $e(FormatHelper::datetime($s['data_solicitacao'] ?? $s['dataSolicitacao'] ?? '')) ?></td>
              <td><span class="badge badge-success"><?= $e($s['status'] ?? 'Concluído') ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
