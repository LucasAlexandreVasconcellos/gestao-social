<?php

declare(strict_types=1);

/**
 * Script de Auditoria HTTP End-to-End via PHP Streams Puros (Sem dependência de cURL)
 */

$baseUrl = 'http://localhost:8000';
$sessionCookie = '';

function httpRequest(string $method, string $url, array $data = [], string &$sessionCookie = ''): array
{
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Auditoria/1.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
    ];

    if (!empty($sessionCookie)) {
        $headers[] = "Cookie: {$sessionCookie}";
    }

    $content = '';
    if ($method === 'POST') {
        $content = http_build_query($data);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Content-Length: ' . strlen($content);
    }

    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $content,
            'follow_location' => 0,
            'ignore_errors' => true,
            'timeout' => 5
        ]
    ];

    $context = stream_context_create($opts);
    $fp = @fopen($url, 'r', false, $context);
    if (!$fp) {
        return ['code' => 0, 'headers' => [], 'body' => ''];
    }

    $meta = stream_get_meta_data($fp);
    $responseHeaders = $meta['wrapper_data'] ?? [];
    $body = stream_get_contents($fp);
    fclose($fp);

    // Captura código de status HTTP da primeira linha
    $statusCode = 0;
    if (!empty($responseHeaders[0])) {
        if (preg_match('#HTTP/\S+\s+(\d+)#', $responseHeaders[0], $m)) {
            $statusCode = (int)$m[1];
        }
    }

    // Captura cookies Set-Cookie
    foreach ($responseHeaders as $hdr) {
        if (stripos($hdr, 'Set-Cookie:') === 0) {
            $cookiePart = trim(substr($hdr, 11));
            $parts = explode(';', $cookiePart);
            $sessionCookie = trim($parts[0]);
        }
    }

    return ['code' => $statusCode, 'headers' => $responseHeaders, 'body' => $body];
}

echo "=== INICIANDO AUDITORIA HTTP DO FLUXO DE LOGIN E SESSÃO ===\n\n";

// 1. GET /login
echo "1. Requisitando GET /login... ";
$res1 = httpRequest('GET', "{$baseUrl}/login", [], $sessionCookie);
if ($res1['code'] !== 200) {
    echo "[FALHA] Código HTTP inesperado: {$res1['code']}\n";
    exit(1);
}

// Verifica se há warnings de session no HTML
if (str_contains($res1['body'], 'Warning: session_start()')) {
    echo "[FALHA] O HTML contém avisos de session_start()!\n";
    exit(1);
}
echo "[OK] Página de login carregada com status 200 e sem warnings de sessão.\n";

// Extrai token CSRF do formulário
preg_match('/name="_csrf_token"\s+value="([^"]+)"/i', $res1['body'], $matches);
$csrfToken = $matches[1] ?? '';
if (empty($csrfToken)) {
    echo "[FALHA] Não foi possível extrair o token CSRF da página de login.\n";
    echo "Trecho do HTML recebido:\n" . substr($res1['body'], 0, 1000) . "\n";
    exit(1);
}
echo "   Token CSRF: " . substr($csrfToken, 0, 16) . "...\n";
echo "   Cookie de sessão recebido: {$sessionCookie}\n";

// 2. POST /login
echo "2. Submetendo credenciais para POST /login... ";
$res2 = httpRequest('POST', "{$baseUrl}/login", [
    'email' => 'admin@gestaosocial.org',
    'password' => 'demo123',
    '_csrf_token' => $csrfToken
], $sessionCookie);

if ($res2['code'] !== 302) {
    echo "[FALHA] Esperava redirecionamento 302, recebido {$res2['code']}\n";
    exit(1);
}
echo "[OK] Login efetuado com sucesso! Redirecionamento 302 recebido.\n";

// 3. GET /dashboard com o cookie da sessão
echo "3. Requisitando GET /dashboard com a sessão autenticada... ";
$res3 = httpRequest('GET', "{$baseUrl}/dashboard", [], $sessionCookie);
if ($res3['code'] === 302) {
    echo "[FALHA CRÍTICA] Aplicação redirecionou novamente (usuário preso no loop de login)!\n";
    foreach ($res3['headers'] as $h) {
        echo "   {$h}\n";
    }
    exit(1);
}

if ($res3['code'] !== 200) {
    echo "[FALHA] Dashboard retornou código HTTP {$res3['code']}\n";
    echo "Conteúdo da resposta:\n" . $res3['body'] . "\n";
    exit(1);
}

if (!str_contains($res3['body'], 'Painel Gerencial')) {
    echo "[FALHA] Conteúdo do Dashboard não encontrado no corpo da resposta.\n";
    exit(1);
}
echo "[OK] Dashboard carregado com sucesso (HTTP 200)! Sessão persistida perfeitamente.\n";

// 4. Testar todos os 11 módulos funcionais
$modules = [
    '/familias' => 'Famílias',
    '/pessoas' => 'Pessoas',
    '/avaliacoes' => 'Avaliações',
    '/atendimentos' => 'Atendimentos',
    '/beneficios' => 'Benefícios',
    '/estoque' => 'Estoque',
    '/doacoes' => 'Doações',
    '/projetos' => 'Projetos',
    '/relatorios' => 'Relatórios',
    '/usuarios' => 'Usuários',
    '/auditoria' => 'Auditoria'
];

foreach ($modules as $uri => $nome) {
    echo "4. Testando rota {$uri} ({$nome})... ";
    $res = httpRequest('GET', "{$baseUrl}{$uri}", [], $sessionCookie);
    if ($res['code'] !== 200) {
        echo "[FALHA] Código HTTP {$res['code']} em {$uri}!\n";
        echo substr($res['body'], 0, 800) . "\n";
        exit(1);
    }
    echo "[OK] Status 200\n";
}

echo "\n============================================================\n";
echo "AUDITORIA COMPLETA DE TODAS AS ROTAS CONCLUÍDA COM SUCESSO!\n";
echo "Todos os 11 módulos estão navegáveis, sem erros e com sessão ativa.\n";
echo "============================================================\n";
exit(0);
