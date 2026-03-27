<?php
/**
 * Diagnóstico detalhado: problemas específicos nos SQLs NF-e
 */
require_once __DIR__ . '/../app/config/database.php';
$db = (new Database())->getConnection();

// 1. Verificar tipo de id em nfe_documents
echo "=== TIPO DE ID EM nfe_documents ===" . PHP_EOL;
$cols = $db->query("SHOW COLUMNS FROM nfe_documents WHERE Field = 'id'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  " . $c['Field'] . " => " . $c['Type'] . " | Extra: " . $c['Extra'] . PHP_EOL;
}

// 2. Verificar estrutura de company_settings
echo PHP_EOL . "=== COLUNAS company_settings ===" . PHP_EOL;
try {
    $cols = $db->query('SHOW COLUMNS FROM company_settings')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . str_pad($c['Field'], 25) . " " . $c['Type'] . " | Null=" . $c['Null'] . " | Default=" . ($c['Default'] ?? 'NULL') . PHP_EOL;
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// 3. Verificar se há registros em company_settings
echo PHP_EOL . "=== TODOS OS REGISTROS company_settings ===" . PHP_EOL;
try {
    $rows = $db->query("SELECT * FROM company_settings LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  Tabela vazia." . PHP_EOL;
    } else {
        // Mostrar as colunas existentes
        $colNames = array_keys($rows[0]);
        echo "  Colunas: " . implode(', ', $colNames) . PHP_EOL;
        echo "  Total mostrados: " . count($rows) . PHP_EOL;
        foreach ($rows as $r) {
            $key = $r['setting_key'] ?? $r['key'] ?? ($r[array_keys($r)[0]] ?? '?');
            echo "  -> " . $key . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "  ERRO: " . $e->getMessage() . PHP_EOL;
}

// 4. Testar criar nfe_document_items manualmente para ver o erro
echo PHP_EOL . "=== TENTANDO CRIAR nfe_document_items ===" . PHP_EOL;
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `test_fk_check` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nfe_document_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id`),
        CONSTRAINT `fk_test_nfe_doc`
            FOREIGN KEY (`nfe_document_id`) REFERENCES `nfe_documents`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  SUCESSO — FK com INT UNSIGNED funciona" . PHP_EOL;
    $db->exec("DROP TABLE IF EXISTS `test_fk_check`");
} catch (Exception $e) {
    echo "  ERRO FK (UNSIGNED vs SIGNED): " . $e->getMessage() . PHP_EOL;
    // Tentar com INT (signed)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `test_fk_check2` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `nfe_document_id` INT NOT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_test_nfe_doc2`
                FOREIGN KEY (`nfe_document_id`) REFERENCES `nfe_documents`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "  SUCESSO — FK com INT (signed) funciona" . PHP_EOL;
        $db->exec("DROP TABLE IF EXISTS `test_fk_check2`");
    } catch (Exception $e2) {
        echo "  ERRO FK (SIGNED): " . $e2->getMessage() . PHP_EOL;
    }
}

// 5. Verificar status ENUM vs VARCHAR
echo PHP_EOL . "=== TIPO DO CAMPO status EM nfe_documents ===" . PHP_EOL;
$cols = $db->query("SHOW COLUMNS FROM nfe_documents WHERE Field = 'status'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  " . $c['Field'] . " => " . $c['Type'] . PHP_EOL;
}

// 6. Checar se inutilizada está no ENUM
echo PHP_EOL . "=== VERIFICACAO: status 'inutilizada' no ENUM ===" . PHP_EOL;
$type = $cols[0]['Type'] ?? '';
if (strpos($type, 'inutilizada') !== false) {
    echo "  OK: 'inutilizada' esta no ENUM" . PHP_EOL;
} else {
    echo "  FALTANDO: 'inutilizada' NAO esta no ENUM: $type" . PHP_EOL;
}

echo PHP_EOL . "=== FIM ===" . PHP_EOL;
