<?php

namespace Akti\Controllers;

use Akti\Services\ProductionCostService;
use Akti\Utils\Input;

/**
 * Class ProductionCostController.
 */
class ProductionCostController extends BaseController
{
    private ProductionCostService $costService;

    /**
     * Construtor da classe ProductionCostController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->costService = new ProductionCostService($db);
    }

    /**
     * Exibe a página de listagem.
     */
    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $config = $this->costService->getConfig($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/production_costs/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salva dados.
     */
    public function saveConfig()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'         => $this->getTenantId(),
            'labor_cost_hour'   => Input::post('labor_cost_hour', 'string', '0.00'),
            'overhead_type'     => Input::post('overhead_type', 'string', 'percentage'),
            'overhead_value'    => Input::post('overhead_value', 'string', '0.00'),
            'profit_margin_pct' => Input::post('profit_margin_pct', 'string', '0.00'),
        ];

        $this->costService->saveConfig($data);
        $_SESSION['flash_success'] = 'Configuração de custos salva.';
        header('Location: ?page=production_costs');
    }

    /**
     * Calcula valor.
     */
    public function calculate()
    {
        $this->requireAuth();
        $orderId = Input::get('order_id', 'int', 0) ?: Input::post('order_id', 'int', 0);
        $tenantId = $this->getTenantId();

        if (!$orderId) {
            $_SESSION['flash_error'] = 'Pedido não informado.';
            header('Location: ?page=production_costs');
            return;
        }

        $cost = $this->costService->calculateOrderCost($orderId, $tenantId);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => $cost]);
            return;
        }

        $config = $this->costService->getConfig($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/production_costs/result.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Margin report.
     */
    public function marginReport()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $page = Input::get('p', 'int', 1);
        $report = $this->costService->getMarginReport($tenantId, $page, 20);

        require 'app/views/layout/header.php';
        require 'app/views/production_costs/margin_report.php';
        require 'app/views/layout/footer.php';
    }
}
