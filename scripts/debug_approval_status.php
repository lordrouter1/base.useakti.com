<?php
/**
 * Diagnóstico temporário: verificar estado das colunas de aprovação
 */
require __DIR__ . '/../app/bootstrap/autoload.php';
require_once __DIR__ . '/../app/config/database.php';

$db = (new \Database())->getConnection();

echo "=== Colunas customer_approval_* na tabela orders ===\n";
$stmt = $db->query("SELECT column_name, column_type, column_default, is_nullable 
                     FROM information_schema.columns 
                     WHERE table_schema = DATABASE() 
                       AND table_name = 'orders' 
                       AND column_name LIKE 'customer_approval%'");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['column_name']} => {$c['column_type']} (default: {$c['column_default']}, nullable: {$c['is_nullable']})\n";
}
if (empty($cols)) {
    echo "  NENHUMA COLUNA ENCONTRADA!\n";
}

echo "\n=== Pedidos com customer_approval_status preenchido ===\n";
$stmt = $db->query("SELECT id, customer_id, customer_approval_status, customer_approval_at, 
                            customer_approval_ip, customer_approval_notes,
                            payment_link_url
                     FROM orders 
                     WHERE customer_approval_status IS NOT NULL 
                     ORDER BY id DESC LIMIT 15");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  Pedido #{$r['id']} (customer_id={$r['customer_id']}) => status={$r['customer_approval_status']}";
    echo " | at=" . ($r['customer_approval_at'] ?? 'NULL');
    echo " | ip=" . ($r['customer_approval_ip'] ?? 'NULL');
    echo " | link=" . (!empty($r['payment_link_url']) ? 'SIM' : 'NÃO');
    echo "\n";
}

echo "\n=== Teste direto: updateApprovalStatus via PortalAccess ===\n";
// Buscar um pedido pendente para testar
$stmt = $db->query("SELECT id, customer_id FROM orders WHERE customer_approval_status = 'pendente' LIMIT 1");
$pending = $stmt->fetch(PDO::FETCH_ASSOC);
if ($pending) {
    echo "  Pedido pendente encontrado: #{$pending['id']} (customer_id={$pending['customer_id']})\n";
    
    $pa = new \Akti\Models\PortalAccess($db);
    
    // Testar se o update funciona
    $result = $pa->updateApprovalStatus((int)$pending['id'], (int)$pending['customer_id'], 'aprovado', '127.0.0.1', 'teste debug');
    echo "  Resultado updateApprovalStatus: " . ($result ? 'TRUE' : 'FALSE') . "\n";
    
    // Verificar o resultado
    $stmt2 = $db->prepare("SELECT customer_approval_status, customer_approval_at FROM orders WHERE id = ?");
    $stmt2->execute([$pending['id']]);
    $after = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "  Status após update: {$after['customer_approval_status']} | at: {$after['customer_approval_at']}\n";
    
    // Reverter para pendente (para não quebrar o teste)
    $db->prepare("UPDATE orders SET customer_approval_status = 'pendente', customer_approval_at = NULL, customer_approval_ip = NULL, customer_approval_notes = NULL WHERE id = ?")->execute([$pending['id']]);
    echo "  (Revertido para pendente)\n";
} else {
    echo "  Nenhum pedido pendente encontrado.\n";
}

echo "\nDiagnóstico concluído.\n";
