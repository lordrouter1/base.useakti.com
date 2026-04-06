-- Migration: Criar tabela file_assets para gestão centralizada de arquivos
-- Criado em: 06/04/2026 08:23
-- Sequencial: 0

-- Tabela para rastrear todos os arquivos gerenciados pelo FileManager
-- Preparada para futura integração com Cloudflare R2

CREATE TABLE IF NOT EXISTS `file_assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `disk` VARCHAR(20) NOT NULL DEFAULT 'local' COMMENT 'local, r2, s3',
    `module` VARCHAR(50) NOT NULL COMMENT 'products, customers, attachments, etc.',
    `entity_type` VARCHAR(50) DEFAULT NULL COMMENT 'Tipo de entidade vinculada',
    `entity_id` INT DEFAULT NULL COMMENT 'ID da entidade vinculada',
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(500) NOT NULL COMMENT 'Caminho relativo do arquivo',
    `mime_type` VARCHAR(100) NOT NULL,
    `size` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Tamanho em bytes',
    `is_image` TINYINT(1) NOT NULL DEFAULT 0,
    `image_width` INT UNSIGNED DEFAULT NULL,
    `image_height` INT UNSIGNED DEFAULT NULL,
    `has_thumbnail` TINYINT(1) NOT NULL DEFAULT 0,
    `thumbnail_path` VARCHAR(500) DEFAULT NULL,
    `external_url` VARCHAR(1000) DEFAULT NULL COMMENT 'URL externa (R2/S3)',
    `external_key` VARCHAR(500) DEFAULT NULL COMMENT 'Key no storage externo',
    `metadata` JSON DEFAULT NULL COMMENT 'Metadados adicionais',
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_file_assets_tenant` (`tenant_id`),
    INDEX `idx_file_assets_module` (`module`),
    INDEX `idx_file_assets_entity` (`entity_type`, `entity_id`),
    INDEX `idx_file_assets_disk` (`disk`),
    INDEX `idx_file_assets_path` (`path`(191)),
    INDEX `idx_file_assets_deleted` (`deleted_at`),
    CONSTRAINT `fk_file_assets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
