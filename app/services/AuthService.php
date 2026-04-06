<?php
namespace Akti\Services;

use Akti\Models\User;
use Akti\Models\LoginAttempt;
use Akti\Models\Logger;
use Akti\Models\PortalAccess;
use Akti\Models\Customer;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Middleware\PortalAuthMiddleware;
use Database;
use PDO;

/**
 * AuthService — Lógica de autenticação (login, brute-force, portal unificado).
 *
 * Responsabilidades:
 *   - Validar credenciais de usuário admin
 *   - Controle de tentativas de login (brute-force)
 *   - Verificação de captcha
 *   - Login unificado (admin + portal do cliente)
 *   - Despacho de eventos de login
 *
 * @package Akti\Services
 */
class AuthService
{
    private PDO $db;
    private User $userModel;
    private LoginAttempt $loginAttempt;
    private Logger $logger;

    public function __construct(PDO $db, User $userModel, LoginAttempt $loginAttempt, Logger $logger)
    {
        $this->db = $db;
        $this->userModel = $userModel;
        $this->loginAttempt = $loginAttempt;
        $this->logger = $logger;
    }

    /**
     * Processar tentativa de login.
     *
     * @param string $email
     * @param string $password
     * @param string $ip
     * @param string $postedTenant
     * @param string $resolvedTenant
     * @param string|null $captchaResponse
     * @return array ['success' => bool, 'error' => string|null, 'show_captcha' => bool, 'redirect' => string|null, 'type' => 'admin'|'portal'|null]
     */
    public function attemptLogin(
        string $email,
        string $password,
        string $ip,
        string $postedTenant,
        string $resolvedTenant,
        ?string $captchaResponse = null
    ): array {
        // Validação de tenant
        if ($postedTenant !== $resolvedTenant) {
            $this->logger->log('LOGIN_FAIL', 'Tentativa de login com tenant divergente.');
            return [
                'success'      => false,
                'error'        => 'Validação de cliente inválida. Atualize a página e tente novamente.',
                'show_captcha' => false,
                'redirect'     => null,
                'type'         => null,
            ];
        }

        // Verificar bloqueio (>= 5 falhas em 10 min)
        $lockout = $this->loginAttempt->checkLockout($ip, $email);
        if ($lockout['blocked']) {
            $this->logger->log('LOGIN_BLOCKED', "IP bloqueado por força bruta: $ip / $email");
            $remaining = $lockout['remaining_minutes'];
            return [
                'success'      => false,
                'error'        => "Muitas tentativas de login. Aguarde {$remaining} minuto" . ($remaining > 1 ? 's' : '') . " e tente novamente.",
                'show_captcha' => $this->loginAttempt->requiresCaptcha($ip, $email),
                'redirect'     => null,
                'type'         => null,
            ];
        }

        // Verificar reCAPTCHA (>= 3 falhas)
        $showCaptcha = $this->loginAttempt->requiresCaptcha($ip, $email);
        if ($showCaptcha) {
            if (empty($captchaResponse) || !$this->loginAttempt->validateCaptcha($captchaResponse, $ip)) {
                $this->logger->log('LOGIN_CAPTCHA_FAIL', "reCAPTCHA inválido: $ip / $email");
                return [
                    'success'      => false,
                    'error'        => 'Por favor, confirme que você não é um robô.',
                    'show_captcha' => true,
                    'redirect'     => null,
                    'type'         => null,
                ];
            }
        }

        // Tentativa de login admin
        if ($this->userModel->login($email, $password)) {
            return $this->handleAdminLoginSuccess($email, $ip);
        }

        // Falha no login admin — tentar como master admin
        $masterResult = $this->attemptMasterLogin($email, $password, $ip);
        if ($masterResult !== null) {
            return $masterResult;
        }

        // Falha no login admin — registrar tentativa
        $this->loginAttempt->record($ip, $email, false);
        $this->logger->log('LOGIN_FAIL', 'Failed login attempt for: ' . $email);

        EventDispatcher::dispatch('controller.user.login_failed', new Event('controller.user.login_failed', [
            'email' => $email,
            'ip'    => $ip,
        ]));

        // Login Unificado: tentar como cliente do portal
        $portalResult = $this->attemptPortalLogin($email, $password, $ip);
        if ($portalResult !== null) {
            return $portalResult;
        }

        // Recalcular estado após registrar a falha
        $lockout = $this->loginAttempt->checkLockout($ip, $email);
        $showCaptcha = $this->loginAttempt->requiresCaptcha($ip, $email);

        $error = $lockout['blocked']
            ? "Muitas tentativas de login. Aguarde {$lockout['remaining_minutes']} minuto" . ($lockout['remaining_minutes'] > 1 ? 's' : '') . " e tente novamente."
            : 'Credenciais inválidas. Verifique seu e-mail e senha.';

        return [
            'success'      => false,
            'error'        => $error,
            'show_captcha' => $showCaptcha,
            'redirect'     => null,
            'type'         => null,
        ];
    }

    /**
     * Tratar login admin bem-sucedido.
     */
    private function handleAdminLoginSuccess(string $email, string $ip): array
    {
        $this->loginAttempt->record($ip, $email, true);
        $this->loginAttempt->clearFailures($ip, $email);
        $this->loginAttempt->purgeOld();

        session_regenerate_id(true);

        $_SESSION['user_id']         = $this->userModel->id;
        $_SESSION['user_name']       = $this->userModel->name;
        $_SESSION['user_role']       = $this->userModel->role;
        $_SESSION['group_id']        = $this->userModel->group_id;
        $_SESSION['user_tenant_key'] = $_SESSION['tenant']['key'] ?? null;
        $_SESSION['last_activity']   = time();

        $this->logger->log('LOGIN', 'User logged in: ' . $email, $this->userModel->id);

        EventDispatcher::dispatch('controller.user.login', new Event('controller.user.login', [
            'user_id' => $this->userModel->id,
            'email'   => $email,
            'ip'      => $ip,
        ]));

        return [
            'success'      => true,
            'error'        => null,
            'show_captcha' => false,
            'redirect'     => '?',
            'type'         => 'admin',
        ];
    }

    /**
     * Tentar login como administrador master.
     *
     * @return array|null null se não é master admin, array se login ok
     */
    private function attemptMasterLogin(string $email, string $password, string $ip): ?array
    {
        try {
            $masterDb = \Database::getMasterInstance();
            $adminUser = new \Akti\Models\Master\AdminUser($masterDb);
            $admin = $adminUser->findByEmail($email);

            if (!$admin || !password_verify($password, $admin['password'])) {
                return null;
            }

            $this->loginAttempt->record($ip, $email, true);
            $this->loginAttempt->clearFailures($ip, $email);

            session_regenerate_id(true);

            $_SESSION['user_id']          = $admin['id'];
            $_SESSION['user_name']        = $admin['name'];
            $_SESSION['user_role']        = 'master_admin';
            $_SESSION['group_id']         = 0;
            $_SESSION['is_master_admin']  = true;
            $_SESSION['master_admin_id']  = $admin['id'];
            $_SESSION['last_activity']    = time();

            $adminUser->updateLastLogin($admin['id']);

            $adminLog = new \Akti\Models\Master\AdminLog($masterDb);
            $adminLog->log($admin['id'], 'login', 'admin_user', $admin['id'], 'Master login from IP: ' . $ip);

            $this->logger->log('MASTER_LOGIN', 'Master admin logged in: ' . $email);

            return [
                'success'      => true,
                'error'        => null,
                'show_captcha' => false,
                'redirect'     => '?page=master_dashboard',
                'type'         => 'master',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Tentar login como cliente do portal (login unificado).
     *
     * @return array|null null se não conseguiu login no portal, array se conseguiu
     */
    private function attemptPortalLogin(string $email, string $password, string $ip): ?array
    {
        try {
            $portalAccess = new PortalAccess($this->db);
            $portalAccount = $portalAccess->findByEmail($email);

            if (
                $portalAccount
                && $portalAccount['is_active']
                && !$portalAccess->isLocked($portalAccount)
                && !empty($portalAccount['password_hash'])
                && $portalAccess->verifyPassword($password, $portalAccount['password_hash'])
            ) {
                // Login como cliente do portal bem-sucedido
                $portalAccess->registerSuccessfulLogin($portalAccount['id'], $ip);
                $this->loginAttempt->clearFailures($ip, $email);

                session_regenerate_id(true);

                $customerModel = new Customer($this->db);
                $customer = $customerModel->readOne($portalAccount['customer_id']);
                $customerName = $customer ? $customer['name'] : 'Cliente';

                PortalAuthMiddleware::login(
                    $portalAccount['customer_id'],
                    $portalAccount['id'],
                    $customerName,
                    $portalAccount['email'],
                    $portalAccount['lang'] ?? 'pt-br'
                );

                EventDispatcher::dispatch('portal.customer.logged_in', new Event('portal.customer.logged_in', [
                    'customer_id' => $portalAccount['customer_id'],
                    'email'       => $portalAccount['email'],
                    'ip'          => $ip,
                    'method'      => 'unified_login',
                ]));

                return [
                    'success'      => true,
                    'error'        => null,
                    'show_captcha' => false,
                    'redirect'     => '?page=portal&action=dashboard',
                    'type'         => 'portal',
                ];
            } elseif ($portalAccount && !empty($portalAccount['password_hash'])) {
                // Senha errada no portal — registrar tentativa falha
                $portalAccess->registerFailedAttempt($portalAccount['id']);
            }
        } catch (\Exception $e) {
            // Silenciar erros do portal para não interferir no fluxo admin
        }

        return null;
    }
}
