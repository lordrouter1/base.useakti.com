<?php

namespace Akti\Services;

use Akti\Models\SupplyStock;
use Akti\Models\Supply;
use Akti\Models\Logger;
use Akti\Utils\Sanitizer;
use PDO;

class SupplyStockMovementService
{
    private PDO $db;
    private SupplyStock $stockModel;
    private Supply $supplyModel;
    private Logger $logger;

    private const TYPE_LABELS = [
        'entrada'       => 'Entrada',
        'saida'         => 'Saída',
        'ajuste'        => 'Ajuste',
        'transferencia' => 'Transferência',
    ];

    public function __construct(PDO $db, SupplyStock $stockModel, Supply $supplyModel, Logger $logger)
    {
        $this->db = $db;
        $this->stockModel = $stockModel;
        $this->supplyModel = $supplyModel;
        $this->logger = $logger;
    }

    /**
     * Processa entrada de insumos no estoque com suporte a lote/validade e CMP.
     */
    public function processEntry(
        int $warehouseId,
        ?string $reason,
        array $items,
        int $createdBy
    ): array {
        $processed = 0;
        $errors = [];

        if (empty($items)) {
            return ['success' => false, 'processed' => 0, 'errors' => ['Nenhum item informado.'], 'message' => 'Nenhum item informado.'];
        }

        foreach ($items as $i => $item) {
            try {
                $supplyId  = Sanitizer::int($item['supply_id'] ?? 0);
                $quantity  = Sanitizer::float($item['quantity'] ?? 0);
                $unitPrice = Sanitizer::float($item['unit_price'] ?? 0);
                $batch     = isset($item['batch_number']) ? trim($item['batch_number']) : null;
                $expiryDate = isset($item['expiry_date']) && $item['expiry_date'] !== '' ? $item['expiry_date'] : null;

                if ($supplyId <= 0 || $quantity <= 0) {
                    $errors[] = "Item {$i}: insumo ou quantidade inválida.";
                    continue;
                }

                // Get or create stock item (possibly with batch)
                $stockItem = $this->stockModel->getOrCreateItem($warehouseId, $supplyId, $batch ?: null);

                // Update expiry_date if provided and item has no date yet
                if ($expiryDate && empty($stockItem['expiry_date'])) {
                    $stmt = $this->db->prepare("UPDATE supply_stock_items SET expiry_date = :exp WHERE id = :id");
                    $stmt->execute([':exp' => $expiryDate, ':id' => $stockItem['id']]);
                }

                // Update min quantities if provided
                if (isset($item['min_quantity'])) {
                    $stmt = $this->db->prepare("UPDATE supply_stock_items SET min_quantity = :mq WHERE id = :id");
                    $stmt->execute([':mq' => Sanitizer::float($item['min_quantity']), ':id' => $stockItem['id']]);
                }

                // Calcular CMP (Custo Médio Ponderado)
                $supply = $this->supplyModel->readOne($supplyId);
                if ($supply && $unitPrice > 0) {
                    $currentStock = $this->stockModel->getTotalStock($supplyId);
                    $currentCost = (float) ($supply['cost_price'] ?? 0);
                    $newCmp = $this->calculateWeightedAverageCost($currentStock, $currentCost, $quantity, $unitPrice);
                    $this->supplyModel->updateCostPrice($supplyId, $newCmp);

                    // Registrar histórico de preço
                    $this->supplyModel->recordPriceHistory($supplyId, null, $unitPrice, 'entrada');
                }

                // Atualizar quantidade
                $newQuantity = (float) $stockItem['quantity'] + $quantity;
                $this->stockModel->updateQuantity($stockItem['id'], $newQuantity);

                // Registrar movimentação
                $this->stockModel->addMovement([
                    'warehouse_id'   => $warehouseId,
                    'supply_id'      => $supplyId,
                    'type'           => 'entrada',
                    'quantity'        => $quantity,
                    'unit_price'     => $unitPrice,
                    'batch_number'   => $batch,
                    'reason'         => $reason,
                    'created_by'     => $createdBy,
                ]);

                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Item {$i}: " . $e->getMessage();
            }
        }

        $label = self::TYPE_LABELS['entrada'];
        if ($processed > 0) {
            $this->logger->log('SUPPLY_STOCK_ENTRY', "{$label} de {$processed} insumo(s) no estoque");
        }

        return [
            'success'   => $processed > 0,
            'processed' => $processed,
            'errors'    => $errors,
            'message'   => $processed > 0 ? "{$label} de {$processed} item(ns) realizada." : 'Nenhum item processado.',
        ];
    }

    /**
     * Processa saída de insumos do estoque com FEFO automático.
     */
    public function processExit(
        int $warehouseId,
        ?string $reason,
        array $items,
        int $createdBy,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): array {
        $processed = 0;
        $errors = [];

        if (empty($items)) {
            return ['success' => false, 'processed' => 0, 'errors' => ['Nenhum item informado.'], 'message' => 'Nenhum item informado.'];
        }

        foreach ($items as $i => $item) {
            try {
                $supplyId = Sanitizer::int($item['supply_id'] ?? 0);
                $quantity = Sanitizer::float($item['quantity'] ?? 0);

                if ($supplyId <= 0 || $quantity <= 0) {
                    $errors[] = "Item {$i}: insumo ou quantidade inválida.";
                    continue;
                }

                // Verificar estoque suficiente
                if (!$this->validateSufficientStock($warehouseId, $supplyId, $quantity)) {
                    $errors[] = "Item {$i}: estoque insuficiente.";
                    continue;
                }

                // FEFO: consumir dos lotes mais antigos/próximos do vencimento
                $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
                $remaining = $quantity;

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;

                    $consume = min($remaining, (float) $batch['quantity']);
                    $newQty = (float) $batch['quantity'] - $consume;
                    $this->stockModel->updateQuantity($batch['id'], $newQty);
                    $remaining -= $consume;

                    // Registrar movimentação por lote
                    $this->stockModel->addMovement([
                        'warehouse_id'   => $warehouseId,
                        'supply_id'      => $supplyId,
                        'type'           => 'saida',
                        'quantity'        => $consume,
                        'unit_price'     => null,
                        'batch_number'   => $batch['batch_number'],
                        'reason'         => $reason,
                        'reference_type' => $referenceType,
                        'reference_id'   => $referenceId,
                        'created_by'     => $createdBy,
                    ]);
                }

                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Item {$i}: " . $e->getMessage();
            }
        }

        $label = self::TYPE_LABELS['saida'];
        if ($processed > 0) {
            $this->logger->log('SUPPLY_STOCK_EXIT', "{$label} de {$processed} insumo(s) do estoque");
        }

        // Verificar alertas de reorder
        $this->checkReorderAlerts();

        return [
            'success'   => $processed > 0,
            'processed' => $processed,
            'errors'    => $errors,
            'message'   => $processed > 0 ? "{$label} de {$processed} item(ns) realizada." : 'Nenhum item processado.',
        ];
    }

    /**
     * Processa ajuste de estoque (inventário).
     */
    public function processAdjustment(
        int $warehouseId,
        ?string $reason,
        array $items,
        int $createdBy
    ): array {
        $processed = 0;
        $errors = [];

        if (empty($items)) {
            return ['success' => false, 'processed' => 0, 'errors' => ['Nenhum item informado.'], 'message' => 'Nenhum item informado.'];
        }

        foreach ($items as $i => $item) {
            try {
                $supplyId    = Sanitizer::int($item['supply_id'] ?? 0);
                $newQuantity = Sanitizer::float($item['new_quantity'] ?? 0);

                if ($supplyId <= 0) {
                    $errors[] = "Item {$i}: insumo inválido.";
                    continue;
                }

                $stockItem = $this->stockModel->getOrCreateItem($warehouseId, $supplyId);
                $oldQuantity = (float) $stockItem['quantity'];
                $diff = $newQuantity - $oldQuantity;

                $this->stockModel->updateQuantity($stockItem['id'], $newQuantity);

                $this->stockModel->addMovement([
                    'warehouse_id' => $warehouseId,
                    'supply_id'    => $supplyId,
                    'type'         => 'ajuste',
                    'quantity'     => $diff,
                    'reason'       => $reason ?? "Ajuste de {$oldQuantity} para {$newQuantity}",
                    'created_by'   => $createdBy,
                ]);

                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Item {$i}: " . $e->getMessage();
            }
        }

        $label = self::TYPE_LABELS['ajuste'];
        if ($processed > 0) {
            $this->logger->log('SUPPLY_STOCK_ADJUST', "{$label} de {$processed} insumo(s)");
        }

        return [
            'success'   => $processed > 0,
            'processed' => $processed,
            'errors'    => $errors,
            'message'   => $processed > 0 ? "{$label} de {$processed} item(ns) realizado." : 'Nenhum item processado.',
        ];
    }

    /**
     * Processa transferência entre armazéns.
     */
    public function processTransfer(
        int $originWarehouseId,
        int $destWarehouseId,
        ?string $reason,
        array $items,
        int $createdBy
    ): array {
        $processed = 0;
        $errors = [];

        if ($originWarehouseId === $destWarehouseId) {
            return ['success' => false, 'processed' => 0, 'errors' => ['Origem e destino devem ser diferentes.'], 'message' => 'Origem e destino devem ser diferentes.'];
        }
        if (empty($items)) {
            return ['success' => false, 'processed' => 0, 'errors' => ['Nenhum item informado.'], 'message' => 'Nenhum item informado.'];
        }

        foreach ($items as $i => $item) {
            try {
                $supplyId = Sanitizer::int($item['supply_id'] ?? 0);
                $quantity = Sanitizer::float($item['quantity'] ?? 0);
                $batch    = isset($item['batch_number']) ? trim($item['batch_number']) : null;

                if ($supplyId <= 0 || $quantity <= 0) {
                    $errors[] = "Item {$i}: insumo ou quantidade inválida.";
                    continue;
                }

                if (!$this->validateSufficientStock($originWarehouseId, $supplyId, $quantity)) {
                    $errors[] = "Item {$i}: estoque insuficiente na origem.";
                    continue;
                }

                // Saída da origem
                $originItem = $this->stockModel->getOrCreateItem($originWarehouseId, $supplyId, $batch);
                $this->stockModel->updateQuantity($originItem['id'], (float) $originItem['quantity'] - $quantity);

                // Entrada no destino
                $destItem = $this->stockModel->getOrCreateItem($destWarehouseId, $supplyId, $batch);
                $this->stockModel->updateQuantity($destItem['id'], (float) $destItem['quantity'] + $quantity);

                // Movimentações
                $this->stockModel->addMovement([
                    'warehouse_id' => $originWarehouseId,
                    'supply_id'    => $supplyId,
                    'type'         => 'transferencia',
                    'quantity'     => -$quantity,
                    'batch_number' => $batch,
                    'reason'       => $reason ?? 'Transferência para outro armazém',
                    'created_by'   => $createdBy,
                ]);
                $this->stockModel->addMovement([
                    'warehouse_id' => $destWarehouseId,
                    'supply_id'    => $supplyId,
                    'type'         => 'transferencia',
                    'quantity'     => $quantity,
                    'batch_number' => $batch,
                    'reason'       => $reason ?? 'Transferência recebida',
                    'created_by'   => $createdBy,
                ]);

                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Item {$i}: " . $e->getMessage();
            }
        }

        $label = self::TYPE_LABELS['transferencia'];
        if ($processed > 0) {
            $this->logger->log('SUPPLY_STOCK_TRANSFER', "{$label} de {$processed} insumo(s)");
        }

        return [
            'success'   => $processed > 0,
            'processed' => $processed,
            'errors'    => $errors,
            'message'   => $processed > 0 ? "{$label} de {$processed} item(ns) realizada." : 'Nenhum item processado.',
        ];
    }

    // ──── Métodos auxiliares ────

    public function validateSufficientStock(int $warehouseId, int $supplyId, float $quantity): bool
    {
        $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
        $total = 0.0;
        foreach ($batches as $b) {
            $total += (float) $b['quantity'];
        }
        return $total >= $quantity;
    }

    public function calculateWeightedAverageCost(float $currentStock, float $currentCost, float $newQty, float $newPrice): float
    {
        $totalQty = $currentStock + $newQty;
        if ($totalQty <= 0) {
            return $newPrice;
        }
        return (($currentStock * $currentCost) + ($newQty * $newPrice)) / $totalQty;
    }

    public function checkReorderAlerts(): array
    {
        $reorderItems = $this->stockModel->getReorderItems();
        // Alertas podem ser consumidos pela view via session ou evento futuro
        if (!empty($reorderItems)) {
            $_SESSION['supply_reorder_alerts'] = count($reorderItems);
        }
        return $reorderItems;
    }

    /**
     * Sugere o próximo lote para saída via FEFO.
     */
    public function suggestBatchForExit(int $supplyId, int $warehouseId): ?array
    {
        $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
        return !empty($batches) ? $batches[0] : null;
    }
}
