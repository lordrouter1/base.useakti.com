<?php
namespace Akti\Services;

use Akti\Models\ReportModel;
use Akti\Models\NfeReportModel;
use Akti\Utils\Input;
use TCPDF;

/**
 * Service: ReportPdfService
 * Responsável por toda a geração de relatórios em PDF.
 * Encapsula helpers TCPDF e cada tipo de relatório exportável.
 */
class ReportPdfService
{
    private $report;
    private $nfeReport;
    private $company;
    private string $responsibleUser;

    /**
     * Construtor da classe ReportPdfService.
     *
     * @param ReportModel $report Report
     * @param NfeReportModel $nfeReport Nfe report
     * @param array $company Company
     * @param string $responsibleUser Responsible user
     */
    public function __construct(ReportModel $report, NfeReportModel $nfeReport, array $company, string $responsibleUser)
    {
        $this->report          = $report;
        $this->nfeReport       = $nfeReport;
        $this->company         = $company;
        $this->responsibleUser = $responsibleUser;
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — PEDIDOS POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportOrdersByPeriod(string $start, string $end): void
    {
        $data = $this->report->getOrdersByPeriod($start, $end);
        $stageLabels = ReportModel::getStageLabels();

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $pdf = $this->createPdf('Relatório de Pedidos por Período');

        $totalValue = array_sum(array_column($data, 'total'));
        $totalPaid = count(array_filter($data, fn($r) => ($r['payment_status'] ?? '') === 'pago'));
        $this->pdfSummaryBox($pdf, [
            'Período'          => $period,
            'Total de Pedidos' => count($data),
            'Valor Total'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
            'Pedidos Pagos'    => $totalPaid,
        ]);

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

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportRevenueByCustomer(string $start, string $end): void
    {
        $data = $this->report->getRevenueByCustomer($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalOrders  = array_sum(array_column($data, 'total_orders'));
        $totalRevenue = array_sum(array_column($data, 'total_revenue'));

        $pdf = $this->createPdf('Faturamento por Cliente');

        $this->pdfSummaryBox($pdf, [
            'Período'             => $period,
            'Clientes Ativos'     => count($data),
            'Total de Pedidos'    => $totalOrders,
            'Faturamento Total'   => 'R$ ' . number_format($totalRevenue, 2, ',', '.'),
        ]);

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

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportIncomeStatement(string $start, string $end): void
    {
        $data = $this->report->getIncomeStatement($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $net = $data['totals']['net_balance'];

        $pdf = $this->createPdf('Demonstrativo de Resultado (DRE)');

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

    /**
     * Exporta dados.
     * @return void
     */
    public function exportOpenInstallments(): void
    {
        $data = $this->report->getOpenInstallments();

        $totalValue    = array_sum(array_column($data, 'amount'));
        $overdueCount  = count(array_filter($data, fn($r) => (int)$r['days_overdue'] > 0));

        $pdf = $this->createPdf('Parcelas Pendentes / Atrasadas');

        $this->pdfSummaryBox($pdf, [
            'Gerado em'           => date('d/m/Y H:i'),
            'Total de Parcelas'   => count($data),
            'Parcelas Atrasadas'  => $overdueCount,
            'Valor Pendente'      => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

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

        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1] + $widths[2], 'text' => 'TOTAL (' . count($data) . ' parcelas)', 'align' => 'R'],
            ['w' => $widths[3], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[4] + $widths[5], 'text' => '', 'align' => 'C'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'parcelas_pendentes_' . date('Ymd'));
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — AGENDAMENTOS DE CONTATO (ORÇAMENTO)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportScheduledContacts(string $start, string $end): void
    {
        $data = $this->report->getScheduledContacts($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totalValue  = array_sum(array_column($data, 'total'));
        $urgentCount = count(array_filter($data, fn($r) => ($r['priority'] ?? '') === 'urgente'));

        $pdf = $this->createPdf('Agendamentos de Contato — Orçamento');

        $this->pdfSummaryBox($pdf, [
            'Período'              => $period,
            'Total de Agendamentos'=> count($data),
            'Urgentes'             => $urgentCount,
            'Valor em Orçamento'   => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

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

        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1] + $widths[2], 'text' => 'TOTAL (' . count($data) . ' agendamentos)', 'align' => 'R'],
            ['w' => $widths[3] + $widths[4], 'text' => '', 'align' => 'C'],
            ['w' => $widths[5], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'agendamentos_contato_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — CATÁLOGO DE PRODUTOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     * @return void
     */
    public function exportProductCatalog(): void
    {
        $productId  = Input::get('product_id', 'int', null);
        $showVars   = Input::get('show_variations', 'string', '1') !== '0';

        $data = $this->report->getProductsCatalog($productId ?: null, $showVars);
        $products    = $data['products'];
        $priceTables = $data['price_tables'];

        $pdf = $this->createPdf('Catálogo de Produtos');

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

        foreach ($products as $idx => $prod) {
            if ($idx > 0) {
                $pdf->Ln(4);
            }

            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetFillColor(241, 245, 249);
            $pdf->Cell(0, 7, $prod['name'], 0, 1, 'L', true);

            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->SetTextColor(100, 100, 100);

            $infoLine = 'SKU: ' . ($prod['sku'] ?: 'N/D');
            $infoLine .= '   |   Categoria: ' . ($prod['category_name'] ?: 'N/D');
            $infoLine .= '   |   Subcategoria: ' . ($prod['subcategory_name'] ?: 'N/D');
            $infoLine .= '   |   Preço Base: R$ ' . number_format((float)$prod['price'], 2, ',', '.');
            $pdf->Cell(0, 5, $infoLine, 0, 1, 'L');

            if (!empty($prod['sectors'])) {
                $pdf->SetFont('helvetica', 'I', 7);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->Cell(0, 4, 'Setores: ' . implode(' > ', $prod['sectors']), 0, 1, 'L');
            }

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

    /**
     * Exporta dados.
     * @return void
     */
    public function exportStockByWarehouse(): void
    {
        $productId   = Input::get('product_id', 'int', null);
        $warehouseId = Input::get('warehouse_id', 'int', null);

        $data = $this->report->getStockByWarehouse($productId ?: null, $warehouseId ?: null);
        $items      = $data['items'];

        $pdf = $this->createPdf('Estoque por Armazém');

        $totalQty   = array_sum(array_column($items, 'quantity'));
        $totalValue = 0;
        foreach ($items as $item) {
            $totalValue += (float)$item['quantity'] * (float)$item['product_price'];
        }
        $this->pdfSummaryBox($pdf, [
            'Gerado em'        => date('d/m/Y H:i'),
            'Itens no Estoque' => count($items),
            'Qtd Total'        => number_format($totalQty, 0, ',', '.'),
            'Valor Estimado'   => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

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
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

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

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportStockMovements(string $start, string $end): void
    {
        $data = $this->report->getStockMovements($start, $end);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $entryCount = count(array_filter($data, fn($r) => $r['type'] === 'entrada'));
        $exitCount  = count(array_filter($data, fn($r) => $r['type'] === 'saida'));

        $pdf = $this->createPdf('Movimentações de Estoque');

        $this->pdfSummaryBox($pdf, [
            'Período'       => $period,
            'Movimentações' => count($data),
            'Entradas'      => $entryCount,
            'Saídas'        => $exitCount,
        ]);

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
    // PDF — COMISSÕES POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @param int|null $userId ID do usuário
     * @return void
     */
    public function exportCommissionsReport(string $start, string $end, ?int $userId = null): void
    {
        $data = $this->report->getCommissionsByPeriod($start, $end, $userId);

        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));
        $totals = $data['totals'];

        $pdf = $this->createPdf('Relatório de Comissões');

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

        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(100, 100, 100);
        $statusLine = 'Calculada: R$ ' . number_format($totals['total_calculada'], 2, ',', '.')
                    . '   |   Aprovada: R$ ' . number_format($totals['total_aprovada'], 2, ',', '.')
                    . '   |   Paga: R$ ' . number_format($totals['total_paga'], 2, ',', '.');
        $pdf->Cell(0, 5, $statusLine, 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Ln(2);

        foreach ($data['by_user'] as $uIdx => $userGroup) {
            if ($uIdx > 0) {
                $pdf->Ln(4);
            }

            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetFillColor(241, 245, 249);
            $pdf->Cell(0, 7, chr(226) . chr(150) . chr(186) . '  ' . $userGroup['user_name'] . ' (' . $userGroup['count'] . ' registros)', 0, 1, 'L', true);
            $pdf->SetTextColor(51, 51, 51);

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

            $this->pdfTotalRow($pdf, [
                ['w' => $widths[0] + $widths[1] + $widths[2] + $widths[3], 'text' => 'Subtotal ' . $userGroup['user_name'], 'align' => 'R'],
                ['w' => $widths[4], 'text' => 'R$ ' . number_format($userGroup['total_valor_base'], 2, ',', '.'), 'align' => 'R'],
                ['w' => $widths[5], 'text' => 'R$ ' . number_format($userGroup['total_comissao'], 2, ',', '.'), 'align' => 'R'],
                ['w' => $widths[6] + $widths[7], 'text' => '', 'align' => 'C'],
            ]);
        }

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
    // PDF — NF-e POR PERÍODO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
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

        $pdf = $this->createPdf('Relatório de NF-e por Período');

        $this->pdfSummaryBox($pdf, [
            'Período'        => $period,
            'Total NF-e'     => (int) $kpis['total_emitidas'],
            'Autorizadas'    => (int) $kpis['autorizadas'],
            'Valor Autoriz.' => 'R$ ' . number_format((float) $kpis['valor_autorizado'], 2, ',', '.'),
        ]);

        $headers = ['#', 'Mod.', 'Número', 'Destinatário', 'Valor (R$)', 'Status', 'Data'];
        $widths  = [10, 14, 18, 52, 28, 24, 34];
        $aligns  = ['C', 'C', 'C', 'L', 'R', 'C', 'C'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                $row['id'],
                NfeReportModel::getModeloLabel((int) $row['modelo']),
                $row['numero'] . '/' . $row['serie'],
                mb_substr($row['dest_nome'] ?? 'Consumidor', 0, 32),
                number_format((float) $row['valor_total'], 2, ',', '.'),
                NfeReportModel::getNfeStatusLabel($row['status']),
                $row['emitted_at_fmt'] ?: $row['created_at_fmt'],
            ], $aligns, $fill);
            $fill = !$fill;
        }

        $totalValue = array_sum(array_column($data, 'valor_total'));
        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1] + $widths[2] + $widths[3], 'text' => 'TOTAL (' . count($data) . ' documentos)', 'align' => 'R'],
            ['w' => $widths[4], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[5] + $widths[6], 'text' => '', 'align' => 'C'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'nfe_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — RESUMO DE IMPOSTOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportTaxSummary(string $start, string $end): void
    {
        $data = $this->nfeReport->getTaxSummary($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $totals = $data['totals'];
        $totalTributos = (float) $totals['total_icms'] + (float) $totals['total_pis']
                       + (float) $totals['total_cofins'] + (float) $totals['total_ipi'];

        $pdf = $this->createPdf('Resumo de Impostos por Período');

        $this->pdfSummaryBox($pdf, [
            'Período'       => $period,
            'Total NF-e'    => (int) $totals['total_nfes'],
            'ICMS Total'    => 'R$ ' . number_format((float) $totals['total_icms'], 2, ',', '.'),
            'Total Tributos'=> 'R$ ' . number_format($totalTributos, 2, ',', '.'),
        ]);

        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(52, 73, 94);
        $pdf->Cell(0, 7, 'Resumo por Tipo de Imposto', 0, 1, 'L');
        $pdf->SetTextColor(51, 51, 51);

        $taxHeaders = ['Imposto', 'Valor Total (R$)', '% do Total'];
        $taxWidths  = [60, 60, 60];
        $this->pdfTableHeader($pdf, $taxHeaders, $taxWidths);

        $taxes = [
            ['ICMS', (float) $totals['total_icms']],
            ['PIS', (float) $totals['total_pis']],
            ['COFINS', (float) $totals['total_cofins']],
            ['IPI', (float) $totals['total_ipi']],
        ];
        $fill = false;
        foreach ($taxes as $tax) {
            $pct = $totalTributos > 0 ? ($tax[1] / $totalTributos * 100) : 0;
            $this->pdfTableRow($pdf, $taxWidths, [
                $tax[0],
                'R$ ' . number_format($tax[1], 2, ',', '.'),
                number_format($pct, 1, ',', '.') . '%',
            ], ['L', 'R', 'C'], $fill);
            $fill = !$fill;
        }

        $this->pdfTotalRow($pdf, [
            ['w' => $taxWidths[0], 'text' => 'TOTAL TRIBUTOS', 'align' => 'R'],
            ['w' => $taxWidths[1], 'text' => 'R$ ' . number_format($totalTributos, 2, ',', '.'), 'align' => 'R'],
            ['w' => $taxWidths[2], 'text' => '100%', 'align' => 'C'],
        ]);

        if (!empty($data['items'])) {
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(52, 73, 94);
            $pdf->Cell(0, 7, 'Detalhamento por NCM / CFOP', 0, 1, 'L');
            $pdf->SetTextColor(51, 51, 51);

            $detHeaders = ['NCM', 'CFOP', 'Qtd', 'Valor (R$)', 'ICMS (R$)', 'PIS (R$)', 'COFINS (R$)'];
            $detWidths  = [24, 18, 14, 30, 28, 28, 28];
            $this->pdfTableHeader($pdf, $detHeaders, $detWidths);

            $fill = false;
            foreach (array_slice($data['items'], 0, 30) as $item) {
                $this->pdfTableRow($pdf, $detWidths, [
                    $item['ncm'] ?? '-',
                    $item['cfop'] ?? '-',
                    $item['qtd_itens'],
                    number_format((float) $item['valor_total'], 2, ',', '.'),
                    number_format((float) $item['icms'], 2, ',', '.'),
                    number_format((float) $item['pis'], 2, ',', '.'),
                    number_format((float) $item['cofins'], 2, ',', '.'),
                ], ['C', 'C', 'C', 'R', 'R', 'R', 'R'], $fill);
                $fill = !$fill;
            }
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'impostos_periodo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — NF-e POR CLIENTE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportNfesByCustomer(string $start, string $end): void
    {
        $customerId = Input::get('customer_id', 'int', null);
        $data = $this->nfeReport->getNfesByCustomer($start, $end, $customerId ?: null);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $totalNfes = array_sum(array_column($data, 'total_nfes'));
        $totalValue = array_sum(array_column($data, 'valor_total'));

        $pdf = $this->createPdf('NF-e por Cliente');

        $this->pdfSummaryBox($pdf, [
            'Período'         => $period,
            'Clientes'        => count($data),
            'Total NF-e'      => $totalNfes,
            'Valor Total'     => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        $headers = ['Cliente', 'CPF/CNPJ', 'Qtd NF-e', 'Valor Total (R$)', 'Primeira', 'Última'];
        $widths  = [50, 36, 18, 32, 26, 26];
        $aligns  = ['L', 'C', 'C', 'R', 'C', 'C'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                mb_substr($row['customer_name'] ?? 'N/A', 0, 30),
                $row['dest_cnpj_cpf'] ?? '-',
                $row['total_nfes'],
                'R$ ' . number_format((float) $row['valor_total'], 2, ',', '.'),
                $row['primeira_emissao'] ?? '-',
                $row['ultima_emissao'] ?? '-',
            ], $aligns, $fill);
            $fill = !$fill;
        }

        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1], 'text' => 'TOTAL (' . count($data) . ' clientes)', 'align' => 'R'],
            ['w' => $widths[2], 'text' => (string) $totalNfes, 'align' => 'C'],
            ['w' => $widths[3], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[4] + $widths[5], 'text' => '', 'align' => 'C'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'nfe_cliente_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — RESUMO CFOP
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportCfopSummary(string $start, string $end): void
    {
        $data = $this->nfeReport->getCfopSummary($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $totalValue = array_sum(array_column($data, 'valor_total'));

        $pdf = $this->createPdf('Resumo por CFOP');

        $this->pdfSummaryBox($pdf, [
            'Período'        => $period,
            'CFOPs Utilizados' => count($data),
            'Valor Total'    => 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        $headers = ['CFOP', 'Descrição', 'NF-e', 'Itens', 'Valor (R$)', 'ICMS (R$)'];
        $widths  = [16, 55, 16, 16, 36, 36];
        $aligns  = ['C', 'L', 'C', 'C', 'R', 'R'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                $row['cfop'],
                mb_substr(NfeReportModel::getCfopDescription($row['cfop']), 0, 35),
                $row['qtd_nfes'],
                $row['qtd_itens'],
                number_format((float) $row['valor_total'], 2, ',', '.'),
                number_format((float) $row['icms_total'], 2, ',', '.'),
            ], $aligns, $fill);
            $fill = !$fill;
        }

        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1] + $widths[2] + $widths[3], 'text' => 'TOTAL', 'align' => 'R'],
            ['w' => $widths[4], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[5], 'text' => 'R$ ' . number_format(array_sum(array_column($data, 'icms_total')), 2, ',', '.'), 'align' => 'R'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'cfop_resumo_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — NF-e CANCELADAS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportCancelledNfes(string $start, string $end): void
    {
        $data = $this->nfeReport->getCancelledNfes($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $totalValue = array_sum(array_column($data, 'valor_total'));

        $pdf = $this->createPdf('NF-e Canceladas');

        $this->pdfSummaryBox($pdf, [
            'Período'        => $period,
            'Canceladas'     => count($data),
            'Valor Cancelado'=> 'R$ ' . number_format($totalValue, 2, ',', '.'),
        ]);

        $headers = ['Número', 'Destinatário', 'Valor (R$)', 'Data Emissão', 'Data Cancel.', 'Motivo'];
        $widths  = [18, 40, 26, 26, 26, 44];
        $aligns  = ['C', 'L', 'R', 'C', 'C', 'L'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                $row['numero'] . '/' . $row['serie'],
                mb_substr($row['dest_nome'] ?? 'N/A', 0, 24),
                number_format((float) $row['valor_total'], 2, ',', '.'),
                $row['emitted_at_fmt'] ?? '-',
                $row['cancel_date_fmt'] ?? '-',
                mb_substr($row['cancel_motivo'] ?? '-', 0, 28),
            ], $aligns, $fill);
            $fill = !$fill;
        }

        $this->pdfTotalRow($pdf, [
            ['w' => $widths[0] + $widths[1], 'text' => 'TOTAL (' . count($data) . ' canceladas)', 'align' => 'R'],
            ['w' => $widths[2], 'text' => 'R$ ' . number_format($totalValue, 2, ',', '.'), 'align' => 'R'],
            ['w' => $widths[3] + $widths[4] + $widths[5], 'text' => '', 'align' => 'C'],
        ]);

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'nfe_canceladas_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — INUTILIZAÇÕES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportInutilizacoes(string $start, string $end): void
    {
        $data = $this->nfeReport->getInutilizacoes($start, $end);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $pdf = $this->createPdf('Numerações Inutilizadas');

        $this->pdfSummaryBox($pdf, [
            'Período'           => $period,
            'Inutilizações'     => count($data),
        ]);

        $headers = ['#', 'Número', 'Série', 'Modelo', 'Justificativa', 'Data'];
        $widths  = [12, 22, 16, 18, 72, 40];
        $aligns  = ['C', 'C', 'C', 'C', 'L', 'C'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $this->pdfTableRow($pdf, $widths, [
                $row['id'],
                $row['numero'],
                $row['serie'],
                NfeReportModel::getModeloLabel((int) $row['modelo']),
                mb_substr($row['justificativa'] ?? '-', 0, 45),
                $row['created_at_fmt'],
            ], $aligns, $fill);
            $fill = !$fill;
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'inutilizacoes_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // PDF — LOGS SEFAZ
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exporta dados.
     *
     * @param string $start Start
     * @param string $end End
     * @return void
     */
    public function exportSefazLogs(string $start, string $end): void
    {
        $actionFilter = Input::get('log_action', 'string', '');
        $data = $this->nfeReport->getSefazLogs($start, $end, $actionFilter ?: null);
        $period = date('d/m/Y', strtotime($start)) . ' a ' . date('d/m/Y', strtotime($end));

        $pdf = $this->createPdf('Log de Comunicação SEFAZ');

        $this->pdfSummaryBox($pdf, [
            'Período'       => $period,
            'Total Registros' => count($data),
        ]);

        $headers = ['NF-e', 'Ação', 'Status', 'Código', 'Mensagem', 'Usuário', 'Data'];
        $widths  = [18, 22, 16, 16, 50, 24, 34];
        $aligns  = ['C', 'C', 'C', 'C', 'L', 'C', 'C'];
        $this->pdfTableHeader($pdf, $headers, $widths);

        $fill = false;
        foreach ($data as $row) {
            $nfeLabel = $row['nfe_numero'] ? $row['nfe_numero'] . '/' . $row['nfe_serie'] : '-';
            $this->pdfTableRow($pdf, $widths, [
                $nfeLabel,
                NfeReportModel::getLogActionLabel($row['action']),
                $row['status'],
                $row['code_sefaz'] ?? '-',
                mb_substr($row['message'] ?? '-', 0, 32),
                mb_substr($row['user_name'] ?? 'Sistema', 0, 14),
                $row['created_at_fmt'],
            ], $aligns, $fill);
            $fill = !$fill;
        }

        $this->pdfFooter($pdf);
        $this->sendPdf($pdf, 'sefaz_logs_' . $start . '_' . $end);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS — PDF (TCPDF)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Cria instância TCPDF com cabeçalho profissional minimalista.
     */
    private function createPdf(string $title): TCPDF
    {
        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';
        $logoPath    = $this->company['company_logo'] ?? '';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Akti — Gestão em Produção');
        $pdf->SetAuthor($companyName);
        $pdf->SetTitle($title);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $startY = $pdf->GetY();

        $logoX = 12;
        $logoW = 0;
        if (!empty($logoPath) && file_exists($logoPath)) {
            $pdf->Image($logoPath, $logoX, $startY, 18, 18, '', '', '', true, 300, '', false, false, 0);
            $logoW = 22;
        }

        $pdf->SetXY($logoX + $logoW, $startY);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 7, $companyName, 0, 1, 'L');

        $pdf->SetX($logoX + $logoW);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(149, 165, 166);
        $pdf->Cell(0, 5, $title, 0, 1, 'L');

        $lineY = max($pdf->GetY(), $startY + 18) + 2;
        $pdf->SetY($lineY);
        $pdf->SetDrawColor(52, 152, 219);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(12, $lineY, 198, $lineY);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->Ln(4);

        $pdf->SetTextColor(51, 51, 51);

        return $pdf;
    }

    /**
     * Desenha uma caixa de resumo executivo com métricas-chave.
     */
    private function pdfSummaryBox(TCPDF $pdf, array $metrics): void
    {
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(222, 226, 230);

        $boxX = 12;
        $boxW = 186;
        $boxH = 16;

        $startY = $pdf->GetY();

        $pdf->RoundedRect($boxX, $startY, $boxW, $boxH, 2, '1111', 'DF');

        $keys   = array_keys($metrics);
        $values = array_values($metrics);
        $count  = count($metrics);
        $colW   = $boxW / $count;

        for ($i = 0; $i < $count; $i++) {
            $colX = $boxX + ($i * $colW);

            $pdf->SetXY($colX, $startY + 2);
            $pdf->SetFont('helvetica', '', 6.5);
            $pdf->SetTextColor(149, 165, 166);
            $pdf->Cell($colW, 4, strtoupper($keys[$i]), 0, 0, 'C');

            $pdf->SetXY($colX, $startY + 7);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->Cell($colW, 6, (string)$values[$i], 0, 0, 'C');

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

        $y = $pdf->GetY();
        $pdf->SetDrawColor(238, 238, 238);
        $pdf->Line(12, $y, 12 + array_sum($widths), $y);
        $pdf->SetDrawColor(222, 226, 230);
    }

    /**
     * Desenha linha de totais com destaque visual.
     */
    private function pdfTotalRow(TCPDF $pdf, array $cells): void
    {
        $pdf->Ln(0.5);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(52, 152, 219);

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
     * Desenha rodapé profissional no PDF.
     */
    private function pdfFooter(TCPDF $pdf): void
    {
        $pdf->Ln(10);

        $y = $pdf->GetY();
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->Line(12, $y, 198, $y);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(149, 165, 166);

        $companyName = $this->company['company_name'] ?? 'Akti — Gestão em Produção';
        $dateTime    = date('d/m/Y \à\s H:i:s');

        $pdf->Cell(0, 4, $companyName . '  |  Akti — Gestão em Produção', 0, 1, 'C');
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
}
