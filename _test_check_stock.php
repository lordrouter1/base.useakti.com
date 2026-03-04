<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_GET['order_id'] = 1;

require 'app/config/database.php';
$db = (new Database())->getConnection();
require 'app/models/Stock.php';
require 'app/models/Order.php';
require 'app/models/Product.php';

$stockModel = new Stock($db);
$orderModel = new Order($db);
$productModel = new Product($db);

$orderItems = $orderModel->getItems(1);
$warehouses = $stockModel->getAllWarehouses(true);
$defaultWarehouse = $stockModel->getDefaultWarehouse();
$defaultWarehouseId = $defaultWarehouse ? $defaultWarehouse['id'] : null;
$warehouseId = $defaultWarehouseId;

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
            'id' => $item['id'],
            'product_name' => $item['product_name'] ?? ($product['name'] ?? '—'),
            'combination_label' => $item['combination_label'] ?? null,
            'quantity' => (int)$item['quantity'],
            'use_stock_control' => $useStock,
            'stock_available' => (float)$stockQty,
            'sufficient' => $sufficient,
        ];
    }
}

$response = [
    'success' => true,
    'warehouses' => $warehouses,
    'default_warehouse_id' => $defaultWarehouseId,
    'warehouse_id' => $warehouseId,
    'items' => $items,
    'all_from_stock' => $allFromStock,
];

echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
