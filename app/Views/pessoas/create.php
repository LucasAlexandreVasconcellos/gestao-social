<?php
/** @var array $old */
/** @var array $errors */
?>
<div style="margin-bottom: 20px;">
  <a href="/pessoas" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">← Voltar para Pessoas</a>
  <h2 style="font-size: 20px; font-weight: 700; color: var(--text-main);">Cadastrar Pessoa</h2>
  <p style="font-size: 13px; color: var(--text-muted);">Registro individual para integração a composições familiares e acompanhamento socioassistencial.</p>
</div>

<div class="card">
  <div class="card-body">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px; display: block;">
        <div style="font-weight: 700; margin-bottom: 6px;">Por favor, corrija os seguintes itens:</div>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.6;">
          <?php foreach ($errors as $erro): ?>
            <li><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="/pessoas" method="POST">
      <?= \App\Core\View::render('partials/csrf', [], null) ?>

      <div class="form-grid" style="margin-bottom: 20px;">
        <div class="form-group col-6">
          <label class="form-label" for="nomeCompleto">Nome Completo <span class="required">*</span></label>
          <input type="text" id="nomeCompleto" name="nomeCompleto" class="form-control <?= !empty($errors['nomeCompleto']) ? 'is-invalid' : '' ?>" required value="<?= htmlspecialchars($old['nomeCompleto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nomeCompleto'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nomeCompleto'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-6">
          <label class="form-label" for="nomeSocial">Nome Social (Se houver)</label>
          <input type="text" id="nomeSocial" name="nomeSocial" class="form-control <?= !empty($errors['nomeSocial']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['nomeSocial'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['nomeSocial'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['nomeSocial'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="dataNascimento">Data de Nascimento <span class="required">*</span></label>
          <input type="date" id="dataNascimento" name="dataNascimento" class="form-control <?= !empty($errors['dataNascimento']) ? 'is-invalid' : '' ?>" required value="<?= htmlspecialchars($old['dataNascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['dataNascimento'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['dataNascimento'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="cpf">CPF (Opcional para Beneficiários)</label>
          <input type="text" id="cpf" name="cpf" class="form-control mask-cpf <?= !empty($errors['cpf']) ? 'is-invalid' : '' ?>" placeholder="000.000.000-00" value="<?= htmlspecialchars($old['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <span class="form-help">Não é obrigatório para crianças ou acolhidos sem documento.</span>
          <?php if (!empty($errors['cpf'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['cpf'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="telefone">Telefone / WhatsApp</label>
          <input type="text" id="telefone" name="telefone" class="form-control mask-phone <?= !empty($errors['telefone']) ? 'is-invalid' : '' ?>" placeholder="(11) 90000-0000" value="<?= htmlspecialchars($old['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['telefone'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['telefone'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-3">
          <label class="form-label" for="escolaridade">Escolaridade</label>
          <select id="escolaridade" name="escolaridade" class="form-select <?= !empty($errors['escolaridade']) ? 'is-invalid' : '' ?>">
            <option value="Educação Infantil">Educação Infantil</option>
            <option value="Ensino Fundamental Incompleto" selected>Fundamental Incompleto</option>
            <option value="Ensino Fundamental Completo">Fundamental Completo</option>
            <option value="Ensino Médio Incompleto">Médio Incompleto</option>
            <option value="Ensino Médio Completo">Médio Completo</option>
            <option value="Superior">Ensino Superior</option>
            <option value="Não se aplica">Não se aplica</option>
          </select>
          <?php if (!empty($errors['escolaridade'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['escolaridade'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="ocupacao">Ocupação / Profissão</label>
          <input type="text" id="ocupacao" name="ocupacao" class="form-control <?= !empty($errors['ocupacao']) ? 'is-invalid' : '' ?>" placeholder="Diarista, Ajudante, Autônomo, etc." value="<?= htmlspecialchars($old['ocupacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($errors['ocupacao'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['ocupacao'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacaoOcupacional">Situação Ocupacional</label>
          <select id="situacaoOcupacional" name="situacaoOcupacional" class="form-select <?= !empty($errors['situacaoOcupacional']) ? 'is-invalid' : '' ?>">
            <option value="Desempregado">Desempregado</option>
            <option value="Autônomo" selected>Autônomo / Bico</option>
            <option value="Empregado">Empregado (Formal)</option>
            <option value="Aposentado">Aposentado / Pensionista</option>
            <option value="Estudante">Estudante</option>
            <option value="Não se aplica">Não se aplica</option>
          </select>
          <?php if (!empty($errors['situacaoOcupacional'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['situacaoOcupacional'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-4">
          <label class="form-label" for="situacaoCadastro">Situação do Cadastro</label>
          <select id="situacaoCadastro" name="situacaoCadastro" class="form-select <?= !empty($errors['situacaoCadastro']) ? 'is-invalid' : '' ?>">
            <option value="Ativo" selected>Ativo</option>
            <option value="Inativo">Inativo</option>
          </select>
          <?php if (!empty($errors['situacaoCadastro'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['situacaoCadastro'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group col-12">
          <label class="form-label" for="observacoes">Observações Autorizadas</label>
          <textarea id="observacoes" name="observacoes" class="form-control <?= !empty($errors['observacoes']) ? 'is-invalid' : '' ?>" rows="3" placeholder="Informações sociais pertinentes, com consentimento..."><?= htmlspecialchars($old['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <?php if (!empty($errors['observacoes'])): ?>
            <span class="form-error"><?= htmlspecialchars($errors['observacoes'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
        <a href="/pessoas" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Salvar Pessoa</button>
      </div>
    </form>
  </div>
</div>
