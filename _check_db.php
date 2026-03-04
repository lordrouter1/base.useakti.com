<?php
require 'app/config/database.php';
$db = (new Database())->getConnection();

echo "=== Pedido #1 ===" . PHP_EOL;
$s = $db->query("SELECT id, total_amount, discount, payment_status, payment_method, installments, pipeline_stage FROM orders WHERE id = 1");
$o = $s->fetch(PDO::FETCH_ASSOC);
print_r($o);

echo PHP_EOL . "=== Parcelas do Pedido #1 ===" . PHP_EOL;
$s = $db->query("SELECT * FROM order_installments WHERE order_id = 1 ORDER BY installment_number");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) echo "Nenhuma parcela encontrada!" . PHP_EOL;
else foreach ($rows as $r) print_r($r);

echo PHP_EOL . "=== Total parcelas no sistema ===" . PHP_EOL;
$s = $db->query("SELECT COUNT(*) as total FROM order_installments");
echo $s->fetchColumn() . PHP_EOL;

echo PHP_EOL . "=== financial_transactions (ultimas 5) ===" . PHP_EOL;
$s = $db->query("SELECT id, type, category, description, amount, transaction_date, reference_type, reference_id FROM financial_transactions ORDER BY id DESC LIMIT 5");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) echo "Nenhuma transacao encontrada!" . PHP_EOL;
else foreach ($rows as $r) print_r($r);
