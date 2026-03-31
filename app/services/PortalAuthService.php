<?php
namespace Akti\Services;

use Akti\Models\PortalAccess;
use Akti\Models\Customer;
use Akti\Models\Logger;
use Akti\Middleware\PortalAuthMiddleware;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Service: PortalAuthService
 *
 * Encapsula lógica de autenticação do Portal do Cliente,
 * incluindo login por senha, magic link, setup de senha e registro.
 *
 * @package Akti\Services
 */
class PortalAuthService
{
    private PDO $db;
    private PortalAccess $portalAccess;
    private Logger $logger;

    public function __construct(PDO $db, PortalAccess $portalAccess, Logger $logger)
    {
        $this->db = $db;
        $this->portalAccess = $portalAccess;
        $this->logger = $logger;
    }

    /**
     * Processa login por e-mail e senha.
     *
     * @param string $email
     * @param string $password
     * @param array  $config     Configuração do portal (require_password, enable_2fa, etc.)
     * @return array ['success' => bool, 'error' => string|null, 'redirect' => string|null, '2fa' => bool]
     */
    public function loginWithPassword(string $email, string $password, array $config): array
    {
        $access = $this->portalAccess->findByEmail($email);
        $requirePassword = ($config['require_password'] ?? '0') === '1';

        if (!$access) {
            return ['success' => false, 'error' => 'login_error'];
        }

        if (!$access['is_active']) {
            return ['success' => false, 'error' => 'login_inactive'];
        }

        if ($this->portalAccess->isLocked($access)) {
            $remainingMin = max(1, (int) ceil((strtotime($access['locked_until']) - time()) / 60));
            return ['success' => false, 'error' => 'login_locked', 'minutes' => $remainingMin];
        }

        if (empty($access['password_hash']) && !$requirePassword) {
            return ['success' => false, 'error' => 'login_use_magic_link'];
        }

        if (empty($access['password_hash']) && $requirePassword) {
            return ['success' => false, 'error' => 'login_error'];
        }

        if (!$this->portalAccess->verifyPassword($password, $access['password_hash'])) {
            $this->portalAccess->registerFailedAttempt($access['id']);
            $ip = PortalAuthMiddleware::getClientIp();
            $this->logger->log('portal_login_failed', "E-mail: {$email} | IP: {$ip}");
            return ['success' => false, 'error' => 'login_error'];
        }

        // Login bem-sucedido
        $ip = PortalAuthMiddleware::getClientIp();
        $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);

        // Verificar se deve trocar senha temporária
        if (!empty($access['must_change_password'])) {
            $resetToken = $this->portalAccess->generateResetToken($access['id']);
            return [
                'success'  => true,
                'redirect' => '?page=portal&action=setupPassword&token=' . urlencode($resetToken),
            ];
        }

        // Executar login na sessão
        $this->executeSessionLogin($access, $ip);

        // Verificar 2FA
        $global2fa = ($config['enable_2fa'] ?? '0') === '1';
        if ($global2fa && $this->portalAccess->is2faEnabled($access['id'])) {
            $code = $this->portalAccess->generate2faCode($access['id']);
            PortalAuthMiddleware::set2faPending(true);

            EventDispatcher::dispatch('portal.customer.2fa_requested', new Event('portal.customer.2fa_requested', [
                'customer_id' => $access['customer_id'],
                'email'       => $access['email'],
            ]));

            return ['success' => true, 'redirect' => '?page=portal&action=verify2fa', '2fa' => true];
        }

        EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
            'customer_id' => $access['customer_id'],
            'email'       => $access['email'],
            'ip'          => $ip,
        ]));

        return ['success' => true, 'redirect' => '?page=portal&action=dashboard'];
    }

    /**
     * Processa login via magic link token.
     *
     * @param string $token
     * @return array ['success' => bool, 'error' => string|null, 'redirect' => string]
     */
    public function loginWithMagicLink(string $token): array
    {
        $access = $this->portalAccess->validateMagicToken($token);

        if (!$access) {
            return ['success' => false, 'error' => 'Link de acesso inválido ou expirado.'];
        }

        // Se ainda não tem senha, redirecionar para setup
        if (empty($access['password_hash']) || !empty($access['must_change_password'])) {
            return [
                'success'  => true,
                'redirect' => '?page=portal&action=setupPassword&token=' . urlencode($token),
            ];
        }

        $ip = PortalAuthMiddleware::getClientIp();
        $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);
        $this->portalAccess->invalidateMagicToken($access['id']);

        $this->executeSessionLogin($access, $ip);

        EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
            'customer_id' => $access['customer_id'],
            'email'       => $access['email'],
            'ip'          => $ip,
            'method'      => 'magic_link',
        ]));

        return ['success' => true, 'redirect' => '?page=portal&action=dashboard'];
    }

    /**
     * Valida token e salva nova senha (para setupPassword e resetPassword).
     *
     * @param string $token
     * @param string $newPassword
     * @param string $confirmPassword
     * @return array ['success' => bool, 'error' => string|null, 'redirect' => string|null]
     */
    public function setupNewPassword(string $token, string $newPassword, string $confirmPassword): array
    {
        // Tentar validar como magic_token primeiro, depois como reset_token
        $access = $this->portalAccess->validateMagicToken($token);
        $tokenType = 'magic';

        if (!$access) {
            $access = $this->portalAccess->validateResetToken($token);
            $tokenType = 'reset';
        }

        if (!$access) {
            return ['success' => false, 'error' => 'setup_password_token_expired'];
        }

        if (empty($newPassword) || empty($confirmPassword)) {
            return ['success' => false, 'error' => 'error_required', 'access' => $access, 'tokenType' => $tokenType];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'error' => 'register_password_mismatch', 'access' => $access, 'tokenType' => $tokenType];
        }

        if (!$this->isPasswordStrong($newPassword)) {
            return ['success' => false, 'error' => 'profile_password_weak', 'access' => $access, 'tokenType' => $tokenType];
        }

        // Salvar a senha
        $this->portalAccess->resetPassword($access['id'], $newPassword);

        // Invalidar o token
        if ($tokenType === 'magic') {
            $this->portalAccess->invalidateMagicToken($access['id']);
        } else {
            $this->portalAccess->invalidateResetToken($access['id']);
        }

        // Login automático
        $ip = PortalAuthMiddleware::getClientIp();
        $this->portalAccess->registerSuccessfulLogin($access['id'], $ip);
        $this->executeSessionLogin($access, $ip);

        EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
            'customer_id' => $access['customer_id'],
            'email'       => $access['email'],
            'ip'          => $ip,
            'method'      => 'password_setup',
        ]));

        return ['success' => true, 'redirect' => '?page=portal&action=dashboard'];
    }

    /**
     * Valida um token (magic ou reset) sem processar login.
     *
     * @param string $token
     * @return array|null Access data or null if invalid
     */
    public function validateToken(string $token): ?array
    {
        $access = $this->portalAccess->validateMagicToken($token);
        if (!$access) {
            $access = $this->portalAccess->validateResetToken($token);
        }
        return $access;
    }

    /**
     * Processa auto-registro de cliente.
     *
     * @param array $formData Dados do formulário (name, email, phone, document, password, password_confirm)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function register(array $formData): array
    {
        if (empty($formData['name']) || empty($formData['email']) || empty($formData['password'])) {
            return ['success' => false, 'error' => 'error_required'];
        }

        if ($formData['password'] !== $formData['password_confirm']) {
            return ['success' => false, 'error' => 'register_password_mismatch'];
        }

        if ($this->portalAccess->emailExists($formData['email'])) {
            return ['success' => false, 'error' => 'register_email_exists'];
        }

        // Buscar ou criar cliente
        $customerModel = new Customer($this->db);
        $existingCustomer = $customerModel->findByEmail($formData['email']);

        if ($existingCustomer) {
            $customerId = $existingCustomer['id'];
        } else {
            $customerId = $customerModel->create([
                'name'     => $formData['name'],
                'email'    => $formData['email'],
                'phone'    => $formData['phone'] ?? '',
                'document' => $formData['document'] ?? '',
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

        return ['success' => true];
    }

    /**
     * Executa login na sessão (regenerar ID, definir dados).
     *
     * @param array  $access Dados do acesso
     * @param string $ip     IP do cliente
     */
    private function executeSessionLogin(array $access, string $ip): void
    {
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

        $_SESSION['portal_customer_avatar'] = $access['avatar'] ?? '';

        $this->logger->log('portal_login', "Cliente ID: {$access['customer_id']} | Nome: {$customerName} | E-mail: {$access['email']} | IP: {$ip}");
    }

    /**
     * Valida força da senha (mín. 8 chars, letras + números).
     *
     * @param string $password
     * @return bool
     */
    public function isPasswordStrong(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }
        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }
        return true;
    }
}
