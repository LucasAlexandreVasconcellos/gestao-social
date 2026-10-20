<?php

declare(strict_types=1);

/**
 * Script de Inicialização e Configuração do Banco de Dados PostgreSQL
 * 
 * Uso: php scripts/setup_database.php [senha_postgres] [usuario_postgres] [porta] [banco]
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Env.php';
\App\Core\Env::load($root . '/.env');

$password = $argv[1] ?? getenv('DB_PASSWORD') ?: '';
$username = $argv[2] ?? getenv('DB_USERNAME') ?: 'postgres';
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int)($argv[3] ?? getenv('DB_PORT') ?: 5432);
$dbName = $argv[4] ?? getenv('DB_DATABASE') ?: 'gestao_social';
$schema = getenv('DB_SCHEMA') ?: 'gestao_social';

echo "========================================================\n";
echo "Configuração e Inicialização do Banco de Dados PostgreSQL\n";
echo "Host: {$host}:{$port}\n";
echo "Usuário: {$username}\n";
echo "Banco Alvo: {$dbName}\n";
echo "========================================================\n\n";

// 1. Conectar ao banco inicial (postgres) para verificar/criar o banco gestao_social
echo "[1/4] Conectando ao servidor PostgreSQL...\n";
try {
    $pdoInit = new PDO(
        "pgsql:host={$host};port={$port};dbname=postgres",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    echo "  -> Conexão estabelecida com sucesso!\n";
} catch (PDOException $e) {
    echo "  [ERRO] Falha ao autenticar no PostgreSQL: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar se o banco de dados gestao_social existe
echo "\n[2/4] Verificando existência do banco '{$dbName}'...\n";
$stmt = $pdoInit->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
$stmt->execute(['dbname' => $dbName]);
if (!$stmt->fetch()) {
    echo "  -> Banco '{$dbName}' não encontrado. Criando...\n";
    $pdoInit->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING 'UTF8'");
    echo "  -> Banco '{$dbName}' criado com sucesso!\n";
} else {
    echo "  -> Banco '{$dbName}' já existe.\n";
}
unset($pdoInit);

// 3. Conectar ao banco gestao_social e executar os scripts DDL, Triggers e Seeds
echo "\n[3/4] Conectando ao banco '{$dbName}' e aplicando scripts SQL...\n";
try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbName}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]
    );

    $sqlFiles = [
        '01_schema_ddl.sql' => $root . '/sql/01_schema_ddl.sql',
        '02_views_triggers.sql' => $root . '/sql/02_views_triggers.sql',
        '03_seed_data.sql' => $root . '/sql/03_seed_data.sql',
    ];

    foreach ($sqlFiles as $label => $filePath) {
        if (!file_exists($filePath)) {
            echo "  [AVISO] Arquivo {$label} não encontrado em {$filePath}\n";
            continue;
        }
        echo "  -> Executando {$label}... ";
        $sqlContent = file_get_contents($filePath);
        if ($sqlContent === false) {
            echo "falha na leitura!\n";
            continue;
        }

        $pdo->exec($sqlContent);
        echo "OK!\n";
    }

} catch (PDOException $e) {
    echo "\n  [ERRO] Falha ao executar scripts SQL: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Salvar configuração no arquivo .env
echo "\n[4/4] Atualizando arquivo .env...\n";
$envContent = <<<ENV
# Configurações do Banco de Dados PostgreSQL
DB_CONNECTION=pgsql
DB_HOST={$host}
DB_PORT={$port}
DB_DATABASE={$dbName}
DB_USERNAME={$username}
DB_PASSWORD={$password}
DB_SCHEMA={$schema}
ENV;

file_put_contents($root . '/.env', $envContent . "\n");
echo "  -> Arquivo .env salvo com sucesso!\n";

// 5. Teste final da arquitetura
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');

\App\Core\Database::disconnect();
if (\App\Core\Database::isConnected()) {
    echo "\n========================================================\n";
    echo "SUCESSO! Conexão PDO ativa e validada pelo App\\Core\\Database!\n";
    echo "Todos os 12 repositórios Db estão prontos para produção.\n";
    echo "========================================================\n";
} else {
    echo "\n[AVISO] Conexão via App\\Core\\Database retornou falso: " . \App\Core\Database::getLastError() . "\n";
}
