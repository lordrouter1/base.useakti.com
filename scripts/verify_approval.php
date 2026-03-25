<?php
/**
 * Teste final: verificar que o pedido #4 do Julio aparece como aprovação no portal.
 */
$dbName = getenv('AKTI_DB_NAME') ?: 'akti_teste';
$dbUser = getenv('AKTI_DB_USER') ?: 'akti_sis_usr';
$dbPass = getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
$dbHost = getenv('AKTI_DB_HOST') ?: 'localhost';

$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== Verificação Final ===\n\n";

// 1. Verificar que as colunas existem
$cols = ['customer_approval_status','customer_approval_at','customer_approval_ip','customer_approval_notes'];
$allOk = true;
foreach ($cols as $col) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=:db AND table_name='orders' AND column_name=:col");
    $s->execute([':db' => $dbName, ':col' => $col]);
    $exists = (int)$s->fetchColumn() > 0;
    echo ($exists ? '✅' : '❌') . " Coluna orders.{$col}\n";
    if (!$exists) $allOk = false;
}

// 2. Verificar pedido #4 do Julio
echo "\n--- Pedido #4 ---\n";
$stmt = $pdo->query(
    "SELECT o.id, o.customer_id, o.pipeline_stage, o.status, o.customer_approval_status,
            o.payment_link_url, c.name
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     WHERE o.id = 4"
);
$order = $stmt->fetch();
if ($order) {
    echo "  Cliente: {$order['name']}\n";
    echo "  Stage: {$order['pipeline_stage']}\n";
    echo "  Status: {$order['status']}\n";
    echo "  Approval: {$order['customer_approval_status']}\n";
    echo "  Payment Link: " . (!empty($order['payment_link_url']) ? 'SIM' : 'NAO') . "\n";
    
    $ok = $order['customer_approval_status'] === 'pendente';
    echo "\n" . ($ok ? '✅' : '❌') . " Pedido aparece como 'pendente' de aprovação: " . ($ok ? 'SIM' : 'NAO') . "\n";
} else {
    echo "  ❌ Pedido #4 não encontrado\n";
}

// 3. Verificar acesso portal
echo "\n--- Portal do Julio ---\n";
$stmt2 = $pdo->prepare("SELECT * FROM customer_portal_access WHERE customer_id = :cid");
$stmt2->execute([':cid' => $order['customer_id'] ?? 0]);
$portal = $stmt2->fetch();
if ($portal) {
    echo "  ✅ Acesso ativo: email={$portal['email']}, is_active={$portal['is_active']}\n";
} else {
    echo "  ❌ Sem acesso ao portal\n";
}

// 4. Simular consulta do portal - aba Aprovação
echo "\n--- Simulando aba 'Aprovação' do portal ---\n";
$cid = $order['customer_id'] ?? 0;
$stmt3 = $pdo->prepare(
    "SELECT o.id, o.pipeline_stage, o.customer_approval_status, o.total_amount,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
     FROM orders o
     WHERE o.customer_id = :cid AND o.customer_approval_status = 'pendente'
     ORDER BY o.created_at DESC"
);
$stmt3->execute([':cid' => $cid]);
$approvalOrders = $stmt3->fetchAll();
if (empty($approvalOrders)) {
    echo "  ❌ Nenhum pedido na aba Aprovação!\n";
} else {
    foreach ($approvalOrders as $ao) {
        echo "  ✅ Pedido #{$ao['id']} | stage: {$ao['pipeline_stage']} | items: {$ao['items_count']} | total: {$ao['total_amount']}\n";
    }
}

// 5. Verificar link de catálogo ativo
echo "\n--- Link de Catálogo ---\n";
try {
    $stmt4 = $pdo->prepare("SELECT token, require_confirmation, is_active, expires_at FROM catalog_links WHERE order_id = :oid AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt4->execute([':oid' => 4]);
    $catalog = $stmt4->fetch();
    if ($catalog) {
        echo "  ✅ Link ativo: token=" . substr($catalog['token'], 0, 20) . "...\n";
        echo "     Require confirmation: " . ($catalog['require_confirmation'] ? 'SIM' : 'NAO') . "\n";
        echo "     Expires at: " . ($catalog['expires_at'] ?? 'nunca') . "\n";
    } else {
        echo "  ❌ Nenhum link de catálogo ativo\n";
    }
} catch (PDOException $e) {
    echo "  ⚠ Erro: {$e->getMessage()}\n";
}

echo "\n=== Verificação concluída ===\n";
