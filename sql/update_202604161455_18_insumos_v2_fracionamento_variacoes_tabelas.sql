-- Migration: Insumos v2 - Fracionamento, variações, substitutos, alertas, forecast, consumo
-- Criado em: 16/04/2026 14:55
-- Sequencial: 18

-- =============================================================================
-- 1. ALTER supplies — adicionar colunas de fracionamento
-- =============================================================================
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'permite_fracionamento') = 0,
    'ALTER TABLE `supplies` ADD COLUMN `permite_fracionamento` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''Se 0, consumo é arredondado para cima (CEIL)'' AFTER `waste_percent`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'decimal_precision') = 0,
    'ALTER TABLE `supplies` ADD COLUMN `decimal_precision` TINYINT NOT NULL DEFAULT 4 COMMENT ''Casas decimais para cálculos de consumo (2-6)'' AFTER `permite_fracionamento`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 2. ALTER product_supplies — adicionar variation_id e loss_percent
-- =============================================================================
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND COLUMN_NAME = 'variation_id') = 0,
    'ALTER TABLE `product_supplies` ADD COLUMN `variation_id` INT UNSIGNED NULL DEFAULT NULL COMMENT ''Se preenchido, aplica-se a esta variação; se NULL, aplica ao produto pai'' AFTER `product_id`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND COLUMN_NAME = 'loss_percent') = 0,
    'ALTER TABLE `product_supplies` ADD COLUMN `loss_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT ''Percentual de perda específico deste vínculo (override do waste_percent do insumo)'' AFTER `waste_percent`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remover índice único antigo e criar novo incluindo variation_id
-- É necessário dropar as FKs que dependem do índice antes de removê-lo

-- Drop FK fk_product_supplies_product (se existir)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND CONSTRAINT_NAME = 'fk_product_supplies_product' AND CONSTRAINT_TYPE = 'FOREIGN KEY') > 0,
    'ALTER TABLE `product_supplies` DROP FOREIGN KEY `fk_product_supplies_product`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop FK fk_product_supplies_supply (se existir)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND CONSTRAINT_NAME = 'fk_product_supplies_supply' AND CONSTRAINT_TYPE = 'FOREIGN KEY') > 0,
    'ALTER TABLE `product_supplies` DROP FOREIGN KEY `fk_product_supplies_supply`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agora é seguro remover o índice antigo
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND INDEX_NAME = 'uq_product_supply') > 0,
    'ALTER TABLE `product_supplies` DROP INDEX `uq_product_supply`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar novo índice único incluindo variation_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND INDEX_NAME = 'idx_product_variation_supply') = 0,
    'ALTER TABLE `product_supplies` ADD UNIQUE INDEX `idx_product_variation_supply` (`product_id`, `variation_id`, `supply_id`)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Re-criar as FKs
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND CONSTRAINT_NAME = 'fk_product_supplies_product' AND CONSTRAINT_TYPE = 'FOREIGN KEY') = 0,
    'ALTER TABLE `product_supplies` ADD CONSTRAINT `fk_product_supplies_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_supplies' AND CONSTRAINT_NAME = 'fk_product_supplies_supply' AND CONSTRAINT_TYPE = 'FOREIGN KEY') = 0,
    'ALTER TABLE `product_supplies` ADD CONSTRAINT `fk_product_supplies_supply` FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 3. supply_substitutes — Insumos substitutos de emergência
-- =============================================================================
CREATE TABLE IF NOT EXISTS `supply_substitutes` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `supply_id`       INT NOT NULL COMMENT 'Insumo principal',
    `substitute_id`   INT NOT NULL COMMENT 'Insumo substituto',
    `conversion_rate` DECIMAL(12,6) NOT NULL DEFAULT 1.000000
                      COMMENT 'Proporção: 1 un do principal = X un do substituto',
    `priority`        TINYINT UNSIGNED NOT NULL DEFAULT 1
                      COMMENT 'Prioridade de substituição (1 = mais prioritário)',
    `notes`           TEXT NULL,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `tenant_id`       INT NOT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_supply_substitutes_supply` (`supply_id`),
    INDEX `idx_supply_substitutes_substitute` (`substitute_id`),
    INDEX `idx_supply_substitutes_tenant` (`tenant_id`),
    UNIQUE INDEX `idx_supply_substitute_pair` (`supply_id`, `substitute_id`),

    CONSTRAINT `fk_supply_substitutes_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_supply_substitutes_substitute`
        FOREIGN KEY (`substitute_id`) REFERENCES `supplies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_supply_substitutes_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos substitutos de emergência com prioridade';

-- =============================================================================
-- 4. supply_cost_alerts — Alertas de custo e margem
-- =============================================================================
CREATE TABLE IF NOT EXISTS `supply_cost_alerts` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `product_id`        INT NOT NULL,
    `supply_id`         INT NOT NULL,
    `old_cost`          DECIMAL(12,4) NOT NULL COMMENT 'Custo anterior do insumo',
    `new_cost`          DECIMAL(12,4) NOT NULL COMMENT 'Novo custo após recálculo CMP',
    `old_product_cost`  DECIMAL(12,4) NOT NULL COMMENT 'Custo de produção anterior do produto',
    `new_product_cost`  DECIMAL(12,4) NOT NULL COMMENT 'Novo custo de produção do produto',
    `current_price`     DECIMAL(12,4) NOT NULL COMMENT 'Preço de venda atual do produto',
    `old_margin`        DECIMAL(5,2) NOT NULL COMMENT 'Margem anterior (%)',
    `new_margin`        DECIMAL(5,2) NOT NULL COMMENT 'Nova margem (%)',
    `margin_threshold`  DECIMAL(5,2) NOT NULL COMMENT 'Limite mínimo configurado',
    `suggested_price`   DECIMAL(12,4) NULL COMMENT 'Preço sugerido para manter margem mínima',
    `status`            ENUM('pending','acknowledged','applied','dismissed') NOT NULL DEFAULT 'pending',
    `acknowledged_by`   INT NULL,
    `acknowledged_at`   DATETIME NULL,
    `tenant_id`         INT NOT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_cost_alerts_product` (`product_id`),
    INDEX `idx_cost_alerts_supply` (`supply_id`),
    INDEX `idx_cost_alerts_status` (`status`),
    INDEX `idx_cost_alerts_tenant` (`tenant_id`),

    CONSTRAINT `fk_cost_alerts_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cost_alerts_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cost_alerts_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alertas de impacto de custo na margem do produto';

-- =============================================================================
-- 5. production_consumption_log — Apontamento de consumo real vs planejado
-- =============================================================================
CREATE TABLE IF NOT EXISTS `production_consumption_log` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`            INT NOT NULL COMMENT 'Pedido/ordem de produção',
    `product_id`          INT NOT NULL,
    `variation_id`        INT NULL,
    `supply_id`           INT NOT NULL,
    `warehouse_id`        INT NOT NULL,
    `planned_quantity`    DECIMAL(12,4) NOT NULL COMMENT 'Quantidade calculada (ratio x lote)',
    `actual_quantity`     DECIMAL(12,4) NULL COMMENT 'Quantidade real apontada pelo operador',
    `batch_number`        VARCHAR(50) NULL COMMENT 'Lote consumido',
    `variance`            DECIMAL(12,4) GENERATED ALWAYS AS (`actual_quantity` - `planned_quantity`) STORED
                          COMMENT 'Diferença: positivo = desperdício, negativo = economia',
    `variance_percent`    DECIMAL(8,4) GENERATED ALWAYS AS (
                              CASE WHEN `planned_quantity` > 0
                                  THEN ((`actual_quantity` - `planned_quantity`) / `planned_quantity`) * 100
                                  ELSE 0
                              END
                          ) STORED COMMENT 'Variação percentual',
    `notes`               TEXT NULL,
    `created_by`          INT NOT NULL,
    `tenant_id`           INT NOT NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_consumption_order` (`order_id`),
    INDEX `idx_consumption_product` (`product_id`),
    INDEX `idx_consumption_supply` (`supply_id`),
    INDEX `idx_consumption_warehouse` (`warehouse_id`),
    INDEX `idx_consumption_tenant` (`tenant_id`),
    INDEX `idx_consumption_created` (`created_at`),

    CONSTRAINT `fk_consumption_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_consumption_warehouse`
        FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_consumption_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de consumo real vs planejado por ordem de produção';

-- =============================================================================
-- 6. supply_rupture_forecasts — Cache de previsão de ruptura
-- =============================================================================
CREATE TABLE IF NOT EXISTS `supply_rupture_forecasts` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `supply_id`           INT NOT NULL,
    `warehouse_id`        INT NULL COMMENT 'NULL = total consolidado',
    `current_stock`       DECIMAL(12,4) NOT NULL,
    `committed_quantity`  DECIMAL(12,4) NOT NULL COMMENT 'Soma dos pedidos em aberto',
    `available_stock`     DECIMAL(12,4) GENERATED ALWAYS AS (`current_stock` - `committed_quantity`) STORED,
    `days_to_rupture`     INT NULL COMMENT 'Dias estimados até ruptura',
    `status`              ENUM('ok','warning','critical','ruptured') NOT NULL DEFAULT 'ok',
    `last_calculated_at`  DATETIME NOT NULL,
    `tenant_id`           INT NOT NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_forecast_supply` (`supply_id`),
    INDEX `idx_forecast_status` (`status`),
    INDEX `idx_forecast_tenant` (`tenant_id`),
    UNIQUE INDEX `idx_forecast_supply_warehouse` (`supply_id`, `warehouse_id`),

    CONSTRAINT `fk_forecast_supply`
        FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_forecast_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cache de previsão de ruptura de estoque por insumo';

-- =============================================================================
-- 7. supply_settings — Configurações do módulo por tenant
-- =============================================================================
CREATE TABLE IF NOT EXISTS `supply_settings` (
    `id`                          INT AUTO_INCREMENT PRIMARY KEY,
    `min_margin_threshold`        DECIMAL(5,2) NOT NULL DEFAULT 15.00
                                  COMMENT 'Margem mínima (%) para gerar alerta de custo',
    `forecast_calculation_method` ENUM('average','weighted','last_30_days') NOT NULL DEFAULT 'weighted'
                                  COMMENT 'Método de cálculo para previsão de ruptura',
    `allow_negative_stock`        TINYINT(1) NOT NULL DEFAULT 0,
    `default_fefo_strategy`       ENUM('fefo','fifo','manual') NOT NULL DEFAULT 'fefo',
    `auto_recalculate_cmp`        TINYINT(1) NOT NULL DEFAULT 1,
    `default_decimal_precision`   TINYINT NOT NULL DEFAULT 4,
    `tenant_id`                   INT NOT NULL,
    `created_at`                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX `idx_supply_settings_tenant` (`tenant_id`),

    CONSTRAINT `fk_supply_settings_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configurações do módulo de insumos por tenant';
