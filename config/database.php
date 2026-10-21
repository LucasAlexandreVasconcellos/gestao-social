<?php

declare(strict_types=1);

/**
 * Configurações de Conexão com o Banco de Dados (PostgreSQL)
 * 
 * As variáveis podem ser definidas no ambiente (.env ou variáveis de sistema)
 * ou ajustadas diretamente neste arquivo para o ambiente de implantação.
 */
// Carrega variáveis do arquivo .env na raiz do projeto se existir
$envPath = dirname(__DIR__) . '/.env';
if (class_exists(\App\Core\Env::class)) {
    \App\Core\Env::load($envPath);
}

use App\Core\Env;

return [
    'default' => class_exists(Env::class) ? Env::get('DB_CONNECTION', 'pgsql') : (getenv('DB_CONNECTION') ?: 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => class_exists(Env::class) ? Env::get('DB_HOST', '10.223.152.5') : (getenv('DB_HOST') ?: '10.223.152.5'),
            'port' => (int)(class_exists(Env::class) ? Env::get('DB_PORT', 5432) : (getenv('DB_PORT') ?: 5432)),
            'database' => class_exists(Env::class) ? Env::get('DB_DATABASE', 'obras') : (getenv('DB_DATABASE') ?: 'obras'),
            'username' => class_exists(Env::class) ? Env::get('DB_USERNAME', 'postgres') : (getenv('DB_USERNAME') ?: 'postgres'),
            'password' => class_exists(Env::class) ? Env::get('DB_PASSWORD', 'Sodf@1234') : (getenv('DB_PASSWORD') ?: 'Sodf@1234'),
            'schema' => class_exists(Env::class) ? Env::get('DB_SCHEMA', 'gestao_social') : (getenv('DB_SCHEMA') ?: 'gestao_social'),
            'charset' => 'utf8',
            'timeout' => 5,
        ],
    ],
];
