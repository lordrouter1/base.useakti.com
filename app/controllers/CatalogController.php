<?php
namespace Akti\Controllers;

use Akti\Models\CatalogLink;
use Akti\Models\Order;
use Akti\Models\Product;
use Akti\Models\PriceTable;
use Akti\Models\CompanySettings;
use Akti\Models\Logger;
use Database;
use PDOException;
use PDO;

/**
 * Controller: CatalogController
 * 
 * Gerencia a geração de links de catálogo e a página pública do catálogo.
 * O catálogo permite ao cliente navegar produtos, adicionar/remover do carrinho,
 * e essas mudanças se refletem em tempo real nos itens do pedido.
 */
class CatalogController {

    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Página pública do catálogo (não precisa de login)
     */
    public function index() {
        $token = $_GET['token'] ?? '';
        
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            // Link inválido ou expirado
            require 'app/views/catalog/expired.php';
            exit;
        }

        $orderId = $link['order_id'];
        $showPrices = (bool)$link['show_prices'];
        $customerId = $link['customer_id'];
        $customerName = $link['customer_name'] ?? 'Cliente';

        // Buscar todos os produtos com imagens
        $productModel = new Product($this->db);
        $stmt = $productModel->readAll();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar categorias para filtro
        $categories = $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Buscar preços do cliente (se mostrar preços)
        $customerPrices = [];
        if ($showPrices && $customerId) {
            $priceTableModel = new PriceTable($this->db);
            $customerPrices = $priceTableModel->getAllPricesForCustomer($customerId);
        }

        // Buscar itens já no carrinho (itens do pedido)
        $orderModel = new Order($this->db);
        $cartItems = $orderModel->getItems($orderId);

        // Buscar dados da empresa para branding
        $companyModel = new CompanySettings($this->db);
        $company = $companyModel->getAll();

        // Carregar imagens dos produtos
        $productImages = [];
        foreach ($products as $p) {
            $images = $productModel->getImages($p['id']);
            $productImages[$p['id']] = $images;
        }

        // Carregar combinações de grade ativas para cada produto
        $productCombinations = [];
        foreach ($products as $p) {
            $combos = $productModel->getActiveCombinations($p['id']);
            if (!empty($combos)) {
                $productCombinations[$p['id']] = $combos;
            }
        }

        require 'app/views/catalog/index.php';
        exit;
    }

    /**
     * API: Gerar link de catálogo (chamado via AJAX do pipeline)
     */
    public function generate() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $orderId = $_POST['order_id'] ?? null;
        $showPrices = isset($_POST['show_prices']) ? (bool)$_POST['show_prices'] : true;
        $expiresIn = $_POST['expires_in'] ?? null; // dias até expirar

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado']);
            exit;
        }

        $expiresAt = null;
        if ($expiresIn && (int)$expiresIn > 0) {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresIn} days"));
        }

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->create($orderId, $showPrices, $expiresAt);

        if ($link) {
            $url = CatalogLink::buildUrl($link['token']);
            
            // Log
            $logger = new Logger($this->db);
            $logger->log('CATALOG_LINK', "Link de catálogo gerado para pedido #{$orderId}");

            echo json_encode([
                'success' => true,
                'url' => $url,
                'token' => $link['token'],
                'show_prices' => $link['show_prices'],
                'expires_at' => $link['expires_at']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao gerar link']);
        }
        exit;
    }

    /**
     * API: Desativar link de catálogo
     */
    public function deactivate() {
        header('Content-Type: application/json');

        $orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? null;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado']);
            exit;
        }

        try {
            $catalogModel = new CatalogLink($this->db);
            $catalogModel->deactivateByOrder($orderId);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao desativar link: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Buscar link ativo de um pedido
     */
    public function getLink() {
        header('Content-Type: application/json');

        $orderId = $_GET['order_id'] ?? null;
        if (!$orderId) {
            echo json_encode(['success' => false]);
            exit;
        }

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findActiveByOrder($orderId);

        if ($link) {
            echo json_encode([
                'success' => true,
                'url' => CatalogLink::buildUrl($link['token']),
                'token' => $link['token'],
                'show_prices' => (bool)$link['show_prices'],
                'expires_at' => $link['expires_at'],
                'created_at' => $link['created_at']
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    /**
     * API: Adicionar produto ao carrinho (= adicionar item ao pedido)
     * Chamado via AJAX do catálogo público
     */
    public function addToCart() {
        header('Content-Type: application/json');

        $token = $_POST['token'] ?? '';
        $productId = $_POST['product_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $combinationId = !empty($_POST['combination_id']) ? (int)$_POST['combination_id'] : null;
        $gradeDescription = $_POST['grade_description'] ?? null;

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        $orderId = $link['order_id'];
        $customerId = $link['customer_id'];

        // Buscar preço do produto para o cliente
        $priceTableModel = new PriceTable($this->db);
        $unitPrice = $priceTableModel->getProductPriceForCustomer($productId, $customerId);

        // Se a combinação tem preço override, usá-lo
        if ($combinationId) {
            $comboStmt = $this->db->prepare("SELECT price_override FROM product_grade_combinations WHERE id = :id AND is_active = 1");
            $comboStmt->bindParam(':id', $combinationId, PDO::PARAM_INT);
            $comboStmt->execute();
            $combo = $comboStmt->fetch(PDO::FETCH_ASSOC);
            if ($combo && $combo['price_override'] !== null) {
                $unitPrice = (float)$combo['price_override'];
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
            // Atualizar quantidade
            $newQty = $existingItem['quantity'] + $quantity;
            $orderModel->updateItem($existingItem['id'], $newQty, $unitPrice);
        } else {
            // Adicionar novo item
            $orderModel->addItem($orderId, $productId, $quantity, $unitPrice, $combinationId, $gradeDescription);
        }

        // Retornar carrinho atualizado
        $updatedItems = $orderModel->getItems($orderId);
        echo json_encode([
            'success' => true,
            'cart' => $updatedItems,
            'cart_count' => count($updatedItems),
            'cart_total' => array_sum(array_column($updatedItems, 'subtotal'))
        ]);
        exit;
    }

    /**
     * API: Remover produto do carrinho (= remover item do pedido)
     */
    public function removeFromCart() {
        header('Content-Type: application/json');

        $token = $_POST['token'] ?? '';
        $itemId = $_POST['item_id'] ?? null;

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
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
            echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
            exit;
        }

        $orderModel->deleteItem($itemId);

        // Retornar carrinho atualizado
        $updatedItems = $orderModel->getItems($orderId);
        echo json_encode([
            'success' => true,
            'cart' => $updatedItems,
            'cart_count' => count($updatedItems),
            'cart_total' => array_sum(array_column($updatedItems, 'subtotal'))
        ]);
        exit;
    }

    /**
     * API: Atualizar quantidade de um item no carrinho
     */
    public function updateCartItem() {
        header('Content-Type: application/json');

        $token = $_POST['token'] ?? '';
        $itemId = $_POST['item_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        if ($quantity < 1) {
            // Se quantidade zero, remover
            $_POST['item_id'] = $itemId;
            $this->removeFromCart();
            return;
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
            echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
            exit;
        }

        // Retornar carrinho atualizado
        $updatedItems = $orderModel->getItems($orderId);
        echo json_encode([
            'success' => true,
            'cart' => $updatedItems,
            'cart_count' => count($updatedItems),
            'cart_total' => array_sum(array_column($updatedItems, 'subtotal'))
        ]);
        exit;
    }

    /**
     * API: Buscar carrinho atual (para polling do catálogo)
     */
    public function getCart() {
        header('Content-Type: application/json');

        $token = $_GET['token'] ?? '';
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        $orderModel = new Order($this->db);
        $items = $orderModel->getItems($link['order_id']);

        echo json_encode([
            'success' => true,
            'cart' => $items,
            'cart_count' => count($items),
            'cart_total' => array_sum(array_column($items, 'subtotal'))
        ]);
        exit;
    }
}
