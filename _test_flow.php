<?php
require 'app/config/database.php';
require 'app/models/Financial.php';
$db = (new Database())->getConnection();
$fin = new Financial($db);

echo "=== 1. Pagar parcela 1 ===" . PHP_EOL;
$fin->payInstallment(1, [
    'paid_date' => '2026-03-04',
    'paid_amount' => 5.00,
    'payment_method' => 'pix',
    'notes' => 'Teste pagamento',
    'user_id' => 1,
]);

$parcelas = $fin->getInstallments(1);
foreach ($parcelas as $p) {
    echo "  Parcela {$p['installment_number']}: status={$p['status']} pago={$p['paid_amount']} confirmado={$p['is_confirmed']}" . PHP_EOL;
}

echo PHP_EOL . "=== 2. Confirmar parcela 1 ===" . PHP_EOL;
$fin->confirmInstallment(1, 1);

$parcelas = $fin->getInstallments(1);
foreach ($parcelas as $p) {
    echo "  Parcela {$p['installment_number']}: status={$p['status']} confirmado={$p['is_confirmed']}" . PHP_EOL;
}

// Check order payment_status
$s = $db->query("SELECT payment_status FROM orders WHERE id = 1");
echo "  Order payment_status: " . $s->fetchColumn() . PHP_EOL;

echo PHP_EOL . "=== 3. financial_transactions apos pagamento ===" . PHP_EOL;
$txs = $fin->getTransactions();
foreach ($txs as $t) {
    echo "  ID={$t['id']} {$t['type']} {$t['category']} '{$t['description']}' R\${$t['amount']} ref={$t['reference_type']}:{$t['reference_id']}" . PHP_EOL;
}

echo PHP_EOL . "=== 4. Estornar parcela 1 ===" . PHP_EOL;
$fin->cancelInstallment(1, 1);

$parcelas = $fin->getInstallments(1);
foreach ($parcelas as $p) {
    echo "  Parcela {$p['installment_number']}: status={$p['status']} pago={$p['paid_amount']} confirmado={$p['is_confirmed']}" . PHP_EOL;
}

$s = $db->query("SELECT payment_status FROM orders WHERE id = 1");
echo "  Order payment_status: " . $s->fetchColumn() . PHP_EOL;

echo PHP_EOL . "=== 5. financial_transactions apos estorno ===" . PHP_EOL;
$txs = $fin->getTransactions();
foreach ($txs as $t) {
    echo "  ID={$t['id']} {$t['type']} {$t['category']} '{$t['description']}' R\${$t['amount']} ref={$t['reference_type']}:{$t['reference_id']}" . PHP_EOL;
}

echo PHP_EOL . "=== TESTE CONCLUIDO ===" . PHP_EOL;
