<?php

declare(strict_types=1);

/**
 * Higienização de Base para Início de Operação Oficial em Produção
 *
 * Remove todos os registros fictícios/demonstrativos inseridos em 03_seed_data.sql,
 * preservando apenas:
 *   - Perfis RBAC oficiais (gestao_social.perfis)
 *   - Conta administrativa principal (mantendo a senha atual definida)
 *   - Catálogos de referência SUAS (tipos_carencia e categorias_produtos)
 *   - Todas as views, triggers de auditoria e regras de menor privilégio
 *
 * Uso:
 *   php scripts/reset_for_production.php <senha_postgres> [usuario_admin=postgres] [porta=5432] [banco=gestao-social] [host=127.0.0.1]
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Env.php';
\App\Core\Env::load($root . '/.env');

$adminPass = $argv[1] ?? getenv('DB_ADMIN_PASSWORD') ?: '';
if ($adminPass === '') {
    fwrite(STDERR, "ERRO: Informe a senha do administrador PostgreSQL.\n");
    fwrite(STDERR, "Uso: php scripts/reset_for_production.php <senha_postgres> [usuario=postgres] [porta=5432] [banco=gestao-social] [host=127.0.0.1]\n");
    exit(1);
}

$adminUser = $argv[2] ?? 'postgres';
$port = (int)($argv[3] ?? \App\Core\Env::get('DB_PORT', 5432));
$dbName = $argv[4] ?? \App\Core\Env::get('DB_DATABASE', 'gestao-social');
$host = $argv[5] ?? \App\Core\Env::get('DB_HOST', '127.0.0.1');

$pdo = new PDO(
    "pgsql:host={$host};port={$port};dbname={$dbName};options='--client_encoding=utf8'",
    $adminUser,
    $adminPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tablesToTruncate = [
    'gestao_social.documentos',
    'gestao_social.notificacoes',
    'gestao_social.lgpd_solicitacoes',
    'gestao_social.entrega_itens',
    'gestao_social.entregas',
    'gestao_social.acao_beneficiarios',
    'gestao_social.acoes_sociais',
    'gestao_social.encaminhamentos',
    'gestao_social.atendimentos',
    'gestao_social.avaliacao_carencias',
    'gestao_social.avaliacoes_sociais',
    'gestao_social.familia_integrantes',
    'gestao_social.familias',
    'gestao_social.pessoas',
    'gestao_social.doacao_itens',
    'gestao_social.doacoes',
    'gestao_social.doadores',
    'gestao_social.movimentacoes_estoque',
    'gestao_social.lotes_produto',
    'gestao_social.produtos',
    'gestao_social.auditoria',
];

$allOperationalTables = array_merge($tablesToTruncate, ['gestao_social.usuarios']);

$pdo->beginTransaction();
try {
    foreach ($allOperationalTables as $tbl) {
        $pdo->exec("ALTER TABLE {$tbl} DISABLE TRIGGER USER");
    }

    $pdo->exec('TRUNCATE TABLE ' . implode(', ', $tablesToTruncate) . ' RESTART IDENTITY CASCADE');

    // Mantém apenas a conta administrativa principal (preservando senha e 2FA atuais)
    $pdo->exec("DELETE FROM gestao_social.usuarios WHERE login <> 'admin'");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('gestao_social.usuarios', 'id'), COALESCE((SELECT MAX(id) FROM gestao_social.usuarios), 1))");

    foreach ($allOperationalTables as $tbl) {
        $pdo->exec("ALTER TABLE {$tbl} ENABLE TRIGGER USER");
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "ERRO durante higienização: " . $e->getMessage() . "\n");
    exit(1);
}

// Limpa anexos demonstrativos mantendo .htaccess e .gitkeep
$docsDir = $root . '/storage/documentos';
$filesRemoved = 0;
if (is_dir($docsDir)) {
    foreach (scandir($docsDir) ?: [] as $item) {
        if ($item === '.' || $item === '..' || $item === '.htaccess' || $item === '.gitkeep') {
            continue;
        }
        $fullPath = $docsDir . DIRECTORY_SEPARATOR . $item;
        if (is_file($fullPath) && @unlink($fullPath)) {
            $filesRemoved++;
        }
    }
}

echo "Base de dados '{$dbName}' higienizada com sucesso para operação oficial!\n";
echo "Arquivos físicos removidos de storage/documentos: {$filesRemoved}\n";

