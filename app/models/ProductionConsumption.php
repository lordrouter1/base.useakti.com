<?php

namespace Akti\Models;

use PDO;

/**
 * Model de log de consumo de produção (v2).
 */
class ProductionConsumption
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna logs de consumo por ordem.
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrder(int $orderId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT pcl.*, s.name AS supply_name, s.code AS supply_code, s.unit_measure
             FROM production_consumption_log pcl
             JOIN supplies s ON s.id = pcl.supply_id
             WHERE pcl.order_id = :order_id
             ORDER BY s.name"
        );
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra consumo real (apontamento do operador).
     *
     * @param int   $logId
     * @param float $actualQuantity
     * @param string|null $notes
     * @return bool
     */
    public function logActualConsumption(int $logId, float $actualQuantity, ?string $notes = null): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE production_consumption_log
             SET actual_quantity = :actual, notes = :notes
             WHERE id = :id"
        );
        return $stmt->execute([
            ':actual' => $actualQuantity,
            ':notes'  => $notes,
            ':id'     => $logId,
        ]);
    }

    /**
     * Retorna logs pendentes (sem apontamento real).
     *
     * @param int|null $productId
     * @return array
     */
    public function getPendingReports(?int $productId = null): array
    {
        $sql = "SELECT pcl.*, s.name AS supply_name, s.code AS supply_code, s.unit_measure,
                       p.name AS product_name
                FROM production_consumption_log pcl
                JOIN supplies s ON s.id = pcl.supply_id
                JOIN products p ON p.id = pcl.product_id
                WHERE pcl.actual_quantity IS NULL";
        $params = [];

        if ($productId) {
            $sql .= " AND pcl.product_id = :product_id";
            $params[':product_id'] = $productId;
        }

        $sql .= " ORDER BY pcl.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna estatísticas de eficiência.
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param int|null    $productId
     * @return array
     */
    public function getEfficiencyStats(?string $dateFrom = null, ?string $dateTo = null, ?int $productId = null): array
    {
        $where = "WHERE pcl.actual_quantity IS NOT NULL";
        $params = [];

        if ($dateFrom) {
            $where .= " AND pcl.created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $where .= " AND pcl.created_at <= :date_to";
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }
        if ($productId) {
            $where .= " AND pcl.product_id = :product_id";
            $params[':product_id'] = $productId;
        }

        // Global KPIs
        $kpiSql = "SELECT
                COUNT(DISTINCT pcl.order_id) AS total_orders,
                COUNT(*) AS total_items,
                COALESCE(SUM(pcl.planned_quantity), 0) AS total_planned,
                COALESCE(SUM(pcl.actual_quantity), 0) AS total_actual,
                COALESCE(SUM(pcl.variance), 0) AS total_variance,
                CASE WHEN SUM(pcl.planned_quantity) > 0
                    THEN (SUM(pcl.actual_quantity) / SUM(pcl.planned_quantity)) * 100
                    ELSE 100
                END AS efficiency_percent,
                COALESCE(AVG(pcl.variance_percent), 0) AS avg_variance_percent
            FROM production_consumption_log pcl
            {$where}";
        $stmt = $this->conn->prepare($kpiSql);
        $stmt->execute($params);
        $kpis = $stmt->fetch(PDO::FETCH_ASSOC);

        // Custo de perda estimado
        $costSql = "SELECT COALESCE(SUM(
                        CASE WHEN pcl.variance > 0
                            THEN pcl.variance * s.cost_price
                            ELSE 0
                        END
                    ), 0) AS waste_cost
                FROM production_consumption_log pcl
                JOIN supplies s ON s.id = pcl.supply_id
                {$where}";
        $costStmt = $this->conn->prepare($costSql);
        $costStmt->execute($params);
        $kpis['waste_cost'] = (float) $costStmt->fetchColumn();

        return $kpis;
    }

    /**
     * Retorna top insumos com maior desperdício.
     *
     * @param int         $limit
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getTopWaste(int $limit = 10, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = "WHERE pcl.actual_quantity IS NOT NULL AND pcl.variance > 0";
        $params = [];

        if ($dateFrom) {
            $where .= " AND pcl.created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $where .= " AND pcl.created_at <= :date_to";
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        $sql = "SELECT pcl.supply_id, s.name AS supply_name, s.code AS supply_code,
                       s.unit_measure, s.cost_price,
                       SUM(pcl.variance) AS total_waste,
                       SUM(pcl.variance * s.cost_price) AS waste_cost,
                       AVG(pcl.variance_percent) AS avg_variance_percent,
                       COUNT(*) AS occurrences
                FROM production_consumption_log pcl
                JOIN supplies s ON s.id = pcl.supply_id
                {$where}
                GROUP BY pcl.supply_id, s.name, s.code, s.unit_measure, s.cost_price
                ORDER BY waste_cost DESC
                LIMIT :lim";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna dados para gráfico previsto vs real por período.
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getChartData(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = "WHERE pcl.actual_quantity IS NOT NULL";
        $params = [];

        if ($dateFrom) {
            $where .= " AND pcl.created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $where .= " AND pcl.created_at <= :date_to";
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        $sql = "SELECT DATE(pcl.created_at) AS day,
                       SUM(pcl.planned_quantity) AS planned,
                       SUM(pcl.actual_quantity) AS actual
                FROM production_consumption_log pcl
                {$where}
                GROUP BY DATE(pcl.created_at)
                ORDER BY day ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
