<?php
namespace Akti\Controllers;

use Akti\Models\Stock;
use Akti\Models\Product;
use Akti\Models\Logger;
use Akti\Models\Order;
use Database;
use TenantManager;

class StockController {

    private $stockModel;
    private $productModel;
    private $logger;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->stockModel = new Stock($this->db);
        $this->productModel = new Product($this->db);
        $this->logger = new Logger($this->db);

        // Auto-migrate: garantir colunas e tabelas novas
        $this->stockModel->ensureDefaultColumn();
        $this->stockModel->ensureDeductionsTable();
        $this->stockModel->ensureOrderWarehouseColumn();
    }

    // ─── Página principal: visão geral do estoque ───
    public function index() {
        $warehouseId = $_GET['warehouse_id'] ?? null;
        $search = $_GET['search'] ?? '';
        $lowStock = isset($_GET['low_stock']) && $_GET['low_stock'] == '1';

        $warehouses = $this->stockModel->getAllWarehouses();
        $stockItems = $this->stockModel->getStockItems($warehouseId, $search, $lowStock);
        $summary = $this->stockModel->getDashboardSummary();
        $lowStockItems = $this->stockModel->getLowStockItems(5);

        require 'app/views/layout/header.php';
        require 'app/views/stock/index.php';
        require 'app/views/layout/footer.php';
    }

    // ─── Armazéns: listagem e gestão ───
    public function warehouses() {
        $warehouses = $this->stockModel->getAllWarehouses(false);

        // Verificar limite de armazéns do tenant
        $maxWarehouses = TenantManager::getTenantLimit('max_warehouses');
        $currentWarehouses = $this->stockModel->countWarehouses();
        $limitReached = ($maxWarehouses !== null && $currentWarehouses >= $maxWarehouses);
        $limitInfo = $limitReached ? ['current' => $currentWarehouses, 'max' => $maxWarehouses] : null;

        require 'app/views/layout/header.php';
        require 'app/views/stock/warehouses.php';
        require 'app/views/layout/footer.php';
    }

    public function storeWarehouse() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar limite de armazéns do tenant
            $maxWarehouses = TenantManager::getTenantLimit('max_warehouses');
            if ($maxWarehouses !== null) {
                $currentWarehouses = $this->stockModel->countWarehouses();
                if ($currentWarehouses >= $maxWarehouses) {
                    header('Location: ?page=stock&action=warehouses&status=limit_warehouses');
                    exit;
                }
            }

            $data = [
                'name'     => trim($_POST['name'] ?? ''),
                'address'  => trim($_POST['address'] ?? ''),
                'city'     => trim($_POST['city'] ?? ''),
                'state'    => trim($_POST['state'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'phone'    => trim($_POST['phone'] ?? ''),
                'notes'    => trim($_POST['notes'] ?? ''),
                'is_default' => isset($_POST['is_default']) ? 1 : 0,
            ];

            if (empty($data['name'])) {
                header('Location: ?page=stock&action=warehouses&error=name');
                exit;
            }

            $id = $this->stockModel->createWarehouse($data);
            if ($id) {
                $this->logger->log('STOCK_WAREHOUSE_CREATE', "Armazém criado: {$data['name']} (ID: $id)" . ($data['is_default'] ? ' [PADRÃO]' : ''));
            }
            header('Location: ?page=stock&action=warehouses&status=created');
            exit;
        }
    }

    public function updateWarehouse() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id'        => intval($_POST['id'] ?? 0),
                'name'      => trim($_POST['name'] ?? ''),
                'address'   => trim($_POST['address'] ?? ''),
                'city'      => trim($_POST['city'] ?? ''),
                'state'     => trim($_POST['state'] ?? ''),
                'zip_code'  => trim($_POST['zip_code'] ?? ''),
                'phone'     => trim($_POST['phone'] ?? ''),
                'notes'     => trim($_POST['notes'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_default' => isset($_POST['is_default']) ? 1 : 0,
            ];

            $this->stockModel->updateWarehouse($data);
            $this->logger->log('STOCK_WAREHOUSE_UPDATE', "Armazém atualizado: {$data['name']} (ID: {$data['id']})" . ($data['is_default'] ? ' [PADRÃO]' : ''));
            header('Location: ?page=stock&action=warehouses&status=updated');
            exit;
        }
    }

    public function deleteWarehouse() {
        $id = intval($_GET['id'] ?? 0);
        if ($id) {
            $wh = $this->stockModel->getWarehouse($id);
            $this->stockModel->deleteWarehouse($id);
            $this->logger->log('STOCK_WAREHOUSE_DELETE', "Armazém removido: " . ($wh['name'] ?? $id));
        }
        header('Location: ?page=stock&action=warehouses&status=deleted');
        exit;
    }

    // ─── Movimentações ───
    public function movements() {
        $filters = [
            'warehouse_id' => $_GET['warehouse_id'] ?? null,
            'product_id'   => $_GET['product_id'] ?? null,
            'type'         => $_GET['type'] ?? null,
            'date_from'    => $_GET['date_from'] ?? null,
            'date_to'      => $_GET['date_to'] ?? null,
            'limit'        => isset($_GET['limit']) ? intval($_GET['limit']) : 200,
        ];

        $movements = $this->stockModel->getMovements($filters);

        // Se requisição JSON (para o mini-histórico na página de entrada)
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json');
            echo json_encode($movements);
            exit;
        }

        $warehouses = $this->stockModel->getAllWarehouses();
        $products = $this->stockModel->getProductsForSelection();

        require 'app/views/layout/header.php';
        require 'app/views/stock/movements.php';
        require 'app/views/layout/footer.php';
    }

    // ─── Entrada de Estoque ───
    public function entry() {
        $warehouses = $this->stockModel->getAllWarehouses();
        $products = $this->stockModel->getProductsForSelection();

        require 'app/views/layout/header.php';
        require 'app/views/stock/entry.php';
        require 'app/views/layout/footer.php';
    }

    // ─── AJAX: Processar movimentação (entrada/saída/ajuste/transferência) ───
    public function storeMovement() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $warehouseId = intval($_POST['warehouse_id'] ?? 0);
        $type = $_POST['type'] ?? 'entrada';
        $reason = trim($_POST['reason'] ?? '');
        $items = $_POST['items'] ?? [];
        $destWarehouseId = intval($_POST['destination_warehouse_id'] ?? 0);

        if (!$warehouseId || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Selecione um armazém e pelo menos um produto.']);
            exit;
        }

        if ($type === 'transferencia' && !$destWarehouseId) {
            echo json_encode(['success' => false, 'message' => 'Selecione o armazém de destino para transferência.']);
            exit;
        }

        $processed = 0;
        $errors = [];

        foreach ($items as $i => $item) {
            $productId = intval($item['product_id'] ?? 0);
            $combinationId = !empty($item['combination_id']) ? intval($item['combination_id']) : null;
            $quantity = floatval($item['quantity'] ?? 0);

            if (!$productId || $quantity <= 0) {
                $errors[] = "Item #" . ($i + 1) . ": produto ou quantidade inválida.";
                continue;
            }

            try {
                $this->stockModel->addMovement([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'combination_id' => $combinationId,
                    'type' => $type,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'reference_type' => 'manual',
                    'destination_warehouse_id' => $type === 'transferencia' ? $destWarehouseId : null,
                ]);
                $processed++;
            } catch (Exception $e) {
                $errors[] = "Item #" . ($i + 1) . ": " . $e->getMessage();
            }
        }

        $typeLabels = ['entrada' => 'Entrada', 'saida' => 'Saída', 'ajuste' => 'Ajuste', 'transferencia' => 'Transferência'];
        $this->logger->log('STOCK_MOVEMENT', "{$typeLabels[$type]}: $processed item(s) processado(s) no armazém #$warehouseId");

        echo json_encode([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
            'message' => "$processed item(s) processado(s) com sucesso."
        ]);
        exit;
    }

    // ─── AJAX: Buscar combinações de um produto ───
    public function getProductCombinations() {
        header('Content-Type: application/json');
        $productId = intval($_GET['product_id'] ?? 0);
        if (!$productId) {
            echo json_encode([]);
            exit;
        }
        $combos = $this->stockModel->getProductCombinations($productId);
        echo json_encode($combos);
        exit;
    }

    // ─── AJAX: Atualizar metadados de um item de estoque ───
    public function updateItemMeta() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $minQty = floatval($_POST['min_quantity'] ?? 0);
        $locCode = trim($_POST['location_code'] ?? '');

        if ($id) {
            $this->stockModel->updateStockItemMeta($id, $minQty, $locCode);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        }
        exit;
    }

    // ─── AJAX: Buscar estoque atual de um produto em um armazém ───
    public function getProductStock() {
        header('Content-Type: application/json');
        $warehouseId = intval($_GET['warehouse_id'] ?? 0);
        $productId = intval($_GET['product_id'] ?? 0);

        $items = $this->stockModel->getStockItems($warehouseId, '', false);
        $result = [];
        foreach ($items as $item) {
            if ($item['product_id'] == $productId) {
                $result[] = $item;
            }
        }
        echo json_encode($result);
        exit;
    }

    // ─── AJAX: Definir armazém padrão ───
    public function setDefault() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }
        $this->stockModel->setDefaultWarehouse($id);
        $wh = $this->stockModel->getWarehouse($id);
        $this->logger->log('STOCK_WAREHOUSE_DEFAULT', "Armazém padrão definido: " . ($wh['name'] ?? $id));
        echo json_encode(['success' => true]);
        exit;
    }

    // ─── AJAX: Buscar armazém padrão ───
    public function getDefaultWarehouse() {
        header('Content-Type: application/json');
        $wh = $this->stockModel->getDefaultWarehouse();
        echo json_encode(['success' => true, 'warehouse' => $wh]);
        exit;
    }

    // ─── AJAX: Verificar disponibilidade de estoque de um pedido em um armazém ───
    public function checkOrderStock() {
        header('Content-Type: application/json');
        $orderId = intval($_GET['order_id'] ?? 0);
        $warehouseId = intval($_GET['warehouse_id'] ?? 0);

        if (!$orderId || !$warehouseId) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        $orderModel = new Order($this->db);
        $items = $orderModel->getItems($orderId);

        $result = [];
        $allAvailable = true;

        foreach ($items as $item) {
            $product = $this->productModel->readOne($item['product_id']);
            $useStock = !empty($product['use_stock_control']);
            $combinationId = $item['grade_combination_id'] ?: null;
            $needed = (float) $item['quantity'];
            $available = 0;

            if ($useStock) {
                $available = $this->stockModel->getProductStockInWarehouse($warehouseId, $item['product_id'], $combinationId);
            }

            $sufficient = !$useStock || ($available >= $needed);
            if ($useStock && !$sufficient) $allAvailable = false;

            $result[] = [
                'item_id' => $item['id'],
                'product_name' => $item['product_name'],
                'combination' => $item['combination_label'] ?? null,
                'quantity_needed' => $needed,
                'quantity_available' => $available,
                'use_stock_control' => $useStock,
                'sufficient' => $sufficient,
            ];
        }

        echo json_encode([
            'success' => true,
            'all_available' => $allAvailable,
            'items' => $result,
        ]);
        exit;
    }
}
