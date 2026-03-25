-- ══════════════════════════════════════════════════════════════
-- Portal do Cliente — Fase 6: Admin do Portal
-- Permissões e configurações administrativas
-- ══════════════════════════════════════════════════════════════

-- ── Configs adicionais para o admin do portal ──
INSERT IGNORE INTO `customer_portal_config` (`config_key`, `config_value`) VALUES
    ('portal_enabled', '1'),
    ('require_password', '0'),
    ('allow_self_register', '0'),
    ('allow_order_approval', '1'),
    ('magic_link_expiry_hours', '24'),
    ('session_timeout_minutes', '120');

-- ── Tabela de sessões do portal (se não existe) ──
-- Usada para multi-device tracking e forçar logout remoto
CREATE TABLE IF NOT EXISTS `customer_portal_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `access_id` INT UNSIGNED NOT NULL,
    `customer_id` INT NOT NULL,
    `session_id` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_access_sessions` (`access_id`),
    INDEX `idx_customer_sessions` (`customer_id`),
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
