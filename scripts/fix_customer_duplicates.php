<?php
/**
 * Script de limpeza de duplicatas de clientes.
 *
 * Fase 4 — Item 4.10
 *
 * Identifica clientes com o mesmo CPF/CNPJ (document) e aplica estratégia
 * de mesclagem, mantendo o registro mais relevante e soft-deletando os demais.
 *
 * USO:
 *   php scripts/fix_customer_duplicates.php              → Modo dry-run (apenas relatório)
 *   php scripts/fix_customer_duplicates.php --execute     → Executa a limpeza
 *   php scripts/fix_customer_duplicates.php --execute --apply-unique  → Limpa + aplica UNIQUE INDEX
 *
 * SAÍDA:
 *   Gera relatório em storage/logs/duplicates_report_YYYYMMDDHHmmss.txt
 *
 * @package Akti\Scripts
 */

// ═══════════════════════════════════════════════════════════════
// Bootstrap
// ═══════════════════════════════════════════════════════════════

$rootDir = dirname(__DIR__);

// Carregar configuração de banco
$dbConfig = require $rootDir . '/app/config/database.php';

// Verificar se temos config de banco
if (empty($dbConfig) || !is_array($dbConfig)) {
    echo "❌ Erro: Configuração de banco de dados não encontrada.\n";
    exit(1);
}

// Obter tenant (se multi-tenant, usar default)
$host = $dbConfig['host'] ?? 'localhost';
$dbname = $dbConfig['database'] ?? $dbConfig['dbname'] ?? '';
$user = $dbConfig['username'] ?? $dbConfig['user'] ?? 'root';
$pass = $dbConfig['password'] ?? $dbConfig['pass'] ?? '';
$charset = $dbConfig['charset'] ?? 'utf8mb4';

if (!$dbname) {
    echo "❌ Erro: Nome do banco de dados não definido na configuração.\n";
    exit(1);
}

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════
// Parâmetros de execução
// ═══════════════════════════════════════════════════════════════

$args = $argv ?? [];
$dryRun = !in_array('--execute', $args);
$applyUnique = in_array('--apply-unique', $args);

$timestamp = date('YmdHis');
$logDir = $rootDir . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$reportFile = $logDir . '/duplicates_report_' . $timestamp . '.txt';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║    Akti — Script de Limpeza de Duplicatas de Clientes       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "🔍 MODO: DRY-RUN (apenas relatório, nenhuma alteração será feita)\n";
    echo "   Para executar de verdade, adicione: --execute\n\n";
} else {
    echo "⚠️  MODO: EXECUÇÃO — Alterações serão aplicadas no banco!\n\n";
}

// ═══════════════════════════════════════════════════════════════
// Etapa 1: Identificar documentos duplicados
// ═══════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 Etapa 1: Buscando documentos duplicados...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$stmtDuplicates = $pdo->query("
    SELECT document, COUNT(*) as total
    FROM customers
    WHERE document IS NOT NULL
      AND document != ''
      AND deleted_at IS NULL
    GROUP BY document
    HAVING COUNT(*) > 1
    ORDER BY total DESC
");

$duplicates = $stmtDuplicates->fetchAll();
$totalGroups = count($duplicates);

if ($totalGroups === 0) {
    echo "✅ Nenhuma duplicata encontrada! O banco está limpo.\n\n";
    $report = "RELATÓRIO DE DUPLICATAS - " . date('d/m/Y H:i:s') . "\n";
    $report .= "Resultado: Nenhuma duplicata encontrada.\n";
    file_put_contents($reportFile, $report);
    echo "📄 Relatório salvo em: $reportFile\n";

    if ($applyUnique) {
        echo "\n📌 Aplicando UNIQUE INDEX em document...\n";
        try {
            $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX idx_customers_document_unique (document)");
            echo "✅ UNIQUE INDEX aplicado com sucesso!\n";
        } catch (PDOException $e) {
            echo "⚠️  Índice já existe ou erro: " . $e->getMessage() . "\n";
        }
    }

    exit(0);
}

$totalDuplicateRecords = array_sum(array_column($duplicates, 'total'));
echo "🔎 Encontrados: {$totalGroups} documentos duplicados ({$totalDuplicateRecords} registros total)\n\n";

// ═══════════════════════════════════════════════════════════════
// Etapa 2: Analisar cada grupo de duplicatas
// ═══════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 Etapa 2: Analisando registros duplicados...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$report = [];
$report[] = "═══════════════════════════════════════════════════════════════";
$report[] = "RELATÓRIO DE DUPLICATAS — Akti Gestão em Produção";
$report[] = "Data: " . date('d/m/Y H:i:s');
$report[] = "Modo: " . ($dryRun ? 'DRY-RUN' : 'EXECUÇÃO');
$report[] = "Total de grupos duplicados: {$totalGroups}";
$report[] = "Total de registros envolvidos: {$totalDuplicateRecords}";
$report[] = "═══════════════════════════════════════════════════════════════\n";

$actions = [];  // Array de ações a tomar
$merged = 0;
$deleted = 0;

foreach ($duplicates as $index => $dup) {
    $document = $dup['document'];
    $total = $dup['total'];
    $groupNum = $index + 1;

    echo "  ┌─ Grupo {$groupNum}/{$totalGroups}: Documento {$document} ({$total} registros)\n";
    $report[] = "─── Grupo {$groupNum}: Documento {$document} ({$total} registros) ───";

    // Buscar todos os registros com este documento
    $stmtRecords = $pdo->prepare("
        SELECT c.id, c.code, c.name, c.email, c.phone, c.cellphone,
               c.person_type, c.status, c.created_at, c.updated_at,
               (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count,
               (SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE o.customer_id = c.id) as order_value
        FROM customers c
        WHERE c.document = :doc
          AND c.deleted_at IS NULL
        ORDER BY order_count DESC, c.created_at ASC
    ");
    $stmtRecords->execute([':doc' => $document]);
    $records = $stmtRecords->fetchAll();

    // Estratégia de decisão: manter o registro com mais pedidos,
    // desempate por mais dados preenchidos, depois mais antigo
    $scored = [];
    foreach ($records as $rec) {
        $score = 0;
        // Peso principal: quantidade de pedidos
        $score += (int)$rec['order_count'] * 1000;
        // Peso secundário: valor total de pedidos
        $score += (float)$rec['order_value'];
        // Peso terciário: completude do registro
        if (!empty($rec['email'])) $score += 10;
        if (!empty($rec['phone']) || !empty($rec['cellphone'])) $score += 10;
        if ($rec['status'] === 'active') $score += 5;
        // Bonus para mais antigo (menos ID = mais antigo)
        $score += (1 / max((int)$rec['id'], 1));

        $scored[] = array_merge($rec, ['score' => $score]);
    }

    // Ordenar por score decrescente — o primeiro é o que mantemos
    usort($scored, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $keep = $scored[0];
    $toDelete = array_slice($scored, 1);

    echo "  │  ✅ MANTER: ID={$keep['id']} | {$keep['name']} | Code={$keep['code']} | Pedidos={$keep['order_count']} | Status={$keep['status']}\n";
    $report[] = "  MANTER: ID={$keep['id']} | {$keep['name']} | Code={$keep['code']} | Pedidos={$keep['order_count']} | Status={$keep['status']}";

    foreach ($toDelete as $del) {
        echo "  │  ❌ DELETAR: ID={$del['id']} | {$del['name']} | Code={$del['code']} | Pedidos={$del['order_count']} | Status={$del['status']}\n";
        $report[] = "  DELETAR: ID={$del['id']} | {$del['name']} | Code={$del['code']} | Pedidos={$del['order_count']} | Status={$del['status']}";

        // Mesclagem de dados: campos vazios no registro principal são preenchidos pelo duplicado
        $fieldsToMerge = [
            'email', 'phone', 'cellphone', 'fantasy_name', 'rg_ie', 'im',
            'website', 'instagram', 'contact_name', 'contact_role',
            'zipcode', 'address_street', 'address_city', 'address_state',
            'payment_term', 'observations', 'tags', 'origin'
        ];

        $actions[] = [
            'type'     => 'softdelete',
            'keep_id'  => $keep['id'],
            'del_id'   => $del['id'],
            'document' => $document,
            'merge_fields' => $fieldsToMerge,
            'reassign_orders' => ($del['order_count'] > 0),
        ];

        $deleted++;
    }

    echo "  └─\n\n";
    $report[] = "";
    $merged++;
}

// ═══════════════════════════════════════════════════════════════
// Etapa 3: Executar ações (se não dry-run)
// ═══════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 Etapa 3: " . ($dryRun ? "Resumo de ações (dry-run)" : "Executando ações...") . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$report[] = "═══════════════════════════════════════════════════════════════";
$report[] = "AÇÕES " . ($dryRun ? "(PLANEJADAS — DRY-RUN)" : "EXECUTADAS");
$report[] = "═══════════════════════════════════════════════════════════════\n";

$successCount = 0;
$errorCount = 0;

if (!$dryRun) {
    $pdo->beginTransaction();
}

try {
    foreach ($actions as $i => $action) {
        $actionNum = $i + 1;

        if ($dryRun) {
            echo "  [{$actionNum}] PLANO: Soft-delete ID={$action['del_id']} (manter ID={$action['keep_id']})\n";
            if ($action['reassign_orders']) {
                echo "       → Reatribuir pedidos de ID={$action['del_id']} para ID={$action['keep_id']}\n";
            }
            $report[] = "  [{$actionNum}] PLANO: Soft-delete ID={$action['del_id']} (manter ID={$action['keep_id']})";
            $successCount++;
            continue;
        }

        try {
            // 3a. Mesclar dados: copiar campos preenchidos do duplicado para o principal
            $stmtKeep = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
            $stmtKeep->execute([':id' => $action['keep_id']]);
            $keepData = $stmtKeep->fetch();

            $stmtDel = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
            $stmtDel->execute([':id' => $action['del_id']]);
            $delData = $stmtDel->fetch();

            if ($keepData && $delData) {
                $updates = [];
                $updateParams = [];

                foreach ($action['merge_fields'] as $field) {
                    if (
                        isset($delData[$field]) && !empty($delData[$field])
                        && (empty($keepData[$field]))
                    ) {
                        $updates[] = "{$field} = ?";
                        $updateParams[] = $delData[$field];
                    }
                }

                if (!empty($updates)) {
                    $sql = "UPDATE customers SET " . implode(', ', $updates) . " WHERE id = ?";
                    $updateParams[] = $action['keep_id'];
                    $stmtUpdate = $pdo->prepare($sql);
                    $stmtUpdate->execute($updateParams);
                    echo "  [{$actionNum}] 🔀 Mesclados " . count($updates) . " campos de ID={$action['del_id']} → ID={$action['keep_id']}\n";
                    $report[] = "  [{$actionNum}] Mesclados " . count($updates) . " campos";
                }
            }

            // 3b. Reatribuir pedidos
            if ($action['reassign_orders']) {
                $stmtReassign = $pdo->prepare("UPDATE orders SET customer_id = :keep WHERE customer_id = :del");
                $stmtReassign->execute([':keep' => $action['keep_id'], ':del' => $action['del_id']]);
                $reassigned = $stmtReassign->rowCount();
                echo "  [{$actionNum}] 📦 Reatribuídos {$reassigned} pedidos de ID={$action['del_id']} → ID={$action['keep_id']}\n";
                $report[] = "  [{$actionNum}] Reatribuídos {$reassigned} pedidos";
            }

            // 3c. Soft-delete do registro duplicado
            $stmtSoftDel = $pdo->prepare("UPDATE customers SET deleted_at = NOW(), observations = CONCAT(COALESCE(observations, ''), '\n[DUPLICATA] Mesclado com ID={$action['keep_id']} em " . date('d/m/Y H:i') . "') WHERE id = :id");
            $stmtSoftDel->execute([':id' => $action['del_id']]);

            echo "  [{$actionNum}] ✅ Soft-delete ID={$action['del_id']} (mantido ID={$action['keep_id']})\n";
            $report[] = "  [{$actionNum}] Soft-delete ID={$action['del_id']} executado com sucesso";
            $successCount++;

        } catch (PDOException $e) {
            echo "  [{$actionNum}] ❌ ERRO: " . $e->getMessage() . "\n";
            $report[] = "  [{$actionNum}] ERRO: " . $e->getMessage();
            $errorCount++;
        }
    }

    if (!$dryRun) {
        if ($errorCount === 0) {
            $pdo->commit();
            echo "\n✅ Transação commitada com sucesso.\n";
            $report[] = "\nTransação commitada com sucesso.";
        } else {
            $pdo->rollBack();
            echo "\n⚠️ Transação revertida devido a {$errorCount} erro(s).\n";
            $report[] = "\nTransação revertida devido a {$errorCount} erro(s).";
        }
    }

} catch (Exception $e) {
    if (!$dryRun && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ Erro fatal: " . $e->getMessage() . "\n";
    $report[] = "\nErro fatal: " . $e->getMessage();
}

// ═══════════════════════════════════════════════════════════════
// Etapa 4: Aplicar UNIQUE INDEX (se solicitado)
// ═══════════════════════════════════════════════════════════════

if ($applyUnique && !$dryRun && $errorCount === 0) {
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 Etapa 4: Aplicando UNIQUE INDEX em document...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    try {
        // Verificar se ainda há duplicatas ativas
        $stmtCheck = $pdo->query("
            SELECT document, COUNT(*) as total
            FROM customers
            WHERE document IS NOT NULL
              AND document != ''
              AND deleted_at IS NULL
            GROUP BY document
            HAVING COUNT(*) > 1
        ");
        $remaining = $stmtCheck->fetchAll();

        if (count($remaining) > 0) {
            echo "⚠️ Ainda existem " . count($remaining) . " duplicatas ativas. UNIQUE INDEX não será aplicado.\n";
            $report[] = "\nUNIQUE INDEX NÃO aplicado — ainda há duplicatas ativas.";
        } else {
            $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX idx_customers_document_unique (document)");
            echo "✅ UNIQUE INDEX idx_customers_document_unique aplicado com sucesso!\n";
            $report[] = "\nUNIQUE INDEX idx_customers_document_unique aplicado com sucesso.";
        }
    } catch (PDOException $e) {
        echo "⚠️ Erro ao aplicar UNIQUE INDEX: " . $e->getMessage() . "\n";
        $report[] = "\nErro ao aplicar UNIQUE INDEX: " . $e->getMessage();
    }
}

// ═══════════════════════════════════════════════════════════════
// Resumo final e relatório
// ═══════════════════════════════════════════════════════════════

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RESUMO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "  Documentos duplicados encontrados: {$totalGroups}\n";
echo "  Registros para soft-delete:        {$deleted}\n";
echo "  Ações " . ($dryRun ? "planejadas" : "executadas") . ":        {$successCount}\n";
echo "  Erros:                             {$errorCount}\n\n";

$report[] = "\n═══════════════════════════════════════════════════════════════";
$report[] = "RESUMO FINAL";
$report[] = "═══════════════════════════════════════════════════════════════";
$report[] = "Documentos duplicados encontrados: {$totalGroups}";
$report[] = "Registros para soft-delete: {$deleted}";
$report[] = "Ações " . ($dryRun ? "planejadas" : "executadas") . ": {$successCount}";
$report[] = "Erros: {$errorCount}";
$report[] = "Modo: " . ($dryRun ? 'DRY-RUN (nenhuma alteração feita)' : 'EXECUÇÃO');

// Salvar relatório
file_put_contents($reportFile, implode("\n", $report));
echo "📄 Relatório salvo em: {$reportFile}\n\n";

if ($dryRun) {
    echo "💡 Para executar as ações, rode novamente com: --execute\n";
    echo "   php scripts/fix_customer_duplicates.php --execute\n\n";
    echo "💡 Para executar e aplicar UNIQUE INDEX:\n";
    echo "   php scripts/fix_customer_duplicates.php --execute --apply-unique\n\n";
}

exit($errorCount > 0 ? 1 : 0);
