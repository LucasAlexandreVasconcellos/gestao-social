<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Carregador simples e seguro de arquivos de ambiente (.env).
 */
final class Env
{
    private static bool $loaded = false;

    /**
     * Carrega as variáveis de um arquivo .env para putenv(), $_ENV e $_SERVER.
     */
    public static function load(string $filePath): void
    {
        if (self::$loaded || !file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove aspas simples ou duplas se houver
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Obtém o valor de uma variável de ambiente com fallback padrão.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
