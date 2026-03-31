<?php
/**
 * Replace error_log with Log:: in service files.
 * Run from project root: php _fix_services_log.php
 */

$dir = 'app/services';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $c = file_get_contents($file);
    $changed = false;
    
    // Skip if no error_log
    if (strpos($c, 'error_log(') === false) continue;
    
    // Add use Akti\Core\Log if not present
    if (strpos($c, 'use Akti\\Core\\Log;') === false) {
        if (preg_match('/^(namespace Akti\\\\Services;)/m', $c)) {
            $c = preg_replace('/^(namespace Akti\\\\Services;)/m', "$1\n\nuse Akti\\Core\\Log;", $c, 1);
            $changed = true;
        }
    }
    
    // Replace: error_log('[ClassName] msg: ' . $e->getMessage());
    $c = preg_replace_callback(
        "/error_log\('\[(\w+)\]\s*(.+?):\s*'\s*\.\s*\\\$([\w>()-]+)\);/s",
        function($m) {
            $class = trim($m[1]);
            $msg = trim($m[2]);
            $var = trim($m[3]);
            return "Log::error('{$class}: {$msg}', ['exception' => \${$var}]);";
        },
        $c,
        -1,
        $count1
    );
    
    // Replace: error_log("[ClassName] Msg: " . $expr);
    $c = preg_replace_callback(
        '/error_log\("\[(\w+)\]\s*(.+?):\s*"\s*\.\s*(.+?)\);/s',
        function($m) {
            $class = trim($m[1]);
            $msg = trim($m[2]);
            $var = trim($m[3]);
            return "Log::error('{$class}: {$msg}', ['detail' => {$var}]);";
        },
        $c,
        -1,
        $count2
    );
    
    if ($count1 > 0 || $count2 > 0) $changed = true;
    
    if ($changed) {
        file_put_contents($file, $c);
        echo "OK: $file (replaced " . ($count1 + $count2) . " calls)\n";
    }
}

echo "Done.\n";
