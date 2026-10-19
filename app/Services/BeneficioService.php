<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Models\BeneficioEntrega;
use App\Models\ItemEntrega;
use App\Models\MovimentacaoEstoque;
use App\Validators\FormValidator;

final class BeneficioService
{
    public function __construct(
        private BeneficioRepositoryInterface $beneficioRepo,
        private EstoqueRepositoryInterface $estoqueRepo,
        private FamiliaRepositoryInterface $familiaRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->beneficioRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?BeneficioEntrega
    {
        return $this->beneficioRepo->findById($id);
    }

    public function registrarEntrega(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('familiaId', 'Família Beneficiada')
            ->numeric('familiaId', 'Família Beneficiada')
            ->required('tipoBeneficio', 'Tipo de Benefício')
            ->required('dataEntrega', 'Data da Entrega')
            ->date('dataEntrega', 'Data da Entrega')
            ->required('responsavelDistribuicao', 'Responsável pela Distribuição')
            ->inList('situacao', ['Preparada', 'Efetivada'], 'Situação da Entrega');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $familiaId = (int)$dados['familiaId'];
        $familia = $this->familiaRepo->findById($familiaId);
        if (!$familia) {
            return ['success' => false, 'errors' => ['familiaId' => 'Família não encontrada.']];
        }

        // Validação estrita: Não permitir confirmar uma entrega efetivada sem itens e quantidades
        $itensInput = $dados['itens'] ?? [];
        $situacao = $dados['situacao'] ?? 'Efetivada';

        if ($situacao === 'Efetivada' && empty($itensInput)) {
            return [
                'success' => false,
                'errors' => ['itens' => 'Não é permitido efetivar uma entrega sem itens e quantidades correspondentes cadastrados.']
            ];
        }

        $campanhaId = !empty($dados['campanhaId']) ? (int)$dados['campanhaId'] : null;

        $entrega = new BeneficioEntrega(
            0,
            $familiaId,
            trim($dados['dataEntrega']),
            trim($dados['tipoBeneficio']),
            trim($dados['responsavelDistribuicao']),
            $campanhaId,
            [],
            trim($dados['comprovante'] ?? '') ?: null,
            trim($dados['observacoes'] ?? '') ?: null,
            $situacao
        );

        $savedEntrega = $this->beneficioRepo->save($entrega);

        // Processa os itens
        if (!empty($itensInput) && is_array($itensInput)) {
            foreach ($itensInput as $itemData) {
                if (empty($itemData['produtoId']) || empty($itemData['quantidade'])) {
                    continue;
                }

                $produtoId = (int)$itemData['produtoId'];
                $quantidade = (float)str_replace(',', '.', (string)$itemData['quantidade']);
                if ($quantidade <= 0) {
                    continue;
                }

                $produto = $this->estoqueRepo->findProdutoById($produtoId);
                $nomeProduto = $produto ? $produto->nome : 'Item Diverso';
                $unidade = $produto ? $produto->unidadeMedida : 'Unidade';

                $item = new ItemEntrega(
                    0,
                    $savedEntrega->id,
                    $produtoId,
                    $nomeProduto,
                    $quantidade,
                    $unidade,
                    trim($itemData['lote'] ?? '') ?: null
                );
                $this->beneficioRepo->addItem($item);

                // Se a entrega foi efetivada, simula em memória a baixa do estoque
                // NOTA DE ARQUITETURA: Na futura integração com banco de dados,
                // a gravação da entrega e a baixa do estoque DEVEM ocorrer dentro de uma transação atômica (BEGIN TRANSACTION ... COMMIT).
                if ($this->isDemoEstoque() && $situacao === 'Efetivada' && $produto) {
                    $mov = new MovimentacaoEstoque(
                        0,
                        $savedEntrega->dataEntrega,
                        $produto->id,
                        $produto->nome,
                        'Saída',
                        $quantidade,
                        $item->lote,
                        null,
                        "Família {$familia->codigo} ({$familia->pessoaReferenciaNome})",
                        "Entrega de benefício assistencial ID #{$savedEntrega->id}",
                        $savedEntrega->responsavelDistribuicao
                    );
                    $this->estoqueRepo->saveMovimentacao($mov);
                }
            }
        }

        $this->auditLog->log(
            'Criar',
            'BeneficioEntrega',
            $savedEntrega->id,
            "Entrega de {$savedEntrega->tipoBeneficio} para família {$familia->codigo} [Situação: {$situacao}]"
        );

        return ['success' => true, 'entrega' => $savedEntrega];
    }

    public function efetivarEntrega(int $entregaId): array
    {
        $entrega = $this->beneficioRepo->findById($entregaId);
        if (!$entrega) {
            return ['success' => false, 'error' => 'Entrega não encontrada.'];
        }

        if ($entrega->situacao === 'Efetivada') {
            return ['success' => false, 'error' => 'Esta entrega já se encontra efetivada.'];
        }

        if (empty($entrega->itens)) {
            return ['success' => false, 'error' => 'Não é permitido efetivar uma entrega sem itens cadastrados.'];
        }

        $familia = $this->familiaRepo->findById($entrega->familiaId);
        $entrega->situacao = 'Efetivada';
        $this->beneficioRepo->update($entrega);

        // Realiza baixa de estoque manual apenas no modo Demo.
        // No PostgreSQL, a trigger trg_ciclo_status_entrega_estoque faz a baixa automaticamente.
        if ($this->isDemoEstoque()) {
            foreach ($entrega->itens as $item) {
                $mov = new MovimentacaoEstoque(
                    0,
                    date('Y-m-d'),
                    $item->produtoId,
                    $item->nomeProduto,
                    'Saída',
                    $item->quantidade,
                    $item->lote,
                    null,
                    $familia ? "Família {$familia->codigo}" : "Família #{$entrega->familiaId}",
                    "Efetivação de entrega de benefício #{$entrega->id}",
                    $entrega->responsavelDistribuicao
                );
                $this->estoqueRepo->saveMovimentacao($mov);
            }
        }

        $this->auditLog->log('Efetivar Entrega', 'BeneficioEntrega', $entrega->id, "Entrega #{$entrega->id} efetivada.");

        return ['success' => true, 'entrega' => $entrega];
    }

    private function isDemoEstoque(): bool
    {
        return defined(get_class($this->estoqueRepo) . '::IS_DEMO');
    }

    public function cancelarEntrega(int $entregaId, string $motivo): array
    {
        $entrega = $this->beneficioRepo->findById($entregaId);
        if (!$entrega) {
            return ['success' => false, 'error' => 'Entrega não encontrada.'];
        }

        $entrega->situacao = 'Cancelada';
        $entrega->observacoes = trim($entrega->observacoes . " | Cancelamento: {$motivo}");
        $this->beneficioRepo->update($entrega);

        $this->auditLog->log('Cancelar Entrega', 'BeneficioEntrega', $entrega->id, "Entrega cancelada. Motivo: {$motivo}");

        return ['success' => true, 'entrega' => $entrega];
    }
}
