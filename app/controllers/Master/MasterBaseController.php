<?php

namespace Akti\Controllers\Master;

use Akti\Controllers\BaseController;

abstract class MasterBaseController extends BaseController
{
    public function __construct(?\PDO $db = null)
    {
        // Always use master DB regardless of what the Router injects
        $this->db = \Database::getMasterInstance();
    }

    protected function requireMasterAuth(): void
    {
        if (empty($_SESSION['is_master_admin'])) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Acesso negado.'], 403);
            }
            $this->redirect('?page=login');
        }
    }

    protected function getMasterAdminId(): ?int
    {
        return $_SESSION['master_admin_id'] ?? null;
    }

    protected function logAction(string $action, string $targetType, ?int $targetId, string $details): void
    {
        try {
            $adminId = $this->getMasterAdminId();
            if ($adminId) {
                $log = new \Akti\Models\Master\AdminLog($this->db);
                $log->log($adminId, $action, $targetType, $targetId, $details);
            }
        } catch (\Exception $e) {
            // Não bloquear ação por erro de log
        }
    }

    protected function renderMaster(string $view, array $data = []): void
    {
        // Variables available to views via $data array or individually
        foreach ($data as $__key => $__val) {
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $__key)) {
                continue;
            }
            $$__key = $__val;
        }
        unset($__key, $__val);

        // Buffer the view first so variables set in the view ($pageTitle,
        // $topbarActions, $pageScripts, etc.) are available to header/footer.
        ob_start();
        require 'app/views/master/' . $view . '.php';
        $__viewContent = ob_get_clean();

        require 'app/views/master/layout/header.php';
        echo $__viewContent;
        require 'app/views/master/layout/footer.php';
    }
}
