<?php
namespace Akti\Controllers;

use Akti\Models\CatalogLink;
use Akti\Models\Order;
use Akti\Models\Product;
use Akti\Models\PriceTable;
use Akti\Models\CompanySettings;
use Akti\Models\Logger;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;
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
        $token = Input::get('token');
        
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            // Link inválido ou expirado
            require 'app/views/catalog/expired.php';
            exit;
        }

        $orderId = $link['order_id'];
        $showPrices = (bool)$link['show_prices'];
        $requireConfirmation = (bool)($link['require_confirmation'] ?? false);
        $customerId = $link['customer_id'];
        $customerName = $link['customer_name'] ?? 'Cliente';
        $orderDiscount = (float)($link['discount'] ?? 0);
        $quoteConfirmedAt = $link['quote_confirmed_at'] ?? null;
        $quoteConfirmedIp = $link['quote_confirmed_ip'] ?? null;

        // Buscar primeira página de produtos (paginação – 20 por vez)
        $productModel = new Product($this->db);
        $perPage = 20;
        $initialResult = $productModel->readPaginatedFiltered(1, $perPage, null, null);
        $products = $initialResult['data'];
        $totalProducts = $initialResult['total'];
        $totalPages = ceil($totalProducts / $perPage);

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

        // Buscar custos extras do pedido (para modo confirmação)
        $extraCosts = [];
        if ($requireConfirmation) {
            $extraCosts = $orderModel->getExtraCosts($orderId);
        }

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

        $orderId = Input::post('order_id', 'int');
        $showPrices = Input::post('show_prices', 'bool');
        $requireConfirmation = Input::post('require_confirmation', 'bool');
        $expiresIn = Input::post('expires_in', 'int');

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado']);
            exit;
        }

        // Se requer confirmação, forçar exibição de preços
        if ($requireConfirmation) {
            $showPrices = true;
        }

        $expiresAt = null;
        if ($expiresIn && (int)$expiresIn > 0) {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresIn} days"));
        }

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->create($orderId, $showPrices, $expiresAt, $requireConfirmation);

        if ($link) {
            $url = CatalogLink::buildUrl($link['token']);
            
            // Log
            $logger = new Logger($this->db);
            $logger->log('CATALOG_LINK', "Link de catálogo gerado para pedido #{$orderId}" . ($requireConfirmation ? ' (com confirmação)' : ''));

            echo json_encode([
                'success' => true,
                'url' => $url,
                'token' => $link['token'],
                'show_prices' => $link['show_prices'],
                'require_confirmation' => $link['require_confirmation'],
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

        $orderId = Input::post('order_id', 'int') ?: Input::get('order_id', 'int');
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

        $orderId = Input::get('order_id', 'int');
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
                'require_confirmation' => (bool)($link['require_confirmation'] ?? false),
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

        $token = Input::post('token');
        $productId = Input::post('product_id', 'int');
        $quantity = Input::post('quantity', 'int', 1);
        $combinationId = Input::post('combination_id', 'int') ?: null;
        $gradeDescription = Input::post('grade_description');

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

        $token = Input::post('token');
        $itemId = Input::post('item_id', 'int');

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

        $token = Input::post('token');
        $itemId = Input::post('item_id', 'int');
        $quantity = Input::post('quantity', 'int', 1);

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        if ($quantity < 1) {
            // Se quantidade zero, remover
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

        $token = Input::get('token');
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

    /**
     * API: Confirmar orçamento pelo cliente (via catálogo público)
     * Marca o pedido como aprovado pelo cliente, salvando a data de confirmação.
     */
    public function confirmQuote() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $token = Input::post('token');

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        // Verificar se o link requer confirmação
        if (empty($link['require_confirmation'])) {
            echo json_encode(['success' => false, 'message' => 'Este link não permite confirmação de orçamento']);
            exit;
        }

        // Verificar se já foi confirmado
        if (!empty($link['quote_confirmed_at'])) {
            echo json_encode(['success' => false, 'message' => 'Este orçamento já foi confirmado anteriormente']);
            exit;
        }

        $orderId = $link['order_id'];

        // Verificar se tem itens no pedido
        $orderModel = new Order($this->db);
        $items = $orderModel->getItems($orderId);
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Não é possível confirmar um orçamento sem produtos']);
            exit;
        }

        // Capturar IP do cliente
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        // Se vier lista de IPs, pegar o primeiro (IP real do cliente)
        if (strpos($clientIp, ',') !== false) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }

        // Marcar a confirmação do orçamento com IP
        $stmt = $this->db->prepare("UPDATE orders SET quote_confirmed_at = NOW(), quote_confirmed_ip = :ip WHERE id = :id");
        $stmt->bindParam(':ip', $clientIp);
        $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        // Log
        $logger = new Logger($this->db);
        $logger->log('QUOTE_CONFIRMED', "Orçamento do pedido #{$orderId} confirmado pelo cliente via catálogo (IP: {$clientIp})");

        echo json_encode([
            'success' => true,
            'message' => 'Orçamento confirmado com sucesso!',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_ip' => $clientIp
        ]);
        exit;
    }

    /**
     * API: Revogar confirmação de orçamento pelo cliente (permite editar novamente)
     */
    public function revokeQuote() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $token = Input::post('token');

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        if (empty($link['require_confirmation'])) {
            echo json_encode(['success' => false, 'message' => 'Este link não permite confirmação de orçamento']);
            exit;
        }

        if (empty($link['quote_confirmed_at'])) {
            echo json_encode(['success' => false, 'message' => 'O orçamento ainda não foi confirmado']);
            exit;
        }

        $orderId = $link['order_id'];

        // Revogar a confirmação
        $stmt = $this->db->prepare("UPDATE orders SET quote_confirmed_at = NULL, quote_confirmed_ip = NULL WHERE id = :id");
        $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        // Log
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        if (strpos($clientIp, ',') !== false) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }
        $logger = new Logger($this->db);
        $logger->log('QUOTE_REVOKED', "Orçamento do pedido #{$orderId} revogado pelo cliente via catálogo (IP: {$clientIp})");

        echo json_encode([
            'success' => true,
            'message' => 'Confirmação revogada. Agora você pode editar o orçamento.'
        ]);
        exit;
    }

    /**
     * API: Buscar produtos paginados para o catálogo (AJAX)
     * Retorna JSON com produtos para lazy loading / infinite scroll
     */
    public function getProducts() {
        header('Content-Type: application/json');

        $token = Input::get('token');
        $page = Input::get('page_num', 'int', 1);
        $perPage = Input::get('per_page', 'int', 20);
        $category = Input::get('category', 'int');
        $search = Input::get('search');

        if ($perPage > 50) $perPage = 50;
        if ($page < 1) $page = 1;

        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado']);
            exit;
        }

        $customerId = $link['customer_id'];
        $showPrices = (bool)$link['show_prices'];

        // Buscar produtos paginados
        $productModel = new Product($this->db);
        $result = $productModel->readPaginatedFiltered($page, $perPage, $category, $search);
        $products = $result['data'];
        $totalProducts = $result['total'];
        $totalPages = ceil($totalProducts / $perPage);

        // Buscar preços do cliente
        $customerPrices = [];
        if ($showPrices && $customerId) {
            $priceTableModel = new PriceTable($this->db);
            $customerPrices = $priceTableModel->getAllPricesForCustomer($customerId);
        }

        // Montar dados dos produtos com imagem e combinações
        $items = [];
        foreach ($products as $p) {
            $images = $productModel->getImages($p['id']);
            $mainImage = null;
            foreach ($images as $img) {
                if (!empty($img['is_main'])) { $mainImage = $img['image_path']; break; }
            }
            if (!$mainImage && !empty($images)) $mainImage = $images[0]['image_path'];

            $combos = $productModel->getActiveCombinations($p['id']);

            $displayPrice = $customerPrices[$p['id']] ?? $p['price'];

            $items[] = [
                'id'            => $p['id'],
                'name'          => $p['name'],
                'description'   => $p['description'] ?? '',
                'category_id'   => $p['category_id'],
                'price'         => (float)$displayPrice,
                'main_image'    => $mainImage,
                'combinations'  => $combos,
            ];
        }

        echo json_encode([
            'success'     => true,
            'products'    => $items,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $totalProducts,
            'total_pages' => $totalPages,
            'has_more'    => $page < $totalPages,
        ]);
        exit;
    }
}
