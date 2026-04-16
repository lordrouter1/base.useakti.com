<?php

namespace Akti\Controllers;

use Akti\Models\ProductionConsumption;
use Akti\Services\ProducaoService;
use Akti\Services\SupplyCostService;
use Akti\Services\SupplyForecastService;
use Akti\Utils\Input;

/**
 * Controller do dashboard de insumos (eficiência, alertas, previsões).
 */
class SupplyDashboardController extends BaseController
{
    private ProducaoService $producaoService;
    private ProductionConsumption $consumptionModel;

    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->producaoService = new ProducaoService($db);
        $this->consumptionModel = new ProductionConsumption($db);
    }

    /**
     * Dashboard de eficiência (previsto vs real).
     */
    public function efficiency(): void
    {
        $filters = [
            'date_from'  => Input::get('date_from', ''),
            'date_to'    => Input::get('date_to', ''),
            'product_id' => Input::get('product_id') ? (int) Input::get('product_id') : null,
        ];

        $data = $this->producaoService->getEfficiencyDashboard($filters);
        $data['filters'] = $filters;

        // Lista de produtos para filtro
        $stmt = $this->db->query("SELECT id, name FROM products WHERE active = 1 ORDER BY name");
        $data['products'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $pageTitle = 'Dashboard de Eficiência';
        extract($data);

        include __DIR__ . '/../views/supply_dashboard/efficiency.php';
    }

    /**
     * Relatório de consumo pendente (apontamento).
     */
    public function report(): void
    {
        $productId = Input::get('product_id') ? (int) Input::get('product_id') : null;
        $pending = $this->consumptionModel->getPendingReports($productId);

        $stmt = $this->db->query("SELECT id, name FROM products WHERE active = 1 ORDER BY name");
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $pageTitle = 'Apontamento de Consumo';
        include __DIR__ . '/../views/supply_dashboard/report.php';
    }

    /**
     * AJAX: salvar apontamento real.
     */
    public function saveReport(): void
    {
        $logId    = (int) Input::post('log_id');
        $actual   = (float) Input::post('actual_quantity');
        $notes    = Input::post('notes', '');
        $userId   = $_SESSION['user_id'] ?? 0;

        $result = $this->producaoService->reportActualConsumption($logId, $actual, $userId, $notes);
        $this->json($result);
    }

    /**
     * AJAX: dados do dashboard de eficiência (para recarregar sem page reload).
     */
    public function efficiencyData(): void
    {
        $filters = [
            'date_from'  => Input::get('date_from', ''),
            'date_to'    => Input::get('date_to', ''),
            'product_id' => Input::get('product_id') ? (int) Input::get('product_id') : null,
        ];

        $data = $this->producaoService->getEfficiencyDashboard($filters);
        $this->json($data);
    }
}
