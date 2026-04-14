-- Migration: Criar tabela ai_chat_history para o assistente IA (FEAT-031)
-- Criado em: 14/04/2025 16:16
-- Sequencial: 14

CREATE TABLE IF NOT EXISTS `ai_chat_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('user','assistant','system') NOT NULL DEFAULT 'user',
    `content` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created` (`created_at`),
    CONSTRAINT `fk_ai_chat_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
