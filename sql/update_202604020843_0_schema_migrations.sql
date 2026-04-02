-- Migration: Tabela de controle de migrations executadas
-- Criado em: 02/04/2026 08:43
-- Sequencial: 0

CREATE TABLE IF NOT EXISTS `applied_migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `checksum` VARCHAR(64) DEFAULT NULL,
    UNIQUE KEY `uk_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
