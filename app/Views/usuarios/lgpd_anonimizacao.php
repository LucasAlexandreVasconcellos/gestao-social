<?php
use App\Helpers\FormatHelper;
use App\Models\Usuario;

/** @var Usuario $currentUser */
/** @var array $inativos */
/** @var array $solicitacoes */
/** @var string|null $success */
/** @var string|null $error */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/auditoria" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Auditoria Geral</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main);">Governança LGPD — Eliminação & Anonimização</h2>
      <span class="badge badge-primary" style="font-size: 13px; padding: 4px 10px;">Art. 18 — Lei 13.709/2018</span>
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

<!-- Resumo Informativo LGPD -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      <span>Diretrizes de Conformidade Legal e Anonimização</span>
    </div>
  </div>
  <div class="card-body">
    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 0;">
      Em estrita conformidade com os <strong>Arts. 16 e 18 da LGPD (Lei Geral de Proteção de Dados)</strong>, esta ferramenta permite a anonimização irreversível dos dados pessoais e sensíveis dos titulares atendidos, mantendo a integridade das métricas quantitativas e histórico contábil para fins de prestação de contas aos órgãos públicos de controle (MDS, Tribunal de Contas).
    </p>
  </div>
</div>

<!-- Card 1: Executar Anonimização sob Demanda -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
      <span>Anonimização Pontual por Solicitação do Titular (Art. 18, VI)</span>
    </div>
  </div>
  <div class="card-body">
    <form action="/auditoria/lgpd/anonimizar" method="POST" data-confirm="ATENÇÃO: A anonimização é um procedimento IRREVERSÍVEL. O nome, CPF, telefone e laudos anexos do titular serão destruídos/substituídos por hash criptográfico. Deseja prosseguir?">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid">
        <div class="form-group col-3">
          <label class="form-label" for="pessoaId">ID da Pessoa Cadastrada *</label>
          <input type="number" id="pessoaId" name="pessoaId" class="form-control" required placeholder="Ex: 1, 2, 3">
        </div>

        <div class="form-group col-5">
          <label class="form-label" for="motivo">Fundamento Legal / Motivo da Solicitação *</label>
          <select id="motivo" name="motivo" class="form-select" required>
            <option value="Exercício do Direito pelo Titular (Art. 18, VI da LGPD)">Exercício do Direito pelo Titular (Art. 18, VI da LGPD)</option>
            <option value="Revogação Expressa de Consentimento">Revogação Expressa de Consentimento</option>
            <option value="Término da Finalidade do Tratamento dos Dados">Término da Finalidade do Tratamento dos Dados</option>
            <option value="Decisão Administrativa da ANPD ou Ordem Judicial">Decisão Administrativa da ANPD ou Ordem Judicial</option>
            <option value="Rotina Legal de Purga por Inatividade Superior a 5 Anos">Rotina Legal de Purga por Inatividade Superior a 5 Anos</option>
          </select>
        </div>

        <div class="form-group col-4" style="display: flex; align-items: center; padding-top: 26px;">
          <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--text-main);">
            <input type="checkbox" name="expurgarDocumentos" value="1" checked style="width: 16px; height: 16px;">
            <span>Destruir permanentemente documentos e laudos anexados</span>
          </label>
        </div>

        <div class="form-group col-12" style="text-align: right; margin-top: 10px;">
          <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger); font-weight: 700;">
            🔒 Executar Anonimização Permanente
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Card 2: Varredura de Cadastros Inativos (> 5 Anos) -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
      <span>Diagnóstico de Retenção — Cadastros Inativos há mais de 5 Anos (Art. 16)</span>
    </div>
    <span style="font-size: 12px; color: var(--text-muted);"><?= count($inativos) ?> titular(es) elegível(is) para purga</span>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID / Nome Cadastrado</th>
          <th>CPF</th>
          <th>Última Interação / Registro</th>
          <th>Tempo de Inatividade</th>
          <th style="text-align: right;">Ação Recomendada</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($inativos)): ?>
          <tr><td colspan="5" class="empty-state">Parabéns! Não existem cadastros sem movimentação nos últimos 5 anos na base de dados.</td></tr>
        <?php else: ?>
          <?php foreach ($inativos as $item): ?>
            <?php $p = $item['pessoa']; ?>
            <tr>
              <td>
                <div style="font-weight: 600; color: var(--text-main); font-size: 13px;">
                  #<?= $p->id ?> — <?= htmlspecialchars($p->nomeCompleto, ENT_QUOTES, 'UTF-8') ?>
                </div>
              </td>
              <td><?= FormatHelper::cpf($p->cpf) ?></td>
              <td style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars((string)$item['ultimoAcesso'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge badge-warning"><?= $item['anosInativo'] ?> anos inativo</span>
              </td>
              <td style="text-align: right;">
                <form action="/auditoria/lgpd/anonimizar" method="POST" style="display: inline;" data-confirm="Confirmar anonimização do cadastro inativo #<?= $p->id ?> (<?= htmlspecialchars($p->nomeCompleto, ENT_QUOTES, 'UTF-8') ?>)?">
                  <?= \App\Core\View::render('partials/csrf', [], null) ?>
                  <input type="hidden" name="pessoaId" value="<?= $p->id ?>">
                  <input type="hidden" name="motivo" value="Rotina Legal de Purga por Inatividade Superior a 5 Anos (Art. 16, I da LGPD)">
                  <input type="hidden" name="expurgarDocumentos" value="1">
                  <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);">
                    Anonimizar
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Card 3: Histórico de Anonimizações Concluídas -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
      <span>Protocolos Oficiais de Anonimização Emitidos (Comprovação ANPD)</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Protocolo</th>
          <th>ID Titular</th>
          <th>Motivo / Base Legal</th>
          <th>Docs Físicos Purgados</th>
          <th>Data / Hora</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($solicitacoes)): ?>
          <tr><td colspan="6" class="empty-state">Nenhum protocolo de anonimização emitido até o momento.</td></tr>
        <?php else: ?>
          <?php foreach ($solicitacoes as $s): ?>
            <tr>
              <td>
                <strong style="font-family: monospace; color: var(--primary);"><?= htmlspecialchars($s['protocolo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
              </td>
              <td>#<?= htmlspecialchars((string)($s['pessoa_id'] ?? $s['pessoaId'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size: 12px;"><?= htmlspecialchars($s['motivo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge badge-secondary"><?= htmlspecialchars((string)($s['documentos_expurgados'] ?? 0), ENT_QUOTES, 'UTF-8') ?> anexos</span>
              </td>
              <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                <?= FormatHelper::datetime($s['data_solicitacao'] ?? $s['dataSolicitacao'] ?? '') ?>
              </td>
              <td>
                <span class="badge badge-success"><?= htmlspecialchars($s['status'] ?? 'Concluído', ENT_QUOTES, 'UTF-8') ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
