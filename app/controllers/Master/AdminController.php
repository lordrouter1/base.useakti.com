<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\AdminUser;

/**
 * Class AdminController.
 */
class AdminController extends MasterBaseController
{
    private AdminUser $adminModel;

    private const VALID_ROLES = ['superadmin', 'operator', 'viewer'];

    /**
     * Construtor da classe AdminController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->adminModel = new AdminUser($this->db);
    }

    /**
     * Check if current user is superadmin (required for admin management).
     */
    private function requireSuperadmin(): void
    {
        $adminId = $this->getMasterAdminId();
        if (!$adminId) {
            $this->redirect('?page=login');
        }

        $admin = $this->adminModel->findById($adminId);
        if (!$admin || ($admin['role'] ?? 'superadmin') !== 'superadmin') {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Permissão insuficiente. Apenas superadmins podem gerenciar administradores.'], 403);
            }
            $_SESSION['error'] = 'Permissão insuficiente. Apenas superadmins podem gerenciar administradores.';
            $this->redirect('?page=master_dashboard');
        }
    }

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        $admins = $this->adminModel->readAll();
        $roleLabels = [
            'superadmin' => ['label' => 'Super Admin', 'badge' => 'bg-danger'],
            'operator'   => ['label' => 'Operador', 'badge' => 'bg-primary'],
            'viewer'     => ['label' => 'Visualizador', 'badge' => 'bg-secondary'],
        ];

        $this->renderMaster('admins/index', compact('admins', 'roleLabels'));
    }

    /**
     * Cria um novo registro no banco de dados.
     * @return void
     */
    public function create(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        $roles = self::VALID_ROLES;
        $this->renderMaster('admins/create', compact('roles'));
    }

    /**
     * Processa e armazena um novo registro.
     * @return void
     */
    public function store(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_admins');
        }

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'operator';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Nome, e-mail e senha são obrigatórios.';
            $this->redirect('?page=master_admins&action=create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail inválido.';
            $this->redirect('?page=master_admins&action=create');
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 8 caracteres.';
            $this->redirect('?page=master_admins&action=create');
        }

        if (!in_array($role, self::VALID_ROLES, true)) {
            $_SESSION['error'] = 'Papel inválido.';
            $this->redirect('?page=master_admins&action=create');
        }

        if ($this->adminModel->emailExists($email)) {
            $_SESSION['error'] = 'Já existe um administrador com este e-mail.';
            $this->redirect('?page=master_admins&action=create');
        }

        $id = $this->adminModel->create([
            'name'      => $name,
            'email'     => $email,
            'password'  => $password,
            'role'      => $role,
            'is_active' => $isActive,
        ]);

        $this->logAction('create_admin', 'admin_user', (int)$id, "Admin '{$email}' criado com papel '{$role}'");
        $_SESSION['success'] = "Administrador '{$name}' criado com sucesso.";
        $this->redirect('?page=master_admins');
    }

 /**
  * Edit.
  * @return void
  */
    public function edit(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $this->redirect('?page=master_admins');
        }

        $admin = $this->adminModel->findById($id);
        if (!$admin) {
            $_SESSION['error'] = 'Administrador não encontrado.';
            $this->redirect('?page=master_admins');
        }

        $roles = self::VALID_ROLES;
        $this->renderMaster('admins/edit', compact('admin', 'roles'));
    }

 /**
  * Update.
  * @return void
  */
    public function update(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_admins');
        }

        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'operator';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!$id) {
            $this->redirect('?page=master_admins');
        }

        $existing = $this->adminModel->findById($id);
        if (!$existing) {
            $_SESSION['error'] = 'Administrador não encontrado.';
            $this->redirect('?page=master_admins');
        }

        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'Nome e e-mail são obrigatórios.';
            $this->redirect('?page=master_admins&action=edit&id=' . $id);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail inválido.';
            $this->redirect('?page=master_admins&action=edit&id=' . $id);
        }

        if (!empty($password) && strlen($password) < 8) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 8 caracteres.';
            $this->redirect('?page=master_admins&action=edit&id=' . $id);
        }

        if (!in_array($role, self::VALID_ROLES, true)) {
            $_SESSION['error'] = 'Papel inválido.';
            $this->redirect('?page=master_admins&action=edit&id=' . $id);
        }

        if ($this->adminModel->emailExists($email, $id)) {
            $_SESSION['error'] = 'Já existe outro administrador com este e-mail.';
            $this->redirect('?page=master_admins&action=edit&id=' . $id);
        }

        // Prevent removing own superadmin role
        $currentAdminId = $this->getMasterAdminId();
        if ($id === $currentAdminId && $role !== 'superadmin') {
            $_SESSION['error'] = 'Você não pode remover seu próprio papel de superadmin.';
            $this->redirect('?page=master_admins&action=edit&id=' . $id);
        }

        $updateData = [
            'name'      => $name,
            'email'     => $email,
            'role'      => $role,
            'is_active' => $isActive,
        ];

        if (!empty($password)) {
            $updateData['password'] = $password;
        }

        $this->adminModel->update($id, $updateData);

        $this->logAction('update_admin', 'admin_user', $id, "Admin '{$email}' atualizado (papel: {$role})");
        $_SESSION['success'] = "Administrador '{$name}' atualizado com sucesso.";
        $this->redirect('?page=master_admins');
    }

 /**
  * Delete.
  * @return void
  */
    public function delete(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_admins');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido']);
        }

        // Prevent self-deletion
        $currentAdminId = $this->getMasterAdminId();
        if ($id === $currentAdminId) {
            $this->json(['success' => false, 'message' => 'Você não pode excluir sua própria conta.']);
        }

        $admin = $this->adminModel->findById($id);
        if (!$admin) {
            $this->json(['success' => false, 'message' => 'Administrador não encontrado.']);
        }

        $this->adminModel->delete($id);
        $this->logAction('delete_admin', 'admin_user', $id, "Admin '{$admin['email']}' removido");

        $this->json(['success' => true, 'message' => "Administrador '{$admin['name']}' removido com sucesso."]);
    }
}
