<?php
/**
 * Controller: AuthController
 * Gerencia login e logout do painel admin
 */

class AuthController
{
    private $db;
    private $adminUser;

    public function __construct($db)
    {
        $this->db = $db;
        $this->adminUser = new AdminUser($db);
    }

    public function login()
    {
        if (isset($_SESSION['admin_id'])) {
            header('Location: ?page=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function authenticate()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Preencha todos os campos.';
            header('Location: ?page=login');
            exit;
        }

        $user = $this->adminUser->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_error'] = 'E-mail ou senha inválidos.';
            header('Location: ?page=login');
            exit;
        }

        // Login bem-sucedido
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
