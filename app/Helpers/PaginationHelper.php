<?php

declare(strict_types=1);

namespace App\Helpers;

final class PaginationHelper
{
    /**
     * Pagina uma lista em memória de forma uniforme para todas as listagens e relatórios.
     *
     * @param array<int, mixed> $items
     * @return array{
     *   items: array<int, mixed>,
     *   page: int,
     *   perPage: int,
     *   total: int,
     *   totalPages: int,
     *   from: int,
     *   to: int
     * }
     */
    public static function paginate(array $items, int $page = 1, int $perPage = 15): array
    {
        $items = array_values($items);
        $total = count($items);
        $perPage = max(1, min(100, $perPage));
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($items, $offset, $perPage);

        $from = $total === 0 ? 0 : $offset + 1;
        $to = $total === 0 ? 0 : min($offset + count($slice), $total);

        return [
            'items' => $slice,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'from' => $from,
            'to' => $to,
        ];
    }
}

