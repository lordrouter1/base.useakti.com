-- ══════════════════════════════════════════════════════════════
-- Portal do Cliente — Fases 3, 4 e 5
-- Tabelas e colunas necessárias para:
--   Fase 3: Novo Pedido (Orçamento pelo Portal)
--   Fase 4: Financeiro + Tracking
--   Fase 5: Mensagens + Documentos
-- ══════════════════════════════════════════════════════════════

-- ── Tabela de Mensagens do Portal ──
CREATE TABLE IF NOT EXISTS `customer_portal_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `sender_type` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `sender_id` INT DEFAULT NULL COMMENT 'user_id quando admin, portal_access.id quando customer',
    `message` TEXT NOT NULL,
    `attachment_path` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_customer_messages` (`customer_id`, `created_at`),
    INDEX `idx_order_messages` (`order_id`, `created_at`),
    INDEX `idx_unread` (`customer_id`, `sender_type`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de Configurações do Portal (se não existe) ──
CREATE TABLE IF NOT EXISTS `customer_portal_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL,
    `config_value` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Configs padrão para novas funcionalidades ──
INSERT IGNORE INTO `customer_portal_config` (`config_key`, `config_value`) VALUES
    ('allow_new_order', '1'),
    ('allow_messages', '1'),
    ('allow_documents', '1'),
    ('allow_tracking', '1'),
    ('allow_financial', '1'),
    ('new_order_notes', 'Seu pedido será analisado pela nossa equipe.');

-- ── Coluna de rastreamento na tabela orders (se não existe) ──
-- Resiliente: usa IF NOT EXISTS via procedure
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `add_portal_tracking_columns`()
BEGIN
    -- tracking_code
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'orders'
          AND COLUMN_NAME = 'tracking_code'
    ) THEN
        ALTER TABLE `orders` ADD COLUMN `tracking_code` VARCHAR(100) DEFAULT NULL AFTER `quote_notes`;
    END IF;

    -- tracking_carrier
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'orders'
          AND COLUMN_NAME = 'tracking_carrier'
    ) THEN
        ALTER TABLE `orders` ADD COLUMN `tracking_carrier` VARCHAR(100) DEFAULT NULL AFTER `tracking_code`;
    END IF;

    -- tracking_url
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'orders'
          AND COLUMN_NAME = 'tracking_url'
    ) THEN
        ALTER TABLE `orders` ADD COLUMN `tracking_url` VARCHAR(500) DEFAULT NULL AFTER `tracking_carrier`;
    END IF;

    -- shipping_address
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'orders'
          AND COLUMN_NAME = 'shipping_address'
    ) THEN
        ALTER TABLE `orders` ADD COLUMN `shipping_address` TEXT DEFAULT NULL AFTER `tracking_url`;
    END IF;
END //
DELIMITER ;

CALL `add_portal_tracking_columns`();
DROP PROCEDURE IF EXISTS `add_portal_tracking_columns`;
