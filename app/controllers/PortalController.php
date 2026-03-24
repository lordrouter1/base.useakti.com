<?php
namespace Akti\Controllers;

use Akti\Models\PortalAccess;
use Akti\Models\PortalMessage;
use Akti\Models\Customer;
use Akti\Models\CompanySettings;
use Akti\Middleware\PortalAuthMiddleware;
use Akti\Services\PortalLang;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Core\Security;
use Akti\Utils\Input;
use PDO;

/**
 * Controller: PortalController
 * Controller principal do Portal do Cliente.
 * Gerencia autenticação, dashboard e navegação do portal.
 *
 * Rotas: ?page=portal&action=<action>
 *
 * @package Akti\Controllers
 */
class PortalController
{
    private PDO $db;
    private PortalAccess $portalAccess;
    private ?CompanySettings $companySettings = null;
    private array $company = [];

    public function __construct()
    {
        $this->db = (new \Database())->getConnection();
        $this->portalAccess = new PortalAccess($this->db);

        // Carregar configurações da empresa
        $this->companySettings = new CompanySettings($this->db);
        $this->company = $this->companySettings->getAll();

        // Inicializar sistema de tradução
        $lang = $_SESSION['portal_lang'] ?? 'pt-br';
        PortalLang::init($lang);
    }

    // ══════════════════════════════════════════════
    // INDEX (Redireciona para login ou dashboard)
    // ══════════════════════════════════════════════

    /**
     * Página inicial do portal — redireciona conforme estado de autenticação.
     */
    public function index(): void
    {
        // Verificar se o portal está habilitado
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        if (PortalAuthMiddleware::isAuthenticated()) {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        header('Location: ?page=portal&action=login');
        exit;
    }

    // ══════════════════════════════════════════════
    // AUTENTICAÇÃO
    // ══════════════════════════════════════════════

    /**
     * Exibe a tela de login / Processa o login (POST).
     */
    public function login(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        // Se já está logado, vai pro dashboard
        if (PortalAuthMiddleware::isAuthenticated()) {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $error = '';
        $successMsg = '';
        $config = $this->portalAccess->getAllConfig();

        // Verificar mensagens de sessão
        if (isset($_GET['registered'])) {
            $successMsg = __p('register_success');
        }
        if (isset($_GET['expired'])) {
            $error = __p('login_session_expired');
        }

        // POST — processar login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = Input::post('email', 'email');
            $password = Input::post('password');

            if (empty($email)) {
                $error = __p('error_required');
            } else {
                $access = $this->portalAccess->findByEmail($email);

                if (!$access) {
                    $error = __p('login_error');
                } elseif (!$access['is_active']) {
                    $error = __p('login_inactive');
                } elseif ($this->portalAccess->isLocked($access)) {
                    $remainingMin = max(1, (int) ceil((strtotime($access['locked_until']) - time()) / 60));
                    $error = __p('login_locked', ['minutes' => $remainingMin]);
                } elseif (empty($access['password_hash'])) {
                    // Conta sem senha (apenas link mágico)
                    $error = __p('login_error');
                } elseif (!$this->portalAccess->verifyPassword($password, $access['password_hash'])) {
                    $this->portalAccess->registerFailedAttempt($access['id']);
                    $error = __p('login_error');
                } else {
                    // Login bem-sucedido
                    $ip = PortalAuthMiddleware::getClientIp();
                    $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);

                    // ── Prevenir session fixation ──
                    session_regenerate_id(true);

                    // Buscar dados do cliente
                    $customer = (new Customer($this->db))->readOne($access['customer_id']);
                    $customerName = $customer ? $customer['name'] : 'Cliente';

                    PortalAuthMiddleware::login(
                        $access['customer_id'],
                        $access['id'],
                        $customerName,
                        $access['email'],
                        $access['lang'] ?? 'pt-br'
                    );

                    EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
                        'customer_id' => $access['customer_id'],
                        'email'       => $access['email'],
                        'ip'          => $ip,
                    ]));

                    header('Location: ?page=portal&action=dashboard');
                    exit;
                }
            }
        }

        // Renderizar view de login
        $allowSelfRegister = ($config['allow_self_register'] ?? '0') === '1';
        $company = $this->company;

        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/login.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    /**
     * Login via link mágico.
     */
    public function loginMagic(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        $token = Input::get('token');

        if (empty($token)) {
            header('Location: ?page=portal&action=login');
            exit;
        }

        $access = $this->portalAccess->validateMagicToken($token);

        if (!$access) {
            // Token inválido ou expirado
            $_SESSION['portal_login_error'] = 'Link de acesso inválido ou expirado.';
            header('Location: ?page=portal&action=login');
            exit;
        }

        // Login bem-sucedido via link mágico
        $ip = PortalAuthMiddleware::getClientIp();
        $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);
        $this->portalAccess->invalidateMagicToken($access['id']); // Uso único

        // ── Prevenir session fixation ──
        session_regenerate_id(true);

        $customer = (new Customer($this->db))->readOne($access['customer_id']);
        $customerName = $customer ? $customer['name'] : 'Cliente';

        PortalAuthMiddleware::login(
            $access['customer_id'],
            $access['id'],
            $customerName,
            $access['email'],
            $access['lang'] ?? 'pt-br'
        );

        EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
            'customer_id' => $access['customer_id'],
            'email'       => $access['email'],
            'ip'          => $ip,
            'method'      => 'magic_link',
        ]));

        header('Location: ?page=portal&action=dashboard');
        exit;
    }

    /**
     * Logout do portal.
     */
    public function logout(): void
    {
        PortalAuthMiddleware::logout();
        header('Location: ?page=portal&action=login');
        exit;
    }

    /**
     * Auto-registro do cliente.
     */
    public function register(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        $config = $this->portalAccess->getAllConfig();

        if (($config['allow_self_register'] ?? '0') !== '1') {
            header('Location: ?page=portal&action=login');
            exit;
        }

        if (PortalAuthMiddleware::isAuthenticated()) {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $error = '';
        $formData = [];
        $company = $this->company;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = [
                'name'     => Input::post('name'),
                'email'    => Input::post('email', 'email'),
                'phone'    => Input::post('phone'),
                'document' => Input::post('document'),
                'password' => Input::post('password'),
                'password_confirm' => Input::post('password_confirm'),
            ];

            // Validação
            if (empty($formData['name']) || empty($formData['email']) || empty($formData['password'])) {
                $error = __p('error_required');
            } elseif ($formData['password'] !== $formData['password_confirm']) {
                $error = __p('register_password_mismatch');
            } elseif ($this->portalAccess->emailExists($formData['email'])) {
                $error = __p('register_email_exists');
            } else {
                // Verificar se já existe um cliente com este e-mail
                $customerModel = new Customer($this->db);
                $existingCustomer = $this->findCustomerByEmail($formData['email']);

                if ($existingCustomer) {
                    // Vincular ao cliente existente
                    $customerId = $existingCustomer['id'];
                } else {
                    // Criar novo cliente
                    $customerId = $customerModel->create([
                        'name'     => $formData['name'],
                        'email'    => $formData['email'],
                        'phone'    => $formData['phone'],
                        'document' => $formData['document'],
                        'address'  => '',
                        'photo'    => '',
                    ]);
                }

                // Criar acesso ao portal
                $this->portalAccess->create([
                    'customer_id' => $customerId,
                    'email'       => $formData['email'],
                    'password'    => $formData['password'],
                    'lang'        => 'pt-br',
                ]);

                header('Location: ?page=portal&action=login&registered=1');
                exit;
            }
        }

        $allowSelfRegister = true;

        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/register.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    // ══════════════════════════════════════════════
    // DASHBOARD
    // ══════════════════════════════════════════════

    /**
     * Dashboard do cliente — tela principal após login.
     */
    public function dashboard(): void
    {
        PortalAuthMiddleware::check();

        $customerId   = PortalAuthMiddleware::getCustomerId();
        $customerName = $_SESSION['portal_customer_name'] ?? 'Cliente';

        // Dados do dashboard
        $stats         = $this->portalAccess->getDashboardStats($customerId);
        $recentOrders  = $this->portalAccess->getRecentOrders($customerId, 5);
        $notifications = $this->portalAccess->getRecentNotifications($customerId, 5);
        $unreadMessages = (new PortalMessage($this->db))->countUnread($customerId);
        $company       = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/dashboard.php';
        require 'app/views/portal/layout/footer.php';
    }

    // ══════════════════════════════════════════════
    // PERFIL
    // ══════════════════════════════════════════════

    /**
     * Exibe perfil do cliente.
     */
    public function profile(): void
    {
        PortalAuthMiddleware::check();

        $customerId = PortalAuthMiddleware::getCustomerId();
        $customer   = (new Customer($this->db))->readOne($customerId);
        $access     = $this->portalAccess->findByCustomerId($customerId);
        $company    = $this->company;
        $languages  = PortalLang::getAvailableLanguages();
        $message    = '';

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/profile/index.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Atualiza perfil do cliente (POST).
     */
    public function updateProfile(): void
    {
        PortalAuthMiddleware::check();

        $customerId = PortalAuthMiddleware::getCustomerId();
        $accessId   = PortalAuthMiddleware::getAccessId();

        $data = [
            'name'    => Input::post('name'),
            'phone'   => Input::post('phone'),
            'address' => Input::post('address'),
        ];

        // Atualizar dados do cliente
        $customerModel = new Customer($this->db);
        $customer = $customerModel->readOne($customerId);

        if ($customer) {
            $customerModel->update([
                'id'             => $customerId,
                'name'           => $data['name'] ?: $customer['name'],
                'email'          => $customer['email'],
                'phone'          => $data['phone'] ?: $customer['phone'],
                'document'       => $customer['document'],
                'address'        => $data['address'] ?: $customer['address'],
                'price_table_id' => $customer['price_table_id'] ?? null,
            ]);
        }

        // Atualizar idioma se alterado
        $lang = Input::post('lang');
        if ($lang && in_array($lang, array_keys(PortalLang::getAvailableLanguages()))) {
            $this->portalAccess->update($accessId, ['lang' => $lang]);
            $_SESSION['portal_lang'] = $lang;
        }

        // Atualizar senha se informada
        $newPassword = Input::post('new_password');
        $newPasswordConfirm = Input::post('new_password_confirm');
        if (!empty($newPassword)) {
            if ($newPassword === $newPasswordConfirm) {
                $this->portalAccess->update($accessId, ['password' => $newPassword]);
            }
        }

        // Atualizar nome na sessão
        if (!empty($data['name'])) {
            $_SESSION['portal_customer_name'] = $data['name'];
        }

        // Resposta JSON para AJAX
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => __p('profile_updated')]);
            exit;
        }

        header('Location: ?page=portal&action=profile&updated=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // MÉTODOS AUXILIARES (privados)
    // ══════════════════════════════════════════════

    /**
     * Verifica se o portal está habilitado.
     */
    private function isPortalEnabled(): bool
    {
        return $this->portalAccess->getConfig('portal_enabled', '1') === '1';
    }

    /**
     * Renderiza tela de portal desabilitado.
     */
    private function renderDisabled(): void
    {
        $company = $this->company;
        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/disabled.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    /**
     * Verifica se a requisição é AJAX.
     */
    private function isAjax(): bool
    {
        $xhrHeader = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strtolower($xhrHeader) === 'xmlhttprequest'
            || stripos($acceptHeader, 'application/json') !== false;
    }

    /**
     * Busca cliente por e-mail na tabela customers.
     */
    private function findCustomerByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
