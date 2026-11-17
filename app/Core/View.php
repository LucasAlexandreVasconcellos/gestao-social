<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\SecurityHelper;
use App\Models\Usuario;

final class View
{
    private static string $viewsPath;

    public static function init(string $viewsPath): void
    {
        self::$viewsPath = rtrim($viewsPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $templateFile = self::$viewsPath . str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("View template não encontrada: {$templateFile}");
        }

        extract($data);

        $e = fn(?string $val): string => SecurityHelper::escape($val ?? '');
        $csrfField = fn(): string => '<input type="hidden" name="_csrf_token" value="' . Session::csrfToken() . '">';
        $csrfToken = fn(): string => Session::csrfToken();
        $sessionUser = Session::get('user');
        if (!isset($user)) {
            $user = $sessionUser;
        }

        $usuarioObj = null;
        if (is_array($sessionUser) && !empty($sessionUser['id'])) {
            $usuarioObj = new Usuario(
                (int)$sessionUser['id'],
                (string)($sessionUser['nome'] ?? ''),
                (string)($sessionUser['email'] ?? ''),
                (string)($sessionUser['perfil'] ?? Usuario::PERFIL_CONSULTA),
                (bool)($sessionUser['ativo'] ?? true),
                $sessionUser['ultimoAcesso'] ?? null,
                null,
                (bool)($sessionUser['doisFatoresAtivo'] ?? false),
                null,
                [],
                (string)($sessionUser['login'] ?? ''),
                (bool)($sessionUser['trocarSenhaObrigatorio'] ?? false)
            );
        }

        $can = static function (string $permission) use ($usuarioObj): bool {
            return $usuarioObj !== null && $usuarioObj->can($permission);
        };

        $orgName = (string)Env::get('APP_ORG_NAME', 'Secretaria Municipal de Assistência Social');
        $unitName = (string)Env::get('APP_UNIT_NAME', 'Gestão Socioassistencial — SUAS');

        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::$viewsPath . str_replace('.', DIRECTORY_SEPARATOR, $layout) . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("View layout não encontrado: {$layoutFile}");
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    public static function renderPartial(string $template, array $data = []): string
    {
        return self::render($template, $data, null);
    }
}
