<?php
$dbName = getenv('AKTI_DB_NAME') ?: 'akti_teste';
$dbUser = getenv('AKTI_DB_USER') ?: 'akti_sis_usr';
$dbPass = getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
$dbHost = getenv('AKTI_DB_HOST') ?: 'localhost';

$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== Banco: {$dbName} ===\n\n";

// Buscar cliente Julio Cesar
echo "--- Buscando cliente 'julio' ou 'kornhardt' ---\n";
$stmt = $pdo->prepare("SELECT id, name, email, phone FROM customers WHERE name LIKE :n1 OR name LIKE :n2 LIMIT 10");
$stmt->execute([':n1' => '%julio%', ':n2' => '%kornhardt%']);
$customers = $stmt->fetchAll();

if (empty($customers)) {
    echo "  Nenhum cliente encontrado com esse nome.\n";
    // Listar todos os clientes para debug
    echo "\n--- Todos os clientes ---\n";
    $all = $pdo->query("SELECT id, name, email FROM customers ORDER BY id DESC LIMIT 20")->fetchAll();
    foreach ($all as $c) {
        echo "  [{$c['id']}] {$c['name']} - {$c['email']}\n";
    }
} else {
    foreach ($customers as $c) {
        echo "  [{$c['id']}] {$c['name']} - {$c['email']} - {$c['phone']}\n";
        
        // Buscar pedidos desse cliente
        $stmtO = $pdo->prepare(
            "SELECT o.id, o.status, o.pipeline_stage, o.total_amount, o.payment_link_url, 
                    o.payment_link_gateway, o.customer_approval_status, o.created_at
             FROM orders o WHERE o.customer_id = :cid ORDER BY o.id DESC"
        );
        $stmtO->execute([':cid' => $c['id']]);
        $orders = $stmtO->fetchAll();
        
        if (empty($orders)) {
            echo "    Nenhum pedido.\n";
        } else {
            echo "    Pedidos:\n";
            foreach ($orders as $o) {
                echo "    #{$o['id']} | stage: {$o['pipeline_stage']} | status: {$o['status']} | total: {$o['total_amount']}";
                echo " | approval: " . ($o['customer_approval_status'] ?? 'NULL');
                echo " | link: " . (!empty($o['payment_link_url']) ? substr($o['payment_link_url'], 0, 50) : 'SEM LINK');
                echo " | created: {$o['created_at']}\n";
            }
        }
        
        // Verificar acesso portal
        $stmtP = $pdo->prepare("SELECT * FROM customer_portal_access WHERE customer_id = :cid");
        $stmtP->execute([':cid' => $c['id']]);
        $portal = $stmtP->fetch();
        if ($portal) {
            echo "    Portal: ativo={$portal['is_active']}, email={$portal['email']}, last_login={$portal['last_login_at']}\n";
        } else {
            echo "    Portal: SEM ACESSO CONFIGURADO\n";
        }
    }
}

// Listar pedidos que estão na etapa financeiro (onde links são gerados)
echo "\n--- Pedidos na etapa 'financeiro' ---\n";
$stmtF = $pdo->query(
    "SELECT o.id, o.customer_id, o.total_amount, o.payment_link_url, o.customer_approval_status,
            c.name as customer_name
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     WHERE o.pipeline_stage = 'financeiro'
     ORDER BY o.id DESC LIMIT 20"
);
$fOrders = $stmtF->fetchAll();
if (empty($fOrders)) {
    echo "  Nenhum pedido na etapa financeiro.\n";
} else {
    foreach ($fOrders as $o) {
        echo "  #{$o['id']} | {$o['customer_name']} | total: {$o['total_amount']}";
        echo " | link: " . (!empty($o['payment_link_url']) ? 'SIM' : 'NAO');
        echo " | approval: " . ($o['customer_approval_status'] ?? 'NULL') . "\n";
    }
}

// Verificar gateway transactions
echo "\n--- Verificando transações de gateway ---\n";
try {
    $stmtGw = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='{$dbName}' AND table_name='gateway_transactions'");
    if ((int)$stmtGw->fetchColumn() > 0) {
        $gwTx = $pdo->query("SELECT * FROM gateway_transactions ORDER BY id DESC LIMIT 5")->fetchAll();
        if (empty($gwTx)) {
            echo "  Nenhuma transação.\n";
        } else {
            foreach ($gwTx as $tx) {
                echo "  [{$tx['id']}] order_id={$tx['order_id']} | gateway={$tx['gateway_slug']} | event={$tx['event_type']} | amount={$tx['amount']}\n";
            }
        }
    } else {
        echo "  Tabela gateway_transactions não existe.\n";
    }
} catch (Exception $e) {
    echo "  Erro: {$e->getMessage()}\n";
}

echo "\nDone!\n";
