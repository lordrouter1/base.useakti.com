<?php
namespace Akti\Services;

use Akti\Models\Financial;
use Akti\Models\Installment;
use Akti\Models\RecurringTransaction;
use PDO;

/**
 * FinancialReportService — Camada de Serviço para Relatórios Financeiros.
 *
 * Consolida dados do dashboard, gráficos, DRE, fluxo de caixa e exportações.
 *
 * @package Akti\Services
 */
class FinancialReportService
{
    private Financial $financial;
    private Installment $installment;
    private PDO $db;

    /**
     * Construtor da classe FinancialReportService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param Financial $financial Financial
     * @param Installment $installment Installment
     */
    public function __construct(PDO $db, Financial $financial, Installment $installment)
    {
        $this->db = $db;
        $this->financial = $financial;
        $this->installment = $installment;
    }

    /**
     * Retorna resumo geral do financeiro (dashboard).
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getSummary(int $month, int $year): array
    {
        return $this->financial->getSummary($month, $year);
    }

    /**
     * Retorna dados para gráfico de receita x despesa.
     * @param int $months
     * @return array
     */
    public function getChartData(int $months = 6): array
    {
        return $this->financial->getChartData($months);
    }

    /**
     * Retorna parcelas pendentes de confirmação.
     * @return array
     */
    public function getPendingConfirmations(): array
    {
        return $this->installment->getPendingConfirmations();
    }

    /**
     * Retorna parcelas vencidas.
     * @return array
     */
    public function getOverdueInstallments(): array
    {
        return $this->installment->getOverdue();
    }

    /**
     * Retorna próximas parcelas a vencer.
     * @param int $days
     * @return array
     */
    public function getUpcomingInstallments(int $days = 7): array
    {
        return $this->installment->getUpcoming($days);
    }

    /**
     * Retorna pedidos com pagamento pendente.
     * @return array
     */
    public function getOrdersPendingPayment(): array
    {
        return $this->financial->getOrdersPendingPayment();
    }

    // ═══════════════════════════════════════════
    // DRE — Demonstrativo de Resultado do Exercício
    // ═══════════════════════════════════════════

    /**
     * Gera DRE simplificado para um período.
     *
     * Retorna receitas agrupadas por categoria, despesas agrupadas por categoria,
     * totais, e resultado líquido.
     *
     * @param string $fromMonth YYYY-MM
     * @param string $toMonth   YYYY-MM
     * @return array
     */
    public function getDre(string $fromMonth, string $toMonth): array
    {
        $fromDate = $fromMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($toMonth . '-01'));

        // Receitas (entradas) agrupadas por categoria
        $sqlEntradas = "SELECT category, 
                               COALESCE(SUM(amount), 0) as total
                        FROM financial_transactions 
                        WHERE type = 'entrada' 
                          AND transaction_date BETWEEN :from AND :to"
                        . ($this->financial->hasSoftDeleteColumn() ? " AND deleted_at IS NULL" : "") .
                       " GROUP BY category ORDER BY total DESC";

        $stmt = $this->db->prepare($sqlEntradas);
        $stmt->execute([':from' => $fromDate, ':to' => $toDate]);
        $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Despesas (saídas) agrupadas por categoria
        $sqlSaidas = "SELECT category, 
                             COALESCE(SUM(amount), 0) as total
                      FROM financial_transactions 
                      WHERE type = 'saida' 
                        AND transaction_date BETWEEN :from AND :to"
                      . ($this->financial->hasSoftDeleteColumn() ? " AND deleted_at IS NULL" : "") .
                     " GROUP BY category ORDER BY total DESC";

        $stmt = $this->db->prepare($sqlSaidas);
        $stmt->execute([':from' => $fromDate, ':to' => $toDate]);
        $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Receita de parcelas pagas no período
        $sqlParcelas = "SELECT COALESCE(SUM(paid_amount), 0) as total 
                        FROM order_installments 
                        WHERE status = 'pago' AND paid_date BETWEEN :from AND :to";
        $stmt = $this->db->prepare($sqlParcelas);
        $stmt->execute([':from' => $fromDate, ':to' => $toDate]);
        $parcelasPagas = (float) $stmt->fetchColumn();

        $totalReceitas = array_sum(array_column($receitas, 'total'));
        $totalDespesas = array_sum(array_column($despesas, 'total'));

        // Mapear nomes de categorias
        $categories = Financial::getCategories();
        $allCats = array_merge($categories['entrada'] ?? [], $categories['saida'] ?? [], Financial::getInternalCategories());

        $receitasDetalhadas = array_map(function ($r) use ($allCats) {
            $r['category_name'] = $allCats[$r['category']] ?? $r['category'];
            $r['total'] = (float) $r['total'];
            return $r;
        }, $receitas);

        $despesasDetalhadas = array_map(function ($d) use ($allCats) {
            $d['category_name'] = $allCats[$d['category']] ?? $d['category'];
            $d['total'] = (float) $d['total'];
            return $d;
        }, $despesas);

        return [
            'periodo'    => ['de' => $fromMonth, 'ate' => $toMonth],
            'receitas'   => $receitasDetalhadas,
            'despesas'   => $despesasDetalhadas,
            'parcelas_pagas' => $parcelasPagas,
            'total_receitas' => $totalReceitas + $parcelasPagas,
            'total_despesas' => $totalDespesas,
            'resultado'      => ($totalReceitas + $parcelasPagas) - $totalDespesas,
        ];
    }

    // ═══════════════════════════════════════════
    // FLUXO DE CAIXA PROJETADO
    // ═══════════════════════════════════════════

    /**
     * Gera fluxo de caixa projetado para os próximos N meses.
     *
     * Combina:
     * - Parcelas pendentes (por mês de vencimento) como entradas previstas
     * - Recorrências ativas projetadas
     * - Histórico de transações confirmadas do mês atual
     *
     * @param int  $months           Horizonte de projeção (3, 6 ou 12)
     * @param bool $includeRecurring Incluir projeção de recorrências
     * @return array
     */
    public function getCashflowProjection(int $months = 6, bool $includeRecurring = true): array
    {
        $projection = [];

        // 1. Parcelas pendentes agrupadas por mês de vencimento
        $sqlParcelas = "SELECT 
                            DATE_FORMAT(due_date, '%Y-%m') as month_key,
                            COALESCE(SUM(amount), 0) as expected_income
                        FROM order_installments 
                        WHERE status IN ('pendente','atrasado') 
                          AND due_date >= CURDATE()
                          AND due_date < DATE_ADD(CURDATE(), INTERVAL :months MONTH)
                        GROUP BY DATE_FORMAT(due_date, '%Y-%m')
                        ORDER BY month_key";
        $stmt = $this->db->prepare($sqlParcelas);
        $stmt->execute([':months' => $months]);
        $installmentsByMonth = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $installmentsByMonth[$row['month_key']] = (float) $row['expected_income'];
        }

        // 2. Transações já realizadas no mês atual
        $currentMonth = date('Y-m');
        $sqlCurrent = "SELECT 
                          COALESCE(SUM(CASE WHEN type='entrada' THEN amount ELSE 0 END), 0) as entradas,
                          COALESCE(SUM(CASE WHEN type='saida' THEN amount ELSE 0 END), 0) as saidas
                       FROM financial_transactions 
                       WHERE DATE_FORMAT(transaction_date, '%Y-%m') = :month"
                       . ($this->financial->hasSoftDeleteColumn() ? " AND deleted_at IS NULL" : "");
        $stmt = $this->db->prepare($sqlCurrent);
        $stmt->execute([':month' => $currentMonth]);
        $currentTx = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Recorrências projetadas
        $recurringProjection = [];
        if ($includeRecurring && RecurringTransaction::tableExists($this->db)) {
            $recurringModel = new RecurringTransaction($this->db);
            $recurringProjection = $recurringModel->projectMonths($months);
        }
        $recurringByMonth = [];
        foreach ($recurringProjection as $rp) {
            $recurringByMonth[$rp['month']] = $rp;
        }

        // 4. Montar projeção mês a mês
        $runningBalance = (float) ($currentTx['entradas'] ?? 0) - (float) ($currentTx['saidas'] ?? 0);

        for ($i = 0; $i < $months; $i++) {
            $monthKey = date('Y-m', strtotime("+{$i} months"));

            $installmentIncome    = $installmentsByMonth[$monthKey] ?? 0;
            $recurringEntradas    = $recurringByMonth[$monthKey]['entradas'] ?? 0;
            $recurringSaidas      = $recurringByMonth[$monthKey]['saidas'] ?? 0;

            $totalEntradas = $installmentIncome + $recurringEntradas;
            $totalSaidas   = $recurringSaidas;

            if ($i === 0) {
                // Mês atual: usar dados reais
                $totalEntradas += (float) ($currentTx['entradas'] ?? 0);
                $totalSaidas   += (float) ($currentTx['saidas'] ?? 0);
            }

            $saldo = $totalEntradas - $totalSaidas;
            $runningBalance = ($i === 0) ? $saldo : $runningBalance + $saldo;

            $projection[] = [
                'month'                  => $monthKey,
                'label'                  => $this->formatMonthLabel($monthKey),
                'entradas_parcelas'      => $installmentIncome,
                'entradas_recorrencias'  => $recurringEntradas,
                'saidas_recorrencias'    => $recurringSaidas,
                'total_entradas'         => $totalEntradas,
                'total_saidas'           => $totalSaidas,
                'saldo_mes'              => $saldo,
                'saldo_acumulado'        => $runningBalance,
            ];
        }

        return $projection;
    }

    // ═══════════════════════════════════════════
    // EXPORTAÇÃO CSV
    // ═══════════════════════════════════════════

    /**
     * Exporta transações filtradas em formato CSV.
     *
     * @param array $filters Filtros (type, category, month, year, search)
     * @return string CSV content
     */
    public function exportTransactionsCsv(array $filters = []): string
    {
        $transactions = $this->financial->getTransactions($filters);

        $categories = Financial::getCategories();
        $allCats = array_merge($categories['entrada'] ?? [], $categories['saida'] ?? [], Financial::getInternalCategories());

        $output = fopen('php://temp', 'r+');
        // BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Data', 'Tipo', 'Categoria', 'Descrição', 'Valor', 'Forma Pgto', 'Confirmado', 'Observação'], ';');

        foreach ($transactions as $tx) {
            fputcsv($output, [
                date('d/m/Y', strtotime($tx['transaction_date'])),
                $tx['type'] === 'entrada' ? 'Entrada' : ($tx['type'] === 'saida' ? 'Saída' : 'Registro'),
                $allCats[$tx['category']] ?? $tx['category'],
                $tx['description'],
                number_format((float) $tx['amount'], 2, ',', '.'),
                $tx['payment_method'] ?? '',
                ($tx['is_confirmed'] ?? 0) ? 'Sim' : 'Não',
                $tx['notes'] ?? '',
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Exporta DRE em formato CSV.
     */
    public function exportDreCsv(string $fromMonth, string $toMonth): string
    {
        $dre = $this->getDre($fromMonth, $toMonth);

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['DRE Simplificado — ' . $dre['periodo']['de'] . ' a ' . $dre['periodo']['ate']], ';');
        fputcsv($output, [], ';');

        fputcsv($output, ['RECEITAS', ''], ';');
        foreach ($dre['receitas'] as $r) {
            fputcsv($output, ['  ' . $r['category_name'], number_format($r['total'], 2, ',', '.')], ';');
        }
        fputcsv($output, ['  Parcelas Pagas (Pedidos)', number_format($dre['parcelas_pagas'], 2, ',', '.')], ';');
        fputcsv($output, ['TOTAL RECEITAS', number_format($dre['total_receitas'], 2, ',', '.')], ';');
        fputcsv($output, [], ';');

        fputcsv($output, ['DESPESAS', ''], ';');
        foreach ($dre['despesas'] as $d) {
            fputcsv($output, ['  ' . $d['category_name'], number_format($d['total'], 2, ',', '.')], ';');
        }
        fputcsv($output, ['TOTAL DESPESAS', number_format($dre['total_despesas'], 2, ',', '.')], ';');
        fputcsv($output, [], ';');

        fputcsv($output, ['RESULTADO LÍQUIDO', number_format($dre['resultado'], 2, ',', '.')], ';');

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Exporta fluxo de caixa projetado em CSV.
     */
    public function exportCashflowCsv(int $months = 6, bool $includeRecurring = true): string
    {
        $projection = $this->getCashflowProjection($months, $includeRecurring);

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Mês', 'Entradas (Parcelas)', 'Entradas (Recorrências)', 'Saídas (Recorrências)', 'Total Entradas', 'Total Saídas', 'Saldo Mês', 'Saldo Acumulado'], ';');

        foreach ($projection as $p) {
            fputcsv($output, [
                $p['label'],
                number_format($p['entradas_parcelas'], 2, ',', '.'),
                number_format($p['entradas_recorrencias'], 2, ',', '.'),
                number_format($p['saidas_recorrencias'], 2, ',', '.'),
                number_format($p['total_entradas'], 2, ',', '.'),
                number_format($p['total_saidas'], 2, ',', '.'),
                number_format($p['saldo_mes'], 2, ',', '.'),
                number_format($p['saldo_acumulado'], 2, ',', '.'),
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    /**
     * Formata mês YYYY-MM em "Mar/2026".
     */
    private function formatMonthLabel(string $yearMonth): string
    {
        $months = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        [$y, $m] = explode('-', $yearMonth);
        return ($months[(int) $m] ?? $m) . '/' . $y;
    }
}
