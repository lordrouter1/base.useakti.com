<?php
namespace Akti\Controllers;

use Akti\Models\ReportModel;
use Akti\Models\NfeReportModel;
use Akti\Models\CompanySettings;
use Akti\Services\ReportPdfService;
use Akti\Services\ReportExcelService;
use Akti\Utils\Input;
use Akti\Utils\Validator;

/**
 * Controller: ReportController
 * Gerencia a geração de relatórios financeiros em PDF e Excel.
 * Actions: index (view de filtros), exportPdf, exportExcel.
 *
 * Toda lógica de geração foi extraída para:
 *  - ReportPdfService   (PDF via TCPDF)
 *  - ReportExcelService (Excel via PhpSpreadsheet)
 */
class ReportController extends BaseController {
    private ReportModel $report;
    private NfeReportModel $nfeReport;
    private array $company;

    /** @var ReportPdfService */
    private ReportPdfService $pdfService;

    /** @var ReportExcelService */
    private ReportExcelService $excelService;

    /**
     * Construtor da classe ReportController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param ReportModel $report Report
     * @param NfeReportModel $nfeReport Nfe report
     * @param CompanySettings $companySettings Company settings
     */
    public function __construct(\PDO $db, ReportModel $report, NfeReportModel $nfeReport, CompanySettings $companySettings)
    {
        $this->db = $db;
        $this->report    = $report;
        $this->nfeReport = $nfeReport;

        $this->company   = $companySettings->getAll();

        $responsibleUser = $_SESSION['user_name'] ?? 'Sistema';

        $this->pdfService   = new ReportPdfService($this->report, $this->nfeReport, $this->company, $responsibleUser);
        $this->excelService = new ReportExcelService($this->report, $this->nfeReport, $this->company, $responsibleUser);
    }

    // ═══════════════════════════════════════════
    // INDEX — VIEW DE FILTROS
    // ═══════════════════════════════════════════

    /**
     * Exibe a tela de filtros e seleção de relatórios.
     */
    public function index(): void
    {
        $company = $this->company;

        // Dados para os selects de filtro (categoria Produtos & Estoque)
        $productsList    = $this->report->getProductsForSelect();
        $warehousesList  = $this->report->getWarehousesForSelect();

        // Dados para o select de filtro (categoria Comissões)
        $usersList = $this->report->getUsersForSelect();

        // Dados para os selects de filtro (categoria Fiscal)
        $nfeCustomersList = $this->nfeReport->getCustomersWithNfe();

        require 'app/views/layout/header.php';
        require 'app/views/reports/index.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════
    // EXPORTAR PDF
    // ═══════════════════════════════════════════

    /**
     * Gera e envia um PDF para download conforme o tipo de relatório.
     */
    public function exportPdf(): void
    {
        $type = Input::get('type', 'string', '');

        // Relatórios sem período obrigatório
        $noPeriodTypes = ['open_installments', 'product_catalog', 'stock_warehouse'];
        if (in_array($type, $noPeriodTypes, true)) {
            $this->dispatchPdf($type);
            return;
        }

        // Relatórios que exigem período
        [$start, $end] = $this->requirePeriod();
        if ($start === null) {
            return;
        }

        $this->dispatchPdf($type, $start, $end);
    }

    // ═══════════════════════════════════════════
    // EXPORTAR EXCEL
    // ═══════════════════════════════════════════

    /**
     * Gera e envia um XLSX para download conforme o tipo de relatório.
     */
    public function exportExcel(): void
    {
        $type = Input::get('type', 'string', '');

        // Relatórios sem período obrigatório
        $noPeriodTypes = ['open_installments', 'product_catalog', 'stock_warehouse'];
        if (in_array($type, $noPeriodTypes, true)) {
            $this->dispatchExcel($type);
            return;
        }

        // Relatórios que exigem período
        [$start, $end] = $this->requirePeriod();
        if ($start === null) {
            return;
        }

        $this->dispatchExcel($type, $start, $end);
    }

    // ═══════════════════════════════════════════
    // HELPERS INTERNOS
    // ═══════════════════════════════════════════

    /**
     * Valida e retorna período obrigatório (start, end). Redireciona se inválido.
     * @return array{0: ?string, 1: ?string}
     */
    private function requirePeriod(): array
    {
        $start = Input::get('start', 'date', '');
        $end   = Input::get('end', 'date', '');

        $v = new Validator();
        $v->required('start', $start, 'Data Inicial')
          ->required('end', $end, 'Data Final');

        if ($v->fails()) {
            $_SESSION['flash_error'] = implode('<br>', $v->errors());
            header('Location: ?page=reports');
            exit;
        }

        return [$start, $end];
    }

    /**
     * Despacha a geração de PDF para o ReportPdfService conforme o tipo.
     */
    private function dispatchPdf(string $type, ?string $start = null, ?string $end = null): void
    {
        $map = $this->getTypeMethodMap();

        if (!isset($map[$type])) {
            $_SESSION['flash_error'] = 'Tipo de relatório inválido.';
            header('Location: ?page=reports');
            exit;
        }

        $method = $map[$type]['method'];
        $args   = $this->buildArgs($map[$type], $start, $end);

        $this->pdfService->$method(...$args);
    }

    /**
     * Despacha a geração de Excel para o ReportExcelService conforme o tipo.
     */
    private function dispatchExcel(string $type, ?string $start = null, ?string $end = null): void
    {
        $map = $this->getTypeMethodMap();

        if (!isset($map[$type])) {
            $_SESSION['flash_error'] = 'Tipo de relatório inválido.';
            header('Location: ?page=reports');
            exit;
        }

        $method = $map[$type]['method'];
        $args   = $this->buildArgs($map[$type], $start, $end);

        $this->excelService->$method(...$args);
    }

    /**
     * Mapa de tipo de relatório → método do service e parâmetros extras.
     */
    private function getTypeMethodMap(): array
    {
        return [
            'orders_period'      => ['method' => 'exportOrdersByPeriod',      'period' => true],
            'revenue_customer'   => ['method' => 'exportRevenueByCustomer',   'period' => true],
            'income_statement'   => ['method' => 'exportIncomeStatement',     'period' => true],
            'scheduled_contacts' => ['method' => 'exportScheduledContacts',   'period' => true],
            'stock_movements'    => ['method' => 'exportStockMovements',      'period' => true],
            'commissions_report' => ['method' => 'exportCommissionsReport',   'period' => true,  'extra' => 'user_id'],
            'nfes_period'        => ['method' => 'exportNfesByPeriod',        'period' => true],
            'tax_summary'        => ['method' => 'exportTaxSummary',          'period' => true],
            'nfes_customer'      => ['method' => 'exportNfesByCustomer',      'period' => true],
            'cfop_summary'       => ['method' => 'exportCfopSummary',         'period' => true],
            'cancelled_nfes'     => ['method' => 'exportCancelledNfes',       'period' => true],
            'inutilizacoes'      => ['method' => 'exportInutilizacoes',       'period' => true],
            'sefaz_logs'         => ['method' => 'exportSefazLogs',           'period' => true],
            'open_installments'  => ['method' => 'exportOpenInstallments',    'period' => false],
            'product_catalog'    => ['method' => 'exportProductCatalog',      'period' => false],
            'stock_warehouse'    => ['method' => 'exportStockByWarehouse',    'period' => false],
        ];
    }

    /**
     * Constrói a lista de argumentos para o método do service.
     */
    private function buildArgs(array $config, ?string $start, ?string $end): array
    {
        $args = [];

        if ($config['period'] ?? false) {
            $args[] = $start;
            $args[] = $end;
        }

        if (isset($config['extra']) && $config['extra'] === 'user_id') {
            $userId = Input::get('user_id', 'int', null);
            $args[] = $userId ?: null;
        }

        return $args;
    }
}
