<?php

declare(strict_types=1);

namespace App\Models;

final class RegistroAuditoria
{
    public function __construct(
        public int $id,
        public int $usuarioId,
        public string $usuarioNome,
        public string $dataHora,
        public string $operacao, // Criar, Editar, Inativar, Cancelar, Efetivar Entrega, Movimentar Estoque
        public string $entidade, // Familia, Pessoa, Avaliacao, Atendimento, Beneficio, Estoque, Doacao, Projeto
        public string|int $registroId,
        public string $detalhes = ''
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
