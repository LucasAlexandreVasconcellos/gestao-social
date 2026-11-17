<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\DocumentoRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Core\Database;
use App\Core\Session;
use PDO;

final class LgpdService
{
    private const SESSION_KEY = '_demo_lgpd_solicitacoes';

    public function __construct(
        private PessoaRepositoryInterface $pessoaRepo,
        private AtendimentoRepositoryInterface $atendimentoRepo,
        private DocumentoRepositoryInterface $documentoRepo,
        private AuditLogService $auditLog
    ) {}

    public function anonimizarPessoa(int $pessoaId, string $motivo, int $operadorId, bool $expurgarDocumentos = true): array
    {
        $pessoa = $this->pessoaRepo->findById($pessoaId);
        if (!$pessoa) {
            return ['success' => false, 'error' => 'Pessoa não encontrada no sistema.'];
        }

        $protocolo = 'LGPD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // 1. Expurgar documentos anexos vinculados à pessoa se solicitado
        $docsExcluidos = 0;
        if ($expurgarDocumentos) {
            $docs = $this->documentoRepo->findByEntidade('pessoa', $pessoaId);
            $baseDir = dirname(__DIR__, 2);
            foreach ($docs as $doc) {
                $path = $baseDir . '/' . ltrim($doc->caminhoRelativo, '/\\');
                if (file_exists($path)) {
                    @unlink($path);
                }
                $this->documentoRepo->delete($doc->id);
                $docsExcluidos++;
            }
        }

        // 2. Descobre vínculos familiares antes de limpar para atualizar referências caso aplicável
        $vinculos = $this->pessoaRepo->getHistoricoVinculosFamiliares($pessoaId);
        $familiaIdsResp = [];
        foreach ($vinculos as $v) {
            if ($v->isResponsavel) {
                $familiaIdsResp[] = $v->familiaId;
            }
        }

        // 3. Anonimização estrita dos campos identificadores (Art. 18 LGPD)
        $pessoa->nomeCompleto = "Cidadão Anonimizado ({$protocolo})";
        $pessoa->nomeSocial = null;
        $pessoa->cpf = null;
        $pessoa->telefone = null;
        $pessoa->ocupacao = 'Anonimizado';
        $pessoa->observacoes = sprintf(
            "[DADOS PESSOAIS E SENSÍVEIS PURGADOS EM %s CONFORME ART. 18 DA LGPD]\nProtocolo: %s\nMotivo/Base Legal: %s\nDocumentos físicos destruídos: %d",
            date('d/m/Y H:i:s'),
            $protocolo,
            $motivo,
            $docsExcluidos
        );
        $pessoa->situacaoCadastro = 'Inativo';

        $this->pessoaRepo->update($pessoa);

        // 4. Anonimiza referências derivadas em famílias e notificações no banco conectado
        if (Database::isConnected()) {
            try {
                $db = Database::getConnection();
                foreach ($familiaIdsResp as $famId) {
                    $stmtFam = $db->prepare("
                        UPDATE gestao_social.familias
                           SET nome_referencia = :anon_nome
                         WHERE id = :fam_id
                    ");
                    $stmtFam->execute([
                        'anon_nome' => "Titular Anonimizado ({$protocolo})",
                        'fam_id' => $famId,
                    ]);
                }

                $stmtNotif = $db->prepare("
                    UPDATE gestao_social.notificacoes
                       SET destinatario_nome = :anon_nome,
                           destinatario_contato = 'ANONIMIZADO'
                     WHERE entidade_tipo = 'pessoa' AND entidade_id = :pessoa_id
                ");
                $stmtNotif->execute([
                    'anon_nome' => "Titular Anonimizado ({$protocolo})",
                    'pessoa_id' => $pessoaId,
                ]);
            } catch (\Throwable) {
                // Silencioso caso tabela auxiliar não exista em ambiente parcial
            }
        }

        // 5. Registrar a solicitação na trilha LGPD
        $registro = [
            'protocolo' => $protocolo,
            'pessoa_id' => $pessoaId,
            'motivo' => $motivo,
            'operador_id' => $operadorId,
            'documentos_expurgados' => $docsExcluidos,
            'data_solicitacao' => date('Y-m-d H:i:s'),
            'status' => 'Concluído'
        ];

        if (Database::isConnected()) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    INSERT INTO gestao_social.lgpd_solicitacoes (
                        protocolo, pessoa_id, motivo, operador_id, documentos_expurgados, data_solicitacao, status
                    ) VALUES (
                        :protocolo, :pessoa_id, :motivo, :operador_id, :documentos_expurgados, :data_solicitacao, :status
                    )
                ");
                $stmt->execute($registro);
            } catch (\Throwable) {
                $solicitacoes = Session::get(self::SESSION_KEY, []);
                $solicitacoes[] = $registro;
                Session::set(self::SESSION_KEY, $solicitacoes);
            }
        } else {
            $solicitacoes = Session::get(self::SESSION_KEY, []);
            $solicitacoes[] = $registro;
            Session::set(self::SESSION_KEY, $solicitacoes);
        }

        // 6. Auditoria sem vazar o nome original do titular após o expurgo
        $this->auditLog->log(
            'Anonimização de Titular (LGPD Art. 18)',
            'Pessoa',
            $pessoaId,
            "Titular #{$pessoaId} anonimizado permanentemente sob o protocolo {$protocolo}. Documentos expurgados: {$docsExcluidos}. Motivo: {$motivo}."
        );

        return [
            'success' => true,
            'protocolo' => $protocolo,
            'docsExcluidos' => $docsExcluidos
        ];
    }

    public function buscarInativos(int $anos = 5): array
    {
        $limite = date('Y-m-d', strtotime("-{$anos} years"));
        $inativos = [];

        $todas = $this->pessoaRepo->findAll();
        $atendimentos = $this->atendimentoRepo->findAll();

        $ultimoPorPessoa = [];
        $ultimoPorFamilia = [];
        foreach ($atendimentos as $at) {
            $dataAt = substr((string)$at->dataAtendimento, 0, 10);
            if (!empty($at->pessoaId)) {
                $pid = (int)$at->pessoaId;
                if (!isset($ultimoPorPessoa[$pid]) || $dataAt > $ultimoPorPessoa[$pid]) {
                    $ultimoPorPessoa[$pid] = $dataAt;
                }
            }
            if (!empty($at->familiaId)) {
                $fid = (int)$at->familiaId;
                if (!isset($ultimoPorFamilia[$fid]) || $dataAt > $ultimoPorFamilia[$fid]) {
                    $ultimoPorFamilia[$fid] = $dataAt;
                }
            }
        }

        foreach ($todas as $p) {
            if (str_contains($p->nomeCompleto, 'Anonimizado')) {
                continue;
            }

            $ultimoAtendimento = $ultimoPorPessoa[$p->id] ?? null;

            // Considera atendimentos da(s) família(s) às quais a pessoa pertence
            $vinculos = $this->pessoaRepo->getHistoricoVinculosFamiliares($p->id);
            foreach ($vinculos as $v) {
                $dataFam = $ultimoPorFamilia[$v->familiaId] ?? null;
                if ($dataFam && (!$ultimoAtendimento || $dataFam > $ultimoAtendimento)) {
                    $ultimoAtendimento = $dataFam;
                }
            }

            // Nunca usar data de nascimento como proxy de inatividade; usar última movimentação ou data de cadastro
            $dataReferencia = $ultimoAtendimento ?: ($p->dataCadastro ?: null);
            if ($dataReferencia && $dataReferencia < $limite) {
                $ts = strtotime($dataReferencia);
                $anosInativo = $ts ? (int)floor((time() - $ts) / (365 * 86400)) : $anos;
                $inativos[] = [
                    'pessoa' => $p,
                    'ultimoAcesso' => $ultimoAtendimento ?: ('Cadastro em ' . date('d/m/Y', $ts ?: time())),
                    'anosInativo' => max($anos, $anosInativo)
                ];
            }
        }

        return $inativos;
    }

    public function listarSolicitacoes(): array
    {
        if (Database::isConnected()) {
            try {
                $db = Database::getConnection();
                $stmt = $db->query("SELECT * FROM gestao_social.lgpd_solicitacoes ORDER BY data_solicitacao DESC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable) {
                return Session::get(self::SESSION_KEY, []);
            }
        }

        return Session::get(self::SESSION_KEY, []);
    }
}
