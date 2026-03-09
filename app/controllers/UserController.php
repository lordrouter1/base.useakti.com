<?php
require_once 'app/models/User.php';
require_once 'app/models/UserGroup.php';
require_once 'app/models/LoginAttempt.php';

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
        require_once 'app/models/Logger.php';
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
             $this->userModel->name = $_POST['name'];
             $this->userModel->email = $_POST['email'];
             $this->userModel->password = $_POST['password'];
             $this->userModel->role = $_POST['role'];
             $this->userModel->group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : null;

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
        
        if (!isset($_GET['id'])) {
            header('Location: ?page=users');
            exit;
        }
        
        $id = $_GET['id'];
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
            $this->userModel->id = $_POST['id'];
            $this->userModel->name = $_POST['name'];
            $this->userModel->email = $_POST['email'];
            $this->userModel->role = $_POST['role'];
            $this->userModel->group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : null;
            
            // Password only if provided
            if (!empty($_POST['password'])) {
                $this->userModel->password = $_POST['password'];
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
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
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
        if (isset($_GET['manage_id'])) {
            $editGroup = $this->groupModel->readOne($_GET['manage_id']);
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
            $this->groupModel->name = $_POST['name'];
            $this->groupModel->description = $_POST['description'];
            
            if ($this->groupModel->create()) {
                $groupId = $this->groupModel->id;

                if (isset($_POST['permissions'])) {
                    foreach ($_POST['permissions'] as $page) {
                        $this->groupModel->addPermission($groupId, $page);
                    }
                }
                header('Location: ?page=users&action=groups&status=success');
            }
        }
    }

    public function updateGroup() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->groupModel->id = $_POST['id'];
            $this->groupModel->name = $_POST['name'];
            $this->groupModel->description = $_POST['description'];
            
            if ($this->groupModel->update()) {
                // Update permissions
                $this->groupModel->deletePermissions($_POST['id']);
                
                if (isset($_POST['permissions'])) {
                    foreach ($_POST['permissions'] as $page) {
                        $this->groupModel->addPermission($_POST['id'], $page);
                    }
                }
                header('Location: ?page=users&action=groups&status=success');
            }
        }
    }

    public function deleteGroup() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            if ($this->groupModel->delete($_POST['id'])) {
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
             // Validar se o usuario esta editando o proprio perfil
             if (!isset($_SESSION['user_id'])) {
                 header('Location: ?page=login');
                 exit;
             }
             
             // Reuse existing model update logic but safer
             // Here we are updating currently logged user, not passed ID via hidden input (security risk)
             // But for now let's use the session ID to be safe
             $this->userModel->id = $_SESSION['user_id'];
             $this->userModel->name = $_POST['name'];
             $this->userModel->email = $_POST['email'];
             
             // Keep existing role/group - should not be changeable by user profile in standard logic
             // Need to fetch current values first to preserve them if model update overwrites all
             $currentUser = $this->userModel->readOne($_SESSION['user_id']);
             $this->userModel->role = $currentUser['role']; 
             $this->userModel->group_id = $currentUser['group_id'];

             if (!empty($_POST['password'])) {
                 $this->userModel->password = $_POST['password'];
             }
             
             if ($this->userModel->update()) {
                 $_SESSION['user_name'] = $_POST['name'];
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
             $email    = trim($_POST['email'] ?? '');
             $password = $_POST['password'] ?? '';
             $postedTenant   = $_POST['tenant_key'] ?? '';
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
                 $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
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

                 $_SESSION['user_id']   = $this->userModel->id;
                 $_SESSION['user_name'] = $this->userModel->name;
                 $_SESSION['user_role'] = $this->userModel->role;
                 $_SESSION['group_id']  = $this->userModel->group_id;
                 $_SESSION['user_tenant_key'] = $_SESSION['tenant']['key'] ?? null;

                 $this->logger->log('LOGIN', 'User logged in: ' . $email, $this->userModel->id);
                 
                 header('Location: ?');
                 exit;
             } else {
                 // Falha — registrar tentativa
                 $this->loginAttempt->record($ip, $email, false);
                 $this->logger->log('LOGIN_FAIL', 'Failed login attempt for: ' . $email);

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
        }
        session_destroy();
        header('Location: ?page=login');
        exit;
    }
}
