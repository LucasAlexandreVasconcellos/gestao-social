<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Contracts\Repositories\ProjetoRepositoryInterface;
use App\Models\ParticipanteProjeto;
use App\Models\ProjetoSocial;
use App\Validators\FormValidator;

final class ProjetoService
{
    public function __construct(
        private ProjetoRepositoryInterface $projetoRepo,
        private FamiliaRepositoryInterface $familiaRepo,
        private PessoaRepositoryInterface $pessoaRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->projetoRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?ProjetoSocial
    {
        return $this->projetoRepo->findById($id);
    }

    public function salvar(array $dados, ?int $id = null): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('nome', 'Nome do Projeto / Ação')
            ->minLength('nome', 3, 'Nome do Projeto')
            ->required('tipo', 'Tipo de Ação')
            ->inList('tipo', ['Campanha', 'Evento', 'Oficina', 'Projeto Social', 'Distribuição Coletiva'], 'Tipo de Ação')
            ->required('publicoAlvo', 'Público-Alvo')
            ->required('dataInicio', 'Data de Início')
            ->date('dataInicio', 'Data de Início')
            ->required('local', 'Local da Ação')
            ->required('responsavel', 'Responsável')
            ->numeric('metaBeneficiarios', 'Meta de Beneficiários');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $orcamentoPrevisto = (float)str_replace(',', '.', (string)($dados['orcamentoPrevisto'] ?? 0));
        $valorRealizado = (float)str_replace(',', '.', (string)($dados['valorRealizado'] ?? 0));
        $meta = (int)($dados['metaBeneficiarios'] ?? 0);

        if ($id !== null && $id > 0) {
            $projeto = $this->projetoRepo->findById($id);
            if (!$projeto) {
                return ['success' => false, 'errors' => ['geral' => 'Projeto não encontrado.']];
            }

            $projeto->nome = trim($dados['nome']);
            $projeto->tipo = trim($dados['tipo']);
            $projeto->publicoAlvo = trim($dados['publicoAlvo']);
            $projeto->dataInicio = trim($dados['dataInicio']);
            $projeto->dataEncerramento = trim($dados['dataEncerramento'] ?? '') ?: null;
            $projeto->local = trim($dados['local']);
            $projeto->responsavel = trim($dados['responsavel']);
            $projeto->orcamentoPrevisto = $orcamentoPrevisto;
            $projeto->valorRealizado = $valorRealizado;
            $projeto->metaBeneficiarios = $meta;
            $projeto->situacao = trim($dados['situacao'] ?? $projeto->situacao);
            $projeto->descricao = trim($dados['descricao'] ?? '') ?: null;

            $this->projetoRepo->update($projeto);
            $this->auditLog->log('Editar', 'ProjetoSocial', $projeto->id, "Atualização do projeto {$projeto->nome}");

            return ['success' => true, 'projeto' => $projeto];
        }

        $projeto = new ProjetoSocial(
            0,
            trim($dados['nome']),
            trim($dados['tipo']),
            trim($dados['publicoAlvo']),
            trim($dados['dataInicio']),
            trim($dados['dataEncerramento'] ?? '') ?: null,
            trim($dados['local']),
            trim($dados['responsavel']),
            $orcamentoPrevisto,
            $valorRealizado,
            $meta,
            trim($dados['situacao'] ?? 'Planejada'),
            trim($dados['descricao'] ?? '') ?: null,
            []
        );

        $saved = $this->projetoRepo->save($projeto);
        $this->auditLog->log('Criar', 'ProjetoSocial', $saved->id, "Criação do projeto {$saved->nome}");

        return ['success' => true, 'projeto' => $saved];
    }

    public function inscreverParticipante(int $projetoId, array $dados): array
    {
        $projeto = $this->projetoRepo->findById($projetoId);
        if (!$projeto) {
            return ['success' => false, 'errors' => ['geral' => 'Projeto não encontrado.']];
        }

        $validator = new FormValidator($dados);
        $validator->required('nomePessoa', 'Nome da Pessoa');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $familiaId = !empty($dados['familiaId']) ? (int)$dados['familiaId'] : null;
        $pessoaId = !empty($dados['pessoaId']) ? (int)$dados['pessoaId'] : null;

        $participante = new ParticipanteProjeto(
            0,
            $projetoId,
            $familiaId,
            $pessoaId,
            trim($dados['nomePessoa']),
            trim($dados['situacaoParticipacao'] ?? 'Inscrito'),
            null,
            trim($dados['observacoes'] ?? '') ?: null
        );

        $saved = $this->projetoRepo->addParticipante($participante);
        $this->auditLog->log('Inscrever Participante', 'ProjetoSocial', $projetoId, "Participante {$saved->nomePessoa} inscrito no projeto {$projeto->nome}");

        return ['success' => true, 'participante' => $saved];
    }

    public function atualizarStatusParticipante(int $participanteId, string $status, ?string $dataPresenca = null): bool
    {
        $participantes = $this->projetoRepo->getParticipantes(0);
        foreach ($participantes as $p) {
            if ($p->id === $participanteId) {
                $p->situacaoParticipacao = $status;
                if ($status === 'Efetivamente Atendido') {
                    $p->dataPresenca = $dataPresenca ?? date('Y-m-d');
                }
                $this->projetoRepo->updateParticipante($p);
                $this->auditLog->log('Atualizar Participante', 'ParticipanteProjeto', $participanteId, "Status de participação alterado para [{$status}]");
                return true;
            }
        }
        return false;
    }
}
