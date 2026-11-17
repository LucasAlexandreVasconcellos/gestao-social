<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Models\Doacao;
use App\Models\ItemDoacao;
use App\Models\MovimentacaoEstoque;
use App\Validators\FormValidator;

final class DoacaoService
{
    public function __construct(
        private DoacaoRepositoryInterface $doacaoRepo,
        private EstoqueRepositoryInterface $estoqueRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->doacaoRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?Doacao
    {
        return $this->doacaoRepo->findById($id);
    }

    public function registrar(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('nomeDoador', 'Nome do Doador / Parceiro')
            ->minLength('nomeDoador', 2, 'Nome do Doador')
            ->required('tipoDoador', 'Tipo de Doador')
            ->inList('tipoDoador', ['Pessoa Física', 'Pessoa Jurídica', 'Anônimo'], 'Tipo de Doador')
            ->cpfOrCnpj('documento', 'CPF / CNPJ do Doador', false)
            ->email('email', 'E-mail do Doador', false)
            ->required('tipoDoacao', 'Tipo de Doação')
            ->inList('tipoDoacao', ['Produtos', 'Financeira', 'Serviços/Outros'], 'Tipo de Doação')
            ->required('dataRecebimento', 'Data de Recebimento')
            ->date('dataRecebimento', 'Data de Recebimento')
            ->required('responsavel', 'Responsável pelo Recebimento');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $valorEstimado = null;
        if (!empty($dados['valorEstimado'])) {
            $valorEstimado = FormValidator::normalizeMoneyInput($dados['valorEstimado']);
        }

        $itensModels = [];
        $itensInput = $dados['itens'] ?? [];
        if (trim($dados['tipoDoacao']) === 'Produtos' && !empty($itensInput) && is_array($itensInput)) {
            foreach ($itensInput as $itemData) {
                if (empty($itemData['nomeProduto']) || empty($itemData['quantidade'])) {
                    continue;
                }
                $qtd = (float)str_replace(',', '.', (string)$itemData['quantidade']);
                if ($qtd <= 0) {
                    continue;
                }
                $prodId = !empty($itemData['produtoId']) ? (int)$itemData['produtoId'] : null;
                $itensModels[] = new ItemDoacao(
                    0,
                    0,
                    $prodId,
                    trim($itemData['nomeProduto']),
                    $qtd,
                    trim($itemData['unidadeMedida'] ?? 'UN'),
                    trim($itemData['lote'] ?? '') ?: null,
                    trim($itemData['dataValidade'] ?? '') ?: null
                );
            }
        }

        $doacao = new Doacao(
            0,
            trim($dados['tipoDoador']),
            trim($dados['nomeDoador']),
            trim($dados['documento'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            trim($dados['email'] ?? '') ?: null,
            trim($dados['tipoDoacao']),
            trim($dados['dataRecebimento']),
            $valorEstimado,
            trim($dados['descricao'] ?? ''),
            trim($dados['comprovante'] ?? '') ?: null,
            trim($dados['responsavel']),
            $itensModels
        );

        try {
            $saved = $this->doacaoRepo->save($doacao);
        } catch (\Throwable $e) {
            return ['success' => false, 'errors' => ['geral' => 'Não foi possível registrar a doação: ' . $e->getMessage()]];
        }

        // Em modo Demo em memória, espelha entradas manuais no estoque; em PostgreSQL, a trigger faz atomicamente
        if ($this->isDemoEstoque() && $saved->tipoDoacao === 'Produtos') {
            foreach ($saved->itens as $item) {
                if ($item->produtoId) {
                    $produto = $this->estoqueRepo->findProdutoById($item->produtoId);
                    if ($produto) {
                        $mov = new MovimentacaoEstoque(
                            0,
                            $saved->dataRecebimento,
                            $produto->id,
                            $produto->nome,
                            'Entrada',
                            $item->quantidade,
                            $item->lote,
                            $item->dataValidade,
                            "Doação #{$saved->id} ({$saved->nomeDoador})",
                            "Entrada de estoque originada de doação recebida",
                            $saved->responsavel
                        );
                        $this->estoqueRepo->saveMovimentacao($mov);
                    }
                }
            }
        }

        $this->auditLog->log('Criar', 'Doacao', $saved->id, "Doação recebida de {$saved->nomeDoador} ({$saved->tipoDoacao})");

        return ['success' => true, 'doacao' => $saved];
    }

    private function isDemoEstoque(): bool
    {
        return defined(get_class($this->estoqueRepo) . '::IS_DEMO');
    }
}
