-- Migration: Criar tabelas de permissoes por tenant/plano e replies de tickets no master
-- Criado em: 16/04/2026 09:28
-- Sequencial: 15

-- ============================================================
-- 1. Tabela: plan_page_permissions (permissoes padrao por plano)
-- ============================================================
CREATE TABLE IF NOT EXISTS `plan_page_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `page_key` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plan_page` (`plan_id`, `page_key`),
    INDEX `idx_plan_id` (`plan_id`),
    CONSTRAINT `fk_plan_page_perm_plan` FOREIGN KEY (`plan_id`) REFERENCES `akti_master`.`plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Tabela: tenant_page_permissions (permissoes por tenant)
-- ============================================================
CREATE TABLE IF NOT EXISTS `tenant_page_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_client_id` INT NOT NULL,
    `page_key` VARCHAR(100) NOT NULL,
    `granted_by` INT DEFAULT NULL COMMENT 'admin_id que concedeu',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_page` (`tenant_client_id`, `page_key`),
    INDEX `idx_tenant_client_id` (`tenant_client_id`),
    CONSTRAINT `fk_tenant_page_perm_client` FOREIGN KEY (`tenant_client_id`) REFERENCES `akti_master`.`tenant_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Tabela: master_ticket_replies (log de respostas do master)
-- ============================================================
CREATE TABLE IF NOT EXISTS `master_ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `tenant_client_id` INT NOT NULL,
    `ticket_id` INT NOT NULL COMMENT 'ID do ticket no banco do tenant',
    `message` TEXT NOT NULL,
    `action_type` ENUM('reply', 'status_change', 'note') NOT NULL DEFAULT 'reply',
    `old_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_tenant_ticket` (`tenant_client_id`, `ticket_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_ticket_reply_admin` FOREIGN KEY (`admin_id`) REFERENCES `akti_master`.`admin_users`(`id`),
    CONSTRAINT `fk_ticket_reply_tenant` FOREIGN KEY (`tenant_client_id`) REFERENCES `akti_master`.`tenant_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
