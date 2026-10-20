<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Core\Session;
use App\Models\Pessoa;
use App\Models\VinculoFamiliar;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Implementação temporária para consulta e cadastro de pessoas físicas.
 */
final class PessoaRepositoryDemo implements PessoaRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_pessoas';
    private const SESSION_VINCULOS_KEY = '_demo_vinculos';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $initialPessoas = [
                1 => new Pessoa(1, 'Maria da Silva', null, '1984-05-12', '12345678909', '11987654321', 'Ensino Fundamental Incompleto', 'Diarista', 'Autônomo', 'Trabalha 2x por semana'),
                2 => new Pessoa(2, 'Lucas da Silva Santos', null, '2016-09-22', null, null, 'Ensino Fundamental Incompleto', 'Estudante', 'Não se aplica', 'Frequenta escola municipal'),
                3 => new Pessoa(3, 'Carlos Eduardo de Oliveira', null, '1979-11-03', '98765432100', '11976543210', 'Ensino Médio Completo', 'Vendedor Ambulante', 'Autônomo', null),
                4 => new Pessoa(4, 'Patrícia Gomes de Oliveira', null, '1983-04-18', '23456789012', '11976543211', 'Ensino Fundamental Completo', 'Cuidadora', 'Desempregado', null),
                5 => new Pessoa(5, 'Joana D’Arc Santos', null, '1992-08-30', '34567890123', '11965432109', 'Ensino Fundamental Incompleto', 'Desempregada', 'Desempregado', 'Gestante'),
                6 => new Pessoa(6, 'Enzo Gabriel Santos', null, '2020-03-15', null, null, 'Educação Infantil', 'Não se aplica', 'Não se aplica', null),
                7 => new Pessoa(7, 'Antonio Marcos Pereira', null, '1968-01-25', '45678901234', '11954321098', 'Ensino Médio Incompleto', 'Aposentado por Invalidez', 'Aposentado', 'Hipertenso'),
                8 => new Pessoa(8, 'Cleusa Regina Pereira', null, '1972-07-14', null, '11954321098', 'Ensino Fundamental Incompleto', 'Do Lar', 'Não se aplica', null),
                9 => new Pessoa(9, 'Francisca das Chagas Costa', null, '1988-12-05', '56789012345', '11943210987', 'Ensino Fundamental Incompleto', 'Sem ocupação', 'Desempregado', 'Mãe solo'),
                10 => new Pessoa(10, 'Cauã Felipe Costa', null, '2014-06-10', null, null, 'Ensino Fundamental Incompleto', 'Estudante', 'Não se aplica', null),
                11 => new Pessoa(11, 'Isabela Costa', null, '2018-09-02', null, null, 'Educação Infantil', 'Não se aplica', 'Não se aplica', null)
            ];

            Session::set(self::SESSION_KEY, $initialPessoas);
        }
    }

    public function findAll(array $filters = []): array
    {
        $this->ensureSeedData();
        $pessoas = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['search'])) {
            $term = mb_strtolower(trim($filters['search']), 'UTF-8');
            $pessoas = array_filter($pessoas, function (Pessoa $p) use ($term) {
                return str_contains(mb_strtolower($p->nomeCompleto, 'UTF-8'), $term) ||
                       str_contains((string)$p->cpf, $term) ||
                       str_contains(mb_strtolower((string)$p->ocupacao, 'UTF-8'), $term);
            });
        }

        return array_values($pessoas);
    }

    public function findById(int $id): ?Pessoa
    {
        $this->ensureSeedData();
        $pessoas = Session::get(self::SESSION_KEY, []);
        return $pessoas[$id] ?? null;
    }

    public function findByCpf(string $cpf): ?Pessoa
    {
        $this->ensureSeedData();
        $clean = preg_replace('/\D/', '', $cpf);
        $pessoas = Session::get(self::SESSION_KEY, []);
        foreach ($pessoas as $pessoa) {
            if ($pessoa->cpf && preg_replace('/\D/', '', $pessoa->cpf) === $clean) {
                return $pessoa;
            }
        }
        return null;
    }

    public function search(string $term): array
    {
        return $this->findAll(['search' => $term]);
    }

    public function save(Pessoa $pessoa): Pessoa
    {
        $pessoas = Session::get(self::SESSION_KEY, []);
        if ($pessoa->id <= 0) {
            $maxId = empty($pessoas) ? 0 : max(array_keys($pessoas));
            $pessoa->id = $maxId + 1;
        }

        $pessoas[$pessoa->id] = $pessoa;
        Session::set(self::SESSION_KEY, $pessoas);
        return $pessoa;
    }

    public function update(Pessoa $pessoa): bool
    {
        $pessoas = Session::get(self::SESSION_KEY, []);
        if (!isset($pessoas[$pessoa->id])) {
            return false;
        }

        $pessoas[$pessoa->id] = $pessoa;
        Session::set(self::SESSION_KEY, $pessoas);
        return true;
    }

    public function getHistoricoVinculosFamiliares(int $pessoaId): array
    {
        $vinculos = Session::get(self::SESSION_VINCULOS_KEY, []);
        $pessoaVinculos = array_filter($vinculos, fn(VinculoFamiliar $v) => $v->pessoaId === $pessoaId);
        return array_values($pessoaVinculos);
    }

    public function countTotal(): int
    {
        return count(Session::get(self::SESSION_KEY, []));
    }
}
