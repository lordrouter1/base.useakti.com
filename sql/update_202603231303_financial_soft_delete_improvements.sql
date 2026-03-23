-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Soft-delete e melhorias em transações e parcelas
-- Data: 2026-03-23 13:03
-- Fase 2 do relatório de refatoração
-- Descrição:
--   1. Soft-delete para financial_transactions (deleted_at)
--   2. Campos de juros/multa em order_installments
--   3. Campo original_amount para rastreabilidade
-- ═══════════════════════════════════════════════════════════════

SET @dbname = DATABASE();

-- ─────────────────────────────────────────────────────
-- 1. Soft-delete em financial_transactions
-- ─────────────────────────────────────────────────────

-- Adicionar coluna deleted_at se não existir
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'financial_transactions'
      AND COLUMN_NAME = 'deleted_at');

SET @stmt = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE financial_transactions ADD COLUMN deleted_at DATETIME DEFAULT NULL COMMENT ''Soft-delete: data de exclusão'' AFTER notes'
);
PREPARE alterStmt FROM @stmt;
EXECUTE alterStmt;
DEALLOCATE PREPARE alterStmt;

-- Índice para queries que filtram registros não deletados
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'financial_transactions'
      AND INDEX_NAME = 'idx_ft_deleted_at');

SET @stmt = IF(@idx_exists > 0,
    'SELECT 1',
    'CREATE INDEX idx_ft_deleted_at ON financial_transactions (deleted_at)'
);
PREPARE alterStmt FROM @stmt;
EXECUTE alterStmt;
DEALLOCATE PREPARE alterStmt;

-- ─────────────────────────────────────────────────────
-- 2. Campo original_amount em order_installments
--    (para rastrear valor original antes de split/partial)
-- ─────────────────────────────────────────────────────

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'order_installments'
      AND COLUMN_NAME = 'original_amount');

SET @stmt = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE order_installments ADD COLUMN original_amount DECIMAL(12,2) DEFAULT NULL COMMENT ''Valor original antes de split/partial'' AFTER amount'
);
PREPARE alterStmt FROM @stmt;
EXECUTE alterStmt;
DEALLOCATE PREPARE alterStmt;

-- ─────────────────────────────────────────────────────
-- 3. Campos de juros e multa em order_installments
-- ─────────────────────────────────────────────────────

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'order_installments'
      AND COLUMN_NAME = 'interest_amount');

SET @stmt = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE order_installments ADD COLUMN interest_amount DECIMAL(12,2) DEFAULT 0 COMMENT ''Valor de juros calculado'' AFTER original_amount'
);
PREPARE alterStmt FROM @stmt;
EXECUTE alterStmt;
DEALLOCATE PREPARE alterStmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'order_installments'
      AND COLUMN_NAME = 'penalty_amount');

SET @stmt = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE order_installments ADD COLUMN penalty_amount DECIMAL(12,2) DEFAULT 0 COMMENT ''Valor de multa'' AFTER interest_amount'
);
PREPARE alterStmt FROM @stmt;
EXECUTE alterStmt;
DEALLOCATE PREPARE alterStmt;
