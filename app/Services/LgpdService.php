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
        $nomeOriginal = $pessoa->nomeCompleto;

        // 1. Expurgar documentos anexos se solicitado
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

        // 2. Anonimização estrita dos campos identificadores (Art. 18 LGPD)
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

        // 3. Registrar a solicitação na trilha LGPD
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
                // Fallback em sessão se a tabela ainda não estiver criada
                $solicitacoes = Session::get(self::SESSION_KEY, []);
                $solicitacoes[] = $registro;
                Session::set(self::SESSION_KEY, $solicitacoes);
            }
        } else {
            $solicitacoes = Session::get(self::SESSION_KEY, []);
            $solicitacoes[] = $registro;
            Session::set(self::SESSION_KEY, $solicitacoes);
        }

        // 4. Registro na auditoria geral
        $this->auditLog->log(
            'Anonimização de Titular (LGPD Art. 18)',
            'Pessoa',
            $pessoaId,
            "Dados de '{$nomeOriginal}' anonimizados permanentemente sob o protocolo {$protocolo}. Motivo: {$motivo}."
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
        foreach ($todas as $p) {
            // Ignorar já anonimizados
            if (str_contains($p->nomeCompleto, 'Anonimizado')) {
                continue;
            }

            // Verifica se tem atendimentos
            $atendimentos = $this->atendimentoRepo->findAll();
            $ultimoAtendimento = null;
            foreach ($atendimentos as $at) {
                // Se vinculado a atendimento
                if (isset($at->pessoaId) && (int)$at->pessoaId === $p->id) {
                    if (!$ultimoAtendimento || $at->dataAtendimento > $ultimoAtendimento) {
                        $ultimoAtendimento = $at->dataAtendimento;
                    }
                }
            }

            // Se cadastro é antigo e sem atendimentos recentes
            $dataBase = $ultimoAtendimento ?: $p->dataNascimento;
            if ($dataBase && $dataBase < $limite) {
                $inativos[] = [
                    'pessoa' => $p,
                    'ultimoAcesso' => $ultimoAtendimento ?: 'Sem registros nos últimos ' . $anos . ' anos',
                    'anosInativo' => $dataBase ? (int)((time() - strtotime($dataBase)) / (365 * 86400)) : $anos
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
