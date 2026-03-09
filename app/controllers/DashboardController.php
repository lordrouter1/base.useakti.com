<?php
namespace Akti\Controllers;

use Akti\Models\Pipeline;
use Akti\Models\Order;
use Akti\Models\Customer;
use Database;

class DashboardController {
    public function index() {
        $database = new Database();
        $db = $database->getConnection();

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
        } catch (Exception $e) {
            // Se o pipeline ainda não foi migrado, não travar
            error_log('Dashboard error: ' . $e->getMessage());
        }

        require 'app/views/layout/header.php';
        require 'app/views/dashboard/index.php';
        require 'app/views/layout/footer.php';
    }
}
