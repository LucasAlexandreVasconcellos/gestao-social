<?php

declare(strict_types=1);

/**
 * Script de Inicialização e Configuração do Banco de Dados PostgreSQL Oficial
 * 
 * Uso: php scripts/setup_database.php [senha_postgres] [usuario_postgres] [porta] [banco] [host] [schema]
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Env.php';
\App\Core\Env::load($root . '/.env');

$password = $argv[1] ?? getenv('DB_PASSWORD') ?: 'Sodf@1234';
$username = $argv[2] ?? getenv('DB_USERNAME') ?: 'postgres';
$host = $argv[5] ?? getenv('DB_HOST') ?: '10.223.152.5';
$port = (int)($argv[3] ?? getenv('DB_PORT') ?: 5432);
$dbName = $argv[4] ?? getenv('DB_DATABASE') ?: 'obras';
$schema = $argv[6] ?? getenv('DB_SCHEMA') ?: 'gestao_social';

echo "========================================================\n";
echo "Configuração e Inicialização do Banco de Dados PostgreSQL Oficial\n";
echo "Host: {$host}:{$port}\n";
echo "Usuário: {$username}\n";
echo "Banco Alvo: {$dbName}\n";
echo "Schema: {$schema}\n";
echo "========================================================\n\n";

$pdo = null;

// 1. Tentar conectar diretamente ao banco alvo
echo "[1/4] Conectando ao banco alvo '{$dbName}'...\n";
try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbName};options='--client_encoding=utf8'",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    echo "  -> Conexão com o banco '{$dbName}' estabelecida com sucesso!\n";
} catch (PDOException $e) {
    echo "  -> Não foi possível conectar diretamente ao banco '{$dbName}' ({$e->getMessage()}).\n";
    echo "  -> Tentando conectar ao banco de manutenção 'postgres' para provisionar '{$dbName}'...\n";

    try {
        $pdoInit = new PDO(
            "pgsql:host={$host};port={$port};dbname=postgres;options='--client_encoding=utf8'",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]
        );
        echo "  -> Conectado ao banco de manutenção 'postgres'.\n";

        $stmt = $pdoInit->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
        $stmt->execute(['dbname' => $dbName]);
        if (!$stmt->fetch()) {
            echo "  -> Criando banco de dados '{$dbName}'...\n";
            $pdoInit->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING 'UTF8'");
            echo "  -> Banco '{$dbName}' criado com sucesso!\n";
        }
        unset($pdoInit);

        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$dbName};options='--client_encoding=utf8'",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]
        );
    } catch (PDOException $err2) {
        echo "  [ERRO FATAL] Falha de conexão: " . $err2->getMessage() . "\n";
        exit(1);
    }
}

// 2. Garantir criação e permissão do schema
echo "\n[2/4] Verificando schema '{$schema}'...\n";
try {
    $pdo->exec("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");
    $pdo->exec("SET search_path TO \"{$schema}\", public");
    echo "  -> Schema '{$schema}' configurado com sucesso!\n";
} catch (PDOException $e) {
    echo "  [ERRO] Falha ao configurar schema: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Executar scripts SQL
echo "\n[3/4] Aplicando scripts SQL (DDL, Triggers e Carga Inicial)...\n";
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

    try {
        $pdo->exec($sqlContent);
        echo "OK!\n";
    } catch (PDOException $e) {
        echo "\n  [AVISO/ERRO em {$label}]: " . $e->getMessage() . "\n";
    }
}

// 4. Salvar configuração no arquivo .env
echo "\n[4/4] Atualizando arquivo .env com credenciais oficiais...\n";
$envContent = <<<ENV
# Configurações do Banco de Dados PostgreSQL Oficial
DB_CONNECTION=pgsql
DB_HOST={$host}
DB_PORT={$port}
DB_DATABASE={$dbName}
DB_USERNAME={$username}
DB_PASSWORD={$password}
DB_SCHEMA={$schema}

# Ambiente de Execução Oficial
APP_ENV=production
APP_DEMO_MODE=false
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
    echo "Ambiente final configurado para a base '{$dbName}' (schema '{$schema}').\n";
    echo "========================================================\n";
} else {
    echo "\n[AVISO] Conexão via App\\Core\\Database retornou: " . \App\Core\Database::getLastError() . "\n";
}
