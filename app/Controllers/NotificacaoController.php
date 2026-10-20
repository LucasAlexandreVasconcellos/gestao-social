<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\NotificacaoService;

final class NotificacaoController
{
    public function __construct(
        private NotificacaoService $notificacaoService
    ) {}

    public function enviar(Request $request): void
    {
        $dados = $request->allPost();
        $redirect = (string)$request->post('redirect', '/dashboard');

        $result = $this->notificacaoService->enviar($dados);

        if (!$result['success']) {
            $msg = implode(' ', $result['errors'] ?? ['Erro ao processar envio da mensagem.']);
            Session::flash('error', $msg);
            Response::redirect($redirect);
            return;
        }

        $canal = strtoupper((string)($dados['canal'] ?? 'WHATSAPP'));
        Session::flash('success', "Notificação enviada com sucesso para {$dados['destinatarioNome']} via {$canal}!");
        Response::redirect($redirect);
    }

    public function historico(Request $request): string
    {
        $filters = [
            'canal' => $request->query('canal'),
            'entidadeTipo' => $request->query('entidadeTipo')
        ];

        $notificacoes = $this->notificacaoService->listarTodas(array_filter($filters));

        return View::render('notificacoes/index', [
            'notificacoes' => $notificacoes,
            'filters' => $filters
        ]);
    }
}
