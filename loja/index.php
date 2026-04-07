<?php
/**
 * Akti — Loja Online (Entry Point Público)
 *
 * Este arquivo serve como ponto de entrada independente da loja.
 * Pode ser acessado diretamente via subdomínio, domínio customizado
 * ou subpasta (/loja/).
 *
 * Fluxo:
 *   1. Carrega autoloader e infra do Akti (tenant, DB, sessão)
 *   2. Resolve o tenant pelo domínio/subdomínio
 *   3. Roteia a URL para o LojaController
 *   4. Renderiza a página com Twig
 */

// ── Bootstrap ──────────────────────────────────────────────────────
require_once __DIR__ . '/../app/bootstrap/autoload.php';

use Akti\Controllers\LojaController;
use Akti\Models\SiteBuilder;
use Akti\Services\TwigRenderer;

session_start();

// ── Tratamento de erros ────────────────────────────────────────────
set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    $isDev = (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'local');

    $logMessage = sprintf(
        '[%s][LOJA ERROR] %s in %s:%d',
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
    error_log($logMessage);

    $logDir = dirname(__DIR__) . '/storage/logs';
    if (is_dir($logDir) && is_writable($logDir)) {
        file_put_contents(
            $logDir . '/loja_error_' . date('Y-m-d') . '.log',
            $logMessage . "\n" . $e->getTraceAsString() . "\n\n",
            FILE_APPEND | LOCK_EX
        );
    }

    if ($isDev) {
        echo '<h1 style="font-family:sans-serif;color:#dc3545">Erro na Loja</h1>';
        echo '<pre style="font-family:monospace;background:#f8f9fa;padding:1rem;border-radius:8px">';
        echo htmlspecialchars($e->getMessage()) . "\n";
        echo htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro</title></head>';
        echo '<body style="font-family:sans-serif;text-align:center;padding:4rem">';
        echo '<h1>Ops! Algo deu errado.</h1>';
        echo '<p>Tente novamente em alguns instantes.</p>';
        echo '</body></html>';
    }
    exit;
});

// ── Tenant ─────────────────────────────────────────────────────────
$tenantId = (int) ($_SESSION['tenant']['id'] ?? 0);

if ($tenantId <= 0) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Loja não encontrada</title></head>';
    echo '<body style="font-family:sans-serif;text-align:center;padding:4rem">';
    echo '<h1>Loja não encontrada</h1>';
    echo '<p>Verifique o endereço e tente novamente.</p>';
    echo '</body></html>';
    exit;
}

// ── Conexão com o banco ────────────────────────────────────────────
$db = \Database::getInstance();

// ── Serviços ───────────────────────────────────────────────────────
$basePath = dirname(__DIR__);
$siteBuilder = new SiteBuilder($db);
$twig = new TwigRenderer($basePath);
$controller = new LojaController($db, $siteBuilder, $twig, $tenantId);

// ── Roteamento ─────────────────────────────────────────────────────
$route = trim($_GET['route'] ?? '', '/');

// Sanitizar rota — apenas alfanuméricos, hifens, barras e underscores
$route = preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $route);

// AJAX API routes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    match ($route) {
        'api/cart/add'    => $controller->addToCart(),
        'api/cart/remove' => $controller->removeFromCart(),
        default           => null,
    };

    // Se chegou aqui, POST para rota não-API
    if ($route !== '' && str_starts_with($route, 'api/')) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Endpoint não encontrado']);
        exit;
    }
}

// GET API routes
if ($route === 'api/search') {
    $controller->searchSuggestions();
    exit;
}

// Page routes
match (true) {
    $route === '' || $route === 'home'
        => $controller->home(),
    $route === 'produtos' || $route === 'products'
        => $controller->collection(),
    str_starts_with($route, 'produto/') || str_starts_with($route, 'product/')
        => $controller->product(basename($route)),
    $route === 'carrinho' || $route === 'cart'
        => $controller->cart(),
    $route === 'contato' || $route === 'contact'
        => $controller->contact(),
    $route === 'perfil' || $route === 'profile'
        => $controller->profile(),
    default
        => $controller->home(),
};
