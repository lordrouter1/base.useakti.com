<?php
/**
 * Diagnóstico completo do banco de dados — NF-e + Prontos pendentes
 * Verifica TODAS as tabelas, colunas e configurações que deveriam existir
 */

$db = new PDO(
    'mysql:host=localhost;port=3306;dbname=akti_teste;charset=utf8mb4',
    'akti_sis_usr',
    'kP9!vR2@mX6#zL5$',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$dbName = 'akti_teste';

echo "=== DIAGNOSTICO COMPLETO DO BANCO ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "Banco: {$dbName}\n\n";

// ====================
// 1. TABELAS ESPERADAS
// ====================
echo "--- 1. VERIFICACAO DE TABELAS ---\n";
$expectedTables = [
    // NF-e
    'nfe_credentials',
    'nfe_documents',
    'nfe_logs',
    'nfe_document_items',
    'nfe_correction_history',
    // Fase 4
    'tax_ibptax',
    'notifications',
    // Portal
    'customer_portal_access',
    'customer_portal_messages',
    'customer_portal_config',
    'customer_portal_documents',
    'customer_portal_sessions',
    // Comissões
    'comissoes_registradas',
    'comissao_config',
    // Financeiro
    'financial_audit_log',
    'order_installments',
    // Geral
    'company_settings',
    'orders',
    'products',
    'users',
];

foreach ($expectedTables as $table) {
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $stmt->execute([$dbName, $table]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
    echo ($exists ? "[OK]  " : "[FALTA] ") . $table . "\n";
}

// ====================
// 2. COLUNAS NF-e
// ====================
echo "\n--- 2. COLUNAS EM nfe_credentials ---\n";
$expectedCredsColumns = [
    'serie_nfce', 'proximo_numero_nfce', 'tp_emis',
    'contingencia_justificativa', 'contingencia_ativada_em',
    'cnpj', 'ie', 'razao_social', 'certificate_path', 'certificate_password',
    'certificate_expiry', 'environment', 'serie_nfe', 'proximo_numero',
    'csc_id', 'csc_token',
];
checkColumns($db, $dbName, 'nfe_credentials', $expectedCredsColumns);

echo "\n--- 3. COLUNAS EM nfe_documents ---\n";
$expectedDocsColumns = [
    'order_id', 'modelo', 'numero', 'serie', 'chave', 'protocolo', 'recibo',
    'status', 'status_sefaz', 'motivo_sefaz', 'natureza_op',
    'valor_total', 'valor_produtos', 'valor_desconto', 'valor_frete',
    'valor_icms', 'valor_pis', 'valor_cofins', 'valor_ipi',
    'valor_tributos_aprox',
    'dest_cnpj_cpf', 'dest_nome', 'dest_ie', 'dest_uf',
    'tp_emis', 'contingencia_justificativa',
    'xml_envio', 'xml_autorizado', 'xml_cancelamento', 'xml_correcao',
    'xml_path', 'danfe_path', 'cancel_xml_path',
    'cancel_protocolo', 'cancel_motivo', 'cancel_date',
    'correcao_texto', 'correcao_seq', 'correcao_date',
    'emitted_at', 'created_at', 'updated_at',
];
checkColumns($db, $dbName, 'nfe_documents', $expectedDocsColumns);

echo "\n--- 4. ENUM status de nfe_documents ---\n";
$stmt = $db->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = 'nfe_documents' AND column_name = 'status'");
$stmt->execute([$dbName]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "   Tipo: " . $row['COLUMN_TYPE'] . "\n";
    // Verificar valores esperados
    $required = ['rascunho','processando','autorizada','rejeitada','cancelada','denegada','corrigida','inutilizada'];
    foreach ($required as $val) {
        if (strpos($row['COLUMN_TYPE'], "'$val'") !== false) {
            echo "   [OK]    ENUM '$val'\n";
        } else {
            echo "   [FALTA] ENUM '$val'\n";
        }
    }
} else {
    echo "   [ERRO] Coluna status nao encontrada!\n";
}

echo "\n--- 5. TIPO DA COLUNA nfe_documents.id (FK compatibility) ---\n";
$stmt = $db->prepare("SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM information_schema.columns WHERE table_schema = ? AND table_name = 'nfe_documents' AND column_name = 'id'");
$stmt->execute([$dbName]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "   nfe_documents.id => " . $row['COLUMN_TYPE'] . " (Key: " . $row['COLUMN_KEY'] . ", Extra: " . $row['EXTRA'] . ")\n";
    $isUnsigned = (strpos(strtolower($row['COLUMN_TYPE']), 'unsigned') !== false);
    echo "   É UNSIGNED? " . ($isUnsigned ? "SIM" : "NAO (signed)") . "\n";
} else {
    echo "   [ERRO] Tabela nfe_documents nao existe!\n";
}

// Se nfe_document_items existe, verificar tipo da FK
$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'nfe_document_items'");
$stmt->execute([$dbName]);
if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
    $stmt = $db->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = 'nfe_document_items' AND column_name = 'nfe_document_id'");
    $stmt->execute([$dbName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "   nfe_document_items.nfe_document_id => " . $row['COLUMN_TYPE'] . "\n";
    }
}

// Se nfe_correction_history existe, verificar tipo da FK
$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'nfe_correction_history'");
$stmt->execute([$dbName]);
if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
    $stmt = $db->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = 'nfe_correction_history' AND column_name = 'nfe_document_id'");
    $stmt->execute([$dbName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "   nfe_correction_history.nfe_document_id => " . $row['COLUMN_TYPE'] . "\n";
    }
}

// ====================
// 6. COLUNAS EM orders
// ====================
echo "\n--- 6. COLUNAS ESPERADAS EM orders ---\n";
$expectedOrdersCols = [
    'seller_id', 'shipping_cost', 'sale_type', 'tracking_code', 'tracking_url',
    'customer_approval_status', 'customer_approval_at', 'customer_approval_ip',
    'customer_approval_notes', 'portal_origin',
    'nfe_id', 'nfe_status', 'nf_number', 'nf_series', 'nf_status', 'nf_access_key', 'nf_notes',
];
checkColumns($db, $dbName, 'orders', $expectedOrdersCols);

// ====================
// 7. COLUNAS EM products
// ====================
echo "\n--- 7. CAMPOS FISCAIS EM products ---\n";
$expectedProductCols = [
    'fiscal_ncm', 'fiscal_cest', 'fiscal_cfop', 'fiscal_cfop_interestadual',
    'fiscal_cst_icms', 'fiscal_csosn', 'fiscal_cst_pis', 'fiscal_cst_cofins',
    'fiscal_cst_ipi', 'fiscal_origem', 'fiscal_unidade', 'fiscal_ean',
    'fiscal_aliq_icms', 'fiscal_icms_reducao_bc', 'fiscal_aliq_ipi',
    'fiscal_aliq_pis', 'fiscal_aliq_cofins', 'fiscal_beneficio', 'fiscal_info_adicional',
];
checkColumns($db, $dbName, 'products', $expectedProductCols);

// ====================
// 8. COLUNAS EM order_installments
// ====================
echo "\n--- 8. COLUNAS NF-e EM order_installments ---\n";
$expectedInstCols = ['nfe_faturada', 'nfe_document_id', 'is_confirmed'];
checkColumns($db, $dbName, 'order_installments', $expectedInstCols);

// ====================
// 9. COLUNAS company_settings
// ====================
echo "\n--- 9. ESTRUTURA DE company_settings ---\n";
$stmt = $db->prepare("SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = 'company_settings' ORDER BY ORDINAL_POSITION");
$stmt->execute([$dbName]);
$csColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($csColumns)) {
    foreach ($csColumns as $col) {
        echo "   " . $col['COLUMN_NAME'] . " => " . $col['COLUMN_TYPE'] . "\n";
    }
} else {
    echo "   [ERRO] Tabela company_settings nao encontrada!\n";
}

// ====================
// 10. CONFIGS NF-e em company_settings
// ====================
echo "\n--- 10. CONFIGURACOES NF-e EM company_settings ---\n";
$expectedSettings = [
    'nfe_auto_email', 'nfe_ibptax_enabled', 'nfe_pipeline_stage_emit',
    'nfe_stock_auto_debit', 'nfe_financial_auto_faturar',
];
try {
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM company_settings WHERE setting_key LIKE 'nfe_%'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($expectedSettings as $key) {
        if (isset($settings[$key])) {
            echo "   [OK]    $key = '" . $settings[$key] . "'\n";
        } else {
            echo "   [FALTA] $key\n";
        }
    }
} catch (Exception $e) {
    echo "   [ERRO] " . $e->getMessage() . "\n";
}

// ====================
// 11. COLUNAS customer_portal_access
// ====================
echo "\n--- 11. COLUNAS customer_portal_access ---\n";
$expectedPortalCols = [
    'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at', 'avatar',
    'reset_token', 'reset_token_expires_at',
    'email', 'password_hash', 'magic_token', 'is_active',
];
checkColumns($db, $dbName, 'customer_portal_access', $expectedPortalCols);

// ====================
// 12. COLUNAS customer_portal_sessions
// ====================
echo "\n--- 12. COLUNAS customer_portal_sessions ---\n";
$expectedSessionCols = ['expires_at', 'access_id', 'customer_id', 'session_id'];
checkColumns($db, $dbName, 'customer_portal_sessions', $expectedSessionCols);

// ====================
// 13. INDICES nfe_documents
// ====================
echo "\n--- 13. INDICES EM nfe_documents ---\n";
try {
    $stmt = $db->query("SHOW INDEX FROM nfe_documents");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    $expectedIndexes = [
        'idx_nfe_order_id', 'idx_nfe_chave', 'idx_nfe_status',
        'idx_nfe_numero_serie', 'idx_nfe_modelo', 'idx_nfe_emitted_at',
        'idx_nfe_doc_status_emitted', 'idx_nfe_doc_status_created', 'idx_nfe_doc_order',
    ];
    foreach ($expectedIndexes as $idx) {
        echo (in_array($idx, $indexNames) ? "   [OK]    " : "   [FALTA] ") . $idx . "\n";
    }
} catch (Exception $e) {
    echo "   [ERRO] " . $e->getMessage() . "\n";
}

// ====================
// 14. FOREIGN KEYS nas tabelas NF-e
// ====================
echo "\n--- 14. FOREIGN KEYS ---\n";
$expectedFKs = [
    ['nfe_logs', 'fk_nfe_logs_document'],
    ['nfe_document_items', 'fk_nfe_items_document'],
    ['nfe_correction_history', 'fk_nfe_cce_document'],
];
foreach ($expectedFKs as [$table, $fkName]) {
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS 
        WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'");
    $stmt->execute([$dbName, $table, $fkName]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
    echo ($exists ? "   [OK]    " : "   [FALTA] ") . "{$table}.{$fkName}\n";
}

// ====================
// 15. PORTAL CONFIGS
// ====================
echo "\n--- 15. PORTAL CONFIGS ---\n";
$expectedPortalConfigs = [
    'allow_order_creation', 'allow_document_upload', 'allow_messages',
    'show_financial', 'show_tracking',
    'portal_enabled', 'require_password', 'allow_self_register',
    'allow_order_approval', 'magic_link_expiry_hours', 'session_timeout_minutes',
    'enable_2fa', 'enable_avatar_upload', 'rate_limit_portal_max', 'rate_limit_portal_window',
];
try {
    $stmt = $db->query("SELECT config_key FROM customer_portal_config");
    $existingConfigs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($expectedPortalConfigs as $key) {
        echo (in_array($key, $existingConfigs) ? "   [OK]    " : "   [FALTA] ") . $key . "\n";
    }
} catch (Exception $e) {
    echo "   [ERRO] " . $e->getMessage() . "\n";
}

// ====================
// 16. Comissão config
// ====================
echo "\n--- 16. COMISSAO CONFIG ---\n";
try {
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'comissao_config'");
    $stmt->execute([$dbName]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        $stmt2 = $db->query("SELECT config_key FROM comissao_config WHERE config_key = 'criterio_liberacao_comissao'");
        $r = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo ($r ? "   [OK]    " : "   [FALTA] ") . "criterio_liberacao_comissao\n";
    } else {
        echo "   [INFO]  Tabela comissao_config nao existe\n";
    }
} catch (Exception $e) {
    echo "   [ERRO] " . $e->getMessage() . "\n";
}

// ====================
// 17. financial_audit_log.reason
// ====================
echo "\n--- 17. FINANCIAL AUDIT LOG ---\n";
try {
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'financial_audit_log'");
    $stmt->execute([$dbName]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        checkColumns($db, $dbName, 'financial_audit_log', ['reason']);
    } else {
        echo "   [INFO]  Tabela financial_audit_log nao existe\n";
    }
} catch (Exception $e) {
    echo "   [ERRO] " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO DIAGNOSTICO ===\n";

// ===== FUNCAO AUXILIAR =====
function checkColumns($db, $dbName, $table, $expectedColumns) {
    // Verificar se a tabela existe
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $stmt->execute([$dbName, $table]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] == 0) {
        echo "   [ERRO] Tabela '$table' NAO EXISTE!\n";
        return;
    }
    
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = ? AND table_name = ?");
    $stmt->execute([$dbName, $table]);
    $existingCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($expectedColumns as $col) {
        echo (in_array($col, $existingCols) ? "   [OK]    " : "   [FALTA] ") . "$table.$col\n";
    }
}
