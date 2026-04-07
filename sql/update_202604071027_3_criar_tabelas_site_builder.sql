-- Migration: Criar tabelas do Site Builder (sb_pages, sb_sections, sb_components, sb_theme_settings)
-- Criado em: 07/04/2026 10:27
-- Sequencial: 3

-- ─── sb_pages ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sb_pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `type` ENUM('home','about','products','services','contact','blog','custom') DEFAULT 'custom',
    `meta_title` VARCHAR(200) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_slug` (`tenant_id`, `slug`),
    INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
    CONSTRAINT `fk_sb_pages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── sb_sections ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sb_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `page_id` INT NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `settings` JSON DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_visible` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_page_sort` (`page_id`, `sort_order`),
    CONSTRAINT `fk_sb_sections_page` FOREIGN KEY (`page_id`) REFERENCES `sb_pages`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sb_sections_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── sb_components ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sb_components` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `section_id` INT NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `content` JSON DEFAULT NULL,
    `grid_col` INT DEFAULT 12,
    `grid_row` INT DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_section_sort` (`section_id`, `sort_order`),
    CONSTRAINT `fk_sb_components_section` FOREIGN KEY (`section_id`) REFERENCES `sb_sections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sb_components_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── sb_theme_settings ────────────────────────────────────────────────
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
