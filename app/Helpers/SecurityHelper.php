<?php

declare(strict_types=1);

namespace App\Helpers;

final class SecurityHelper
{
    public static function escape(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function sanitizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(strip_tags($value));
    }

    public static function maskCpf(?string $cpf): string
    {
        return FormatHelper::maskCpf($cpf);
    }
}
