<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Core\Session;
use App\Models\Doacao;
use App\Models\ItemDoacao;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Simula o cadastro de doações de pessoas físicas, jurídicas e parceiros.
 */
final class DoacaoRepositoryDemo implements DoacaoRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_doacoes';
    private const SESSION_ITENS_KEY = '_demo_itens_doacao';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $item1 = new ItemDoacao(1, 1, 1, 'Cesta Básica Tipo A (Completa)', 30.0, 'Unidade', 'LOTE-2026-01', '2026-08-30');
            $item2 = new ItemDoacao(2, 2, 4, 'Leite em Pó Integral 400g', 15.0, 'Pacote', 'LOTE-LEI-88', '2026-04-10');

            $initial = [
                1 => new Doacao(
                    1, 'Pessoa Jurídica', 'Supermercados Esperança Ltda', '12.345.678/0001-90',
                    '1133334444', 'contato@superesperanca.com.br', 'Produtos', '2026-02-10',
                    4500.00, 'Doação solidária para início das atividades do ano.', 'COMP-2026-012',
                    'Fernanda Coordenadora', [$item1]
                ),
                2 => new Doacao(
                    2, 'Pessoa Jurídica', 'Distribuidora Central de Alimentos', '98.765.432/0001-11',
                    '1144445555', 'doacoes@distcentral.com.br', 'Produtos', '2026-03-01',
                    350.00, 'Doação de lote de leite em pó com validade próxima.', 'COMP-2026-034',
                    'Fernanda Coordenadora', [$item2]
                ),
                3 => new Doacao(
                    3, 'Pessoa Física', 'Dr. Roberto Magalhães', '555.666.777-88',
                    '11988887777', 'roberto.magalhaes@email.com', 'Financeira', '2026-03-05',
                    1500.00, 'Doação financeira via PIX para manutenção do programa alimentar.', 'PIX-E92348102',
                    'Fernanda Coordenadora', []
                ),
                4 => new Doacao(
                    4, 'Pessoa Física', 'Anônimo', null,
                    null, null, 'Financeira', '2026-03-10',
                    500.00, 'Doação anônima para apoio emergencial.', 'REC-ANON-05',
                    'Fernanda Coordenadora', []
                )
            ];

            Session::set(self::SESSION_KEY, $initial);
            Session::set(self::SESSION_ITENS_KEY, [$item1, $item2]);
        }
    }

    public function findAll(array $filters = []): array
    {
        $doacoes = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['tipo'])) {
            $tipo = $filters['tipo'];
            $doacoes = array_filter($doacoes, fn(Doacao $d) => $d->tipoDoacao === $tipo);
        }

        if (!empty($filters['search'])) {
            $term = mb_strtolower(trim($filters['search']), 'UTF-8');
            $doacoes = array_filter($doacoes, function (Doacao $d) use ($term) {
                return str_contains(mb_strtolower($d->nomeDoador, 'UTF-8'), $term) ||
                       str_contains(mb_strtolower($d->descricao, 'UTF-8'), $term);
            });
        }

        usort($doacoes, fn(Doacao $a, Doacao $b) => strcmp($b->dataRecebimento, $a->dataRecebimento));

        return array_values($doacoes);
    }

    public function findById(int $id): ?Doacao
    {
        $doacoes = Session::get(self::SESSION_KEY, []);
        return $doacoes[$id] ?? null;
    }

    public function save(Doacao $doacao): Doacao
    {
        $doacoes = Session::get(self::SESSION_KEY, []);
        if ($doacao->id <= 0) {
            $maxId = empty($doacoes) ? 0 : max(array_keys($doacoes));
            $doacao->id = $maxId + 1;
        }

        $doacoes[$doacao->id] = $doacao;
        Session::set(self::SESSION_KEY, $doacoes);
        return $doacao;
    }

    public function addItem(ItemDoacao $item): ItemDoacao
    {
        $itens = Session::get(self::SESSION_ITENS_KEY, []);
        if ($item->id <= 0) {
            $maxId = empty($itens) ? 0 : max(array_map(fn($it) => $it->id, $itens));
            $item->id = $maxId + 1;
        }
        $itens[] = $item;
        Session::set(self::SESSION_ITENS_KEY, $itens);

        $doacao = $this->findById($item->doacaoId);
        if ($doacao) {
            $doacao->itens[] = $item;
            $this->save($doacao);
        }

        return $item;
    }

    public function sumValorFinanceiro(string $mesAno = ''): float
    {
        $doacoes = Session::get(self::SESSION_KEY, []);
        $total = 0.0;
        foreach ($doacoes as $d) {
            if ($d->tipoDoacao === 'Financeira' && $d->valorEstimado !== null) {
                if (empty($mesAno) || str_starts_with($d->dataRecebimento, $mesAno)) {
                    $total += $d->valorEstimado;
                }
            }
        }
        return $total;
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }
}
