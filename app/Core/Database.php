<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Gerenciador Central de Conexão com Banco de Dados Relacional (PostgreSQL)
 * 
 * Implementa conexão segura via PDO com prepared statements obrigatórios,
 * controle transacional (ACID), injeção de contexto de auditoria de aplicação
 * e proteção estrita contra vazamento de credenciais.
 */
final class Database
{
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;
    private static ?string $lastError = null;

    /**
     * Retorna a instância ativa do PDO ou estabelece nova conexão.
     *
     * @throws RuntimeException se não for possível conectar ao banco
     */
    public static function getConnection(?array $customConfig = null): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$connectionAttempted = true;
        $config = $customConfig ?? self::loadConfig();

        $driver = $config['driver'] ?? 'pgsql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int)($config['port'] ?? 5432);
        $dbname = $config['database'] ?? 'gestao_social';
        $user = $config['username'] ?? 'postgres';
        $pass = $config['password'] ?? '';
        $schema = $config['schema'] ?? 'gestao_social';
        $charset = $config['charset'] ?? 'utf8';
        $timeout = (int)($config['timeout'] ?? 5);

        $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};options='--client_encoding={$charset}'";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => $timeout,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            
            // Configura o search_path para o schema oficial do sistema
            $pdo->exec("SET search_path TO {$schema}, public");
            
            self::$instance = $pdo;
            self::$lastError = null;
            return self::$instance;
        } catch (PDOException $e) {
            // Sanitiza mensagem para NUNCA vazar senha ou credenciais
            $sanitizedMessage = "Falha ao conectar com o banco de dados [{$driver}@{$host}:{$port}/{$dbname}].";
            self::$lastError = $sanitizedMessage;
            self::logError($sanitizedMessage . " Detalhes: " . $e->getCode());
            throw new RuntimeException($sanitizedMessage, (int)$e->getCode());
        }
    }

    /**
     * Verifica se a conexão com o banco de dados está disponível e funcional.
     */
    public static function isConnected(): bool
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Define o contexto do usuário logado na sessão PostgreSQL para auditoria LGPD automática.
     * Os triggers 'trg_auditar_operacao' utilizam 'app.usuario_logado_id' e 'app.usuario_logado'.
     */
    public static function setAuditContext(?int $usuarioId, ?string $login): void
    {
        if (self::$instance === null) {
            return;
        }

        try {
            if ($usuarioId !== null) {
                $stmtId = self::$instance->prepare("SELECT set_config('app.usuario_logado_id', :id, true)");
                $stmtId->execute(['id' => (string)$usuarioId]);
            }
            if (!empty($login)) {
                $stmtUser = self::$instance->prepare("SELECT set_config('app.usuario_logado', :login, true)");
                $stmtUser->execute(['login' => $login]);
            }
        } catch (\Throwable $e) {
            self::logError("Falha ao configurar contexto de auditoria: " . $e->getMessage());
        }
    }

    /**
     * Executa uma operação em bloco transacional seguro (ACID).
     * Se houver qualquer falha ou exceção, o ROLLBACK é automático.
     *
     * @param callable(PDO): mixed $callback
     * @return mixed
     * @throws \Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getConnection();

        $isOuterTransaction = !$pdo->inTransaction();
        if ($isOuterTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);
            if ($isOuterTransaction) {
                $pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if ($isOuterTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::logError("Transação abortada: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fecha a conexão atual (útil para testes unitários).
     */
    public static function disconnect(): void
    {
        self::$instance = null;
        self::$connectionAttempted = false;
        self::$lastError = null;
    }

    /**
     * Retorna a última mensagem de erro (sanitizada).
     */
    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    private static function loadConfig(): array
    {
        $configPath = dirname(__DIR__, 2) . '/config/database.php';
        if (file_exists($configPath)) {
            $cfg = require $configPath;
            $default = $cfg['default'] ?? 'pgsql';
            return $cfg['connections'][$default] ?? [];
        }

        return [
            'driver' => getenv('DB_CONNECTION') ?: 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => (int)(getenv('DB_PORT') ?: 5432),
            'database' => getenv('DB_DATABASE') ?: 'gestao_social',
            'username' => getenv('DB_USERNAME') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: '',
            'schema' => getenv('DB_SCHEMA') ?: 'gestao_social',
            'charset' => 'utf8',
            'timeout' => 5,
        ];
    }

    private static function logError(string $message): void
    {
        $logFile = dirname(__DIR__, 2) . '/storage/logs/database.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
}
