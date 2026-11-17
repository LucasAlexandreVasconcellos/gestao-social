<?php
/**
 * Partial de Paginação Padronizada
 * Variáveis esperadas:
 *   $pagination = ['page' => int, 'perPage' => int, 'total' => int, 'totalPages' => int, 'from' => int, 'to' => int]
 *   $basePath   = string (ex: '/familias')
 *   $query      = array (filtros atuais GET)
 */
if (empty($pagination) || ($pagination['totalPages'] ?? 1) <= 1) {
    return;
}

$page = (int)$pagination['page'];
$totalPages = (int)$pagination['totalPages'];
$baseQuery = array_filter($query ?? [], static fn($v) => $v !== null && $v !== '');

$buildUrl = static function (int $targetPage) use ($basePath, $baseQuery): string {
    $params = array_merge($baseQuery, ['page' => $targetPage]);
    return $basePath . '?' . http_build_query($params);
};
?>
<div class="pagination-bar no-print">
  <div class="pagination-info">
    Exibindo <strong class="tabular-nums"><?= (int)$pagination['from'] ?>–<?= (int)$pagination['to'] ?></strong>
    de <strong class="tabular-nums"><?= (int)$pagination['total'] ?></strong> registros
    (Página <?= $page ?> de <?= $totalPages ?>)
  </div>
  <nav class="pagination-links" aria-label="Paginação">
    <a class="page-link <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $e($buildUrl(max(1, $page - 1))) ?>">Anterior</a>
    <?php
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    for ($p = $start; $p <= $end; $p++):
    ?>
      <a class="page-link <?= $p === $page ? 'active' : '' ?>" href="<?= $e($buildUrl($p)) ?>"><?= $p ?></a>
    <?php endfor; ?>
    <a class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $e($buildUrl(min($totalPages, $page + 1))) ?>">Próxima</a>
  </nav>
</div>

