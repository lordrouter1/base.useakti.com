<?php

namespace Akti\Services;

use PDO;
use Akti\Models\Supply;

/**
 * Service de alertas de custo e margem de insumos (v2).
 */
class SupplyCostService
{
    private PDO $db;
    private Supply $supplyModel;

    public function __construct(PDO $db, Supply $supplyModel)
    {
        $this->db = $db;
        $this->supplyModel = $supplyModel;
    }

    /**
     * Verifica impacto de alteração de custo em todos os produtos que usam o insumo.
     * Gera alertas em supply_cost_alerts quando margem cai abaixo do threshold.
     *
     * @param int   $supplyId ID do insumo
     * @param float $oldCost  Custo anterior
     * @param float $newCost  Novo custo (CMP recalculado)
     * @return array Alertas gerados
     */
    public function checkMarginImpact(int $supplyId, float $oldCost, float $newCost): array
    {
        if ($oldCost == $newCost) {
            return [];
        }

        $settings = $this->getSettings();
        $threshold = (float) ($settings['min_margin_threshold'] ?? 15.00);

        // Buscar produtos que usam este insumo (não opcionais)
        $stmt = $this->db->prepare(
            "SELECT ps.product_id, ps.quantity, ps.yield_qty, ps.waste_percent, ps.loss_percent,
                    p.name AS product_name, p.price AS current_price, p.cost_price AS product_cost_price
             FROM product_supplies ps
             JOIN products p ON p.id = ps.product_id
             WHERE ps.supply_id = :supply_id AND ps.is_optional = 0"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $alerts = [];

        foreach ($products as $product) {
            $currentPrice = (float) $product['current_price'];
            if ($currentPrice <= 0) {
                continue;
            }

            // Calcular custo de produção antigo e novo
            $oldProductCost = $this->calculateProductCostWithOverride($product['product_id'], $supplyId, $oldCost);
            $newProductCost = $this->calculateProductCostWithOverride($product['product_id'], $supplyId, $newCost);

            $oldMargin = (($currentPrice - $oldProductCost) / $currentPrice) * 100;
            $newMargin = (($currentPrice - $newProductCost) / $currentPrice) * 100;

            if ($newMargin < $threshold) {
                $suggestedPrice = $this->suggestPrice($newProductCost, $threshold);

                $insertStmt = $this->db->prepare(
                    "INSERT INTO supply_cost_alerts
                        (product_id, supply_id, old_cost, new_cost, old_product_cost, new_product_cost,
                         current_price, old_margin, new_margin, margin_threshold, suggested_price, tenant_id)
                     VALUES (:product_id, :supply_id, :old_cost, :new_cost, :old_prod_cost, :new_prod_cost,
                         :price, :old_margin, :new_margin, :threshold, :suggested,
                         (SELECT tenant_id FROM supplies WHERE id = :sid LIMIT 1))"
                );
                $insertStmt->execute([
                    ':product_id'   => $product['product_id'],
                    ':supply_id'    => $supplyId,
                    ':old_cost'     => $oldCost,
                    ':new_cost'     => $newCost,
                    ':old_prod_cost' => $oldProductCost,
                    ':new_prod_cost' => $newProductCost,
                    ':price'        => $currentPrice,
                    ':old_margin'   => round($oldMargin, 2),
                    ':new_margin'   => round($newMargin, 2),
                    ':threshold'    => $threshold,
                    ':suggested'    => $suggestedPrice,
                    ':sid'          => $supplyId,
                ]);

                $alerts[] = [
                    'id'              => (int) $this->db->lastInsertId(),
                    'product_id'      => $product['product_id'],
                    'product_name'    => $product['product_name'],
                    'old_margin'      => round($oldMargin, 2),
                    'new_margin'      => round($newMargin, 2),
                    'suggested_price' => $suggestedPrice,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Calcula preço sugerido para manter margem mínima.
     *
     * @param float $productCost Custo de produção
     * @param float $marginPercent Margem mínima desejada (%)
     * @return float
     */
    public function suggestPrice(float $productCost, float $marginPercent): float
    {
        if ($marginPercent >= 100) {
            return $productCost * 2;
        }
        return round($productCost / (1 - $marginPercent / 100), 4);
    }

    /**
     * Retorna alertas pendentes.
     *
     * @param string $status Filtro de status
     * @param int    $limit  Limite
     * @return array
     */
    public function getAlerts(string $status = 'pending', int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT sca.*, p.name AS product_name, s.name AS supply_name, s.code AS supply_code
             FROM supply_cost_alerts sca
             JOIN products p ON p.id = sca.product_id
             JOIN supplies s ON s.id = sca.supply_id
             WHERE sca.status = :status
             ORDER BY sca.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza status de um alerta.
     *
     * @param int    $alertId
     * @param string $status
     * @param int|null $userId
     * @return bool
     */
    public function updateAlertStatus(int $alertId, string $status, ?int $userId = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE supply_cost_alerts
             SET status = :status, acknowledged_by = :user, acknowledged_at = NOW()
             WHERE id = :id"
        );
        return $stmt->execute([':status' => $status, ':user' => $userId, ':id' => $alertId]);
    }

    /**
     * Aplica preço sugerido ao produto.
     *
     * @param int $alertId
     * @param int $userId
     * @return bool
     */
    public function applyAlertPrice(int $alertId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT product_id, suggested_price FROM supply_cost_alerts WHERE id = :id");
        $stmt->execute([':id' => $alertId]);
        $alert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alert || !$alert['suggested_price']) {
            return false;
        }

        $updateStmt = $this->db->prepare("UPDATE products SET price = :price WHERE id = :id");
        $updateStmt->execute([':price' => $alert['suggested_price'], ':id' => $alert['product_id']]);

        $this->updateAlertStatus($alertId, 'applied', $userId);

        return true;
    }

    /**
     * Retorna configurações do módulo de insumos.
     *
     * @return array
     */
    public function getSettings(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM supply_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        return $settings ?: [
            'min_margin_threshold'        => 15.00,
            'forecast_calculation_method' => 'weighted',
            'allow_negative_stock'        => 0,
            'default_fefo_strategy'       => 'fefo',
            'auto_recalculate_cmp'        => 1,
            'default_decimal_precision'   => 4,
        ];
    }

    /**
     * Salva configurações do módulo.
     *
     * @param array $data
     * @return bool
     */
    public function saveSettings(array $data): bool
    {
        $existing = $this->db->prepare("SELECT id FROM supply_settings LIMIT 1");
        $existing->execute();
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $stmt = $this->db->prepare(
                "UPDATE supply_settings SET
                    min_margin_threshold = :threshold,
                    forecast_calculation_method = :forecast_method,
                    allow_negative_stock = :neg_stock,
                    default_fefo_strategy = :fefo,
                    auto_recalculate_cmp = :auto_cmp,
                    default_decimal_precision = :precision
                 WHERE id = :id"
            );
            return $stmt->execute([
                ':threshold'       => $data['min_margin_threshold'] ?? 15.00,
                ':forecast_method' => $data['forecast_calculation_method'] ?? 'weighted',
                ':neg_stock'       => $data['allow_negative_stock'] ?? 0,
                ':fefo'            => $data['default_fefo_strategy'] ?? 'fefo',
                ':auto_cmp'        => $data['auto_recalculate_cmp'] ?? 1,
                ':precision'       => $data['default_decimal_precision'] ?? 4,
                ':id'              => $row['id'],
            ]);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO supply_settings
                (min_margin_threshold, forecast_calculation_method, allow_negative_stock,
                 default_fefo_strategy, auto_recalculate_cmp, default_decimal_precision, tenant_id)
             VALUES (:threshold, :forecast_method, :neg_stock, :fefo, :auto_cmp, :precision,
                 (SELECT id FROM `akti_master`.`tenant_clients` LIMIT 1))"
        );
        return $stmt->execute([
            ':threshold'       => $data['min_margin_threshold'] ?? 15.00,
            ':forecast_method' => $data['forecast_calculation_method'] ?? 'weighted',
            ':neg_stock'       => $data['allow_negative_stock'] ?? 0,
            ':fefo'            => $data['default_fefo_strategy'] ?? 'fefo',
            ':auto_cmp'        => $data['auto_recalculate_cmp'] ?? 1,
            ':precision'       => $data['default_decimal_precision'] ?? 4,
        ]);
    }

    /**
     * Calcula custo de produção de um produto sobrescrevendo o custo de um insumo.
     *
     * @param int   $productId
     * @param int   $overrideSupplyId
     * @param float $overrideCost
     * @return float
     */
    private function calculateProductCostWithOverride(int $productId, int $overrideSupplyId, float $overrideCost): float
    {
        $stmt = $this->db->prepare(
            "SELECT ps.supply_id, ps.quantity, ps.yield_qty, ps.waste_percent, ps.loss_percent,
                    s.cost_price
             FROM product_supplies ps
             JOIN supplies s ON s.id = ps.supply_id
             WHERE ps.product_id = :product_id AND ps.is_optional = 0"
        );
        $stmt->execute([':product_id' => $productId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0.0;
        foreach ($items as $item) {
            $cost = ((int) $item['supply_id'] === $overrideSupplyId) ? $overrideCost : (float) $item['cost_price'];
            $yieldQty = max((float) ($item['yield_qty'] ?? 1), 0.0001);
            $loss = ((float) ($item['loss_percent'] ?? 0)) > 0 ? (float) $item['loss_percent'] : (float) $item['waste_percent'];
            $perUnit = (float) $item['quantity'] / $yieldQty;
            $effective = $perUnit * (1 + $loss / 100);
            $total += $effective * $cost;
        }

        return round($total, 4);
    }
}
