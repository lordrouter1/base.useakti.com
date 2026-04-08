-- Migration: Corrigir colunas de auditoria em migration_logs (master e init_base)
-- Criado em: 08/04/2026 15:26
-- Sequencial: 9
-- Nota: Aplicar no akti_master e demais bancos que possuam migration_logs.
--       Resolve erro "Unknown column sql_content in INSERT INTO" e
--       "Table migration_logs doesn't exist" em akti_init_base.

-- Adicionar coluna sql_content se a tabela existir mas a coluna nao
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs' AND COLUMN_NAME = 'sql_content') = 0,
    'ALTER TABLE `migration_logs` ADD COLUMN `sql_content` LONGTEXT NULL AFTER `error_log`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna warnings se a tabela existir mas a coluna nao
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs' AND COLUMN_NAME = 'warnings') = 0,
    'ALTER TABLE `migration_logs` ADD COLUMN `warnings` TEXT NULL AFTER `sql_content`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna execution_time_ms se a tabela existir mas a coluna nao
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs' AND COLUMN_NAME = 'execution_time_ms') = 0,
    'ALTER TABLE `migration_logs` ADD COLUMN `execution_time_ms` INT UNSIGNED NULL AFTER `warnings`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
