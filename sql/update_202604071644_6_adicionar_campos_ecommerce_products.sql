-- Migration: Adicionar campos de e-commerce na tabela products
-- Criado em: 07/04/2026 16:44
-- Sequencial: 6

-- Campos para integração com marketplaces (Shopee, Mercado Livre, Amazon, etc.)

SET @dbname = DATABASE();

-- ecommerce_description (descrição detalhada HTML)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_description') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_description` LONGTEXT NULL DEFAULT NULL AFTER `description`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_brand (marca)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_brand') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_brand` VARCHAR(100) NULL DEFAULT NULL AFTER `ecommerce_description`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_gtin (código de barras EAN/GTIN)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_gtin') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_gtin` VARCHAR(14) NULL DEFAULT NULL AFTER `ecommerce_brand`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_weight (peso em kg)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_weight') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_weight` DECIMAL(10,3) NULL DEFAULT NULL AFTER `ecommerce_gtin`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_height (altura em cm)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_height') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_height` DECIMAL(10,2) NULL DEFAULT NULL AFTER `ecommerce_weight`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_width (largura em cm)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_width') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_width` DECIMAL(10,2) NULL DEFAULT NULL AFTER `ecommerce_height`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_length (comprimento em cm)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_length') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_length` DECIMAL(10,2) NULL DEFAULT NULL AFTER `ecommerce_width`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_condition (condição: new, used, refurbished)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_condition') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_condition` VARCHAR(20) NOT NULL DEFAULT ''new'' AFTER `ecommerce_length`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_warranty (garantia)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_warranty') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_warranty` VARCHAR(100) NULL DEFAULT NULL AFTER `ecommerce_condition`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_keywords (palavras-chave / tags SEO)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_keywords') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_keywords` TEXT NULL DEFAULT NULL AFTER `ecommerce_warranty`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ecommerce_video_url (URL de vídeo do produto)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ecommerce_video_url') = 0,
    'ALTER TABLE `products` ADD COLUMN `ecommerce_video_url` VARCHAR(500) NULL DEFAULT NULL AFTER `ecommerce_keywords`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
