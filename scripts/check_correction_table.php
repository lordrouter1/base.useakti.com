<?php
require_once __DIR__ . '/../app/bootstrap/autoload.php';
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();

// Check nfe_correction_history columns
try {
    $stmt = $db->query('DESCRIBE nfe_correction_history');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "=== nfe_correction_history ===\n";
    foreach ($cols as $c) {
        echo "{$c['Field']} ({$c['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
