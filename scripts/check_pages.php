<?php
/**
 * Script de verificação rápida de páginas — Akti
 *
 * Carrega todas as rotas de tests/routes_test.php, faz login, testa cada
 * página e imprime um relatório colorido no terminal.
 *
 * Uso:
 *   php scripts/check_pages.php
 *   php scripts/check_pages.php --no-color
 *   php scripts/check_pages.php --base-url=http://meusite.com
 *
 * Requisitos: extensão cURL habilitada no PHP.
 */

// ── Configuração ─────────────────────────────────────────────────
$baseUrl  = 'http://localhost/teste.akti.com';
$email    = 'admin@sistema.com';
$password = 'admin123';
$timeout  = 30;
$noColor  = false;

// Processar argumentos CLI
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = substr($arg, strlen('--base-url='));
    }
    if (strpos($arg, '--email=') === 0) {
        $email = substr($arg, strlen('--email='));
    }
    if (strpos($arg, '--password=') === 0) {
        $password = substr($arg, strlen('--password='));
    }
    if ($arg === '--no-color') {
        $noColor = true;
    }
}

$baseUrl = rtrim($baseUrl, '/');

// ── Detecção de cores no terminal ─────────────────────────────────
$isWindows = PHP_OS_FAMILY === 'Windows';
if ($noColor || ($isWindows && !getenv('ANSICON') && !getenv('ConEmuANSI') && !function_exists('sapi_windows_vt100_support'))) {
    // Sem suporte a cores
    function colorize(string $text, string $color): string { return $text; }
} else {
    // Tentar habilitar VT100 no Windows 10+
    if ($isWindows && function_exists('sapi_windows_vt100_support')) {
        @sapi_windows_vt100_support(STDOUT, true);
    }
    function colorize(string $text, string $color): string {
        $codes = [
            'green'  => "\033[32m",
            'red'    => "\033[31m",
            'yellow' => "\033[33m",
            'cyan'   => "\033[36m",
            'bold'   => "\033[1m",
            'reset'  => "\033[0m",
        ];
        return ($codes[$color] ?? '') . $text . ($codes['reset'] ?? '');
    }
}

// ── Strings de erro PHP ───────────────────────────────────────────
$errorPatterns = [
    'Fatal error', 'Parse error', 'Warning:', 'Notice:',
    'Uncaught Error', 'Uncaught Exception', 'Stack trace:',
    'xdebug-error', 'Undefined variable', 'Undefined index',
    'Call to undefined', 'Class "',
];

// ── Carregar rotas ────────────────────────────────────────────────
$routesFile = __DIR__ . '/../tests/routes_test.php';
if (!file_exists($routesFile)) {
    echo colorize("[ERRO]", 'red') . " Arquivo de rotas não encontrado: {$routesFile}\n";
    exit(1);
}
$routes = require $routesFile;

echo colorize("\n══════════════════════════════════════════════════════", 'cyan') . "\n";
echo colorize("  Akti — Verificação Rápida de Páginas", 'bold') . "\n";
echo colorize("══════════════════════════════════════════════════════", 'cyan') . "\n";
echo "  Base URL:  {$baseUrl}\n";
echo "  Rotas:     " . count($routes) . "\n";
echo colorize("──────────────────────────────────────────────────────\n", 'cyan');

// ── Login ─────────────────────────────────────────────────────────
echo "\n  Fazendo login como {$email}... ";

// Passo 1: GET na página de login para obter sessão e tenant_key
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/?page=login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => $timeout,
]);
$getResponse = curl_exec($ch);
$getHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$getHeaders = substr($getResponse, 0, $getHeaderSize);
$getBody = substr($getResponse, $getHeaderSize);

// Extrair cookie de sessão do GET
$sessionCookie = null;
if (preg_match('/Set-Cookie:\s*(AKTI_SID=[^;]+)/i', $getHeaders, $m)) {
    $sessionCookie = $m[1];
} elseif (preg_match('/Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $getHeaders, $m)) {
    $sessionCookie = $m[1];
}

// Extrair tenant_key do formulário
$tenantKey = '';
if (preg_match('/name="tenant_key"\s+value="([^"]*)"/', $getBody, $m)) {
    $tenantKey = $m[1];
}

// Passo 2: POST de login com credenciais e tenant_key
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/?page=login',
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'email'      => $email,
        'password'   => $password,
        'tenant_key' => $tenantKey,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_COOKIE         => $sessionCookie ?? '',
]);
$loginResponse = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Após login bem-sucedido (302), o servidor envia novo cookie (session_regenerate_id)
if (preg_match('/Set-Cookie:\s*(AKTI_SID=[^;]+)/i', $loginResponse, $m)) {
    $sessionCookie = $m[1];
} elseif (preg_match('/Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $loginResponse, $m)) {
    $sessionCookie = $m[1];
}

if ($sessionCookie === null || ($loginCode !== 302 && $loginCode !== 303)) {
    echo colorize("FALHOU!", 'red') . " (HTTP {$loginCode}, tenant_key={$tenantKey})\n";
    echo "  Verifique as credenciais e a URL.\n\n";
    exit(1);
}

echo colorize("OK", 'green') . " (HTTP {$loginCode})\n\n";

// ── Testar rotas ──────────────────────────────────────────────────
$passed = 0;
$failed = 0;
$errors = [];
$startTime = microtime(true);

foreach ($routes as $route) {
    $label = $route['label'] ?? $route['route'];
    $auth  = $route['auth'] ?? true;
    $url   = $baseUrl . '/' . ltrim($route['route'], '/');

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_COOKIE         => $auth ? $sessionCookie : '',
    ]);

    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
    curl_close($ch);

    // Verificar status HTTP
    $statusOk = ($httpCode === 200);

    // Verificar erros PHP no body
    $phpErrors = [];
    if ($body !== false) {
        foreach ($errorPatterns as $pattern) {
            if (stripos($body, $pattern) !== false) {
                $phpErrors[] = $pattern;
            }
        }
    }

    // Verificar strings esperadas
    $missingContains = [];
    if (!empty($route['contains']) && $body !== false) {
        foreach ($route['contains'] as $needle) {
            if (stripos($body, $needle) === false) {
                $missingContains[] = $needle;
            }
        }
    }

    $isOk = $statusOk && empty($phpErrors) && empty($missingContains) && $body !== false;

    if ($isOk) {
        $passed++;
        echo "  " . colorize("[OK]", 'green') . "   {$label}" . colorize(" ({$time}ms)", 'cyan') . "\n";
    } else {
        $failed++;
        $reasons = [];
        if (!$statusOk) $reasons[] = "HTTP {$httpCode}";
        if ($body === false) $reasons[] = "Sem resposta (cURL error)";
        if (!empty($phpErrors)) $reasons[] = "Erro PHP: " . implode(', ', $phpErrors);
        if (!empty($missingContains)) $reasons[] = "Falta: " . implode(', ', $missingContains);

        $reasonStr = implode(' | ', $reasons);
        echo "  " . colorize("[ERRO]", 'red') . "  {$label} — " . colorize($reasonStr, 'yellow') . "\n";
        $errors[] = ['label' => $label, 'route' => $route['route'], 'reasons' => $reasonStr];
    }
}

$totalTime = round((microtime(true) - $startTime) * 1000);

// ── Relatório final ───────────────────────────────────────────────
echo colorize("\n──────────────────────────────────────────────────────\n", 'cyan');
echo "  " . colorize("Resultados:", 'bold') . "\n";
echo "  Total:   " . ($passed + $failed) . " rotas\n";
echo "  " . colorize("OK:      {$passed}", 'green') . "\n";

if ($failed > 0) {
    echo "  " . colorize("ERRO:    {$failed}", 'red') . "\n";
    echo "\n  " . colorize("Rotas com falha:", 'yellow') . "\n";
    foreach ($errors as $err) {
        echo "    • {$err['label']} ({$err['route']}) — {$err['reasons']}\n";
    }
}

echo "  Tempo:   {$totalTime}ms\n";
echo colorize("══════════════════════════════════════════════════════\n\n", 'cyan');

exit($failed > 0 ? 1 : 0);
