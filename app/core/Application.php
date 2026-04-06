<?php
namespace Akti\Core;

use Akti\Middleware\CsrfMiddleware;
use Akti\Middleware\PortalAuthMiddleware;
use Akti\Middleware\SecurityHeadersMiddleware;
use Akti\Models\User;
use Psr\Container\ContainerInterface;

/**
 * Application — encapsula o ciclo de vida da requisição HTTP.
 *
 * Responsável por:
 *  - Aplicar security headers
 *  - Verificar tenant e sessão
 *  - Resolver autenticação e permissões
 *  - Despachar a rota
 */
class Application
{
    private string $basePath;
    private Router $router;
    private string $page;
    private string $action;
    private ContainerInterface $container;

    /** @var \PDO|null PDO de sessão */
    private $sessionDb;

    public function __construct(string $basePath, ContainerInterface $container)
    {
        $this->basePath = $basePath;
        $this->container = $container;
    }

    /**
     * Boot — inicializa security headers, sessão, tenant, router.
     */
    public function boot(): void
    {
        SecurityHeadersMiddleware::handle();

        \TenantManager::enforceTenantSession();

        // Inatividade de sessão
        if (isset($_SESSION['user_id'])) {
            $this->sessionDb = \Database::getInstance();
            \SessionGuard::checkInactivity($this->sessionDb);
            \SessionGuard::touch();
        }

        // Inatividade do portal
        if (isset($_SESSION['portal_customer_id'])) {
            PortalAuthMiddleware::checkInactivity(60);
        }

        // Inicializar router
        $this->router = new Router($this->basePath . '/app/config/routes.php', $this->container);
        $this->page   = $this->router->getPage();
        $this->action = $this->router->getAction();
    }

    /**
     * Handle — resolve autenticação, permissões e CSRF.
     * Retorna false se a requisição já foi despachada (keepalive, login redirect, etc).
     */
    public function handle(): bool
    {
        // Keepalive endpoint
        if ($this->page === 'session' && $this->action === 'keepalive') {
            $this->handleKeepalive();
            return false;
        }

        // Páginas públicas / before_auth
        if ($this->router->isPublicPage() || $this->router->hasBeforeAuth()) {
            if ($this->router->isPublicPage() && $this->page !== 'login') {
                CsrfMiddleware::handle();
                Security::generateCsrfToken();
                $this->router->dispatch();
                return false;
            }
        }

        // Auth check
        if (!isset($_SESSION['user_id'])) {
            if ($this->page !== 'login') {
                header('Location: ?page=login');
                exit;
            }
            CsrfMiddleware::handle();
            Security::generateCsrfToken();
            $this->router->dispatch();
            return false;
        }

        // Logado + page=login
        if ($this->page === 'login' && $this->action !== 'logout') {
            $redirect = !empty($_SESSION['is_master_admin']) ? '?page=master_dashboard' : '?';
            header('Location: ' . $redirect);
            exit;
        }
        if ($this->page === 'login' && $this->action === 'logout') {
            $this->router->dispatch();
            return false;
        }

        // Master pages — bypass module bootloader and permission check
        $routeConfig = $this->router->getRouteConfig($this->page);
        if (!empty($routeConfig['master_only'])) {
            if (empty($_SESSION['is_master_admin'])) {
                header('Location: ?page=login');
                exit;
            }
            CsrfMiddleware::handle();
            Security::generateCsrfToken();
            return true;
        }

        // Module bootloader check
        if (!ModuleBootloader::canAccessPage($this->page)) {
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo indisponível para este tenant.<br>Página bloqueada pelo bootloader: <strong>" . strtoupper($this->page) . "</strong>.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        // Permission check via menu.php
        $this->checkPermissions();

        // CSRF
        CsrfMiddleware::handle();
        Security::generateCsrfToken();

        return true;
    }

    /**
     * Dispatch — despacha a rota autenticada.
     */
    public function dispatch(): void
    {
        $this->router->dispatch();
    }

    // ── Helpers internos ──

    private function handleKeepalive(): void
    {
        header('Content-Type: application/json');
        if (isset($_SESSION['user_id'])) {
            \SessionGuard::touch();
            $data = \SessionGuard::getJsSessionData($this->sessionDb);
            echo json_encode(['success' => true, 'remaining_seconds' => $data['remaining_seconds']]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'session_expired' => true]);
        }
        exit;
    }

    private function checkPermissions(): void
    {
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

        $needsPermission = isset($flatMenuConfig[$this->page]) && !empty($flatMenuConfig[$this->page]['permission']);
        $permissionPage = $this->page;
        if (isset($flatMenuConfig[$this->page]['permission_alias'])) {
            $permissionPage = $flatMenuConfig[$this->page]['permission_alias'];
        }

        $permissionBypassActions = [
            'getSubcategories', 'getInheritedGrades', 'getInheritedSectors',
            'getProductsForExport', 'exportToProducts',
        ];

        if ($needsPermission && !in_array($this->action, $permissionBypassActions)) {
            $db = \Database::getInstance();
            $user = new User($db);
            if (!$user->checkPermission($_SESSION['user_id'], $permissionPage)) {
                require 'app/views/layout/header.php';
                echo "<div class='container mt-5'><div class='alert alert-danger'><i class='fas fa-ban me-2'></i>Acesso Negado.<br>Você não tem permissão para acessar o módulo: <strong>" . strtoupper($this->page) . "</strong>.</div></div>";
                require 'app/views/layout/footer.php';
                exit;
            }
        }
    }
}
