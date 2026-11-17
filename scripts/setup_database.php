<?php

declare(strict_types=1);

/**
 * Script de Inicialização e Configuração do Banco de Dados PostgreSQL Oficial
 *
 * Uso:
 *   php scripts/setup_database.php <senha_admin_postgres> [usuario_admin] [porta] [banco] [host] [schema]
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Env.php';
\App\Core\Env::load($root . '/.env');

$adminPass = $argv[1] ?? getenv('DB_ADMIN_PASSWORD') ?: '';
if ($adminPass === '') {
    fwrite(STDERR, "ERRO: Informe a senha do administrador PostgreSQL como primeiro argumento ou via DB_ADMIN_PASSWORD.\n");
    fwrite(STDERR, "Uso: php scripts/setup_database.php <senha_postgres> [usuario_admin=postgres] [porta=5432] [banco=gestao_social] [host=127.0.0.1] [schema=gestao_social]\n");
    exit(1);
}

$adminUser = $argv[2] ?? 'postgres';
$port = (int)($argv[3] ?? \App\Core\Env::get('DB_PORT', 5432));
$dbName = $argv[4] ?? \App\Core\Env::get('DB_DATABASE', 'gestao-social');
$host = $argv[5] ?? \App\Core\Env::get('DB_HOST', '127.0.0.1');
$schema = $argv[6] ?? \App\Core\Env::get('DB_SCHEMA', 'gestao_social');

if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $dbName) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $schema)) {
    fwrite(STDERR, "ERRO: Nome de banco ou schema inválido.\n");
    exit(1);
}

$appRole = 'gestao_app';
$appPassword = bin2hex(random_bytes(16));
$signingKey = bin2hex(random_bytes(32));

echo "========================================================\n";
echo "Configuração Segura do Banco de Dados PostgreSQL Oficial\n";
echo "Host: {$host}:{$port} | Banco: {$dbName} | Schema: {$schema}\n";
echo "========================================================\n\n";

try {
    $pdoInit = new PDO(
        "pgsql:host={$host};port={$port};dbname=postgres;options='--client_encoding=utf8'",
        $adminUser,
        $adminPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );

    $stmt = $pdoInit->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
    $stmt->execute(['dbname' => $dbName]);
    if (!$stmt->fetch()) {
        echo "[1/5] Criando banco '{$dbName}'...\n";
        $pdoInit->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING 'UTF8'");
    } else {
        echo "[1/5] Banco '{$dbName}' já existe.\n";
    }
    unset($pdoInit);

    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbName};options='--client_encoding=utf8'",
        $adminUser,
        $adminPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "[ERRO FATAL] Falha de conexão administrativa: " . $e->getMessage() . "\n");
    exit(1);
}

echo "[2/5] Configurando schema '{$schema}'...\n";
$pdo->exec("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");
$pdo->exec("SET search_path TO \"{$schema}\", public");

echo "[3/5] Aplicando scripts SQL (DDL, Views/Triggers e Carga Inicial)...\n";
$sqlFiles = [
    '01_schema_ddl.sql' => $root . '/sql/01_schema_ddl.sql',
    '02_views_triggers.sql' => $root . '/sql/02_views_triggers.sql',
    '03_seed_data.sql' => $root . '/sql/03_seed_data.sql',
];

foreach ($sqlFiles as $label => $filePath) {
    if (!file_exists($filePath)) {
        echo "  [AVISO] {$label} não encontrado.\n";
        continue;
    }
    echo "  -> {$label}... ";
    $pdo->exec((string)file_get_contents($filePath));
    echo "OK!\n";
}

echo "[4/5] Provisionando usuário de menor privilégio '{$appRole}'...\n";
$stmtRole = $pdo->prepare("SELECT 1 FROM pg_roles WHERE rolname = :r");
$stmtRole->execute(['r' => $appRole]);
$escapedPass = str_replace("'", "''", $appPassword);
if ($stmtRole->fetch()) {
    $pdo->exec("ALTER ROLE \"{$appRole}\" WITH LOGIN PASSWORD '{$escapedPass}' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT");
} else {
    $pdo->exec("CREATE ROLE \"{$appRole}\" WITH LOGIN PASSWORD '{$escapedPass}' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT");
}

if (file_exists($root . '/sql/04_least_privilege.sql')) {
    $privSql = (string)file_get_contents($root . '/sql/04_least_privilege.sql');
    // Substitui bloco de criação de role já tratado acima
    $privSql = preg_replace('/DO \$\$.*?END \$\$;/s', '', $privSql);
    $pdo->exec($privSql);
}
echo "  -> Permissões restritas aplicadas para '{$appRole}' (sem UPDATE/DELETE/TRUNCATE na auditoria).\n";

echo "[5/5] Gravando arquivo .env com credenciais da role de aplicação...\n";
$envContent = <<<ENV
# Configurações do Banco de Dados PostgreSQL Oficial (Role de Menor Privilégio)
DB_CONNECTION=pgsql
DB_HOST={$host}
DB_PORT={$port}
DB_DATABASE={$dbName}
DB_USERNAME={$appRole}
DB_PASSWORD={$appPassword}
DB_SCHEMA={$schema}

# Ambiente e Segurança da Aplicação
APP_ENV=production
APP_DEMO_MODE=false
APP_PUBLIC_URL=http://localhost:8000
APP_SIGNING_KEY={$signingKey}
SESSION_IDLE_TIMEOUT=1800

# Identidade Institucional
APP_ORG_NAME="Secretaria Municipal de Assistência Social"
APP_UNIT_NAME="Gestão Socioassistencial — SUAS"
ENV;

file_put_contents($root . '/.env', $envContent . "\n");
@chmod($root . '/.env', 0640);

require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');

\App\Core\Database::disconnect();
if (\App\Core\Database::isConnected()) {
    echo "\nSUCESSO! Conexão PDO validada com a role '{$appRole}'.\n";
    echo "IMPORTANTE: As contas iniciais exigirão troca de senha obrigatória no primeiro acesso.\n";
} else {
    fwrite(STDERR, "\n[AVISO] Falha ao testar conexão com App\\Core\\Database: " . \App\Core\Database::getLastError() . "\n");
    exit(1);
}
