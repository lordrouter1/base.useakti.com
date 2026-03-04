<?php
require 'app/config/database.php';
require 'app/models/Financial.php';
$db = (new Database())->getConnection();
$fin = new Financial($db);

// Pedido #1: total=10, discount=0, down_payment=0, installments=2
$orderId = 1;
$totalAmount = 10.00 - 0.00; // total - discount
$numInstallments = 2;
$downPayment = 0.00;
$startDate = '2026-03-04';

$fin->generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment, $startDate);

echo "Parcelas geradas para pedido #1!" . PHP_EOL;

// Verificar
$parcelas = $fin->getInstallments($orderId);
foreach ($parcelas as $p) {
    echo "  Parcela {$p['installment_number']}: R\${$p['amount']} vence {$p['due_date']} status={$p['status']}" . PHP_EOL;
}

echo PHP_EOL . "Total de parcelas no sistema: ";
$s = $db->query("SELECT COUNT(*) FROM order_installments");
echo $s->fetchColumn() . PHP_EOL;
