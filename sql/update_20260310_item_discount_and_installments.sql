-- ============================================================================
-- UPDATE: update_20260310_item_discount_and_installments.sql
-- Descrição: Adiciona coluna de desconto por item (order_items.discount)
--            e garante que colunas financeiras existam na tabela orders.
-- Data: 2026-03-10
-- Autor: Sistema Akti
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────
-- 1. Adicionar coluna discount na tabela order_items
-- ─────────────────────────────────────────────────────
SET @dbname = DATABASE();

-- Verifica se a coluna discount já existe em order_items
SET @exists_discount = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'discount');

SET @sql_add_discount = IF(@exists_discount = 0,
    'ALTER TABLE order_items ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT \'Desconto aplicado ao item\' AFTER subtotal',
    'SELECT 1');
PREPARE stmt FROM @sql_add_discount;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────
-- 2. Garantir colunas financeiras na tabela orders
-- ─────────────────────────────────────────────────────

-- installments
SET @exists_installments = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'installments');
SET @sql_inst = IF(@exists_installments = 0,
    'ALTER TABLE orders ADD COLUMN installments INT DEFAULT NULL COMMENT \'Número de parcelas\' AFTER payment_method',
    'SELECT 1');
PREPARE stmt FROM @sql_inst;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- installment_value
SET @exists_iv = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'installment_value');
SET @sql_iv = IF(@exists_iv = 0,
    'ALTER TABLE orders ADD COLUMN installment_value DECIMAL(12,2) DEFAULT NULL COMMENT \'Valor de cada parcela\' AFTER installments',
    'SELECT 1');
PREPARE stmt FROM @sql_iv;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- down_payment
SET @exists_dp = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'down_payment');
SET @sql_dp = IF(@exists_dp = 0,
    'ALTER TABLE orders ADD COLUMN down_payment DECIMAL(12,2) DEFAULT 0 COMMENT \'Valor de entrada/sinal\' AFTER installment_value',
    'SELECT 1');
PREPARE stmt FROM @sql_dp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nf_number
SET @exists_nfn = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_number');
SET @sql_nfn = IF(@exists_nfn = 0,
    'ALTER TABLE orders ADD COLUMN nf_number VARCHAR(50) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_nfn;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nf_series
SET @exists_nfs = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_series');
SET @sql_nfs = IF(@exists_nfs = 0,
    'ALTER TABLE orders ADD COLUMN nf_series VARCHAR(10) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_nfs;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nf_status
SET @exists_nfst = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_status');
SET @sql_nfst = IF(@exists_nfst = 0,
    'ALTER TABLE orders ADD COLUMN nf_status VARCHAR(20) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_nfst;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nf_access_key
SET @exists_nfak = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_access_key');
SET @sql_nfak = IF(@exists_nfak = 0,
    'ALTER TABLE orders ADD COLUMN nf_access_key VARCHAR(50) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_nfak;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nf_notes
SET @exists_nfno = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_notes');
SET @sql_nfno = IF(@exists_nfno = 0,
    'ALTER TABLE orders ADD COLUMN nf_notes TEXT DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_nfno;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
