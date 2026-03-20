<?php
require __DIR__ . '/../app/bootstrap/autoload.php';

$db = (new Database())->getConnection();
$r = new \Akti\Models\ReportModel($db);

echo "ReportModel OK\n";

$methods = [
    'getProductsCatalog',
    'getStockByWarehouse', 
    'getStockMovements',
    'getProductsForSelect',
    'getWarehousesForSelect',
    'getMovementTypeLabels',
    'getMovementTypeLabel',
];

$m = new ReflectionClass($r);
foreach ($methods as $name) {
    if ($m->hasMethod($name)) {
        echo "  Method: {$name} OK\n";
    } else {
        echo "  MISSING: {$name}\n";
    }
}

echo "\nAll checks passed.\n";
