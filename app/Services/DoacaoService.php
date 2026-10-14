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
            $valorEstimado = (float)str_replace(',', '.', (string)$dados['valorEstimado']);
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
            []
        );

        $saved = $this->doacaoRepo->save($doacao);

        // Se a doação for de produtos e houver itens informados
        $itensInput = $dados['itens'] ?? [];
        if ($saved->tipoDoacao === 'Produtos' && !empty($itensInput) && is_array($itensInput)) {
            foreach ($itensInput as $itemData) {
                if (empty($itemData['nomeProduto']) || empty($itemData['quantidade'])) {
                    continue;
                }

                $qtd = (float)str_replace(',', '.', (string)$itemData['quantidade']);
                if ($qtd <= 0) {
                    continue;
                }

                $prodId = !empty($itemData['produtoId']) ? (int)$itemData['produtoId'] : null;

                $item = new ItemDoacao(
                    0,
                    $saved->id,
                    $prodId,
                    trim($itemData['nomeProduto']),
                    $qtd,
                    trim($itemData['unidadeMedida'] ?? 'Unidade'),
                    trim($itemData['lote'] ?? '') ?: null,
                    trim($itemData['dataValidade'] ?? '') ?: null
                );

                $this->doacaoRepo->addItem($item);

                // Na simulação em memória, se o produto já existir no estoque, realiza entrada automática
                // NOTA: Na futura integração com banco de dados, a doação de produtos gerará registro de entrada no estoque
                if ($prodId) {
                    $produto = $this->estoqueRepo->findProdutoById($prodId);
                    if ($produto) {
                        $mov = new MovimentacaoEstoque(
                            0,
                            $saved->dataRecebimento,
                            $produto->id,
                            $produto->nome,
                            'Entrada',
                            $qtd,
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
}
