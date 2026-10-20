<?php
use App\Helpers\FormatHelper;
use App\Models\Notificacao;

/** @var Notificacao[] $notificacoes */
/** @var array $filters */
?>
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main);">Comunicações & Notificações ao Cidadão</h2>
    <span style="font-size: 13px; color: var(--text-muted);">Histórico de disparos de lembretes, avisos de benefícios e convocações via WhatsApp e SMS.</span>
  </div>
</div>

<!-- Filtros Rápidos -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-body">
    <form method="GET" action="/notificacoes/historico" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 180px;">
        <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Canal</label>
        <select name="canal" class="form-control" style="font-size: 13px;">
          <option value="">Todos os canais</option>
          <option value="whatsapp" <?= ($filters['canal'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
          <option value="sms" <?= ($filters['canal'] ?? '') === 'sms' ? 'selected' : '' ?>>SMS</option>
        </select>
      </div>

      <div style="flex: 1; min-width: 180px;">
        <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">Origem / Módulo</label>
        <select name="entidadeTipo" class="form-control" style="font-size: 13px;">
          <option value="">Todos os módulos</option>
          <option value="atendimento" <?= ($filters['entidadeTipo'] ?? '') === 'atendimento' ? 'selected' : '' ?>>Atendimentos</option>
          <option value="beneficio" <?= ($filters['entidadeTipo'] ?? '') === 'beneficio' ? 'selected' : '' ?>>Benefícios</option>
          <option value="projeto" <?= ($filters['entidadeTipo'] ?? '') === 'projeto' ? 'selected' : '' ?>>Projetos Sociais</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Filtrar</button>
      <a href="/notificacoes/historico" class="btn btn-outline btn-sm" style="height: 38px;">Limpar</a>
    </form>
  </div>
</div>

<!-- Tabela de Histórico -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Canal / Status</th>
          <th>Destinatário</th>
          <th>Tipo da Mensagem</th>
          <th>Conteúdo do Disparo</th>
          <th>Data / Hora</th>
          <th>Comprovante / Protocolo</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($notificacoes)): ?>
          <tr><td colspan="6" class="empty-state">Nenhuma notificação registrada com os filtros selecionados.</td></tr>
        <?php else: ?>
          <?php foreach ($notificacoes as $n): ?>
            <tr>
              <td>
                <?php if ($n->canal === 'whatsapp'): ?>
                  <span class="badge badge-success" style="font-weight: 700;">🟢 WhatsApp</span>
                <?php elseif ($n->canal === 'sms'): ?>
                  <span class="badge badge-info" style="font-weight: 700;">📱 SMS</span>
                <?php else: ?>
                  <span class="badge badge-secondary"><?= htmlspecialchars($n->canal, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                
                <div style="font-size: 11px; margin-top: 4px; color: var(--text-muted);">
                  Status: <strong><?= htmlspecialchars($n->status, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--text-main); font-size: 13px;">
                  <?= htmlspecialchars($n->destinatarioNome, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); font-family: monospace;">
                  <?= htmlspecialchars($n->destinatarioContato, ENT_QUOTES, 'UTF-8') ?>
                </div>
              </td>
              <td>
                <?php
                $labels = [
                  'lembrete_atendimento' => 'Lembrete de Atendimento',
                  'aviso_beneficio' => 'Aviso de Retirada de Benefício',
                  'convocacao_projeto' => 'Convocação para Projeto Social',
                  'personalizado' => 'Comunicado Geral'
                ];
                $tipoLabel = $labels[$n->tipoMensagem] ?? $n->tipoMensagem;
                ?>
                <span class="badge badge-secondary"><?= htmlspecialchars($tipoLabel, ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td style="max-width: 320px; font-size: 12px; line-height: 1.4; color: var(--text-main);">
                <?= nl2br(htmlspecialchars($n->mensagem, ENT_QUOTES, 'UTF-8')) ?>
              </td>
              <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                <?= FormatHelper::datetime($n->dataEnvio) ?>
              </td>
              <td style="font-size: 11px; color: var(--text-muted); font-family: monospace; white-space: nowrap;">
                <?= htmlspecialchars($n->respostaProvedor ?? 'Disparado com sucesso', ENT_QUOTES, 'UTF-8') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
