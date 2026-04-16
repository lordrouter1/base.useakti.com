<?php
/**
 * Controller: TenantPermissionController
 * Gerencia permissões de páginas por tenant e por plano.
 */

class TenantPermissionController
{
    private $db;
    private $permModel;
    private $clientModel;
    private $planModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->permModel = new TenantPagePermission($db);
        $this->clientModel = new TenantClient($db);
        $this->planModel = new Plan($db);
    }

    /**
     * Exibe a tela de permissões por tenant.
     * GET ?page=permissions&action=edit&id=<tenant_client_id>
     */
    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        $currentPermissions = $this->permModel->getPermissions($id);
        $hasRestrictions = $this->permModel->hasRestrictions($id);
        $plans = $this->planModel->readActive();
        $pageGroups = TenantPagePermission::PAGE_GROUPS;
        $pageLabels = TenantPagePermission::PAGE_LABELS;
        $controllablePages = TenantPagePermission::CONTROLLABLE_PAGES;

        require_once __DIR__ . '/../views/permissions/edit.php';
    }

    /**
     * Salva as permissões de um tenant.
     * POST ?page=permissions&action=update
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=clients');
            exit;
        }

        $tenantClientId = (int)($_POST['tenant_client_id'] ?? 0);
        $accessMode = $_POST['access_mode'] ?? 'full';
        $adminId = (int)$_SESSION['admin_id'];

        $client = $this->clientModel->readOne($tenantClientId);
        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        if ($accessMode === 'full') {
            // Remover todas as restrições → acesso total
            $this->permModel->removeRestrictions($tenantClientId);
            $this->logAction('remove_restrictions', 'tenant_client', $tenantClientId, 'Removidas restrições de páginas');
            $_SESSION['success'] = 'Permissões removidas. O tenant tem acesso total.';
        } else {
            // Modo restrito → whitelist
            $pages = $_POST['pages'] ?? [];
            if (!is_array($pages)) {
                $pages = [];
            }
            $this->permModel->setPermissions($tenantClientId, $pages, $adminId);
            $this->logAction('set_permissions', 'tenant_client', $tenantClientId, 'Definidas ' . count($pages) . ' permissões de página');
            $_SESSION['success'] = 'Permissões salvas com sucesso. ' . count($pages) . ' páginas permitidas.';
        }

        header('Location: ?page=permissions&action=edit&id=' . $tenantClientId);
        exit;
    }

    /**
     * Aplica permissões de um plano a um tenant.
     * POST ?page=permissions&action=applyPlan
     */
    public function applyPlan(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=clients');
            exit;
        }

        $tenantClientId = (int)($_POST['tenant_client_id'] ?? 0);
        $planId = (int)($_POST['plan_id'] ?? 0);
        $adminId = (int)$_SESSION['admin_id'];

        $client = $this->clientModel->readOne($tenantClientId);
        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        $plan = $this->planModel->readOne($planId);
        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=permissions&action=edit&id=' . $tenantClientId);
            exit;
        }

        if (!$this->permModel->planHasPermissions($planId)) {
            $_SESSION['error'] = 'O plano selecionado não tem permissões definidas.';
            header('Location: ?page=permissions&action=edit&id=' . $tenantClientId);
            exit;
        }

        $this->permModel->applyPlanPermissions($tenantClientId, $planId, $adminId);
        $this->logAction('apply_plan_permissions', 'tenant_client', $tenantClientId, 'Aplicadas permissões do plano: ' . $plan['plan_name']);
        $_SESSION['success'] = 'Permissões do plano "' . htmlspecialchars($plan['plan_name']) . '" aplicadas com sucesso.';

        header('Location: ?page=permissions&action=edit&id=' . $tenantClientId);
        exit;
    }

    /**
     * Exibe a tela de permissões por plano.
     * GET ?page=permissions&action=editPlan&id=<plan_id>
     */
    public function editPlan(): void
    {
        $planId = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->readOne($planId);

        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=plans');
            exit;
        }

        $currentPermissions = $this->permModel->getPlanPermissions($planId);
        $hasPermissions = $this->permModel->planHasPermissions($planId);
        $pageGroups = TenantPagePermission::PAGE_GROUPS;
        $pageLabels = TenantPagePermission::PAGE_LABELS;
        $controllablePages = TenantPagePermission::CONTROLLABLE_PAGES;

        require_once __DIR__ . '/../views/permissions/edit_plan.php';
    }

    /**
     * Salva as permissões de um plano.
     * POST ?page=permissions&action=updatePlan
     */
    public function updatePlan(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=plans');
            exit;
        }

        $planId = (int)($_POST['plan_id'] ?? 0);
        $syncTenants = isset($_POST['sync_tenants']);
        $adminId = (int)$_SESSION['admin_id'];

        $plan = $this->planModel->readOne($planId);
        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=plans');
            exit;
        }

        $pages = $_POST['pages'] ?? [];
        if (!is_array($pages)) {
            $pages = [];
        }

        $this->permModel->setPlanPermissions($planId, $pages);
        $this->logAction('set_plan_permissions', 'plan', $planId, 'Definidas ' . count($pages) . ' permissões para o plano');

        $message = 'Permissões do plano salvas. ' . count($pages) . ' páginas definidas.';

        if ($syncTenants) {
            $count = $this->permModel->syncPlanToAllTenants($planId, $adminId);
            $this->logAction('sync_plan_permissions', 'plan', $planId, 'Sincronizadas permissões para ' . $count . ' tenants');
            $message .= ' Sincronizado com ' . $count . ' tenant(s).';
        }

        $_SESSION['success'] = $message;
        header('Location: ?page=permissions&action=editPlan&id=' . $planId);
        exit;
    }

    /**
     * Registra ação no log de auditoria.
     */
    private function logAction(string $action, string $targetType, int $targetId, string $details): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, details)
            VALUES (:admin_id, :action, :target_type, :target_id, :details)
        ");
        $stmt->execute([
            'admin_id' => $_SESSION['admin_id'],
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }
}
