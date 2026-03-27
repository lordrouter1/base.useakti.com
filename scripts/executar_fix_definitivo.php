<?php
/**
 * Executor do SQL corretivo definitivo
 */
$db = new PDO(
    'mysql:host=localhost;port=3306;dbname=akti_teste;charset=utf8mb4',
    'akti_sis_usr',
    'kP9!vR2@mX6#zL5$',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFile = __DIR__ . '/../sql/update_202603261830_fix_definitivo.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Erro ao ler arquivo SQL\n");
}

// Remover comentários de linha (-- ...)
$sql = preg_replace('/--[^\n]*/', '', $sql);

// Separar por ponto-e-vírgula
$statements = array_filter(array_map('trim', explode(';', $sql)));

$total = count($statements);
$ok = 0;
$erros = [];

echo "Executando $total statements...\n\n";

foreach ($statements as $i => $stmt) {
    if (empty($stmt)) continue;
    
    $num = $i + 1;
    // Pegar primeira linha não-vazia para identificar
    $firstLine = '';
    foreach (explode("\n", $stmt) as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $firstLine = substr($line, 0, 80);
            break;
        }
    }
    
    try {
        $db->exec($stmt);
        echo "[OK  ] $num/$total: $firstLine\n";
        $ok++;
    } catch (PDOException $e) {
        $errMsg = $e->getMessage();
        // Ignorar erros de "já existe" (índice duplicado etc.)
        if (strpos($errMsg, 'Duplicate') !== false || 
            strpos($errMsg, 'already exists') !== false) {
            echo "[SKIP] $num/$total: $firstLine (já existe)\n";
            $ok++;
        } else {
            echo "[ERRO] $num/$total: $firstLine\n";
            echo "       => $errMsg\n";
            $erros[] = "Statement $num: $errMsg";
        }
    }
}

echo "\n=================================\n";
echo "Resultado: $ok OK de $total statements\n";
if (!empty($erros)) {
    echo count($erros) . " ERROS:\n";
    foreach ($erros as $e) echo "  - $e\n";
} else {
    echo "NENHUM ERRO! Todas as statements executadas com sucesso.\n";
}
echo "=================================\n";
