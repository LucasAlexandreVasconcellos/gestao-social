<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\AuditoriaRepositoryInterface;
use App\Core\Session;
use App\Models\RegistroAuditoria;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Registra eventos de auditoria das operações de usuários no sistema.
 */
final class AuditoriaRepositoryDemo implements AuditoriaRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_auditoria';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $initial = [
                1 => new RegistroAuditoria(1, 1, 'Administrador do Sistema', '2026-03-14 09:30:15', 'Login', 'Sessao', 'USR-001', 'Início de sessão no painel institucional'),
                2 => new RegistroAuditoria(2, 3, 'Ana Paula Silva', '2026-03-05 14:22:00', 'Criar', 'AvaliacaoSocial', '3', 'Realizada avaliação de vulnerabilidade da família FAM-003'),
                3 => new RegistroAuditoria(3, 3, 'Ana Paula Silva', '2026-03-06 10:15:30', 'Criar', 'Atendimento', '3', 'Registro de atendimento emergencial para gestante Joana D’Arc'),
                4 => new RegistroAuditoria(4, 4, 'Robson Almoxarife', '2026-03-07 11:40:00', 'Efetivar Entrega', 'BeneficioEntrega', '3', 'Confirmada entrega emergencial REC-2026-078 com baixa no estoque')
            ];

            Session::set(self::SESSION_KEY, $initial);
        }
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $registros = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['entidade'])) {
            $ent = $filters['entidade'];
            $registros = array_filter($registros, fn(RegistroAuditoria $r) => $r->entidade === $ent);
        }

        if (!empty($filters['operacao'])) {
            $op = $filters['operacao'];
            $registros = array_filter($registros, fn(RegistroAuditoria $r) => $r->operacao === $op);
        }

        if (!empty($filters['usuarioId'])) {
            $uId = (int)$filters['usuarioId'];
            $registros = array_filter($registros, fn(RegistroAuditoria $r) => $r->usuarioId === $uId);
        }

        usort($registros, fn(RegistroAuditoria $a, RegistroAuditoria $b) => strcmp($b->dataHora, $a->dataHora));

        return array_slice(array_values($registros), 0, $limit);
    }

    public function save(RegistroAuditoria $registro): RegistroAuditoria
    {
        $registros = Session::get(self::SESSION_KEY, []);
        if ($registro->id <= 0) {
            $maxId = empty($registros) ? 0 : max(array_keys($registros));
            $registro->id = $maxId + 1;
        }

        $registros[$registro->id] = $registro;
        Session::set(self::SESSION_KEY, $registros);
        return $registro;
    }
}
