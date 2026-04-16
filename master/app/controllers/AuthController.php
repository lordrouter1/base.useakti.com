<?php
/**
 * Controller: AuthController
 * Gerencia login e logout do painel admin
 */

class AuthController
{
    private $db;
    private $adminUser;
    private $loginAttempt;

    public function __construct($db)
    {
        $this->db = $db;
        $this->adminUser = new AdminUser($db);
        $this->loginAttempt = new MasterLoginAttempt($db);
    }

    public function login()
    {
        if (isset($_SESSION['admin_id'])) {
            header('Location: ?page=dashboard');
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $showCaptcha = $this->loginAttempt->requiresCaptcha($ip);
        $isBlocked = $this->loginAttempt->isBlocked($ip);
        $blockMinutes = $isBlocked ? $this->loginAttempt->getBlockMinutesRemaining($ip) : 0;

        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function authenticate()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Verificar bloqueio por rate limiting
        if ($this->loginAttempt->isBlocked($ip)) {
            $minutes = $this->loginAttempt->getBlockMinutesRemaining($ip);
            $_SESSION['login_error'] = "Acesso bloqueado por excesso de tentativas. Tente novamente em {$minutes} minuto(s).";
            header('Location: ?page=login');
            exit;
        }

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Preencha todos os campos.';
            header('Location: ?page=login');
            exit;
        }

        // Verificar reCAPTCHA se necessário
        if ($this->loginAttempt->requiresCaptcha($ip)) {
            $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
            if (empty($recaptchaResponse)) {
                $_SESSION['login_error'] = 'Por favor, confirme que você não é um robô.';
                header('Location: ?page=login');
                exit;
            }
            // Nota: validação server-side do reCAPTCHA pode ser adicionada aqui
            // quando a chave secreta estiver configurada
        }

        $user = $this->adminUser->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->loginAttempt->record($ip, $email, false);
            $_SESSION['login_error'] = 'E-mail ou senha inválidos.';
            header('Location: ?page=login');
            exit;
        }

        // Login bem-sucedido — limpar tentativas falhas
        $this->loginAttempt->record($ip, $email, true);
        $this->loginAttempt->clearFailures($ip);

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_email'] = $user['email'];

        $this->adminUser->updateLastLogin($user['id']);

        // Log
        $log = new AdminLog($this->db);
        $log->log($user['id'], 'login', 'admin', $user['id'], 'Login realizado com sucesso');

        header('Location: ?page=dashboard');
        exit;
    }

    public function logout()
    {
        if (isset($_SESSION['admin_id'])) {
            $log = new AdminLog($this->db);
            $log->log($_SESSION['admin_id'], 'logout', 'admin', $_SESSION['admin_id'], 'Logout realizado');
        }
        session_destroy();
        header('Location: ?page=login');
        exit;
    }
}
