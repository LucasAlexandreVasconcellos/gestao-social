<?php

declare(strict_types=1);

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AuditoriaRepositoryInterface;
use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Contracts\Repositories\ProjetoRepositoryInterface;
use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Core\Container;
use App\Core\Database;
use App\Repositories\Database\AtendimentoRepositoryDb;
use App\Repositories\Database\AuditoriaRepositoryDb;
use App\Repositories\Database\AvaliacaoRepositoryDb;
use App\Repositories\Database\BeneficioRepositoryDb;
use App\Repositories\Database\DoacaoRepositoryDb;
use App\Repositories\Database\EstoqueRepositoryDb;
use App\Repositories\Database\FamiliaRepositoryDb;
use App\Repositories\Database\PessoaRepositoryDb;
use App\Repositories\Database\ProjetoRepositoryDb;
use App\Repositories\Database\UsuarioRepositoryDb;
use App\Repositories\Demo\AtendimentoRepositoryDemo;
use App\Repositories\Demo\AuditoriaRepositoryDemo;
use App\Repositories\Demo\AvaliacaoRepositoryDemo;
use App\Repositories\Demo\BeneficioRepositoryDemo;
use App\Repositories\Demo\DoacaoRepositoryDemo;
use App\Repositories\Demo\EstoqueRepositoryDemo;
use App\Repositories\Demo\FamiliaRepositoryDemo;
use App\Repositories\Demo\PessoaRepositoryDemo;
use App\Repositories\Demo\ProjetoRepositoryDemo;
use App\Repositories\Demo\UsuarioRepositoryDemo;

// Fuso horário institucional
date_default_timezone_set('America/Sao_Paulo');

// Detecção de Modo de Operação (Banco de Dados PostgreSQL vs Demonstração em Memória)
// Se APP_DEMO_MODE não estiver forçado para 'true' e o banco PostgreSQL estiver conectado,
// ativa os repositórios reais com persistência em banco de dados!
$forceDemo = (getenv('APP_DEMO_MODE') === 'true');
$useDatabase = !$forceDemo && (getenv('DB_CONNECTION') === 'pgsql' || Database::isConnected());

if ($useDatabase) {
    Container::singleton(FamiliaRepositoryInterface::class, FamiliaRepositoryDb::class);
    Container::singleton(PessoaRepositoryInterface::class, PessoaRepositoryDb::class);
    Container::singleton(AvaliacaoRepositoryInterface::class, AvaliacaoRepositoryDb::class);
    Container::singleton(AtendimentoRepositoryInterface::class, AtendimentoRepositoryDb::class);
    Container::singleton(BeneficioRepositoryInterface::class, BeneficioRepositoryDb::class);
    Container::singleton(EstoqueRepositoryInterface::class, EstoqueRepositoryDb::class);
    Container::singleton(DoacaoRepositoryInterface::class, DoacaoRepositoryDb::class);
    Container::singleton(ProjetoRepositoryInterface::class, ProjetoRepositoryDb::class);
    Container::singleton(UsuarioRepositoryInterface::class, UsuarioRepositoryDb::class);
    Container::singleton(AuditoriaRepositoryInterface::class, AuditoriaRepositoryDb::class);
} else {
    Container::singleton(FamiliaRepositoryInterface::class, FamiliaRepositoryDemo::class);
    Container::singleton(PessoaRepositoryInterface::class, PessoaRepositoryDemo::class);
    Container::singleton(AvaliacaoRepositoryInterface::class, AvaliacaoRepositoryDemo::class);
    Container::singleton(AtendimentoRepositoryInterface::class, AtendimentoRepositoryDemo::class);
    Container::singleton(BeneficioRepositoryInterface::class, BeneficioRepositoryDemo::class);
    Container::singleton(EstoqueRepositoryInterface::class, EstoqueRepositoryDemo::class);
    Container::singleton(DoacaoRepositoryInterface::class, DoacaoRepositoryDemo::class);
    Container::singleton(ProjetoRepositoryInterface::class, ProjetoRepositoryDemo::class);
    Container::singleton(UsuarioRepositoryInterface::class, UsuarioRepositoryDemo::class);
    Container::singleton(AuditoriaRepositoryInterface::class, AuditoriaRepositoryDemo::class);
}

return [
    'name' => 'Sistema de Gestão Social e Assistencial',
    'version' => '1.0.0',
    'env' => getenv('APP_ENV') ?: 'local',
    'demo_mode' => !$useDatabase,
    'db_driver' => $useDatabase ? 'pgsql' : 'memory',
    'charset' => 'UTF-8'
];
