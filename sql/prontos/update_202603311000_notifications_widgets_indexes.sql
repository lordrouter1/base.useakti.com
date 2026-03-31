-- ═══════════════════════════════════════════════════════════════
-- Migration: Notifications (Phase 6.1)
-- Tabela de notificações em tempo real
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Destinatário',
    `type` ENUM('order_delayed','payment_received','stock_low','new_order','system','custom') NOT NULL DEFAULT 'system',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NULL,
    `data` JSON NULL COMMENT 'Metadata: order_id, amount, etc',
    `read_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notifications_user` (`user_id`, `read_at`),
    INDEX `idx_notifications_tenant` (`tenant_id`, `created_at`),
    INDEX `idx_notifications_type` (`type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════
-- Migration: Dashboard Widgets (Phase 6.2)
-- Configuração de widgets por usuário
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `dashboard_widgets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL,
    `widget_key` VARCHAR(50) NOT NULL COMMENT 'Ex: revenue_chart, orders_status, pipeline_summary',
    `position` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ordem de exibição',
    `col_span` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Largura em colunas (1-4)',
    `config` JSON NULL COMMENT 'Configurações do widget (período, filtros)',
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_dw_user_widget` (`user_id`, `widget_key`),
    INDEX `idx_dw_tenant` (`tenant_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════
-- Performance indexes (Phase 4.3)
-- ═══════════════════════════════════════════════════════════════

-- Orders
CREATE INDEX IF NOT EXISTS `idx_orders_pipeline_status` ON `orders` (`pipeline_stage`, `status`);
CREATE INDEX IF NOT EXISTS `idx_orders_customer` ON `orders` (`customer_id`);
CREATE INDEX IF NOT EXISTS `idx_orders_created` ON `orders` (`created_at`);

-- Customers
CREATE INDEX IF NOT EXISTS `idx_customers_name` ON `customers` (`name`(100));
CREATE INDEX IF NOT EXISTS `idx_customers_document` ON `customers` (`document`(20));

-- System logs
CREATE INDEX IF NOT EXISTS `idx_system_logs_created` ON `system_logs` (`created_at`);
