<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Garante que o diretório de armazenamento de sessão seja válido e gravável com permissão restrita
        $savePath = session_save_path();
        if (empty($savePath) || !is_dir($savePath) || !is_writable($savePath) || str_contains($savePath, 'PATH_PHP')) {
            $customSavePath = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
            if (!is_dir($customSavePath)) {
                @mkdir($customSavePath, 0750, true);
            }
            if (is_dir($customSavePath) && is_writable($customSavePath)) {
                session_save_path($customSavePath);
            } else {
                session_save_path(sys_get_temp_dir());
            }
        }

        // Configuração de cookies seguros de sessão
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        if (!headers_sent()) {
            session_set_cookie_params($cookieParams);
        }
        @session_start();
        self::$started = true;

        // Gerar token CSRF inicial se não existir
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Verifica timeout de inatividade e atualiza timestamp da última atividade.
     * Retorna false se a sessão expirou por ociosidade.
     */
    public static function touchAndCheckIdleTimeout(?int $timeoutSeconds = null): bool
    {
        self::start();
        if ($timeoutSeconds === null) {
            $envVal = class_exists(Env::class) ? Env::get('SESSION_IDLE_TIMEOUT', 1800) : (getenv('SESSION_IDLE_TIMEOUT') ?: 1800);
            $timeoutSeconds = max(60, (int)$envVal);
        }

        $now = time();
        $lastActivity = $_SESSION['_last_activity'] ?? null;

        if ($lastActivity !== null && ($now - (int)$lastActivity) > $timeoutSeconds) {
            self::destroy();
            self::start();
            return false;
        }

        $_SESSION['_last_activity'] = $now;
        return true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }

    public static function csrfToken(): string
    {
        self::start();
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        self::start();
        if (empty($token) || !isset($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id($deleteOldSession);
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['_last_activity'] = time();
        }
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        @session_destroy();
        self::$started = false;
    }
}
