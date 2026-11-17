<?php

declare(strict_types=1);

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
use App\Core\Container;
use App\Core\Database;
use App\Core\Env;
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

// Carrega .env se disponível
if (class_exists(Env::class)) {
    Env::load(dirname(__DIR__) . '/.env');
}

// Fuso horário institucional
date_default_timezone_set('America/Sao_Paulo');

$appEnv = class_exists(Env::class) ? (string)Env::get('APP_ENV', 'production') : (getenv('APP_ENV') ?: 'production');
$isTesting = ($appEnv === 'testing');

if (!$isTesting) {
    Container::singleton(\PDO::class, fn() => Database::getConnection());
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
    Container::singleton(DocumentoRepositoryInterface::class, DocumentoRepositoryDb::class);
    Container::singleton(NotificacaoRepositoryInterface::class, NotificacaoRepositoryDb::class);
} else {
    // Ambiente isolado de testes unitários automatizados (exclusivo via CLI)
    Container::singleton(FamiliaRepositoryInterface::class, \App\Repositories\Demo\FamiliaRepositoryDemo::class);
    Container::singleton(PessoaRepositoryInterface::class, \App\Repositories\Demo\PessoaRepositoryDemo::class);
    Container::singleton(AvaliacaoRepositoryInterface::class, \App\Repositories\Demo\AvaliacaoRepositoryDemo::class);
    Container::singleton(AtendimentoRepositoryInterface::class, \App\Repositories\Demo\AtendimentoRepositoryDemo::class);
    Container::singleton(BeneficioRepositoryInterface::class, \App\Repositories\Demo\BeneficioRepositoryDemo::class);
    Container::singleton(EstoqueRepositoryInterface::class, \App\Repositories\Demo\EstoqueRepositoryDemo::class);
    Container::singleton(DoacaoRepositoryInterface::class, \App\Repositories\Demo\DoacaoRepositoryDemo::class);
    Container::singleton(ProjetoRepositoryInterface::class, \App\Repositories\Demo\ProjetoRepositoryDemo::class);
    Container::singleton(UsuarioRepositoryInterface::class, \App\Repositories\Demo\UsuarioRepositoryDemo::class);
    Container::singleton(AuditoriaRepositoryInterface::class, \App\Repositories\Demo\AuditoriaRepositoryDemo::class);
    Container::singleton(DocumentoRepositoryInterface::class, \App\Repositories\Demo\DocumentoRepositoryDemo::class);
    Container::singleton(NotificacaoRepositoryInterface::class, \App\Repositories\Demo\NotificacaoRepositoryDemo::class);
}

return [
    'name' => 'Sistema de Gestão Social e Assistencial',
    'org_name' => (string)(class_exists(Env::class) ? Env::get('APP_ORG_NAME', 'Secretaria Municipal de Assistência Social') : 'Secretaria Municipal de Assistência Social'),
    'unit_name' => (string)(class_exists(Env::class) ? Env::get('APP_UNIT_NAME', 'Gestão Socioassistencial — SUAS') : 'Gestão Socioassistencial — SUAS'),
    'public_url' => rtrim((string)(class_exists(Env::class) ? Env::get('APP_PUBLIC_URL', 'http://localhost:8000') : 'http://localhost:8000'), '/'),
    'version' => '2.0.0',
    'env' => $appEnv,
    'demo_mode' => false,
    'db_driver' => 'pgsql',
    'charset' => 'UTF-8',
];
