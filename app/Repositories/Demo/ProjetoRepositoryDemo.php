<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\ProjetoRepositoryInterface;
use App\Core\Session;
use App\Models\ParticipanteProjeto;
use App\Models\ProjetoSocial;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Simula campanhas, projetos, oficinas e controle de presenças/beneficiários.
 */
final class ProjetoRepositoryDemo implements ProjetoRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_projetos';
    private const SESSION_PART_KEY = '_demo_participantes';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $part1 = new ParticipanteProjeto(1, 1, 1, 1, 'Maria da Silva', 'Efetivamente Atendido', '2026-01-25', 'Retirada regular de cesta');
            $part2 = new ParticipanteProjeto(2, 1, 2, 3, 'Carlos Eduardo de Oliveira', 'Efetivamente Atendido', '2026-02-28', 'Retirada regular de cesta');
            $part3 = new ParticipanteProjeto(3, 1, 3, 5, 'Joana D’Arc Santos', 'Convocado', null, 'Aguardando comparecimento');
            $part4 = new ParticipanteProjeto(4, 2, 1, 1, 'Maria da Silva', 'Inscrito', null, 'Inscrita na turma matutina');
            $part5 = new ParticipanteProjeto(5, 2, 2, 4, 'Patrícia Gomes de Oliveira', 'Inscrito', null, 'Inscrita na turma matutina');

            $initial = [
                1 => new ProjetoSocial(
                    1, 'Campanha Alimento para Todos 2026', 'Distribuição Coletiva',
                    'Famílias em extrema vulnerabilidade e insegurança alimentar',
                    '2026-01-15', '2026-12-20', 'Sede da Entidade e Postos Comunitários',
                    'Fernanda Coordenadora', 60000.00, 15400.00, 120, 'Em Andamento',
                    'Distribuição mensal sistemática de cestas básicas e kits nutricionais.',
                    [$part1, $part2, $part3]
                ),
                2 => new ProjetoSocial(
                    2, 'Oficina de Capacitação em Panificação Artesanal', 'Oficina',
                    'Mulheres chefes de família e jovens em busca do primeiro emprego',
                    '2026-04-01', '2026-06-30', 'Cozinha Comunitária',
                    'Marcos Vinicius', 8500.00, 1200.00, 25, 'Planejada',
                    'Capacitação prática para geração de renda e autonomia financeira.',
                    [$part4, $part5]
                ),
                3 => new ProjetoSocial(
                    3, 'Ação do Agasalho e Cobertores - Inverno Solidário', 'Campanha',
                    'Pessoas em situação de rua e famílias em habitações precárias',
                    '2026-05-15', '2026-07-31', 'Vila Esperança e Morro Alto',
                    'Robson Almoxarife', 12000.00, 0.00, 200, 'Planejada',
                    'Coleta e entrega emergencial de agasalhos, cobertores e calçados.',
                    []
                )
            ];

            Session::set(self::SESSION_KEY, $initial);
            Session::set(self::SESSION_PART_KEY, [$part1, $part2, $part3, $part4, $part5]);
        }
    }

    public function findAll(array $filters = []): array
    {
        $projetos = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['tipo'])) {
            $tipo = $filters['tipo'];
            $projetos = array_filter($projetos, fn(ProjetoSocial $p) => $p->tipo === $tipo);
        }

        if (!empty($filters['situacao'])) {
            $situacao = $filters['situacao'];
            $projetos = array_filter($projetos, fn(ProjetoSocial $p) => $p->situacao === $situacao);
        }

        usort($projetos, fn(ProjetoSocial $a, ProjetoSocial $b) => strcmp($b->dataInicio, $a->dataInicio));

        return array_values($projetos);
    }

    public function findById(int $id): ?ProjetoSocial
    {
        $projetos = Session::get(self::SESSION_KEY, []);
        return $projetos[$id] ?? null;
    }

    public function save(ProjetoSocial $projeto): ProjetoSocial
    {
        $projetos = Session::get(self::SESSION_KEY, []);
        if ($projeto->id <= 0) {
            $maxId = empty($projetos) ? 0 : max(array_keys($projetos));
            $projeto->id = $maxId + 1;
        }

        $projetos[$projeto->id] = $projeto;
        Session::set(self::SESSION_KEY, $projetos);
        return $projeto;
    }

    public function update(ProjetoSocial $projeto): bool
    {
        $projetos = Session::get(self::SESSION_KEY, []);
        if (!isset($projetos[$projeto->id])) {
            return false;
        }

        $projetos[$projeto->id] = $projeto;
        Session::set(self::SESSION_KEY, $projetos);
        return true;
    }

    public function addParticipante(ParticipanteProjeto $participante): ParticipanteProjeto
    {
        $parts = Session::get(self::SESSION_PART_KEY, []);
        if ($participante->id <= 0) {
            $maxId = empty($parts) ? 0 : max(array_map(fn($p) => $p->id, $parts));
            $participante->id = $maxId + 1;
        }
        $parts[] = $participante;
        Session::set(self::SESSION_PART_KEY, $parts);

        $projeto = $this->findById($participante->projetoId);
        if ($projeto) {
            $projeto->participantes[] = $participante;
            $this->update($projeto);
        }

        return $participante;
    }

    public function updateParticipante(ParticipanteProjeto $participante): bool
    {
        $parts = Session::get(self::SESSION_PART_KEY, []);
        foreach ($parts as $idx => $p) {
            if ($p->id === $participante->id) {
                $parts[$idx] = $participante;
                Session::set(self::SESSION_PART_KEY, $parts);

                // Sincroniza com o projeto
                $projeto = $this->findById($participante->projetoId);
                if ($projeto) {
                    foreach ($projeto->participantes as $pidx => $pp) {
                        if ($pp->id === $participante->id) {
                            $projeto->participantes[$pidx] = $participante;
                            $this->update($projeto);
                            break;
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function getParticipantes(int $projetoId): array
    {
        $parts = Session::get(self::SESSION_PART_KEY, []);
        if ($projetoId > 0) {
            $parts = array_filter($parts, fn(ParticipanteProjeto $p) => $p->projetoId === $projetoId);
        }
        return array_values($parts);
    }

    public function countEmAndamento(): int
    {
        $projetos = Session::get(self::SESSION_KEY, []);
        $count = 0;
        foreach ($projetos as $p) {
            if ($p->situacao === 'Em Andamento') {
                $count++;
            }
        }
        return $count;
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }
}
