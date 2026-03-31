<?php
namespace Akti\Services;

use Akti\Models\PortalAccess;
use PDO;

/**
 * Service: PortalCartService
 * Gerencia o carrinho de compras do portal do cliente (sessão).
 */
class PortalCartService
{
    private PortalAccess $portalAccess;

    public function __construct(PortalAccess $portalAccess)
    {
        $this->portalAccess = $portalAccess;
    }

    /**
     * Retorna os dados atuais do carrinho (itens, contagem, total).
     *
     * @return array{cart: array, cartCount: int, cartTotal: float}
     */
    public function getCartSummary(): array
    {
        $cart = $_SESSION['portal_cart'] ?? [];
        $cartCount = array_sum(array_column($cart, 'quantity'));
        $cartTotal = 0.0;
        foreach ($cart as $ci) {
            $cartTotal += $ci['price'] * $ci['quantity'];
        }

        return [
            'cart'      => $cart,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
        ];
    }

    /**
     * Adiciona um produto ao carrinho.
     *
     * @param int $productId
     * @param int $quantity
     * @return array{success: bool, message: string, cart: array, cartCount: int, cartTotal: float}
     */
    public function addItem(int $productId, int $quantity = 1): array
    {
        if ($productId <= 0) {
            return array_merge(['success' => false, 'message' => 'Produto inválido.'], $this->getCartSummary());
        }

        $product = $this->portalAccess->getProductById($productId);
        if (!$product) {
            return array_merge(['success' => false, 'message' => 'Produto não encontrado.'], $this->getCartSummary());
        }

        if (!isset($_SESSION['portal_cart'])) {
            $_SESSION['portal_cart'] = [];
        }

        // Verificar se o produto já está no carrinho
        $found = false;
        foreach ($_SESSION['portal_cart'] as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['portal_cart'][] = [
                'product_id' => $productId,
                'name'       => $product['name'],
                'price'      => (float) $product['price'],
                'quantity'   => $quantity,
                'image'      => $product['main_image_path'] ?? null,
            ];
        }

        return array_merge(['success' => true, 'message' => ''], $this->getCartSummary());
    }

    /**
     * Remove um produto do carrinho.
     *
     * @param int $productId
     * @return array{cart: array, cartCount: int, cartTotal: float}
     */
    public function removeItem(int $productId): array
    {
        if (isset($_SESSION['portal_cart'])) {
            $_SESSION['portal_cart'] = array_values(array_filter(
                $_SESSION['portal_cart'],
                fn($item) => (int) $item['product_id'] !== $productId
            ));
        }

        return $this->getCartSummary();
    }

    /**
     * Atualiza a quantidade de um item no carrinho.
     *
     * @param int $productId
     * @param int $quantity Se <= 0, remove o item
     * @return array{cart: array, cartCount: int, cartTotal: float}
     */
    public function updateItemQuantity(int $productId, int $quantity): array
    {
        if (isset($_SESSION['portal_cart'])) {
            if ($quantity <= 0) {
                return $this->removeItem($productId);
            }

            foreach ($_SESSION['portal_cart'] as &$item) {
                if ((int) $item['product_id'] === $productId) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
            unset($item);
        }

        return $this->getCartSummary();
    }

    /**
     * Limpa o carrinho completamente.
     */
    public function clear(): void
    {
        $_SESSION['portal_cart'] = [];
    }
}
