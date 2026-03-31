-- ============================================================
-- Migration: Estruturas DDL extraídas dos Models PHP
-- Fonte: Stock.php, OrderItemLog.php, PreparationStep.php, OrderPreparation.php
-- Objetivo: Mover ALTER TABLE e CREATE TABLE IF NOT EXISTS para arquivo SQL
-- ============================================================

-- 1. Tabela order_stock_deductions (extraída de Stock.php)
CREATE TABLE IF NOT EXISTS `order_stock_deductions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `order_item_id` INT(11) NOT NULL,
    `warehouse_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `combination_id` INT(11) DEFAULT NULL,
    `quantity` DECIMAL(12,2) NOT NULL,
    `movement_id` INT(11) DEFAULT NULL,
    `status` ENUM('deducted','reversed') NOT NULL DEFAULT 'deducted',
    `deducted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `reversed_at` DATETIME DEFAULT NULL,
    `reversed_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Coluna is_default na tabela warehouses (extraída de Stock.php)
SET @col_exists_wd = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses' AND COLUMN_NAME = 'is_default');
SET @sql_wd = IF(@col_exists_wd = 0,
    'ALTER TABLE warehouses ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active',
    'SELECT 1');
PREPARE stmt_wd FROM @sql_wd;
EXECUTE stmt_wd;
DEALLOCATE PREPARE stmt_wd;

-- 3. Coluna stock_warehouse_id na tabela orders (extraída de Stock.php)
SET @col_exists_ow = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'stock_warehouse_id');
SET @sql_ow = IF(@col_exists_ow = 0,
    'ALTER TABLE orders ADD COLUMN stock_warehouse_id INT(11) DEFAULT NULL AFTER tracking_code',
    'SELECT 1');
PREPARE stmt_ow FROM @sql_ow;
EXECUTE stmt_ow;
DEALLOCATE PREPARE stmt_ow;

-- 4. Tabela order_item_logs (extraída de OrderItemLog.php)
CREATE TABLE IF NOT EXISTS order_item_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    message TEXT DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_order_item_id (order_item_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabela preparation_steps (extraída de PreparationStep.php)
CREATE TABLE IF NOT EXISTS preparation_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_key VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    description VARCHAR(500) DEFAULT '',
    icon VARCHAR(100) DEFAULT 'fas fa-check',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabela order_preparation_checklist (extraída de OrderPreparation.php)
CREATE TABLE IF NOT EXISTS order_preparation_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    check_key VARCHAR(100) NOT NULL,
    checked TINYINT(1) DEFAULT 0,
    checked_by INT DEFAULT NULL,
    checked_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_order_key (order_id, check_key),
    INDEX idx_order_id (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabela applied_migrations (para o sistema de migrations automatizado)
CREATE TABLE IF NOT EXISTS `applied_migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL UNIQUE,
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `checksum` VARCHAR(64) DEFAULT NULL COMMENT 'MD5 do arquivo SQL para detectar alterações'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
