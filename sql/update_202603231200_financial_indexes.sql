-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Índices de performance para o módulo financeiro
-- Data: 2026-03-23 12:00
-- Fase 1 do relatório de refatoração
-- Descrição:
--   1. Índice em order_installments.due_date (P1)
--   2. Índice composto em orders (pipeline_stage, status) (P2)
--   3. Índice em order_installments (status, due_date) para queries de vencimento
-- ═══════════════════════════════════════════════════════════════

SET @dbname = DATABASE();

-- ─────────────────────────────────────────────────────
-- 1. Índice em due_date (order_installments)
--    Query afetada: filtro por parcelas vencidas, ordenação por due_date
-- ─────────────────────────────────────────────────────
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'order_installments' AND INDEX_NAME = 'idx_oi_due_date') > 0,
    'SELECT 1',
    'CREATE INDEX idx_oi_due_date ON order_installments (due_date)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- ─────────────────────────────────────────────────────
-- 2. Índice composto (status, due_date) em order_installments
--    Query afetada: getOverdueInstallments, getUpcomingInstallments, updateOverdueInstallments
-- ─────────────────────────────────────────────────────
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'order_installments' AND INDEX_NAME = 'idx_oi_status_due') > 0,
    'SELECT 1',
    'CREATE INDEX idx_oi_status_due ON order_installments (status, due_date)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- ─────────────────────────────────────────────────────
-- 3. Índice em orders (pipeline_stage, status)
--    Query afetada: getAllInstallmentsPaginated, getOrdersPendingPayment
-- ─────────────────────────────────────────────────────
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_orders_pipeline_status') > 0,
    'SELECT 1',
    'CREATE INDEX idx_orders_pipeline_status ON orders (pipeline_stage, status)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- ─────────────────────────────────────────────────────
-- 4. Índice em order_installments (is_confirmed, status)
--    Query afetada: getPendingConfirmations, getSummary
-- ─────────────────────────────────────────────────────
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'order_installments' AND INDEX_NAME = 'idx_oi_confirmed_status') > 0,
    'SELECT 1',
    'CREATE INDEX idx_oi_confirmed_status ON order_installments (is_confirmed, status)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- ─────────────────────────────────────────────────────
-- 5. Índice em order_installments (paid_date) para getSummary e getChartData
-- ─────────────────────────────────────────────────────
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'order_installments' AND INDEX_NAME = 'idx_oi_paid_date') > 0,
    'SELECT 1',
    'CREATE INDEX idx_oi_paid_date ON order_installments (paid_date)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;
