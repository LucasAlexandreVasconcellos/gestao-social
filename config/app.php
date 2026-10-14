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

// Registro de Injeção de Dependências dos Repositórios (Atualmente mapeados para os Repositórios Demo em memória)
// Na integração futura com banco de dados, basta substituir as classes concretas abaixo
// pelos repositórios de banco (ex: FamiliaRepositoryDb) sem alterar nenhuma linha de Controller ou Service!
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

return [
    'name' => 'Sistema de Gestão Social e Assistencial',
    'version' => '1.0.0-demo',
    'env' => 'local',
    'demo_mode' => true,
    'charset' => 'UTF-8'
];
