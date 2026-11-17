<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

final class CsrfHelper
{
    public static function token(): string
    {
        return Session::csrfToken();
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

