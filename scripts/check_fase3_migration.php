<?php
/**
 * Script para verificar se as colunas da migration Fase 3 foram aplicadas.
 */
require_once __DIR__ . '/../app/bootstrap/autoload.php';
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();

$checks = [
    ['table' => 'nfe_documents', 'column' => 'fin_nfe'],
    ['table' => 'nfe_documents', 'column' => 'chave_ref'],
    ['table' => 'nfe_documents', 'column' => 'valor_fcp_uf_dest'],
    ['table' => 'nfe_documents', 'column' => 'valor_icms_uf_dest'],
    ['table' => 'nfe_documents', 'column' => 'valor_icms_uf_remet'],
    ['table' => 'nfe_document_items', 'column' => 'difal_vbc'],
    ['table' => 'nfe_document_items', 'column' => 'difal_fcp'],
    ['table' => 'nfe_document_items', 'column' => 'difal_icms_dest'],
    ['table' => 'nfe_document_items', 'column' => 'difal_icms_remet'],
    ['table' => 'nfe_queue', 'column' => 'batch_id'],
];

echo "=== Verificação Migration Fase 3 ===" . PHP_EOL;
$allOk = true;
foreach ($checks as $check) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `{$check['table']}` LIKE :col");
    $stmt->execute([':col' => $check['column']]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    $status = $exists ? '✅' : '❌';
    echo "{$status} {$check['table']}.{$check['column']}" . PHP_EOL;
    if (!$exists) $allOk = false;
}

// Verificar índice batch_status
$stmt = $db->query("SHOW INDEX FROM nfe_queue WHERE Key_name = 'idx_nfe_queue_batch_status'");
$idxExists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
echo ($idxExists ? '✅' : '❌') . " nfe_queue INDEX idx_nfe_queue_batch_status" . PHP_EOL;
if (!$idxExists) $allOk = false;

echo PHP_EOL;
echo $allOk ? "✅ Todas as colunas da Fase 3 estão presentes!" : "❌ Algumas colunas estão faltando. Execute a migration sql/update_202603271100_fase3_nfe.sql";
echo PHP_EOL;
