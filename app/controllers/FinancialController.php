<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Models\Installment;
use Akti\Models\PaymentGateway;
use Akti\Models\CompanySettings;
use Akti\Core\ModuleBootloader;
use Akti\Services\InstallmentService;
use Akti\Services\TransactionService;
use Akti\Services\FinancialImportService;
use Akti\Services\FinancialReportService;
use Akti\Services\FinancialAuditService;
use Akti\Utils\Input;

/**
 * FinancialController — Controller principal do módulo financeiro (SLIM).
 *
 * Após a refatoração Fase 2, este controller cuida APENAS de:
 *   - index()          → Dashboard financeiro
 *   - payments()       → Página unificada com sidebar
 *   - getSummaryJson() → AJAX: resumo do mês
 *
 * Responsabilidades delegadas via rotas para:
 *   - InstallmentController      → Parcelas
 *   - TransactionController      → Transações (entradas/saídas)
 *   - FinancialImportController  → Importação OFX/CSV/Excel
 *
 * Método estático getFinancialImportFields() mantido por compatibilidade
 * com views que o referenciam via FinancialController::getFinancialImportFields().
 *
 * @package Akti\Controllers
 * @see     InstallmentController
 * @see     TransactionController
 * @see     FinancialImportController
 */
class FinancialController extends BaseController
{
    private Financial $financial;
    private Installment $installmentModel;
    private InstallmentService $installmentService;
    private FinancialReportService $reportService;

    public function __construct(
        \PDO $db,
        Financial $financial,
        Installment $installmentModel,
        InstallmentService $installmentService,
        FinancialReportService $reportService
    ) {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo financeiro desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $this->db = $db;
        $this->financial = $financial;
        $this->installmentModel = $installmentModel;
        $this->installmentService = $installmentService;
        $this->reportService = $reportService;
    }

    // ═══════════════════════════════════════════
    // DASHBOARD FINANCEIRO
    // ═══════════════════════════════════════════

    /**
     * Dashboard com cards de resumo, gráficos e alertas.
     */
    public function index()
    {
        $month = Input::get('month', 'int', (int) date('m'));
        $year  = Input::get('year', 'int', (int) date('Y'));

        $this->installmentService->updateOverdue();

        $summary   = $this->reportService->getSummary($month, $year);
        $chartData = $this->reportService->getChartData(6);

        $pendingConfirmations = $this->reportService->getPendingConfirmations();
        $overdueInstallments  = $this->reportService->getOverdueInstallments();
        $upcomingInstallments = $this->reportService->getUpcomingInstallments(7);

        $categories = Financial::getCategories();

        require 'app/views/layout/header.php';
        require 'app/views/financial/index.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════
    // PAGAMENTOS — Página Unificada com Sidebar
    // ═══════════════════════════════════════════

    /**
     * Página unificada com sidebar: parcelas, transações, importação, nova transação.
     * As tabelas internas são carregadas via AJAX (InstallmentController, TransactionController).
     */
    public function payments()
    {
        $this->installmentService->updateOverdue();

        $month   = (int) date('m');
        $year    = (int) date('Y');
        $summary = $this->reportService->getSummary($month, $year);

        $categories = Financial::getCategories();

        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        $overdueCount        = count($this->reportService->getOverdueInstallments());
        $pendingConfirmCount = count($this->reportService->getPendingConfirmations());

        // Gateways de pagamento ativos (para integração com parcelas)
        $gatewayModel = new PaymentGateway($this->db);
        $activeGateways = $gatewayModel->getActive();

        require 'app/views/layout/header.php';
        require 'app/views/financial/payments_new.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════
    // AJAX: Resumo financeiro em JSON
    // ═══════════════════════════════════════════

    /**
     * Retorna resumo financeiro do mês/ano em JSON.
     */
    public function getSummaryJson()
    {
        $month = Input::get('month', 'int') ?: (int) date('m');
        $year  = Input::get('year', 'int') ?: (int) date('Y');
        $summary = $this->reportService->getSummary($month, $year);
        $this->json($summary);
    }

    // ═══════════════════════════════════════════
    // AJAX: DRE (Demonstrativo de Resultado)
    // ═══════════════════════════════════════════

    /**
     * Retorna DRE em JSON para o período informado.
     */
    public function getDre()
    {
        $fromMonth = Input::get('from', 'string', date('Y') . '-01');
        $toMonth   = Input::get('to', 'string', date('Y-m'));

        // Validar formato YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $fromMonth)) $fromMonth = date('Y') . '-01';
        if (!preg_match('/^\d{4}-\d{2}$/', $toMonth)) $toMonth = date('Y-m');

        $dre = $this->reportService->getDre($fromMonth, $toMonth);
        $this->json(['success' => true, 'data' => $dre]);
    }

    // ═══════════════════════════════════════════
    // AJAX: Fluxo de Caixa Projetado
    // ═══════════════════════════════════════════

    /**
     * Retorna projeção de fluxo de caixa em JSON.
     */
    public function getCashflow()
    {
        $months           = Input::get('months', 'int', 6);
        $includeRecurring = Input::get('recurring', 'int', 1);

        if ($months < 1 || $months > 24) $months = 6;

        $projection = $this->reportService->getCashflowProjection($months, (bool) $includeRecurring);
        $this->json(['success' => true, 'data' => $projection]);
    }

    // ═══════════════════════════════════════════
    // EXPORTAÇÃO CSV
    // ═══════════════════════════════════════════

    /**
     * Exporta transações em CSV (download direto).
     */
    public function exportTransactionsCsv()
    {
        $filters = [
            'type'     => Input::get('type', 'string', ''),
            'category' => Input::get('category', 'string', ''),
            'month'    => Input::get('month', 'int', 0),
            'year'     => Input::get('year', 'int', 0),
            'search'   => Input::get('search', 'string', ''),
        ];

        $csv = $this->reportService->exportTransactionsCsv($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transacoes_' . date('Ymd_His') . '.csv"');
        echo $csv;
        exit;
    }

    /**
     * Exporta DRE em CSV (download direto).
     */
    public function exportDreCsv()
    {
        $fromMonth = Input::get('from', 'string', date('Y') . '-01');
        $toMonth   = Input::get('to', 'string', date('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $fromMonth)) $fromMonth = date('Y') . '-01';
        if (!preg_match('/^\d{4}-\d{2}$/', $toMonth)) $toMonth = date('Y-m');

        $csv = $this->reportService->exportDreCsv($fromMonth, $toMonth);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dre_' . $fromMonth . '_' . $toMonth . '.csv"');
        echo $csv;
        exit;
    }

    /**
     * Exporta fluxo de caixa projetado em CSV (download direto).
     */
    public function exportCashflowCsv()
    {
        $months           = Input::get('months', 'int', 6);
        $includeRecurring = Input::get('recurring', 'int', 1);

        if ($months < 1 || $months > 24) $months = 6;

        $csv = $this->reportService->exportCashflowCsv($months, (bool) $includeRecurring);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="fluxo_caixa_' . date('Ymd') . '.csv"');
        echo $csv;
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: Relatório de Auditoria Financeira
    // ═══════════════════════════════════════════

    /**
     * Retorna log de auditoria financeira em JSON (paginado com filtros).
     */
    public function getAuditLog()
    {
        header('Content-Type: application/json');

        $auditService = new FinancialAuditService($this->db);

        $filters = [];
        $entityType = Input::get('entity_type', 'string', '');
        $action     = Input::get('action_filter', 'string', '');
        $dateFrom   = Input::get('date_from', 'string', '');
        $dateTo     = Input::get('date_to', 'string', '');
        $search     = Input::get('search', 'string', '');

        if ($entityType) $filters['entity_type'] = $entityType;
        if ($action)     $filters['action'] = $action;
        if ($dateFrom)   $filters['date_from'] = $dateFrom;
        if ($dateTo)     $filters['date_to'] = $dateTo;
        if ($search)     $filters['search'] = $search;

        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $result = $auditService->getPaginated($filters, $page, $perPage);

        $this->json([
            'success'     => true,
            'items'       => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'per_page'    => $result['perPage'],
            'total_pages' => $result['totalPages'],
        ]);
    }

    /**
     * Exporta auditoria financeira em CSV.
     */
    public function exportAuditCsv()
    {
        $auditService = new FinancialAuditService($this->db);

        $filters = [];
        $entityType = Input::get('entity_type', 'string', '');
        $action     = Input::get('action_filter', 'string', '');
        $dateFrom   = Input::get('date_from', 'string', '');
        $dateTo     = Input::get('date_to', 'string', '');
        $search     = Input::get('search', 'string', '');

        if ($entityType) $filters['entity_type'] = $entityType;
        if ($action)     $filters['action'] = $action;
        if ($dateFrom)   $filters['date_from'] = $dateFrom;
        if ($dateTo)     $filters['date_to'] = $dateTo;
        if ($search)     $filters['search'] = $search;

        $csv = $auditService->exportCsv($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_financeira_' . date('Ymd_His') . '.csv"');
        echo $csv;
        exit;
    }

    // ═══════════════════════════════════════════
    // COMPATIBILIDADE: Métodos estáticos usados por views
    // ═══════════════════════════════════════════

    /**
     * Campos disponíveis para mapeamento de importação financeira.
     * Mantido por compatibilidade com views que referenciam FinancialController::getFinancialImportFields().
     *
     * @deprecated Use FinancialImportController::getFinancialImportFields() ou FinancialImportService::getFinancialImportFields()
     */
    public static function getFinancialImportFields(): array
    {
        return FinancialImportService::getFinancialImportFields();
    }
}
