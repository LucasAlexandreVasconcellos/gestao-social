<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AuditoriaRepositoryInterface;
use App\Core\Session;
use App\Models\RegistroAuditoria;

final class AuditLogService
{
    public function __construct(
        private AuditoriaRepositoryInterface $auditoriaRepo
    ) {}

    public function log(string $operacao, string $entidade, string|int $registroId, string $detalhes = ''): void
    {
        $user = Session::get('user');
        $userId = $user['id'] ?? 0;
        $userName = $user['nome'] ?? 'Sistema / Visitante';

        // Sanitiza detalhes para garantir que nenhuma senha ou token seja registrado
        $sanitizedDetalhes = $this->sanitizeLogDetails($detalhes);

        $registro = new RegistroAuditoria(
            0,
            $userId,
            $userName,
            date('Y-m-d H:i:s'),
            $operacao,
            $entidade,
            $registroId,
            $sanitizedDetalhes
        );

        $this->auditoriaRepo->save($registro);
    }

    private function sanitizeLogDetails(string $text): string
    {
        // Remove padrões comuns de campos confidenciais
        $patterns = [
            '/password\s*[:=]\s*[^\s,]+/i' => 'password: [REDACTED]',
            '/senha\s*[:=]\s*[^\s,]+/i' => 'senha: [REDACTED]',
            '/token\s*[:=]\s*[^\s,]+/i' => 'token: [REDACTED]',
            '/_csrf_token\s*[:=]\s*[^\s,]+/i' => '_csrf_token: [REDACTED]'
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text);
    }

    public function listarTodos(array $filters = [], int $limit = 100): array
    {
        return $this->auditoriaRepo->findAll($filters, $limit);
    }
}
