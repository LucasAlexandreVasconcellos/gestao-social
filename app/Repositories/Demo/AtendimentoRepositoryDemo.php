<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Core\Session;
use App\Models\Atendimento;
use App\Models\Encaminhamento;

/**
 * REPOSITÓRIO DEMONSTRATIVO EM MEMÓRIA (NÃO PERSISTENTE)
 * 
 * Simula registros de atendimentos, visitas domiciliares e encaminhamentos.
 */
final class AtendimentoRepositoryDemo implements AtendimentoRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_atendimentos';
    private const SESSION_ENC_KEY = '_demo_encaminhamentos';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $enc1 = new Encaminhamento(1, 1, 'CRAS Vila Esperança', 'Inclusão no Cadastro Único (CadÚnico)', 'Ana Paula Silva', '2026-01-20', 'Concluído', 'Família cadastrada com sucesso.');
            $enc2 = new Encaminhamento(2, 3, 'UBS Morro Alto', 'Pré-natal prioritário e suplementação de ferro', 'Ana Paula Silva', '2026-03-06', 'Em Acompanhamento', 'Primeira consulta agendada para 12/03.');
            $enc3 = new Encaminhamento(3, 3, 'Centro de Referência de Alimentos', 'Solicitação de benefício eventual de cesta de alimentos', 'Ana Paula Silva', '2026-03-06', 'Pendente', null);

            $initial = [
                1 => new Atendimento(
                    1, 1, 1, 'Atendimento Social', '2026-01-20', 'Ana Paula Silva (Assistente Social)',
                    'Acolhimento da Sra. Maria da Silva para atualização cadastral e diagnóstico de necessidades.',
                    'Orientações sobre documentação civil e solicitação de cesta de alimentos mensal.',
                    '2026-04-20', 'Concluído', '2026-01-20', [$enc1]
                ),
                2 => new Atendimento(
                    2, 2, 3, 'Orientação', '2026-02-22', 'Marcos Vinicius (Assistente Social)',
                    'Orientação sobre cursos de capacitação profissional e direitos trabalhistas.',
                    'Entregue material informativo sobre oficinas profissionalizantes da entidade.',
                    null, 'Concluído', '2026-02-22', []
                ),
                3 => new Atendimento(
                    3, 3, 5, 'Atendimento Emergencial', '2026-03-06', 'Ana Paula Silva (Assistente Social)',
                    'Atendimento emergencial da gestante Joana, sem renda e sem provimentos alimentares na residência.',
                    'Concessão imediata de auxílio alimentar de emergência e articulação com a rede de saúde local.',
                    '2026-03-20', 'Pendente', null, [$enc2, $enc3]
                ),
                4 => new Atendimento(
                    4, 5, 9, 'Visita Domiciliar', '2026-03-08', 'Marcos Vinicius (Assistente Social)',
                    'Visita domiciliar para averiguação in loco da situação de vulnerabilidade da família Costa.',
                    'Constatada extrema penúria, sem gás de cozinha e sem estoque de alimentos. Agendada distribuição emergencial.',
                    '2026-03-22', 'Em Andamento', null, []
                )
            ];

            Session::set(self::SESSION_KEY, $initial);
            Session::set(self::SESSION_ENC_KEY, [$enc1, $enc2, $enc3]);
        }
    }

    public function findAll(array $filters = []): array
    {
        $atendimentos = Session::get(self::SESSION_KEY, []);

        if (!empty($filters['familiaId'])) {
            $famId = (int)$filters['familiaId'];
            $atendimentos = array_filter($atendimentos, fn(Atendimento $a) => $a->familiaId === $famId);
        }

        if (!empty($filters['situacao'])) {
            $situacao = $filters['situacao'];
            $atendimentos = array_filter($atendimentos, fn(Atendimento $a) => $a->situacao === $situacao);
        }

        if (!empty($filters['tipo'])) {
            $tipo = $filters['tipo'];
            $atendimentos = array_filter($atendimentos, fn(Atendimento $a) => $a->tipo === $tipo);
        }

        // Ordenar por data decrescente
        usort($atendimentos, fn(Atendimento $a, Atendimento $b) => strcmp($b->dataAtendimento, $a->dataAtendimento));

        return array_values($atendimentos);
    }

    public function findById(int $id): ?Atendimento
    {
        $atendimentos = Session::get(self::SESSION_KEY, []);
        return $atendimentos[$id] ?? null;
    }

    public function findByFamiliaId(int $familiaId): array
    {
        return $this->findAll(['familiaId' => $familiaId]);
    }

    public function save(Atendimento $atendimento): Atendimento
    {
        $atendimentos = Session::get(self::SESSION_KEY, []);
        if ($atendimento->id <= 0) {
            $maxId = empty($atendimentos) ? 0 : max(array_keys($atendimentos));
            $atendimento->id = $maxId + 1;
        }

        $atendimentos[$atendimento->id] = $atendimento;
        Session::set(self::SESSION_KEY, $atendimentos);
        return $atendimento;
    }

    public function update(Atendimento $atendimento): bool
    {
        $atendimentos = Session::get(self::SESSION_KEY, []);
        if (!isset($atendimentos[$atendimento->id])) {
            return false;
        }

        $atendimentos[$atendimento->id] = $atendimento;
        Session::set(self::SESSION_KEY, $atendimentos);
        return true;
    }

    public function addEncaminhamento(Encaminhamento $encaminhamento): Encaminhamento
    {
        $encs = Session::get(self::SESSION_ENC_KEY, []);
        if ($encaminhamento->id <= 0) {
            $maxId = empty($encs) ? 0 : max(array_map(fn($e) => $e->id, $encs));
            $encaminhamento->id = $maxId + 1;
        }
        $encs[] = $encaminhamento;
        Session::set(self::SESSION_ENC_KEY, $encs);

        // Atualiza também na lista do atendimento
        $atendimento = $this->findById($encaminhamento->atendimentoId);
        if ($atendimento) {
            $atendimento->encaminhamentos[] = $encaminhamento;
            $this->update($atendimento);
        }

        return $encaminhamento;
    }

    public function getEncaminhamentos(int $atendimentoId): array
    {
        $encs = Session::get(self::SESSION_ENC_KEY, []);
        $filtered = array_filter($encs, fn(Encaminhamento $e) => $e->atendimentoId === $atendimentoId);
        return array_values($filtered);
    }

    public function countPendentes(): int
    {
        $atendimentos = Session::get(self::SESSION_KEY, []);
        $count = 0;
        foreach ($atendimentos as $a) {
            if ($a->situacao === 'Pendente' || $a->situacao === 'Em Andamento') {
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
