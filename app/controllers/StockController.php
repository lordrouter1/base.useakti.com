<?php
namespace Akti\Controllers;

use Akti\Models\Stock;
use Akti\Models\Product;
use Akti\Models\Logger;
use Akti\Models\Order;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Akti\Utils\Sanitizer;
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

    // ─── Página principal: visão geral do estoque (com sidebar unificada) ───
    public function index() {
        // ── Dados da Visão Geral (resumo — carregamento leve, tabelas via AJAX) ──
        $warehouseId = Input::get('warehouse_id', 'int');
        $search = Input::get('search');
        $lowStock = Input::get('low_stock') === '1';

        $warehouses = $this->stockModel->getAllWarehouses();
        $warehousesAll = $this->stockModel->getAllWarehouses(false); // inclui inativos para gestão
        $summary = $this->stockModel->getDashboardSummary();
        $lowStockItems = $this->stockModel->getLowStockItems(5);

        // ── Filtros de Movimentações (valores iniciais para preencher selects) ──
        $movFilters = [
            'warehouse_id' => Input::get('mov_warehouse_id', 'int'),
            'product_id'   => Input::get('mov_product_id', 'int'),
            'type'         => Input::get('mov_type'),
            'date_from'    => Input::get('mov_date_from', 'date'),
            'date_to'      => Input::get('mov_date_to', 'date'),
        ];

        // ── Dados para Entrada/Saída ──
        $products = $this->stockModel->getProductsForSelection();

        // ── Dados de Armazéns ──
        $maxWarehouses = TenantManager::getTenantLimit('max_warehouses');
        $currentWarehouses = $this->stockModel->countWarehouses();
        $limitReached = ($maxWarehouses !== null && $currentWarehouses >= $maxWarehouses);
        $limitInfo = $limitReached ? ['current' => $currentWarehouses, 'max' => $maxWarehouses] : null;

        require 'app/views/layout/header.php';
        require 'app/views/stock/index.php';
        require 'app/views/layout/footer.php';
    }

    // ─── Armazéns: redireciona para a página unificada ───
    public function warehouses() {
        header('Location: ?page=stock&section=warehouses');
        exit;
    }

    public function storeWarehouse() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar limite de armazéns do tenant
            $maxWarehouses = TenantManager::getTenantLimit('max_warehouses');
            if ($maxWarehouses !== null) {
                $currentWarehouses = $this->stockModel->countWarehouses();
                if ($currentWarehouses >= $maxWarehouses) {
                    header('Location: ?page=stock&section=warehouses&status=limit_warehouses');
                    exit;
                }
            }

            $data = [
                'name'     => Input::post('name'),
                'address'  => Input::post('address'),
                'city'     => Input::post('city'),
                'state'    => Input::post('state'),
                'zip_code' => Input::post('zip_code', 'cep'),
                'phone'    => Input::post('phone', 'phone'),
                'notes'    => Input::post('notes'),
                'is_default' => Input::post('is_default', 'bool') ? 1 : 0,
            ];

            if (empty($data['name'])) {
                header('Location: ?page=stock&section=warehouses&error=name');
                exit;
            }

            $id = $this->stockModel->createWarehouse($data);
            if ($id) {
                $this->logger->log('STOCK_WAREHOUSE_CREATE', "Armazém criado: {$data['name']} (ID: $id)" . ($data['is_default'] ? ' [PADRÃO]' : ''));
            }
            header('Location: ?page=stock&section=warehouses&status=created');
            exit;
        }
    }

    public function updateWarehouse() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id'        => Input::post('id', 'int', 0),
                'name'      => Input::post('name'),
                'address'   => Input::post('address'),
                'city'      => Input::post('city'),
                'state'     => Input::post('state'),
                'zip_code'  => Input::post('zip_code', 'cep'),
                'phone'     => Input::post('phone', 'phone'),
                'notes'     => Input::post('notes'),
                'is_active' => Input::post('is_active', 'bool') ? 1 : 0,
                'is_default' => Input::post('is_default', 'bool') ? 1 : 0,
            ];

            $this->stockModel->updateWarehouse($data);
            $this->logger->log('STOCK_WAREHOUSE_UPDATE', "Armazém atualizado: {$data['name']} (ID: {$data['id']})" . ($data['is_default'] ? ' [PADRÃO]' : ''));
            header('Location: ?page=stock&section=warehouses&status=updated');
            exit;
        }
    }

    public function deleteWarehouse() {
        $id = Input::get('id', 'int', 0);
        if ($id) {
            $wh = $this->stockModel->getWarehouse($id);
            $this->stockModel->deleteWarehouse($id);
            $this->logger->log('STOCK_WAREHOUSE_DELETE', "Armazém removido: " . ($wh['name'] ?? $id));
        }
        header('Location: ?page=stock&section=warehouses&status=deleted');
        exit;
    }

    // ─── Movimentações (JSON para AJAX, ou redireciona para página unificada) ───
    public function movements() {
        $filters = [
            'warehouse_id' => Input::get('warehouse_id', 'int'),
            'product_id'   => Input::get('product_id', 'int'),
            'type'         => Input::get('type'),
            'date_from'    => Input::get('date_from', 'date'),
            'date_to'      => Input::get('date_to', 'date'),
            'limit'        => Input::get('limit', 'int', 200),
        ];

        $movements = $this->stockModel->getMovements($filters);

        // Se requisição JSON (para o mini-histórico na página de entrada)
        if (Input::get('format') === 'json') {
            header('Content-Type: application/json');
            echo json_encode($movements);
            exit;
        }

        // Redireciona para página unificada na seção de movimentações
        header('Location: ?page=stock&section=movements');
        exit;
    }

    // ─── Entrada de Estoque: redireciona para página unificada ───
    public function entry() {
        header('Location: ?page=stock&section=entry');
        exit;
    }

    // ─── AJAX: Buscar itens de estoque (para filtros dinâmicos + paginação) ───
    public function getStockItems() {
        header('Content-Type: application/json');

        $warehouseId = Input::get('warehouse_id', 'int');
        $search = Input::get('search');
        $lowStock = Input::get('low_stock') === '1';
        $page = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $allItems = $this->stockModel->getStockItems($warehouseId, $search, $lowStock);
        $total = count($allItems);
        $totalPages = max(1, ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allItems, $offset, $perPage);

        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ]);
        exit;
    }

    // ─── AJAX: Buscar movimentações (para filtros dinâmicos + paginação) ───
    public function getMovements() {
        header('Content-Type: application/json');

        $filters = [
            'warehouse_id' => Input::get('warehouse_id', 'int'),
            'product_id'   => Input::get('product_id', 'int'),
            'type'         => Input::get('type'),
            'date_from'    => Input::get('date_from', 'date'),
            'date_to'      => Input::get('date_to', 'date'),
            'limit'        => 5000, // buscar todos e paginar no PHP
        ];
        $page = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $allMovements = $this->stockModel->getMovements($filters);
        $total = count($allMovements);
        $totalPages = max(1, ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allMovements, $offset, $perPage);

        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ]);
        exit;
    }

    // ─── AJAX: Processar movimentação (entrada/saída/ajuste/transferência) ───
    public function storeMovement() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $warehouseId = Input::post('warehouse_id', 'int', 0);
        $type = Input::post('type', 'enum', 'entrada', ['entrada', 'saida', 'ajuste', 'transferencia']);
        $reason = Input::post('reason');
        $items = Input::postArray('items');
        $destWarehouseId = Input::post('destination_warehouse_id', 'int', 0);

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
            $productId = Sanitizer::int($item['product_id'] ?? 0, 0);
            $combinationId = !empty($item['combination_id']) ? Sanitizer::int($item['combination_id']) : null;
            $quantity = Sanitizer::float($item['quantity'] ?? 0, 0);

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

    // ─── AJAX: Buscar uma movimentação pelo ID ───
    public function getMovement() {
        header('Content-Type: application/json');
        $id = Input::get('id', 'int', 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }
        $movement = $this->stockModel->getMovement($id);
        if (!$movement) {
            echo json_encode(['success' => false, 'message' => 'Movimentação não encontrada.']);
            exit;
        }
        echo json_encode(['success' => true, 'movement' => $movement]);
        exit;
    }

    // ─── AJAX: Atualizar uma movimentação existente ───
    public function updateMovement() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $id = Input::post('id', 'int', 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $movement = $this->stockModel->getMovement($id);
        if (!$movement) {
            echo json_encode(['success' => false, 'message' => 'Movimentação não encontrada.']);
            exit;
        }

        // Não permitir edição de movimentações automáticas (pedidos, reversões)
        $autoTypes = ['order', 'order_reversal', 'transfer'];
        if (in_array($movement['reference_type'], $autoTypes)) {
            echo json_encode(['success' => false, 'message' => 'Movimentações automáticas não podem ser editadas.']);
            exit;
        }

        $data = [
            'type'     => Input::post('type', 'enum', $movement['type'], ['entrada', 'saida', 'ajuste']),
            'quantity' => Input::post('quantity', 'float', $movement['quantity']),
            'reason'   => Input::post('reason'),
        ];

        if ($data['quantity'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantidade deve ser maior que zero.']);
            exit;
        }

        $result = $this->stockModel->updateMovement($id, $data);
        if ($result) {
            $typeLabels = ['entrada' => 'Entrada', 'saida' => 'Saída', 'ajuste' => 'Ajuste'];
            $this->logger->log('STOCK_MOVEMENT_UPDATE', "Movimentação #{$id} atualizada: {$typeLabels[$data['type']]} — Qtd: {$data['quantity']} — Produto: {$movement['product_name']}");
            echo json_encode(['success' => true, 'message' => 'Movimentação atualizada com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar movimentação.']);
        }
        exit;
    }

    // ─── AJAX: Excluir uma movimentação ───
    public function deleteMovement() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $id = Input::post('id', 'int', 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $movement = $this->stockModel->getMovement($id);
        if (!$movement) {
            echo json_encode(['success' => false, 'message' => 'Movimentação não encontrada.']);
            exit;
        }

        // Não permitir exclusão de movimentações automáticas
        $autoTypes = ['order', 'order_reversal', 'transfer'];
        if (in_array($movement['reference_type'], $autoTypes)) {
            echo json_encode(['success' => false, 'message' => 'Movimentações automáticas não podem ser excluídas.']);
            exit;
        }

        $result = $this->stockModel->deleteMovement($id);
        if ($result) {
            $typeLabels = ['entrada' => 'Entrada', 'saida' => 'Saída', 'ajuste' => 'Ajuste', 'transferencia' => 'Transferência'];
            $this->logger->log('STOCK_MOVEMENT_DELETE', "Movimentação #{$id} excluída: {$typeLabels[$movement['type']]} — Qtd: {$movement['quantity']} — Produto: {$movement['product_name']}");
            echo json_encode(['success' => true, 'message' => 'Movimentação excluída com sucesso. Saldo revertido.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir movimentação.']);
        }
        exit;
    }

    // ─── AJAX: Buscar combinações de um produto ───
    public function getProductCombinations() {
        header('Content-Type: application/json');
        $productId = Input::get('product_id', 'int', 0);
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

        $id = Input::post('id', 'int', 0);
        $minQty = Input::post('min_quantity', 'float', 0);
        $locCode = Input::post('location_code');

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
        $warehouseId = Input::get('warehouse_id', 'int', 0);
        $productId = Input::get('product_id', 'int', 0);

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
        $id = Input::post('id', 'int', 0);
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
        $orderId = Input::get('order_id', 'int', 0);
        $warehouseId = Input::get('warehouse_id', 'int', 0);

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
