<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
require 'app/config/database.php';
$db = (new Database())->getConnection();
require 'app/models/Stock.php';
$stock = new Stock($db);
$whs = $stock->getAllWarehouses(true);
$default = $stock->getDefaultWarehouse();
echo "Warehouses: " . json_encode($whs, JSON_PRETTY_PRINT) . "\n";
echo "Default: " . json_encode($default, JSON_PRETTY_PRINT) . "\n";
echo "Stock in WH1 for product 1: " . $stock->getProductStockInWarehouse(1, 1, null) . "\n";
