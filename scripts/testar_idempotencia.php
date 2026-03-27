<?php
/**
 * Testar idempotência do SQL do servidor
 */
$db = new PDO(
    'mysql:host=localhost;port=3306;dbname=akti_teste;charset=utf8mb4',
    'akti_sis_usr',
    'kP9!vR2@mX6#zL5$',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFile = __DIR__ . '/../sql/update_202603261830_fix_definitivo_servidor.sql';
$sql = file_get_contents($sqlFile);

// Remover comentários de linha
$sql = preg_replace('/--[^\n]*/', '', $sql);

// Separar statements (cuidado com PREPARE/EXECUTE que usam ;)
// Vamos usar um approach mais robusto
$statements = [];
$current = '';
$lines = explode("\n", $sql);
$inPrepare = false;

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (empty($trimmed)) continue;
    
    $current .= $line . "\n";
    
    // Detectar fim de statement
    if (preg_match('/;\s*$/', $trimmed)) {
        $stmt = trim($current);
        if (!empty($stmt) && $stmt !== ';') {
            $statements[] = $stmt;
        }
        $current = '';
    }
}

if (!empty(trim($current))) {
    $statements[] = trim($current);
}

$total = count($statements);
$ok = 0;
$skip = 0;
$erros = [];

echo "Testando idempotencia: $total statements...\n\n";

foreach ($statements as $i => $stmt) {
    $num = $i + 1;
    // Primeira linha significativa
    $firstLine = '';
    foreach (explode("\n", $stmt) as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $firstLine = substr($line, 0, 90);
            break;
        }
    }
    
    try {
        $db->exec($stmt);
        echo "[OK  ] $num: $firstLine\n";
        $ok++;
    } catch (PDOException $e) {
        $errMsg = $e->getMessage();
        if (strpos($errMsg, 'Duplicate') !== false || 
            strpos($errMsg, 'already exists') !== false) {
            echo "[SKIP] $num: $firstLine (já existe)\n";
            $skip++;
        } else {
            echo "[ERRO] $num: $firstLine\n";
            echo "       => $errMsg\n";
            $erros[] = "Statement $num: $errMsg\n       SQL: $firstLine";
        }
    }
}

echo "\n=================================\n";
echo "Total: $total | OK: $ok | Skip: $skip | Erros: " . count($erros) . "\n";
if (!empty($erros)) {
    echo "\nERROS ENCONTRADOS:\n";
    foreach ($erros as $e) echo "  - $e\n";
} else {
    echo "SUCESSO! Arquivo é totalmente idempotente.\n";
}
echo "=================================\n";
