<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\NotificacaoRepositoryInterface;
use App\Core\Session;
use App\Models\Notificacao;

final class NotificacaoService
{
    public function __construct(
        private NotificacaoRepositoryInterface $notificacaoRepo,
        private AuditLogService $auditLog
    ) {}

    public function enviar(array $dados): array
    {
        $destinatarioNome = trim((string)($dados['destinatarioNome'] ?? ''));
        $destinatarioContato = trim((string)($dados['destinatarioContato'] ?? ''));
        $canal = strtolower(trim((string)($dados['canal'] ?? 'whatsapp')));
        $tipoMensagem = trim((string)($dados['tipoMensagem'] ?? 'personalizado'));
        $mensagem = trim((string)($dados['mensagem'] ?? ''));
        $entidadeTipo = trim((string)($dados['entidadeTipo'] ?? 'geral'));
        $entidadeId = !empty($dados['entidadeId']) ? (int)$dados['entidadeId'] : null;

        $errors = [];
        if (empty($destinatarioNome)) {
            $errors[] = 'O nome do destinatário é obrigatório.';
        }
        if (empty($destinatarioContato)) {
            $errors[] = 'O telefone / contato do destinatário é obrigatório.';
        }
        if (empty($mensagem)) {
            $errors[] = 'O texto da mensagem não pode ficar vazio.';
        }
        if (!in_array($canal, ['whatsapp', 'sms', 'email'], true)) {
            $canal = 'whatsapp';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $usuario = Session::get('user');
        $usuarioId = $usuario ? (int)$usuario['id'] : null;

        // Disparo via Provedor (Simulado ou Webhook)
        $webhookUrl = getenv('NOTIFICACOES_WEBHOOK_URL') ?: null;
        $status = 'enviado';
        $respostaProvedor = 'SIMULADO_OK: Protocolo ' . strtoupper(bin2hex(random_bytes(4)));

        if ($webhookUrl && filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            try {
                $payload = json_encode([
                    'canal' => $canal,
                    'destinatario' => [
                        'nome' => $destinatarioNome,
                        'contato' => $destinatarioContato
                    ],
                    'tipo' => $tipoMensagem,
                    'mensagem' => $mensagem,
                    'timestamp' => date('c')
                ]);

                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                        'content' => $payload,
                        'timeout' => 5
                    ]
                ]);

                $response = @file_get_contents($webhookUrl, false, $ctx);
                if ($response !== false) {
                    $respostaProvedor = 'WEBHOOK_OK: ' . substr($response, 0, 100);
                } else {
                    $status = 'falha';
                    $respostaProvedor = 'WEBHOOK_ERROR: Sem resposta do gateway';
                }
            } catch (\Throwable $e) {
                $status = 'falha';
                $respostaProvedor = 'EXCECAO: ' . substr($e->getMessage(), 0, 100);
            }
        }

        $notificacao = new Notificacao(
            id: 0,
            entidadeTipo: $entidadeTipo,
            entidadeId: $entidadeId,
            destinatarioNome: $destinatarioNome,
            destinatarioContato: $destinatarioContato,
            canal: $canal,
            tipoMensagem: $tipoMensagem,
            mensagem: $mensagem,
            status: $status,
            respostaProvedor: $respostaProvedor,
            usuarioId: $usuarioId,
            dataEnvio: date('Y-m-d H:i:s')
        );

        $salva = $this->notificacaoRepo->save($notificacao);

        $this->auditLog->log(
            'Disparo de Notificação',
            'Notificacao',
            $salva->id,
            "Mensagem enviada via [{$canal}] para {$destinatarioNome} ({$destinatarioContato}). Tipo: {$tipoMensagem}."
        );

        return [
            'success' => true,
            'notificacao' => $salva
        ];
    }

    public function listarPorEntidade(string $entidadeTipo, int $entidadeId): array
    {
        return $this->notificacaoRepo->findAll([
            'entidadeTipo' => $entidadeTipo,
            'entidadeId' => $entidadeId
        ]);
    }

    public function listarTodas(array $filters = []): array
    {
        return $this->notificacaoRepo->findAll($filters);
    }

    public static function template(string $tipo, array $vars): string
    {
        $nome = $vars['nome'] ?? 'Cidadão';

        return match ($tipo) {
            'lembrete_atendimento' => sprintf(
                "Olá, %s! Lembramos do seu atendimento agendado no Centro Social para o dia %s às %s. Favor comparecer com documento de identificação original com foto.",
                $nome,
                $vars['data'] ?? 'amanhã',
                $vars['hora'] ?? '14:00'
            ),
            'aviso_beneficio' => sprintf(
                "Olá, %s! Seu benefício assistencial (%s) foi deferido e está pronto para retirada no setor de entregas. Apresente este comunicado ou seu CPF.",
                $nome,
                $vars['beneficio'] ?? 'auxílio emergencial'
            ),
            'convocacao_projeto' => sprintf(
                "Olá, %s! Sua inscrição na oficina/projeto '%s' foi confirmada! As atividades começam no dia %s. Aguardamos sua presença!",
                $nome,
                $vars['projeto'] ?? 'Projeto Social',
                $vars['data'] ?? 'em breve'
            ),
            default => sprintf("Olá, %s! Informamos que há uma atualização referente ao seu cadastro junto à Assistência Social.", $nome)
        };
    }
}
