<?php

declare(strict_types=1);

/**
 * Configurações de Conexão com o Banco de Dados (PostgreSQL)
 *
 * Todas as credenciais devem ser fornecidas via arquivo .env ou variáveis de ambiente do servidor.
 * Nenhuma credencial sensível deve ser mantida fixa neste arquivo.
 */
$envPath = dirname(__DIR__) . '/.env';
if (class_exists(\App\Core\Env::class)) {
    \App\Core\Env::load($envPath);
}

use App\Core\Env;

$getEnv = static function (string $key, mixed $default = null): mixed {
    return class_exists(Env::class) ? Env::get($key, $default) : (getenv($key) ?: $default);
};

return [
    'default' => $getEnv('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => (string)$getEnv('DB_HOST', '127.0.0.1'),
            'port' => (int)$getEnv('DB_PORT', 5432),
            'database' => (string)$getEnv('DB_DATABASE', 'gestao_social'),
            'username' => (string)$getEnv('DB_USERNAME', 'gestao_app'),
            'password' => (string)$getEnv('DB_PASSWORD', ''),
            'schema' => (string)$getEnv('DB_SCHEMA', 'gestao_social'),
            'charset' => 'utf8',
            'timeout' => 5,
        ],
    ],
];
