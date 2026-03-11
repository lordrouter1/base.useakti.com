<?php
namespace Akti\Controllers;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Models\User;
use Akti\Models\UserGroup;
use Akti\Models\LoginAttempt;
use Akti\Models\Logger;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use PDO;
use TenantManager;

class UserController {
    
    private $userModel;
    private $groupModel;
    private $logger;
    private $loginAttempt;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->userModel = new User($db);
        $this->groupModel = new UserGroup($db);
        $this->loginAttempt = new LoginAttempt($db);
        $this->logger = new Logger($db);
    }

    public function index() {
        // Apenas admin
        $this->checkAdmin();
        
        $stmt = $this->userModel->readAll();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Verificar limite de usuários do tenant
        $maxUsers = TenantManager::getTenantLimit('max_users');
        $currentUsers = $this->userModel->countAll();
        $limitReached = ($maxUsers !== null && $currentUsers >= $maxUsers);
        $limitInfo = $limitReached ? ['current' => $currentUsers, 'max' => $maxUsers] : null;
        
        require 'app/views/layout/header.php';
        require 'app/views/users/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        $this->checkAdmin();
        $stmt = $this->groupModel->readAll();
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require 'app/views/layout/header.php';
        require 'app/views/users/create.php';
        require 'app/views/layout/footer.php';
    }
    
    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $name = Input::post('name');
             $email = Input::post('email', 'email');
             $password = Input::postRaw('password');
             $role = Input::post('role', 'enum', 'user', ['admin', 'user']);
             $groupId = Input::post('group_id', 'int');

             $v = new Validator();
             $v->required('name', $name, 'Nome')
               ->maxLength('name', $name, 191, 'Nome')
               ->required('email', $email, 'E-mail')
               ->email('email', $email, 'E-mail')
               ->required('password', $password, 'Senha')
               ->minLength('password', $password, 6, 'Senha');

             if ($v->fails()) {
                 $_SESSION['errors'] = $v->errors();
                 $_SESSION['old'] = $_POST;
                 header('Location: ?page=users&action=create');
                 exit;
             }

             $this->userModel->name = $name;
             $this->userModel->email = $email;
             $this->userModel->password = $password;
             $this->userModel->role = $role;
             $this->userModel->group_id = $groupId ?: null;

             $maxUsers = TenantManager::getTenantLimit('max_users');
             if ($maxUsers !== null) {
                 $currentUsers = $this->userModel->countAll();
                 if ($currentUsers >= $maxUsers) {
                     header('Location: ?page=users&status=limit_users');
                     exit;
                 }
             }
             
             if ($this->userModel->create()) {
                 $this->logger->log('CREATE_USER', 'Created user: ' . $this->userModel->email);
                 header('Location: ?page=users&status=success');
                 exit;
             } else {
                 echo "Erro ao cadastrar usuário.";
             }
        }
    }

    public function edit() {
        $this->checkAdmin();
        
        $id = Input::get('id', 'int');
        if (!$id) {
            header('Location: ?page=users');
            exit;
        }
        
        $user = $this->userModel->readOne($id);
        
        if (!$user) {
            header('Location: ?page=users');
            exit;
        }

        $stmt = $this->groupModel->readAll();
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require 'app/views/layout/header.php';
        require 'app/views/users/edit.php';
        require 'app/views/layout/footer.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            $name = Input::post('name');
            $email = Input::post('email', 'email');
            $role = Input::post('role', 'enum', 'user', ['admin', 'user']);
            $groupId = Input::post('group_id', 'int');
            $password = Input::postRaw('password');

            $v = new Validator();
            $v->required('id', $id, 'ID')
              ->required('name', $name, 'Nome')
              ->maxLength('name', $name, 191, 'Nome')
              ->required('email', $email, 'E-mail')
              ->email('email', $email, 'E-mail');

            if ($v->fails()) {
                $_SESSION['errors'] = $v->errors();
                header('Location: ?page=users&action=edit&id=' . $id);
                exit;
            }

            $this->userModel->id = $id;
            $this->userModel->name = $name;
            $this->userModel->email = $email;
            $this->userModel->role = $role;
            $this->userModel->group_id = $groupId ?: null;
            
            // Password only if provided
            if (!empty($password)) {
                $this->userModel->password = $password;
            }
            
            if ($this->userModel->update()) {
                $this->logger->log('UPDATE_USER', 'Updated user ID: ' . $this->userModel->id);
                header('Location: ?page=users&status=success');
                exit;
            } else {
                echo "Erro ao atualizar usuário.";
            }
        }
    }

    public function delete() {
        $this->checkAdmin();
        $id = Input::get('id', 'int');
        if ($id) {
            if ($this->userModel->delete($id)) {
                $this->logger->log('DELETE_USER', 'Deleted user ID: ' . $id);
            }
            header('Location: ?page=users');
            exit;
        }
    }
    
    // Grupos
    public function groups() {
        $this->checkAdmin();
        
        // If editing a group (show edit form instead of create)
        $editGroup = null;
        $manageId = Input::get('manage_id', 'int');
        if ($manageId) {
            $editGroup = $this->groupModel->readOne($manageId);
            if ($editGroup) {
                 $editGroup['permissions'] = $this->groupModel->getPermissions($editGroup['id']);
            }
        }

        $stmt = $this->groupModel->readAll();
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch permissions for each group
        foreach ($groups as &$group) {
            $group['permissions'] = $this->groupModel->getPermissions($group['id']);
        }
        
        require 'app/views/layout/header.php';
        require 'app/views/users/groups.php';
        require 'app/views/layout/footer.php';
    }
    
    public function createGroup() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->groupModel->name = Input::post('name');
            $this->groupModel->description = Input::post('description');
            
            if ($this->groupModel->create()) {
                $groupId = $this->groupModel->id;

                $permissions = Input::postArray('permissions');
                if (!empty($permissions)) {
                    foreach ($permissions as $page) {
                        $this->groupModel->addPermission($groupId, \Akti\Utils\Sanitizer::string($page));
                    }
                }
                header('Location: ?page=users&action=groups&status=success');
            }
        }
    }

    public function updateGroup() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            $this->groupModel->id = $id;
            $this->groupModel->name = Input::post('name');
            $this->groupModel->description = Input::post('description');
            
            if ($this->groupModel->update()) {
                // Update permissions
                $this->groupModel->deletePermissions($id);
                
                $permissions = Input::postArray('permissions');
                if (!empty($permissions)) {
                    foreach ($permissions as $page) {
                        $this->groupModel->addPermission($id, \Akti\Utils\Sanitizer::string($page));
                    }
                }
                header('Location: ?page=users&action=groups&status=success');
            }
        }
    }

    public function deleteGroup() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            if ($id && $this->groupModel->delete($id)) {
                header('Location: ?page=users&action=groups&status=success');
                exit;
            } else {
                 echo "Erro ao deletar grupo.";
            }
        }
    }

    public function profile() {
        if (!isset($_SESSION['user_id'])) {
             header('Location: ?page=login');
             exit;
        }
        
        $id = $_SESSION['user_id'];
        $user = $this->userModel->readOne($id);
        
        require 'app/views/layout/header.php';
        require 'app/views/users/profile.php';
        require 'app/views/layout/footer.php';
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             if (!isset($_SESSION['user_id'])) {
                 header('Location: ?page=login');
                 exit;
             }
             
             $name = Input::post('name');
             $email = Input::post('email', 'email');
             $password = Input::postRaw('password');

             $v = new Validator();
             $v->required('name', $name, 'Nome')
               ->maxLength('name', $name, 191, 'Nome')
               ->required('email', $email, 'E-mail')
               ->email('email', $email, 'E-mail');

             if ($v->fails()) {
                 $_SESSION['errors'] = $v->errors();
                 header('Location: ?page=profile');
                 exit;
             }

             $this->userModel->id = $_SESSION['user_id'];
             $this->userModel->name = $name;
             $this->userModel->email = $email;
             
             $currentUser = $this->userModel->readOne($_SESSION['user_id']);
             $this->userModel->role = $currentUser['role']; 
             $this->userModel->group_id = $currentUser['group_id'];

             if (!empty($password)) {
                 $this->userModel->password = $password;
             }
             
             if ($this->userModel->update()) {
                 $_SESSION['user_name'] = $name;
                 $this->logger->log('UPDATE_PROFILE', 'User updated own profile');
                 header('Location: ?page=profile&success=1');
                 exit;
             } else {
                 echo "Erro ao atualizar perfil.";
             }
        }
    }

    private function checkAdmin() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ?page=home&error=acesso_negado');
            exit;
        }
    }

    // Login
    public function login() {
        if (isset($_SESSION['user_id'])) {
            header('Location: ?');
            exit;
        }

        if (!empty($_SESSION['tenant']['has_error'])) {
            $error = $_SESSION['tenant']['error_message'] ?: 'Não foi possível identificar o cliente pelo subdomínio.';
            require 'app/views/auth/login.php';
            return;
        }

        $ip = LoginAttempt::getClientIp();
        $showCaptcha = false;
        $lockout = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $email    = Input::post('email', 'email');
             $password = Input::postRaw('password');
             $postedTenant   = Input::post('tenant_key');
             $resolvedTenant = $_SESSION['tenant']['key'] ?? '';

             // ── Validação de tenant ──
             if ($postedTenant !== $resolvedTenant) {
                 $this->logger->log('LOGIN_FAIL', 'Tentativa de login com tenant divergente.');
                 $error = 'Validação de cliente inválida. Atualize a página e tente novamente.';
                 require 'app/views/auth/login.php';
                 return;
             }

             // ── Verificar bloqueio (>= 5 falhas em 10 min) ──
             $lockout = $this->loginAttempt->checkLockout($ip, $email);
             if ($lockout['blocked']) {
                 $this->logger->log('LOGIN_BLOCKED', "IP bloqueado por força bruta: $ip / $email");
                 $remaining = $lockout['remaining_minutes'];
                 $error = "Muitas tentativas de login. Aguarde {$remaining} minuto" . ($remaining > 1 ? 's' : '') . " e tente novamente.";
                 // Manter captcha visível caso desbloqueie
                 $showCaptcha = $this->loginAttempt->requiresCaptcha($ip, $email);
                 require 'app/views/auth/login.php';
                 return;
             }

             // ── Verificar reCAPTCHA (>= 3 falhas) ──
             $showCaptcha = $this->loginAttempt->requiresCaptcha($ip, $email);
             if ($showCaptcha) {
                 $captchaResponse = Input::postRaw('g-recaptcha-response') ?? '';
                 if (empty($captchaResponse) || !$this->loginAttempt->validateCaptcha($captchaResponse, $ip)) {
                     $this->logger->log('LOGIN_CAPTCHA_FAIL', "reCAPTCHA inválido: $ip / $email");
                     $error = 'Por favor, confirme que você não é um robô.';
                     require 'app/views/auth/login.php';
                     return;
                 }
             }

             // ── Tentativa de login ──
             if ($this->userModel->login($email, $password)) {
                 // Sucesso — registrar e limpar
                 $this->loginAttempt->record($ip, $email, true);
                 $this->loginAttempt->clearFailures($ip, $email);
                 $this->loginAttempt->purgeOld();

                 // ── Prevenir session fixation: regenerar ID da sessão ──
                 session_regenerate_id(true);

                 $_SESSION['user_id']   = $this->userModel->id;
                 $_SESSION['user_name'] = $this->userModel->name;
                 $_SESSION['user_role'] = $this->userModel->role;
                 $_SESSION['group_id']  = $this->userModel->group_id;
                 $_SESSION['user_tenant_key'] = $_SESSION['tenant']['key'] ?? null;

                 // ── Inicializar timestamp de atividade para controle de timeout ──
                 $_SESSION['last_activity'] = time();

                 $this->logger->log('LOGIN', 'User logged in: ' . $email, $this->userModel->id);
                 
                 EventDispatcher::dispatch('controller.user.login', new Event('controller.user.login', [
                     'user_id' => $this->userModel->id,
                     'email' => $email,
                     'ip' => $ip,
                 ]));

                 header('Location: ?');
                 exit;
             } else {
                 // Falha — registrar tentativa
                 $this->loginAttempt->record($ip, $email, false);
                 $this->logger->log('LOGIN_FAIL', 'Failed login attempt for: ' . $email);

                 EventDispatcher::dispatch('controller.user.login_failed', new Event('controller.user.login_failed', [
                     'email' => $email,
                     'ip' => $ip,
                 ]));

                 // Recalcular estado após registrar a falha
                 $lockout = $this->loginAttempt->checkLockout($ip, $email);
                 $showCaptcha = $this->loginAttempt->requiresCaptcha($ip, $email);

                 if ($lockout['blocked']) {
                     $remaining = $lockout['remaining_minutes'];
                     $error = "Muitas tentativas de login. Aguarde {$remaining} minuto" . ($remaining > 1 ? 's' : '') . " e tente novamente.";
                 } else {
                     // Mensagem genérica — nunca vazar se o email existe
                     $error = 'Credenciais inválidas. Verifique seu e-mail e senha.';
                 }

                 require 'app/views/auth/login.php';
             }
        } else {
             // GET — verificar se precisa de captcha (por IP genérico ou email em sessão)
             // Na abertura da página não temos email, então captcha só aparece após falha
             require 'app/views/auth/login.php';
        }
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
             $this->logger->log('LOGOUT', 'User logged out', $_SESSION['user_id']);
             EventDispatcher::dispatch('controller.user.logout', new Event('controller.user.logout', [
                 'user_id' => $_SESSION['user_id'],
             ]));
        }
        session_destroy();
        header('Location: ?page=login');
        exit;
    }
}
