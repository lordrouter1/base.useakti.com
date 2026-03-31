<?php
namespace Akti\Services;

use Akti\Models\CatalogLink;
use Akti\Models\Order;
use Akti\Models\PriceTable;
use PDO;

/**
 * CatalogCartService — Lógica de carrinho do catálogo público.
 *
 * Responsabilidades:
 *   - Adicionar, remover e atualizar itens do carrinho via token de catálogo
 *   - Montar resposta do carrinho atualizado
 *   - Validar link e itens
 *
 * @package Akti\Services
 */
class CatalogCartService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Adicionar produto ao carrinho (item do pedido).
     *
     * @param string $token Token do catálogo
     * @param int $productId
     * @param int $quantity
     * @param int|null $combinationId
     * @param string|null $gradeDescription
     * @return array ['success' => bool, ...] 
     */
    public function addToCart(string $token, int $productId, int $quantity, ?int $combinationId, ?string $gradeDescription): array
    {
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Link inválido ou expirado'];
        }

        $orderId = $link['order_id'];
        $customerId = $link['customer_id'];

        // Buscar preço do produto para o cliente
        $priceTableModel = new PriceTable($this->db);
        $unitPrice = $priceTableModel->getProductPriceForCustomer($productId, $customerId);

        // Se a combinação tem preço override, usá-lo
        if ($combinationId) {
            $comboStmt = $this->db->prepare(
                "SELECT price_override FROM product_grade_combinations WHERE id = :id AND is_active = 1"
            );
            $comboStmt->bindParam(':id', $combinationId, PDO::PARAM_INT);
            $comboStmt->execute();
            $combo = $comboStmt->fetch(PDO::FETCH_ASSOC);
            if ($combo && $combo['price_override'] !== null) {
                $unitPrice = (float) $combo['price_override'];
            }
        }

        // Verificar se o produto (com mesma combinação) já está no carrinho
        $orderModel = new Order($this->db);
        $currentItems = $orderModel->getItems($orderId);
        $existingItem = null;
        foreach ($currentItems as $item) {
            if ($item['product_id'] == $productId && ($item['grade_combination_id'] ?? null) == $combinationId) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $newQty = $existingItem['quantity'] + $quantity;
            $orderModel->updateItem($existingItem['id'], $newQty, $unitPrice);
        } else {
            $orderModel->addItem($orderId, $productId, $quantity, $unitPrice, $combinationId, $gradeDescription);
        }

        return $this->buildCartResponse($orderModel, $orderId);
    }

    /**
     * Remover item do carrinho.
     *
     * @param string $token
     * @param int $itemId
     * @return array
     */
    public function removeFromCart(string $token, int $itemId): array
    {
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Link inválido ou expirado'];
        }

        $orderId = $link['order_id'];
        $orderModel = new Order($this->db);

        // Verificar se o item pertence ao pedido correto
        $currentItems = $orderModel->getItems($orderId);
        $valid = false;
        foreach ($currentItems as $item) {
            if ($item['id'] == $itemId) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            return ['success' => false, 'message' => 'Item não encontrado'];
        }

        $orderModel->deleteItem($itemId);

        return $this->buildCartResponse($orderModel, $orderId);
    }

    /**
     * Atualizar quantidade de um item no carrinho.
     *
     * @param string $token
     * @param int $itemId
     * @param int $quantity
     * @return array
     */
    public function updateCartItem(string $token, int $itemId, int $quantity): array
    {
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Link inválido ou expirado'];
        }

        if ($quantity < 1) {
            return $this->removeFromCart($token, $itemId);
        }

        $orderId = $link['order_id'];
        $orderModel = new Order($this->db);

        // Buscar item atual para pegar preço
        $currentItems = $orderModel->getItems($orderId);
        $found = false;
        foreach ($currentItems as $item) {
            if ($item['id'] == $itemId) {
                $orderModel->updateItem($itemId, $quantity, $item['unit_price']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return ['success' => false, 'message' => 'Item não encontrado'];
        }

        return $this->buildCartResponse($orderModel, $orderId);
    }

    /**
     * Buscar carrinho atual por token.
     *
     * @param string $token
     * @return array
     */
    public function getCart(string $token): array
    {
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Link inválido ou expirado'];
        }

        $orderModel = new Order($this->db);
        $items = $orderModel->getItems($link['order_id']);

        return [
            'success'    => true,
            'cart'       => $items,
            'cart_count' => count($items),
            'cart_total' => array_sum(array_column($items, 'subtotal')),
        ];
    }

    /**
     * Montar resposta padrão do carrinho atualizado.
     */
    private function buildCartResponse(Order $orderModel, int $orderId): array
    {
        $updatedItems = $orderModel->getItems($orderId);
        return [
            'success'    => true,
            'cart'       => $updatedItems,
            'cart_count' => count($updatedItems),
            'cart_total' => array_sum(array_column($updatedItems, 'subtotal')),
        ];
    }
}
