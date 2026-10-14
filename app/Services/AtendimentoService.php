<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Models\Atendimento;
use App\Models\Encaminhamento;
use App\Validators\FormValidator;

final class AtendimentoService
{
    public function __construct(
        private AtendimentoRepositoryInterface $atendimentoRepo,
        private FamiliaRepositoryInterface $familiaRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->atendimentoRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?Atendimento
    {
        return $this->atendimentoRepo->findById($id);
    }

    public function registrar(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('familiaId', 'Família')
            ->numeric('familiaId', 'Família')
            ->required('tipo', 'Tipo de Atendimento')
            ->inList('tipo', ['Atendimento Social', 'Atendimento Emergencial', 'Orientação', 'Visita Domiciliar'], 'Tipo de Atendimento')
            ->required('dataAtendimento', 'Data do Atendimento')
            ->date('dataAtendimento', 'Data do Atendimento')
            ->required('profissionalResponsavel', 'Profissional Responsável')
            ->required('descricao', 'Descrição do Atendimento')
            ->minLength('descricao', 5, 'Descrição do Atendimento');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $familiaId = (int)$dados['familiaId'];
        $familia = $this->familiaRepo->findById($familiaId);
        if (!$familia) {
            return ['success' => false, 'errors' => ['familiaId' => 'Família não encontrada.']];
        }

        $pessoaId = !empty($dados['pessoaId']) ? (int)$dados['pessoaId'] : null;

        $atendimento = new Atendimento(
            0,
            $familiaId,
            $pessoaId,
            trim($dados['tipo']),
            trim($dados['dataAtendimento']),
            trim($dados['profissionalResponsavel']),
            trim($dados['descricao']),
            trim($dados['providencias'] ?? ''),
            trim($dados['retornoPrevisto'] ?? '') ?: null,
            trim($dados['situacao'] ?? 'Concluído'),
            $dados['situacao'] === 'Concluído' ? date('Y-m-d') : null,
            []
        );

        $saved = $this->atendimentoRepo->save($atendimento);

        // Se houver encaminhamento inicial preenchido
        if (!empty($dados['encaminhamentoDestino']) && !empty($dados['encaminhamentoMotivo'])) {
            $enc = new Encaminhamento(
                0,
                $saved->id,
                trim($dados['encaminhamentoDestino']),
                trim($dados['encaminhamentoMotivo']),
                $saved->profissionalResponsavel,
                $saved->dataAtendimento,
                'Pendente',
                null
            );
            $this->atendimentoRepo->addEncaminhamento($enc);
        }

        $this->auditLog->log('Criar', 'Atendimento', $saved->id, "Registrado {$saved->tipo} para família {$familia->codigo}");

        return ['success' => true, 'atendimento' => $saved];
    }

    public function adicionarEncaminhamento(int $atendimentoId, array $dados): array
    {
        $atendimento = $this->atendimentoRepo->findById($atendimentoId);
        if (!$atendimento) {
            return ['success' => false, 'errors' => ['geral' => 'Atendimento não encontrado.']];
        }

        $validator = new FormValidator($dados);
        $validator
            ->required('servicoDestino', 'Serviço de Destino')
            ->required('motivo', 'Motivo do Encaminhamento')
            ->required('responsavel', 'Responsável pelo Encaminhamento')
            ->required('data', 'Data do Encaminhamento');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $enc = new Encaminhamento(
            0,
            $atendimentoId,
            trim($dados['servicoDestino']),
            trim($dados['motivo']),
            trim($dados['responsavel']),
            trim($dados['data']),
            trim($dados['situacao'] ?? 'Pendente'),
            trim($dados['resultado'] ?? '') ?: null
        );

        $saved = $this->atendimentoRepo->addEncaminhamento($enc);

        $this->auditLog->log('Encaminhamento', 'Atendimento', $atendimentoId, "Encaminhamento para {$saved->servicoDestino}: {$saved->motivo}");

        return ['success' => true, 'encaminhamento' => $saved];
    }
}
