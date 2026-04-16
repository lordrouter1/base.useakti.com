<?php

namespace Akti\Services;

/**
 * DemandPredictionService — Previsão de demanda com base em dados históricos.
 * FEAT-015: IA para Previsão de Demanda
 *
 * Utiliza análise de séries temporais (média móvel, tendência linear)
 * para estimar demanda futura de produtos.
 */
class DemandPredictionService
{
    private \PDO $db;

    /**
     * Construtor da classe DemandPredictionService.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Prever demanda de um produto para os próximos N dias.
     *
     * @param int $productId ID do produto
     * @param int $days Número de dias para previsão
     * @return array ['forecast' => [...], 'confidence' => float, 'trend' => string]
     */
    public function predictDemand(int $productId, int $days = 30): array
    {
        $history = $this->getOrderHistory($productId, 180);

        if (count($history) < 7) {
            return [
                'forecast'   => [],
                'confidence' => 0,
                'trend'      => 'insufficient_data',
                'message'    => 'Dados insuficientes para previsão (mínimo 7 dias de histórico).',
            ];
        }

        $movingAvg = $this->movingAverage($history, 7);
        $trend = $this->linearTrend($history);

        $forecast = [];
        $lastAvg = end($movingAvg);
        for ($i = 1; $i <= $days; $i++) {
            $predicted = max(0, round($lastAvg + ($trend['slope'] * $i), 2));
            $forecast[] = [
                'date'     => date('Y-m-d', strtotime("+{$i} days")),
                'quantity' => $predicted,
            ];
        }

        $trendLabel = 'stable';
        if ($trend['slope'] > 0.1) {
            $trendLabel = 'growing';
        } elseif ($trend['slope'] < -0.1) {
            $trendLabel = 'declining';
        }

        return [
            'product_id' => $productId,
            'forecast'   => $forecast,
            'confidence' => min(1, count($history) / 90),
            'trend'      => $trendLabel,
            'slope'      => round($trend['slope'], 4),
            'avg_daily'  => round($lastAvg, 2),
        ];
    }

    /**
     * Sugestão de reposição de estoque baseada na previsão.
     *
     * @param int $productId
     * @param int $currentStock Estoque atual
     * @param int $leadTimeDays Prazo de entrega do fornecedor
     * @return array
     */
    public function suggestRestock(int $productId, int $currentStock, int $leadTimeDays = 7): array
    {
        $prediction = $this->predictDemand($productId, $leadTimeDays + 14);

        if ($prediction['trend'] === 'insufficient_data') {
            return ['action' => 'none', 'reason' => $prediction['message']];
        }

        $demandDuringLead = 0;
        $demandSafety = 0;
        foreach ($prediction['forecast'] as $i => $day) {
            if ($i < $leadTimeDays) {
                $demandDuringLead += $day['quantity'];
            } else {
                $demandSafety += $day['quantity'];
            }
        }

        $reorderPoint = ceil($demandDuringLead * 1.2);
        $suggestedQty = ceil($demandDuringLead + $demandSafety);

        return [
            'product_id'       => $productId,
            'current_stock'    => $currentStock,
            'reorder_point'    => $reorderPoint,
            'suggested_qty'    => $suggestedQty,
            'demand_lead_time' => round($demandDuringLead, 0),
            'action'           => $currentStock <= $reorderPoint ? 'reorder_now' : 'monitor',
            'urgency'          => $currentStock <= ($reorderPoint * 0.5) ? 'high' : 'normal',
        ];
    }

    /**
     * Top produtos por demanda prevista.
     */
    public function topDemandProducts(int $limit = 10): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT oi.product_id, p.name as product_name
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
             GROUP BY oi.product_id, p.name
             ORDER BY SUM(oi.quantity) DESC
             LIMIT " . intval($limit)
        );
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($products as $product) {
            $prediction = $this->predictDemand((int)$product['product_id'], 30);
            $totalForecast = array_sum(array_column($prediction['forecast'], 'quantity'));
            $results[] = [
                'product_id'   => $product['product_id'],
                'product_name' => $product['product_name'],
                'forecast_30d' => round($totalForecast, 0),
                'trend'        => $prediction['trend'],
                'avg_daily'    => $prediction['avg_daily'] ?? 0,
            ];
        }

        return $results;
    }

    /**
     * Busca histórico de pedidos diário para um produto.
     */
    private function getOrderHistory(int $productId, int $days): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(o.created_at) as order_date, SUM(oi.quantity) as total_qty
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id = :product_id
               AND o.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(o.created_at)
             ORDER BY order_date ASC"
        );
        $stmt->execute([':product_id' => $productId, ':days' => $days]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Preencher dias sem vendas com zero
        $filled = [];
        $startDate = strtotime("-{$days} days");
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', $startDate + ($i * 86400));
            $filled[$date] = 0;
        }
        foreach ($rows as $row) {
            $filled[$row['order_date']] = (float)$row['total_qty'];
        }

        return array_values($filled);
    }

    /**
     * Média móvel de N períodos.
     */
    private function movingAverage(array $data, int $window): array
    {
        $result = [];
        $count = count($data);
        for ($i = $window - 1; $i < $count; $i++) {
            $sum = 0;
            for ($j = 0; $j < $window; $j++) {
                $sum += $data[$i - $j];
            }
            $result[] = $sum / $window;
        }
        return $result;
    }

    /**
     * Tendência linear (regressão simples y = a + bx).
     */
    private function linearTrend(array $data): array
    {
        $n = count($data);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $data[$i];
            $sumXY += $i * $data[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return ['slope' => 0, 'intercept' => $sumY / $n];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        return ['slope' => $slope, 'intercept' => $intercept];
    }
}
