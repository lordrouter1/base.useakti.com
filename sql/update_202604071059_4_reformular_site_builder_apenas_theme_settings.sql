-- Migration: Reformular site builder - apenas tabela sb_theme_settings
-- Criado em: 07/04/2026 10:59
-- Sequencial: 4
-- Nota: A migration anterior (sequencial 3) NÃO deve ser aplicada.
--       Esta migration substitui totalmente o schema do site builder.
--       Tabelas sb_pages, sb_sections e sb_components foram removidas do projeto.

-- Remover tabelas antigas se existirem (ordem correta por FK)
DROP TABLE IF EXISTS `sb_components`;
DROP TABLE IF EXISTS `sb_sections`;
DROP TABLE IF EXISTS `sb_pages`;

-- Tabela única para configurações do site builder (tema + conteúdo das páginas)
CREATE TABLE IF NOT EXISTS `sb_theme_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) DEFAULT 'general',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_key` (`tenant_id`, `setting_key`),
    INDEX `idx_tenant_group` (`tenant_id`, `setting_group`),
    CONSTRAINT `fk_sb_theme_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
