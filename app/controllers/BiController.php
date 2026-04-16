<?php

namespace Akti\Controllers;

use Akti\Services\BiService;
use Akti\Utils\Input;

/**
 * Class BiController.
 */
class BiController extends BaseController
{
    private BiService $biService;

    /**
     * Construtor da classe BiController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->biService = new BiService($this->db);
    }

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $dateFrom = Input::get('date_from') ?: date('Y-m-01', strtotime('-5 months'));
        $dateTo = Input::get('date_to') ?: date('Y-m-d');
        $tab = Input::get('tab') ?: 'sales';

        $salesData = $this->biService->getSalesDashboard($dateFrom, $dateTo);
        $productionData = $this->biService->getProductionDashboard($dateFrom, $dateTo);
        $financialData = $this->biService->getFinancialDashboard($dateFrom, $dateTo);

        $pageTitle = 'Business Intelligence';
        require 'app/views/layout/header.php';
        require 'app/views/bi/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Drill down.
     * @return void
     */
    public function drillDown(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $type = Input::get('type') ?: '';
        $filters = [
            'date_from'  => Input::get('date_from') ?: '',
            'date_to'    => Input::get('date_to') ?: '',
            'status'     => Input::get('status') ?: '',
            'stage'      => Input::get('stage') ?: '',
            'product_id' => Input::get('product_id') ?: '',
        ];

        $data = $this->biService->drillDown($type, $filters);
        $this->json(['success' => true, 'data' => $data, 'type' => $type]);
    }

    /**
     * Exporta dados.
     * @return void
     */
    public function exportPdf(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $dateFrom = Input::get('date_from') ?: date('Y-m-01', strtotime('-5 months'));
        $dateTo = Input::get('date_to') ?: date('Y-m-d');
        $tab = Input::get('tab') ?: 'sales';

        // Return JSON with the data for client-side PDF generation via html2canvas + jsPDF
        $data = match ($tab) {
            'production' => $this->biService->getProductionDashboard($dateFrom, $dateTo),
            'financial'  => $this->biService->getFinancialDashboard($dateFrom, $dateTo),
            default      => $this->biService->getSalesDashboard($dateFrom, $dateTo),
        };

        $this->json(['success' => true, 'data' => $data, 'tab' => $tab]);
    }
}
