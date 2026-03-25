<?php
/**
 * Diagnóstico: verifica qual banco a aplicação realmente usa
 * e se a coluna customer_approval_status existe nele.
 */
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();
$dbName = $db->query("SELECT DATABASE()")->fetchColumn();
$user = $db->query("SELECT CURRENT_USER()")->fetchColumn();

echo "=== Conexão via TenantManager ===\n";
echo "  Database: {$dbName}\n";
echo "  User: {$user}\n\n";

// Verificar colunas
echo "--- Colunas customer_approval_* na tabela orders ---\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'customer_approval%'");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($cols)) {
        echo "  *** NENHUMA COLUNA ENCONTRADA! ***\n";
        echo "  >>> ESTE É O PROBLEMA! A coluna não existe neste banco.\n";
    } else {
        foreach ($cols as $c) {
            echo "  {$c['Field']} => Type: {$c['Type']} | Null: {$c['Null']} | Default: {$c['Default']}\n";
        }
    }
} catch (Exception $e) {
    echo "  ERRO: {$e->getMessage()}\n";
}

// Verificar pedidos
echo "\n--- Pedidos com customer_approval_status ---\n";
try {
    $stmt2 = $db->query("SELECT id, customer_id, pipeline_stage, customer_approval_status, customer_approval_at FROM orders WHERE customer_approval_status IS NOT NULL LIMIT 10");
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  Nenhum pedido com approval_status preenchido.\n";
    } else {
        foreach ($rows as $r) {
            echo "  #{$r['id']} stage={$r['pipeline_stage']} approval={$r['customer_approval_status']} at={$r['customer_approval_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "  ERRO: {$e->getMessage()}\n";
}

// Todos os pedidos
echo "\n--- Todos os pedidos ---\n";
try {
    $stmt3 = $db->query("SELECT id, customer_id, pipeline_stage, status, customer_approval_status, payment_link_url FROM orders ORDER BY id DESC LIMIT 10");
    $allOrders = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allOrders as $o) {
        echo "  #{$o['id']} stage={$o['pipeline_stage']} status={$o['status']} approval=" . ($o['customer_approval_status'] ?? 'NULL') . " link=" . ($o['payment_link_url'] ? 'SIM' : 'NAO') . "\n";
    }
} catch (Exception $e) {
    echo "  ERRO: {$e->getMessage()}\n";
}

echo "\nDone.\n";
