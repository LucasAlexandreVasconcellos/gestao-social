<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\PaginationHelper;
use App\Helpers\SecurityHelper;
use App\Services\NotificacaoService;

final class NotificacaoController
{
    public function __construct(
        private NotificacaoService $notificacaoService
    ) {}

    public function enviar(Request $request): void
    {
        $dados = $request->allPost();
        $redirect = SecurityHelper::safeRedirectPath((string)$request->post('redirect', '/dashboard'));

        $result = $this->notificacaoService->enviar($dados);

        if (!$result['success']) {
            $msg = implode(' ', $result['errors'] ?? ['Erro ao processar envio da mensagem.']);
            Session::flash('error', $msg);
            Response::redirect($redirect);
            return;
        }

        $notificacao = $result['notificacao'];
        $canal = strtoupper((string)($dados['canal'] ?? 'WHATSAPP'));

        if ($notificacao->status === 'simulado') {
            Session::flash('info', "Registro de comunicação salvo em modo simulado (gateway externo não configurado) para {$dados['destinatarioNome']} via {$canal}.");
        } elseif ($notificacao->status === 'falha') {
            Session::flash('error', "Não foi possível entregar a notificação pelo gateway HTTP configurado: {$notificacao->respostaProvedor}");
        } else {
            Session::flash('success', "Notificação enviada com sucesso para {$dados['destinatarioNome']} via {$canal}!");
        }

        Response::redirect($redirect);
    }

    public function historico(Request $request): string
    {
        $filters = [
            'canal' => $request->query('canal'),
            'entidadeTipo' => $request->query('entidadeTipo')
        ];

        $notificacoes = $this->notificacaoService->listarTodas(array_filter($filters));
        $page = max(1, (int)$request->query('page', 1));
        $pag = PaginationHelper::paginate($notificacoes, $page, 20);

        return View::render('notificacoes/index', [
            'notificacoes' => $pag['items'],
            'pagination' => $pag,
            'filters' => $filters
        ]);
    }
}
