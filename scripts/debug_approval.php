<?php
/**
 * Script de diagnóstico: verificar estado de aprovação dos pedidos com link de pagamento.
 * Uso: php scripts/debug_approval.php
 */

// Usar config do tenant padrão
$dbName = getenv('AKTI_DB_NAME') ?: 'akti_teste';
$dbUser = getenv('AKTI_DB_USER') ?: 'akti_sis_usr';
$dbPass = getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
$dbHost = getenv('AKTI_DB_HOST') ?: 'localhost';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erro ao conectar no banco {$dbName}: " . $e->getMessage() . "\n");
}

echo "=== Conectado ao banco: {$dbName} ===\n\n";

// Simular loop (apenas 1 banco)
$tenants = [['db_name' => $dbName]];
foreach ($tenants as $t) {

    // Verificar se coluna customer_approval_status existe
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = :db AND table_name = 'orders' AND column_name = 'customer_approval_status'"
    );
    $check->execute([':db' => $dbName]);
    $hasCol = (int)$check->fetchColumn();

    if (!$hasCol) {
        echo "[{$dbName}] ATENÇÃO: coluna customer_approval_status NÃO EXISTE!\n";
        continue;
    }

    // Verificar se coluna payment_link_url existe
    $check2 = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = :db AND table_name = 'orders' AND column_name = 'payment_link_url'"
    );
    $check2->execute([':db' => $dbName]);
    $hasPayCol = (int)$check2->fetchColumn();

    if (!$hasPayCol) {
        echo "[{$dbName}] coluna payment_link_url não existe\n";
        continue;
    }

    // Buscar pedidos com link de pagamento
    $stmt = $pdo->query(
        "SELECT o.id, o.customer_id, o.payment_link_url, o.customer_approval_status, 
                o.pipeline_stage, o.status, c.name as customer_name
         FROM orders o
         LEFT JOIN customers c ON c.id = o.customer_id
         WHERE o.payment_link_url IS NOT NULL AND o.payment_link_url != ''
         ORDER BY o.id DESC
         LIMIT 20"
    );
    $orders = $stmt->fetchAll();

    if (empty($orders)) {
        echo "[{$dbName}] Nenhum pedido com link de pagamento.\n";
        continue;
    }

    echo "\n=== [{$dbName}] Pedidos com link de pagamento ===\n";
    echo str_pad("ID", 6) . str_pad("Cliente", 40) . str_pad("Stage", 15) . str_pad("Status", 15) . str_pad("Approval", 15) . "Link\n";
    echo str_repeat("-", 130) . "\n";
    foreach ($orders as $o) {
        echo str_pad($o['id'], 6)
            . str_pad($o['customer_name'] ?? '—', 40)
            . str_pad($o['pipeline_stage'] ?? '—', 15)
            . str_pad($o['status'] ?? '—', 15)
            . str_pad($o['customer_approval_status'] ?? 'NULL', 15)
            . substr($o['payment_link_url'], 0, 60) . "\n";
    }

    // Contar os que precisam de fix
    $stmtFix = $pdo->query(
        "SELECT COUNT(*) FROM orders 
         WHERE payment_link_url IS NOT NULL AND payment_link_url != ''
           AND (customer_approval_status IS NULL)"
    );
    $countFix = (int)$stmtFix->fetchColumn();
    if ($countFix > 0) {
        echo "\n  >>> {$countFix} pedido(s) com link gerado mas SEM status de aprovação (NULL)\n";
        echo "  >>> Esses pedidos NÃO aparecem na aba 'Aprovação' do portal!\n";
    }
}
echo "\nDone.\n";
