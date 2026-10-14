<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Core\Session;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Simula o controle de estoque de produtos, doações em espécie e saídas de benefícios.
 */
final class EstoqueRepositoryDemo implements EstoqueRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_PROD_KEY = '_demo_produtos';
    private const SESSION_MOV_KEY = '_demo_movimentacoes';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_PROD_KEY)) {
            $initialProdutos = [
                1 => new Produto(1, 'PROD-001', 'Cesta Básica Tipo A (Completa)', 'Alimentos', 'Unidade', 14.0, 20.0, '2026-08-30', true),
                2 => new Produto(2, 'PROD-002', 'Arroz Agulhinha Tipo 1 5kg', 'Alimentos', 'Pacote', 8.0, 25.0, '2026-11-15', true),
                3 => new Produto(3, 'PROD-003', 'Kit de Higiene Familiar', 'Higiene e Limpeza', 'Unidade', 35.0, 15.0, '2027-05-10', true),
                4 => new Produto(4, 'PROD-004', 'Leite em Pó Integral 400g', 'Alimentos', 'Pacote', 6.0, 20.0, '2026-04-10', true), // Próximo ao vencimento e estoque baixo!
                5 => new Produto(5, 'PROD-005', 'Feijão Carioca 1kg', 'Alimentos', 'Pacote', 45.0, 20.0, '2026-10-20', true),
                6 => new Produto(6, 'PROD-006', 'Cobertor / Manta Térmica', 'Vestuário', 'Unidade', 22.0, 10.0, null, true),
                7 => new Produto(7, 'PROD-007', 'Fralda Infantil Tamanho M', 'Higiene e Limpeza', 'Pacote', 12.0, 15.0, '2027-01-15', true)
            ];

            $initialMov = [
                1 => new MovimentacaoEstoque(1, '2026-02-10', 1, 'Cesta Básica Tipo A (Completa)', 'Entrada', 30.0, 'LOTE-2026-01', '2026-08-30', 'Doação Campanha Solidária', 'Recebimento de doações de campanha', 'Robson Almoxarife'),
                2 => new MovimentacaoEstoque(2, '2026-02-28', 1, 'Cesta Básica Tipo A (Completa)', 'Saída', 1.0, 'LOTE-2026-01', null, 'Benefício Família Silva', 'Entrega regular REC-2026-045', 'Robson Almoxarife'),
                3 => new MovimentacaoEstoque(3, '2026-03-01', 4, 'Leite em Pó Integral 400g', 'Entrada', 15.0, 'LOTE-LEI-88', '2026-04-10', 'Supermercado Central Parceiro', 'Doação mensal parceiro corporativo', 'Robson Almoxarife'),
                4 => new MovimentacaoEstoque(4, '2026-03-07', 4, 'Leite em Pó Integral 400g', 'Saída', 2.0, 'LOTE-LEI-88', null, 'Benefício Família Santos', 'Entrega emergencial autorizada', 'Robson Almoxarife')
            ];

            Session::set(self::SESSION_PROD_KEY, $initialProdutos);
            Session::set(self::SESSION_MOV_KEY, $initialMov);
        }
    }

    public function findAllProdutos(array $filters = []): array
    {
        $produtos = Session::get(self::SESSION_PROD_KEY, []);

        if (!empty($filters['search'])) {
            $term = mb_strtolower(trim($filters['search']), 'UTF-8');
            $produtos = array_filter($produtos, function (Produto $p) use ($term) {
                return str_contains(mb_strtolower($p->nome, 'UTF-8'), $term) ||
                       str_contains(mb_strtolower($p->codigo, 'UTF-8'), $term) ||
                       str_contains(mb_strtolower($p->categoria, 'UTF-8'), $term);
            });
        }

        if (!empty($filters['categoria'])) {
            $cat = $filters['categoria'];
            $produtos = array_filter($produtos, fn(Produto $p) => $p->categoria === $cat);
        }

        if (!empty($filters['estoqueBaixo'])) {
            $produtos = array_filter($produtos, fn(Produto $p) => $p->isEstoqueBaixo());
        }

        return array_values($produtos);
    }

    public function findProdutoById(int $id): ?Produto
    {
        $produtos = Session::get(self::SESSION_PROD_KEY, []);
        return $produtos[$id] ?? null;
    }

    public function saveProduto(Produto $produto): Produto
    {
        $produtos = Session::get(self::SESSION_PROD_KEY, []);
        if ($produto->id <= 0) {
            $maxId = empty($produtos) ? 0 : max(array_keys($produtos));
            $produto->id = $maxId + 1;
            if (empty($produto->codigo)) {
                $produto->codigo = sprintf('PROD-%03d', $produto->id);
            }
        }

        $produtos[$produto->id] = $produto;
        Session::set(self::SESSION_PROD_KEY, $produtos);
        return $produto;
    }

    public function updateProduto(Produto $produto): bool
    {
        $produtos = Session::get(self::SESSION_PROD_KEY, []);
        if (!isset($produtos[$produto->id])) {
            return false;
        }

        $produtos[$produto->id] = $produto;
        Session::set(self::SESSION_PROD_KEY, $produtos);
        return true;
    }

    public function findMovimentacoes(array $filters = []): array
    {
        $movs = Session::get(self::SESSION_MOV_KEY, []);

        if (!empty($filters['produtoId'])) {
            $prodId = (int)$filters['produtoId'];
            $movs = array_filter($movs, fn(MovimentacaoEstoque $m) => $m->produtoId === $prodId);
        }

        if (!empty($filters['tipo'])) {
            $tipo = $filters['tipo'];
            $movs = array_filter($movs, fn(MovimentacaoEstoque $m) => $m->tipo === $tipo);
        }

        usort($movs, fn(MovimentacaoEstoque $a, MovimentacaoEstoque $b) => strcmp($b->data, $a->data));

        return array_values($movs);
    }

    public function saveMovimentacao(MovimentacaoEstoque $mov): MovimentacaoEstoque
    {
        $movs = Session::get(self::SESSION_MOV_KEY, []);
        if ($mov->id <= 0) {
            $maxId = empty($movs) ? 0 : max(array_keys($movs));
            $mov->id = $maxId + 1;
        }

        $movs[$mov->id] = $mov;
        Session::set(self::SESSION_MOV_KEY, $movs);

        // Atualização em memória do estoque do produto correspondente
        $produto = $this->findProdutoById($mov->produtoId);
        if ($produto) {
            if ($mov->tipo === 'Entrada') {
                $produto->estoqueAtual += $mov->quantidade;
            } elseif ($mov->tipo === 'Saída' || $mov->tipo === 'Perda') {
                $produto->estoqueAtual = max(0.0, $produto->estoqueAtual - $mov->quantidade);
            } elseif ($mov->tipo === 'Ajuste') {
                $produto->estoqueAtual = $mov->quantidade;
            }
            if (!empty($mov->dataValidade)) {
                $produto->proximoVencimento = $mov->dataValidade;
            }
            $this->updateProduto($produto);
        }

        return $mov;
    }

    public function getProdutosEstoqueBaixo(): array
    {
        return $this->findAllProdutos(['estoqueBaixo' => true]);
    }

    public function getProdutosProximoVencimento(int $dias = 30): array
    {
        $produtos = Session::get(self::SESSION_PROD_KEY, []);
        $limiteTimestamp = time() + ($dias * 86400);

        $vencendo = array_filter($produtos, function (Produto $p) use ($limiteTimestamp) {
            if (empty($p->proximoVencimento)) {
                return false;
            }
            $vencTimestamp = strtotime($p->proximoVencimento);
            return $vencTimestamp && $vencTimestamp <= $limiteTimestamp;
        });

        return array_values($vencendo);
    }

    public function countProdutosCriticos(): int
    {
        return count($this->getProdutosEstoqueBaixo());
    }

    public function countProdutosVencendo(): int
    {
        return count($this->getProdutosProximoVencimento());
    }
}
