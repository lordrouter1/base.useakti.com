-- Migration: Adicionar coluna show_in_store nas tabelas categories e subcategories
-- Criado em: 07/04/2026 16:30
-- Sequencial: 5

-- Permite ocultar categorias e subcategorias da loja online
-- Default 1 (visível) para não afetar registros existentes

SET @dbname = DATABASE();

-- Categories: adicionar show_in_store
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'show_in_store') = 0,
    'ALTER TABLE `categories` ADD COLUMN `show_in_store` TINYINT(1) NOT NULL DEFAULT 1 AFTER `name`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Subcategories: adicionar show_in_store
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'subcategories' AND COLUMN_NAME = 'show_in_store') = 0,
    'ALTER TABLE `subcategories` ADD COLUMN `show_in_store` TINYINT(1) NOT NULL DEFAULT 1 AFTER `name`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
