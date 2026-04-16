<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\TenantClient;
use Akti\Models\Master\AdminLog;

/**
 * Class DashboardController.
 */
class DashboardController extends MasterBaseController
{
    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();

        $clientModel = new TenantClient($this->db);
        $logModel = new AdminLog($this->db);

        $stats = $clientModel->getStats();
        $recentLogs = $logModel->readRecent(10);

        $this->renderMaster('dashboard/index', compact('stats', 'recentLogs'));
    }
}
