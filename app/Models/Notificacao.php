<?php

declare(strict_types=1);

namespace App\Models;

final class Notificacao
{
    public function __construct(
        public int $id,
        public string $entidadeTipo,
        public ?int $entidadeId,
        public string $destinatarioNome,
        public string $destinatarioContato,
        public string $canal, // whatsapp, sms, email
        public string $tipoMensagem, // lembrete_atendimento, aviso_beneficio, convocacao_projeto, geral
        public string $mensagem,
        public string $status = 'enviado', // enviado, simulado, falha
        public ?string $respostaProvedor = null,
        public ?int $usuarioId = null,
        public string $dataEnvio = ''
    ) {
        if ($this->dataEnvio === '') {
            $this->dataEnvio = date('Y-m-d H:i:s');
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
