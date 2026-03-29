<?php
namespace Akti\Controllers;

use Akti\Models\PortalAccess;
use Akti\Models\PortalMessage;
use Akti\Models\Customer;
use Akti\Models\CompanySettings;
use Akti\Models\CatalogLink;
use Akti\Models\Logger;
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
    private Logger $logger;
    private array $company = [];

    public function __construct()
    {
        $this->db = (new \Database())->getConnection();
        $this->portalAccess = new PortalAccess($this->db);
        $this->logger = new Logger($this->db);

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
        if (isset($_GET['password_reset'])) {
            $successMsg = __p('reset_success');
        }

        // POST — processar login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = Input::post('email', 'email');
            $password = Input::post('password');

            if (empty($email)) {
                $error = __p('error_required');
            } else {
                $access = $this->portalAccess->findByEmail($email);

                // Ler config require_password
                $requirePassword = ($config['require_password'] ?? '0') === '1';

                if (!$access) {
                    $error = __p('login_error');
                } elseif (!$access['is_active']) {
                    $error = __p('login_inactive');
                } elseif ($this->portalAccess->isLocked($access)) {
                    $remainingMin = max(1, (int) ceil((strtotime($access['locked_until']) - time()) / 60));
                    $error = __p('login_locked', ['minutes' => $remainingMin]);
                } elseif (empty($access['password_hash']) && !$requirePassword) {
                    // Conta sem senha e senha não obrigatória — sugerir magic link
                    $error = __p('login_use_magic_link');
                } elseif (empty($access['password_hash']) && $requirePassword) {
                    // Conta sem senha mas senha obrigatória — erro genérico
                    $error = __p('login_error');
                } elseif (!$this->portalAccess->verifyPassword($password, $access['password_hash'])) {
                    $this->portalAccess->registerFailedAttempt($access['id']);
                    $this->logger->log('portal_login_failed', "E-mail: {$email} | IP: " . PortalAuthMiddleware::getClientIp());
                    $error = __p('login_error');
                } else {
                    // Login bem-sucedido
                    $ip = PortalAuthMiddleware::getClientIp();
                    $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);

                    // ── Verificar se deve trocar senha temporária ──
                    if (!empty($access['must_change_password'])) {
                        $resetToken = $this->portalAccess->generateResetToken($access['id']);
                        header('Location: ?page=portal&action=setupPassword&token=' . urlencode($resetToken));
                        exit;
                    }

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

                    // Guardar avatar na sessão para exibição no topbar
                    $_SESSION['portal_customer_avatar'] = $access['avatar'] ?? '';

                    $this->logger->log('portal_login', "Cliente ID: {$access['customer_id']} | Nome: {$customerName} | E-mail: {$email} | IP: {$ip}");

                    // ── Verificar 2FA ──
                    $config = $this->portalAccess->getAllConfig();
                    $global2fa = ($config['enable_2fa'] ?? '0') === '1';
                    if ($global2fa && $this->portalAccess->is2faEnabled($access['id'])) {
                        $code = $this->portalAccess->generate2faCode($access['id']);
                        PortalAuthMiddleware::set2faPending(true);

                        // TODO: Enviar e-mail com o código 2FA
                        // mail($access['email'], 'Código de Verificação', "Seu código: {$code}");

                        EventDispatcher::dispatch('portal.customer.2fa_requested', new Event('portal.customer.2fa_requested', [
                            'customer_id' => $access['customer_id'],
                            'email'       => $access['email'],
                        ]));

                        header('Location: ?page=portal&action=verify2fa');
                        exit;
                    }

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
     * Se o usuário ainda não tem senha cadastrada, redireciona para a
     * página de cadastro de senha (setupPassword).
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

        // Se o usuário ainda não tem senha, redirecionar para cadastro de senha
        if (empty($access['password_hash']) || !empty($access['must_change_password'])) {
            header('Location: ?page=portal&action=setupPassword&token=' . urlencode($token));
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
     * Página temporária para cadastrar senha (via magic link ou senha temporária).
     * Aceita magic_token ou reset_token.
     * GET: valida token e exibe formulário / POST: salva a senha e faz login.
     */
    public function setupPassword(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        $error = '';
        $successMsg = '';
        $company = $this->company;
        $token = Input::get('token') ?: Input::post('token');

        if (empty($token)) {
            header('Location: ?page=portal&action=login');
            exit;
        }

        // Tentar validar como magic_token primeiro, depois como reset_token
        $access = $this->portalAccess->validateMagicToken($token);
        $tokenType = 'magic';

        if (!$access) {
            $access = $this->portalAccess->validateResetToken($token);
            $tokenType = 'reset';
        }

        if (!$access) {
            $_SESSION['portal_login_error'] = __p('setup_password_token_expired');
            header('Location: ?page=portal&action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = Input::post('password');
            $confirmPassword = Input::post('password_confirm');

            if (empty($newPassword) || empty($confirmPassword)) {
                $error = __p('error_required');
            } elseif ($newPassword !== $confirmPassword) {
                $error = __p('register_password_mismatch');
            } elseif (!$this->isPasswordStrong($newPassword)) {
                $error = __p('profile_password_weak');
            } else {
                // Salvar a senha (resetPassword já limpa must_change_password)
                $this->portalAccess->resetPassword($access['id'], $newPassword);

                // Invalidar o token usado
                if ($tokenType === 'magic') {
                    $this->portalAccess->invalidateMagicToken($access['id']);
                } else {
                    $this->portalAccess->invalidateResetToken($access['id']);
                }

                // Fazer login automaticamente
                $ip = PortalAuthMiddleware::getClientIp();
                $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);

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
                    'method'      => 'password_setup',
                ]));

                header('Location: ?page=portal&action=dashboard');
                exit;
            }
        }

        // Renderizar formulário de cadastro de senha
        $validToken = true;
        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/set_password.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    /**
     * Logout do portal.
     */
    public function logout(): void
    {
        $customerId = PortalAuthMiddleware::getCustomerId();
        $email = $_SESSION['portal_email'] ?? '';
        $this->logger->log('portal_logout', "E-mail: {$email} | IP: " . PortalAuthMiddleware::getClientIp(), $customerId);

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

        // Auto-marcar parcelas atrasadas
        $this->portalAccess->markOverdueInstallments($customerId);

        // Dados do dashboard
        $stats          = $this->portalAccess->getDashboardStats($customerId);
        $recentOrders   = $this->portalAccess->getRecentOrders($customerId, 5);
        $notifications  = $this->portalAccess->getRecentNotifications($customerId, 5);
        $unreadMessages = (new PortalMessage($this->db))->countUnread($customerId);
        $trackingCount  = count($this->portalAccess->getTrackingOrders($customerId));
        $documentsCount = count($this->portalAccess->getDocumentsByCustomer($customerId));
        $company        = $this->company;

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
        $accessId   = PortalAuthMiddleware::getAccessId();
        $customer   = (new Customer($this->db))->readOne($customerId);
        $access     = $this->portalAccess->findByCustomerId($customerId);
        $company    = $this->company;
        $languages  = PortalLang::getAvailableLanguages();
        $message    = '';

        // Fase 7 — Avatar e 2FA
        $avatarPath   = $access['avatar'] ?? '';
        $is2faEnabled = (($access['two_factor_enabled'] ?? 0) == 1);

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

        // Atualizar senha se informada (exige senha atual + validação de força)
        $newPassword = Input::post('new_password');
        $newPasswordConfirm = Input::post('new_password_confirm');
        $currentPassword = Input::post('current_password');

        if (!empty($newPassword)) {
            // Buscar acesso para verificar senha atual
            $access = $this->portalAccess->findById($accessId);

            // Validar senha atual
            if (empty($currentPassword)) {
                $passwordError = __p('profile_password_current_required');
            } elseif (!empty($access['password_hash']) && !$this->portalAccess->verifyPassword($currentPassword, $access['password_hash'])) {
                $passwordError = __p('profile_password_current_wrong');
            } elseif ($newPassword !== $newPasswordConfirm) {
                $passwordError = __p('register_password_mismatch');
            } elseif (!$this->isPasswordStrong($newPassword)) {
                $passwordError = __p('profile_password_weak');
            } else {
                $this->portalAccess->update($accessId, ['password' => $newPassword]);
                $this->logger->log('portal_password_changed', "E-mail: " . ($_SESSION['portal_email'] ?? '') . " | IP: " . PortalAuthMiddleware::getClientIp(), $customerId);
            }

            if (isset($passwordError)) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $passwordError]);
                    exit;
                }
                header('Location: ?page=portal&action=profile&password_error=' . urlencode($passwordError));
                exit;
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
    // MAGIC LINK — Solicitar link de acesso
    // ══════════════════════════════════════════════

    /**
     * Processa solicitação de link mágico (POST).
     * Gera token e exibe mensagem genérica (sem vazar se o e-mail existe).
     */
    public function requestMagicLink(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=login');
            exit;
        }

        $email = Input::post('magic_email', 'email');
        $successMsg = __p('magic_link_requested');

        if (!empty($email)) {
            $access = $this->portalAccess->findByEmail($email);
            if ($access && $access['is_active']) {
                // Gerar token (lê expiryHours da config automaticamente)
                $token = $this->portalAccess->generateMagicToken($access['id']);

                // TODO: Enviar e-mail com o link mágico
                // $magicLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
                //              $_SERVER['HTTP_HOST'] . '?page=portal&action=loginMagic&token=' . $token;
                // mail($email, 'Seu link de acesso', $magicLink);

                EventDispatcher::dispatch('portal.magic_link.requested', new Event('portal.magic_link.requested', [
                    'customer_id' => $access['customer_id'],
                    'email'       => $email,
                    'ip'          => PortalAuthMiddleware::getClientIp(),
                ]));
            }
        }

        // Sempre exibe mensagem de sucesso — NUNCA vazar se o email existe ou não
        $error = '';
        $config = $this->portalAccess->getAllConfig();
        $allowSelfRegister = ($config['allow_self_register'] ?? '0') === '1';
        $company = $this->company;

        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/login.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    // ══════════════════════════════════════════════
    // RECUPERAÇÃO DE SENHA (Forgot / Reset)
    // ══════════════════════════════════════════════

    /**
     * Formulário/processamento de "Esqueci minha senha".
     * GET: renderiza form / POST: gera token de reset.
     */
    public function forgotPassword(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        if (PortalAuthMiddleware::isAuthenticated()) {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $error = '';
        $successMsg = '';
        $company = $this->company;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = Input::post('email', 'email');

            if (empty($email)) {
                $error = __p('error_required');
            } else {
                $access = $this->portalAccess->findByEmail($email);
                if ($access && $access['is_active']) {
                    // Gerar token de reset (validade de 1 hora)
                    $token = $this->portalAccess->generateResetToken($access['id'], 1);

                    // TODO: Enviar e-mail com o link de reset
                    // $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
                    //              $_SERVER['HTTP_HOST'] . '?page=portal&action=resetPassword&token=' . $token;
                    // mail($email, 'Recuperação de senha', $resetLink);

                    EventDispatcher::dispatch('portal.password_reset.requested', new Event('portal.password_reset.requested', [
                        'customer_id' => $access['customer_id'],
                        'email'       => $email,
                        'ip'          => PortalAuthMiddleware::getClientIp(),
                    ]));
                }

                // Sempre exibe mensagem de sucesso — NUNCA vazar se o email existe
                $successMsg = __p('forgot_success');
            }
        }

        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/forgot.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    /**
     * Formulário/processamento de redefinição de senha via token.
     * GET: valida token e exibe form / POST: salva nova senha.
     */
    public function resetPassword(): void
    {
        if (!$this->isPortalEnabled()) {
            $this->renderDisabled();
            return;
        }

        $error = '';
        $successMsg = '';
        $company = $this->company;
        $token = Input::get('token') ?: Input::post('token');

        if (empty($token)) {
            header('Location: ?page=portal&action=forgotPassword');
            exit;
        }

        // Validar token
        $access = $this->portalAccess->validateResetToken($token);
        if (!$access) {
            $error = __p('reset_invalid_token');
            require 'app/views/portal/layout/header_auth.php';
            require 'app/views/portal/auth/forgot.php';
            require 'app/views/portal/layout/footer_auth.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = Input::post('password');
            $confirmPassword = Input::post('password_confirm');

            if (empty($newPassword) || empty($confirmPassword)) {
                $error = __p('error_required');
            } elseif ($newPassword !== $confirmPassword) {
                $error = __p('register_password_mismatch');
            } elseif (!$this->isPasswordStrong($newPassword)) {
                $error = __p('profile_password_weak');
            } else {
                // Salvar nova senha e invalidar token
                $this->portalAccess->resetPassword($access['id'], $newPassword);
                $this->portalAccess->invalidateResetToken($access['id']);

                EventDispatcher::dispatch('portal.password_reset.completed', new Event('portal.password_reset.completed', [
                    'customer_id' => $access['customer_id'],
                    'email'       => $access['email'],
                    'ip'          => PortalAuthMiddleware::getClientIp(),
                ]));

                // Redirecionar para login com mensagem de sucesso
                header('Location: ?page=portal&action=login&password_reset=1');
                exit;
            }
        }

        // Renderizar form de nova senha
        $validToken = true;
        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/reset.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    // ══════════════════════════════════════════════
    // MEUS PEDIDOS (Fase 2)
    // ══════════════════════════════════════════════

    /**
     * Listagem de pedidos do cliente com filtro e paginação.
     * GET: ?page=portal&action=orders[&filter=all|open|approval|done][&p=1]
     */
    public function orders(): void
    {
        PortalAuthMiddleware::check();

        $customerId = PortalAuthMiddleware::getCustomerId();
        $filter     = Input::get('filter') ?: 'all';
        $page       = max(1, (int) (Input::get('p') ?: 1));
        $perPage    = 10;
        $offset     = ($page - 1) * $perPage;

        // Validar filtro
        $validFilters = ['all', 'open', 'approval', 'done'];
        if (!in_array($filter, $validFilters)) {
            $filter = 'all';
        }

        $orders     = $this->portalAccess->getOrdersByCustomer($customerId, $filter, $perPage, $offset);
        $totalCount = $this->portalAccess->countOrdersByCustomer($customerId, $filter);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));

        // Contadores para badges nas tabs
        $countAll      = $this->portalAccess->countOrdersByCustomer($customerId, 'all');
        $countOpen     = $this->portalAccess->countOrdersByCustomer($customerId, 'open');
        $countApproval = $this->portalAccess->countOrdersByCustomer($customerId, 'approval');
        $countDone     = $this->portalAccess->countOrdersByCustomer($customerId, 'done');

        $company = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/orders/index.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Detalhe completo de um pedido.
     * GET: ?page=portal&action=orderDetail&id=X
     */
    public function orderDetail(): void
    {
        PortalAuthMiddleware::check();

        $customerId = PortalAuthMiddleware::getCustomerId();
        $orderId    = (int) Input::get('id');

        if ($orderId <= 0) {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        // Buscar pedido (valida pertence ao cliente no Model via WHERE customer_id)
        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);

        if (!$order) {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        // Dados complementares
        $items        = $this->portalAccess->getOrderItems($orderId);
        $installments = $this->portalAccess->getOrderInstallments($orderId);
        $extraCosts   = $this->portalAccess->getOrderExtraCosts($orderId);
        $timeline     = $this->portalAccess->getOrderTimeline($order);

        // Buscar link de catálogo ativo (para pedidos em fase de orçamento)
        $catalogUrl = null;
        try {
            $catalogModel = new CatalogLink($this->db);
            $activeLink = $catalogModel->findActiveByOrder($orderId);
            if ($activeLink) {
                $catalogUrl = CatalogLink::buildUrl($activeLink['token']);
            }
        } catch (\Exception $e) {
            // Tabela pode não existir — ignorar
        }

        // Config de aprovação
        $allowApproval = $this->portalAccess->getConfig('allow_order_approval', '1') === '1';

        // Contexto focado de aprovação (vindo da aba Aprovação)
        $approvalContext = (Input::get('context') === 'approval')
            && ($order['customer_approval_status'] ?? '') === 'pendente'
            && $allowApproval;

        // Mensagens flash
        $successMsg = '';
        if (isset($_GET['approved'])) {
            $successMsg = __p('approval_success');
            $approvalContext = false; // Já aprovou, mostrar view completa
        }
        if (isset($_GET['rejected'])) {
            $successMsg = __p('approval_rejected');
            $approvalContext = false; // Já recusou, mostrar view completa
        }
        if (isset($_GET['cancelled'])) {
            $successMsg = __p('approval_cancelled');
        }

        $company = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/orders/detail.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Aprovar orçamento (POST).
     * POST: ?page=portal&action=approveOrder
     */
    public function approveOrder(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $orderId    = (int) Input::post('id');
        $notes      = Input::post('notes');
        $ip         = PortalAuthMiddleware::getClientIp();

        // Validar config
        if ($this->portalAccess->getConfig('allow_order_approval', '1') !== '1') {
            header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&error=disabled');
            exit;
        }

        // Verificar que o pedido pertence ao cliente e está pendente
        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        $approvalStatus = $order['customer_approval_status'] ?? null;
        if ($approvalStatus !== 'pendente') {
            header('Location: ?page=portal&action=orderDetail&id=' . $orderId);
            exit;
        }

        $success = $this->portalAccess->updateApprovalStatus($orderId, $customerId, 'aprovado', $ip, $notes);

        if ($success) {
            $this->logger->log('portal_order_approved', "Pedido #{$orderId} aprovado | IP: {$ip}", $customerId);
            EventDispatcher::dispatch('portal.order.approved', new Event('portal.order.approved', [
                'order_id'    => $orderId,
                'customer_id' => $customerId,
                'ip'          => $ip,
                'notes'       => $notes,
            ]));
        }

        header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&approved=1');
        exit;
    }

    /**
     * Recusar orçamento (POST).
     * POST: ?page=portal&action=rejectOrder
     */
    public function rejectOrder(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $orderId    = (int) Input::post('id');
        $notes      = Input::post('notes');
        $ip         = PortalAuthMiddleware::getClientIp();

        // Validar config
        if ($this->portalAccess->getConfig('allow_order_approval', '1') !== '1') {
            header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&error=disabled');
            exit;
        }

        // Verificar que o pedido pertence ao cliente e está pendente
        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        $approvalStatus = $order['customer_approval_status'] ?? null;
        if ($approvalStatus !== 'pendente') {
            header('Location: ?page=portal&action=orderDetail&id=' . $orderId);
            exit;
        }

        $success = $this->portalAccess->updateApprovalStatus($orderId, $customerId, 'recusado', $ip, $notes);

        if ($success) {
            $this->logger->log('portal_order_rejected', "Pedido #{$orderId} recusado | IP: {$ip}", $customerId);
            EventDispatcher::dispatch('portal.order.rejected', new Event('portal.order.rejected', [
                'order_id'    => $orderId,
                'customer_id' => $customerId,
                'ip'          => $ip,
                'notes'       => $notes,
            ]));
        }

        header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&rejected=1');
        exit;
    }

    /**
     * Cancelar aprovação/rejeição (voltar para pendente) — POST.
     * POST: ?page=portal&action=cancelApproval
     */
    public function cancelApproval(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $orderId    = (int) Input::post('id');
        $ip         = PortalAuthMiddleware::getClientIp();

        // Validar config
        if ($this->portalAccess->getConfig('allow_order_approval', '1') !== '1') {
            header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&error=disabled');
            exit;
        }

        // Verificar que o pedido pertence ao cliente e está aprovado ou recusado
        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            header('Location: ?page=portal&action=orders');
            exit;
        }

        $approvalStatus = $order['customer_approval_status'] ?? null;
        if (!in_array($approvalStatus, ['aprovado', 'recusado'])) {
            header('Location: ?page=portal&action=orderDetail&id=' . $orderId);
            exit;
        }

        $success = $this->portalAccess->cancelApprovalStatus($orderId, $customerId, $ip);

        if ($success) {
            EventDispatcher::dispatch('portal.order.approval_cancelled', new Event('portal.order.approval_cancelled', [
                'order_id'       => $orderId,
                'customer_id'    => $customerId,
                'ip'             => $ip,
                'previous_status' => $approvalStatus,
            ]));
        }

        header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&cancelled=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // NOVO PEDIDO / ORÇAMENTO (Fase 3)
    // ══════════════════════════════════════════════

    /**
     * Página de criação de novo pedido (catálogo de produtos + carrinho).
     * GET: ?page=portal&action=newOrder
     */
    public function newOrder(): void
    {
        PortalAuthMiddleware::check();

        // Verificar se a funcionalidade está habilitada
        if ($this->portalAccess->getConfig('allow_new_order', '1') !== '1') {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $search     = Input::get('q') ?: '';
        $categoryId = (int) (Input::get('category') ?: 0);
        $page       = max(1, (int) (Input::get('p') ?: 1));
        $perPage    = 12;
        $offset     = ($page - 1) * $perPage;

        $result     = $this->portalAccess->getAvailableProducts(
            $search ?: null,
            $categoryId ?: null,
            $perPage,
            $offset
        );

        $products   = $result['data'];
        $totalCount = $result['total'];
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $categories = $this->portalAccess->getCategories();

        // Carrinho da sessão
        $cart = $_SESSION['portal_cart'] ?? [];
        $cartCount = array_sum(array_column($cart, 'quantity'));

        $orderNotes = $this->portalAccess->getConfig('new_order_notes', '');
        $company    = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/orders/new.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Retorna produtos em JSON (para busca AJAX no catálogo).
     * GET: ?page=portal&action=getProducts&q=...&category=...&p=1
     */
    public function getProducts(): void
    {
        PortalAuthMiddleware::check();

        $search     = Input::get('q') ?: '';
        $categoryId = (int) (Input::get('category') ?: 0);
        $page       = max(1, (int) (Input::get('p') ?: 1));
        $perPage    = 12;
        $offset     = ($page - 1) * $perPage;

        $result = $this->portalAccess->getAvailableProducts(
            $search ?: null,
            $categoryId ?: null,
            $perPage,
            $offset
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'products' => $result['data'],
            'total'    => $result['total'],
            'pages'    => max(1, (int) ceil($result['total'] / $perPage)),
            'page'     => $page,
        ]);
        exit;
    }

    /**
     * Adiciona item ao carrinho (sessão). POST com JSON body.
     * POST: ?page=portal&action=addToCart
     */
    public function addToCart(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $productId = (int) Input::post('product_id');
        $quantity  = max(1, (int) (Input::post('quantity') ?: 1));

        if ($productId <= 0) {
            $this->jsonResponse(false, 'Produto inválido.');
        }

        // Buscar dados do produto
        $product = $this->portalAccess->getProductById($productId);
        if (!$product) {
            $this->jsonResponse(false, 'Produto não encontrado.');
        }

        // Inicializar carrinho
        if (!isset($_SESSION['portal_cart'])) {
            $_SESSION['portal_cart'] = [];
        }

        // Verificar se o produto já está no carrinho
        $found = false;
        foreach ($_SESSION['portal_cart'] as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['portal_cart'][] = [
                'product_id' => $productId,
                'name'       => $product['name'],
                'price'      => (float) $product['price'],
                'quantity'   => $quantity,
                'image'      => $product['main_image_path'] ?? null,
            ];
        }

        $cart = $_SESSION['portal_cart'];
        $cartCount = array_sum(array_column($cart, 'quantity'));
        $cartTotal = 0;
        foreach ($cart as $ci) {
            $cartTotal += $ci['price'] * $ci['quantity'];
        }

        $this->jsonResponse(true, __p('cart_item_added'), [
            'cart'      => $cart,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ]);
    }

    /**
     * Remove item do carrinho. POST.
     * POST: ?page=portal&action=removeFromCart
     */
    public function removeFromCart(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $productId = (int) Input::post('product_id');

        if (isset($_SESSION['portal_cart'])) {
            $_SESSION['portal_cart'] = array_values(array_filter(
                $_SESSION['portal_cart'],
                fn($item) => (int) $item['product_id'] !== $productId
            ));
        }

        $cart = $_SESSION['portal_cart'] ?? [];
        $cartCount = array_sum(array_column($cart, 'quantity'));
        $cartTotal = 0;
        foreach ($cart as $ci) {
            $cartTotal += $ci['price'] * $ci['quantity'];
        }

        $this->jsonResponse(true, __p('cart_item_removed'), [
            'cart'      => $cart,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ]);
    }

    /**
     * Atualiza quantidade de um item no carrinho. POST.
     * POST: ?page=portal&action=updateCartItem
     */
    public function updateCartItem(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $productId = (int) Input::post('product_id');
        $quantity  = max(0, (int) Input::post('quantity'));

        if (isset($_SESSION['portal_cart'])) {
            if ($quantity <= 0) {
                // Remover
                $_SESSION['portal_cart'] = array_values(array_filter(
                    $_SESSION['portal_cart'],
                    fn($item) => (int) $item['product_id'] !== $productId
                ));
            } else {
                foreach ($_SESSION['portal_cart'] as &$item) {
                    if ((int) $item['product_id'] === $productId) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
                unset($item);
            }
        }

        $cart = $_SESSION['portal_cart'] ?? [];
        $cartCount = array_sum(array_column($cart, 'quantity'));
        $cartTotal = 0;
        foreach ($cart as $ci) {
            $cartTotal += $ci['price'] * $ci['quantity'];
        }

        $this->jsonResponse(true, 'OK', [
            'cart'      => $cart,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ]);
    }

    /**
     * Retorna o carrinho atual em JSON.
     * GET: ?page=portal&action=getCart
     */
    public function getCart(): void
    {
        PortalAuthMiddleware::check();

        $cart = $_SESSION['portal_cart'] ?? [];
        $cartCount = array_sum(array_column($cart, 'quantity'));
        $cartTotal = 0;
        foreach ($cart as $ci) {
            $cartTotal += $ci['price'] * $ci['quantity'];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'cart'      => $cart,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ]);
        exit;
    }

    /**
     * Submete o pedido (cria orçamento a partir do carrinho). POST.
     * POST: ?page=portal&action=submitOrder
     */
    public function submitOrder(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=newOrder');
            exit;
        }

        // Verificar se a funcionalidade está habilitada
        if ($this->portalAccess->getConfig('allow_new_order', '1') !== '1') {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $notes      = Input::post('notes');
        $cart       = $_SESSION['portal_cart'] ?? [];

        if (empty($cart)) {
            if ($this->isAjax()) {
                $this->jsonResponse(false, __p('cart_empty'));
            }
            header('Location: ?page=portal&action=newOrder&error=empty');
            exit;
        }

        $orderId = $this->portalAccess->createPortalOrder($customerId, $cart, $notes);

        // Limpar carrinho
        $_SESSION['portal_cart'] = [];

        // Disparar evento
        EventDispatcher::dispatch('portal.order.created', new Event('portal.order.created', [
            'order_id'    => $orderId,
            'customer_id' => $customerId,
            'items_count' => count($cart),
            'ip'          => PortalAuthMiddleware::getClientIp(),
        ]));

        if ($this->isAjax()) {
            $this->jsonResponse(true, __p('new_order_success'), ['order_id' => $orderId]);
        }

        header('Location: ?page=portal&action=orderDetail&id=' . $orderId . '&created=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // FINANCEIRO (Fase 4)
    // ══════════════════════════════════════════════

    /**
     * Listagem de parcelas do cliente.
     * GET: ?page=portal&action=installments[&filter=all|open|paid]
     */
    public function installments(): void
    {
        PortalAuthMiddleware::check();

        // Verificar se a funcionalidade está habilitada
        if ($this->portalAccess->getConfig('allow_financial', '1') !== '1') {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $filter     = Input::get('filter') ?: 'all';
        $page       = max(1, (int) (Input::get('p') ?: 1));
        $perPage    = 15;
        $offset     = ($page - 1) * $perPage;

        // Auto-marcar parcelas atrasadas antes de listar
        $this->portalAccess->markOverdueInstallments($customerId);

        $validFilters = ['all', 'open', 'paid'];
        if (!in_array($filter, $validFilters)) {
            $filter = 'all';
        }

        $installments = $this->portalAccess->getInstallmentsByCustomer($customerId, $filter, $perPage, $offset);
        $totalCount   = $this->portalAccess->countInstallmentsByCustomer($customerId, $filter);
        $totalPages   = max(1, (int) ceil($totalCount / $perPage));
        $summary      = $this->portalAccess->getFinancialSummary($customerId);

        // Contadores para badges
        $countAll  = $this->portalAccess->countInstallmentsByCustomer($customerId, 'all');
        $countOpen = $this->portalAccess->countInstallmentsByCustomer($customerId, 'open');
        $countPaid = $this->portalAccess->countInstallmentsByCustomer($customerId, 'paid');

        $company = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/financial/index.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Detalhe de uma parcela.
     * GET: ?page=portal&action=installmentDetail&id=X
     */
    public function installmentDetail(): void
    {
        PortalAuthMiddleware::check();

        $customerId    = PortalAuthMiddleware::getCustomerId();
        $installmentId = (int) Input::get('id');

        if ($installmentId <= 0) {
            header('Location: ?page=portal&action=installments');
            exit;
        }

        $installment = $this->portalAccess->getInstallmentDetail($installmentId, $customerId);
        if (!$installment) {
            header('Location: ?page=portal&action=installments');
            exit;
        }

        $company = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/financial/detail.php';
        require 'app/views/portal/layout/footer.php';
    }

    // ══════════════════════════════════════════════
    // RASTREAMENTO (Fase 4)
    // ══════════════════════════════════════════════

    /**
     * Página de rastreamento dos pedidos.
     * GET: ?page=portal&action=tracking[&id=X]
     */
    public function tracking(): void
    {
        PortalAuthMiddleware::check();

        if ($this->portalAccess->getConfig('allow_tracking', '1') !== '1') {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $orderId    = (int) Input::get('id');

        // Se um ID específico foi informado, mostrar detalhe
        $trackingDetail = null;
        if ($orderId > 0) {
            $trackingDetail = $this->portalAccess->getTrackingDetail($orderId, $customerId);
        }

        $trackingOrders = $this->portalAccess->getTrackingOrders($customerId);
        $company = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/tracking/index.php';
        require 'app/views/portal/layout/footer.php';
    }

    // ══════════════════════════════════════════════
    // MENSAGENS (Fase 5)
    // ══════════════════════════════════════════════

    /**
     * Tela de mensagens.
     * GET: ?page=portal&action=messages[&order_id=X]
     */
    public function messages(): void
    {
        PortalAuthMiddleware::check();

        if ($this->portalAccess->getConfig('allow_messages', '1') !== '1') {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $orderId    = (int) (Input::get('order_id') ?: 0);

        $messageModel = new PortalMessage($this->db);

        // Marcar como lidas
        $messageModel->markAsRead($customerId, $orderId ?: null);

        // Buscar mensagens
        $messages = $messageModel->getByCustomer($customerId, $orderId ?: null, 100);

        // Buscar pedidos do cliente para seletor
        $orders = $this->portalAccess->getOrdersByCustomer($customerId, 'all', 50, 0);

        $company = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/messages/index.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Enviar mensagem. POST.
     * POST: ?page=portal&action=sendMessage
     */
    public function sendMessage(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=messages');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $accessId   = PortalAuthMiddleware::getAccessId();
        $message    = Input::post('message');
        $orderId    = (int) (Input::post('order_id') ?: 0);

        if (empty(trim($message))) {
            if ($this->isAjax()) {
                $this->jsonResponse(false, __p('error_required'));
            }
            header('Location: ?page=portal&action=messages' . ($orderId ? '&order_id=' . $orderId : ''));
            exit;
        }

        $messageModel = new PortalMessage($this->db);
        $msgId = $messageModel->create([
            'customer_id' => $customerId,
            'order_id'    => $orderId ?: null,
            'sender_type' => 'customer',
            'sender_id'   => $accessId,
            'message'     => $message,
        ]);

        EventDispatcher::dispatch('portal.message.sent', new Event('portal.message.sent', [
            'message_id'  => $msgId,
            'customer_id' => $customerId,
            'order_id'    => $orderId,
            'ip'          => PortalAuthMiddleware::getClientIp(),
        ]));

        if ($this->isAjax()) {
            $this->jsonResponse(true, __p('messages_sent'), [
                'message_id' => $msgId,
                'message'    => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        header('Location: ?page=portal&action=messages' . ($orderId ? '&order_id=' . $orderId : '') . '&sent=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // DOCUMENTOS (Fase 5)
    // ══════════════════════════════════════════════

    /**
     * Listagem de documentos (NF-e, boletos).
     * GET: ?page=portal&action=documents
     */
    public function documents(): void
    {
        PortalAuthMiddleware::check();

        if ($this->portalAccess->getConfig('allow_documents', '1') !== '1') {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $customerId = PortalAuthMiddleware::getCustomerId();
        $documents  = $this->portalAccess->getDocumentsByCustomer($customerId);
        $company    = $this->company;

        require 'app/views/portal/layout/header.php';
        require 'app/views/portal/documents/index.php';
        require 'app/views/portal/layout/footer.php';
    }

    /**
     * Download de documento (PDF ou XML).
     * GET: ?page=portal&action=downloadDocument&id=X&type=pdf|xml
     */
    public function downloadDocument(): void
    {
        PortalAuthMiddleware::check();

        $customerId = PortalAuthMiddleware::getCustomerId();
        $documentId = (int) Input::get('id');
        $type       = Input::get('type') ?: 'pdf';

        if ($documentId <= 0 || !in_array($type, ['pdf', 'xml'])) {
            header('Location: ?page=portal&action=documents');
            exit;
        }

        $document = $this->portalAccess->getDocumentDetail($documentId, $customerId);
        if (!$document) {
            header('Location: ?page=portal&action=documents');
            exit;
        }

        $filePath = $type === 'pdf' ? ($document['pdf_path'] ?? '') : ($document['xml_path'] ?? '');
        if (empty($filePath) || !file_exists($filePath)) {
            header('Location: ?page=portal&action=documents');
            exit;
        }

        $filename = 'NFe_' . ($document['number'] ?? 'doc') . '.' . $type;
        $mime = $type === 'pdf' ? 'application/pdf' : 'application/xml';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    // ══════════════════════════════════════════════
    // 2FA — Verificação de dois fatores
    // ══════════════════════════════════════════════

    /**
     * Tela de verificação 2FA / Processa código (POST).
     */
    public function verify2fa(): void
    {
        if (!PortalAuthMiddleware::isAuthenticated()) {
            header('Location: ?page=portal&action=login');
            exit;
        }

        // Se 2FA não está pendente, ir para dashboard
        if (!PortalAuthMiddleware::is2faPending()) {
            header('Location: ?page=portal&action=dashboard');
            exit;
        }

        $error = '';
        $accessId = PortalAuthMiddleware::getAccessId();
        $company = $this->company;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = Input::post('code');

            if (empty($code) || strlen($code) !== 6) {
                $error = __p('2fa_invalid_code');
            } elseif ($this->portalAccess->validate2faCode($accessId, $code)) {
                PortalAuthMiddleware::set2faVerified();
                $this->logger->log('portal_2fa_verified', "2FA verificado | IP: " . PortalAuthMiddleware::getClientIp(), PortalAuthMiddleware::getCustomerId());

                EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
                    'customer_id' => PortalAuthMiddleware::getCustomerId(),
                    'email'       => $_SESSION['portal_email'] ?? '',
                    'ip'          => PortalAuthMiddleware::getClientIp(),
                    'method'      => '2fa',
                ]));

                header('Location: ?page=portal&action=dashboard');
                exit;
            } else {
                $error = __p('2fa_invalid_code');
                $this->logger->log('portal_2fa_failed', "Código 2FA inválido | IP: " . PortalAuthMiddleware::getClientIp(), PortalAuthMiddleware::getCustomerId());
            }
        }

        require 'app/views/portal/layout/header_auth.php';
        require 'app/views/portal/auth/verify_2fa.php';
        require 'app/views/portal/layout/footer_auth.php';
    }

    /**
     * Reenviar código 2FA (POST AJAX).
     */
    public function resend2fa(): void
    {
        if (!PortalAuthMiddleware::isAuthenticated() || !PortalAuthMiddleware::is2faPending()) {
            $this->jsonResponse(false, __p('error_generic'));
        }

        $accessId = PortalAuthMiddleware::getAccessId();
        $code = $this->portalAccess->generate2faCode($accessId);

        // TODO: Enviar e-mail com o novo código
        // $email = $_SESSION['portal_email'] ?? '';
        // mail($email, 'Novo código de verificação', "Seu código: {$code}");

        $this->jsonResponse(true, __p('2fa_code_resent'));
    }

    /**
     * Ativa/desativa 2FA no perfil (POST AJAX).
     */
    public function toggle2fa(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $accessId = PortalAuthMiddleware::getAccessId();
        $enable = Input::post('enable') === '1';

        $this->portalAccess->toggle2fa($accessId, $enable);

        $action = $enable ? 'ativou' : 'desativou';
        $this->logger->log('portal_2fa_toggled', "2FA {$action} | IP: " . PortalAuthMiddleware::getClientIp(), PortalAuthMiddleware::getCustomerId());

        $this->jsonResponse(true, $enable ? __p('2fa_enabled') : __p('2fa_disabled'));
    }

    // ══════════════════════════════════════════════
    // AVATAR — Upload de foto do cliente
    // ══════════════════════════════════════════════

    /**
     * Upload de avatar (POST com multipart/form-data).
     */
    public function uploadAvatar(): void
    {
        PortalAuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal&action=profile');
            exit;
        }

        $accessId   = PortalAuthMiddleware::getAccessId();
        $customerId = PortalAuthMiddleware::getCustomerId();

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            if ($this->isAjax()) {
                $this->jsonResponse(false, __p('avatar_upload_error'));
            }
            header('Location: ?page=portal&action=profile&avatar_error=1');
            exit;
        }

        $file = $_FILES['avatar'];

        // Validar tipo e tamanho (máx. 2MB, apenas imagens)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            if ($this->isAjax()) {
                $this->jsonResponse(false, __p('avatar_invalid_type'));
            }
            header('Location: ?page=portal&action=profile&avatar_error=type');
            exit;
        }

        if ($file['size'] > $maxSize) {
            if ($this->isAjax()) {
                $this->jsonResponse(false, __p('avatar_too_large'));
            }
            header('Location: ?page=portal&action=profile&avatar_error=size');
            exit;
        }

        // Determinar extensão
        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        // Gerar nome único
        $filename = 'portal_avatar_' . $customerId . '_' . time() . '.' . $ext;
        $destDir  = 'assets/uploads/portal/avatars/';

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            if ($this->isAjax()) {
                $this->jsonResponse(false, __p('avatar_upload_error'));
            }
            header('Location: ?page=portal&action=profile&avatar_error=1');
            exit;
        }

        // Remover avatar antigo
        $oldAvatar = $this->portalAccess->getAvatar($accessId);
        if ($oldAvatar && file_exists($oldAvatar)) {
            @unlink($oldAvatar);
        }

        $this->portalAccess->updateAvatar($accessId, $destPath);
        $_SESSION['portal_customer_avatar'] = $destPath;
        $this->logger->log('portal_avatar_uploaded', "Avatar atualizado | IP: " . PortalAuthMiddleware::getClientIp(), $customerId);

        if ($this->isAjax()) {
            $this->jsonResponse(true, __p('avatar_updated'), ['path' => $destPath]);
        }

        header('Location: ?page=portal&action=profile&avatar_updated=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // RATE LIMITING — Proteção contra abuso
    // ══════════════════════════════════════════════

    /**
     * Verifica rate limiting para ações do portal (login, register, etc.).
     * Usa a tabela de IpGuard existente.
     *
     * @param string $action Nome da ação para rate limit
     * @param int    $maxAttempts Máximo de tentativas
     * @param int    $windowSeconds Janela de tempo em segundos
     * @return bool true se dentro do limite, false se excedeu
     */
    private function checkRateLimit(string $action, int $maxAttempts = 30, int $windowSeconds = 60): bool
    {
        $ip = PortalAuthMiddleware::getClientIp();
        $key = "portal_{$action}_{$ip}";

        // Ler config de rate limit
        $maxConfig    = (int) $this->portalAccess->getConfig('rate_limit_portal_max', (string) $maxAttempts);
        $windowConfig = (int) $this->portalAccess->getConfig('rate_limit_portal_window', (string) $windowSeconds);

        try {
            // Limpar hits antigos
            $cleanup = $this->db->prepare(
                "DELETE FROM ip_hits WHERE ip_address = :ip AND path = :key AND hit_at < DATE_SUB(NOW(), INTERVAL :window SECOND)"
            );
            $cleanup->execute([':ip' => $ip, ':key' => $key, ':window' => $windowConfig]);

            // Contar hits recentes
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM ip_hits WHERE ip_address = :ip AND path = :key"
            );
            $stmt->execute([':ip' => $ip, ':key' => $key]);
            $count = (int) $stmt->fetchColumn();

            if ($count >= $maxConfig) {
                $this->logger->log('portal_rate_limited', "IP: {$ip} | Action: {$action} | Hits: {$count}");
                return false;
            }

            // Registrar hit
            $ins = $this->db->prepare(
                "INSERT INTO ip_hits (ip_address, path, hit_at) VALUES (:ip, :key, NOW())"
            );
            $ins->execute([':ip' => $ip, ':key' => $key]);

            return true;
        } catch (\Throwable $e) {
            return true; // Em caso de erro, não bloquear
        }
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

    /**
     * Valida força da senha (mín. 8 chars, letras + números).
     *
     * @param string $password
     * @return bool
     */
    private function isPasswordStrong(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }
        // Deve conter pelo menos uma letra e pelo menos um número
        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }
        return true;
    }

    /**
     * Envia resposta JSON padronizada e encerra execução.
     *
     * @param bool   $success
     * @param string $message
     * @param array  $data
     */
    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    }
}
