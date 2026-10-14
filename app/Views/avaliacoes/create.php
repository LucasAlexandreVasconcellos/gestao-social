<?php
use App\Models\Familia;

/** @var array $familias */
/** @var Familia|null $familiaSelecionada */
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/avaliacoes" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Avaliações</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Nova Avaliação Socioassistencial</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Instrumento de diagnóstico social, mensuração de carências e emissão de parecer técnico do assistente social.</p>
</div>

<div class="card">
  <div class="card-body">
    <form action="/avaliacoes" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <!-- 1. Identificação da Família e Responsável Técnico -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        <span>1. Identificação do Atendimento Técnico</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-6">
          <label class="form-label" for="familiaId">Família a Ser Avaliada <span class="required">*</span></label>
          <select id="familiaId" name="familiaId" class="form-select" required>
            <option value="">Selecione uma família...</option>
            <?php foreach ($familias as $f): ?>
              <option value="<?= $f->id ?>" <?= ((int)($old['familiaId'] ?? 0)) === $f->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($f->codigo, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($f->pessoaReferenciaNome, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($f->bairro, ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['familiaId'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['familiaId'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataAvaliacao">Data da Avaliação <span class="required">*</span></label>
          <input type="date" id="dataAvaliacao" name="dataAvaliacao" class="form-control" required value="<?= htmlspecialchars($old['dataAvaliacao'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="responsavelAvaliacao">Assistente Social / Técnico <span class="required">*</span></label>
          <input type="text" id="responsavelAvaliacao" name="responsavelAvaliacao" class="form-control" required placeholder="Nome e registro CRESS" value="<?= htmlspecialchars($old['responsavelAvaliacao'] ?? 'Ana Paula Silva (CRESS 12345/SP)', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <!-- 2. Dimensões Sociais e Condições de Vida -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
        <span>2. Dimensões Socioeconômicas e Vulnerabilidades Identificadas</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-4">
          <label class="form-label" for="condicoesMoradia">Condições de Moradia <span class="required">*</span></label>
          <select id="condicoesMoradia" name="condicoesMoradia" class="form-select" required>
            <option value="Boa">Adequada / Saneamento Básico</option>
            <option value="Regular" selected>Regular (Infiltrações ou carência pontual)</option>
            <option value="Precária">Precária (Madeira/Lona/Sem Saneamento)</option>
            <option value="Área de Risco">Área de Risco Geológico / Enchente</option>
          </select>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacaoAlimentar">Segurança Alimentar <span class="required">*</span></label>
          <select id="situacaoAlimentar" name="situacaoAlimentar" class="form-select" required>
            <option value="Segura">Segurança Alimentar Plena</option>
            <option value="Insegurança Leve" selected>Insegurança Leve (Preocupação com provisão)</option>
            <option value="Insegurança Moderada">Insegurança Moderada (Restrição de quantidade)</option>
            <option value="Insegurança Grave">Insegurança Grave (Escassez severa / Fome)</option>
          </select>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacaoEmprego">Situação de Emprego <span class="required">*</span></label>
          <select id="situacaoEmprego" name="situacaoEmprego" class="form-select" required>
            <option value="Renda estável">Vínculo formal / Renda estável</option>
            <option value="Informal/Instável" selected>Informal / Bicos / Renda incerta</option>
            <option value="Desemprego total">Desemprego total no núcleo</option>
          </select>
        </div>

        <!-- Indicadores de Composição de Risco -->
        <div class="form-group col-12" style="background-color: var(--bg-subtle); padding: 16px; border-radius: var(--radius-md);">
          <label class="form-label" style="margin-bottom: 8px;">Fatores Específicos de Prioridade no Núcleo Familiar:</label>
          <div style="display: flex; gap: 24px; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
              <input type="checkbox" name="temCriancas" value="1" <?= !empty($old['temCriancas']) ? 'checked' : '' ?>>
              <span>Presença de Crianças / Adolescentes</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
              <input type="checkbox" name="temIdosos" value="1" <?= !empty($old['temIdosos']) ? 'checked' : '' ?>>
              <span>Presença de Idosos Dependentes</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
              <input type="checkbox" name="temPcd" value="1" <?= !empty($old['temPcd']) ? 'checked' : '' ?>>
              <span>Pessoas com Deficiência (PCD) ou Doença Crônica</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
              <input type="checkbox" name="necessidadeAcompanhamento" value="1" checked>
              <span>Necessidade de Acompanhamento Social Contínuo</span>
            </label>
          </div>
        </div>

        <!-- Carências Prioritárias -->
        <div class="form-group col-12">
          <label class="form-label">Carências Imediatas Identificadas:</label>
          <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 4px;">
            <?php
              $carencias = [
                'alimentacao' => 'Alimentação / Cesta Básica',
                'saude' => 'Saúde / Remédios',
                'higiene' => 'Higiene Pessoal e Limpeza',
                'vestuario' => 'Vestuário / Agasalhos',
                'habitacao' => 'Habitação / Reparos',
                'educacao' => 'Material Escolar / Apoio Pedagógico',
                'acessibilidade' => 'Acessibilidade / Fraldas / Órteses'
              ];
              $selCare = $old['necessidadesPrioritarias'] ?? ['alimentacao', 'higiene'];
            ?>
            <?php foreach ($carencias as $key => $lbl): ?>
              <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; background: #ffffff; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer;">
                <input type="checkbox" name="necessidadesPrioritarias[]" value="<?= $key ?>" <?= in_array($key, (array)$selCare, true) ? 'checked' : '' ?>>
                <span><?= $lbl ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- 3. Classificação e Parecer Social -->
      <div class="form-section-title">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
        <span>3. Julgamento Técnico, Parecer Social e Reavaliação</span>
      </div>

      <div class="form-grid" style="margin-bottom: 24px;">
        <div class="form-group col-4">
          <label class="form-label" for="nivelVulnerabilidade">Classificação Técnica de Vulnerabilidade <span class="required">*</span></label>
          <select id="nivelVulnerabilidade" name="nivelVulnerabilidade" class="form-select" required>
            <option value="Baixa" <?= ($old['nivelVulnerabilidade'] ?? '') === 'Baixa' ? 'selected' : '' ?>>Baixa</option>
            <option value="Média" <?= ($old['nivelVulnerabilidade'] ?? '') === 'Média' ? 'selected' : '' ?>>Média</option>
            <option value="Alta" <?= ($old['nivelVulnerabilidade'] ?? 'Alta') === 'Alta' ? 'selected' : '' ?>>Alta</option>
            <option value="Crítica" <?= ($old['nivelVulnerabilidade'] ?? '') === 'Crítica' ? 'selected' : '' ?>>Crítica (Extrema Urgência)</option>
          </select>
          <span class="form-help">A classificação é revisável e decorre da soberana avaliação profissional.</span>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="justificativaClassificacao">Justificativa da Classificação</label>
          <input type="text" id="justificativaClassificacao" name="justificativaClassificacao" class="form-control" placeholder="Ex: Renda per capita zerada e vulnerabilidade habitacional" value="<?= htmlspecialchars($old['justificativaClassificacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="dataPrevistaReavaliacao">Data Prevista para Reavaliação</label>
          <input type="date" id="dataPrevistaReavaliacao" name="dataPrevistaReavaliacao" class="form-control" value="<?= htmlspecialchars($old['dataPrevistaReavaliacao'] ?? date('Y-m-d', strtotime('+90 days')), ENT_QUOTES, 'UTF-8') ?>">
          <span class="form-help">Recomenda-se 30 a 90 dias para casos críticos.</span>
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="parecerSocial">Parecer Técnico Social <span class="required">*</span></label>
          <textarea id="parecerSocial" name="parecerSocial" class="form-control" rows="5" required placeholder="Relato técnico fundamentado sobre a dinâmica familiar, histórico da demanda, providências indicadas e plano de intervenção socioassistencial..."><?= htmlspecialchars($old['parecerSocial'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <?php if (!empty($errors['parecerSocial'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['parecerSocial'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/avaliacoes" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Avaliação Social</button>
      </div>
    </form>
  </div>
</div>
