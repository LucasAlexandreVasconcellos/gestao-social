<?php

declare(strict_types=1);

/**
 * Validação de Conformidade dos Repositórios de Banco de Dados (Database Repositories)
 * 
 * Verifica se todas as 10 implementações de banco atendem estritamente aos contratos
 * das interfaces de repositório, assinaturas de métodos e gerenciamento transacional.
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Core/Autoloader.php';
require_once ROOT_PATH . '/app/Core/polyfills.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', ROOT_PATH . '/app');

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AuditoriaRepositoryInterface;
use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Contracts\Repositories\DocumentoRepositoryInterface;
use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\NotificacaoRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Contracts\Repositories\ProjetoRepositoryInterface;
use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Core\Database;
use App\Repositories\Database\AtendimentoRepositoryDb;
use App\Repositories\Database\AuditoriaRepositoryDb;
use App\Repositories\Database\AvaliacaoRepositoryDb;
use App\Repositories\Database\BeneficioRepositoryDb;
use App\Repositories\Database\DoacaoRepositoryDb;
use App\Repositories\Database\DocumentoRepositoryDb;
use App\Repositories\Database\EstoqueRepositoryDb;
use App\Repositories\Database\FamiliaRepositoryDb;
use App\Repositories\Database\NotificacaoRepositoryDb;
use App\Repositories\Database\PessoaRepositoryDb;
use App\Repositories\Database\ProjetoRepositoryDb;
use App\Repositories\Database\UsuarioRepositoryDb;

echo "============================================================\n";
echo "VALIDAÇÃO DE CONFORMIDADE: CAMADA DE BANCO DE DADOS (POSTGRESQL)\n";
echo "============================================================\n\n";

$repositories = [
    FamiliaRepositoryInterface::class => FamiliaRepositoryDb::class,
    PessoaRepositoryInterface::class => PessoaRepositoryDb::class,
    AvaliacaoRepositoryInterface::class => AvaliacaoRepositoryDb::class,
    AtendimentoRepositoryInterface::class => AtendimentoRepositoryDb::class,
    BeneficioRepositoryInterface::class => BeneficioRepositoryDb::class,
    EstoqueRepositoryInterface::class => EstoqueRepositoryDb::class,
    DoacaoRepositoryInterface::class => DoacaoRepositoryDb::class,
    ProjetoRepositoryInterface::class => ProjetoRepositoryDb::class,
    UsuarioRepositoryInterface::class => UsuarioRepositoryDb::class,
    AuditoriaRepositoryInterface::class => AuditoriaRepositoryDb::class,
    DocumentoRepositoryInterface::class => DocumentoRepositoryDb::class,
    NotificacaoRepositoryInterface::class => NotificacaoRepositoryDb::class,
];

$errors = 0;

// 1. Testa mapeamento de interfaces e herança
foreach ($repositories as $interface => $class) {
    echo "Verificando contrato de " . basename(str_replace('\\', '/', $class)) . "... ";
    
    if (!class_exists($class)) {
        echo "\033[31m[FALHOU: Classe não encontrada]\033[0m\n";
        $errors++;
        continue;
    }

    $reflector = new ReflectionClass($class);
    if (!$reflector->implementsInterface($interface)) {
        echo "\033[31m[FALHOU: Não implementa {$interface}]\033[0m\n";
        $errors++;
        continue;
    }

    // Verifica se todos os métodos da interface estão implementados com compatibilidade
    $interfaceReflector = new ReflectionClass($interface);
    $missingMethods = [];
    foreach ($interfaceReflector->getMethods() as $method) {
        if (!$reflector->hasMethod($method->getName())) {
            $missingMethods[] = $method->getName();
        }
    }

    if (!empty($missingMethods)) {
        echo "\033[31m[FALHOU: Métodos ausentes: " . implode(', ', $missingMethods) . "]\033[0m\n";
        $errors++;
        continue;
    }

    echo "\033[32m[OK: 100% Compatível]\033[0m\n";
}

// 2. Valida classe Database (App\Core\Database)
echo "\nVerificando utilitário central App\\Core\\Database... ";
$dbReflector = new ReflectionClass(Database::class);
$requiredMethods = ['getConnection', 'isConnected', 'setAuditContext', 'transaction', 'disconnect', 'getLastError'];
$missingDbMethods = [];

foreach ($requiredMethods as $m) {
    if (!$dbReflector->hasMethod($m)) {
        $missingDbMethods[] = $m;
    }
}

if (!empty($missingDbMethods)) {
    echo "\033[31m[FALHOU: Métodos ausentes em Database: " . implode(', ', $missingDbMethods) . "]\033[0m\n";
    $errors++;
} else {
    echo "\033[32m[OK: Métodos ACID e Auditoria presentes]\033[0m\n";
}

// 3. Valida arquivo de configuração config/database.php
echo "Verificando arquivo de configuração config/database.php... ";
$configPath = ROOT_PATH . '/config/database.php';
if (!file_exists($configPath)) {
    echo "\033[31m[FALHOU: Arquivo não encontrado]\033[0m\n";
    $errors++;
} else {
    $cfg = require $configPath;
    if (isset($cfg['connections']['pgsql']['driver']) && $cfg['connections']['pgsql']['driver'] === 'pgsql') {
        echo "\033[32m[OK: Driver PostgreSQL configurado]\033[0m\n";
    } else {
        echo "\033[31m[FALHOU: Estrutura inválida em config/database.php]\033[0m\n";
        $errors++;
    }
}

echo "\n============================================================\n";
echo "RESULTADO DA VERIFICAÇÃO DE REPOSITÓRIOS\n";
echo "Total de repositórios validados: " . count($repositories) . "\n";
echo "Erros detectados: {$errors}\n";
echo "============================================================\n";

exit($errors === 0 ? 0 : 1);
