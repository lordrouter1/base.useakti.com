<?php
require 'app/config/database.php';
$db = (new Database())->getConnection();

echo "=== Tabela order_installments ===" . PHP_EOL;
$s = $db->query("SHOW COLUMNS FROM order_installments");
$cols = $s->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  " . $c['Field'] . " (" . $c['Type'] . ")" . PHP_EOL;

echo PHP_EOL . "=== Pedido #1 completo ===" . PHP_EOL;
$s = $db->query("SELECT * FROM orders WHERE id = 1");
$o = $s->fetch(PDO::FETCH_ASSOC);
print_r($o);

echo PHP_EOL . "=== financial_transactions table ===" . PHP_EOL;
$s = $db->query("SHOW COLUMNS FROM financial_transactions");
$cols = $s->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  " . $c['Field'] . " (" . $c['Type'] . ")" . PHP_EOL;
