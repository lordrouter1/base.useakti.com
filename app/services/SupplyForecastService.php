<?php

namespace Akti\Services;

use PDO;
use Akti\Models\SupplyStock;

/**
 * Service de previsão de ruptura de estoque (v2).
 */
class SupplyForecastService
{
    private PDO $db;
    private SupplyStock $stockModel;

    public function __construct(PDO $db, SupplyStock $stockModel)
    {
        $this->db = $db;
        $this->stockModel = $stockModel;
    }

    /**
     * Recalcula previsões de ruptura para todos os insumos ativos.
     *
     * @param string $method Método: 'average', 'weighted', 'last_30_days'
     * @return int Número de previsões atualizadas
     */
    public function recalculateForecasts(string $method = 'weighted'): int
    {
        // Buscar todos os insumos ativos
        $stmt = $this->db->prepare(
            "SELECT id, name, code, min_stock, reorder_point
             FROM supplies WHERE is_active = 1 AND deleted_at IS NULL"
        );
        $stmt->execute();
        $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updated = 0;

        foreach ($supplies as $supply) {
            $supplyId = (int) $supply['id'];

            // Estoque atual total
            $currentStock = $this->getTotalStock($supplyId);

            // Quantidade comprometida (pedidos em aberto)
            $committedQty = $this->getCommittedQuantity($supplyId);

            // Consumo médio diário
            $avgDailyConsumption = $this->calculateDailyConsumption($supplyId, $method);

            // Dias até ruptura
            $availableStock = $currentStock - $committedQty;
            $daysToRupture = null;
            if ($avgDailyConsumption > 0) {
                $daysToRupture = (int) floor($availableStock / $avgDailyConsumption);
                if ($daysToRupture < 0) {
                    $daysToRupture = 0;
                }
            }

            // Determinar status
            $status = $this->determineStatus($availableStock, $daysToRupture, $supply);

            // Upsert na tabela de forecast
            $this->upsertForecast($supplyId, null, $currentStock, $committedQty, $daysToRupture, $status);
            $updated++;
        }

        return $updated;
    }

    /**
     * Retorna alertas de ruptura filtrados por status.
     *
     * @param string|null $status Filtro: 'ok', 'warning', 'critical', 'ruptured', null = todos
     * @return array
     */
    public function getRuptureAlerts(?string $status = null): array
    {
        $sql = "SELECT srf.*, s.name AS supply_name, s.code AS supply_code,
                       s.unit_measure, s.min_stock, s.reorder_point
                FROM supply_rupture_forecasts srf
                JOIN supplies s ON s.id = srf.supply_id
                WHERE s.deleted_at IS NULL";
        $params = [];

        if ($status !== null) {
            $sql .= " AND srf.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY
                    FIELD(srf.status, 'ruptured', 'critical', 'warning', 'ok'),
                    srf.days_to_rupture ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna detalhe de previsão para um insumo específico.
     *
     * @param int $supplyId
     * @return array
     */
    public function getSupplyForecastDetail(int $supplyId): array
    {
        // Forecast data
        $stmt = $this->db->prepare(
            "SELECT srf.*, s.name AS supply_name, s.code AS supply_code,
                    s.unit_measure, s.min_stock, s.reorder_point, s.cost_price
             FROM supply_rupture_forecasts srf
             JOIN supplies s ON s.id = srf.supply_id
             WHERE srf.supply_id = :supply_id AND srf.warehouse_id IS NULL"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        $forecast = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Últimas 30 movimentações de saída
        $movStmt = $this->db->prepare(
            "SELECT DATE(created_at) AS day, SUM(ABS(quantity)) AS total_qty
             FROM supply_stock_movements
             WHERE supply_id = :supply_id AND type IN ('saida')
               AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day DESC
             LIMIT 90"
        );
        $movStmt->execute([':supply_id' => $supplyId]);
        $dailyConsumption = $movStmt->fetchAll(PDO::FETCH_ASSOC);

        // Pedidos em aberto que demandam este insumo
        $ordersStmt = $this->db->prepare(
            "SELECT ps.product_id, p.name AS product_name, ps.quantity AS ratio,
                    ps.waste_percent, ps.loss_percent
             FROM product_supplies ps
             JOIN products p ON p.id = ps.product_id
             WHERE ps.supply_id = :supply_id AND ps.is_optional = 0"
        );
        $ordersStmt->execute([':supply_id' => $supplyId]);
        $demandProducts = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'forecast'          => $forecast,
            'daily_consumption' => $dailyConsumption,
            'demand_products'   => $demandProducts,
        ];
    }

    /**
     * Retorna KPIs de forecast para dashboard.
     *
     * @return array
     */
    public function getDashboardKpis(): array
    {
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM supply_rupture_forecasts
             GROUP BY status"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'ruptured' => (int) ($rows['ruptured'] ?? 0),
            'critical' => (int) ($rows['critical'] ?? 0),
            'warning'  => (int) ($rows['warning'] ?? 0),
            'ok'       => (int) ($rows['ok'] ?? 0),
            'total'    => array_sum(array_map('intval', $rows)),
        ];
    }

    /**
     * Retorna estoque total de um insumo.
     */
    private function getTotalStock(int $supplyId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items WHERE supply_id = :sid AND quantity > 0"
        );
        $stmt->execute([':sid' => $supplyId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Retorna quantidade comprometida (estimada via BOM × pedidos em produção).
     */
    private function getCommittedQuantity(int $supplyId): float
    {
        // Estimativa baseada em pedidos com status de produção
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(ps.quantity * (1 + ps.waste_percent / 100)), 0)
             FROM product_supplies ps
             JOIN order_items oi ON oi.product_id = ps.product_id
             JOIN orders o ON o.id = oi.order_id
             WHERE ps.supply_id = :sid AND ps.is_optional = 0
               AND o.status IN ('producao', 'em_producao', 'aprovado', 'pending')"
        );
        $stmt->execute([':sid' => $supplyId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Calcula consumo médio diário baseado no método escolhido.
     */
    private function calculateDailyConsumption(int $supplyId, string $method): float
    {
        switch ($method) {
            case 'last_30_days':
                $days = 30;
                break;
            case 'weighted':
                $days = 60;
                break;
            case 'average':
            default:
                $days = 90;
                break;
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(ABS(quantity)), 0) AS total_consumed
             FROM supply_stock_movements
             WHERE supply_id = :sid AND type IN ('saida')
               AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)"
        );
        $stmt->bindValue(':sid', $supplyId, PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $totalConsumed = (float) $stmt->fetchColumn();

        if ($method === 'weighted') {
            // Dar mais peso aos últimos 30 dias
            $stmtRecent = $this->db->prepare(
                "SELECT COALESCE(SUM(ABS(quantity)), 0) AS recent_consumed
                 FROM supply_stock_movements
                 WHERE supply_id = :sid AND type IN ('saida')
                   AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $stmtRecent->execute([':sid' => $supplyId]);
            $recentConsumed = (float) $stmtRecent->fetchColumn();

            // Peso: 70% últimos 30 dias, 30% período completo
            $avgRecent = $recentConsumed / 30;
            $avgTotal = $totalConsumed / $days;
            return ($avgRecent * 0.7) + ($avgTotal * 0.3);
        }

        return $days > 0 ? $totalConsumed / $days : 0;
    }

    /**
     * Determina status de ruptura.
     */
    private function determineStatus(float $availableStock, ?int $daysToRupture, array $supply): string
    {
        if ($availableStock <= 0) {
            return 'ruptured';
        }

        if ($daysToRupture !== null && $daysToRupture <= 7) {
            return 'critical';
        }

        $reorderPoint = (float) ($supply['reorder_point'] ?? 0);
        if ($reorderPoint > 0 && $availableStock <= $reorderPoint) {
            return 'warning';
        }

        if ($daysToRupture !== null && $daysToRupture <= 30) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * Upsert forecast record.
     */
    private function upsertForecast(int $supplyId, ?int $warehouseId, float $currentStock, float $committedQty, ?int $daysToRupture, string $status): void
    {
        // Check if exists
        if ($warehouseId === null) {
            $check = $this->db->prepare(
                "SELECT id FROM supply_rupture_forecasts WHERE supply_id = :sid AND warehouse_id IS NULL"
            );
            $check->execute([':sid' => $supplyId]);
        } else {
            $check = $this->db->prepare(
                "SELECT id FROM supply_rupture_forecasts WHERE supply_id = :sid AND warehouse_id = :wid"
            );
            $check->execute([':sid' => $supplyId, ':wid' => $warehouseId]);
        }

        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE supply_rupture_forecasts SET
                    current_stock = :stock, committed_quantity = :committed,
                    days_to_rupture = :days, status = :status, last_calculated_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                ':stock'     => $currentStock,
                ':committed' => $committedQty,
                ':days'      => $daysToRupture,
                ':status'    => $status,
                ':id'        => $existing['id'],
            ]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO supply_rupture_forecasts
                    (supply_id, warehouse_id, current_stock, committed_quantity, days_to_rupture, status, last_calculated_at, tenant_id)
                 VALUES (:sid, :wid, :stock, :committed, :days, :status, NOW(),
                    (SELECT tenant_id FROM supplies WHERE id = :sid2 LIMIT 1))"
            );
            $stmt->execute([
                ':sid'       => $supplyId,
                ':wid'       => $warehouseId,
                ':stock'     => $currentStock,
                ':committed' => $committedQty,
                ':days'      => $daysToRupture,
                ':status'    => $status,
                ':sid2'      => $supplyId,
            ]);
        }
    }
}
