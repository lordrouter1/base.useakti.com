<?php

namespace Akti\Services;

use PDO;

class ProductionCostService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function calculateOrderCost(int $orderId, int $tenantId): array
    {
        $materialCost = $this->getMaterialCost($orderId, $tenantId);
        $laborCost = $this->getLaborCost($orderId, $tenantId);
        $overhead = $this->getOverheadCost($tenantId, $materialCost + $laborCost);
        $totalCost = $materialCost + $laborCost + $overhead;

        $estimated = $this->getEstimatedCost($orderId, $tenantId);

        $stmt = $this->db->prepare("
            INSERT INTO production_costs (tenant_id, order_id, material_cost, labor_cost, overhead_cost, total_cost, estimated_cost, production_time_minutes)
            VALUES (:tid, :oid, :material, :labor, :overhead, :total, :estimated, :time)
            ON DUPLICATE KEY UPDATE material_cost = :material2, labor_cost = :labor2, overhead_cost = :overhead2, total_cost = :total2, calculated_at = NOW()
        ");
        $time = $this->getProductionTimeMinutes($orderId, $tenantId);
        $stmt->execute([
            ':tid'       => $tenantId,
            ':oid'       => $orderId,
            ':material'  => $materialCost,
            ':labor'     => $laborCost,
            ':overhead'  => $overhead,
            ':total'     => $totalCost,
            ':estimated' => $estimated,
            ':time'      => $time,
            ':material2' => $materialCost,
            ':labor2'    => $laborCost,
            ':overhead2' => $overhead,
            ':total2'    => $totalCost,
        ]);

        return [
            'material_cost'  => $materialCost,
            'labor_cost'     => $laborCost,
            'overhead_cost'  => $overhead,
            'total_cost'     => $totalCost,
            'estimated_cost' => $estimated,
            'variance'       => $estimated > 0 ? round((($totalCost - $estimated) / $estimated) * 100, 2) : 0,
            'production_time_minutes' => $time,
        ];
    }

    public function getOrderCost(int $orderId, int $tenantId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM production_costs WHERE order_id = :oid AND tenant_id = :tid ORDER BY calculated_at DESC LIMIT 1");
        $stmt->execute([':oid' => $orderId, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getConfig(int $tenantId, ?int $sectorId = null): array
    {
        $where = 'tenant_id = :tid';
        $params = [':tid' => $tenantId];
        if ($sectorId) {
            $where .= ' AND sector_id = :sid';
            $params[':sid'] = $sectorId;
        } else {
            $where .= ' AND sector_id IS NULL';
        }
        $stmt = $this->db->prepare("SELECT * FROM production_cost_configs WHERE {$where} LIMIT 1");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['labor_cost_per_hour' => 0, 'overhead_type' => 'percentage', 'overhead_value' => 0];
    }

    public function saveConfig(int $tenantId, array $data): bool
    {
        $sectorId = $data['sector_id'] ?? null;
        $existing = $this->getConfig($tenantId, $sectorId);

        if (!empty($existing['id'])) {
            $stmt = $this->db->prepare("UPDATE production_cost_configs SET labor_cost_per_hour = :labor, overhead_type = :type, overhead_value = :val WHERE id = :id");
            return $stmt->execute([
                ':labor' => $data['labor_cost_per_hour'],
                ':type'  => $data['overhead_type'],
                ':val'   => $data['overhead_value'],
                ':id'    => $existing['id'],
            ]);
        }
        $stmt = $this->db->prepare("
            INSERT INTO production_cost_configs (tenant_id, sector_id, labor_cost_per_hour, overhead_type, overhead_value)
            VALUES (:tid, :sid, :labor, :type, :val)
        ");
        return $stmt->execute([
            ':tid'   => $tenantId,
            ':sid'   => $sectorId,
            ':labor' => $data['labor_cost_per_hour'],
            ':type'  => $data['overhead_type'],
            ':val'   => $data['overhead_value'],
        ]);
    }

    public function getMarginReport(int $tenantId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = 'pc.tenant_id = :tid';
        $params = [':tid' => $tenantId];
        if ($dateFrom) {
            $where .= ' AND pc.calculated_at >= :from';
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $where .= ' AND pc.calculated_at <= :to';
            $params[':to'] = $dateTo . ' 23:59:59';
        }

        $stmt = $this->db->prepare("
            SELECT pc.*, p.total_price AS sale_price,
                   (p.total_price - pc.total_cost) AS margin,
                   CASE WHEN p.total_price > 0 THEN ROUND(((p.total_price - pc.total_cost) / p.total_price) * 100, 2) ELSE 0 END AS margin_pct
            FROM production_costs pc
            LEFT JOIN pipeline p ON pc.order_id = p.id
            WHERE {$where}
            ORDER BY pc.calculated_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMaterialCost(int $orderId, int $tenantId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(ps.quantity * s.unit_cost), 0)
            FROM product_supplies ps
            JOIN supplies s ON ps.supply_id = s.id
            JOIN pipeline_items pi ON ps.product_id = pi.product_id
            WHERE pi.pipeline_id = :oid AND s.deleted_at IS NULL
        ");
        $stmt->execute([':oid' => $orderId]);
        return (float) $stmt->fetchColumn();
    }

    private function getLaborCost(int $orderId, int $tenantId): float
    {
        $timeMinutes = $this->getProductionTimeMinutes($orderId, $tenantId);
        $config = $this->getConfig($tenantId);
        $hourlyRate = (float) $config['labor_cost_per_hour'];
        return round(($timeMinutes / 60) * $hourlyRate, 2);
    }

    private function getOverheadCost(int $tenantId, float $directCost): float
    {
        $config = $this->getConfig($tenantId);
        if ($config['overhead_type'] === 'percentage') {
            return round($directCost * (float) $config['overhead_value'] / 100, 2);
        }
        return (float) $config['overhead_value'];
    }

    private function getEstimatedCost(int $orderId, int $tenantId): float
    {
        $stmt = $this->db->prepare("SELECT estimated_cost FROM production_costs WHERE order_id = :oid AND tenant_id = :tid ORDER BY id ASC LIMIT 1");
        $stmt->execute([':oid' => $orderId, ':tid' => $tenantId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float) $val : 0;
    }

    private function getProductionTimeMinutes(int $orderId, int $tenantId): int
    {
        $stmt = $this->db->prepare("
            SELECT TIMESTAMPDIFF(MINUTE, MIN(pl.created_at), MAX(pl.created_at))
            FROM pipeline_logs pl
            WHERE pl.pipeline_id = :oid
        ");
        $stmt->execute([':oid' => $orderId]);
        return (int) $stmt->fetchColumn();
    }
}
