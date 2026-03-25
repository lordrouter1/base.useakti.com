<?php
/**
 * Script para marcar pedido como pendente de aprovação no portal.
 * Uso: php scripts/fix_order_approval.php
 */
$dbName = getenv('AKTI_DB_NAME') ?: 'akti_teste';
$dbUser = getenv('AKTI_DB_USER') ?: 'akti_sis_usr';
$dbPass = getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
$dbHost = getenv('AKTI_DB_HOST') ?: 'localhost';

$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Verificar se a coluna existe
$check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=:db AND table_name='orders' AND column_name='customer_approval_status'");
$check->execute([':db' => $dbName]);
if ((int)$check->fetchColumn() === 0) {
    die("Coluna customer_approval_status não existe!\n");
}

// Buscar pedidos na etapa orcamento que tenham links de catálogo ativos com confirmação
echo "--- Pedidos com links de catálogo ativos com require_confirmation ---\n";
try {
    $stmt = $pdo->query(
        "SELECT cl.order_id, cl.token, cl.require_confirmation, cl.is_active, cl.expires_at,
                o.pipeline_stage, o.customer_approval_status, c.name as customer_name
         FROM catalog_links cl
         JOIN orders o ON o.id = cl.order_id
         LEFT JOIN customers c ON c.id = o.customer_id
         WHERE cl.is_active = 1
         ORDER BY cl.order_id DESC"
    );
    $links = $stmt->fetchAll();
    
    if (empty($links)) {
        echo "  Nenhum link de catálogo ativo.\n";
    } else {
        foreach ($links as $l) {
            echo "  Pedido #{$l['order_id']} | {$l['customer_name']} | stage: {$l['pipeline_stage']}";
            echo " | confirm: " . ($l['require_confirmation'] ? 'SIM' : 'NAO');
            echo " | approval: " . ($l['customer_approval_status'] ?? 'NULL');
            echo " | token: " . substr($l['token'], 0, 20) . "...\n";
        }
    }
} catch (PDOException $e) {
    echo "  Erro ao buscar catalog_links: {$e->getMessage()}\n";
}

// Buscar todos os pedidos que não estão concluídos/cancelados e verificar
echo "\n--- Pedidos pendentes sem status de aprovação ---\n";
$stmt2 = $pdo->query(
    "SELECT o.id, o.pipeline_stage, o.status, o.customer_approval_status, o.payment_link_url,
            c.name as customer_name
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     WHERE o.status NOT IN ('concluido','cancelado')
     ORDER BY o.id DESC"
);
$orders = $stmt2->fetchAll();
foreach ($orders as $o) {
    echo "  #{$o['id']} | {$o['customer_name']} | stage: {$o['pipeline_stage']} | status: {$o['status']}";
    echo " | approval: " . ($o['customer_approval_status'] ?? 'NULL');
    echo " | payment_link: " . (!empty($o['payment_link_url']) ? 'SIM' : 'NAO') . "\n";
}

// Corrigir: pedidos na etapa orcamento com link de catálogo ativo (require_confirmation=1) => marcar pendente
echo "\n--- Corrigindo pedidos com catálogo de confirmação ---\n";
try {
    $fix = $pdo->prepare(
        "UPDATE orders o
         INNER JOIN catalog_links cl ON cl.order_id = o.id AND cl.is_active = 1 AND cl.require_confirmation = 1
         SET o.customer_approval_status = 'pendente'
         WHERE o.customer_approval_status IS NULL
           AND o.status NOT IN ('concluido','cancelado')"
    );
    $fix->execute();
    echo "  Pedidos corrigidos via catálogo: {$fix->rowCount()}\n";
} catch (PDOException $e) {
    echo "  Tabela catalog_links pode não existir: {$e->getMessage()}\n";
}

// Corrigir: pedidos com payment_link_url => marcar pendente
$fix2 = $pdo->prepare(
    "UPDATE orders 
     SET customer_approval_status = 'pendente'
     WHERE payment_link_url IS NOT NULL AND payment_link_url != ''
       AND customer_approval_status IS NULL
       AND status NOT IN ('concluido','cancelado')"
);
$fix2->execute();
echo "  Pedidos corrigidos via payment_link: {$fix2->rowCount()}\n";

// Verificar resultado final
echo "\n--- Estado final ---\n";
$stmt3 = $pdo->query(
    "SELECT o.id, o.pipeline_stage, o.customer_approval_status, c.name as customer_name
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     WHERE o.status NOT IN ('concluido','cancelado')
     ORDER BY o.id DESC"
);
foreach ($stmt3->fetchAll() as $o) {
    echo "  #{$o['id']} | {$o['customer_name']} | stage: {$o['pipeline_stage']}";
    echo " | approval: " . ($o['customer_approval_status'] ?? 'NULL') . "\n";
}

echo "\nDone!\n";
