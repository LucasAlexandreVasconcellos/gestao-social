<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Core\Session;
use App\Models\BeneficioEntrega;
use App\Models\ItemEntrega;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Simula a entrega e distribuição de benefícios assistenciais.
 */
final class BeneficioRepositoryDemo implements BeneficioRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_beneficios';
    private const SESSION_ITENS_KEY = '_demo_itens_entrega';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $item1 = new ItemEntrega(1, 1, 1, 'Cesta Básica Tipo A (Completa)', 1, 'Unidade', 'LOTE-2026-01');
            $item2 = new ItemEntrega(2, 1, 3, 'Kit de Higiene Familiar', 1, 'Unidade', 'LOTE-HIG-02');

            $item3 = new ItemEntrega(3, 2, 1, 'Cesta Básica Tipo A (Completa)', 1, 'Unidade', 'LOTE-2026-02');

            $item4 = new ItemEntrega(4, 3, 1, 'Cesta Básica Tipo A (Completa)', 1, 'Unidade', 'LOTE-2026-03');
            $item5 = new ItemEntrega(5, 3, 4, 'Leite em Pó Integral 400g', 2, 'Pacote', 'LOTE-LEI-88');

            $initial = [
                1 => new BeneficioEntrega(
                    1, 1, '2026-01-25', 'Cesta Básica', 'Robson Almoxarife', 1,
                    [$item1, $item2], 'REC-2026-001', 'Entrega mensal regular realizada na sede.', 'Efetivada'
                ),
                2 => new BeneficioEntrega(
                    2, 1, '2026-02-28', 'Cesta Básica', 'Robson Almoxarife', 1,
                    [$item3], 'REC-2026-045', 'Entrega mensal regular. Assinatura coletada no ato.', 'Efetivada'
                ),
                3 => new BeneficioEntrega(
                    3, 3, '2026-03-07', 'Cesta Básica + Kit Nutricional', 'Robson Almoxarife', null,
                    [$item4, $item5], 'REC-2026-078', 'Entrega emergencial autorizada pela Assistente Social.', 'Efetivada'
                ),
                4 => new BeneficioEntrega(
                    4, 5, '2026-03-12', 'Kit Higiene + Alimentos', 'Robson Almoxarife', null,
                    [], null, 'Entrega agendada para retirada na próxima sexta-feira.', 'Preparada'
                )
            ];

            Session::set(self::SESSION_KEY, $initial);
            Session::set(self::SESSION_ITENS_KEY, [$item1, $item2, $item3, $item4, $item5]);
        }
    }

    public function findAll(array $filters = []): array
    {
        $this->ensureSeedData();
        $beneficios = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['familiaId'])) {
            $famId = (int)$filters['familiaId'];
            $beneficios = array_filter($beneficios, fn(BeneficioEntrega $b) => $b->familiaId === $famId);
        }

        if (!empty($filters['situacao'])) {
            $situacao = $filters['situacao'];
            $beneficios = array_filter($beneficios, fn(BeneficioEntrega $b) => $b->situacao === $situacao);
        }

        if (!empty($filters['tipo'])) {
            $tipo = $filters['tipo'];
            $beneficios = array_filter($beneficios, fn(BeneficioEntrega $b) => $b->tipoBeneficio === $tipo);
        }

        // Ordenar por data decrescente
        usort($beneficios, fn(BeneficioEntrega $a, BeneficioEntrega $b) => strcmp($b->dataEntrega, $a->dataEntrega));

        return array_values($beneficios);
    }

    public function findById(int $id): ?BeneficioEntrega
    {
        $this->ensureSeedData();
        $beneficios = Session::get(self::SESSION_KEY, []);
        return $beneficios[$id] ?? null;
    }

    public function findByFamiliaId(int $familiaId): array
    {
        $this->ensureSeedData();
        return $this->findAll(['familiaId' => $familiaId]);
    }

    public function save(BeneficioEntrega $entrega): BeneficioEntrega
    {
        $beneficios = Session::get(self::SESSION_KEY, []);
        if ($entrega->id <= 0) {
            $maxId = empty($beneficios) ? 0 : max(array_keys($beneficios));
            $entrega->id = $maxId + 1;
        }

        $beneficios[$entrega->id] = $entrega;
        Session::set(self::SESSION_KEY, $beneficios);
        return $entrega;
    }

    public function update(BeneficioEntrega $entrega): bool
    {
        $beneficios = Session::get(self::SESSION_KEY, []);
        if (!isset($beneficios[$entrega->id])) {
            return false;
        }

        $beneficios[$entrega->id] = $entrega;
        Session::set(self::SESSION_KEY, $beneficios);
        return true;
    }

    public function addItem(ItemEntrega $item): ItemEntrega
    {
        $itens = Session::get(self::SESSION_ITENS_KEY, []);
        if ($item->id <= 0) {
            $maxId = empty($itens) ? 0 : max(array_map(fn($it) => $it->id, $itens));
            $item->id = $maxId + 1;
        }
        $itens[] = $item;
        Session::set(self::SESSION_ITENS_KEY, $itens);

        $entrega = $this->findById($item->entregaId);
        if ($entrega) {
            $entrega->itens[] = $item;
            $this->update($entrega);
        }

        return $item;
    }

    public function countEfetivadas(string $mesAno = ''): int
    {
        $beneficios = Session::get(self::SESSION_KEY, []);
        $count = 0;
        foreach ($beneficios as $b) {
            if ($b->situacao === 'Efetivada') {
                if (empty($mesAno) || str_starts_with($b->dataEntrega, $mesAno)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }
}
