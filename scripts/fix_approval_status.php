<?php
$dbName = getenv('AKTI_DB_NAME') ?: 'akti_teste';
$dbUser = getenv('AKTI_DB_USER') ?: 'akti_sis_usr';
$dbPass = getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
$dbHost = getenv('AKTI_DB_HOST') ?: 'localhost';

$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "=== Banco: {$dbName} ===\n\n";

// 1. Verificar colunas
echo "--- Colunas atuais ---\n";
$cols = ['customer_approval_status','customer_approval_at','customer_approval_ip','customer_approval_notes','portal_origin','payment_link_url'];
foreach ($cols as $col) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=:db AND table_name='orders' AND column_name=:col");
    $s->execute([':db' => $dbName, ':col' => $col]);
    $exists = (int)$s->fetchColumn();
    echo "  {$col}: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

// 2. Adicionar colunas faltantes
echo "\n--- Adicionando colunas faltantes ---\n";

$alterations = [
    'customer_approval_status' => "ALTER TABLE orders ADD COLUMN customer_approval_status ENUM('pendente','aprovado','recusado') DEFAULT NULL COMMENT 'Status de aprovação do cliente via portal'",
    'customer_approval_at'     => "ALTER TABLE orders ADD COLUMN customer_approval_at DATETIME DEFAULT NULL COMMENT 'Data/hora da aprovação/recusa pelo cliente'",
    'customer_approval_ip'     => "ALTER TABLE orders ADD COLUMN customer_approval_ip VARCHAR(45) DEFAULT NULL COMMENT 'IP do cliente no momento da aprovação'",
    'customer_approval_notes'  => "ALTER TABLE orders ADD COLUMN customer_approval_notes TEXT DEFAULT NULL COMMENT 'Observações do cliente na aprovação/recusa'",
    'portal_origin'            => "ALTER TABLE orders ADD COLUMN portal_origin TINYINT(1) DEFAULT 0 COMMENT 'Se o pedido foi originado pelo portal do cliente'",
];

foreach ($alterations as $col => $sql) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=:db AND table_name='orders' AND column_name=:col");
    $s->execute([':db' => $dbName, ':col' => $col]);
    if ((int)$s->fetchColumn() === 0) {
        $pdo->exec($sql);
        echo "  + {$col} adicionada!\n";
    } else {
        echo "  - {$col} já existe.\n";
    }
}

// 3. Adicionar índice se não existir
$s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=:db AND table_name='orders' AND index_name='idx_orders_customer_portal'");
$s->execute([':db' => $dbName]);
if ((int)$s->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE orders ADD INDEX idx_orders_customer_portal (customer_id, status, pipeline_stage)");
    echo "  + idx_orders_customer_portal criado!\n";
} else {
    echo "  - idx_orders_customer_portal já existe.\n";
}

// 4. Verificar tabelas do portal
$portalTables = ['customer_portal_access','customer_portal_sessions','customer_portal_messages','customer_portal_config'];
echo "\n--- Tabelas do portal ---\n";
foreach ($portalTables as $tbl) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:db AND table_name=:tbl");
    $s->execute([':db' => $dbName, ':tbl' => $tbl]);
    echo "  {$tbl}: " . ((int)$s->fetchColumn() > 0 ? 'EXISTS' : 'MISSING') . "\n";
}

// 5. Agora buscar pedidos com link de pagamento e corrigir status de aprovação
echo "\n--- Pedidos com link de pagamento ---\n";
$stmt = $pdo->query(
    "SELECT o.id, o.customer_id, o.payment_link_url, o.customer_approval_status, 
            o.pipeline_stage, o.status, c.name as customer_name
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     WHERE o.payment_link_url IS NOT NULL AND o.payment_link_url != ''
     ORDER BY o.id DESC
     LIMIT 20"
);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    echo "  Nenhum pedido com link de pagamento.\n";
} else {
    echo str_pad("ID", 6) . str_pad("Cliente", 40) . str_pad("Stage", 15) . str_pad("Approval", 15) . "\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($orders as $o) {
        echo str_pad($o['id'], 6)
            . str_pad($o['customer_name'] ?? '-', 40)
            . str_pad($o['pipeline_stage'] ?? '-', 15)
            . str_pad($o['customer_approval_status'] ?? 'NULL', 15)
            . "\n";
    }
}

// 6. Corrigir: definir 'pendente' para pedidos com link mas sem approval_status
$fix = $pdo->prepare(
    "UPDATE orders 
     SET customer_approval_status = 'pendente'
     WHERE payment_link_url IS NOT NULL AND payment_link_url != ''
       AND customer_approval_status IS NULL
       AND status NOT IN ('concluido','cancelado')"
);
$fix->execute();
$fixCount = $fix->rowCount();

echo "\n--- FIX: {$fixCount} pedido(s) atualizados para customer_approval_status='pendente' ---\n";

// 7. Verificar resultado
if ($fixCount > 0) {
    echo "\n--- Resultado após correção ---\n";
    $stmt2 = $pdo->query(
        "SELECT o.id, o.customer_id, o.customer_approval_status, c.name as customer_name
         FROM orders o
         LEFT JOIN customers c ON c.id = o.customer_id
         WHERE o.payment_link_url IS NOT NULL AND o.payment_link_url != ''
         ORDER BY o.id DESC
         LIMIT 20"
    );
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $o) {
        echo "  #{$o['id']} - {$o['customer_name']} => approval: {$o['customer_approval_status']}\n";
    }
}

echo "\nDone!\n";
