<?php
/**
 * Diagnóstico rápido: verifica colunas customer_approval_* na tabela orders
 * e testa se o Pipeline::getOrderDetail retorna o campo.
 */
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();
$dbName = $db->query("SELECT DATABASE()")->fetchColumn();

echo "=== Database: {$dbName} ===\n\n";

// 1. Verificar colunas
echo "--- Colunas customer_approval_* ---\n";
$stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'customer_approval%'");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($cols)) {
    echo "  NENHUMA COLUNA ENCONTRADA!\n";
} else {
    foreach ($cols as $c) {
        echo "  {$c['Field']} => Type: {$c['Type']} | Null: {$c['Null']} | Default: {$c['Default']}\n";
    }
}

// 2. Buscar qualquer pedido com customer_approval_status preenchido
echo "\n--- Pedidos com customer_approval_status preenchido ---\n";
try {
    $stmt2 = $db->query("SELECT id, customer_id, customer_approval_status, customer_approval_at FROM orders WHERE customer_approval_status IS NOT NULL LIMIT 5");
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  Nenhum pedido com customer_approval_status preenchido.\n";
    } else {
        foreach ($rows as $r) {
            echo "  Pedido #{$r['id']} (customer_id={$r['customer_id']}) => status={$r['customer_approval_status']} | at={$r['customer_approval_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "  ERRO: {$e->getMessage()}\n";
}

// 3. Simular Pipeline::getOrderDetail para um pedido
echo "\n--- Simulando Pipeline::getOrderDetail ---\n";
try {
    $stmt3 = $db->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
    $lastOrder = $stmt3->fetchColumn();
    if ($lastOrder) {
        $stmt4 = $db->prepare("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
        $stmt4->execute([$lastOrder]);
        $order = $stmt4->fetch(PDO::FETCH_ASSOC);
        echo "  Pedido #{$lastOrder}:\n";
        echo "    customer_approval_status: " . var_export($order['customer_approval_status'] ?? 'KEY_NOT_EXISTS', true) . "\n";
        echo "    Chaves presentes: " . (array_key_exists('customer_approval_status', $order) ? 'SIM' : 'NÃO') . "\n";
    } else {
        echo "  Nenhum pedido encontrado.\n";
    }
} catch (Exception $e) {
    echo "  ERRO: {$e->getMessage()}\n";
}

// 4. Verificar se multi-tenant pode ser o problema
echo "\n--- Verificando tabela (multi-tenant) ---\n";
try {
    $stmt5 = $db->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '{$dbName}' AND table_name = 'orders' AND column_name = 'customer_approval_status'");
    $exists = (int)$stmt5->fetchColumn();
    echo "  Coluna customer_approval_status existe: " . ($exists ? 'SIM' : 'NÃO') . "\n";
} catch (Exception $e) {
    echo "  ERRO: {$e->getMessage()}\n";
}

echo "\nDone.\n";
