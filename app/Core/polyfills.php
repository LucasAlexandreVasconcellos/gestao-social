<?php

declare(strict_types=1);

/**
 * Polyfills para funções de string multibyte (mbstring)
 * 
 * Garante compatibilidade universal em ambientes onde a extensão mbstring do PHP
 * não estiver instalada ou habilitada no php.ini.
 */

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string {
        return strtolower($string);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string {
        return strtoupper($string);
    }
}

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int {
        return strlen($string);
    }
}

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $string, int $start, int $width, string $trimMarker = '', ?string $encoding = null): string {
        if (strlen($string) <= $width) {
            return $string;
        }
        return substr($string, $start, max(0, $width - strlen($trimMarker))) . $trimMarker;
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

