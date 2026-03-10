<?php
// ── Autoloader PSR-4 (carrega session.php, tenant.php, database.php automaticamente) ──
require_once __DIR__ . '/app/bootstrap/autoload.php';

use Akti\Core\Router;
use Akti\Core\Security;
use Akti\Core\ModuleBootloader;
use Akti\Middleware\CsrfMiddleware;
use Akti\Models\User;
use Akti\Models\IpGuard;

session_start();

// ── Tratamento global de erros — exibe a página 500 em caso de erro fatal ──
set_exception_handler(function($e) {
    http_response_code(500);
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        while (ob_get_level()) ob_end_clean();
    }
    // Se a requisição espera JSON (AJAX/fetch), retorna JSON em vez de HTML
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $xhrHeader = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $isAjax = (strtolower($xhrHeader) === 'xmlhttprequest')
              || (stripos($acceptHeader, 'application/json') !== false)
              || (stripos($contentType, 'application/json') !== false);
    // Também detecta AJAX por actions conhecidas
    $action = $_GET['action'] ?? '';
    $ajaxActions = ['getSubcategories','getInheritedGrades','getInheritedSectors','getProductsForExport','exportToProducts','createCategoryAjax','deleteImage','createGradeType','getGradeTypes','generateCombinations','importProducts','toggleCategoryCombination','toggleSubcategoryCombination','importOfx','getSummaryJson','getInstallmentsJson','moveAjax','checkOrderStock','addExtraCost','deleteExtraCost','moveSector','getItemLogs','addItemLog','deleteItemLog','togglePreparation','countInstallments','deleteInstallments','updateItemDiscount','updateItemQty'];
    if (in_array($action, $ajaxActions)) {
        $isAjax = true;
    }
    if ($isAjax) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        exit;
    }
    require __DIR__ . '/app/views/errors/500.php';
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        http_response_code(500);
        error_log('Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            while (ob_get_level()) ob_end_clean();
        }
        require __DIR__ . '/app/views/errors/500.php';
        exit;
    }
});

// Inicializar tenant
TenantManager::enforceTenantSession();

// ── Verificação de inatividade de sessão ──
// Precisa de conexão ao tenant para ler session_timeout_minutes de company_settings
if (isset($_SESSION['user_id'])) {
    $__sessionDb = (new Database())->getConnection();
    SessionGuard::checkInactivity($__sessionDb);
    SessionGuard::touch();
} else {
    $__sessionDb = null;
}

// ══════════════════════════════════════════════════════════════════
// Inicializar Token CSRF (gera se não existir na sessão)
// ══════════════════════════════════════════════════════════════════
Security::generateCsrfToken();

// ══════════════════════════════════════════════════════════════════
// Inicializar Router baseado em mapa de rotas
// ══════════════════════════════════════════════════════════════════
$router = new Router(__DIR__ . '/app/config/routes.php');
$page   = $router->getPage();
$action = $router->getAction();

// ── Endpoint AJAX de keepalive (renova sessão sem recarregar página) ──
if ($page === 'session' && $action === 'keepalive') {
    header('Content-Type: application/json');
    if (isset($_SESSION['user_id'])) {
        SessionGuard::touch();
        $data = SessionGuard::getJsSessionData($__sessionDb);
        echo json_encode(['success' => true, 'remaining_seconds' => $data['remaining_seconds']]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'session_expired' => true]);
    }
    exit;
}

// ── Páginas públicas/before_auth: despachar ANTES do auth check ──
if ($router->isPublicPage() || $router->hasBeforeAuth()) {
    // Catálogo: sempre público, despachar e sair
    if ($router->isPublicPage() && $page !== 'login') {
        CsrfMiddleware::handle();
        $router->dispatch();
        exit;
    }
}

// ── Authentication Check ──
if (!isset($_SESSION['user_id'])) {
    if ($page !== 'login') {
        header('Location: ?page=login');
        exit;
    }
    // Não logado + page=login → despachar login (POST de login precisa de CSRF)
    CsrfMiddleware::handle();
    $router->dispatch();
    exit;
} else {
    // Logado + page=login (e não é logout) → redirecionar para home
    if ($page === 'login' && $action !== 'logout') {
        header('Location: ?');
        exit;
    }
    // Logado + page=login + action=logout → despachar logout
    if ($page === 'login' && $action === 'logout') {
        $router->dispatch();
        exit;
    }
}

// ── Permission Check — usa o registro centralizado de menu.php ──
if (!ModuleBootloader::canAccessPage($page)) {
    require 'app/views/layout/header.php';
    echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo indisponível para este tenant.<br>Página bloqueada pelo bootloader: <strong>" . strtoupper($page) . "</strong>.</div></div>";
    require 'app/views/layout/footer.php';
    exit;
}

// ── Permission Check — usa o registro centralizado de menu.php ──
$menuConfig = require 'app/config/menu.php';
$flatMenuConfig = [];
foreach ($menuConfig as $key => $info) {
    if (isset($info['children'])) {
        foreach ($info['children'] as $childKey => $childInfo) {
            $flatMenuConfig[$childKey] = $childInfo;
        }
    } else {
        $flatMenuConfig[$key] = $info;
    }
}
$needsPermission = isset($flatMenuConfig[$page]) && !empty($flatMenuConfig[$page]['permission']);

// Mapear subpáginas para a permissão pai usando permission_alias do menu config
$permissionPage = $page;
if (isset($flatMenuConfig[$page]['permission_alias'])) {
    $permissionPage = $flatMenuConfig[$page]['permission_alias'];
}

// Actions AJAX que bypassam verificação de permissão (chamadas de dentro de páginas já autorizadas)
$permissionBypassActions = [
    'getSubcategories', 'getInheritedGrades', 'getInheritedSectors',
    'getProductsForExport', 'exportToProducts',
];

if ($needsPermission && !in_array($action, $permissionBypassActions)) {
    $db = (new Database())->getConnection();
    $user = new User($db);
    if (!$user->checkPermission($_SESSION['user_id'], $permissionPage)) {
        require 'app/views/layout/header.php';
        echo "<div class='container mt-5'><div class='alert alert-danger'><i class='fas fa-ban me-2'></i>Acesso Negado.<br>Você não tem permissão para acessar o módulo: <strong>" . strtoupper($page) . "</strong>.</div></div>";
        require 'app/views/layout/footer.php';
        exit;
    }
}

// ══════════════════════════════════════════════════════════════════
// CSRF Middleware — validar token em requisições POST/PUT/PATCH/DELETE
// ══════════════════════════════════════════════════════════════════
CsrfMiddleware::handle();

// ══════════════════════════════════════════════════════════════════
// Despachar rota autenticada
// ══════════════════════════════════════════════════════════════════
$router->dispatch();
