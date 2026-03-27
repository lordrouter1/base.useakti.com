<?php
/**
 * Script: Verificar estado do banco de dados para módulo NF-e
 * Verifica tabelas, colunas e identifica problemas nos SQLs de migração.
 */
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->getConnection();

echo "=== VERIFICANDO TABELAS NF-e ===" . PHP_EOL;

$tables = [
    'nfe_credentials',
    'nfe_documents',
    'nfe_logs',
    'nfe_document_items',
    'nfe_correction_history',
    'tax_ibptax',
    'notifications',
    'company_settings',
    'order_installments',
    'orders',
    'products',
];

foreach ($tables as $t) {
    $exists = $db->query("SHOW TABLES LIKE '$t'")->rowCount();
    echo "  $t: " . ($exists ? 'EXISTE' : 'NAO EXISTE') . PHP_EOL;
}

// Verificar colunas de nfe_documents
echo PHP_EOL . "=== COLUNAS nfe_documents ===" . PHP_EOL;
try {
    $cols = $db->query('SHOW COLUMNS FROM nfe_documents')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . str_pad($c['Field'], 30) . " " . $c['Type'] . ($c['Null'] === 'YES' ? ' NULL' : ' NOT NULL') . PHP_EOL;
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar colunas de nfe_credentials
echo PHP_EOL . "=== COLUNAS nfe_credentials ===" . PHP_EOL;
try {
    $cols = $db->query('SHOW COLUMNS FROM nfe_credentials')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . str_pad($c['Field'], 30) . " " . $c['Type'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar colunas de order_installments
echo PHP_EOL . "=== COLUNAS order_installments ===" . PHP_EOL;
try {
    $cols = $db->query('SHOW COLUMNS FROM order_installments')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . str_pad($c['Field'], 30) . " " . $c['Type'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar colunas NF-e em orders
echo PHP_EOL . "=== COLUNAS NF-e RELATED EM orders ===" . PHP_EOL;
try {
    $cols = $db->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if (stripos($c['Field'], 'nfe') !== false || stripos($c['Field'], 'nf_') !== false) {
            echo "  " . str_pad($c['Field'], 30) . " " . $c['Type'] . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar colunas fiscais em products
echo PHP_EOL . "=== COLUNAS FISCAIS EM products ===" . PHP_EOL;
try {
    $cols = $db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC);
    $fiscalCols = ['ncm','cest','cfop','icms','pis','cofins','ipi','origem','fiscal','ean','unidade'];
    foreach ($cols as $c) {
        foreach ($fiscalCols as $fc) {
            if (stripos($c['Field'], $fc) !== false) {
                echo "  " . str_pad($c['Field'], 30) . " " . $c['Type'] . PHP_EOL;
                break;
            }
        }
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar indices de nfe_documents
echo PHP_EOL . "=== INDICES nfe_documents ===" . PHP_EOL;
try {
    $idxs = $db->query('SHOW INDEX FROM nfe_documents')->fetchAll(PDO::FETCH_ASSOC);
    $seen = [];
    foreach ($idxs as $i) {
        $name = $i['Key_name'];
        if (!in_array($name, $seen)) {
            echo "  " . str_pad($name, 35) . " col=" . $i['Column_name'] . PHP_EOL;
            $seen[] = $name;
        }
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar company_settings NFe-related
echo PHP_EOL . "=== COMPANY_SETTINGS (nfe) ===" . PHP_EOL;
try {
    $rows = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key LIKE 'nfe_%'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  Nenhuma config NF-e encontrada." . PHP_EOL;
    }
    foreach ($rows as $r) {
        echo "  " . str_pad($r['setting_key'], 30) . " = " . $r['setting_value'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// Verificar MariaDB version
echo PHP_EOL . "=== VERSAO DO BANCO ===" . PHP_EOL;
try {
    $version = $db->query("SELECT VERSION()")->fetchColumn();
    echo "  " . $version . PHP_EOL;
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== FIM ===" . PHP_EOL;
