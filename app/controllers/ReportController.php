<?php
namespace Akti\Controllers;

use Akti\Models\ReportModel;
use Akti\Models\CompanySettings;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use PDO;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Controller: ReportController
 * Gerencia a geração de relatórios financeiros em PDF e Excel.
 * Actions: index (view de filtros), exportPdf, exportExcel.
 *
 * Design de exportação:
 *  - Cabeçalho profissional com logo, nome da empresa e título do relatório
 *  - Resumo executivo com métricas-chave
 *  - Tabelas minimalistas com visual moderno
 *  - Rodapé com data de emissão e usuário responsável
 */
class ReportController
{
    private $db;
    private $report;
    private $company;

    /** @var string Nome do usuário responsável pela geração */
    private $responsibleUser;

    // ── Paleta de cores (ARGB hex) ──
    private const CLR_PRIMARY     = 'FF2C3E50'; // Azul escuro
    private const CLR_PRIMARY_LT  = 'FFECF0F1'; // Cinza muito claro
    private const CLR_ACCENT      = 'FF3498DB'; // Azul accent
    private const CLR_SUCCESS     = 'FF27AE60'; // Verde
    private const CLR_SUCCESS_LT  = 'FFF0FAF4'; // Verde claro bg
    private const CLR_DANGER      = 'FFE74C3C'; // Vermelho
    private const CLR_DANGER_LT   = 'FFFDF0EF'; // Vermelho claro bg
    private const CLR_WHITE       = 'FFFFFFFF';
    private const CLR_MUTED       = 'FF95A5A6'; // Cinza texto
    private const CLR_BORDER      = 'FFDEE2E6'; // Borda sutil
    private const CLR_ROW_ALT     = 'FFF8F9FA'; // Linha zebrada
    private const CLR_SUMMARY_BG  = 'FFF1F5F9'; // Fundo resumo

    public function __construct()
    {
        $database   = new Database();
        $this->db   = $database->getConnection();
        $this->report = new ReportModel($this->db);

        $companySettings = new CompanySettings($this->db);
        $this->company   = $companySettings->getAll();

        $this->responsibleUser = $_SESSION['user_name'] ?? 'Sistema';
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
        if ($type === 'open_installments') {
            $this->exportPdfOpenInstallments();
            return;
        }
        if ($type === 'product_catalog') {
            $this->exportPdfProductCatalog();
            return;
        }
        if ($type === 'stock_warehouse') {
            $this->exportPdfStockByWarehouse();
            return;
        }

        // Relatórios que exigem período
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

        switch ($type) {
            case 'orders_period':
                $this->exportPdfOrdersByPeriod($start, $end);
                break;
            case 'revenue_customer':
                $this->exportPdfRevenueByCustomer($start, $end);
                break;
            case 'income_statement':
                $this->exportPdfIncomeStatement($start, $end);
                break;
            case 'scheduled_contacts':
                $this->exportPdfScheduledContacts($start, $end);
                break;
            case 'stock_movements':
                $this->exportPdfStockMovements($start, $end);
                break;
            case 'commissions_report':
                $userId = Input::get('user_id', 'int', null);
                $this->exportPdfCommissionsReport($start, $end, $userId ?: null);
                break;
            default:
                $_SESSION['flash_error'] = 'Tipo de relatório inválido.';
                header('Location: ?page=reports');
                exit;
        }
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
        if ($type === 'open_installments') {
            $this->exportExcelOpenInstallments();
            return;
        }
        if ($type === 'product_catalog') {
            $this->exportExcelProductCatalog();
            return;
        }
        if ($type === 'stock_warehouse') {
            $this->exportExcelStockByWarehouse();
            return;
        }

        // Relatórios que exigem período
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

        switch ($type) {
            case 'orders_period':
                $this->exportExcelOrdersByPeriod($start, $end);
                break;
            case 'revenue_customer':
                $this->exportExcelRevenueByCustomer($start, $end);
                break;
            case 'income_statement':
                $this->exportExcelIncomeStatement($start, $end);
                break;
            case 'scheduled_contacts':
                $this->exportExcelScheduledContacts($start, $end);
                break;
            case 'stock_movements':
                $this->exportExcelStockMovements($start, $end);
                break;
            case 'commissions_report':
                $userId = Input::get('user_id', 'int', null);
                $this->exportExcelCommissionsReport($start, $end, $userId ?: null);
                break;
            default:
                $_SESSION['flash_error'] = 'Tipo de relatório inválido.';
                header('Location: ?page=reports');
                exit;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — PEDIDOS POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfOrdersByPeriod(string $start, string $end): void
    {
        $data = $this->report->getOrdersByPeriod($start, $end);
        $stageLabels = ReportModel::getStageLabels();

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $pdf = $this->createPdf('Relatório de Pedidos por Período');

        // ── Resumo executivo ──
        $totalValue = array_sum(array_column($data, 'total'));
        $totalPaid = count(array_filter($data, fn($r) => ($r['payment_status'] ?? '') === 'pago'));
        $this->pdfSummaryBox($pdf, [
            'Período'          => $period,
            'Total de Pedidos' => count($data),
            'Valor Total'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
            'Pedidos Pagos'    => $totalPaid,
        ]);

        // ── Tabela ──
        $headers = ['#', 'Cliente', 'Total (R$)', 'Status Pgto', 'Etapa', 'Data'];
        $widths  = [12, 60, 30, 30, 30, 28];
        $aligns  = ['C', 'L', 'R', 'C', 'C', 'C'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                $row['id'],
                mb_substr($row['customer_name'] ?? 'N/A', 0, 35),
                number_format((float)$row['total'], 2, ',', '.'),
                ReportModel::getStatusLabel($row['payment_status'] ?? ''),
                $stageLabels[$row['pipeline_stage']] ?? $row['pipeline_stage'],
                $row['created_at_fmt'],
            ], $aligns, $fill);
            $fill = !$fill;
        }

        // ── Linha de totais ──
        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1], 'text' => 'TOTAL (' . count($data) . ' pedidos)', 'align' => 'R'],
            ['w' => $widths[2], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[3] + $widths[4] + $widths[5], 'text' => '', 'align' => 'C'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'pedidos_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — FATURAMENTO POR CLIENTE
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfRevenueByCustomer(string $start, string $end): void
    {
        $data = $this->report->getRevenueByCustomer($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalOrders  = array_sum(array_column($data, 'total_orders'));
        $totalRevenue = array_sum(array_column($data, 'total_revenue'));

        $pdf = $this->createPdf('Faturamento por Cliente');

        // ── Resumo executivo ──
        $this->pdfSummaryBox($pdf, [
            'Período'             => $period,
            'Clientes Ativos'     => count($data),
            'Total de Pedidos'    => $totalOrders,
            'Faturamento Total'   => 'R$ ' . number_format($totalRevenue, 2, ',', '.'),
        ]);

        // ── Tabela ──
        $headers = ['Cliente', 'Qtd Pedidos', 'Faturamento (R$)'];
        $widths  = [90, 40, 60];
        $aligns  = ['L', 'C', 'R'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                mb_substr($row['customer_name'] ?? 'Sem cliente', 0, 55),
                $row['total_orders'],
                'R$ ' . number_format((float)$row['total_revenue'], 2, ',', '.'),
            ], $aligns, $fill);
            $fill = !$fill;
        }

        // ── Totais ──
        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0], 'text' => 'TOTAL (' . count($data) . ' clientes)', 'align' => 'R'],
            ['w' => $widths[1], 'text' => (string)$totalOrders, 'align' => 'C'],
            ['w' => $widths[2], 'text' => 'R$ ' . number_format($totalRevenue, 2, ',', '.'), 'align' => 'R'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'faturamento_cliente_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — DRE (DEMONSTRATIVO DE RESULTADO)
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfIncomeStatement(string $start, string $end): void
    {
        $data = $this->report->getIncomeStatement($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $net = $data['totals']['net_balance'];

        $pdf = $this->createPdf('Demonstrativo de Resultado (DRE)');

        // ── Resumo executivo ──
        $this->pdfSummaryBox($pdf, [
            'Período'           => $period,
            'Total de Entradas' => 'R$ ' . number_format($data['totals']['total_entries'], 2, ',', '.'),
            'Total de Saídas'   => 'R$ ' . number_format($data['totals']['total_exits'], 2, ',', '.'),
            'Saldo Líquido'     => 'R$ ' . number_format($net, 2, ',', '.'),
        ]);

        $headers = ['Categoria', 'Valor (R$)'];
        $widths  = [130, 60];

        // ── ENTRADAS ──
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(39, 174, 96);
        $pdf->Cell(0, 7, chr(226) . chr(150) . chr(178) . '  ENTRADAS', 0, 1, 'L');
        $pdf->SetTextColor(51, 51, 51);

        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data['entries'] as $row) {
            $label = ReportModel::getCategoryLabel($row['category']);
            $this->pdfTableRow($pdf, $widths, [
                $label,
                'R$ ' . number_format((float)$row['total'], 2, ',', '.'),
            ], ['L', 'R'], $fill);
            $fill = !$fill;
        }

        // Total de entradas
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(240, 250, 244);
        $pdf->SetDrawColor(39, 174, 96);
        $pdf->Cell($widths[0], 7, 'Total de Entradas', 'TB', 0, 'R', true);
        $pdf->Cell($widths[1], 7, 'R$ ' . number_format($data['totals']['total_entries'], 2, ',', '.'), 'TB', 1, 'R', true);
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->Ln(6);

        // ── SAÍDAS ──
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(231, 76, 60);
        $pdf->Cell(0, 7, chr(226) . chr(150) . chr(188) . '  SAÍDAS', 0, 1, 'L');
        $pdf->SetTextColor(51, 51, 51);

        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data['exits'] as $row) {
            $label = ReportModel::getCategoryLabel($row['category']);
            $this->pdfTableRow($pdf, $widths, [
                $label,
                'R$ ' . number_format((float)$row['total'], 2, ',', '.'),
            ], ['L', 'R'], $fill);
            $fill = !$fill;
        }

        // Total de saídas
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(253, 240, 239);
        $pdf->SetDrawColor(231, 76, 60);
        $pdf->Cell($widths[0], 7, 'Total de Saídas', 'TB', 0, 'R', true);
        $pdf->Cell($widths[1], 7, 'R$ ' . number_format($data['totals']['total_exits'], 2, ',', '.'), 'TB', 1, 'R', true);
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->Ln(8);

        // ── SALDO LÍQUIDO ──
        $netColor = $net >= 0 ? [39, 174, 96] : [231, 76, 60];
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(52, 73, 94);
        $pdf->Cell($widths[0], 10, 'SALDO LÍQUIDO', 'TB', 0, 'R', true);
        $pdf->SetTextColor(...$netColor);
        $pdf->Cell($widths[1], 10, 'R$ ' . number_format($net, 2, ',', '.'), 'TB', 1, 'R', true);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetDrawColor(222, 226, 230);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'dre_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — PARCELAS PENDENTES
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfOpenInstallments(): void
    {
        $data = $this->report->getOpenInstallments();

        $totalValue    = array_sum(array_column($data, 'amount'));
        $overdueCount  = count(array_filter($data, fn($r) => (int)$r['days_overdue'] > 0));

        $pdf = $this->createPdf('Parcelas Pendentes / Atrasadas');

        // ── Resumo executivo ──
        $this->pdfSummaryBox($pdf, [
            'Gerado em'           => date('d/m/Y H:i'),
            'Total de Parcelas'   => count($data),
            'Parcelas Atrasadas'  => $overdueCount,
            'Valor Pendente'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        // ── Tabela ──
        $headers = ['Pedido', 'Cliente', 'Parcela', 'Valor (R$)', 'Vencimento', 'Atraso (dias)'];
        $widths  = [18, 55, 20, 30, 28, 28];
        $aligns  = ['C', 'L', 'C', 'R', 'C', 'C'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $isOverdue = (int)$row['days_overdue'] > 0;

            if ($isOverdue) {
                $pdf->SetTextColor(200, 50, 50);
            }

            $this->pdfTableRow($pdf, $widths, [
                '#' . $row['order_id'],
                mb_substr($row['customer_name'] ?? 'N/A', 0, 32),
                $row['installment_number'],
                number_format((float)$row['amount'], 2, ',', '.'),
                $row['due_date_fmt'],
                $isOverdue ? $row['days_overdue'] : '-',
            ], $aligns, $fill);

            if ($isOverdue) {
                $pdf->SetTextColor(51, 51, 51);
            }

            $fill = !$fill;
        }

        // ── Totais ──
        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1] + $widths[2], 'text' => 'TOTAL (' . count($data) . ' parcelas)', 'align' => 'R'],
            ['w' => $widths[3], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[4] + $widths[5], 'text' => '', 'align' => 'C'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'parcelas_pendentes_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — PEDIDOS POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelOrdersByPeriod(string $start, string $end): void
    {
        $data = $this->report->getOrdersByPeriod($start, $end);
        $stageLabels = ReportModel::getStageLabels();

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalValue = array_sum(array_column($data, 'total'));
        $totalPaid  = count(array_filter($data, fn($r) => ($r['payment_status'] ?? '') === 'pago'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pedidos por Período');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Relatório de Pedidos por Período', $lastCol);

        // ── Resumo executivo ──
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'          => $period,
            'Total de Pedidos' => count($data),
            'Valor Total'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
            'Pedidos Pagos'    => $totalPaid,
        ]);

        // ── Cabeçalhos da tabela ──
        $headers = ['#', 'Cliente', 'Total (R$)', 'Status Pgto', 'Etapa', 'Data'];
        $headerRow = $row;
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, $item['customer_name'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, (float)$item['total']);
            $sheet->setCellValue('D' . $row, ReportModel::getStatusLabel($item['payment_status'] ?? ''));
            $sheet->setCellValue('E' . $row, $stageLabels[$item['pipeline_stage']] ?? $item['pipeline_stage']);
            $sheet->setCellValue('F' . $row, $item['created_at_fmt']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        // ── Totais ──
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' pedidos');
        $sheet->setCellValue('C' . $row, '=SUM(C' . $dataStartRow . ':C' . ($row - 1) . ')');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'pedidos_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — FATURAMENTO POR CLIENTE
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelRevenueByCustomer(string $start, string $end): void
    {
        $data = $this->report->getRevenueByCustomer($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalOrders  = array_sum(array_column($data, 'total_orders'));
        $totalRevenue = array_sum(array_column($data, 'total_revenue'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Faturamento por Cliente');

        $cols = ['A', 'B', 'C'];
        $lastCol = 'C';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Faturamento por Cliente', $lastCol);

        // ── Resumo executivo ──
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'           => $period,
            'Clientes Ativos'   => count($data),
            'Total de Pedidos'  => $totalOrders,
            'Faturamento Total' => 'R$ ' . number_format($totalRevenue, 2, ',', '.'),
        ]);

        // ── Cabeçalhos ──
        $headers = ['Cliente', 'Qtd Pedidos', 'Faturamento (R$)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['customer_name'] ?? 'Sem cliente');
            $sheet->setCellValue('B' . $row, (int)$item['total_orders']);
            $sheet->setCellValue('C' . $row, (float)$item['total_revenue']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        // ── Totais ──
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, '=SUM(B' . $dataStartRow . ':B' . ($row - 1) . ')');
        $sheet->setCellValue('C' . $row, '=SUM(C' . $dataStartRow . ':C' . ($row - 1) . ')');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'faturamento_cliente_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — DRE
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelIncomeStatement(string $start, string $end): void
    {
        $data = $this->report->getIncomeStatement($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $net = $data['totals']['net_balance'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DRE');

        $cols = ['A', 'B'];
        $lastCol = 'B';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Demonstrativo de Resultado (DRE)', $lastCol);

        // ── Resumo executivo ──
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'           => $period,
            'Total de Entradas' => 'R$ ' . number_format($data['totals']['total_entries'], 2, ',', '.'),
            'Total de Saídas'   => 'R$ ' . number_format($data['totals']['total_exits'], 2, ',', '.'),
            'Saldo Líquido'     => 'R$ ' . number_format($net, 2, ',', '.'),
        ]);

        // ── ENTRADAS ──
        $sheet->setCellValue('A' . $row, '▲  ENTRADAS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::CLR_SUCCESS);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUCCESS_LT);
        $row++;

        $sheet->setCellValue('A' . $row, 'Categoria');
        $sheet->setCellValue('B' . $row, 'Valor (R$)');
        $this->styleExcelHeader($sheet, "A{$row}:B{$row}");
        $row++;

        $entryStartRow = $row;
        foreach ($data['entries'] as $i => $entry) {
            $label = ReportModel::getCategoryLabel($entry['category']);
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, (float)$entry['total']);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:B{$row}", $i % 2 === 1);
            $row++;
        }

        // Total de entradas
        $sheet->setCellValue('A' . $row, 'Total de Entradas');
        $sheet->setCellValue('B' . $row, '=SUM(B' . $entryStartRow . ':B' . ($row - 1) . ')');
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUCCESS_LT);
        $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::CLR_SUCCESS);
        $totalEntriesRow = $row;
        $row += 2;

        // ── SAÍDAS ──
        $sheet->setCellValue('A' . $row, '▼  SAÍDAS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::CLR_DANGER);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_DANGER_LT);
        $row++;

        $sheet->setCellValue('A' . $row, 'Categoria');
        $sheet->setCellValue('B' . $row, 'Valor (R$)');
        $this->styleExcelHeader($sheet, "A{$row}:B{$row}");
        $row++;

        $exitStartRow = $row;
        foreach ($data['exits'] as $i => $exit) {
            $label = ReportModel::getCategoryLabel($exit['category']);
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, (float)$exit['total']);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:B{$row}", $i % 2 === 1);
            $row++;
        }

        // Total de saídas
        $sheet->setCellValue('A' . $row, 'Total de Saídas');
        $sheet->setCellValue('B' . $row, '=SUM(B' . $exitStartRow . ':B' . ($row - 1) . ')');
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_DANGER_LT);
        $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::CLR_DANGER);
        $totalExitsRow = $row;
        $row += 2;

        // ── SALDO LÍQUIDO ──
        $sheet->setCellValue('A' . $row, 'SALDO LÍQUIDO');
        $sheet->setCellValue('B' . $row, '=B' . $totalEntriesRow . '-B' . $totalExitsRow);
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);
        $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::CLR_PRIMARY);
        $row += 2;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'dre_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — PARCELAS PENDENTES
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelOpenInstallments(): void
    {
        $data = $this->report->getOpenInstallments();

        $totalValue   = array_sum(array_column($data, 'amount'));
        $overdueCount = count(array_filter($data, fn($r) => (int)$r['days_overdue'] > 0));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Parcelas Pendentes');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Parcelas Pendentes / Atrasadas', $lastCol);

        // ── Resumo executivo ──
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Gerado em'          => date('d/m/Y H:i'),
            'Total de Parcelas'  => count($data),
            'Parcelas Atrasadas' => $overdueCount,
            'Valor Pendente'     => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        // ── Cabeçalhos ──
        $headers = ['Pedido', 'Cliente', 'Parcela', 'Valor (R$)', 'Vencimento', 'Atraso (dias)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, '#' . $item['order_id']);
            $sheet->setCellValue('B' . $row, $item['customer_name'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item['installment_number']);
            $sheet->setCellValue('D' . $row, (float)$item['amount']);
            $sheet->setCellValue('E' . $row, $item['due_date_fmt']);
            $sheet->setCellValue('F' . $row, (int)$item['days_overdue'] > 0 ? (int)$item['days_overdue'] : '-');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);

            // Destaque vermelho para atrasados
            if ((int)$item['days_overdue'] > 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setARGB(self::CLR_DANGER);
            }
            $row++;
        }

        // ── Totais ──
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' parcelas');
        $sheet->setCellValue('D' . $row, '=SUM(D' . $dataStartRow . ':D' . ($row - 1) . ')');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'parcelas_pendentes_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — AGENDAMENTOS DE CONTATO (ORÇAMENTO)
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfScheduledContacts(string $start, string $end): void
    {
        $data = $this->report->getScheduledContacts($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalValue  = array_sum(array_column($data, 'total'));
        $urgentCount = count(array_filter($data, fn($r) => ($r['priority'] ?? '') === 'urgente'));

        $pdf = $this->createPdf('Agendamentos de Contato — Orçamento');

        // ── Resumo executivo ──
        $this->pdfSummaryBox($pdf, [
            'Período'              => $period,
            'Total de Agendamentos'=> count($data),
            'Urgentes'             => $urgentCount,
            'Valor em Orçamento'   => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        // ── Tabela ──
        $headers = ['#', 'Cliente', 'Telefone', 'Agendado', 'Prioridade', 'Valor (R$)'];
        $widths  = [12, 52, 32, 26, 26, 30];
        $aligns  = ['C', 'L', 'C', 'C', 'C', 'R'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $isUrgent = ($row['priority'] ?? '') === 'urgente';
            if ($isUrgent) {
                $pdf->SetTextColor(200, 50, 50);
            }

            $this->pdfTableRow($pdf, $widths, [
                $row['id'],
                mb_substr($row['customer_name'] ?? 'N/A', 0, 30),
                $row['customer_phone'] ?? '-',
                $row['scheduled_date_fmt'],
                ReportModel::getPriorityLabel($row['priority'] ?? 'normal'),
                number_format((float)$row['total'], 2, ',', '.'),
            ], $aligns, $fill);

            if ($isUrgent) {
                $pdf->SetTextColor(51, 51, 51);
            }

            $fill = !$fill;
        }

        // ── Totais ──
        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1] + $widths[2], 'text' => 'TOTAL (' . count($data) . ' agendamentos)', 'align' => 'R'],
            ['w' => $widths[3] + $widths[4], 'text' => '', 'align' => 'C'],
            ['w' => $widths[5], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'agendamentos_contato_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — AGENDAMENTOS DE CONTATO (ORÇAMENTO)
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelScheduledContacts(string $start, string $end): void
    {
        $data = $this->report->getScheduledContacts($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalValue  = array_sum(array_column($data, 'total'));
        $urgentCount = count(array_filter($data, fn($r) => ($r['priority'] ?? '') === 'urgente'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Agendamentos');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Agendamentos de Contato — Orçamento', $lastCol);

        // ── Resumo executivo ──
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'               => $period,
            'Total de Agendamentos' => count($data),
            'Urgentes'              => $urgentCount,
            'Valor em Orçamento'    => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        // ── Cabeçalhos ──
        $headers = ['#', 'Cliente', 'Telefone', 'Agendado', 'Prioridade', 'Valor (R$)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, $item['customer_name'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item['customer_phone'] ?? '-');
            $sheet->setCellValue('D' . $row, $item['scheduled_date_fmt']);
            $sheet->setCellValue('E' . $row, ReportModel::getPriorityLabel($item['priority'] ?? 'normal'));
            $sheet->setCellValue('F' . $row, (float)$item['total']);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);

            // Destaque vermelho para urgentes
            if (($item['priority'] ?? '') === 'urgente') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setARGB(self::CLR_DANGER);
            }
            $row++;
        }

        // ── Totais ──
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' agendamentos');
        $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'agendamentos_contato_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — CATÁLOGO DE PRODUTOS
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfProductCatalog(): void
    {
        $productId  = Input::get('product_id', 'int', null);
        $showVars   = Input::get('show_variations', 'string', '1') !== '0';

        $data = $this->report->getProductsCatalog($productId ?: null, $showVars);
        $products    = $data['products'];
        $priceTables = $data['price_tables'];

        $pdf = $this->createPdf('Catálogo de Produtos');

        // ── Resumo executivo ──
        $totalProducts = count($products);
        $totalVariations = 0;
        foreach ($products as $p) {
            $totalVariations += count($p['variations']);
        }
        $summaryItems = [
            'Gerado em'          => date('d/m/Y H:i'),
            'Total de Produtos'  => $totalProducts,
        ];
        if ($showVars) {
            $summaryItems['Total de Variações'] = $totalVariations;
        }
        if ($productId) {
            $summaryItems['Filtro'] = 'Produto #' . $productId;
        }
        $this->pdfSummaryBox($pdf, $summaryItems);

        // ── Para cada produto ──
        foreach ($products as $idx => $prod) {
            if ($idx > 0) {
                $pdf->Ln(4);
            }

            // Verificar se precisa de nova página
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            // ── Nome do produto com destaque ──
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetFillColor(241, 245, 249);
            $pdf->Cell(0, 7, $prod['name'], 0, 1, 'L', true);

            // ── Informações básicas ──
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->SetTextColor(100, 100, 100);

            $infoLine = 'SKU: ' . ($prod['sku'] ?: 'N/D');
            $infoLine .= '   |   Categoria: ' . ($prod['category_name'] ?: 'N/D');
            $infoLine .= '   |   Subcategoria: ' . ($prod['subcategory_name'] ?: 'N/D');
            $infoLine .= '   |   Preço Base: R$ ' . number_format((float)$prod['price'], 2, ',', '.');
            $pdf->Cell(0, 5, $infoLine, 0, 1, 'L');

            // Setores de produção
            if (!empty($prod['sectors'])) {
                $pdf->SetFont('helvetica', 'I', 7);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->Cell(0, 4, 'Setores: ' . implode(' > ', $prod['sectors']), 0, 1, 'L');
            }

            // Preços nas tabelas
            if (!empty($priceTables)) {
                $pdf->SetFont('helvetica', '', 7);
                $pdf->SetTextColor(100, 100, 100);
                $priceStr = 'Tabelas de Preço: ';
                $priceFragments = [];
                foreach ($priceTables as $pt) {
                    $ptPrice = $prod['table_prices'][$pt['id']] ?? null;
                    $priceFragments[] = $pt['name'] . ': R$ ' . ($ptPrice !== null ? number_format((float)$ptPrice, 2, ',', '.') : number_format((float)$prod['price'], 2, ',', '.') . '*');
                }
                $priceStr .= implode('  |  ', $priceFragments);
                $pdf->Cell(0, 4, $priceStr, 0, 1, 'L');
            }

            // ── Variações de grade ──
            if ($showVars && !empty($prod['variations'])) {
                $pdf->Ln(1);
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->SetTextColor(52, 152, 219);
                $pdf->Cell(0, 4, 'Variações da Grade (' . count($prod['variations']) . '):', 0, 1, 'L');

                $varHeaders = ['Combinação', 'SKU', 'Preço'];
                $varWidths  = [90, 50, 46];
                $this->pdfTableHeader($pdf, $varHeaders, $varWidths);

                $fill = false;
                foreach ($prod['variations'] as $var) {
                    $varPrice = $var['price_override'] !== null
                        ? 'R$ ' . number_format((float)$var['price_override'], 2, ',', '.')
                        : 'Padrão';
                    $this->pdfTableRow($pdf, $varWidths, [
                        $var['combination_label'],
                        $var['sku'] ?: '-',
                        $varPrice,
                    ], ['L', 'C', 'R'], $fill);
                    $fill = !$fill;
                }
            }

            // Separador entre produtos
            if ($idx < count($products) - 1) {
                $pdf->Ln(2);
                $y = $pdf->GetY();
                $pdf->SetDrawColor(220, 220, 220);
                $pdf->SetLineStyle(['dash' => '2,2']);
                $pdf->Line(12, $y, 198, $y);
                $pdf->SetLineStyle(['dash' => '0']);
                $pdf->SetDrawColor(222, 226, 230);
                $pdf->Ln(2);
            }
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'catalogo_produtos_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — ESTOQUE POR ARMAZÉM
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfStockByWarehouse(): void
    {
        $productId   = Input::get('product_id', 'int', null);
        $warehouseId = Input::get('warehouse_id', 'int', null);

        $data = $this->report->getStockByWarehouse($productId ?: null, $warehouseId ?: null);
        $items      = $data['items'];
        $warehouses = $data['warehouses'];

        $pdf = $this->createPdf('Estoque por Armazém');

        // ── Resumo executivo ──
        $totalQty   = array_sum(array_column($items, 'quantity'));
        $totalValue = 0;
        foreach ($items as $item) {
            $totalValue += (float)$item['quantity'] * (float)$item['product_price'];
        }
        $summaryItems = [
            'Gerado em'        => date('d/m/Y H:i'),
            'Itens no Estoque' => count($items),
            'Qtd Total'        => number_format($totalQty, 0, ',', '.'),
            'Valor Estimado'   => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ];
        $this->pdfSummaryBox($pdf, $summaryItems);

        // Agrupar por armazém
        $grouped = [];
        foreach ($items as $item) {
            $wName = $item['warehouse_name'];
            if (!isset($grouped[$wName])) {
                $grouped[$wName] = [];
            }
            $grouped[$wName][] = $item;
        }

        $headers = ['Produto', 'SKU', 'Variação', 'Categoria', 'Qtd', 'Local'];
        $widths  = [52, 28, 36, 28, 20, 22];
        $aligns  = ['L', 'C', 'L', 'C', 'R', 'C'];

        foreach ($grouped as $wName => $wItems) {
            // Verificar se precisa de nova página
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            // ── Cabeçalho do armazém ──
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetFillColor(232, 240, 254);
            $wQty = array_sum(array_column($wItems, 'quantity'));
            $pdf->Cell(0, 7, chr(0xE2) . chr(0x97) . chr(0x86) . '  ' . $wName . '  (' . count($wItems) . ' itens — Qtd: ' . number_format($wQty, 0, ',', '.') . ')', 0, 1, 'L', true);
            $pdf->SetTextColor(51, 51, 51);

            $this->pdfTableHeader($pdf, $headers, $widths);

            $fill = false;
            foreach ($wItems as $item) {
                $this->pdfTableRow($pdf, $widths, [
                    mb_substr($item['product_name'], 0, 30),
                    $item['product_sku'] ?: '-',
                    mb_substr($item['combination_label'] ?? '-', 0, 22),
                    mb_substr($item['category_name'] ?? '-', 0, 16),
                    number_format((float)$item['quantity'], 0, ',', '.'),
                    $item['location_code'] ?: '-',
                ], $aligns, $fill);
                $fill = !$fill;
            }
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'estoque_armazem_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — MOVIMENTAÇÕES DE ESTOQUE
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfStockMovements(string $start, string $end): void
    {
        $data = $this->report->getStockMovements($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $entryCount = count(array_filter($data, fn($r) => $r['type'] === 'entrada'));
        $exitCount  = count(array_filter($data, fn($r) => $r['type'] === 'saida'));

        $pdf = $this->createPdf('Movimentações de Estoque');

        // ── Resumo executivo ──
        $this->pdfSummaryBox($pdf, [
            'Período'       => $period,
            'Movimentações' => count($data),
            'Entradas'      => $entryCount,
            'Saídas'        => $exitCount,
        ]);

        // ── Tabela ──
        $headers = ['Data', 'Produto', 'Tipo', 'Qtd', 'Antes', 'Depois', 'Motivo', 'Usuário'];
        $widths  = [24, 34, 22, 14, 14, 14, 40, 24];
        $aligns  = ['C', 'L', 'C', 'R', 'R', 'R', 'L', 'L'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $prodLabel = mb_substr($row['product_name'], 0, 20);
            if (!empty($row['combination_label'])) {
                $prodLabel .= ' [' . mb_substr($row['combination_label'], 0, 10) . ']';
            }

            $typeColor = match($row['type']) {
                'entrada' => [39, 174, 96],
                'saida'   => [231, 76, 60],
                default   => [51, 51, 51],
            };
            $pdf->SetTextColor(...$typeColor);

            $this->pdfTableRow($pdf, $widths, [
                $row['created_at_fmt'],
                $prodLabel,
                ReportModel::getMovementTypeLabel($row['type']),
                number_format((float)$row['quantity'], 0, ',', '.'),
                number_format((float)$row['quantity_before'], 0, ',', '.'),
                number_format((float)$row['quantity_after'], 0, ',', '.'),
                mb_substr($row['reason'] ?? '-', 0, 25),
                mb_substr($row['user_name'], 0, 14),
            ], $aligns, $fill);

            $pdf->SetTextColor(51, 51, 51);
            $fill = !$fill;
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'movimentacoes_estoque_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — CATÁLOGO DE PRODUTOS
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelProductCatalog(): void
    {
        $productId  = Input::get('product_id', 'int', null);
        $showVars   = Input::get('show_variations', 'string', '1') !== '0';

        $data = $this->report->getProductsCatalog($productId ?: null, $showVars);
        $products    = $data['products'];
        $priceTables = $data['price_tables'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Catálogo de Produtos');

        // Colunas dinâmicas: base + tabelas de preço
        $baseCols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $baseHeaders = ['Produto', 'SKU', 'Categoria', 'Subcategoria', 'Preço Base (R$)', 'Setores'];
        $allCols = $baseCols;
        $allHeaders = $baseHeaders;

        // Adicionar colunas de tabelas de preço
        $colIdx = 6; // F = 5, next = 6
        $colLetters = range('A', 'Z');
        foreach ($priceTables as $pt) {
            $col = $colLetters[$colIdx] ?? ('A' . $colLetters[$colIdx - 26]);
            $allCols[] = $col;
            $allHeaders[] = $pt['name'] . ' (R$)';
            $colIdx++;
        }

        $lastCol = end($allCols);

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Catálogo de Produtos', $lastCol);

        // ── Resumo executivo ──
        $totalVariations = 0;
        foreach ($products as $p) {
            $totalVariations += count($p['variations']);
        }
        $summaryItems = [
            'Gerado em'         => date('d/m/Y H:i'),
            'Total de Produtos' => count($products),
        ];
        if ($showVars) {
            $summaryItems['Total de Variações'] = $totalVariations;
        }
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, $summaryItems);

        // ── Cabeçalhos da tabela ──
        foreach ($allHeaders as $i => $h) {
            $sheet->setCellValue($allCols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        $rowAlt = 0;
        foreach ($products as $prod) {
            $sheet->setCellValue('A' . $row, $prod['name']);
            $sheet->setCellValue('B' . $row, $prod['sku'] ?: '-');
            $sheet->setCellValue('C' . $row, $prod['category_name'] ?: '-');
            $sheet->setCellValue('D' . $row, $prod['subcategory_name'] ?: '-');
            $sheet->setCellValue('E' . $row, (float)$prod['price']);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('F' . $row, !empty($prod['sectors']) ? implode(' > ', $prod['sectors']) : '-');

            // Preços por tabela
            $ptIdx = 6;
            foreach ($priceTables as $pt) {
                $col = $allCols[$ptIdx] ?? 'A';
                $ptPrice = $prod['table_prices'][$pt['id']] ?? $prod['price'];
                $sheet->setCellValue($col . $row, (float)$ptPrice);
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $ptIdx++;
            }

            // Estilo do produto (fundo levemente destacado)
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $rowAlt % 2 === 1);
            $row++;

            // Variações
            if ($showVars && !empty($prod['variations'])) {
                foreach ($prod['variations'] as $var) {
                    $sheet->setCellValue('A' . $row, '   ↳ ' . $var['combination_label']);
                    $sheet->setCellValue('B' . $row, $var['sku'] ?: '-');
                    $varPrice = $var['price_override'] !== null ? (float)$var['price_override'] : (float)$prod['price'];
                    $sheet->setCellValue('E' . $row, $varPrice);
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setSize(8)->setItalic(true)
                        ->getColor()->setARGB(self::CLR_MUTED);
                    $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", false);
                    $row++;
                }
            }
            $rowAlt++;
        }

        $row += 1;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $allCols);
        $this->sendExcel($spreadsheet, 'catalogo_produtos_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — ESTOQUE POR ARMAZÉM
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelStockByWarehouse(): void
    {
        $productId   = Input::get('product_id', 'int', null);
        $warehouseId = Input::get('warehouse_id', 'int', null);

        $data = $this->report->getStockByWarehouse($productId ?: null, $warehouseId ?: null);
        $items = $data['items'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estoque por Armazém');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $lastCol = 'G';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Estoque por Armazém', $lastCol);

        // ── Resumo executivo ──
        $totalQty   = array_sum(array_column($items, 'quantity'));
        $totalValue = 0;
        foreach ($items as $item) {
            $totalValue += (float)$item['quantity'] * (float)$item['product_price'];
        }
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Gerado em'        => date('d/m/Y H:i'),
            'Itens no Estoque' => count($items),
            'Qtd Total'        => number_format($totalQty, 0, ',', '.'),
            'Valor Estimado'   => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        // ── Cabeçalhos ──
        $headers = ['Armazém', 'Produto', 'SKU', 'Variação', 'Categoria', 'Quantidade', 'Local'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        $dataStartRow = $row;
        foreach ($items as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['warehouse_name']);
            $sheet->setCellValue('B' . $row, $item['product_name']);
            $sheet->setCellValue('C' . $row, $item['product_sku'] ?: '-');
            $sheet->setCellValue('D' . $row, $item['combination_label'] ?? '-');
            $sheet->setCellValue('E' . $row, $item['category_name'] ?? '-');
            $sheet->setCellValue('F' . $row, (float)$item['quantity']);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->setCellValue('G' . $row, $item['location_code'] ?: '-');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);

            // Destaque vermelho para estoque mínimo
            if ((float)$item['min_quantity'] > 0 && (float)$item['quantity'] <= (float)$item['min_quantity']) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setARGB(self::CLR_DANGER);
            }
            $row++;
        }

        // ── Totais ──
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($items) . ' itens');
        $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'estoque_armazem_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — MOVIMENTAÇÕES DE ESTOQUE
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelStockMovements(string $start, string $end): void
    {
        $data = $this->report->getStockMovements($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $entryCount = count(array_filter($data, fn($r) => $r['type'] === 'entrada'));
        $exitCount  = count(array_filter($data, fn($r) => $r['type'] === 'saida'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movimentações');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $lastCol = 'I';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Movimentações de Estoque', $lastCol);

        // ── Resumo executivo ──
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'       => $period,
            'Movimentações' => count($data),
            'Entradas'      => $entryCount,
            'Saídas'        => $exitCount,
        ]);

        // ── Cabeçalhos ──
        $headers = ['Data', 'Produto', 'Variação', 'Armazém', 'Tipo', 'Qtd', 'Antes', 'Depois', 'Motivo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        // Adicionar coluna de Usuário
        $cols[] = 'J';
        $sheet->setCellValue('J' . $row, 'Usuário');
        $lastCol = 'J';
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        // ── Dados ──
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['created_at_fmt']);
            $sheet->setCellValue('B' . $row, $item['product_name']);
            $sheet->setCellValue('C' . $row, $item['combination_label'] ?? '-');
            $sheet->setCellValue('D' . $row, $item['warehouse_name']);
            $sheet->setCellValue('E' . $row, ReportModel::getMovementTypeLabel($item['type']));
            $sheet->setCellValue('F' . $row, (float)$item['quantity']);
            $sheet->setCellValue('G' . $row, (float)$item['quantity_before']);
            $sheet->setCellValue('H' . $row, (float)$item['quantity_after']);
            $sheet->setCellValue('I' . $row, $item['reason'] ?? '-');
            $sheet->setCellValue('J' . $row, $item['user_name']);

            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);

            // Cores por tipo
            $colorMap = [
                'entrada'       => self::CLR_SUCCESS,
                'saida'         => self::CLR_DANGER,
                'ajuste'        => self::CLR_ACCENT,
                'transferencia' => 'FF8E44AD',
            ];
            $typeColor = $colorMap[$item['type']] ?? self::CLR_PRIMARY;
            $sheet->getStyle('E' . $row)->getFont()->setBold(true)->getColor()->setARGB($typeColor);

            $row++;
        }

        $row += 1;

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'movimentacoes_estoque_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — COMISSÕES POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    private function exportPdfCommissionsReport(string $start, string $end, ?int $userId = null): void
    {
        $data = $this->report->getCommissionsByPeriod($start, $end, $userId);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totals = $data['totals'];

        $pdf = $this->createPdf('Relatório de Comissões');

        // ── Resumo executivo ──
        $summaryItems = [
            'Período'           => $period,
            'Total Registros'   => $totals['total_registros'],
            'Funcionários'      => $totals['total_funcionarios'],
            'Total Comissão'    => 'R$ ' . number_format($totals['total_comissao'], 2, ',', '.'),
        ];
        if ($userId) {
            $userName = !empty($data['by_user']) ? $data['by_user'][0]['user_name'] : 'Funcionário #' . $userId;
            $summaryItems = ['Funcionário' => $userName] + $summaryItems;
        }
        $this->pdfSummaryBox($pdf, $summaryItems);

        // ── Resumo por status ──
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(100, 100, 100);
        $statusLine = 'Calculada: R$ ' . number_format($totals['total_calculada'], 2, ',', '.')
                    . '   |   Aprovada: R$ ' . number_format($totals['total_aprovada'], 2, ',', '.')
                    . '   |   Paga: R$ ' . number_format($totals['total_paga'], 2, ',', '.');
        $pdf->Cell(0, 5, $statusLine, 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Ln(2);

        // ── Para cada funcionário ──
        foreach ($data['by_user'] as $uIdx => $userGroup) {
            if ($uIdx > 0) {
                $pdf->Ln(4);
            }

            // Verificar se precisa de nova página
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            // ── Nome do funcionário com destaque ──
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetFillColor(241, 245, 249);
            $pdf->Cell(0, 7, chr(226) . chr(150) . chr(186) . '  ' . $userGroup['user_name'] . ' (' . $userGroup['count'] . ' registros)', 0, 1, 'L', true);
            $pdf->SetTextColor(51, 51, 51);

            // ── Tabela do funcionário ──
            $headers = ['Pedido', 'Cliente', 'Forma', 'Tipo', 'Base (R$)', 'Comissão (R$)', 'Status', 'Data'];
            $widths  = [16, 36, 28, 20, 24, 24, 20, 22];
            $aligns  = ['C', 'L', 'L', 'C', 'R', 'R', 'C', 'C'];
            $this->pdfTableHeader($pdf, $headers, $widths);

            $fill = false;
            foreach ($userGroup['items'] as $row) {
                $this->pdfTableRow($pdf, $widths, [
                    '#' . $row['order_id'],
                    mb_substr($row['customer_name'] ?? 'N/A', 0, 22),
                    mb_substr($row['forma_nome'] ?? '-', 0, 18),
                    ucfirst($row['tipo_calculo'] ?? '-'),
                    number_format((float)$row['valor_base'], 2, ',', '.'),
                    number_format((float)$row['valor_comissao'], 2, ',', '.'),
                    ReportModel::getCommissionStatusLabel($row['status'] ?? ''),
                    $row['created_at_fmt'],
                ], $aligns, $fill);
                $fill = !$fill;
            }

            // ── Subtotal do funcionário ──
            $this->pdfTotalRow($pdf, [
                ['w' => $widths[0] + $widths[1] + $widths[2] + $widths[3], 'text' => 'Subtotal ' . $userGroup['user_name'], 'align' => 'R'],
                ['w' => $widths[4], 'text' => 'R$ ' . number_format($userGroup['total_valor_base'], 2, ',', '.'), 'align' => 'R'],
                ['w' => $widths[5], 'text' => 'R$ ' . number_format($userGroup['total_comissao'], 2, ',', '.'), 'align' => 'R'],
                ['w' => $widths[6] + $widths[7], 'text' => '', 'align' => 'C'],
            ]);
        }

        // ── Total geral (quando há múltiplos funcionários) ──
        if (count($data['by_user']) > 1) {
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(241, 245, 249);
            $pdf->SetDrawColor(52, 73, 94);
            $totalW = array_sum($widths);
            $splitA = $widths[0] + $widths[1] + $widths[2] + $widths[3];
            $splitB = $widths[4];
            $splitC = $widths[5];
            $splitD = $widths[6] + $widths[7];

            $y = $pdf->GetY();
            $pdf->SetLineWidth(0.5);
            $pdf->Line(12, $y, 12 + $totalW, $y);
            $pdf->SetLineWidth(0.2);

            $pdf->Cell($splitA, 9, 'TOTAL GERAL (' . $totals['total_funcionarios'] . ' funcionários)', 0, 0, 'R', true);
            $pdf->Cell($splitB, 9, 'R$ ' . number_format($totals['total_valor_base'], 2, ',', '.'), 0, 0, 'R', true);
            $pdf->SetTextColor(39, 174, 96);
            $pdf->Cell($splitC, 9, 'R$ ' . number_format($totals['total_comissao'], 2, ',', '.'), 0, 0, 'R', true);
            $pdf->SetTextColor(51, 51, 51);
            $pdf->Cell($splitD, 9, '', 0, 1, 'C', true);

            $pdf->SetDrawColor(222, 226, 230);
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'comissoes_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — COMISSÕES POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    private function exportExcelCommissionsReport(string $start, string $end, ?int $userId = null): void
    {
        $data = $this->report->getCommissionsByPeriod($start, $end, $userId);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totals = $data['totals'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comissões');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $lastCol = 'H';

        // ── Cabeçalho da empresa ──
        $row = $this->excelCompanyHeader($sheet, 'Relatório de Comissões', $lastCol);

        // ── Resumo executivo ──
        $summaryItems = [
            'Período'        => $period,
            'Registros'      => $totals['total_registros'],
            'Funcionários'   => $totals['total_funcionarios'],
            'Total Comissão' => 'R$ ' . number_format($totals['total_comissao'], 2, ',', '.'),
        ];
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, $summaryItems);

        // ── Para cada funcionário ──
        foreach ($data['by_user'] as $uIdx => $userGroup) {
            // Cabeçalho do funcionário
            $sheet->setCellValue('A' . $row, chr(0xE2) . chr(0x96) . chr(0xBA) . '  ' . $userGroup['user_name'] . ' (' . $userGroup['count'] . ' registros)');
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;

            // Cabeçalhos da tabela
            $headers = ['Pedido', 'Cliente', 'Forma', 'Tipo', 'Base (R$)', 'Comissão (R$)', 'Status', 'Data'];
            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i] . $row, $h);
            }
            $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
            $row++;

            // Dados
            $dataStartRow = $row;
            foreach ($userGroup['items'] as $i => $item) {
                $sheet->setCellValue('A' . $row, '#' . $item['order_id']);
                $sheet->setCellValue('B' . $row, $item['customer_name'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item['forma_nome'] ?? '-');
                $sheet->setCellValue('D' . $row, ucfirst($item['tipo_calculo'] ?? '-'));
                $sheet->setCellValue('E' . $row, (float)$item['valor_base']);
                $sheet->setCellValue('F' . $row, (float)$item['valor_comissao']);
                $sheet->setCellValue('G' . $row, ReportModel::getCommissionStatusLabel($item['status'] ?? ''));
                $sheet->setCellValue('H' . $row, $item['created_at_fmt']);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
                $row++;
            }

            // Subtotal do funcionário
            $sheet->setCellValue('A' . $row, 'Subtotal');
            $sheet->setCellValue('B' . $row, $userGroup['user_name']);
            $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
            $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
            $row += 2;
        }

        // ── Total Geral ──
        if (count($data['by_user']) > 1) {
            $sheet->setCellValue('A' . $row, 'TOTAL GERAL');
            $sheet->setCellValue('B' . $row, $totals['total_funcionarios'] . ' funcionários  |  ' . $totals['total_registros'] . ' registros');
            $sheet->setCellValue('E' . $row, (float)$totals['total_valor_base']);
            $sheet->setCellValue('F' . $row, (float)$totals['total_comissao']);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true)->setSize(11)
                ->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getRowDimension($row)->setRowHeight(24);
            $row += 2;
        }

        // ── Rodapé ──
        $this->excelFooter($sheet, $row, $lastCol);

        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'comissoes_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════

    /**
     * Cria instância TCPDF com cabeçalho profissional minimalista.
     * Inclui logo (se disponível), nome da empresa e título do relatório.
     */
    private function createPdf(string $title): TCPDF
    {
        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';
        $logoPath    = $this->company['company_logo'] ?? '';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Akti — Gestão em Produção');
        $pdf->SetAuthor($companyName);
        $pdf->SetTitle($title);

        // Desabilitar cabeçalho/rodapé padrão do TCPDF — usaremos os nossos
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // ── Cabeçalho personalizado ──
        $startY = $pdf->GetY();

        // Logo (se existir)
        $logoX = 12;
        $logoW = 0;
        if (!empty($logoPath) && file_exists($logoPath)) {
            $pdf->Image($logoPath, $logoX, $startY, 18, 18, '', '', '', true, 300, '', false, false, 0);
            $logoW = 22; // largura da logo + margem
        }

        // Nome da empresa
        $pdf->SetXY($logoX + $logoW, $startY);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 7, $companyName, 0, 1, 'L');

        // Título do relatório
        $pdf->SetX($logoX + $logoW);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(149, 165, 166);
        $pdf->Cell(0, 5, $title, 0, 1, 'L');

        // Linha divisória elegante
        $lineY = max($pdf->GetY(), $startY + 18) + 2;
        $pdf->SetY($lineY);
        $pdf->SetDrawColor(52, 152, 219);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(12, $lineY, 198, $lineY);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->Ln(4);

        // Resetar cor de texto padrão
        $pdf->SetTextColor(51, 51, 51);

        return $pdf;
    }

    /**
     * Desenha uma caixa de resumo executivo com métricas-chave.
     * Layout em grid 2×2 com fundo suave e ícones visuais.
     */
    private function pdfSummaryBox(TCPDF $pdf, array $metrics): void
    {
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(222, 226, 230);

        $boxX = 12;
        $boxW = 186; // A4 - margens
        $boxH = 16;

        $startY = $pdf->GetY();

        // Fundo da caixa com borda sutil
        $pdf->RoundedRect($boxX, $startY, $boxW, $boxH, 2, '1111', 'DF');

        // Dividir métricas em colunas
        $keys   = array_keys($metrics);
        $values = array_values($metrics);
        $count  = count($metrics);
        $colW   = $boxW / $count;

        for ($i = 0; $i < $count; $i++) {
            $colX = $boxX + ($i * $colW);

            // Label
            $pdf->SetXY($colX, $startY + 2);
            $pdf->SetFont('helvetica', '', 6.5);
            $pdf->SetTextColor(149, 165, 166);
            $pdf->Cell($colW, 4, strtoupper($keys[$i]), 0, 0, 'C');

            // Valor
            $pdf->SetXY($colX, $startY + 7);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->Cell($colW, 6, (string)$values[$i], 0, 0, 'C');

            // Separador vertical (exceto último)
            if ($i < $count - 1) {
                $sepX = $colX + $colW;
                $pdf->SetDrawColor(210, 215, 220);
                $pdf->Line($sepX, $startY + 3, $sepX, $startY + $boxH - 3);
            }
        }

        $pdf->SetY($startY + $boxH + 4);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetDrawColor(222, 226, 230);
    }

    /**
     * Desenha cabeçalho de tabela minimalista no PDF.
     * Fundo escuro com texto branco e bordas sutis.
     */
    private function pdfTableHeader(TCPDF $pdf, array $headers, array $widths): void
    {
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(44, 62, 80);

        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 7, $h, 0, 0, 'C', true);
        }
        $pdf->Ln();

        // Linha fina abaixo do cabeçalho
        $pdf->SetDrawColor(52, 152, 219);
        $pdf->SetLineWidth(0.3);
        $y = $pdf->GetY();
        $pdf->Line(12, $y, 12 + array_sum($widths), $y);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->SetTextColor(51, 51, 51);
    }

    /**
     * Desenha uma linha de dados na tabela do PDF com zebra striping sutil.
     */
    private function pdfTableRow(TCPDF $pdf, array $widths, array $values, array $aligns, bool $alternate): void
    {
        $rowH = 6;

        if ($alternate) {
            $pdf->SetFillColor(248, 249, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        $pdf->SetFont('helvetica', '', 7.5);

        foreach ($values as $i => $val) {
            $align = $aligns[$i] ?? 'C';
            $isLast = ($i === count($values) - 1);
            $pdf->Cell($widths[$i], $rowH, (string)$val, 0, $isLast ? 1 : 0, $align, true);
        }

        // Linha divisória fina e sutil
        $y = $pdf->GetY();
        $pdf->SetDrawColor(238, 238, 238);
        $pdf->Line(12, $y, 12 + array_sum($widths), $y);
        $pdf->SetDrawColor(222, 226, 230);
    }

    /**
     * Desenha linha de totais com destaque visual.
     * @param array $cells Array de ['w' => largura, 'text' => texto, 'align' => alinhamento]
     */
    private function pdfTotalRow(TCPDF $pdf, array $cells): void
    {
        $pdf->Ln(0.5);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(52, 152, 219);

        // Linha superior de destaque
        $totalW = array_sum(array_column($cells, 'w'));
        $y = $pdf->GetY();
        $pdf->SetLineWidth(0.4);
        $pdf->Line(12, $y, 12 + $totalW, $y);
        $pdf->SetLineWidth(0.2);

        foreach ($cells as $i => $cell) {
            $isLast = ($i === count($cells) - 1);
            $pdf->Cell($cell['w'], 8, $cell['text'], 0, $isLast ? 1 : 0, $cell['align'], true);
        }

        $pdf->SetDrawColor(222, 226, 230);
    }

    /**
     * Desenha rodapé profissional no PDF com data de emissão e usuário responsável.
     */
    private function pdfFooter(TCPDF $pdf): void
    {
        $pdf->Ln(10);

        // Linha divisória
        $y = $pdf->GetY();
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->Line(12, $y, 198, $y);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(149, 165, 166);

        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';
        $dateTime    = date('d/m/Y \à\s H:i:s');

        // Linha 1: Empresa + Sistema
        $pdf->Cell(0, 4, $companyName . '  |  Akti — Gestão em Produção', 0, 1, 'C');

        // Linha 2: Data de emissão + Responsável
        $pdf->Cell(0, 4, 'Emitido em ' . $dateTime . '  |  Responsável: ' . $this->responsibleUser, 0, 1, 'C');

        $pdf->SetTextColor(51, 51, 51);
    }

    /**
     * Envia o PDF diretamente para download.
     */
    private function sendPdf(TCPDF $pdf, string $filename): void
    {
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS — EXCEL (Design Profissional)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Cria o cabeçalho da empresa no Excel (nome, título, data).
     * Retorna a próxima linha disponível.
     */
    private function excelCompanyHeader($sheet, string $title, string $lastCol): int
    {
        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';

        // Linha 1: Nome da empresa (grande, escuro)
        $sheet->setCellValue('A1', $companyName);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Linha 2: Título do relatório (accent color)
        $sheet->setCellValue('A2', $title);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(12)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_ACCENT);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Linha 3: Linha separadora (borda inferior)
        $sheet->getStyle("A3:{$lastCol}3")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::CLR_ACCENT);
        $sheet->getRowDimension(3)->setRowHeight(4);

        return 5; // Próxima linha disponível
    }

    /**
     * Cria bloco de resumo executivo no Excel.
     * Retorna a próxima linha disponível.
     */
    private function excelSummaryBlock($sheet, int $startRow, string $lastCol, array $metrics): int
    {
        $keys   = array_keys($metrics);
        $values = array_values($metrics);
        $count  = count($metrics);

        // Fundo do bloco de resumo
        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($startRow + 1))->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);

        // Labels (linha superior)
        $cols = range('A', 'Z');
        for ($i = 0; $i < $count; $i++) {
            $col = $cols[$i];
            $sheet->setCellValue($col . $startRow, strtoupper($keys[$i]));
            $sheet->getStyle($col . $startRow)->getFont()->setSize(7)->setBold(true)->setName('Calibri')
                ->getColor()->setARGB(self::CLR_MUTED);
            $sheet->getStyle($col . $startRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Valores (linha inferior)
        $valueRow = $startRow + 1;
        for ($i = 0; $i < $count; $i++) {
            $col = $cols[$i];
            $sheet->setCellValue($col . $valueRow, (string)$values[$i]);
            $sheet->getStyle($col . $valueRow)->getFont()->setSize(11)->setBold(true)->setName('Calibri')
                ->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getStyle($col . $valueRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getRowDimension($startRow)->setRowHeight(16);
        $sheet->getRowDimension($valueRow)->setRowHeight(22);

        // Borda inferior do bloco
        $sheet->getStyle("A{$valueRow}:{$lastCol}{$valueRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::CLR_BORDER);

        return $valueRow + 2; // Pula uma linha
    }

    /**
     * Aplica estilo de cabeçalho de tabela profissional no Excel.
     * Fundo escuro, texto branco, sem bordas grossas.
     */
    private function styleExcelHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(9)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_WHITE);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($range)->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::CLR_ACCENT);

        // Extrair número da linha para definir altura
        preg_match('/(\d+)/', $range, $m);
        if (!empty($m[1])) {
            $sheet->getRowDimension((int)$m[1])->setRowHeight(22);
        }
    }

    /**
     * Aplica estilo de linha de dados com zebra striping sutil.
     */
    private function styleExcelDataRow($sheet, string $range, bool $alternate): void
    {
        if ($alternate) {
            $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_ROW_ALT);
        }

        $sheet->getStyle($range)->getFont()->setSize(9)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle($range)->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB(self::CLR_BORDER);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Altura da linha
        preg_match('/(\d+)/', $range, $m);
        if (!empty($m[1])) {
            $sheet->getRowDimension((int)$m[1])->setRowHeight(18);
        }
    }

    /**
     * Aplica estilo de linha de total no Excel.
     */
    private function styleExcelTotalRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(9)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);
        $sheet->getStyle($range)->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle($range)->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        preg_match('/(\d+)/', $range, $m);
        if (!empty($m[1])) {
            $sheet->getRowDimension((int)$m[1])->setRowHeight(22);
        }
    }

    /**
     * Adiciona rodapé profissional no Excel com data e usuário responsável.
     */
    private function excelFooter($sheet, int $row, string $lastCol): void
    {
        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';
        $dateTime    = date('d/m/Y \à\s H:i:s');

        // Borda superior do rodapé
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::CLR_BORDER);

        // Linha 1: Empresa + Sistema
        $sheet->setCellValue('A' . $row, $companyName . '  |  Akti — Gestão em Produção');
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle('A' . $row)->getFont()->setSize(8)->setItalic(true)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_MUTED);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Linha 2: Data de emissão + Responsável
        $sheet->setCellValue('A' . $row, 'Emitido em ' . $dateTime . '  |  Responsável: ' . $this->responsibleUser);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle('A' . $row)->getFont()->setSize(8)->setItalic(true)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_MUTED);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Auto-ajusta largura das colunas com padding mínimo.
     */
    private function autoSizeColumns($sheet, array $cols): void
    {
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Envia o XLSX diretamente para download.
     */
    private function sendExcel(Spreadsheet $spreadsheet, string $filename): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
