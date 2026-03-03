-- ==============================================================
-- Atualização: Sistema de Walkthrough / Onboarding
-- Data: 03/03/2026
-- Descrição: Adiciona tabela para controlar o estado do
--            walkthrough de cada usuário no primeiro acesso.
-- ==============================================================

-- Tabela para rastrear walkthrough do usuário
CREATE TABLE IF NOT EXISTS `user_walkthrough` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `completed` TINYINT(1) NOT NULL DEFAULT 0,
    `skipped` TINYINT(1) NOT NULL DEFAULT 0,
    `current_step` INT(11) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_walkthrough` (`user_id`),
    CONSTRAINT `fk_walkthrough_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
