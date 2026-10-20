<?php

declare(strict_types=1);

namespace App\Repositories\Demo;

use App\Contracts\Repositories\NotificacaoRepositoryInterface;
use App\Core\Session;
use App\Models\Notificacao;

final class NotificacaoRepositoryDemo implements NotificacaoRepositoryInterface
{
    public const IS_DEMO = true;
    private const SESSION_KEY = '_demo_notificacoes';

    public function __construct()
    {
        $this->ensureSeedData();
    }

    private function ensureSeedData(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            $initial = [
                1 => new Notificacao(
                    id: 1,
                    entidadeTipo: 'atendimento',
                    entidadeId: 1,
                    destinatarioNome: 'Maria Silva Sauro',
                    destinatarioContato: '(61) 98888-1234',
                    canal: 'whatsapp',
                    tipoMensagem: 'lembrete_atendimento',
                    mensagem: 'Olá, Maria Silva Sauro! Lembramos do seu agendamento no Centro Social amanhã às 14:00 com a Assistente Social. Favor trazer documento com foto e comprovante de residência.',
                    status: 'enviado',
                    respostaProvedor: 'SIMULADO_OK: ID Msg 729182',
                    usuarioId: 1,
                    dataEnvio: date('Y-m-d H:i:s', strtotime('-1 day'))
                ),
            ];
            Session::set(self::SESSION_KEY, $initial);
        }
    }

    public function findById(int $id): ?Notificacao
    {
        $notifs = Session::get(self::SESSION_KEY, []);
        return $notifs[$id] ?? null;
    }

    public function findAll(array $filters = []): array
    {
        $notifs = Session::get(self::SESSION_KEY, []);
        $results = [];

        foreach ($notifs as $n) {
            if (!empty($filters['entidadeTipo']) && $n->entidadeTipo !== $filters['entidadeTipo']) {
                continue;
            }
            if (!empty($filters['entidadeId']) && $n->entidadeId !== (int)$filters['entidadeId']) {
                continue;
            }
            if (!empty($filters['canal']) && $n->canal !== $filters['canal']) {
                continue;
            }
            $results[] = $n;
        }

        usort($results, fn($a, $b) => strcmp($b->dataEnvio, $a->dataEnvio));
        return $results;
    }

    public function save(Notificacao $notificacao): Notificacao
    {
        $notifs = Session::get(self::SESSION_KEY, []);
        if ($notificacao->id <= 0) {
            $nextId = empty($notifs) ? 1 : max(array_keys($notifs)) + 1;
            $notificacao->id = $nextId;
        }
        $notifs[$notificacao->id] = $notificacao;
        Session::set(self::SESSION_KEY, $notifs);
        return $notificacao;
    }
}
