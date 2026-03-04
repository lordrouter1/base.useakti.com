<?php
require 'app/config/database.php';
require 'app/models/Financial.php';
$db = (new Database())->getConnection();
$fin = new Financial($db);

echo "=== getAllInstallments ===" . PHP_EOL;
$rows = $fin->getAllInstallments();
foreach ($rows as $r) {
    echo "  ID={$r['id']} Order={$r['order_id']} Parcela={$r['installment_number']} ";
    echo "Valor={$r['amount']} Vence={$r['due_date']} Status={$r['status']} ";
    echo "Cliente=" . ($r['customer_name'] ?? 'N/A') . PHP_EOL;
}

echo PHP_EOL . "=== getOrdersWithInstallments ===" . PHP_EOL;
$orders = $fin->getOrdersWithInstallments();
foreach ($orders as $o) {
    echo "  Pedido #{$o['id']} Total={$o['total_amount']} Parcelas={$o['total_parcelas']} Pagas={$o['parcelas_pagas']}" . PHP_EOL;
}
