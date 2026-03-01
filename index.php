<?php
session_start();

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

if (isset($_SESSION['user_id']) && $page !== 'login' && $action !== 'logout' && $action !== 'getSubcategories' && $action !== 'getInheritedGrades' && $needsPermission) {
    $db = (new Database())->getConnection();
    $user = new User($db);
    if (!$user->checkPermission($_SESSION['user_id'], $page)) {
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
        require_once 'app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
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
        } else {
            $controller->index();
        }
        break;

    default:
        http_response_code(404);
        require 'app/views/layout/header.php';
        echo "<div class='container mt-5 text-center py-5'><h2 class='text-muted'><i class='fas fa-search me-2'></i>Página não encontrada</h2><p class='text-muted'>A página que você procura não existe.</p><a href='' class='btn btn-primary mt-3'>Voltar ao Início</a></div>";
        require 'app/views/layout/footer.php';
        break;
}
