<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Produto;
use App\Models\MovimentacaoEstoque;

interface EstoqueRepositoryInterface
{
    /**
     * @return Produto[]
     */
    public function findAllProdutos(array $filters = []): array;

    public function findProdutoById(int $id): ?Produto;

    public function saveProduto(Produto $produto): Produto;

    public function updateProduto(Produto $produto): bool;

    /**
     * @return MovimentacaoEstoque[]
     */
    public function findMovimentacoes(array $filters = []): array;

    public function saveMovimentacao(MovimentacaoEstoque $mov): MovimentacaoEstoque;

    /**
     * @return Produto[]
     */
    public function getProdutosEstoqueBaixo(): array;

    /**
     * @return Produto[]
     */
    public function getProdutosProximoVencimento(int $dias = 30): array;

    public function countProdutosCriticos(): int;

    public function countProdutosVencendo(): int;
}
