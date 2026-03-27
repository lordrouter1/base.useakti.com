<?php
/**
 * Executa a migration SQL da Fase 3 do módulo NF-e.
 */
require_once __DIR__ . '/../app/bootstrap/autoload.php';
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();

$sqlFile = __DIR__ . '/../sql/update_202603271100_fase3_nfe.sql';
if (!file_exists($sqlFile)) {
    die("❌ Arquivo SQL não encontrado: {$sqlFile}\n");
}

$sql = file_get_contents($sqlFile);

// Remover comentários de linha (-- ...) e bloco (/* */)
$sql = preg_replace('/--[^\n]*/', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Separar statements por ';'
$statements = array_filter(array_map('trim', explode(';', $sql)), function($s) {
    $s = trim($s);
    return !empty($s);
});

echo "=== Executando Migration Fase 3 ===" . PHP_EOL;
echo "Total de statements: " . count($statements) . PHP_EOL . PHP_EOL;

$success = 0;
$errors = 0;

foreach ($statements as $i => $stmt) {
    $stmtClean = preg_replace('/\s+/', ' ', substr($stmt, 0, 80));
    echo "[$i] {$stmtClean}..." . PHP_EOL;
    
    try {
        $db->exec($stmt);
        echo "    ✅ OK" . PHP_EOL;
        $success++;
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        // Se for "Duplicate column name" ou "Duplicate key name", é idempotente — OK
        if (strpos($msg, 'Duplicate column name') !== false || strpos($msg, 'Duplicate key name') !== false) {
            echo "    ⚠️ Já existe (idempotente): " . $msg . PHP_EOL;
            $success++;
        } else {
            echo "    ❌ ERRO: " . $msg . PHP_EOL;
            $errors++;
        }
    }
}

echo PHP_EOL;
echo "Resultado: {$success} OK, {$errors} erros" . PHP_EOL;
echo $errors === 0 ? "✅ Migration Fase 3 aplicada com sucesso!" : "❌ Houve erros — verifique acima.";
echo PHP_EOL;
