<?php

declare(strict_types=1);

namespace App\Helpers;

final class FormatHelper
{
    public static function date(?string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '-';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '-';
    }

    public static function datetime(?string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if (empty($datetime)) {
            return '-';
        }
        $timestamp = strtotime($datetime);
        return $timestamp ? date($format, $timestamp) : '-';
    }

    public static function money(float|int|null $amount): string
    {
        if ($amount === null) {
            return 'R$ 0,00';
        }
        return 'R$ ' . number_format((float)$amount, 2, ',', '.');
    }

    public static function cpf(?string $cpf): string
    {
        if (empty($cpf)) {
            return 'Não informado';
        }
        $clean = preg_replace('/\D/', '', $cpf);
        if (strlen($clean) !== 11) {
            return $cpf;
        }
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $clean);
    }

    public static function cep(?string $cep): string
    {
        if (empty($cep)) {
            return '-';
        }
        $clean = preg_replace('/\D/', '', $cep);
        if (strlen($clean) !== 8) {
            return $cep;
        }
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $clean);
    }

    public static function phone(?string $phone): string
    {
        if (empty($phone)) {
            return '-';
        }
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $clean);
        }
        if (strlen($clean) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $clean);
        }
        return $phone;
    }

    public static function badgeStatus(string $status): string
    {
        $statusLower = mb_strtolower(trim($status), 'UTF-8');
        $class = match ($statusLower) {
            'ativa', 'concluído', 'concluida', 'efetivada', 'atendida' => 'badge-success',
            'em análise', 'em analise', 'pendente', 'em andamento', 'planejada', 'preparada' => 'badge-warning',
            'suspensa', 'cancelado', 'cancelada', 'ausente' => 'badge-danger',
            'encerrada', 'inativa' => 'badge-secondary',
            default => 'badge-info',
        };

        return '<span class="badge ' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    public static function badgeVulnerability(string $level): string
    {
        $levelLower = mb_strtolower(trim($level), 'UTF-8');
        $class = match ($levelLower) {
            'crítica', 'critica' => 'badge-danger-glow',
            'alta' => 'badge-danger',
            'média', 'media' => 'badge-warning',
            'baixa' => 'badge-info',
            default => 'badge-secondary',
        };

        return '<span class="badge ' . $class . '">Vulnerabilidade ' . htmlspecialchars($level, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    public static function situacaoCadastralBadge(string $status): string
    {
        return self::badgeStatus($status);
    }

    public static function vulnerabilidadeBadge(string $level): string
    {
        return self::badgeVulnerability($level);
    }

    /**
     * Pseudonimização / Mascaramento de dados pessoais em conformidade com o Art. 13 da LGPD
     * Exemplo: "Maria de Souza Silva" -> "M. d. S. S." ou "M*** S***"
     */
    public static function anonymizeName(?string $name): string
    {
        if (empty($name)) {
            return 'Identidade Preservada (LGPD)';
        }

        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) <= 1) {
            $first = $parts[0] ?? '';
            return (mb_strlen($first) > 0 ? mb_substr($first, 0, 1) . '***' : '***') . ' [LGPD]';
        }

        // Mantém as iniciais de cada nome para correlação técnica sem exposição nominal pública
        $maskedParts = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) > 0) {
                $maskedParts[] = mb_strtoupper(mb_substr($p, 0, 1)) . '.';
            }
        }

        return implode(' ', $maskedParts) . ' [LGPD]';
    }

    /**
     * Mascaramento de CPF em conformidade com a LGPD
     * Exemplo: "123.456.789-00" -> "***.456.789-**"
     */
    public static function maskCpf(?string $cpf): string
    {
        if (empty($cpf) || $cpf === 'Não informado') {
            return 'Não informado';
        }

        $clean = preg_replace('/\D/', '', $cpf);
        if (strlen($clean) !== 11) {
            return '***.***.***-**';
        }

        return '***.' . substr($clean, 3, 3) . '.' . substr($clean, 6, 3) . '-**';
    }
}

