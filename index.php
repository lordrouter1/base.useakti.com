<?php
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
    $ajaxActions = ['getSubcategories','getInheritedGrades','getInheritedSectors','getProductsForExport','exportToProducts','createCategoryAjax','deleteImage','createGradeType','getGradeTypes','generateCombinations','importProducts','toggleCategoryCombination','toggleSubcategoryCombination'];
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

// Carregar configurações e banco de dados
require_once 'app/config/database.php';
require_once 'app/models/User.php';
TenantManager::enforceTenantSession();

// Sistema de Roteamento Simples
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// ── Catálogo público: NÃO exige autenticação ──
if ($page === 'catalog') {
    require_once 'app/controllers/CatalogController.php';
    $controller = new CatalogController();
    if ($action === 'addToCart') {
        $controller->addToCart();
    } elseif ($action === 'removeFromCart') {
        $controller->removeFromCart();
    } elseif ($action === 'updateCartItem') {
        $controller->updateCartItem();
    } elseif ($action === 'getCart') {
        $controller->getCart();
    } else {
        $controller->index();
    }
    exit;
}

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    if ($page !== 'login') {
        header('Location: ?page=login');
        exit;
    }
} else {
    if ($page === 'login' && $action !== 'logout') {
        header('Location: ?');
        exit;
    }
}

// Permission Check — usa o registro centralizado de menu.php
// Páginas com 'permission' => false são acessíveis por todos os logados
// Achata submenus para encontrar a config de qualquer página (inclusive filhas)
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

if (isset($_SESSION['user_id']) && $page !== 'login' && $action !== 'logout' && $action !== 'getSubcategories' && $action !== 'getInheritedGrades' && $action !== 'getInheritedSectors' && $action !== 'getProductsForExport' && $action !== 'exportToProducts' && $needsPermission) {
    $db = (new Database())->getConnection();
    $user = new User($db);
    if (!$user->checkPermission($_SESSION['user_id'], $permissionPage)) {
        require 'app/views/layout/header.php';
        echo "<div class='container mt-5'><div class='alert alert-danger'><i class='fas fa-ban me-2'></i>Acesso Negado.<br>Você não tem permissão para acessar o módulo: <strong>" . strtoupper($page) . "</strong>.</div></div>";
        require 'app/views/layout/footer.php';
        exit;
    }
}

switch ($page) {
    case 'home':
        require 'app/views/layout/header.php';
        require 'app/views/home/index.php';
        require 'app/views/layout/footer.php';
        break;

    case 'login':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        if ($action == 'logout') {
            $controller->logout();
        } else {
            $controller->login();
        }
        break;

    case 'dashboard':
        // Dashboard unificado com a home — redirecionar
        header('Location: ?');
        exit;
        break;

    // ── Perfil do Usuário (acessível por todos os logados) ──
    case 'profile':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        if ($action == 'update') {
            $controller->updateProfile();
        } else {
            $controller->profile();
        }
        break;

    // ── Produtos ──
    case 'products':
        require_once 'app/controllers/ProductController.php';
        $controller = new ProductController();
        if ($action == 'store') {
            $controller->store();
        } elseif ($action == 'create') {
            $controller->create();
        } elseif ($action == 'edit') {
            $controller->edit();
        } elseif ($action == 'update') {
            $controller->update();
        } elseif ($action == 'delete') {
            $controller->delete();
        } elseif ($action == 'deleteImage') {
            $controller->deleteImage();
        } elseif ($action == 'getSubcategories') {
            $controller->getSubcategories();
        } elseif ($action == 'createGradeType') {
            $controller->createGradeTypeAjax();
        } elseif ($action == 'getGradeTypes') {
            $controller->getGradeTypes();
        } elseif ($action == 'generateCombinations') {
            $controller->generateCombinationsAjax();
        } elseif ($action == 'downloadImportTemplate') {
            $controller->downloadImportTemplate();
        } elseif ($action == 'importProducts') {
            $controller->importProducts();
        } else {
            $controller->index();
        }
        break;

    // ── Categorias e Subcategorias ──
    case 'categories':
        require_once 'app/controllers/CategoryController.php';
        $controller = new CategoryController();
        if ($action == 'store') {
            $controller->store();
        } elseif ($action == 'update') {
            $controller->update();
        } elseif ($action == 'delete') {
            $controller->delete();
        } elseif ($action == 'storeSub') {
            $controller->storeSub();
        } elseif ($action == 'updateSub') {
            $controller->updateSub();
        } elseif ($action == 'deleteSub') {
            $controller->deleteSub();
        } elseif ($action == 'getInheritedGrades') {
            $controller->getInheritedGradesAjax();
        } elseif ($action == 'toggleCategoryCombination') {
            $controller->toggleCategoryCombinationAjax();
        } elseif ($action == 'toggleSubcategoryCombination') {
            $controller->toggleSubcategoryCombinationAjax();
        } elseif ($action == 'getProductsForExport') {
            $controller->getProductsForExport();
        } elseif ($action == 'exportToProducts') {
            $controller->exportToProducts();
        } elseif ($action == 'getInheritedSectors') {
            $controller->getInheritedSectorsAjax();
        } else {
            $controller->index();
        }
        break;

    // ── Setores de Produção ──
    case 'sectors':
        require_once 'app/controllers/SectorController.php';
        $controller = new SectorController();
        if ($action == 'store') {
            $controller->store();
        } elseif ($action == 'update') {
            $controller->update();
        } elseif ($action == 'delete') {
            $controller->delete();
        } else {
            $controller->index();
        }
        break;

    // ── Clientes ──
    case 'customers':
        require_once 'app/controllers/CustomerController.php';
        $controller = new CustomerController();
        if ($action == 'store') {
            $controller->store();
        } elseif ($action == 'create') {
            $controller->create();
        } elseif ($action == 'edit') {
            $controller->edit();
        } elseif ($action == 'update') {
            $controller->update();
        } elseif ($action == 'delete') {
            $controller->delete();
        } else {
            $controller->index();
        }
        break;

    // ── Pedidos ──
    case 'orders':
        require_once 'app/controllers/OrderController.php';
        $controller = new OrderController();
        if ($action == 'store') {
            $controller->store();
        } elseif ($action == 'create') {
            $controller->create();
        } elseif ($action == 'edit') {
            $controller->edit();
        } elseif ($action == 'update') {
            $controller->update();
        } elseif ($action == 'delete') {
            $controller->delete();
        } elseif ($action == 'addItem') {
            $controller->addItem();
        } elseif ($action == 'updateItem') {
            $controller->updateItem();
        } elseif ($action == 'deleteItem') {
            $controller->deleteItem();
        } elseif ($action == 'printQuote') {
            $controller->printQuote();
        } elseif ($action == 'agenda') {
            $controller->agenda();
        } elseif ($action == 'report') {
            $controller->report();
        } else {
            $controller->index();
        }
        break;

    // ── Agenda de Contatos (atalho de menu — usa OrderController) ──
    case 'agenda':
        require_once 'app/controllers/OrderController.php';
        $controller = new OrderController();
        $controller->agenda();
        break;

    // ── Linha de Produção (Pipeline) ──
    case 'pipeline':
        require_once 'app/controllers/PipelineController.php';
        $controller = new PipelineController();
        if ($action == 'move') {
            $controller->move();
        } elseif ($action == 'moveAjax') {
            $controller->moveAjax();
        } elseif ($action == 'detail') {
            $controller->detail();
        } elseif ($action == 'updateDetails') {
            $controller->updateDetails();
        } elseif ($action == 'settings') {
            $controller->settings();
        } elseif ($action == 'saveSettings') {
            $controller->saveSettings();
        } elseif ($action == 'alerts') {
            $controller->alerts();
        } elseif ($action == 'getPricesByTable') {
            $controller->getPricesByTable();
        } elseif ($action == 'addExtraCost') {
            $controller->addExtraCost();
        } elseif ($action == 'deleteExtraCost') {
            $controller->deleteExtraCost();
        } elseif ($action == 'generateCatalogLink') {
            require_once 'app/controllers/CatalogController.php';
            $catCtrl = new CatalogController();
            $catCtrl->generate();
        } elseif ($action == 'deactivateCatalogLink') {
            require_once 'app/controllers/CatalogController.php';
            $catCtrl = new CatalogController();
            $catCtrl->deactivate();
        } elseif ($action == 'getCatalogLink') {
            require_once 'app/controllers/CatalogController.php';
            $catCtrl = new CatalogController();
            $catCtrl->getLink();
        } elseif ($action == 'moveSector') {
            $controller->moveSector();
        } elseif ($action == 'getItemLogs') {
            $controller->getItemLogs();
        } elseif ($action == 'addItemLog') {
            $controller->addItemLog();
        } elseif ($action == 'deleteItemLog') {
            $controller->deleteItemLog();
        } elseif ($action == 'togglePreparation') {
            $controller->togglePreparation();
        } elseif ($action == 'checkOrderStock') {
            $controller->checkOrderStock();
        } elseif ($action == 'productionBoard') {
            $controller->productionBoard();
        } elseif ($action == 'printProductionOrder') {
            $controller->printProductionOrder();
        } else {
            $controller->index();
        }
        break;
    
    // ── Painel de Produção (atalho de menu — usa PipelineController) ──
    case 'production_board':
        require_once 'app/controllers/PipelineController.php';
        $controller = new PipelineController();
        if ($action == 'moveSector') {
            $controller->moveSector();
        } elseif ($action == 'getItemLogs') {
            $controller->getItemLogs();
        } elseif ($action == 'addItemLog') {
            $controller->addItemLog();
        } elseif ($action == 'deleteItemLog') {
            $controller->deleteItemLog();
        } else {
            $controller->productionBoard();
        }
        break;

    // ── Tabelas de Preço (atalho de menu — redireciona para settings tab=prices) ──
    case 'price_tables':
        require_once 'app/controllers/SettingsController.php';
        $controller = new SettingsController();
        if ($action == 'createPriceTable') {
            $controller->createPriceTable();
        } elseif ($action == 'updatePriceTable') {
            $controller->updatePriceTable();
        } elseif ($action == 'deletePriceTable') {
            $controller->deletePriceTable();
        } elseif ($action == 'editPriceTable') {
            $controller->editPriceTable();
        } elseif ($action == 'savePriceItem') {
            $controller->savePriceItem();
        } elseif ($action == 'deletePriceItem') {
            $controller->deletePriceItem();
        } else {
            $controller->priceTablesIndex();
        }
        break;

    // ── Configurações do Sistema ──
    case 'settings':
        require_once 'app/controllers/SettingsController.php';
        $controller = new SettingsController();
        if ($action == 'saveCompany') {
            $controller->saveCompany();
        } elseif ($action == 'createPriceTable') {
            $controller->createPriceTable();
        } elseif ($action == 'updatePriceTable') {
            $controller->updatePriceTable();
        } elseif ($action == 'deletePriceTable') {
            $controller->deletePriceTable();
        } elseif ($action == 'editPriceTable') {
            $controller->editPriceTable();
        } elseif ($action == 'savePriceItem') {
            $controller->savePriceItem();
        } elseif ($action == 'deletePriceItem') {
            $controller->deletePriceItem();
        } elseif ($action == 'getPricesForCustomer') {
            $controller->getPricesForCustomer();
        } elseif ($action == 'addPreparationStep') {
            $controller->addPreparationStep();
        } elseif ($action == 'updatePreparationStep') {
            $controller->updatePreparationStep();
        } elseif ($action == 'deletePreparationStep') {
            $controller->deletePreparationStep();
        } elseif ($action == 'togglePreparationStep') {
            $controller->togglePreparationStep();
        } elseif ($action == 'saveBankSettings') {
            $controller->saveBankSettings();
        } elseif ($action == 'saveFiscalSettings') {
            $controller->saveFiscalSettings();
        } else {
            $controller->index();
        }
        break;

    // ── Gestão de Usuários (Admin) ──
    case 'users':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        if ($action == 'create') {
            $controller->create();
        } elseif ($action == 'store') {
            $controller->store();
        } elseif ($action == 'edit') {
            $controller->edit();
        } elseif ($action == 'update') {
            $controller->update();
        } elseif ($action == 'delete') {
            $controller->delete();
        } elseif ($action == 'groups') {
            $controller->groups();
        } elseif ($action == 'createGroup') {
            $controller->createGroup();
        } elseif ($action == 'updateGroup') {
            $controller->updateGroup();
        } elseif ($action == 'deleteGroup') {
            $controller->deleteGroup();
        } else {
            $controller->index();
        }
        break;

    // ── Controle de Estoque ──
    case 'stock':
        require_once 'app/controllers/StockController.php';
        $controller = new StockController();
        if ($action == 'warehouses') {
            $controller->warehouses();
        } elseif ($action == 'storeWarehouse') {
            $controller->storeWarehouse();
        } elseif ($action == 'updateWarehouse') {
            $controller->updateWarehouse();
        } elseif ($action == 'deleteWarehouse') {
            $controller->deleteWarehouse();
        } elseif ($action == 'entry') {
            $controller->entry();
        } elseif ($action == 'storeMovement') {
            $controller->storeMovement();
        } elseif ($action == 'movements') {
            $controller->movements();
        } elseif ($action == 'getProductCombinations') {
            $controller->getProductCombinations();
        } elseif ($action == 'updateItemMeta') {
            $controller->updateItemMeta();
        } elseif ($action == 'getProductStock') {
            $controller->getProductStock();
        } elseif ($action == 'setDefault') {
            $controller->setDefault();
        } elseif ($action == 'getDefaultWarehouse') {
            $controller->getDefaultWarehouse();
        } elseif ($action == 'checkOrderStock') {
            $controller->checkOrderStock();
        } else {
            $controller->index();
        }
        break;

    // ── Walkthrough ──
    case 'walkthrough':
        require_once 'app/controllers/WalkthroughController.php';
        $controller = new WalkthroughController();
        $action = $_GET['action'] ?? 'checkStatus';            $allowed = ['checkStatus', 'start', 'complete', 'skip', 'saveStep', 'reset', 'getSteps', 'manual'];
        if (in_array($action, $allowed)) {
            $controller->$action();
        } else {
            $controller->checkStatus();
        }
        break;

    // ── Fiscal / Financeiro ──
    case 'financial':
        require_once 'app/controllers/FinancialController.php';
        $controller = new FinancialController();
        if ($action == 'payments') {
            $controller->payments();
        } elseif ($action == 'installments') {
            $controller->installments();
        } elseif ($action == 'generateInstallments') {
            $controller->generateInstallments();
        } elseif ($action == 'payInstallment') {
            $controller->payInstallment();
        } elseif ($action == 'confirmPayment') {
            $controller->confirmPayment();
        } elseif ($action == 'cancelInstallment') {
            $controller->cancelInstallment();
        } elseif ($action == 'uploadAttachment') {
            $controller->uploadAttachment();
        } elseif ($action == 'removeAttachment') {
            $controller->removeAttachment();
        } elseif ($action == 'transactions') {
            $controller->transactions();
        } elseif ($action == 'addTransaction') {
            $controller->addTransaction();
        } elseif ($action == 'deleteTransaction') {
            $controller->deleteTransaction();
        } elseif ($action == 'getSummaryJson') {
            $controller->getSummaryJson();
        } elseif ($action == 'getInstallmentsJson') {
            $controller->getInstallmentsJson();
        } else {
            // Dashboard financeiro removido — redirecionar para pagamentos
            $controller->payments();
        }
        break;

    // ── Atalhos de menu fiscal ──
    case 'financial_payments':
        require_once 'app/controllers/FinancialController.php';
        $controller = new FinancialController();
        $controller->payments();
        break;

    case 'financial_transactions':
        require_once 'app/controllers/FinancialController.php';
        $controller = new FinancialController();
        $controller->transactions();
        break;

    default:
        http_response_code(404);
        require 'app/views/errors/404.php';
        break;
}
