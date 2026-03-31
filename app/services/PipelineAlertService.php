<?php
namespace Akti\Services;

use Akti\Models\Pipeline;
use Akti\Models\Financial;
use Akti\Models\Order;
use Akti\Models\Logger;
use PDO;

/**
 * Service responsável pela lógica de alertas e atrasos do pipeline.
 * Extraído do PipelineController (Fase 2 — Refatoração de Controllers Monolíticos).
 */
class PipelineAlertService
{
    private $db;
    private $pipelineModel;

    public function __construct(PDO $db, Pipeline $pipelineModel)
    {
        $this->db = $db;
        $this->pipelineModel = $pipelineModel;
    }

    /**
     * Retorna pedidos atrasados para notificações.
     *
     * @return array ['delayed' => array, 'count' => int]
     */
    public function getDelayedOrders(): array
    {
        $delayed = $this->pipelineModel->getDelayedOrders();
        return [
            'delayed' => $delayed,
            'count'   => count($delayed),
        ];
    }

    /**
     * Retorna estatísticas do pipeline (stats gerais).
     */
    public function getStats(): array
    {
        return $this->pipelineModel->getStats();
    }

    /**
     * Retorna metas de tempo por etapa.
     */
    public function getStageGoals(): array
    {
        return $this->pipelineModel->getStageGoals();
    }

    /**
     * Verifica disponibilidade de estoque dos itens de um pedido num armazém.
     *
     * @param int $orderId
     * @param int|null $warehouseId
     * @param \Akti\Models\Stock $stockModel
     * @return array Resultado com warehouses, items, all_from_stock
     */
    public function checkOrderStock(int $orderId, ?int $warehouseId, $stockModel): array
    {
        $orderModel = new Order($this->db);
        $productModel = new \Akti\Models\Product($this->db);
        $orderItems = $orderModel->getItems($orderId);

        $warehouses = $stockModel->getAllWarehouses(true);
        $defaultWarehouse = $stockModel->getDefaultWarehouse();
        $defaultWarehouseId = $defaultWarehouse ? $defaultWarehouse['id'] : null;

        if (!$warehouseId && $defaultWarehouseId) {
            $warehouseId = $defaultWarehouseId;
        }

        $items = [];
        $allFromStock = true;

        if (!empty($orderItems)) {
            foreach ($orderItems as $item) {
                $product = $productModel->readOne($item['product_id']);
                $useStock = $product && !empty($product['use_stock_control']);
                $combinationId = $item['grade_combination_id'] ?? null;

                $stockQty = 0;
                if ($useStock && $warehouseId) {
                    $stockQty = $stockModel->getProductStockInWarehouse($warehouseId, $item['product_id'], $combinationId);
                }

                $sufficient = !$useStock || ($warehouseId && $stockQty >= (int)$item['quantity']);
                if ($useStock && !$sufficient) {
                    $allFromStock = false;
                }

                $items[] = [
                    'id'                => $item['id'],
                    'product_name'      => $item['product_name'] ?? ($product['name'] ?? '—'),
                    'combination_label' => $item['combination_label'] ?? null,
                    'quantity'          => (int)$item['quantity'],
                    'use_stock_control' => $useStock,
                    'stock_available'   => (float)$stockQty,
                    'sufficient'        => $sufficient,
                ];
            }
        }

        return [
            'success'              => true,
            'warehouses'           => $warehouses,
            'default_warehouse_id' => $defaultWarehouseId,
            'warehouse_id'         => $warehouseId,
            'items'                => $items,
            'all_from_stock'       => $allFromStock,
        ];
    }

    /**
     * Conta parcelas existentes de um pedido.
     */
    public function countInstallments(int $orderId): int
    {
        $financial = new Financial($this->db);
        return $financial->countInstallments($orderId);
    }

    /**
     * Remove todas as parcelas de um pedido (se nenhuma paga).
     *
     * @return array Resultado com success, deleted ou mensagem de erro
     */
    public function deleteInstallments(int $orderId): array
    {
        $financial = new Financial($this->db);

        if ($financial->hasAnyPaidInstallment($orderId)) {
            return [
                'success'  => false,
                'message'  => 'Existem parcelas já pagas. Estorne os pagamentos primeiro.',
                'has_paid' => true,
            ];
        }

        $deleted = $financial->deleteInstallmentsByOrder($orderId);

        // Limpar campos de parcelamento no pedido
        $q = "UPDATE orders SET installments = NULL, installment_value = NULL, down_payment = 0 WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);

        $logger = new Logger($this->db);
        $logger->log('INSTALLMENTS_DELETED', "Deleted $deleted installments for order #$orderId (payment method changed)");

        return ['success' => true, 'deleted' => $deleted];
    }
}
