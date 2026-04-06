<?php
/**
 * Akti Master Admin - Ponto de entrada (Router)
 */

session_start();

// Configurações
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

// Models
require_once __DIR__ . '/app/models/AdminUser.php';
require_once __DIR__ . '/app/models/Plan.php';
require_once __DIR__ . '/app/models/TenantClient.php';
require_once __DIR__ . '/app/models/AdminLog.php';
require_once __DIR__ . '/app/models/Migration.php';
require_once __DIR__ . '/app/models/GitVersion.php';
require_once __DIR__ . '/app/models/Backup.php';
require_once __DIR__ . '/app/models/NginxLog.php';

// Controllers
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/PlanController.php';
require_once __DIR__ . '/app/controllers/ClientController.php';
require_once __DIR__ . '/app/controllers/MigrationController.php';
require_once __DIR__ . '/app/controllers/GitController.php';
require_once __DIR__ . '/app/controllers/BackupController.php';
require_once __DIR__ . '/app/controllers/LogController.php';

// Conexão com banco master
$db = Database::getInstance()->getConnection();

// Roteamento
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'index';

// Páginas que não requerem autenticação
$publicPages = ['login'];

// Verificar autenticação
if (!in_array($page, $publicPages) && !isset($_SESSION['admin_id'])) {
    header('Location: ?page=login');
    exit;
}

// Roteamento por página
switch ($page) {
    case 'login':
        $controller = new AuthController($db);
        if ($action === 'authenticate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->authenticate();
        } elseif ($action === 'logout') {
            $controller->logout();
        } else {
            $controller->login();
        }
        break;

    case 'dashboard':
        $controller = new DashboardController($db);
        $controller->index();
        break;

    case 'plans':
        $controller = new PlanController($db);
        switch ($action) {
            case 'create':
                $controller->create();
                break;
            case 'store':
                $controller->store();
                break;
            case 'edit':
                $controller->edit();
                break;
            case 'update':
                $controller->update();
                break;
            case 'delete':
                $controller->delete();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    case 'clients':
        $controller = new ClientController($db);
        switch ($action) {
            case 'create':
                $controller->create();
                break;
            case 'store':
                $controller->store();
                break;
            case 'edit':
                $controller->edit();
                break;
            case 'update':
                $controller->update();
                break;
            case 'delete':
                $controller->delete();
                break;
            case 'toggleActive':
                $controller->toggleActive();
                break;
            case 'createTenantUser':
                $controller->createTenantUser();
                break;
            case 'getPlanLimits':
                $controller->getPlanLimits();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    case 'migrations':
        $controller = new MigrationController($db);
        switch ($action) {
            case 'apply':
                $controller->apply();
                break;
            case 'results':
                $controller->results();
                break;
            case 'compareDetail':
                $controller->compareDetail();
                break;
            case 'users':
                $controller->users();
                break;
            case 'createUser':
                $controller->createUser();
                break;
            case 'toggleUser':
                $controller->toggleUser();
                break;
            case 'dbUsers':
                $controller->dbUsers();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    case 'git':
        $controller = new GitController($db);
        switch ($action) {
            case 'fetch':
                $controller->fetch();
                break;
            case 'fetchAll':
                $controller->fetchAll();
                break;
            case 'pull':
                $controller->pull();
                break;
            case 'pullAll':
                $controller->pullAll();
                break;
            case 'forceReset':
                $controller->forceReset();
                break;
            case 'detail':
                $controller->detail();
                break;
            case 'checkout':
                $controller->checkout();
                break;
            case 'diagnoseJson':
                $controller->diagnoseJson();
                break;
            case 'loadRepos':
                $controller->loadRepos();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    case 'backup':
        $controller = new BackupController($db);
        switch ($action) {
            case 'run':
                $controller->run();
                break;
            case 'download':
                $controller->download();
                break;
            case 'delete':
                $controller->delete();
                break;
            case 'diagnoseJson':
                $controller->diagnoseJson();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    case 'logs':
        $controller = new LogController($db);
        switch ($action) {
            case 'read':
                $controller->read();
                break;
            case 'search':
                $controller->search();
                break;
            case 'download':
                $controller->download();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    default:
        if (isset($_SESSION['admin_id'])) {
            header('Location: ?page=dashboard');
        } else {
            header('Location: ?page=login');
        }
        exit;
}
