-- Migration: Criar tabelas support_tickets e support_ticket_messages no banco master
-- Criado em: 16/04/2026 11:27
-- Sequencial: 17
-- Descrição: Sistema de tickets de suporte separado (Master ↔ Tenant),
--            independente do módulo de tickets internos do tenant.

USE `akti_master`;

-- Tabela principal de tickets de suporte (tenant → master)
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_client_id` INT NOT NULL COMMENT 'FK para tenant_clients.id',
    `ticket_number` VARCHAR(20) NOT NULL COMMENT 'Número único do ticket (ex: SUP-00001)',
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `status` ENUM('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
    `category` VARCHAR(100) DEFAULT NULL COMMENT 'Categoria livre (bug, duvida, melhoria, financeiro...)',
    `created_by_user_id` INT DEFAULT NULL COMMENT 'ID do usuario no banco do tenant',
    `created_by_name` VARCHAR(150) NOT NULL COMMENT 'Nome do usuario que abriu o ticket',
    `created_by_email` VARCHAR(255) DEFAULT NULL,
    `assigned_admin_id` INT DEFAULT NULL COMMENT 'FK admin_users.id (admin responsavel)',
    `resolved_at` DATETIME DEFAULT NULL,
    `closed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_support_tickets_tenant` (`tenant_client_id`),
    INDEX `idx_support_tickets_status` (`status`),
    INDEX `idx_support_tickets_priority` (`priority`),
    INDEX `idx_support_tickets_number` (`ticket_number`),
    INDEX `idx_support_tickets_assigned` (`assigned_admin_id`),
    CONSTRAINT `fk_support_tickets_tenant` FOREIGN KEY (`tenant_client_id`) REFERENCES `tenant_clients`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_support_tickets_admin` FOREIGN KEY (`assigned_admin_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagens/respostas dos tickets de suporte
CREATE TABLE IF NOT EXISTS `support_ticket_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `support_ticket_id` INT NOT NULL,
    `sender_type` ENUM('tenant','admin') NOT NULL COMMENT 'Quem enviou: tenant user ou admin master',
    `sender_id` INT DEFAULT NULL COMMENT 'ID do remetente (user_id do tenant ou admin_id)',
    `sender_name` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `is_internal_note` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Nota interna visivel apenas no master',
    `attachments` JSON DEFAULT NULL COMMENT 'Lista de anexos (URLs)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_support_messages_ticket` (`support_ticket_id`),
    INDEX `idx_support_messages_sender` (`sender_type`, `sender_id`),
    CONSTRAINT `fk_support_messages_ticket` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
