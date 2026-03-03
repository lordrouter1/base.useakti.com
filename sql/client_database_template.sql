-- ==============================================================
-- Template de banco de dados padrão para novos clientes Akti
-- Este arquivo é executado automaticamente ao criar um novo tenant.
--
-- IMPORTANTE: Deve estar 100% compatível com a estrutura real
-- utilizada pelo sistema (base: akti_teste.sql / database.sql).
--
-- Atualizado em: 02/03/2026
-- ==============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: USUÁRIOS E PERMISSÕES
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `user_groups` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','funcionario') DEFAULT 'funcionario',
    `group_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `fk_user_group` (`group_id`),
    CONSTRAINT `fk_user_group` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `group_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `group_id` INT(11) NOT NULL,
    `page_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `group_id` (`group_id`),
    CONSTRAINT `group_permissions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: CONFIGURAÇÕES DA EMPRESA
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `company_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: TABELAS DE PREÇO
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `price_tables` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: CLIENTES
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `document` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `photo` VARCHAR(255) DEFAULT NULL,
    `price_table_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `price_table_id` (`price_table_id`),
    CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`price_table_id`) REFERENCES `price_tables` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PRODUTOS
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `subcategories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `subcategory_id` INT(11) DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `stock_quantity` INT(11) DEFAULT 0,
    `use_stock_control` TINYINT(1) DEFAULT 0 COMMENT 'Se ativado e houver estoque, pedido não vai para produção',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fiscal_ncm` VARCHAR(10) DEFAULT NULL COMMENT 'NCM - Nomenclatura Comum do Mercosul',
    `fiscal_cest` VARCHAR(10) DEFAULT NULL COMMENT 'CEST - Código Especificador da Substituição Tributária',
    `fiscal_cfop` VARCHAR(10) DEFAULT NULL COMMENT 'CFOP - Código Fiscal de Operações e Prestações',
    `fiscal_cst_icms` VARCHAR(5) DEFAULT NULL COMMENT 'CST ICMS',
    `fiscal_csosn` VARCHAR(5) DEFAULT NULL COMMENT 'CSOSN - Simples Nacional',
    `fiscal_cst_pis` VARCHAR(5) DEFAULT NULL COMMENT 'CST PIS',
    `fiscal_cst_cofins` VARCHAR(5) DEFAULT NULL COMMENT 'CST COFINS',
    `fiscal_cst_ipi` VARCHAR(5) DEFAULT NULL COMMENT 'CST IPI',
    `fiscal_origem` VARCHAR(2) DEFAULT '0' COMMENT 'Origem da mercadoria',
    `fiscal_unidade` VARCHAR(10) DEFAULT 'UN' COMMENT 'Unidade de medida fiscal',
    `fiscal_ean` VARCHAR(14) DEFAULT NULL COMMENT 'Código EAN/GTIN',
    `fiscal_aliq_icms` DECIMAL(5,2) DEFAULT NULL COMMENT 'Alíquota ICMS (%)',
    `fiscal_aliq_ipi` DECIMAL(5,2) DEFAULT NULL COMMENT 'Alíquota IPI (%)',
    `fiscal_aliq_pis` DECIMAL(5,4) DEFAULT NULL COMMENT 'Alíquota PIS (%)',
    `fiscal_aliq_cofins` DECIMAL(5,4) DEFAULT NULL COMMENT 'Alíquota COFINS (%)',
    `fiscal_beneficio` VARCHAR(20) DEFAULT NULL COMMENT 'Código de benefício fiscal',
    `fiscal_info_adicional` TEXT DEFAULT NULL COMMENT 'Informações adicionais do produto para a NF-e',
    PRIMARY KEY (`id`),
    KEY `fk_product_category` (`category_id`),
    KEY `fk_product_subcategory` (`subcategory_id`),
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_product_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `is_main` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: GRADES / VARIAÇÕES DE PRODUTOS
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_grade_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'fas fa-th',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `product_grades` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `grade_type_id` INT(11) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_product_grade` (`product_id`,`grade_type_id`),
    KEY `grade_type_id` (`grade_type_id`),
    CONSTRAINT `product_grades_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_grades_ibfk_2` FOREIGN KEY (`grade_type_id`) REFERENCES `product_grade_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `product_grade_values` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_grade_id` INT(11) NOT NULL,
    `value` VARCHAR(100) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_grade_id` (`product_grade_id`),
    CONSTRAINT `product_grade_values_ibfk_1` FOREIGN KEY (`product_grade_id`) REFERENCES `product_grades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `product_grade_combinations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `combination_key` VARCHAR(255) NOT NULL COMMENT 'Chave serializada ex: "2:5|3:8" (grade_id:value_id)',
    `combination_label` VARCHAR(500) DEFAULT NULL COMMENT 'Label legível ex: "M / Branca"',
    `sku` VARCHAR(100) DEFAULT NULL,
    `price_override` DECIMAL(10,2) DEFAULT NULL COMMENT 'Preço específico da combinação (NULL = usa preço do produto)',
    `stock_quantity` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_product_combination` (`product_id`,`combination_key`),
    CONSTRAINT `product_grade_combinations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Itens das Tabelas de Preço
CREATE TABLE IF NOT EXISTS `price_table_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `price_table_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_table_product` (`price_table_id`,`product_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `price_table_items_ibfk_1` FOREIGN KEY (`price_table_id`) REFERENCES `price_tables` (`id`) ON DELETE CASCADE,
    CONSTRAINT `price_table_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PEDIDOS
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `price_table_id` INT(11) DEFAULT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('orcamento','pendente','Pendente','aprovado','em_producao','concluido','cancelado') DEFAULT 'orcamento',
    `pipeline_stage` ENUM('contato','orcamento','venda','producao','preparacao','envio','financeiro','concluido') DEFAULT 'contato',
    `pipeline_entered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `deadline` DATE DEFAULT NULL,
    `priority` ENUM('baixa','normal','alta','urgente') DEFAULT 'normal',
    `internal_notes` TEXT DEFAULT NULL,
    `quote_notes` TEXT DEFAULT NULL,
    `scheduled_date` DATE DEFAULT NULL,
    `assigned_to` INT(11) DEFAULT NULL,
    `payment_status` ENUM('pendente','parcial','pago') DEFAULT 'pendente',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `installments` INT(11) DEFAULT NULL,
    `installment_value` DECIMAL(10,2) DEFAULT NULL,
    `discount` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_type` ENUM('retirada','entrega','correios') DEFAULT 'retirada',
    `shipping_address` TEXT DEFAULT NULL,
    `tracking_code` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `grade_combination_id` INT(11) DEFAULT NULL,
    `grade_description` VARCHAR(500) DEFAULT NULL,
    `quantity` INT(11) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_extra_costs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `order_extra_costs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_item_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `order_item_id` INT(11) NOT NULL,
    `user_id` INT(11) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_name` VARCHAR(255) DEFAULT NULL,
    `file_type` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_order_item_id` (`order_item_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Catálogo público (links para cliente visualizar pedido/orçamento)
CREATE TABLE IF NOT EXISTS `catalog_links` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `show_prices` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `expires_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `catalog_links_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PIPELINE DE PRODUÇÃO
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `pipeline_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `from_stage` VARCHAR(30) DEFAULT NULL,
    `to_stage` VARCHAR(30) NOT NULL,
    `changed_by` INT(11) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `changed_by` (`changed_by`),
    CONSTRAINT `pipeline_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `pipeline_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pipeline_stage_goals` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stage` VARCHAR(30) NOT NULL,
    `stage_label` VARCHAR(50) NOT NULL,
    `max_hours` INT(11) NOT NULL DEFAULT 24,
    `stage_order` INT(11) NOT NULL DEFAULT 0,
    `color` VARCHAR(20) DEFAULT '#3498db',
    `icon` VARCHAR(50) DEFAULT 'fas fa-circle',
    `is_active` TINYINT(1) DEFAULT 1,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: SETORES DE PRODUÇÃO
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `production_sectors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'fas fa-cogs',
    `color` VARCHAR(20) DEFAULT '#6c757d',
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `product_sectors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `sector_id` INT(11) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_product_sector` (`product_id`,`sector_id`),
    KEY `sector_id` (`sector_id`),
    CONSTRAINT `product_sectors_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_sectors_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `production_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `category_sectors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `sector_id` INT(11) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_cat_sector` (`category_id`,`sector_id`),
    KEY `sector_id` (`sector_id`),
    CONSTRAINT `category_sectors_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `category_sectors_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `production_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `subcategory_sectors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `subcategory_id` INT(11) NOT NULL,
    `sector_id` INT(11) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sub_sector` (`subcategory_id`,`sector_id`),
    KEY `sector_id` (`sector_id`),
    CONSTRAINT `subcategory_sectors_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `subcategory_sectors_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `production_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Setores de produção por item do pedido
CREATE TABLE IF NOT EXISTS `order_production_sectors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `order_item_id` INT(11) NOT NULL,
    `sector_id` INT(11) NOT NULL,
    `status` ENUM('pendente','em_andamento','concluido') DEFAULT 'pendente',
    `sort_order` INT(11) DEFAULT 0,
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `completed_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_item_sector` (`order_item_id`,`sector_id`),
    KEY `order_id` (`order_id`),
    KEY `sector_id` (`sector_id`),
    CONSTRAINT `order_production_sectors_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_production_sectors_ibfk_2` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_production_sectors_ibfk_3` FOREIGN KEY (`sector_id`) REFERENCES `production_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PREPARAÇÃO DE PEDIDOS (checklist)
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `preparation_steps` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `step_key` VARCHAR(100) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `description` VARCHAR(500) DEFAULT '',
    `icon` VARCHAR(100) DEFAULT 'fas fa-check',
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `step_key` (`step_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_preparation_checklist` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `check_key` VARCHAR(100) NOT NULL,
    `checked` TINYINT(1) DEFAULT 0,
    `checked_by` INT(11) DEFAULT NULL,
    `checked_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_order_key` (`order_id`,`check_key`),
    KEY `idx_order_id` (`order_id`),
    CONSTRAINT `order_preparation_checklist_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: GRADES DE CATEGORIAS
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `category_grades` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `grade_type_id` INT(11) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_category_grade` (`category_id`,`grade_type_id`),
    KEY `grade_type_id` (`grade_type_id`),
    CONSTRAINT `category_grades_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `category_grades_ibfk_2` FOREIGN KEY (`grade_type_id`) REFERENCES `product_grade_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `category_grade_values` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_grade_id` INT(11) NOT NULL,
    `value` VARCHAR(100) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_grade_id` (`category_grade_id`),
    CONSTRAINT `category_grade_values_ibfk_1` FOREIGN KEY (`category_grade_id`) REFERENCES `category_grades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `category_grade_combinations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `combination_key` VARCHAR(255) NOT NULL,
    `combination_label` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_category_combination` (`category_id`,`combination_key`),
    CONSTRAINT `category_grade_combinations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: GRADES DE SUBCATEGORIAS
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `subcategory_grades` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `subcategory_id` INT(11) NOT NULL,
    `grade_type_id` INT(11) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_subcategory_grade` (`subcategory_id`,`grade_type_id`),
    KEY `grade_type_id` (`grade_type_id`),
    CONSTRAINT `subcategory_grades_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `subcategory_grades_ibfk_2` FOREIGN KEY (`grade_type_id`) REFERENCES `product_grade_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `subcategory_grade_values` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `subcategory_grade_id` INT(11) NOT NULL,
    `value` VARCHAR(100) NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `subcategory_grade_id` (`subcategory_grade_id`),
    CONSTRAINT `subcategory_grade_values_ibfk_1` FOREIGN KEY (`subcategory_grade_id`) REFERENCES `subcategory_grades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `subcategory_grade_combinations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `subcategory_id` INT(11) NOT NULL,
    `combination_key` VARCHAR(255) NOT NULL,
    `combination_label` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_subcategory_combination` (`subcategory_id`,`combination_key`),
    CONSTRAINT `subcategory_grade_combinations_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: ESTOQUE (ARMAZÉNS)
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `warehouses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `address` VARCHAR(500) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(2) DEFAULT NULL,
    `zip_code` VARCHAR(10) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `stock_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `warehouse_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `combination_id` INT(11) DEFAULT NULL COMMENT 'NULL = produto sem variação',
    `quantity` DECIMAL(12,2) DEFAULT 0.00,
    `min_quantity` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Estoque mínimo para alerta',
    `location_code` VARCHAR(50) DEFAULT NULL COMMENT 'Localização física (ex: A1-P3)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_warehouse_product_combo` (`warehouse_id`,`product_id`,`combination_id`),
    KEY `product_id` (`product_id`),
    KEY `combination_id` (`combination_id`),
    CONSTRAINT `stock_items_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `stock_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `stock_items_ibfk_3` FOREIGN KEY (`combination_id`) REFERENCES `product_grade_combinations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stock_item_id` INT(11) NOT NULL,
    `warehouse_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `combination_id` INT(11) DEFAULT NULL,
    `type` ENUM('entrada','saida','ajuste','transferencia') NOT NULL DEFAULT 'entrada',
    `quantity` DECIMAL(12,2) NOT NULL,
    `quantity_before` DECIMAL(12,2) DEFAULT 0.00,
    `quantity_after` DECIMAL(12,2) DEFAULT 0.00,
    `reason` VARCHAR(255) DEFAULT NULL COMMENT 'Motivo/observação da movimentação',
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'order, manual, adjustment, transfer',
    `reference_id` INT(11) DEFAULT NULL COMMENT 'ID do pedido ou outra referência',
    `destination_warehouse_id` INT(11) DEFAULT NULL COMMENT 'Para transferências entre armazéns',
    `user_id` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `stock_item_id` (`stock_item_id`),
    KEY `user_id` (`user_id`),
    KEY `idx_stock_mov_product` (`product_id`),
    KEY `idx_stock_mov_warehouse` (`warehouse_id`),
    KEY `idx_stock_mov_created` (`created_at`),
    KEY `idx_stock_mov_type` (`type`),
    CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`stock_item_id`) REFERENCES `stock_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `stock_movements_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────
-- MÓDULO: WALKTHROUGH / ONBOARDING
-- ─────────────────────────────────────────────────────

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

-- ─────────────────────────────────────────────────────
-- MÓDULO: LOGS DO SISTEMA
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `system_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ═══════════════════════════════════════════════════════════════
-- DADOS INICIAIS (SEED) — Necessários para o funcionamento
-- ═══════════════════════════════════════════════════════════════

-- Grupo admin padrão
INSERT INTO `user_groups` (`id`, `name`, `description`) VALUES
(1, 'Administradores', 'Acesso total ao sistema');

-- Permissões completas para o grupo admin
INSERT INTO `group_permissions` (`group_id`, `page_name`) VALUES
(1, 'dashboard'),
(1, 'orders'),
(1, 'pipeline'),
(1, 'customers'),
(1, 'products'),
(1, 'categories'),
(1, 'stock'),
(1, 'settings'),
(1, 'users'),
(1, 'sectors'),
(1, 'reports');

-- Usuário admin padrão (senha: admin123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `group_id`) VALUES
(1, 'Administrador', 'admin@sistema.com', '$2y$10$mZj.im9qNZhpe0usv10cU.2sfcOrmk8rY8PXyjJWN7F.GOaTMIJlK', 'admin', 1);

-- Tabela de preço padrão
INSERT INTO `price_tables` (`id`, `name`, `description`, `is_default`) VALUES
(1, 'Tabela Padrão', 'Tabela de preços padrão do sistema', 1);

-- Metas padrão do pipeline
INSERT INTO `pipeline_stage_goals` (`stage`, `stage_label`, `max_hours`, `stage_order`, `color`, `icon`) VALUES
('contato',    'Contato',       24,  1, '#9b59b6', 'fas fa-phone'),
('orcamento',  'Orçamento',     48,  2, '#3498db', 'fas fa-file-invoice-dollar'),
('venda',      'Venda',         24,  3, '#2ecc71', 'fas fa-handshake'),
('producao',   'Produção',      72,  4, '#e67e22', 'fas fa-industry'),
('preparacao', 'Preparação',    24,  5, '#1abc9c', 'fas fa-boxes-packing'),
('envio',      'Envio/Entrega', 48,  6, '#e74c3c', 'fas fa-truck'),
('financeiro', 'Financeiro',    48,  7, '#f39c12', 'fas fa-coins'),
('concluido',  'Concluído',      0,  8, '#27ae60', 'fas fa-check-double');

-- Tipos de grade comuns
INSERT INTO `product_grade_types` (`name`, `description`, `icon`) VALUES
('Tamanho', 'Variações de tamanho do produto (P, M, G, GG, etc.)', 'fas fa-ruler-combined'),
('Cor', 'Variações de cor do produto', 'fas fa-palette'),
('Material', 'Tipo de material ou papel utilizado', 'fas fa-layer-group'),
('Acabamento', 'Tipo de acabamento (laminação, verniz, etc.)', 'fas fa-magic'),
('Gramatura', 'Gramatura do papel (90g, 150g, 300g, etc.)', 'fas fa-weight-hanging'),
('Formato', 'Formato ou dimensão do produto', 'fas fa-expand-arrows-alt'),
('Quantidade', 'Faixas de quantidade (100un, 500un, 1000un)', 'fas fa-boxes');

-- Etapas padrão de preparação
INSERT INTO `preparation_steps` (`step_key`, `label`, `description`, `icon`, `sort_order`, `is_active`) VALUES
('revisao_arquivos', 'Revisão de Arquivos', 'Revisar todos os arquivos antes de iniciar', 'fas fa-file-alt', 0, 1),
('corte_acabamento', 'Revisão e Acabamento', 'Realizar corte, dobra e acabamento dos materiais', 'fas fa-cut', 1, 1),
('embalagem', 'Embalagem', 'Embalar os produtos para envio/retirada', 'fas fa-box', 2, 1),
('conferencia_qtd', 'Conferência de Quantidade', 'Verificar se a quantidade confere com o pedido', 'fas fa-list-check', 3, 1),
('conferencia_qual', 'Conferência de Qualidade', 'Inspecionar qualidade final de todos os itens', 'fas fa-search', 4, 1),
('pronto_envio', 'Pronto para Envio', 'Confirmar que o pedido está 100% pronto para envio', 'fas fa-truck-loading', 5, 1);

-- Armazém padrão
INSERT INTO `warehouses` (`id`, `name`, `address`, `notes`, `is_active`) VALUES
(1, 'Estoque Principal', 'Endereço da sede', 'Armazém principal da empresa', 1);

-- Configurações padrão da empresa
INSERT INTO `company_settings` (`setting_key`, `setting_value`) VALUES
('company_name', ''),
('company_document', ''),
('company_phone', ''),
('company_email', ''),
('company_website', ''),
('company_zipcode', ''),
('company_address_type', 'Rua'),
('company_address_name', ''),
('company_address_number', ''),
('company_neighborhood', ''),
('company_complement', ''),
('company_city', ''),
('company_state', ''),
('company_logo', ''),
('quote_validity_days', '15'),
('quote_footer_note', ''),
('boleto_banco', ''),
('boleto_agencia', ''),
('boleto_agencia_dv', ''),
('boleto_conta', ''),
('boleto_conta_dv', ''),
('boleto_carteira', ''),
('boleto_especie', 'R$'),
('boleto_cedente', ''),
('boleto_cedente_documento', ''),
('boleto_convenio', ''),
('boleto_nosso_numero', '1'),
('boleto_nosso_numero_digitos', '7'),
('boleto_instrucoes', ''),
('boleto_multa', '2.00'),
('boleto_juros', '1.00'),
('boleto_aceite', 'S'),
('boleto_especie_doc', 'DM'),
('boleto_demonstrativo', ''),
('boleto_local_pagamento', 'Pagável em qualquer banco até o vencimento'),
('boleto_cedente_endereco', ''),
('fiscal_razao_social', ''),
('fiscal_nome_fantasia', ''),
('fiscal_cnpj', ''),
('fiscal_ie', ''),
('fiscal_im', ''),
('fiscal_cnae', ''),
('fiscal_crt', '1'),
('fiscal_endereco_logradouro', ''),
('fiscal_endereco_numero', ''),
('fiscal_endereco_complemento', ''),
('fiscal_endereco_bairro', ''),
('fiscal_endereco_cidade', ''),
('fiscal_endereco_uf', ''),
('fiscal_endereco_cep', ''),
('fiscal_endereco_cod_municipio', ''),
('fiscal_endereco_cod_pais', '1058'),
('fiscal_endereco_pais', 'Brasil'),
('fiscal_endereco_fone', ''),
('fiscal_certificado_tipo', 'A1'),
('fiscal_certificado_senha', ''),
('fiscal_certificado_validade', ''),
('fiscal_ambiente', '2'),
('fiscal_serie_nfe', '1'),
('fiscal_proximo_numero_nfe', '1'),
('fiscal_modelo_nfe', '55'),
('fiscal_tipo_emissao', '1'),
('fiscal_finalidade', '1'),
('fiscal_aliq_icms_padrao', ''),
('fiscal_aliq_pis_padrao', '0.65'),
('fiscal_aliq_cofins_padrao', '3.00'),
('fiscal_aliq_iss_padrao', ''),
('fiscal_nat_operacao', 'Venda de mercadoria'),
('fiscal_info_complementar', '');
