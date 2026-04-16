-- Migration: Criar tabela master_login_attempts para rate limiting de login
-- Criado em: 16/04/2026 10:01
-- Sequencial: 16

CREATE TABLE IF NOT EXISTS `master_login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    INDEX `idx_ip_attempted` (`ip_address`, `attempted_at`),
    INDEX `idx_email_attempted` (`email`, `attempted_at`),
    INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
