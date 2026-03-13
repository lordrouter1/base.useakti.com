-- ============================================================================
-- UPDATE: update_20260312_fix_nf_status_column.sql
-- Descrição: Corrige coluna nf_status na tabela orders — converte de ENUM
--            para VARCHAR(20) se necessário, e garante que aceita NULL.
--            Resolve erro "Data truncated for column 'nf_status'" que ocorre
--            quando o campo é ENUM e recebe string vazia.
-- Data: 2026-03-12
-- Autor: Sistema Akti
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();

-- ─────────────────────────────────────────────────────
-- 1. Se nf_status existir como ENUM, converter para VARCHAR(20)
-- ─────────────────────────────────────────────────────
SET @col_type = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_status');

SET @sql_fix = IF(@col_type = 'enum',
    'ALTER TABLE orders MODIFY COLUMN nf_status VARCHAR(20) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_fix;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────
-- 2. Garantir que a coluna existe (caso nunca tenha sido criada)
-- ─────────────────────────────────────────────────────
SET @exists_nfst = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nf_status');
SET @sql_add = IF(@exists_nfst = 0,
    'ALTER TABLE orders ADD COLUMN nf_status VARCHAR(20) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql_add;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────
-- 3. Limpar valores vazios existentes (converter '' para NULL)
-- ─────────────────────────────────────────────────────
UPDATE orders SET nf_status = NULL WHERE nf_status = '';

SET FOREIGN_KEY_CHECKS = 1;
