<?php
/**
 * Diagnóstico: verifica o pedido #4 e como apareceria no pipeline.
 */
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();

echo "=== Verificando pedido #4 no pipeline ===\n\n";

$stmt = $db->prepare("SELECT id, customer_id, pipeline_stage, status, customer_approval_status, 
                              customer_approval_at, payment_link_url, total_amount
                       FROM orders WHERE id = 4");
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Pedido #4 não encontrado!\n";
    exit;
}

echo "Pedido #4:\n";
echo "  pipeline_stage: {$order['pipeline_stage']}\n";
echo "  status: {$order['status']}\n";
echo "  customer_approval_status: " . ($order['customer_approval_status'] ?? 'NULL') . "\n";
echo "  customer_approval_at: " . ($order['customer_approval_at'] ?? 'NULL') . "\n";
echo "  payment_link_url: " . ($order['payment_link_url'] ?? 'NULL') . "\n";
echo "  total_amount: {$order['total_amount']}\n";

$excluded = in_array($order['pipeline_stage'], ['concluido', 'cancelado']) || $order['status'] === 'cancelado';
echo "\n  Excluído do Kanban? " . ($excluded ? 'SIM (concluido/cancelado)' : 'NÃO') . "\n";

// Verificar TODOS os pedidos com customer_approval_status
echo "\n=== TODOS os pedidos com customer_approval_status ===\n";
$stmt2 = $db->query("SELECT id, pipeline_stage, status, customer_approval_status FROM orders WHERE customer_approval_status IS NOT NULL ORDER BY id DESC");
$all = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $o) {
    $excl = in_array($o['pipeline_stage'], ['concluido', 'cancelado']) || $o['status'] === 'cancelado';
    echo "  #{$o['id']} stage={$o['pipeline_stage']} status={$o['status']} approval={$o['customer_approval_status']} excluded=" . ($excl ? 'Y' : 'N') . "\n";
}

echo "\nDone.\n";
