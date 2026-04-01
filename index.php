<?php
// ── Autoloader PSR-4 (carrega session.php, tenant.php, database.php automaticamente) ──
require_once __DIR__ . '/app/bootstrap/autoload.php';

use Akti\Core\Router;
use Akti\Core\Security;
use Akti\Core\ModuleBootloader;
use Akti\Core\Application;
use Akti\Middleware\CsrfMiddleware;
use Akti\Middleware\PortalAuthMiddleware;
use Akti\Middleware\SecurityHeadersMiddleware;
use Akti\Models\User;
use Akti\Models\IpGuard;

session_start();

// ── Security Headers — aplicar ANTES de qualquer output ──
SecurityHeadersMiddleware::handle();

// ── Tratamento global de erros — exibe a página 500 em caso de erro fatal ──
set_exception_handler(function($e) {
    http_response_code(500);

    // Log estruturado
    $logMessage = sprintf(
        '[%s][ERROR] %s in %s:%d | Trace: %s',
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($logMessage);

    // Gravar em storage/logs se disponível
    $logDir = __DIR__ . '/storage/logs';
    if (is_dir($logDir) && is_writable($logDir)) {
        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logMessage . "\n", FILE_APPEND | LOCK_EX);
    }

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
    $ajaxActions = ['getSubcategories','getInheritedGrades','getInheritedSectors','getProductsForExport','exportToProducts','createCategoryAjax','deleteImage','createGradeType','getGradeTypes','generateCombinations','importProducts','toggleCategoryCombination','toggleSubcategoryCombination','importOfx','getSummaryJson','getInstallmentsJson','moveAjax','checkOrderStock','addExtraCost','deleteExtraCost','moveSector','getItemLogs','addItemLog','deleteItemLog','togglePreparation','countInstallments','getStockItems','getMovements','getMovement','updateMovement','deleteMovement','storeMovement','getProductCombinations','updateItemMeta','getProductStock','setDefault','getDefaultWarehouse','deleteInstallments','updateItemDiscount','updateItemQty','getProductsList','getCustomersList','searchSelect2','searchAjax','parseImportFile','importProductsMapped','storeForma','updateForma','deleteForma','getFaixas','linkGrupo','unlinkGrupo','linkUsuario','unlinkUsuario','saveProdutoRegra','deleteProdutoRegra','simularCalculo','calcular','getHistoricoPaginated','aprovar','pagar','cancelar','aprovarLote','pagarLote','saveConfig','query','count','markRead','markAllRead','addSection','deleteSection','updateSection','reorderSections','saveSections','addComponent','updateComponent','removeComponent','createPage','updatePage','deletePage','getPageData','saveThemeSettings','pages'];
    if (in_array($action, $ajaxActions)) {
        $isAjax = true;
    }

    // Em modo desenvolvimento, mostrar detalhes técnicos
    $isDev = (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'local');

    if ($isAjax) {
        if (!headers_sent()) header('Content-Type: application/json');
        $jsonResponse = ['success' => false, 'message' => 'Erro interno do servidor.'];
        if ($isDev) {
            $jsonResponse['message']   = $e->getMessage();
            $jsonResponse['file']      = $e->getFile() . ':' . $e->getLine();
            $jsonResponse['trace']     = explode("\n", $e->getTraceAsString());
        }
        echo json_encode($jsonResponse);
        exit;
    }

    // Em modo desenvolvimento, exibir stack trace
    if ($isDev) {
        echo '<!DOCTYPE html><html><head><title>Erro — Akti (Dev)</title>';
        echo '<style>body{font-family:monospace;margin:2rem;background:#1a1a2e;color:#e8e8e8}';
        echo 'h1{color:#ff6b6b}pre{background:#16213e;padding:1rem;border-radius:8px;overflow-x:auto;font-size:0.85rem}';
        echo '.file{color:#4dabf7}.line{color:#ffd43b}</style></head><body>';
        echo '<h1>⚠️ Exceção não tratada</h1>';
        echo '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>';
        echo '<p>Arquivo: <span class="file">' . htmlspecialchars($e->getFile()) . '</span>';
        echo ' Linha: <span class="line">' . $e->getLine() . '</span></p>';
        echo '<h3>Stack Trace:</h3><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
        exit;
    }

    require __DIR__ . '/app/views/errors/500.php';
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        http_response_code(500);
        $logMessage = sprintf(
            '[%s][FATAL] %s in %s:%d',
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        error_log($logMessage);

        // Gravar em storage/logs
        $logDir = __DIR__ . '/storage/logs';
        if (is_dir($logDir) && is_writable($logDir)) {
            file_put_contents($logDir . '/error_' . date('Y-m-d') . '.log', $logMessage . "\n", FILE_APPEND | LOCK_EX);
        }

        if (!headers_sent()) {
            while (ob_get_level()) ob_end_clean();
        }

        $isDev = (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'local');
        if ($isDev) {
            echo '<h1 style="font-family:monospace;color:#ff6b6b">Fatal Error</h1>';
            echo '<pre style="font-family:monospace">' . htmlspecialchars($error['message']) . '</pre>';
            echo '<p style="font-family:monospace">' . htmlspecialchars($error['file']) . ':' . $error['line'] . '</p>';
            exit;
        }

        require __DIR__ . '/app/views/errors/500.php';
        exit;
    }
});

// ══════════════════════════════════════════════════════════════════
// Application — boot, handle, dispatch
// ══════════════════════════════════════════════════════════════════
$app = new Application(__DIR__);
$app->boot();

if ($app->handle()) {
    $app->dispatch();
}
