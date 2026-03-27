<?php
/**
 * Diagnóstico detalhado - partes faltantes
 */
$db = new PDO(
    'mysql:host=localhost;port=3306;dbname=akti_teste;charset=utf8mb4',
    'akti_sis_usr',
    'kP9!vR2@mX6#zL5$',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "--- nfe_logs: estrutura completa ---\n";
$stmt = $db->query("SHOW CREATE TABLE nfe_logs");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'] . "\n\n";

echo "--- customer_portal_sessions: estrutura completa ---\n";
$stmt = $db->query("SHOW CREATE TABLE customer_portal_sessions");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'] . "\n\n";

echo "--- nfe_documents: tipo id completo ---\n";
$stmt = $db->query("SHOW CREATE TABLE nfe_documents");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
// Só as primeiras linhas
$lines = explode("\n", $row['Create Table']);
foreach ($lines as $i => $line) {
    echo $line . "\n";
    if ($i > 5) break;
}
echo "...\n\n";

echo "--- comissoes_registradas: ENUM status ---\n";
$stmt = $db->query("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = 'akti_teste' AND table_name = 'comissoes_registradas' AND column_name = 'status'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   " . ($row ? $row['COLUMN_TYPE'] : 'coluna nao encontrada') . "\n\n";

echo "--- Verificar se order_installments tem is_confirmed ---\n";
$stmt = $db->query("SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.columns WHERE table_schema = 'akti_teste' AND table_name = 'order_installments' AND column_name IN ('is_confirmed','nfe_faturada','nfe_document_id') ORDER BY ORDINAL_POSITION");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "   " . $r['COLUMN_NAME'] . " => " . $r['COLUMN_TYPE'] . "\n";

echo "\n--- prontos: update_202506151200_audit_reason_and_report.sql ---\n";
echo "   (verificar se financial_audit_log existe no script original)\n";

echo "\n--- company_settings: todas as keys existentes ---\n";
$stmt = $db->query("SELECT setting_key, setting_value FROM company_settings ORDER BY setting_key");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "   " . $r['setting_key'] . " = " . $r['setting_value'] . "\n";
