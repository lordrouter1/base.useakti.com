<?php
$file = __DIR__ . '/../app/controllers/NfeDocumentController.php';
$lines = file($file);
for ($i = 730; $i <= 740; $i++) {
    if (isset($lines[$i])) {
        echo "Line " . ($i + 1) . " hex: " . bin2hex($lines[$i]) . "\n";
        echo "Line " . ($i + 1) . " txt: " . $lines[$i] . "\n";
    }
}
