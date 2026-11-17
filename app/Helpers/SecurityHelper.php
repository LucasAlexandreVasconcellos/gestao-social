<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Env;

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

    /**
     * Impede Open Redirect garantindo que o destino seja um caminho interno relativo começando com "/".
     */
    public static function safeRedirectPath(?string $url, string $default = '/dashboard'): string
    {
        if (empty($url)) {
            return $default;
        }
        $trimmed = trim($url);
        if (!str_starts_with($trimmed, '/') || str_starts_with($trimmed, '//') || str_contains($trimmed, "\r") || str_contains($trimmed, "\n")) {
            return $default;
        }
        return $trimmed;
    }

    /**
     * Retorna a chave secreta institucional para assinaturas HMAC e criptografia de segredos em repouso.
     */
    public static function getSigningKey(): string
    {
        $key = class_exists(Env::class) ? (string)Env::get('APP_SIGNING_KEY', '') : (string)(getenv('APP_SIGNING_KEY') ?: '');
        if ($key !== '' && $key !== 'SUBSTITUA_POR_CHAVE_ALEATORIA_64_HEX') {
            return hash('sha256', $key, true);
        }
        // Fallback determinístico apenas para testes/homologação local sem .env configurado
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        return hash('sha256', 'gestao-social-local-fallback|' . $root, true);
    }

    /**
     * Gera assinatura HMAC-SHA256 curta (16 chars hex uppercase) para validação documental via QR Code.
     */
    public static function signDocumentPayload(string $payload): string
    {
        $hmac = hash_hmac('sha256', $payload, self::getSigningKey());
        return strtoupper(substr($hmac, 0, 16));
    }

    public static function verifyDocumentSignature(string $payload, string $providedHash): bool
    {
        $expected = self::signDocumentPayload($payload);
        return hash_equals($expected, strtoupper(trim($providedHash)));
    }

    /**
     * Criptografa segredos sensíveis (ex: TOTP secret) em repouso com AES-256-GCM.
     */
    public static function encryptSecret(string $plaintext): string
    {
        if (!function_exists('openssl_encrypt')) {
            return 'plain:' . base64_encode($plaintext);
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', self::getSigningKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return 'plain:' . base64_encode($plaintext);
        }
        return 'enc:' . base64_encode($iv . $tag . $cipher);
    }

    /**
     * Descriptografa segredo TOTP (suporta formato legado em texto puro para retrocompatibilidade).
     */
    public static function decryptSecret(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }
        if (str_starts_with($stored, 'plain:')) {
            $decoded = base64_decode(substr($stored, 6), true);
            return $decoded !== false ? $decoded : null;
        }
        if (str_starts_with($stored, 'enc:') && function_exists('openssl_decrypt')) {
            $raw = base64_decode(substr($stored, 4), true);
            if ($raw === false || strlen($raw) <= 28) {
                return null;
            }
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::getSigningKey(), OPENSSL_RAW_DATA, $iv, $tag);
            return $plain !== false ? $plain : null;
        }
        // Legado em texto puro
        return $stored;
    }
}
