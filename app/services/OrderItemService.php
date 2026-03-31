<?php
namespace Akti\Services;

use Akti\Models\Order;
use Akti\Models\Financial;
use Akti\Models\Logger;
use PDO;

/**
 * OrderItemService — Lógica de negócio para itens de pedido.
 *
 * Responsabilidades:
 *   - Verificar bloqueio por parcelas pagas
 *   - Limpar confirmação de orçamento ao alterar produtos
 *   - Obter order_id de um item
 *   - Atualizar quantidade e desconto de itens (com recálculo)
 *
 * @package Akti\Services
 */
class OrderItemService
{
    private PDO $db;
    private Order $orderModel;

    public function __construct(PDO $db, Order $orderModel)
    {
        $this->db = $db;
        $this->orderModel = $orderModel;
    }

    /**
     * Verifica se o pedido possui parcelas pagas (bloqueia alteração de produtos).
     */
    public function orderHasPaidInstallments(int $orderId): bool
    {
        if (!$orderId) {
            return false;
        }
        $financialModel = new Financial($this->db);
        return $financialModel->hasAnyPaidInstallment($orderId);
    }

    /**
     * Remove a confirmação de orçamento quando produtos são modificados.
     * Se o pedido tinha sido aprovado pelo cliente via catálogo,
     * a aprovação é invalidada para que o cliente aprove novamente.
     */
    public function clearQuoteConfirmation(int $orderId): void
    {
        if (!$orderId) {
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE orders SET quote_confirmed_at = NULL, quote_confirmed_ip = NULL 
             WHERE id = :id AND quote_confirmed_at IS NOT NULL"
        );
        $stmt->execute([':id' => $orderId]);

        if ($stmt->rowCount() > 0) {
            $logger = new Logger($this->db);
            $logger->log(
                'QUOTE_CONFIRMATION_CLEARED',
                "Confirmação de orçamento do pedido #{$orderId} removida devido a alteração de produtos"
            );
        }

        // Sincronizar: voltar customer_approval_status para 'pendente' se estava aprovado/recusado
        $order = $this->orderModel->readOne($orderId);
        $approvalStatus = $order['customer_approval_status'] ?? null;
        if ($approvalStatus === 'aprovado' || $approvalStatus === 'recusado') {
            $this->orderModel->setCustomerApprovalStatus($orderId, 'pendente');
            $this->db->prepare(
                "UPDATE orders SET customer_approval_at = NULL, customer_approval_ip = NULL, 
                        customer_approval_notes = NULL WHERE id = :id"
            )->execute([':id' => $orderId]);
        }
    }

    /**
     * Obtém o order_id a partir de um item_id.
     */
    public function getOrderIdFromItem(int $itemId): ?int
    {
        if (!$itemId) {
            return null;
        }
        $q = "SELECT order_id FROM order_items WHERE id = :id LIMIT 1";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $itemId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['order_id'] : null;
    }

    /**
     * Atualizar quantidade de um item e retornar dados atualizados.
     *
     * @return array ['success' => bool, ...data]
     */
    public function updateItemQuantity(int $itemId, int $quantity): array
    {
        $result = $this->orderModel->updateItemQty($itemId, $quantity);

        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao atualizar quantidade'];
        }

        $orderId = $this->getOrderIdFromItem($itemId);
        if ($orderId) {
            $this->clearQuoteConfirmation($orderId);
        }

        // Buscar dados atualizados
        $q = "SELECT oi.order_id, oi.quantity, oi.unit_price, oi.subtotal, oi.discount, o.total_amount 
              FROM order_items oi 
              JOIN orders o ON oi.order_id = o.id 
              WHERE oi.id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $itemId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        return [
            'success'      => true,
            'message'      => 'Quantidade atualizada com sucesso',
            'new_subtotal' => $row ? (float) $row['subtotal'] : 0,
            'new_total'    => $row ? (float) $row['total_amount'] : 0,
            'quantity'     => $row ? (int) $row['quantity'] : 1,
        ];
    }

    /**
     * Atualizar desconto de um item e retornar dados atualizados.
     *
     * @return array ['success' => bool, ...data]
     */
    public function updateItemDiscount(int $itemId, float $discount): array
    {
        $result = $this->orderModel->updateItemDiscount($itemId, $discount);

        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao atualizar desconto'];
        }

        $orderId = $this->getOrderIdFromItem($itemId);
        if ($orderId) {
            $this->clearQuoteConfirmation($orderId);
        }

        // Buscar novo total do pedido (recalculado pelo model)
        $q = "SELECT oi.order_id, o.total_amount 
              FROM order_items oi 
              JOIN orders o ON oi.order_id = o.id 
              WHERE oi.id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $itemId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        return [
            'success'   => true,
            'message'   => 'Desconto atualizado com sucesso',
            'new_total' => $row ? (float) $row['total_amount'] : 0,
        ];
    }
}
