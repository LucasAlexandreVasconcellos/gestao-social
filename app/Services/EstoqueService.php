<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Validators\FormValidator;

final class EstoqueService
{
    public function __construct(
        private EstoqueRepositoryInterface $estoqueRepo,
        private AuditLogService $auditLog
    ) {}

    public function listarProdutos(array $filters = []): array
    {
        return $this->estoqueRepo->findAllProdutos($filters);
    }

    public function obterProdutoPorId(int $id): ?Produto
    {
        return $this->estoqueRepo->findProdutoById($id);
    }

    public function salvarProduto(array $dados, ?int $id = null): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('nome', 'Nome do Produto')
            ->minLength('nome', 3, 'Nome do Produto')
            ->required('categoria', 'Categoria')
            ->required('unidadeMedida', 'Unidade de Medida')
            ->numeric('estoqueMinimo', 'Estoque Mínimo');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $minimo = (float)str_replace(',', '.', (string)($dados['estoqueMinimo'] ?? 0));

        if ($id !== null && $id > 0) {
            $produto = $this->estoqueRepo->findProdutoById($id);
            if (!$produto) {
                return ['success' => false, 'errors' => ['geral' => 'Produto não encontrado.']];
            }

            $produto->nome = trim($dados['nome']);
            $produto->categoria = trim($dados['categoria']);
            $produto->unidadeMedida = trim($dados['unidadeMedida']);
            $produto->estoqueMinimo = $minimo;
            if (!empty($dados['proximoVencimento'])) {
                $produto->proximoVencimento = trim($dados['proximoVencimento']);
            }

            $this->estoqueRepo->updateProduto($produto);
            $this->auditLog->log('Editar', 'Produto', $produto->id, "Atualização do produto {$produto->nome}");

            return ['success' => true, 'produto' => $produto];
        }

        $estoqueInicial = (float)str_replace(',', '.', (string)($dados['estoqueAtual'] ?? 0));

        $produto = new Produto(
            0,
            trim($dados['codigo'] ?? ''),
            trim($dados['nome']),
            trim($dados['categoria']),
            trim($dados['unidadeMedida']),
            $estoqueInicial,
            $minimo,
            trim($dados['proximoVencimento'] ?? '') ?: null,
            true
        );

        $saved = $this->estoqueRepo->saveProduto($produto);
        $this->auditLog->log('Criar', 'Produto', $saved->id, "Cadastro do produto {$saved->nome}");

        return ['success' => true, 'produto' => $saved];
    }

    public function registrarMovimentacao(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('produtoId', 'Produto')
            ->numeric('produtoId', 'Produto')
            ->required('tipo', 'Tipo de Movimentação')
            ->inList('tipo', ['Entrada', 'Saída', 'Ajuste', 'Perda'], 'Tipo de Movimentação')
            ->required('quantidade', 'Quantidade')
            ->numeric('quantidade', 'Quantidade')
            ->min('quantidade', 0.01, 'Quantidade')
            ->required('responsavel', 'Responsável')
            ->required('justificativa', 'Justificativa');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $prodId = (int)$dados['produtoId'];
        $produto = $this->estoqueRepo->findProdutoById($prodId);
        if (!$produto) {
            return ['success' => false, 'errors' => ['produtoId' => 'Produto não encontrado.']];
        }

        $qtd = (float)str_replace(',', '.', (string)$dados['quantidade']);
        $tipo = trim($dados['tipo']);

        // Se for saída e quantidade for maior que estoque atual
        if ($tipo === 'Saída' && $qtd > $produto->estoqueAtual) {
            return [
                'success' => false,
                'errors' => ['quantidade' => "Quantidade insuficiente em estoque. Saldo disponível: {$produto->estoqueAtual} {$produto->unidadeMedida}."]
            ];
        }

        $mov = new MovimentacaoEstoque(
            0,
            trim($dados['data'] ?? date('Y-m-d')),
            $prodId,
            $produto->nome,
            $tipo,
            $qtd,
            trim($dados['lote'] ?? '') ?: null,
            trim($dados['dataValidade'] ?? '') ?: null,
            trim($dados['origemDestino'] ?? '') ?: null,
            trim($dados['justificativa']),
            trim($dados['responsavel'])
        );

        $saved = $this->estoqueRepo->saveMovimentacao($mov);

        $this->auditLog->log(
            'Movimentar Estoque',
            'Produto',
            $produto->id,
            "{$tipo} de {$qtd} {$produto->unidadeMedida} para o produto {$produto->nome}"
        );

        return ['success' => true, 'movimentacao' => $saved];
    }

    public function listarMovimentacoes(array $filters = []): array
    {
        return $this->estoqueRepo->findMovimentacoes($filters);
    }

    public function obterProdutosCriticos(): array
    {
        return $this->estoqueRepo->getProdutosEstoqueBaixo();
    }

    public function obterProdutosVencendo(int $dias = 30): array
    {
        return $this->estoqueRepo->getProdutosProximoVencimento($dias);
    }
}
