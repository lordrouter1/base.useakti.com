<?php
/**
 * Controller: DashboardController
 * Painel principal com estatísticas
 */

class DashboardController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        $clientModel = new TenantClient($this->db);
        $logModel = new AdminLog($this->db);

        $stats = $clientModel->getStats();
        $recentLogs = $logModel->readRecent(10);

        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}
