-- Migration: Criar tabela checkout_tokens e adicionar campo checkout_token_id em orders
-- Criado em: 08/04/2026 09:10
-- Sequencial: 8

-- ─────────────────────────────────────────────
-- 1. Tabela checkout_tokens
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `checkout_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL,
    `order_id` INT NOT NULL,
    `installment_id` INT NULL,
    `gateway_slug` VARCHAR(50) NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'BRL',
    `allowed_methods` JSON NULL COMMENT '["pix","credit_card","boleto"] ou null=todos',
    `status` ENUM('active','used','expired','cancelled') DEFAULT 'active',
    `used_method` VARCHAR(50) NULL COMMENT 'Metodo efetivamente usado: pix, credit_card, boleto',
    `external_id` VARCHAR(255) NULL COMMENT 'ID da transacao no gateway',
    `customer_name` VARCHAR(255) NULL,
    `customer_email` VARCHAR(255) NULL,
    `customer_document` VARCHAR(20) NULL,
    `metadata` JSON NULL,
    `ip_address` VARCHAR(45) NULL COMMENT 'IP do pagador ao acessar',
    `used_at` DATETIME NULL,
    `expires_at` DATETIME NOT NULL,
    `created_by` INT NULL COMMENT 'user_id que gerou o link',
    `tenant_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_token` (`token`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_installment` (`installment_id`),
    INDEX `idx_status_expires` (`status`, `expires_at`),
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_checkout_tokens_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checkout_tokens_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK para order_installments (condicional — tabela pode não existir em akti_init_base)
SET @has_installments_table = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'order_installments'
);
SET @has_installments_fk = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'checkout_tokens'
      AND COLUMN_NAME = 'installment_id'
      AND REFERENCED_TABLE_NAME = 'order_installments'
);
SET @sql_inst_fk = IF(@has_installments_table > 0 AND @has_installments_fk = 0,
    'ALTER TABLE `checkout_tokens` ADD CONSTRAINT `fk_checkout_tokens_installment` FOREIGN KEY (`installment_id`) REFERENCES `order_installments`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_inst FROM @sql_inst_fk;
EXECUTE stmt_inst;
DEALLOCATE PREPARE stmt_inst;

-- ─────────────────────────────────────────────
-- 2. Adicionar campo checkout_token_id em orders
-- ─────────────────────────────────────────────
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'checkout_token_id'
);

SET @has_pay_link_col = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_link_created_at'
);

SET @sql = IF(@col_exists = 0 AND @has_pay_link_col > 0,
    'ALTER TABLE `orders` ADD COLUMN `checkout_token_id` INT NULL AFTER `payment_link_created_at`',
    IF(@col_exists = 0,
        'ALTER TABLE `orders` ADD COLUMN `checkout_token_id` INT NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK separada (pode falhar se coluna já existia com FK)
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'checkout_token_id'
      AND REFERENCED_TABLE_NAME = 'checkout_tokens'
);

SET @sql_fk = IF(@fk_exists = 0 AND @col_exists = 0,
    'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_checkout_token` FOREIGN KEY (`checkout_token_id`) REFERENCES `checkout_tokens`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- ─────────────────────────────────────────────
-- 3. Campos para auto-registro de webhook (Stripe)
--    Condicional: tabela payment_gateways pode não existir em akti_init_base
-- ─────────────────────────────────────────────
SET @has_gw_table = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payment_gateways'
);

SET @col_whid = IF(@has_gw_table > 0,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'payment_gateways'
       AND COLUMN_NAME = 'webhook_endpoint_id'),
    1
);

SET @sql2 = IF(@has_gw_table > 0 AND @col_whid = 0,
    'ALTER TABLE `payment_gateways` ADD COLUMN `webhook_endpoint_id` VARCHAR(255) NULL COMMENT ''ID do webhook endpoint registrado automaticamente (Stripe)''',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @col_auto = IF(@has_gw_table > 0,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'payment_gateways'
       AND COLUMN_NAME = 'webhook_auto_registered'),
    1
);

SET @sql3 = IF(@has_gw_table > 0 AND @col_auto = 0,
    'ALTER TABLE `payment_gateways` ADD COLUMN `webhook_auto_registered` TINYINT(1) DEFAULT 0 COMMENT ''Se webhook foi registrado automaticamente''',
    'SELECT 1'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
