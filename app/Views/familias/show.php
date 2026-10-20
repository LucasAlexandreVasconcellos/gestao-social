<?php
use App\Helpers\FormatHelper;
use App\Models\Familia;
use App\Models\AvaliacaoSocial;

/** @var Familia $familia */
/** @var array $integrantes */
/** @var array $avaliacoes */
/** @var AvaliacaoSocial|null $ultimaAvaliacao */
/** @var array $atendimentos */
/** @var array $beneficios */

$qtdIntegrantesAtivos = count(array_filter($integrantes, fn($v) => $v->ativo));
$rendaPerCapita = $qtdIntegrantesAtivos > 0 ? round($familia->rendaFamiliar / $qtdIntegrantesAtivos, 2) : 0.0;
?>

<!-- Barra de Topo da Ficha -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="/familias" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">← Voltar para Lista de Famílias</a>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($familia->pessoaReferenciaNome, ENT_QUOTES, 'UTF-8') ?></h2>
      <span style="font-size: 14px; color: var(--text-muted); font-weight: 600;">(<?= htmlspecialchars($familia->codigo, ENT_QUOTES, 'UTF-8') ?>)</span>
      <?= FormatHelper::badgeStatus($familia->situacaoCadastral) ?>
      <?php if ($ultimaAvaliacao): ?>
        <?= FormatHelper::badgeVulnerability($ultimaAvaliacao->nivelVulnerabilidade) ?>
      <?php endif; ?>
    </div>
  </div>

  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="/familias/<?= $familia->id ?>/prontuario-pdf?download=1" class="btn btn-outline btn-sm" target="_blank" title="Emitir Prontuário Socioassistencial Completo em PDF com QR Code">📄 Prontuário PDF</a>
    <a href="/avaliacoes/novo?familiaId=<?= $familia->id ?>" class="btn btn-primary btn-sm">+ Avaliação Social</a>
    <a href="/atendimentos/novo?familiaId=<?= $familia->id ?>" class="btn btn-secondary btn-sm">+ Atendimento</a>
    <a href="/beneficios/novo?familiaId=<?= $familia->id ?>" class="btn btn-secondary btn-sm">+ Entrega</a>
    <a href="/familias/<?= $familia->id ?>/editar" class="btn btn-outline btn-sm">Editar Família</a>
  </div>
</div>

<!-- Resumo Socioeconômico Consolidado -->
<div class="metrics-grid" style="margin-bottom: 24px;">
  <div class="metric-card" style="padding: 16px;">
    <div class="metric-title">Integrantes Ativos</div>
    <div class="metric-value" style="font-size: 22px;"><?= $qtdIntegrantesAtivos ?> pessoa(s)</div>
    <div class="metric-subtitle">Núcleo familiar atual</div>
  </div>

  <div class="metric-card" style="padding: 16px;">
    <div class="metric-title">Renda Familiar Mensal</div>
    <div class="metric-value" style="font-size: 22px;"><?= FormatHelper::money($familia->rendaFamiliar) ?></div>
    <div class="metric-subtitle">Despesas: <?= FormatHelper::money($familia->despesasMensais) ?></div>
  </div>

  <div class="metric-card" style="padding: 16px;">
    <div class="metric-title">Renda Per Capita</div>
    <div class="metric-value" style="font-size: 22px;"><?= FormatHelper::money($rendaPerCapita) ?></div>
    <div class="metric-subtitle"><?= $rendaPerCapita <= 375 ? 'Extrema Pobreza (< 1/4 SM)' : ($rendaPerCapita <= 750 ? 'Pobreza (< 1/2 SM)' : 'Baixa Renda') ?></div>
  </div>

  <div class="metric-card" style="padding: 16px;">
    <div class="metric-title">Condição de Moradia</div>
    <div class="metric-value" style="font-size: 20px;"><?= htmlspecialchars($familia->situacaoMoradia, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="metric-subtitle"><?= htmlspecialchars($familia->bairro, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
</div>

<!-- Abas de Conteúdo da Ficha Consolidada -->
<div class="card tabs-container">
  <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
    <div class="tabs-nav" style="margin-bottom: 0;">
      <button type="button" class="tab-btn active" data-tab="integrantes">Composição Familiar (<?= count($integrantes) ?>)</button>
      <button type="button" class="tab-btn" data-tab="endereco">Endereço & Dados</button>
      <button type="button" class="tab-btn" data-tab="avaliacoes">Avaliações Sociais (<?= count($avaliacoes) ?>)</button>
      <button type="button" class="tab-btn" data-tab="atendimentos">Atendimentos & Visitas (<?= count($atendimentos) ?>)</button>
      <button type="button" class="tab-btn" data-tab="beneficios">Benefícios Entregues (<?= count($beneficios) ?>)</button>
      <button type="button" class="tab-btn" data-tab="documentos">Documentos & Laudos (<?= count($documentos ?? []) ?>)</button>
      <button type="button" class="tab-btn" data-tab="historico">Histórico Cadastral</button>
    </div>
  </div>

  <div class="card-body">
    <!-- ABA 1: Composição Familiar -->
    <div id="integrantes" class="tab-content active">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">Integrantes do Núcleo Familiar</h3>
        <span style="font-size: 12px; color: var(--text-muted);">A mudança de vínculo preserva o histórico da pessoa sem exclusão permanente.</span>
      </div>

      <div class="table-responsive" style="margin-bottom: 24px;">
        <table class="table">
          <thead>
            <tr>
              <th>Nome Completo</th>
              <th>Parentesco</th>
              <th>Nascimento / Idade</th>
              <th>CPF</th>
              <th>Ocupação</th>
              <th>Vínculo Desde</th>
              <th>Status</th>
              <th style="text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($integrantes)): ?>
              <tr><td colspan="8" class="empty-state">Nenhum integrante vinculado a esta família.</td></tr>
            <?php else: ?>
              <?php foreach ($integrantes as $vinculo): ?>
                <?php $p = $vinculo->pessoa; ?>
                <tr style="<?= !$vinculo->ativo ? 'opacity: 0.6; background-color: #fafafa;' : '' ?>">
                  <td>
                    <a href="/pessoas/<?= $vinculo->pessoaId ?>" style="font-weight: 600; color: var(--primary); text-decoration: none;">
                      <?= htmlspecialchars($p ? $p->nomeCompleto : 'Pessoa #' . $vinculo->pessoaId, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <?php if ($vinculo->isResponsavel): ?>
                      <span class="badge badge-info" style="font-size: 10px; margin-left: 4px;">Responsável</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($vinculo->parentesco, ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?= $p ? FormatHelper::date($p->dataNascimento) : '-' ?>
                    <?php if ($p && !empty($p->dataNascimento)): ?>
                      <span style="font-size: 11px; color: var(--text-muted);">(<?= (new \DateTime())->diff(new \DateTime($p->dataNascimento))->y ?> anos)</span>
                    <?php endif; ?>
                  </td>
                  <td><?= $p ? FormatHelper::cpf($p->cpf) : '-' ?></td>
                  <td><?= htmlspecialchars($p?->ocupacao ?? 'Não informada', ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= FormatHelper::date($vinculo->dataInicio) ?><?= $vinculo->dataTermino ? ' até ' . FormatHelper::date($vinculo->dataTermino) : '' ?></td>
                  <td>
                    <?php if ($vinculo->ativo): ?>
                      <span class="badge badge-success">Vínculo Ativo</span>
                    <?php else: ?>
                      <span class="badge badge-secondary">Desvinculado (Histórico)</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    <a href="/pessoas/<?= $vinculo->pessoaId ?>" class="btn btn-secondary btn-sm">Ver Pessoa</a>
                    <?php if ($vinculo->ativo && !$vinculo->isResponsavel): ?>
                      <form action="/familias/<?= $familia->id ?>/desvincular-integrante" method="POST" style="display: inline;" data-confirm="Deseja realmente desvincular este integrante? O histórico de vínculos anteriores será mantido no cadastro da pessoa.">
                        <?= \App\Core\View::render('partials/csrf', [], null) ?>
                        <input type="hidden" name="vinculoId" value="<?= $vinculo->id ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);">Desvincular</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Formulário Inline para Vincular Novo Integrante -->
      <div style="background-color: var(--bg-subtle); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
        <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 12px;">+ Adicionar Novo Integrante à Família</h4>
        <form action="/familias/<?= $familia->id ?>/integrantes" method="POST">
          <?= \App\Core\View::render('partials/csrf', [], null) ?>

          <div class="form-grid">
            <div class="form-group col-4">
              <label class="form-label" for="intNome">Nome Completo <span class="required">*</span></label>
              <input type="text" id="intNome" name="nomeCompleto" class="form-control" required placeholder="Nome do familiar">
            </div>

            <div class="form-group col-2">
              <label class="form-label" for="intNasc">Data de Nascimento <span class="required">*</span></label>
              <input type="date" id="intNasc" name="dataNascimento" class="form-control" required>
            </div>

            <div class="form-group col-3">
              <label class="form-label" for="intCpf">CPF (Opcional)</label>
              <input type="text" id="intCpf" name="cpf" class="form-control mask-cpf" placeholder="000.000.000-00">
            </div>

            <div class="form-group col-3">
              <label class="form-label" for="intParentesco">Parentesco com Responsável <span class="required">*</span></label>
              <select id="intParentesco" name="parentesco" class="form-select" required>
                <option value="Filho(a)">Filho(a)</option>
                <option value="Cônjuge/Companheiro(a)">Cônjuge/Companheiro(a)</option>
                <option value="Enteado(a)">Enteado(a)</option>
                <option value="Pai/Mãe">Pai/Mãe</option>
                <option value="Irmão(ã)">Irmão(ã)</option>
                <option value="Avô/Avó">Avô/Avó</option>
                <option value="Neto(a)">Neto(a)</option>
                <option value="Outro">Outro</option>
              </select>
            </div>

            <div class="form-group col-3">
              <label class="form-label" for="intEscolaridade">Escolaridade</label>
              <select id="intEscolaridade" name="escolaridade" class="form-select">
                <option value="Educação Infantil">Educação Infantil</option>
                <option value="Ensino Fundamental Incompleto">Fundamental Incompleto</option>
                <option value="Ensino Fundamental Completo">Fundamental Completo</option>
                <option value="Ensino Médio Incompleto">Médio Incompleto</option>
                <option value="Ensino Médio Completo">Médio Completo</option>
                <option value="Superior">Superior</option>
                <option value="Não se aplica">Não se aplica</option>
              </select>
            </div>

            <div class="form-group col-3">
              <label class="form-label" for="intOcupacao">Ocupação Atual</label>
              <input type="text" id="intOcupacao" name="ocupacao" class="form-control" placeholder="Estudante, Diarista, etc.">
            </div>

            <div class="form-group col-3">
              <label class="form-label" for="intSitOcup">Situação Ocupacional</label>
              <select id="intSitOcup" name="situacaoOcupacional" class="form-select">
                <option value="Estudante">Estudante</option>
                <option value="Desempregado">Desempregado</option>
                <option value="Autônomo">Autônomo</option>
                <option value="Empregado">Empregado</option>
                <option value="Aposentado">Aposentado</option>
                <option value="Não se aplica">Não se aplica</option>
              </select>
            </div>

            <div class="form-group col-3" style="display: flex; align-items: flex-end;">
              <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Integrante</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ABA 2: Endereço & Dados -->
    <div id="endereco" class="tab-content">
      <div class="info-list" style="margin-bottom: 24px;">
        <div class="info-item">
          <label>Código da Família</label>
          <span><?= htmlspecialchars($familia->codigo, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="info-item">
          <label>Data de Cadastro</label>
          <span><?= FormatHelper::date($familia->dataCadastro) ?></span>
        </div>
        <div class="info-item">
          <label>CEP</label>
          <span><?= FormatHelper::cep($familia->cep) ?></span>
        </div>
        <div class="info-item">
          <label>Logradouro Completo</label>
          <span><?= htmlspecialchars($familia->logradouro, ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($familia->numero, ENT_QUOTES, 'UTF-8') ?><?= $familia->complemento ? ' (' . htmlspecialchars($familia->complemento, ENT_QUOTES, 'UTF-8') . ')' : '' ?></span>
        </div>
        <div class="info-item">
          <label>Bairro</label>
          <span><?= htmlspecialchars($familia->bairro, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="info-item">
          <label>Município / UF</label>
          <span><?= htmlspecialchars($familia->municipio, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($familia->uf, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="info-item">
          <label>Situação Cadastral</label>
          <span><?= FormatHelper::badgeStatus($familia->situacaoCadastral) ?></span>
        </div>
      </div>

      <?php if (!empty($familia->observacoes)): ?>
        <div style="background-color: var(--bg-subtle); padding: 16px; border-radius: var(--radius-md);">
          <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Observações do Cadastro</label>
          <p style="margin-top: 4px; font-size: 14px;"><?= nl2br(htmlspecialchars($familia->observacoes, ENT_QUOTES, 'UTF-8')) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- ABA 3: Avaliações Sociais -->
    <div id="avaliacoes" class="tab-content">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">Histórico de Avaliações Socioassistenciais</h3>
        <a href="/avaliacoes/novo?familiaId=<?= $familia->id ?>" class="btn btn-primary btn-sm">+ Realizar Nova Avaliação</a>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Vulnerabilidade</th>
              <th>Renda Per Capita</th>
              <th>Situação Alimentar</th>
              <th>Responsável Técnico</th>
              <th>Próxima Reavaliação</th>
              <th style="text-align: right;">Detalhes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($avaliacoes)): ?>
              <tr><td colspan="7" class="empty-state">Nenhuma avaliação socioassistencial registrada para esta família.</td></tr>
            <?php else: ?>
              <?php foreach ($avaliacoes as $av): ?>
                <tr>
                  <td><?= FormatHelper::date($av->dataAvaliacao) ?></td>
                  <td><?= FormatHelper::badgeVulnerability($av->nivelVulnerabilidade) ?></td>
                  <td><?= FormatHelper::money($av->rendaPerCapita) ?></td>
                  <td><?= htmlspecialchars($av->situacaoAlimentar, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="font-size: 13px;"><?= htmlspecialchars($av->responsavelAvaliacao, ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= FormatHelper::date($av->dataPrevistaReavaliacao) ?></td>
                  <td style="text-align: right;">
                    <a href="/avaliacoes/<?= $av->id ?>" class="btn btn-secondary btn-sm">Ver Parecer</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ABA 4: Atendimentos & Visitas -->
    <div id="atendimentos" class="tab-content">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">Atendimentos, Orientações e Visitas Domiciliares</h3>
        <a href="/atendimentos/novo?familiaId=<?= $familia->id ?>" class="btn btn-primary btn-sm">+ Registrar Atendimento</a>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Tipo de Atendimento</th>
              <th>Descrição Resumida</th>
              <th>Profissional</th>
              <th>Encaminhamentos</th>
              <th>Situação</th>
              <th style="text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($atendimentos)): ?>
              <tr><td colspan="7" class="empty-state">Nenhum atendimento social registrado para esta família.</td></tr>
            <?php else: ?>
              <?php foreach ($atendimentos as $at): ?>
                <tr>
                  <td><?= FormatHelper::date($at->dataAtendimento) ?></td>
                  <td><strong><?= htmlspecialchars($at->tipo, ENT_QUOTES, 'UTF-8') ?></strong></td>
                  <td style="font-size: 13px; max-width: 280px;"><?= htmlspecialchars(mb_strimwidth($at->descricao, 0, 80, '...'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="font-size: 13px;"><?= htmlspecialchars($at->profissionalResponsavel, ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= count($at->encaminhamentos) ?> encaminhamento(s)</td>
                  <td><?= FormatHelper::badgeStatus($at->situacao) ?></td>
                  <td style="text-align: right;">
                    <a href="/atendimentos/<?= $at->id ?>" class="btn btn-secondary btn-sm">Visualizar</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ABA 5: Benefícios Entregues -->
    <div id="beneficios" class="tab-content">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">Distribuição de Mantimentos e Benefícios Assistenciais</h3>
        <a href="/beneficios/novo?familiaId=<?= $familia->id ?>" class="btn btn-primary btn-sm">+ Nova Entrega</a>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Data Entrega</th>
              <th>Benefício</th>
              <th>Comprovante / Recibo</th>
              <th>Itens Entregues</th>
              <th>Responsável</th>
              <th>Situação</th>
              <th style="text-align: right;">Comprovante</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($beneficios)): ?>
              <tr><td colspan="7" class="empty-state">Nenhum benefício assistencial entregue até o momento.</td></tr>
            <?php else: ?>
              <?php foreach ($beneficios as $b): ?>
                <tr>
                  <td><?= FormatHelper::date($b->dataEntrega) ?></td>
                  <td><strong><?= htmlspecialchars($b->tipoBeneficio, ENT_QUOTES, 'UTF-8') ?></strong></td>
                  <td><?= htmlspecialchars($b->comprovante ?? 'REC-PENDENTE', ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="font-size: 13px;">
                    <?php foreach ($b->itens as $it): ?>
                      <div>• <?= $it->quantidade ?> <?= htmlspecialchars($it->unidadeMedida, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($it->nomeProduto, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endforeach; ?>
                  </td>
                  <td><?= htmlspecialchars($b->responsavelDistribuicao, ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= FormatHelper::badgeStatus($b->situacao) ?></td>
                  <td style="text-align: right;">
                    <a href="/beneficios/<?= $b->id ?>" class="btn btn-secondary btn-sm">Recibo</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ABA 6: Histórico Cadastral -->
    <div id="historico" class="tab-content">
      <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 16px;">Registro Histórico de Alterações</h3>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Data / Hora</th>
              <th>Operação</th>
              <th>Descrição do Evento</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($familia->historicoAlteracoes)): ?>
              <tr><td colspan="3" class="empty-state">Nenhuma alteração registrada.</td></tr>
            <?php else: ?>
              <?php foreach (array_reverse($familia->historicoAlteracoes) as $h): ?>
                <tr>
                  <td style="white-space: nowrap;"><?= FormatHelper::datetime($h['data'] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($h['tipo'] ?? 'Alteração', ENT_QUOTES, 'UTF-8') ?></strong></td>
                  <td><?= htmlspecialchars($h['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- ABA 7: Documentos & Laudos Anexados -->
    <div id="documentos" class="tab-content">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main);">Documentos e Laudos do Prontuário</h3>
          <span style="font-size: 12px; color: var(--text-muted);">Armazenamento seguro com isolamento de acesso e proteção estrita conforme a LGPD.</span>
        </div>
      </div>

      <!-- Formulário de Anexação Rápida -->
      <div style="background-color: var(--bg-subtle); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
        <h4 style="font-size: 13px; font-weight: 700; color: var(--primary); margin-bottom: 12px; text-transform: uppercase;">+ Anexar Novo Documento ao Prontuário</h4>
        <form action="/familias/<?= $familia->id ?>/documentos" method="POST" enctype="multipart/form-data">
          <?= \App\Core\View::render('partials/csrf', [], null) ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 12px;">
            <div>
              <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Tipo de Documento *</label>
              <select name="tipoDocumento" class="form-control" required style="font-size: 13px;">
                <option value="comprovante_residencia">Comprovante de Residência</option>
                <option value="comprovante_renda">Comprovante de Renda / CNIS</option>
                <option value="laudo_medico_pcd">Laudo Médico / Diagnóstico PCD</option>
                <option value="termo_guarda">Termo de Guarda / Tutela Judicial</option>
                <option value="certidao">Certidão de Nascimento / Casamento</option>
                <option value="outro">Outro Documento Oficial</option>
              </select>
            </div>
            <div>
              <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Descrição / Observação</label>
              <input type="text" name="descricao" class="form-control" placeholder="Ex: Conta de luz recente com nome da titular" style="font-size: 13px;">
            </div>
            <div>
              <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Arquivo (PDF, JPG, PNG - até 10MB) *</label>
              <input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required style="font-size: 12px;">
            </div>
          </div>
          <div style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-sm">Salvar Anexo no Prontuário</button>
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
              <tr><td colspan="5" class="empty-state">Nenhum documento anexado a esta família até o momento.</td></tr>
            <?php else: ?>
              <?php foreach ($documentos as $doc): ?>
                <tr>
                  <td>
                    <?php
                    $tipoLabels = [
                      'comprovante_residencia' => ['label' => 'Residência', 'class' => 'badge-info'],
                      'comprovante_renda' => ['label' => 'Renda / CNIS', 'class' => 'badge-success'],
                      'laudo_medico_pcd' => ['label' => 'Laudo PCD', 'class' => 'badge-warning'],
                      'termo_guarda' => ['label' => 'Guarda / Judicial', 'class' => 'badge-primary'],
                      'certidao' => ['label' => 'Certidão', 'class' => 'badge-secondary'],
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
                      <input type="hidden" name="redirect" value="/familias/<?= $familia->id ?>#documentos">
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
</div>

