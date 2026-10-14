<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Core\Session;
use App\Models\Familia;
use App\Models\Pessoa;
use App\Models\VinculoFamiliar;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * ATENÇÃO: Esta classe NÃO realiza persistência em banco de dados.
 * Serve exclusivamente para testes e demonstração do sistema em ambiente local.
 * A implementação definitiva deverá ser conectada pelo responsável pelo banco de dados
 * conforme especificado em docs/DatabaseIntegration.md.
 */
final class FamiliaRepositoryDemo implements FamiliaRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_familias';
    private const SESSION_VINCULOS_KEY = '_demo_vinculos';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $initialFamilies = [
                1 => new Familia(
                    1, 'FAM-001', 'Maria da Silva', 1, '04840-000', 'Rua das Flores', '124', 'Casa B',
                    'Vila Esperança', 'São Paulo', 'SP', 700.00, 850.00, 'Cedida', '2026-01-10', 'Ativa',
                    'Família com prioridade para entrega de cestas básicas e apoio escolar.'
                ),
                2 => new Familia(
                    2, 'FAM-002', 'Carlos Eduardo de Oliveira', 3, '04842-120', 'Av. das Palmeiras', '45', null,
                    'Jardim Primavera', 'São Paulo', 'SP', 1412.00, 1300.00, 'Alugada', '2026-02-15', 'Ativa',
                    'Responsável com emprego informal no comércio.'
                ),
                3 => new Familia(
                    3, 'FAM-003', 'Joana D’Arc Santos', 5, '04850-200', 'Travessa Bela Vista', '12', 'Fundos',
                    'Morro Alto', 'São Paulo', 'SP', 350.00, 650.00, 'Ocupação', '2026-03-01', 'Em Análise',
                    'Solicitação de acolhimento emergencial e benefício eventual.'
                ),
                4 => new Familia(
                    4, 'FAM-004', 'Antonio Marcos Pereira', 7, '01001-000', 'Rua Direita', '300', 'Apto 12',
                    'Centro', 'São Paulo', 'SP', 1800.00, 1600.00, 'Própria', '2025-11-20', 'Suspensa',
                    'Cadastro em revisão por melhoria temporária de renda.'
                ),
                5 => new Familia(
                    5, 'FAM-005', 'Francisca das Chagas Costa', 9, '04840-050', 'Rua da Paz', '88', null,
                    'Vila Esperança', 'São Paulo', 'SP', 0.00, 520.00, 'Cedida', '2026-02-28', 'Ativa',
                    'Situação de desemprego total e insegurança alimentar grave.'
                )
            ];

            $initialVinculos = [
                // Familia 1
                new VinculoFamiliar(1, 1, 1, 'Responsável Familiar', true, '2026-01-10', null, true, null, new Pessoa(1, 'Maria da Silva', null, '1984-05-12', '12345678909', '11987654321', 'Ensino Fundamental Incompleto', 'Diarista', 'Autônomo', 'Trabalha 2x por semana')),
                new VinculoFamiliar(2, 1, 2, 'Filho(a)', false, '2026-01-10', null, true, null, new Pessoa(2, 'Lucas da Silva Santos', null, '2016-09-22', null, null, 'Ensino Fundamental Incompleto', 'Estudante', 'Não se aplica', 'Frequenta escola municipal')),
                // Familia 2
                new VinculoFamiliar(3, 2, 3, 'Responsável Familiar', true, '2026-02-15', null, true, null, new Pessoa(3, 'Carlos Eduardo de Oliveira', null, '1979-11-03', '98765432100', '11976543210', 'Ensino Médio Completo', 'Vendedor Ambulante', 'Autônomo', null)),
                new VinculoFamiliar(4, 2, 4, 'Cônjuge/Companheiro(a)', false, '2026-02-15', null, true, null, new Pessoa(4, 'Patrícia Gomes de Oliveira', null, '1983-04-18', '23456789012', '11976543211', 'Ensino Fundamental Completo', 'Cuidadora', 'Desempregado', null)),
                // Familia 3
                new VinculoFamiliar(5, 3, 5, 'Responsável Familiar', true, '2026-03-01', null, true, null, new Pessoa(5, 'Joana D’Arc Santos', null, '1992-08-30', '34567890123', '11965432109', 'Ensino Fundamental Incompleto', 'Desempregada', 'Desempregado', 'Gestante')),
                new VinculoFamiliar(6, 3, 6, 'Filho(a)', false, '2026-03-01', null, true, null, new Pessoa(6, 'Enzo Gabriel Santos', null, '2020-03-15', null, null, 'Educação Infantil', 'Não se aplica', 'Não se aplica', null)),
                // Familia 4
                new VinculoFamiliar(7, 4, 7, 'Responsável Familiar', true, '2025-11-20', null, true, null, new Pessoa(7, 'Antonio Marcos Pereira', null, '1968-01-25', '45678901234', '11954321098', 'Ensino Médio Incompleto', 'Aposentado por Invalidez', 'Aposentado', 'Hipertenso')),
                new VinculoFamiliar(8, 4, 8, 'Cônjuge/Companheiro(a)', false, '2025-11-20', null, true, null, new Pessoa(8, 'Cleusa Regina Pereira', null, '1972-07-14', null, '11954321098', 'Ensino Fundamental Incompleto', 'Do Lar', 'Não se aplica', null)),
                // Familia 5
                new VinculoFamiliar(9, 5, 9, 'Responsável Familiar', true, '2026-02-28', null, true, null, new Pessoa(9, 'Francisca das Chagas Costa', null, '1988-12-05', '56789012345', '11943210987', 'Ensino Fundamental Incompleto', 'Sem ocupação', 'Desempregado', 'Mãe solo')),
                new VinculoFamiliar(10, 5, 10, 'Filho(a)', false, '2026-02-28', null, true, null, new Pessoa(10, 'Cauã Felipe Costa', null, '2014-06-10', null, null, 'Ensino Fundamental Incompleto', 'Estudante', 'Não se aplica', null)),
                new VinculoFamiliar(11, 5, 11, 'Filho(a)', false, '2026-02-28', null, true, null, new Pessoa(11, 'Isabela Costa', null, '2018-09-02', null, null, 'Educação Infantil', 'Não se aplica', 'Não se aplica', null))
            ];

            Session::set(self::SESSION_KEY, $initialFamilies);
            Session::set(self::SESSION_VINCULOS_KEY, $initialVinculos);
        }
    }

    public function findAll(array $filters = []): array
    {
        $families = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['search'])) {
            $term = mb_strtolower(trim($filters['search']), 'UTF-8');
            $families = array_filter($families, function (Familia $f) use ($term) {
                return str_contains(mb_strtolower($f->pessoaReferenciaNome, 'UTF-8'), $term) ||
                       str_contains(mb_strtolower($f->codigo, 'UTF-8'), $term) ||
                       str_contains(mb_strtolower($f->bairro, 'UTF-8'), $term);
            });
        }

        if (!empty($filters['situacao'])) {
            $situacao = $filters['situacao'];
            $families = array_filter($families, fn(Familia $f) => $f->situacaoCadastral === $situacao);
        }

        if (!empty($filters['bairro'])) {
            $bairro = $filters['bairro'];
            $families = array_filter($families, fn(Familia $f) => $f->bairro === $bairro);
        }

        return array_values($families);
    }

    public function findById(int $id): ?Familia
    {
        $families = Session::get(self::SESSION_KEY, []);
        return $families[$id] ?? null;
    }

    public function findByCodigo(string $codigo): ?Familia
    {
        $families = Session::get(self::SESSION_KEY, []);
        foreach ($families as $familia) {
            if (strcasecmp($familia->codigo, $codigo) === 0) {
                return $familia;
            }
        }
        return null;
    }

    public function save(Familia $familia): Familia
    {
        $families = Session::get(self::SESSION_KEY, []);
        if ($familia->id <= 0) {
            $maxId = empty($families) ? 0 : max(array_keys($families));
            $familia->id = $maxId + 1;
            if (empty($familia->codigo)) {
                $familia->codigo = sprintf('FAM-%03d', $familia->id);
            }
        }

        $families[$familia->id] = $familia;
        Session::set(self::SESSION_KEY, $families);
        return $familia;
    }

    public function update(Familia $familia): bool
    {
        $families = Session::get(self::SESSION_KEY, []);
        if (!isset($families[$familia->id])) {
            return false;
        }

        $families[$familia->id] = $familia;
        Session::set(self::SESSION_KEY, $families);
        return true;
    }

    public function getIntegrantes(int $familiaId, bool $apenasAtivos = true): array
    {
        $vinculos = Session::get(self::SESSION_VINCULOS_KEY, []);
        $filtered = array_filter($vinculos, function (VinculoFamiliar $v) use ($familiaId, $apenasAtivos) {
            if ($familiaId > 0 && $v->familiaId !== $familiaId) {
                return false;
            }
            return $apenasAtivos ? $v->ativo : true;
        });

        return array_values($filtered);
    }

    public function addIntegrante(VinculoFamiliar $vinculo): VinculoFamiliar
    {
        $vinculos = Session::get(self::SESSION_VINCULOS_KEY, []);
        if ($vinculo->id <= 0) {
            $maxId = empty($vinculos) ? 0 : max(array_map(fn($v) => $v->id, $vinculos));
            $vinculo->id = $maxId + 1;
        }
        $vinculos[] = $vinculo;
        Session::set(self::SESSION_VINCULOS_KEY, $vinculos);
        return $vinculo;
    }

    public function updateIntegrante(VinculoFamiliar $vinculo): bool
    {
        $vinculos = Session::get(self::SESSION_VINCULOS_KEY, []);
        foreach ($vinculos as $idx => $v) {
            if ($v->id === $vinculo->id) {
                $vinculos[$idx] = $vinculo;
                Session::set(self::SESSION_VINCULOS_KEY, $vinculos);
                return true;
            }
        }
        return false;
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }

    public function countBySituacao(string $situacao): int
    {
        return count($this->findAll(['situacao' => $situacao]));
    }

    public function getBairros(): array
    {
        $families = Session::get(self::SESSION_KEY, []);
        $bairros = array_unique(array_map(fn(Familia $f) => $f->bairro, $families));
        sort($bairros);
        return $bairros;
    }
}
