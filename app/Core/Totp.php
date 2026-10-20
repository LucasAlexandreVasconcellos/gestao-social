<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Utilitário nativo para Autenticação em Dois Fatores (2FA / TOTP)
 * em conformidade com as normas RFC 6238 (TOTP) e RFC 4226 (HOTP).
 * Compatível com Google Authenticator, Microsoft Authenticator, FreeOTP e Authy.
 */
class Totp
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const TIME_STEP = 30; // Janela padrão de 30 segundos
    private const DIGITS = 6;    // 6 dígitos numéricos

    /**
     * Gera um segredo aleatório codificado em Base32 (80 bits / 16 caracteres ou 160 bits / 32 caracteres).
     */
    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        $alphabetLen = strlen(self::BASE32_ALPHABET);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, $alphabetLen - 1)];
        }
        return $secret;
    }

    /**
     * Calcula o código numérico TOTP de 6 dígitos para o segredo e timestamp fornecidos.
     */
    public static function calculateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = (int)floor($timestamp / self::TIME_STEP);
        return self::calculateHotp($secret, $counter);
    }

    /**
     * Verifica se o código de 6 dígitos fornecido é válido, tolerando atrasos de relógio (+/- $discrepancy passos).
     */
    public static function verify(string $secret, string $code, int $discrepancy = 1, ?int $timestamp = null): bool
    {
        $cleanCode = preg_replace('/\D/', '', $code);
        if (strlen($cleanCode) !== self::DIGITS) {
            return false;
        }

        $timestamp = $timestamp ?? time();
        $currentCounter = (int)floor($timestamp / self::TIME_STEP);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $expectedCode = self::calculateHotp($secret, $currentCounter + $i);
            if (hash_equals($expectedCode, $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gera a URI oficial otpauth:// para escaneamento em aplicativos autenticadores.
     */
    public static function getProvisioningUri(string $accountEmail, string $secret, string $issuer = 'Gestão Social'): string
    {
        $cleanIssuer = rawurlencode($issuer);
        $cleanAccount = rawurlencode($accountEmail);
        $cleanSecret = strtoupper(trim($secret));

        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $cleanIssuer, $cleanAccount, $cleanSecret, $cleanIssuer, self::DIGITS, self::TIME_STEP
        );
    }

    /**
     * Gera uma lista de códigos de recuperação descartáveis (Backup Codes).
     */
    public static function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(bin2hex(random_bytes(2)));
            $part2 = strtoupper(bin2hex(random_bytes(2)));
            $codes[] = "{$part1}-{$part2}";
        }
        return $codes;
    }

    /**
     * Algoritmo HOTP (RFC 4226) via HMAC-SHA1.
     */
    private static function calculateHotp(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);

        // Contador como binário de 8 bytes (Big-Endian)
        $binaryCounter = pack('J', $counter);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);

        // Truncamento dinâmico (Dynamic Truncation)
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binaryCode = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binaryCode % (10 ** self::DIGITS);
        return str_pad((string)$otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decodifica string Base32 em bytes binários.
     */
    private static function base32Decode(string $base32): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($clean); $i++) {
            $val = strpos(self::BASE32_ALPHABET, $clean[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
