<?php

namespace Akti\Services;

use PDO;
use Akti\Models\Supply;
use Akti\Models\SupplyStock;

/**
 * Service de cálculos inteligentes de insumos (v2).
 * Fracionamento, BOM com variação, custo de produção, disponibilidade.
 */
class InsumoService
{
    private PDO $db;
    private Supply $supplyModel;
    private SupplyStock $stockModel;

    public function __construct(PDO $db, Supply $supplyModel, SupplyStock $stockModel)
    {
        $this->db = $db;
        $this->supplyModel = $supplyModel;
        $this->stockModel = $stockModel;
    }

    /**
     * Calcula a quantidade efetiva considerando fracionamento e perda.
     *
     * @param float $baseQtyPerUnit Consumo por unidade (ratio)
     * @param int   $lotSize        Tamanho do lote
     * @param float $lossPercent    % de perda
     * @param bool  $allowsFractionation Se permite fracionamento
     * @param int   $precision      Casas decimais (2-6)
     * @return float
     */
    public function calculateEffectiveQuantity(
        float $baseQtyPerUnit,
        int $lotSize,
        float $lossPercent,
        bool $allowsFractionation,
        int $precision = 4
    ): float {
        $total = $baseQtyPerUnit * $lotSize;
        $withLoss = $total * (1 + $lossPercent / 100);

        if ($allowsFractionation) {
            return round($withLoss, $precision);
        }

        return (float) ceil($withLoss);
    }

    /**
     * Calcula BOM completa para um lote, com herança de variação.
     * Variação faz override de insumos do pai (mesmo supply_id) e adiciona os novos.
     *
     * @param int      $productId   ID do produto
     * @param int      $lotSize     Tamanho do lote
     * @param int|null $variationId ID da variação (null = produto pai)
     * @return array Lista de insumos com quantidade efetiva calculada
     */
    public function calculateBomForLot(int $productId, int $lotSize, ?int $variationId = null): array
    {
        // Buscar insumos do produto pai (variation_id IS NULL)
        $parentItems = $this->getBomItems($productId, null);

        // Se não há variação, usar apenas os do pai
        if ($variationId === null) {
            return $this->applyLotCalculation($parentItems, $lotSize);
        }

        // Buscar insumos específicos da variação
        $variationItems = $this->getBomItems($productId, $variationId);

        // Merge: variação faz override do pai por supply_id
        $merged = [];
        $variationSupplyIds = [];

        foreach ($variationItems as $item) {
            $variationSupplyIds[] = (int) $item['supply_id'];
            $merged[] = $item;
        }

        // Herdar do pai os que não foram overridden
        foreach ($parentItems as $item) {
            if (!in_array((int) $item['supply_id'], $variationSupplyIds, true)) {
                $merged[] = $item;
            }
        }

        return $this->applyLotCalculation($merged, $lotSize);
    }

    /**
     * Calcula o custo de produção baseado em CMP × BOM.
     *
     * @param int      $productId   ID do produto
     * @param int      $lotSize     Tamanho do lote (default 1)
     * @param int|null $variationId ID da variação
     * @return array ['total_cost' => float, 'unit_cost' => float, 'items' => array]
     */
    public function calculateProductionCost(int $productId, int $lotSize = 1, ?int $variationId = null): array
    {
        $bomItems = $this->calculateBomForLot($productId, $lotSize, $variationId);
        $totalCost = 0.0;

        foreach ($bomItems as &$item) {
            if (!$item['is_optional']) {
                $item['line_cost'] = round($item['effective_qty'] * (float) $item['cost_price'], 4);
                $totalCost += $item['line_cost'];
            } else {
                $item['line_cost'] = 0.0;
            }
        }
        unset($item);

        return [
            'total_cost' => round($totalCost, 4),
            'unit_cost'  => $lotSize > 0 ? round($totalCost / $lotSize, 4) : 0,
            'items'      => $bomItems,
        ];
    }

    /**
     * Verifica disponibilidade de estoque para produzir N unidades.
     *
     * @param int      $productId   ID do produto
     * @param int      $lotSize     Quantidade a produzir
     * @param int|null $variationId ID da variação
     * @param int|null $warehouseId Depósito (null = todos)
     * @return array ['available' => bool, 'items' => array com status por insumo]
     */
    public function checkAvailability(int $productId, int $lotSize, ?int $variationId = null, ?int $warehouseId = null): array
    {
        $bomItems = $this->calculateBomForLot($productId, $lotSize, $variationId);
        $allAvailable = true;
        $results = [];

        foreach ($bomItems as $item) {
            if ($item['is_optional']) {
                continue;
            }

            $supplyId = (int) $item['supply_id'];
            $needed = $item['effective_qty'];

            // Buscar estoque disponível
            $stock = $this->getAvailableStock($supplyId, $warehouseId);

            $sufficient = $stock >= $needed;
            if (!$sufficient) {
                $allAvailable = false;
            }

            $results[] = [
                'supply_id'    => $supplyId,
                'supply_name'  => $item['supply_name'],
                'supply_code'  => $item['supply_code'],
                'unit_measure' => $item['supply_unit'],
                'needed'       => $needed,
                'available'    => $stock,
                'shortage'     => $sufficient ? 0 : round($needed - $stock, 4),
                'sufficient'   => $sufficient,
            ];
        }

        return [
            'available' => $allAvailable,
            'items'     => $results,
        ];
    }

    /**
     * Busca itens BOM de um produto para uma variação específica (ou pai).
     *
     * @param int      $productId
     * @param int|null $variationId NULL para itens do produto pai
     * @return array
     */
    private function getBomItems(int $productId, ?int $variationId): array
    {
        if ($variationId === null) {
            $sql = "SELECT ps.*, s.name AS supply_name, s.code AS supply_code,
                           s.cost_price, s.unit_measure AS supply_unit,
                           s.permite_fracionamento, s.decimal_precision, s.waste_percent AS supply_waste_percent
                    FROM product_supplies ps
                    JOIN supplies s ON s.id = ps.supply_id
                    WHERE ps.product_id = :product_id AND ps.variation_id IS NULL
                    ORDER BY ps.sort_order, s.name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':product_id' => $productId]);
        } else {
            $sql = "SELECT ps.*, s.name AS supply_name, s.code AS supply_code,
                           s.cost_price, s.unit_measure AS supply_unit,
                           s.permite_fracionamento, s.decimal_precision, s.waste_percent AS supply_waste_percent
                    FROM product_supplies ps
                    JOIN supplies s ON s.id = ps.supply_id
                    WHERE ps.product_id = :product_id AND ps.variation_id = :variation_id
                    ORDER BY ps.sort_order, s.name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':product_id' => $productId, ':variation_id' => $variationId]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aplica cálculo de lote a cada item BOM.
     *
     * @param array $items
     * @param int   $lotSize
     * @return array
     */
    private function applyLotCalculation(array $items, int $lotSize): array
    {
        foreach ($items as &$item) {
            $yieldQty = max((float) ($item['yield_qty'] ?? 1), 0.0001);
            $baseQtyPerUnit = (float) $item['quantity'] / $yieldQty;

            // loss_percent do vínculo (override) ou waste_percent do insumo (fallback)
            $lossPercent = ((float) ($item['loss_percent'] ?? 0)) > 0
                ? (float) $item['loss_percent']
                : (float) $item['waste_percent'];

            $allowsFractionation = (bool) ($item['permite_fracionamento'] ?? true);
            $precision = (int) ($item['decimal_precision'] ?? 4);

            $item['base_qty_per_unit'] = round($baseQtyPerUnit, $precision);
            $item['effective_qty'] = $this->calculateEffectiveQuantity(
                $baseQtyPerUnit,
                $lotSize,
                $lossPercent,
                $allowsFractionation,
                $precision
            );
            $item['loss_applied'] = $lossPercent;
        }
        unset($item);

        return $items;
    }

    /**
     * Retorna estoque disponível para um insumo.
     *
     * @param int      $supplyId
     * @param int|null $warehouseId
     * @return float
     */
    private function getAvailableStock(int $supplyId, ?int $warehouseId): float
    {
        if ($warehouseId !== null) {
            $sql = "SELECT COALESCE(SUM(quantity), 0)
                    FROM supply_stock_items
                    WHERE supply_id = :supply_id AND warehouse_id = :warehouse_id AND quantity > 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':supply_id' => $supplyId, ':warehouse_id' => $warehouseId]);
        } else {
            $sql = "SELECT COALESCE(SUM(quantity), 0)
                    FROM supply_stock_items
                    WHERE supply_id = :supply_id AND quantity > 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':supply_id' => $supplyId]);
        }

        return (float) $stmt->fetchColumn();
    }
}
