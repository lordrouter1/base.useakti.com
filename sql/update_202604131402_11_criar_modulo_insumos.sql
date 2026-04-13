-- Migration: Criar tabelas do módulo de insumos
-- Criado em: 13/04/2026 14:02
-- Sequencial: 11

-- ============================================================================
-- Módulo de Insumos — Criação de tabelas
-- Tabelas: supply_categories, supplies, supply_suppliers,
--          product_supplies, supply_stock_items, supply_stock_movements,
--          supply_price_history
-- ============================================================================

-- 1. Categorias de Insumos
CREATE TABLE IF NOT EXISTS `supply_categories` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insumos (Matérias-Primas)
CREATE TABLE IF NOT EXISTS `supplies` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `category_id`     INT NULL,
    `code`            VARCHAR(50) NOT NULL,
    `name`            VARCHAR(200) NOT NULL,
    `description`     TEXT NULL,
    `unit_measure`    ENUM('un','kg','g','mg','L','mL','m','cm','mm','m2','m3','pc','cx','rl','fl','par')
                      NOT NULL DEFAULT 'un',
    `cost_price`      DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `min_stock`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `reorder_point`   DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `waste_percent`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `notes`           TEXT NULL,
    `fiscal_ncm`      VARCHAR(20) NULL,
    `fiscal_cest`     VARCHAR(20) NULL,
    `fiscal_origem`   VARCHAR(5) NULL,
    `fiscal_unidade`  VARCHAR(10) NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME NULL,

    UNIQUE KEY `uq_supplies_code` (`code`),
    INDEX `idx_supplies_category` (`category_id`),
    INDEX `idx_supplies_name` (`name`),
    INDEX `idx_supplies_active` (`is_active`),
    INDEX `idx_supplies_deleted` (`deleted_at`),

    CONSTRAINT `fk_supplies_category`
        FOREIGN KEY (`category_id`) REFERENCES `supply_categories`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vínculo Insumo ↔ Fornecedor (N:N)
CREATE TABLE IF NOT EXISTS `supply_suppliers` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `supply_id`         INT NOT NULL,
    `supplier_id`       INT NOT NULL,
    `supplier_sku`      VARCHAR(100) NULL,
    `supplier_name`     VARCHAR(200) NULL,
    `unit_price`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `min_order_qty`     DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
    `lead_time_days`    INT NULL,
    `conversion_factor` DECIMAL(12,6) NOT NULL DEFAULT 1.000000
                        COMMENT 'Fator de conversão: 1 UOM do fornecedor = X UOM do insumo',
    `is_preferred`      TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    `notes`             TEXT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_supply_supplier` (`supply_id`, `supplier_id`),
    INDEX `idx_supply_suppliers_supplier` (`supplier_id`),
    INDEX `idx_supply_suppliers_preferred` (`is_preferred`),

    CONSTRAINT `fk_supply_suppliers_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    -- FK para suppliers adicionada condicionalmente no final (tabela pode não existir)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BOM — Vinculação Insumo ↔ Produto (Bill of Materials)
CREATE TABLE IF NOT EXISTS `product_supplies` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `product_id`      INT NOT NULL,
    `supply_id`       INT NOT NULL,
    `quantity`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `unit_measure`    ENUM('un','kg','g','mg','L','mL','m','cm','mm','m2','m3','pc','cx','rl','fl','par')
                      NOT NULL DEFAULT 'un',
    `waste_percent`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_optional`     TINYINT(1) NOT NULL DEFAULT 0,
    `notes`           TEXT NULL,
    `sort_order`      INT NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_product_supply` (`product_id`, `supply_id`),
    INDEX `idx_product_supplies_supply` (`supply_id`),

    CONSTRAINT `fk_product_supplies_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_product_supplies_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Estoque de Insumos por Armazém (com Lote e Validade)
CREATE TABLE IF NOT EXISTS `supply_stock_items` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `warehouse_id`    INT NOT NULL,
    `supply_id`       INT NOT NULL,
    `quantity`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `min_quantity`    DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `batch_number`    VARCHAR(100) NULL COMMENT 'Número do lote (rastreabilidade)',
    `expiry_date`     DATE NULL COMMENT 'Data de validade do lote (FEFO)',
    `location_code`   VARCHAR(50) NULL,
    `last_updated`    DATETIME NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_supply_stock_warehouse` (`warehouse_id`, `supply_id`, `batch_number`),
    INDEX `idx_supply_stock_supply` (`supply_id`),
    INDEX `idx_supply_stock_quantity` (`quantity`),
    INDEX `idx_supply_stock_expiry` (`expiry_date`),
    INDEX `idx_supply_stock_batch` (`batch_number`),

    CONSTRAINT `fk_supply_stock_warehouse`
        FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_supply_stock_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Movimentações de Estoque de Insumos (com Lote)
CREATE TABLE IF NOT EXISTS `supply_stock_movements` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `warehouse_id`    INT NOT NULL,
    `supply_id`       INT NOT NULL,
    `type`            ENUM('entrada','saida','ajuste','transferencia','consumo_producao')
                      NOT NULL,
    `quantity`        DECIMAL(12,4) NOT NULL,
    `unit_price`      DECIMAL(12,4) NULL,
    `batch_number`    VARCHAR(100) NULL COMMENT 'Lote relacionado à movimentação',
    `reason`          VARCHAR(255) NULL,
    `reference_type`  VARCHAR(50) NULL,
    `reference_id`    INT NULL,
    `created_by`      INT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_supply_movements_warehouse` (`warehouse_id`),
    INDEX `idx_supply_movements_supply` (`supply_id`),
    INDEX `idx_supply_movements_type` (`type`),
    INDEX `idx_supply_movements_ref` (`reference_type`, `reference_id`),
    INDEX `idx_supply_movements_date` (`created_at`),
    INDEX `idx_supply_movements_batch` (`batch_number`),

    CONSTRAINT `fk_supply_movements_warehouse`
        FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_supply_movements_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Histórico de Preços de Insumos
CREATE TABLE IF NOT EXISTS `supply_price_history` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `supply_id`       INT NOT NULL,
    `supplier_id`     INT NULL COMMENT 'NULL = ajuste manual ou CMP calculado',
    `unit_price`      DECIMAL(12,4) NOT NULL,
    `quantity`        DECIMAL(12,4) NULL COMMENT 'Quantidade da compra que originou o preço',
    `source`          ENUM('compra','cotacao','ajuste_manual','cmp_calculado') NOT NULL DEFAULT 'compra',
    `notes`           VARCHAR(255) NULL,
    `created_by`      INT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_price_history_supply` (`supply_id`),
    INDEX `idx_price_history_supplier` (`supplier_id`),
    INDEX `idx_price_history_date` (`created_at`),
    INDEX `idx_price_history_source` (`source`),

    CONSTRAINT `fk_price_history_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    -- FK para suppliers adicionada condicionalmente no final (tabela pode não existir)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias padrão de insumos (com verificação de idempotência)
INSERT INTO `supply_categories` (`name`, `description`, `sort_order`)
SELECT 'Matéria-Prima', 'Materiais base para produção', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `supply_categories` WHERE `name` = 'Matéria-Prima');

INSERT INTO `supply_categories` (`name`, `description`, `sort_order`)
SELECT 'Embalagem', 'Materiais de embalagem e acondicionamento', 2
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `supply_categories` WHERE `name` = 'Embalagem');

INSERT INTO `supply_categories` (`name`, `description`, `sort_order`)
SELECT 'Acabamento', 'Materiais de acabamento e finalização', 3
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `supply_categories` WHERE `name` = 'Acabamento');

INSERT INTO `supply_categories` (`name`, `description`, `sort_order`)
SELECT 'Fixação', 'Parafusos, pregos, cola, adesivos', 4
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `supply_categories` WHERE `name` = 'Fixação');

INSERT INTO `supply_categories` (`name`, `description`, `sort_order`)
SELECT 'Químico', 'Tintas, solventes, vernizes, tratamentos', 5
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `supply_categories` WHERE `name` = 'Químico');

INSERT INTO `supply_categories` (`name`, `description`, `sort_order`)
SELECT 'Consumível', 'Materiais de consumo interno (lixas, estopas, etc.)', 6
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `supply_categories` WHERE `name` = 'Consumível');

-- ============================================================================
-- FKs condicionais para tabela suppliers (pode não existir em todos os tenants)
-- ============================================================================
SET @has_suppliers = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers');

SET @sql_fk1 = IF(@has_suppliers > 0,
    'ALTER TABLE `supply_suppliers` ADD CONSTRAINT `fk_supply_suppliers_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1');
PREPARE stmt1 FROM @sql_fk1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @sql_fk2 = IF(@has_suppliers > 0,
    'ALTER TABLE `supply_price_history` ADD CONSTRAINT `fk_price_history_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1');
PREPARE stmt2 FROM @sql_fk2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
