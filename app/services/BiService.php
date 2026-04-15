<?php

namespace Akti\Services;

use Akti\Models\Order;
use Akti\Models\Financial;
use Akti\Models\Pipeline;
use Akti\Models\Customer;

class BiService
{
    private \PDO $db;
    private Order $order;
    private Financial $financial;
    private Pipeline $pipeline;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->order = new Order($db);
        $this->financial = new Financial($db);
        $this->pipeline = new Pipeline($db);
    }

    /**
     * Dashboard de vendas: faturamento, ticket médio, conversão, por período.
     */
    public function getSalesDashboard(string $dateFrom, string $dateTo): array
    {
        // Faturamento no período
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS faturamento,
                COALESCE(AVG(total_amount), 0) AS ticket_medio,
                COUNT(DISTINCT customer_id) AS clientes_ativos
            FROM orders
            WHERE created_at BETWEEN :from AND :to
              AND status NOT IN ('cancelado','canceled')
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $summary = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Faturamento por mês
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS mes,
                COUNT(*) AS pedidos,
                COALESCE(SUM(total_amount), 0) AS faturamento
            FROM orders
            WHERE created_at BETWEEN :from AND :to
              AND status NOT IN ('cancelado','canceled')
            GROUP BY mes ORDER BY mes
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $byMonth = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Top produtos
        $stmt = $this->db->prepare("
            SELECT p.name AS produto, SUM(oi.quantity) AS qtd, SUM(oi.quantity * oi.unit_price) AS valor
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            JOIN orders o ON o.id = oi.order_id
            WHERE o.created_at BETWEEN :from AND :to
              AND o.status NOT IN ('cancelado','canceled')
            GROUP BY p.id, p.name
            ORDER BY valor DESC
            LIMIT 10
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $topProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Top clientes
        $stmt = $this->db->prepare("
            SELECT c.name AS cliente, COUNT(o.id) AS pedidos, SUM(o.total_amount) AS valor
            FROM orders o
            JOIN customers c ON c.id = o.customer_id
            WHERE o.created_at BETWEEN :from AND :to
              AND o.status NOT IN ('cancelado','canceled')
            GROUP BY c.id, c.name
            ORDER BY valor DESC
            LIMIT 10
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $topCustomers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Pedidos por status
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) AS total
            FROM orders
            WHERE created_at BETWEEN :from AND :to
            GROUP BY status
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $byStatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'summary'      => $summary,
            'by_month'     => $byMonth,
            'top_products' => $topProducts,
            'top_customers' => $topCustomers,
            'by_status'    => $byStatus,
        ];
    }

    /**
     * Dashboard de produção: throughput, gargalos, tempo médio por etapa.
     */
    public function getProductionDashboard(string $dateFrom, string $dateTo): array
    {
        $pipelineStats = $this->pipeline->getStats();

        // Throughput: pedidos concluídos por dia
        $stmt = $this->db->prepare("
            SELECT DATE(updated_at) AS dia, COUNT(*) AS concluidos
            FROM orders
            WHERE pipeline_stage = 'concluido'
              AND updated_at BETWEEN :from AND :to
            GROUP BY dia ORDER BY dia
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $throughput = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Tempo médio por etapa (pipeline_history)
        $stmt = $this->db->prepare("
            SELECT
                from_stage,
                to_stage,
                AVG(TIMESTAMPDIFF(HOUR, ph.created_at, 
                    COALESCE((SELECT MIN(ph2.created_at) FROM pipeline_history ph2 
                              WHERE ph2.order_id = ph.order_id 
                              AND ph2.created_at > ph.created_at), NOW())
                )) AS avg_hours
            FROM pipeline_history ph
            WHERE ph.created_at BETWEEN :from AND :to
            GROUP BY from_stage, to_stage
            ORDER BY avg_hours DESC
        ");
        $stmt->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
        $stageTime = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Pedidos atrasados por etapa (comparando horas na etapa com meta)
        $stmt = $this->db->prepare("
            SELECT o.pipeline_stage AS current_stage, COUNT(*) AS atrasados
            FROM orders o
            JOIN pipeline_stage_goals g ON g.stage = o.pipeline_stage
            WHERE o.pipeline_stage NOT IN ('concluido','cancelado')
              AND o.status != 'cancelado'
              AND TIMESTAMPDIFF(HOUR, o.pipeline_entered_at, NOW()) > g.max_hours
            GROUP BY o.pipeline_stage
        ");
        $stmt->execute();
        $bottlenecks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'pipeline_stats' => $pipelineStats,
            'throughput'     => $throughput,
            'stage_time'     => $stageTime,
            'bottlenecks'    => $bottlenecks,
        ];
    }

    /**
     * Dashboard financeiro: fluxo de caixa, inadimplência, DRE simplificado.
     */
    public function getFinancialDashboard(string $dateFrom, string $dateTo): array
    {
        $month = (int) date('m', strtotime($dateTo));
        $year  = (int) date('Y', strtotime($dateTo));
        $summary = $this->financial->getSummary($month, $year);
        $chartData = $this->financial->getChartData(6);
        $overdue = $this->financial->getOverdueInstallments();

        // Fluxo de caixa por mês
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(due_date, '%Y-%m') AS mes,
                SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END) AS entradas,
                SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END) AS saidas
            FROM financial_entries
            WHERE due_date BETWEEN :from AND :to
            GROUP BY mes ORDER BY mes
        ");
        $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $cashFlow = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // DRE simplificado
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'receita' AND status = 'pago' THEN amount ELSE 0 END), 0) AS receita_realizada,
                COALESCE(SUM(CASE WHEN type = 'despesa' AND status = 'pago' THEN amount ELSE 0 END), 0) AS despesa_realizada,
                COALESCE(SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END), 0) AS receita_prevista,
                COALESCE(SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END), 0) AS despesa_prevista
            FROM financial_entries
            WHERE due_date BETWEEN :from AND :to
        ");
        $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $dre = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'summary'   => $summary,
            'chart_data' => $chartData,
            'overdue'    => $overdue,
            'cash_flow'  => $cashFlow,
            'dre'        => $dre,
        ];
    }

    /**
     * Drill-down: detalhamento de pedidos por filtro.
     */
    public function drillDown(string $type, array $filters): array
    {
        $params = [];
        $where = ['1=1'];

        if (!empty($filters['date_from'])) {
            $where[] = 'o.created_at >= :from';
            $params[':from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.created_at <= :to';
            $params[':to'] = $filters['date_to'] . ' 23:59:59';
        }

        switch ($type) {
            case 'orders_by_status':
                if (!empty($filters['status'])) {
                    $where[] = 'o.status = :status';
                    $params[':status'] = $filters['status'];
                }
                $sql = "SELECT o.id, o.order_number, c.name AS customer, o.total_amount, o.status, o.created_at
                        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id
                        WHERE " . implode(' AND ', $where) . " ORDER BY o.created_at DESC LIMIT 100";
                break;

            case 'orders_by_stage':
                if (!empty($filters['stage'])) {
                    $where[] = 'o.pipeline_stage = :stage';
                    $params[':stage'] = $filters['stage'];
                }
                $sql = "SELECT o.id, o.order_number, c.name AS customer, o.total_amount, o.pipeline_stage AS current_stage, o.updated_at
                        FROM orders o
                        LEFT JOIN customers c ON c.id = o.customer_id
                        WHERE " . implode(' AND ', $where) . " ORDER BY o.updated_at DESC LIMIT 100";
                break;

            case 'top_product_orders':
                if (!empty($filters['product_id'])) {
                    $where[] = 'oi.product_id = :pid';
                    $params[':pid'] = (int) $filters['product_id'];
                }
                $sql = "SELECT o.id, o.order_number, c.name AS customer, p.name AS product, oi.quantity, oi.unit_price, (oi.quantity * oi.unit_price) AS subtotal
                        FROM order_items oi
                        JOIN orders o ON o.id = oi.order_id
                        JOIN products p ON p.id = oi.product_id
                        LEFT JOIN customers c ON c.id = o.customer_id
                        WHERE " . implode(' AND ', $where) . " ORDER BY subtotal DESC LIMIT 100";
                break;

            case 'overdue_installments':
                $sql = "SELECT fi.id, o.order_number, c.name AS customer, fi.amount, fi.due_date,
                        DATEDIFF(NOW(), fi.due_date) AS days_overdue
                        FROM financial_installments fi
                        JOIN orders o ON o.id = fi.order_id
                        LEFT JOIN customers c ON c.id = o.customer_id
                        WHERE fi.status = 'pendente' AND fi.due_date < CURDATE()
                        ORDER BY fi.due_date ASC LIMIT 100";
                break;

            default:
                return [];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
