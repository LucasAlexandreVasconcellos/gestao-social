<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\FormatHelper;
use App\Helpers\SecurityHelper;

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

        // Extrai os dados para o escopo da view
        extract($data);

        // Funções auxiliares disponíveis nas views
        $e = fn(?string $val): string => SecurityHelper::escape($val ?? '');
        $csrfField = fn(): string => '<input type="hidden" name="_csrf_token" value="' . Session::csrfToken() . '">';
        $csrfToken = fn(): string => Session::csrfToken();
        $user = Session::get('user');

        // Captura o conteúdo do template filho
        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        // Se layout for nulo, retorna apenas o conteúdo
        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::$viewsPath . str_replace('.', DIRECTORY_SEPARATOR, $layout) . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("View layout não encontrado: {$layoutFile}");
        }

        // Renderiza o layout passando $content e os dados
        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    public static function renderPartial(string $template, array $data = []): string
    {
        return self::render($template, $data, null);
    }
}
