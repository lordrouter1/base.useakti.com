<?php
/**
 * One-time script: replace error_log -> Log in model files
 */
$files = [
    'app/models/IpGuard.php',
    'app/models/NfeCredential.php',
    'app/models/PreparationStep.php',
    'app/models/Stock.php',
    'app/models/OrderPreparation.php',
    'app/models/OrderItemLog.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "SKIP: $file not found\n";
        continue;
    }
    $c = file_get_contents($file);
    
    // Add use Akti\Core\Log if not present
    if (strpos($c, 'use Akti\\Core\\Log;') === false) {
        // Add after namespace
        $c = preg_replace(
            '/(namespace Akti\\\\Models;)/',
            "$1\n\nuse Akti\\Core\\Log;",
            $c,
            1
        );
    }
    
    // Replace error_log patterns
    // Pattern: error_log('[ClassName] message: ' . $e->getMessage());
    $c = preg_replace(
        "/error_log\('\[(\w+)\] (.+?)(?:error|Erro|AVISO DE SEGURAN.A): '\s*\n?\s*\.\s*(.+?)\);/s",
        "Log::error('\\1: \\2', ['detail' => \\3);",
        $c
    );
    
    // Simple patterns: error_log('[ClassName] message.');
    $c = preg_replace(
        "/error_log\('\[(\w+)\] (.+?)'\);/",
        "Log::warning('\\1: \\2');",
        $c
    );
    
    // Pattern: error_log(sprintf(...)
    $c = preg_replace(
        "/error_log\(sprintf\(/",
        "Log::info(sprintf(",
        $c
    );
    
    file_put_contents($file, $c);
    echo "OK: $file\n";
}
echo "Done.\n";
