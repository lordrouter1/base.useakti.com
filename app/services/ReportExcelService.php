<?php
namespace Akti\Services;

use Akti\Models\ReportModel;
use Akti\Models\NfeReportModel;
use Akti\Utils\Input;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Service: ReportExcelService
 * Responsável por toda a geração de relatórios em Excel (XLSX).
 * Encapsula helpers PhpSpreadsheet e cada tipo de relatório exportável.
 */
class ReportExcelService
{
    private $report;
    private $nfeReport;
    private $company;
    private string $responsibleUser;

    // ── Paleta de cores (ARGB hex) ──
    private const CLR_PRIMARY     = 'FF2C3E50';
    private const CLR_PRIMARY_LT  = 'FFECF0F1';
    private const CLR_ACCENT      = 'FF3498DB';
    private const CLR_SUCCESS     = 'FF27AE60';
    private const CLR_SUCCESS_LT  = 'FFF0FAF4';
    private const CLR_DANGER      = 'FFE74C3C';
    private const CLR_DANGER_LT   = 'FFFDF0EF';
    private const CLR_WHITE       = 'FFFFFFFF';
    private const CLR_MUTED       = 'FF95A5A6';
    private const CLR_BORDER      = 'FFDEE2E6';
    private const CLR_ROW_ALT     = 'FFF8F9FA';
    private const CLR_SUMMARY_BG  = 'FFF1F5F9';

    public function __construct(ReportModel $report, NfeReportModel $nfeReport, array $company, string $responsibleUser)
    {
        $this->report          = $report;
        $this->nfeReport       = $nfeReport;
        $this->company         = $company;
        $this->responsibleUser = $responsibleUser;
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — PEDIDOS POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    public function exportOrdersByPeriod(string $start, string $end): void
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

        $row = $this->excelCompanyHeader($sheet, 'Relatório de Pedidos por Período', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'          => $period,
            'Total de Pedidos' => count($data),
            'Valor Total'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
            'Pedidos Pagos'    => $totalPaid,
        ]);

        $headers = ['#', 'Cliente', 'Total (R$)', 'Status Pgto', 'Etapa', 'Data'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

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

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' pedidos');
        $sheet->setCellValue('C' . $row, '=SUM(C' . $dataStartRow . ':C' . ($row - 1) . ')');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'pedidos_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — FATURAMENTO POR CLIENTE
    // ═══════════════════════════════════════════════════════════════

    public function exportRevenueByCustomer(string $start, string $end): void
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

        $row = $this->excelCompanyHeader($sheet, 'Faturamento por Cliente', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'           => $period,
            'Clientes Ativos'   => count($data),
            'Total de Pedidos'  => $totalOrders,
            'Faturamento Total' => 'R$ ' . number_format($totalRevenue, 2, ',', '.'),
        ]);

        $headers = ['Cliente', 'Qtd Pedidos', 'Faturamento (R$)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['customer_name'] ?? 'Sem cliente');
            $sheet->setCellValue('B' . $row, (int)$item['total_orders']);
            $sheet->setCellValue('C' . $row, (float)$item['total_revenue']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, '=SUM(B' . $dataStartRow . ':B' . ($row - 1) . ')');
        $sheet->setCellValue('C' . $row, '=SUM(C' . $dataStartRow . ':C' . ($row - 1) . ')');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'faturamento_cliente_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — DRE
    // ═══════════════════════════════════════════════════════════════

    public function exportIncomeStatement(string $start, string $end): void
    {
        $data = $this->report->getIncomeStatement($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $net = $data['totals']['net_balance'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DRE');

        $cols = ['A', 'B'];
        $lastCol = 'B';

        $row = $this->excelCompanyHeader($sheet, 'Demonstrativo de Resultado (DRE)', $lastCol);
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

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'dre_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — PARCELAS PENDENTES
    // ═══════════════════════════════════════════════════════════════

    public function exportOpenInstallments(): void
    {
        $data = $this->report->getOpenInstallments();

        $totalValue   = array_sum(array_column($data, 'amount'));
        $overdueCount = count(array_filter($data, fn($r) => (int)$r['days_overdue'] > 0));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Parcelas Pendentes');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        $row = $this->excelCompanyHeader($sheet, 'Parcelas Pendentes / Atrasadas', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Gerado em'          => date('d/m/Y H:i'),
            'Total de Parcelas'  => count($data),
            'Parcelas Atrasadas' => $overdueCount,
            'Valor Pendente'     => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        $headers = ['Pedido', 'Cliente', 'Parcela', 'Valor (R$)', 'Vencimento', 'Atraso (dias)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

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

            if ((int)$item['days_overdue'] > 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setARGB(self::CLR_DANGER);
            }
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' parcelas');
        $sheet->setCellValue('D' . $row, '=SUM(D' . $dataStartRow . ':D' . ($row - 1) . ')');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'parcelas_pendentes_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — AGENDAMENTOS DE CONTATO
    // ═══════════════════════════════════════════════════════════════

    public function exportScheduledContacts(string $start, string $end): void
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

        $row = $this->excelCompanyHeader($sheet, 'Agendamentos de Contato — Orçamento', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'               => $period,
            'Total de Agendamentos' => count($data),
            'Urgentes'              => $urgentCount,
            'Valor em Orçamento'    => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        $headers = ['#', 'Cliente', 'Telefone', 'Agendado', 'Prioridade', 'Valor (R$)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

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

            if (($item['priority'] ?? '') === 'urgente') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setARGB(self::CLR_DANGER);
            }
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' agendamentos');
        $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'agendamentos_contato_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — CATÁLOGO DE PRODUTOS
    // ═══════════════════════════════════════════════════════════════

    public function exportProductCatalog(): void
    {
        $productId  = Input::get('product_id', 'int', null);
        $showVars   = Input::get('show_variations', 'string', '1') !== '0';

        $data = $this->report->getProductsCatalog($productId ?: null, $showVars);
        $products    = $data['products'];
        $priceTables = $data['price_tables'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Catálogo de Produtos');

        $baseCols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $baseHeaders = ['Produto', 'SKU', 'Categoria', 'Subcategoria', 'Preço Base (R$)', 'Setores'];
        $allCols = $baseCols;
        $allHeaders = $baseHeaders;

        $colIdx = 6;
        $colLetters = range('A', 'Z');
        foreach ($priceTables as $pt) {
            $col = $colLetters[$colIdx] ?? ('A' . $colLetters[$colIdx - 26]);
            $allCols[] = $col;
            $allHeaders[] = $pt['name'] . ' (R$)';
            $colIdx++;
        }

        $lastCol = end($allCols);

        $row = $this->excelCompanyHeader($sheet, 'Catálogo de Produtos', $lastCol);

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

        foreach ($allHeaders as $i => $h) {
            $sheet->setCellValue($allCols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        $rowAlt = 0;
        foreach ($products as $prod) {
            $sheet->setCellValue('A' . $row, $prod['name']);
            $sheet->setCellValue('B' . $row, $prod['sku'] ?: '-');
            $sheet->setCellValue('C' . $row, $prod['category_name'] ?: '-');
            $sheet->setCellValue('D' . $row, $prod['subcategory_name'] ?: '-');
            $sheet->setCellValue('E' . $row, (float)$prod['price']);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('F' . $row, !empty($prod['sectors']) ? implode(' > ', $prod['sectors']) : '-');

            $ptIdx = 6;
            foreach ($priceTables as $pt) {
                $col = $allCols[$ptIdx] ?? 'A';
                $ptPrice = $prod['table_prices'][$pt['id']] ?? $prod['price'];
                $sheet->setCellValue($col . $row, (float)$ptPrice);
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $ptIdx++;
            }

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $rowAlt % 2 === 1);
            $row++;

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
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $allCols);
        $this->sendExcel($spreadsheet, 'catalogo_produtos_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — ESTOQUE POR ARMAZÉM
    // ═══════════════════════════════════════════════════════════════

    public function exportStockByWarehouse(): void
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

        $row = $this->excelCompanyHeader($sheet, 'Estoque por Armazém', $lastCol);

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

        $headers = ['Armazém', 'Produto', 'SKU', 'Variação', 'Categoria', 'Quantidade', 'Local'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

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

            if ((float)$item['min_quantity'] > 0 && (float)$item['quantity'] <= (float)$item['min_quantity']) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setARGB(self::CLR_DANGER);
            }
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($items) . ' itens');
        $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'estoque_armazem_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — MOVIMENTAÇÕES DE ESTOQUE
    // ═══════════════════════════════════════════════════════════════

    public function exportStockMovements(string $start, string $end): void
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

        $row = $this->excelCompanyHeader($sheet, 'Movimentações de Estoque', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'       => $period,
            'Movimentações' => count($data),
            'Entradas'      => $entryCount,
            'Saídas'        => $exitCount,
        ]);

        $headers = ['Data', 'Produto', 'Variação', 'Armazém', 'Tipo', 'Qtd', 'Antes', 'Depois', 'Motivo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $cols[] = 'J';
        $sheet->setCellValue('J' . $row, 'Usuário');
        $lastCol = 'J';
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

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
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'movimentacoes_estoque_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — COMISSÕES POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    public function exportCommissionsReport(string $start, string $end, ?int $userId = null): void
    {
        $data = $this->report->getCommissionsByPeriod($start, $end, $userId);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totals = $data['totals'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comissões');

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $lastCol = 'H';

        $row = $this->excelCompanyHeader($sheet, 'Relatório de Comissões', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'        => $period,
            'Registros'      => $totals['total_registros'],
            'Funcionários'   => $totals['total_funcionarios'],
            'Total Comissão' => 'R$ ' . number_format($totals['total_comissao'], 2, ',', '.'),
        ]);

        foreach ($data['by_user'] as $uIdx => $userGroup) {
            $sheet->setCellValue('A' . $row, chr(0xE2) . chr(0x96) . chr(0xBA) . '  ' . $userGroup['user_name'] . ' (' . $userGroup['count'] . ' registros)');
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;

            $headers = ['Pedido', 'Cliente', 'Forma', 'Tipo', 'Base (R$)', 'Comissão (R$)', 'Status', 'Data'];
            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i] . $row, $h);
            }
            $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
            $row++;

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

            $sheet->setCellValue('A' . $row, 'Subtotal');
            $sheet->setCellValue('B' . $row, $userGroup['user_name']);
            $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
            $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
            $row += 2;
        }

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
                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB(self::CLR_PRIMARY);
            $sheet->getRowDimension($row)->setRowHeight(24);
            $row += 2;
        }

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'comissoes_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — NF-e POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    public function exportNfesByPeriod(string $start, string $end): void
    {
        $statusFilter = Input::get('nfe_status', 'string', '');
        $modeloFilter = Input::get('nfe_modelo', 'string', '');
        $filters = [];
        if ($statusFilter) $filters['status'] = $statusFilter;
        if ($modeloFilter) $filters['modelo'] = $modeloFilter;

        $data = $this->nfeReport->getNfesByPeriod($start, $end, $filters);
        $kpis = $this->nfeReport->getFiscalKpis($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NF-e por Período');
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $lastCol = 'G';

        $row = $this->excelCompanyHeader($sheet, 'Relatório de NF-e por Período', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'     => $period,
            'Total NF-e'  => (int) $kpis['total_emitidas'],
            'Autorizadas' => (int) $kpis['autorizadas'],
            'Canceladas'  => (int) $kpis['canceladas'],
            'Valor'       => 'R$ ' . number_format((float) $kpis['valor_autorizado'], 2, ',', '.'),
        ]);

        $headers = ['#', 'Modelo', 'Número/Série', 'Destinatário', 'Valor (R$)', 'Status', 'Data Emissão'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, NfeReportModel::getModeloLabel((int) $item['modelo']));
            $sheet->setCellValue('C' . $row, $item['numero'] . '/' . $item['serie']);
            $sheet->setCellValue('D' . $row, $item['dest_nome'] ?? 'Consumidor');
            $sheet->setCellValue('E' . $row, (float) $item['valor_total']);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('F' . $row, NfeReportModel::getNfeStatusLabel($item['status']));
            $sheet->setCellValue('G' . $row, $item['emitted_at_fmt'] ?: $item['created_at_fmt']);
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('D' . $row, count($data) . ' documentos');
        $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'nfe_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — RESUMO DE IMPOSTOS
    // ═══════════════════════════════════════════════════════════════

    public function exportTaxSummary(string $start, string $end): void
    {
        $data = $this->nfeReport->getTaxSummary($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totals = $data['totals'];
        $totalTributos = (float) $totals['total_icms'] + (float) $totals['total_pis']
                       + (float) $totals['total_cofins'] + (float) $totals['total_ipi'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumo de Impostos');
        $cols = ['A', 'B', 'C'];
        $lastCol = 'C';

        $row = $this->excelCompanyHeader($sheet, 'Resumo de Impostos por Período', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'    => $period,
            'Total NF-e' => (int) $totals['total_nfes'],
            'Tributos'   => 'R$ ' . number_format($totalTributos, 2, ',', '.'),
        ]);

        $headers = ['Imposto', 'Valor Total (R$)', '% do Total'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        $taxes = [
            ['ICMS', (float) $totals['total_icms']],
            ['PIS', (float) $totals['total_pis']],
            ['COFINS', (float) $totals['total_cofins']],
            ['IPI', (float) $totals['total_ipi']],
        ];
        foreach ($taxes as $i => $tax) {
            $pct = $totalTributos > 0 ? ($tax[1] / $totalTributos * 100) : 0;
            $sheet->setCellValue('A' . $row, $tax[0]);
            $sheet->setCellValue('B' . $row, $tax[1]);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('C' . $row, number_format($pct, 1) . '%');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $totalTributos);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('C' . $row, '100%');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;

        if (!empty($data['items'])) {
            $detSheet = $spreadsheet->createSheet();
            $detSheet->setTitle('Detalhamento NCM-CFOP');
            $detCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
            $detLastCol = 'G';

            $dRow = $this->excelCompanyHeader($detSheet, 'Detalhamento por NCM / CFOP', $detLastCol);

            $detHeaders = ['NCM', 'CFOP', 'Qtd Itens', 'Valor (R$)', 'ICMS (R$)', 'PIS (R$)', 'COFINS (R$)'];
            foreach ($detHeaders as $i => $h) {
                $detSheet->setCellValue($detCols[$i] . $dRow, $h);
            }
            $this->styleExcelHeader($detSheet, "A{$dRow}:{$detLastCol}{$dRow}");
            $dRow++;

            foreach ($data['items'] as $i => $item) {
                $detSheet->setCellValue('A' . $dRow, $item['ncm'] ?? '-');
                $detSheet->setCellValue('B' . $dRow, $item['cfop'] ?? '-');
                $detSheet->setCellValue('C' . $dRow, (int) $item['qtd_itens']);
                $detSheet->setCellValue('D' . $dRow, (float) $item['valor_total']);
                $detSheet->setCellValue('E' . $dRow, (float) $item['icms']);
                $detSheet->setCellValue('F' . $dRow, (float) $item['pis']);
                $detSheet->setCellValue('G' . $dRow, (float) $item['cofins']);
                foreach (['D', 'E', 'F', 'G'] as $c) {
                    $detSheet->getStyle($c . $dRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $this->styleExcelDataRow($detSheet, "A{$dRow}:{$detLastCol}{$dRow}", $i % 2 === 1);
                $dRow++;
            }
            $this->autoSizeColumns($detSheet, $detCols);
        }

        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $spreadsheet->setActiveSheetIndex(0);
        $this->sendExcel($spreadsheet, 'impostos_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — NF-e POR CLIENTE
    // ═══════════════════════════════════════════════════════════════

    public function exportNfesByCustomer(string $start, string $end): void
    {
        $customerId = Input::get('customer_id', 'int', null);
        $data = $this->nfeReport->getNfesByCustomer($start, $end, $customerId ?: null);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $totalNfes = array_sum(array_column($data, 'total_nfes'));
        $totalValue = array_sum(array_column($data, 'valor_total'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NF-e por Cliente');
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        $row = $this->excelCompanyHeader($sheet, 'NF-e por Cliente', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'    => $period,
            'Clientes'   => count($data),
            'Total NF-e' => $totalNfes,
            'Valor'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        $headers = ['Cliente', 'CPF/CNPJ', 'Qtd NF-e', 'Valor Total (R$)', 'Primeira', 'Última'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['customer_name'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $item['dest_cnpj_cpf'] ?? '-');
            $sheet->setCellValue('C' . $row, (int) $item['total_nfes']);
            $sheet->setCellValue('D' . $row, (float) $item['valor_total']);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('E' . $row, $item['primeira_emissao'] ?? '-');
            $sheet->setCellValue('F' . $row, $item['ultima_emissao'] ?? '-');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, count($data) . ' clientes');
        $sheet->setCellValue('C' . $row, '=SUM(C' . $dataStartRow . ':C' . ($row - 1) . ')');
        $sheet->setCellValue('D' . $row, '=SUM(D' . $dataStartRow . ':D' . ($row - 1) . ')');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'nfe_cliente_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — RESUMO CFOP
    // ═══════════════════════════════════════════════════════════════

    public function exportCfopSummary(string $start, string $end): void
    {
        $data = $this->nfeReport->getCfopSummary($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumo CFOP');
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        $row = $this->excelCompanyHeader($sheet, 'Resumo por CFOP', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'         => $period,
            'CFOPs utilizados'=> count($data),
            'Valor Total'     => 'R$ ' . number_format(array_sum(array_column($data, 'valor_total')), 2, ',', '.'),
        ]);

        $headers = ['CFOP', 'Descrição', 'NF-e', 'Itens', 'Valor (R$)', 'ICMS (R$)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        $dataStartRow = $row;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['cfop']);
            $sheet->setCellValue('B' . $row, NfeReportModel::getCfopDescription($item['cfop']));
            $sheet->setCellValue('C' . $row, (int) $item['qtd_nfes']);
            $sheet->setCellValue('D' . $row, (int) $item['qtd_itens']);
            $sheet->setCellValue('E' . $row, (float) $item['valor_total']);
            $sheet->setCellValue('F' . $row, (float) $item['icms_total']);
            foreach (['E', 'F'] as $c) {
                $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
        $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
        foreach (['E', 'F'] as $c) {
            $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $this->styleExcelTotalRow($sheet, "A{$row}:{$lastCol}{$row}");
        $row += 2;
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'cfop_resumo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — NF-e CANCELADAS
    // ═══════════════════════════════════════════════════════════════

    public function exportCancelledNfes(string $start, string $end): void
    {
        $data = $this->nfeReport->getCancelledNfes($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NF-e Canceladas');
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        $row = $this->excelCompanyHeader($sheet, 'NF-e Canceladas', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'    => $period,
            'Canceladas' => count($data),
            'Valor Total'=> 'R$ ' . number_format(array_sum(array_column($data, 'valor_total')), 2, ',', '.'),
        ]);

        $headers = ['Número/Série', 'Destinatário', 'Valor (R$)', 'Data Emissão', 'Data Cancelamento', 'Motivo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['numero'] . '/' . $item['serie']);
            $sheet->setCellValue('B' . $row, $item['dest_nome'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, (float) $item['valor_total']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('D' . $row, $item['emitted_at_fmt'] ?? '-');
            $sheet->setCellValue('E' . $row, $item['cancel_date_fmt'] ?? '-');
            $sheet->setCellValue('F' . $row, $item['cancel_motivo'] ?? '-');
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $row += 1;
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'nfe_canceladas_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — INUTILIZAÇÕES
    // ═══════════════════════════════════════════════════════════════

    public function exportInutilizacoes(string $start, string $end): void
    {
        $data = $this->nfeReport->getInutilizacoes($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inutilizações');
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        $row = $this->excelCompanyHeader($sheet, 'Numerações Inutilizadas', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'       => $period,
            'Inutilizações' => count($data),
        ]);

        $headers = ['#', 'Número', 'Série', 'Modelo', 'Justificativa', 'Data'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, $item['numero']);
            $sheet->setCellValue('C' . $row, $item['serie']);
            $sheet->setCellValue('D' . $row, NfeReportModel::getModeloLabel((int) $item['modelo']));
            $sheet->setCellValue('E' . $row, $item['justificativa'] ?? '-');
            $sheet->setCellValue('F' . $row, $item['created_at_fmt']);
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $row += 1;
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'inutilizacoes_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // EXCEL — LOGS SEFAZ
    // ═══════════════════════════════════════════════════════════════

    public function exportSefazLogs(string $start, string $end): void
    {
        $actionFilter = Input::get('log_action', 'string', '');
        $data = $this->nfeReport->getSefazLogs($start, $end, $actionFilter ?: null);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Logs SEFAZ');
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $lastCol = 'G';

        $row = $this->excelCompanyHeader($sheet, 'Log de Comunicação SEFAZ', $lastCol);
        $row = $this->excelSummaryBlock($sheet, $row, $lastCol, [
            'Período'         => $period,
            'Total Registros' => count($data),
        ]);

        $headers = ['NF-e', 'Ação', 'Status', 'Código SEFAZ', 'Mensagem', 'Usuário', 'Data/Hora'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $this->styleExcelHeader($sheet, "A{$row}:{$lastCol}{$row}");
        $row++;

        foreach ($data as $i => $item) {
            $nfeLabel = $item['nfe_numero'] ? $item['nfe_numero'] . '/' . $item['nfe_serie'] : '-';
            $sheet->setCellValue('A' . $row, $nfeLabel);
            $sheet->setCellValue('B' . $row, NfeReportModel::getLogActionLabel($item['action']));
            $sheet->setCellValue('C' . $row, $item['status']);
            $sheet->setCellValue('D' . $row, $item['code_sefaz'] ?? '-');
            $sheet->setCellValue('E' . $row, $item['message'] ?? '-');
            $sheet->setCellValue('F' . $row, $item['user_name'] ?? 'Sistema');
            $sheet->setCellValue('G' . $row, $item['created_at_fmt']);
            $this->styleExcelDataRow($sheet, "A{$row}:{$lastCol}{$row}", $i % 2 === 1);
            $row++;
        }

        $row += 1;
        $this->excelFooter($sheet, $row, $lastCol);
        $this->autoSizeColumns($sheet, $cols);
        $this->sendExcel($spreadsheet, 'sefaz_logs_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS — EXCEL (Design Profissional)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Cria o cabeçalho da empresa no Excel. Retorna a próxima linha disponível.
     */
    private function excelCompanyHeader($sheet, string $title, string $lastCol): int
    {
        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';

        $sheet->setCellValue('A1', $companyName);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_PRIMARY);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->setCellValue('A2', $title);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(12)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_ACCENT);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $sheet->getStyle("A3:{$lastCol}3")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::CLR_ACCENT);
        $sheet->getRowDimension(3)->setRowHeight(4);

        return 5;
    }

    /**
     * Cria bloco de resumo executivo no Excel. Retorna a próxima linha disponível.
     */
    private function excelSummaryBlock($sheet, int $startRow, string $lastCol, array $metrics): int
    {
        $keys   = array_keys($metrics);
        $values = array_values($metrics);
        $count  = count($metrics);

        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($startRow + 1))->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::CLR_SUMMARY_BG);

        $cols = range('A', 'Z');
        for ($i = 0; $i < $count; $i++) {
            $col = $cols[$i];
            $sheet->setCellValue($col . $startRow, strtoupper($keys[$i]));
            $sheet->getStyle($col . $startRow)->getFont()->setSize(7)->setBold(true)->setName('Calibri')
                ->getColor()->setARGB(self::CLR_MUTED);
            $sheet->getStyle($col . $startRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

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

        $sheet->getStyle("A{$valueRow}:{$lastCol}{$valueRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::CLR_BORDER);

        return $valueRow + 2;
    }

    /**
     * Aplica estilo de cabeçalho de tabela profissional no Excel.
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
     * Adiciona rodapé profissional no Excel.
     */
    private function excelFooter($sheet, int $row, string $lastCol): void
    {
        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';
        $dateTime    = date('d/m/Y \à\s H:i:s');

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::CLR_BORDER);

        $sheet->setCellValue('A' . $row, $companyName . '  |  Akti — Gestão em Produção');
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle('A' . $row)->getFont()->setSize(8)->setItalic(true)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_MUTED);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $sheet->setCellValue('A' . $row, 'Emitido em ' . $dateTime . '  |  Responsável: ' . $this->responsibleUser);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle('A' . $row)->getFont()->setSize(8)->setItalic(true)->setName('Calibri')
            ->getColor()->setARGB(self::CLR_MUTED);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Auto-ajusta largura das colunas.
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
