<?php
namespace Akti\Controllers;

use Akti\Models\Pipeline;
use Akti\Models\Order;
use Akti\Models\Customer;
use Akti\Core\Log;

class DashboardController {

    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function index() {
        $db = $this->db;

        // Valores padrão (caso alguma query falhe)
        $pipelineStats = ['total_active' => 0, 'total_delayed' => 0, 'completed_month' => 0, 'total_value' => 0, 'by_stage' => [], 'delayed_orders' => []];
        $stages = Pipeline::$stages;
        $delayedOrders = [];
        $totalOrders = 0;
        $ordersByStatus = [];
        $totalActiveValue = 0;
        $totalCustomers = 0;

        try {
            // Estatísticas do Pipeline
            $pipelineModel = new Pipeline($db);
            $pipelineStats = $pipelineModel->getStats();
            $delayedOrders = $pipelineStats['delayed_orders'] ?? [];

            // Estatísticas gerais
            $orderModel = new Order($db);
            $totalOrders = $orderModel->countAll();
            $ordersByStatus = $orderModel->countByStatus();
            $totalActiveValue = $orderModel->totalActiveValue();

            $customerModel = new Customer($db);
            $totalCustomers = $customerModel->countAll();
        } catch (\Exception $e) {
            // Se o pipeline ainda não foi migrado, não travar
            Log::channel('general')->warning('Dashboard error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }

        require 'app/views/layout/header.php';
        require 'app/views/dashboard/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * FEAT-016: Dashboard em tempo real com SSE.
     */
    public function realtime()
    {
        $db = $this->db;

        $stages = Pipeline::$stages;

        require 'app/views/layout/header.php';
        require 'app/views/dashboard/realtime.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * FEAT-016: Endpoint JSON para dados do dashboard (polling/SSE).
     */
    public function realtimeData()
    {
        $db = $this->db;

        header('Content-Type: application/json; charset=utf-8');

        try {
            $pipelineModel = new Pipeline($db);
            $pipelineStats = $pipelineModel->getStats();

            $orderModel = new Order($db);
            $totalOrders = $orderModel->countAll();
            $ordersByStatus = $orderModel->countByStatus();
            $totalActiveValue = $orderModel->totalActiveValue();

            $customerModel = new Customer($db);
            $totalCustomers = $customerModel->countAll();

            echo json_encode([
                'success' => true,
                'data' => [
                    'pipeline'       => $pipelineStats,
                    'total_orders'   => $totalOrders,
                    'orders_status'  => $ordersByStatus,
                    'active_value'   => $totalActiveValue,
                    'total_customers' => $totalCustomers,
                    'timestamp'      => date('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erro ao buscar dados']);
        }
        exit;
    }
}
