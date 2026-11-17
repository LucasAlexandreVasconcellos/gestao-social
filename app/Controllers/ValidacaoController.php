<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Services\PdfService;

final class ValidacaoController
{
    public function __construct(
        private PdfService $pdfService
    ) {}

    public function index(Request $request): string
    {
        $tipo = trim((string)$request->query('tipo', ''));
        $codigo = trim((string)$request->query('codigo', ''));
        $hash = trim((string)$request->query('h', ''));

        $resultado = null;
        if ($tipo !== '' && $codigo !== '') {
            $resultado = $this->pdfService->verificarDocumentoPublico($tipo, $codigo, $hash);
        }

        return View::render('validacao/index', [
            'tipo' => $tipo,
            'codigo' => $codigo,
            'hash' => $hash,
            'resultado' => $resultado,
        ], 'layouts/auth');
    }

    public function verificar(Request $request): string
    {
        $tipo = (string)$request->param('tipo', '');
        $codigo = (string)$request->param('codigo', '');
        $hash = trim((string)$request->query('h', ''));

        $resultado = $this->pdfService->verificarDocumentoPublico($tipo, $codigo, $hash);

        return View::render('validacao/index', [
            'tipo' => $tipo,
            'codigo' => $codigo,
            'hash' => $hash,
            'resultado' => $resultado,
        ], 'layouts/auth');
    }
}

