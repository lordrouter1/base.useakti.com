<?php
namespace Akti\Controllers;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Models\User;
use Akti\Models\UserGroup;
use Akti\Models\LoginAttempt;
use Akti\Models\Logger;
use Akti\Models\PortalAccess;
use Akti\Models\Customer;
use Akti\Middleware\PortalAuthMiddleware;
use Akti\Services\AuthService;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use TenantManager;

/**
 * Class UserController.
 */
class UserController extends BaseController {

    private User $userModel;
    private UserGroup $groupModel;
    private Logger $logger;
    private LoginAttempt $loginAttempt;
    private AuthService $authService;

    /**
     * Construtor da classe UserController.
     *
     * @param User $userModel User model
     * @param UserGroup $groupModel Group model
     * @param LoginAttempt $loginAttempt Login attempt
     * @param Logger $logger Logger
     * @param AuthService $authService Auth service
     */
    public function __construct(
        User $userModel,
        UserGroup $groupModel,
        LoginAttempt $loginAttempt,
        Logger $logger,
        AuthService $authService
    ) {
        $this->userModel = $userModel;
        $this->groupModel = $groupModel;
        $this->loginAttempt = $loginAttempt;
        $this->logger = $logger;
        $this->authService = $authService;
    }

    /**
     * Exibe a página de listagem.
     */
    public function index() {
        $this->checkAdmin();
        
        $users = $this->userModel->readAll();

        // Verificar limite de usuários do tenant
        $maxUsers = TenantManager::getTenantLimit('max_users');
        $currentUsers = $this->userModel->countAll();
        $limitReached = ($maxUsers !== null && $currentUsers >= $maxUsers);
        $limitInfo = $limitReached ? ['current' => $currentUsers, 'max' => $maxUsers] : null;
        
        require 'app/views/layout/header.php';
        require 'app/views/users/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Cria um novo registro no banco de dados.
     */
    public function create() {
        $this->checkAdmin();
        $groups = $this->groupModel->readAll();
        require 'app/views/layout/header.php';
        require 'app/views/users/create.php';
        require 'app/views/layout/footer.php';
    }
    
    /**
     * Processa e armazena um novo registro.
     */
    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $name = Input::post('name');
             $email = Input::post('email', 'email');
             $password = Input::postRaw('password');
             $role = Input::post('role', 'enum', 'funcionario', ['admin', 'funcionario']);
             $groupId = Input::post('group_id', 'int');

             $v = new Validator();
             $v->required('name', $name, 'Nome')
               ->maxLength('name', $name, 191, 'Nome')
               ->required('email', $email, 'E-mail')
               ->email('email', $email, 'E-mail')
               ->required('password', $password, 'Senha')
               ->passwordStrength('password', $password, 'Senha');

             if ($v->fails()) {
                 $_SESSION['errors'] = $v->errors();
                 $_SESSION['old'] = $_POST;
                 header('Location: ?page=users&action=create');
                 exit;
             }

             if ($this->userModel->emailExists($email)) {
                 $_SESSION['errors'] = ['email' => 'Este e-mail já está cadastrado para outro usuário.'];
                 $_SESSION['old'] = $_POST;
                 header('Location: ?page=users&action=create');
                 exit;
             }

             $this->userModel->name = $name;
             $this->userModel->email = $email;
             $this->userModel->setPassword($password);
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

    /**
     * Exibe o formulário de edição.
     */
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

        $groups = $this->groupModel->readAll();
        
        require 'app/views/layout/header.php';
        require 'app/views/users/edit.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Atualiza um registro existente.
     */
    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            $name = Input::post('name');
            $email = Input::post('email', 'email');
            $role = Input::post('role', 'enum', 'funcionario', ['admin', 'funcionario']);
            $groupId = Input::post('group_id', 'int');
            $password = Input::postRaw('password');

            $v = new Validator();
            $v->required('id', $id, 'ID')
              ->required('name', $name, 'Nome')
              ->maxLength('name', $name, 191, 'Nome')
              ->required('email', $email, 'E-mail')
              ->email('email', $email, 'E-mail');

            // Validar força da senha apenas se fornecida (edição)
            if (!empty($password)) {
                $v->passwordStrength('password', $password, 'Senha');
            }

            if ($v->fails()) {
                $_SESSION['errors'] = $v->errors();
                header('Location: ?page=users&action=edit&id=' . $id);
                exit;
            }

            if ($this->userModel->emailExists($email, $id)) {
                $_SESSION['errors'] = ['email' => 'Este e-mail já está cadastrado para outro usuário.'];
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
                $this->userModel->setPassword($password);
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

    /**
     * Remove um registro pelo ID.
     */
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
    /**
     * Groups.
     */
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

        $groups = $this->groupModel->readAll();
        
        // Fetch permissions for each group
        foreach ($groups as &$group) {
            $group['permissions'] = $this->groupModel->getPermissions($group['id']);
        }
        
        require 'app/views/layout/header.php';
        require 'app/views/users/groups.php';
        require 'app/views/layout/footer.php';
    }
    
    /**
     * Create group.
     */
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

    /**
     * Update group.
     */
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

    /**
     * Delete group.
     */
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

    /**
     * Profile.
     */
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

    /**
     * Update profile.
     */
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

             // Validar força da senha apenas se fornecida (troca de senha)
             if (!empty($password)) {
                 $v->passwordStrength('password', $password, 'Senha');
             }

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

    /**
     * Verifica se o usuário é administrador.
     */
    private function checkAdmin() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ?page=home&error=acesso_negado');
            exit;
        }
    }

    // Login
    /**
     * Processa a autenticação do usuário.
     */
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
            $email          = Input::post('email', 'email');
            $password        = Input::postRaw('password');
            $postedTenant    = Input::post('tenant_key');
            $resolvedTenant  = $_SESSION['tenant']['key'] ?? '';
            $captchaResponse = Input::postRaw('g-recaptcha-response') ?? '';

            $result = $this->authService->attemptLogin(
                $email,
                $password,
                $ip,
                $postedTenant,
                $resolvedTenant,
                $captchaResponse ?: null
            );

            if ($result['success']) {
                header('Location: ' . $result['redirect']);
                exit;
            }

            // Login falhou
            $error = $result['error'];
            $showCaptcha = $result['show_captcha'];
            require 'app/views/auth/login.php';
        } else {
            require 'app/views/auth/login.php';
        }
    }

    /**
     * Encerra a sessão do usuário.
     */
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
