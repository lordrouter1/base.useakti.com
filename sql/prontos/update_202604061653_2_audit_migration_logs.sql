-- Migration: Adicionar colunas de auditoria na tabela migration_logs
-- Criado em: 06/04/2025 16:53
-- Sequencial: 2

-- Adicionar coluna sql_content para armazenar cópia do SQL executado
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs' AND COLUMN_NAME = 'sql_content') = 0,
    'ALTER TABLE `migration_logs` ADD COLUMN `sql_content` LONGTEXT NULL AFTER `error_log`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna warnings para armazenar warnings do MySQL
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs' AND COLUMN_NAME = 'warnings') = 0,
    'ALTER TABLE `migration_logs` ADD COLUMN `warnings` TEXT NULL AFTER `sql_content`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna execution_time_ms para tempo de execução em milissegundos
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migration_logs' AND COLUMN_NAME = 'execution_time_ms') = 0,
    'ALTER TABLE `migration_logs` ADD COLUMN `execution_time_ms` INT UNSIGNED NULL AFTER `warnings`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
