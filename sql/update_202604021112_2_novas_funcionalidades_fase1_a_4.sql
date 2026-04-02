-- Migration: Novas funcionalidades — Fase 1 a 4 (FEAT-001 a FEAT-018)
-- Criado em: 02/04/2026 11:12
-- Sequencial: 2

-- ══════════════════════════════════════════════════════════════
-- FEAT-002: RBAC Granular v2 — Permissões por ação
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `page` VARCHAR(100) NOT NULL,
    `action` VARCHAR(50) NOT NULL DEFAULT '*',
    `label` VARCHAR(200) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tenant_page_action` (`tenant_id`, `page`, `action`),
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_permissions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `group_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    `can_view` TINYINT(1) NOT NULL DEFAULT 1,
    `can_create` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_group_permission` (`group_id`, `permission_id`),
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_gp_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_gp_group` FOREIGN KEY (`group_id`) REFERENCES `user_groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_gp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-003: Sistema de Anexos/Documentos
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'order, customer, nfe, supplier, quote',
    `entity_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `size` INT UNSIGNED NOT NULL DEFAULT 0,
    `uploaded_by` INT NULL,
    `description` VARCHAR(500) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_uploaded_by` (`uploaded_by`),
    CONSTRAINT `fk_attachments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-004: Audit Log Completo
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `user_id` INT NULL,
    `user_name` VARCHAR(100) NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'create, update, delete, login, export, print',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'order, customer, product, nfe, user, etc.',
    `entity_id` INT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `description` VARCHAR(500) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`),
    CONSTRAINT `fk_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-005: Módulo de Compras / Fornecedores
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `company_name` VARCHAR(200) NOT NULL,
    `trade_name` VARCHAR(200) NULL,
    `document` VARCHAR(20) NULL COMMENT 'CNPJ ou CPF',
    `state_registration` VARCHAR(20) NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(20) NULL,
    `website` VARCHAR(255) NULL,
    `contact_name` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `address_number` VARCHAR(20) NULL,
    `complement` VARCHAR(100) NULL,
    `neighborhood` VARCHAR(100) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(2) NULL,
    `zip_code` VARCHAR(10) NULL,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_document` (`document`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_suppliers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `user_id` INT NULL COMMENT 'Quem criou',
    `code` VARCHAR(30) NULL COMMENT 'Codigo da OC',
    `status` ENUM('draft', 'sent', 'confirmed', 'partial', 'received', 'cancelled') NOT NULL DEFAULT 'draft',
    `expected_date` DATE NULL,
    `received_date` DATE NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `shipping` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_terms` VARCHAR(200) NULL,
    `notes` TEXT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_po_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `purchase_order_id` INT NOT NULL,
    `product_id` INT NULL,
    `description` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    `unit_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `received_qty` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_po` (`purchase_order_id`),
    INDEX `idx_product` (`product_id`),
    CONSTRAINT `fk_pi_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_pi_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-006: Módulo de Orçamentos Avançados
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `quotes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `user_id` INT NULL COMMENT 'Vendedor',
    `code` VARCHAR(30) NULL,
    `version` INT NOT NULL DEFAULT 1,
    `status` ENUM('draft', 'sent', 'approved', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'draft',
    `valid_until` DATE NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `internal_notes` TEXT NULL,
    `approval_token` VARCHAR(64) NULL COMMENT 'Token para aprovacao via portal',
    `approved_at` DATETIME NULL,
    `converted_order_id` INT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_token` (`approval_token`),
    CONSTRAINT `fk_quotes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `quote_id` INT NOT NULL,
    `product_id` INT NULL,
    `description` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    `unit_price` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_quote` (`quote_id`),
    CONSTRAINT `fk_qi_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_qi_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_versions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `quote_id` INT NOT NULL,
    `version` INT NOT NULL,
    `snapshot` JSON NOT NULL COMMENT 'JSON completo do orcamento nessa versao',
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_quote` (`quote_id`),
    CONSTRAINT `fk_qv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_qv_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-007: Agenda/Calendário Integrado
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `calendar_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `user_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `type` ENUM('manual', 'delivery', 'due_date', 'follow_up', 'sla', 'meeting') NOT NULL DEFAULT 'manual',
    `entity_type` VARCHAR(50) NULL COMMENT 'order, installment, customer, quote',
    `entity_id` INT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NULL,
    `all_day` TINYINT(1) NOT NULL DEFAULT 0,
    `color` VARCHAR(7) NULL COMMENT '#hex color',
    `reminder_minutes` INT NULL COMMENT 'Minutos antes para lembrar',
    `completed` TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_calendar_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-008: Relatórios Customizáveis
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `report_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `user_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `entity` VARCHAR(50) NOT NULL COMMENT 'orders, customers, products, financial',
    `columns` JSON NOT NULL COMMENT 'Array de colunas selecionadas',
    `filters` JSON NULL COMMENT 'Filtros aplicados',
    `grouping` JSON NULL COMMENT 'Agrupamento',
    `sorting` JSON NULL COMMENT 'Ordenacao',
    `is_shared` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Visivel para outros usuarios',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_entity` (`entity`),
    CONSTRAINT `fk_rt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-010: Automação de Workflow
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `workflow_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `event` VARCHAR(100) NOT NULL COMMENT 'model.order.created, model.installment.overdue, etc.',
    `conditions` JSON NOT NULL COMMENT 'Array de condicoes [{field, operator, value}]',
    `actions` JSON NOT NULL COMMENT 'Array de acoes [{type, params}]',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `priority` INT NOT NULL DEFAULT 0,
    `last_triggered_at` DATETIME NULL,
    `trigger_count` INT NOT NULL DEFAULT 0,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_event` (`event`),
    INDEX `idx_active` (`is_active`),
    CONSTRAINT `fk_wr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `workflow_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `rule_id` INT NOT NULL,
    `event` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NULL,
    `entity_id` INT NULL,
    `conditions_met` JSON NULL,
    `actions_executed` JSON NULL,
    `status` ENUM('success', 'partial', 'failed') NOT NULL DEFAULT 'success',
    `error_message` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_rule` (`rule_id`),
    INDEX `idx_created` (`created_at`),
    CONSTRAINT `fk_wl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_wl_rule` FOREIGN KEY (`rule_id`) REFERENCES `workflow_rules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-013: Email Marketing / CRM
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `body_html` LONGTEXT NOT NULL,
    `body_text` TEXT NULL,
    `variables` JSON NULL COMMENT 'Variaveis disponiveis: {{nome}}, {{empresa}}, etc.',
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_et_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `template_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `body_html` LONGTEXT NOT NULL,
    `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') NOT NULL DEFAULT 'draft',
    `scheduled_at` DATETIME NULL,
    `sent_at` DATETIME NULL,
    `total_recipients` INT NOT NULL DEFAULT 0,
    `total_sent` INT NOT NULL DEFAULT 0,
    `total_opened` INT NOT NULL DEFAULT 0,
    `total_clicked` INT NOT NULL DEFAULT 0,
    `segment_filters` JSON NULL COMMENT 'Filtros de segmentacao de clientes',
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_ec_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `campaign_id` INT NULL,
    `recipient_email` VARCHAR(150) NOT NULL,
    `recipient_name` VARCHAR(100) NULL,
    `customer_id` INT NULL,
    `status` ENUM('queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'unsubscribed') NOT NULL DEFAULT 'queued',
    `opened_at` DATETIME NULL,
    `clicked_at` DATETIME NULL,
    `bounced_at` DATETIME NULL,
    `error_message` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_campaign` (`campaign_id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_el_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_el_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-017: Módulo de Qualidade
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `quality_checklists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `pipeline_stage_id` INT NULL COMMENT 'Etapa do pipeline vinculada',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_qc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality_checklist_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `checklist_id` INT NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `required` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_checklist` (`checklist_id`),
    CONSTRAINT `fk_qci_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_qci_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `quality_checklists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality_inspections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `checklist_id` INT NOT NULL,
    `order_id` INT NULL,
    `inspector_id` INT NULL,
    `status` ENUM('pending', 'passed', 'failed', 'partial') NOT NULL DEFAULT 'pending',
    `results` JSON NULL COMMENT '[{item_id, passed, notes}]',
    `notes` TEXT NULL,
    `inspected_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_qi2_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_qi2_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `quality_checklists`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality_nonconformities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `inspection_id` INT NULL,
    `order_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `corrective_action` TEXT NULL,
    `responsible_id` INT NULL,
    `resolved_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_severity` (`severity`),
    CONSTRAINT `fk_qn_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
