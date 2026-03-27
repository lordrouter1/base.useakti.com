<?php
/**
 * Script para executar a migration da Fase 4.
 * sql/update_202603281000_fase4_seguranca_relatorios.sql
 */
require_once __DIR__ . '/../app/bootstrap/autoload.php';
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();

$sqlFile = __DIR__ . '/../sql/update_202603281000_fase4_seguranca_relatorios.sql';

if (!file_exists($sqlFile)) {
    echo "ERRO: Arquivo não encontrado: {$sqlFile}\n";
    exit(1);
}

$content = file_get_contents($sqlFile);

// Remover comentários de linha
$content = preg_replace('/^\s*--.*$/m', '', $content);

// Separar por ;
$statements = array_filter(array_map('trim', explode(';', $content)));

echo "=== Executando Migration Fase 4 ===\n";
echo "Arquivo: {$sqlFile}\n";
echo "Statements: " . count($statements) . "\n\n";

$errors = 0;
$success = 0;

foreach ($statements as $idx => $stmt) {
    if (empty($stmt)) continue;

    echo "  [" . ($idx + 1) . "] " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . "...\n";
    try {
        $db->exec($stmt);
        echo "      ✅ OK\n";
        $success++;
    } catch (\PDOException $e) {
        // Ignorar erros de "já existe"
        $msg = $e->getMessage();
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
            echo "      ⚠️ Já existe (ignorado)\n";
            $success++;
        } else {
            echo "      ❌ ERRO: {$msg}\n";
            $errors++;
        }
    }
}

echo "\n=== Resultado ===\n";
echo "Sucesso: {$success} | Erros: {$errors}\n";

// Verificar
echo "\n=== Verificação ===\n";

// Tabela rate_limit
try {
    $db->query("SELECT 1 FROM rate_limit LIMIT 0");
    echo "✅ Tabela rate_limit criada\n";
} catch (\Throwable $e) {
    echo "❌ Tabela rate_limit NÃO encontrada\n";
}

// Índice correction_history
try {
    $stmt = $db->query("SHOW INDEX FROM nfe_correction_history WHERE Key_name = 'idx_correction_history_created'");
    echo $stmt->rowCount() > 0 ? "✅ Índice idx_correction_history_created criado\n" : "⚠️ Índice não encontrado (pode já existir com outro nome)\n";
} catch (\Throwable $e) {
    echo "⚠️ Não foi possível verificar índice: {$e->getMessage()}\n";
}

// Índice audit
try {
    $stmt = $db->query("SHOW INDEX FROM nfe_audit_log WHERE Key_name = 'idx_audit_entity_type'");
    echo $stmt->rowCount() > 0 ? "✅ Índice idx_audit_entity_type criado\n" : "⚠️ Índice não encontrado\n";
} catch (\Throwable $e) {
    echo "⚠️ Não foi possível verificar índice: {$e->getMessage()}\n";
}
