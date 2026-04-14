-- Migration: Criar tabelas para módulos v3 (FEAT-024 a FEAT-034)
-- Criado em: 14/04/2026 15:41
-- Sequencial: 13
-- Módulos: Tickets, Manutenção, Custos de Produção, BI, WhatsApp, Multi-filial, Entregas, Gamificação, ESG

-- ══════════════════════════════════════════════════════════════
-- FEAT-024: Módulo de Tickets / Help Desk
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `ticket_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `sla_response_hours` INT NULL DEFAULT 24,
    `sla_resolution_hours` INT NULL DEFAULT 72,
    `color` VARCHAR(7) NULL DEFAULT '#6c757d',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_ticket_categories_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `ticket_number` VARCHAR(20) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category_id` INT NULL,
    `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `status` ENUM('open','in_progress','waiting_customer','waiting_internal','resolved','closed') NOT NULL DEFAULT 'open',
    `source` ENUM('internal','portal','email') NOT NULL DEFAULT 'internal',
    `customer_id` INT NULL,
    `assigned_to` INT NULL,
    `created_by` INT NULL,
    `sla_response_due` DATETIME NULL,
    `sla_resolution_due` DATETIME NULL,
    `first_response_at` DATETIME NULL,
    `resolved_at` DATETIME NULL,
    `closed_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_assigned` (`assigned_to`),
    INDEX `idx_category` (`category_id`),
    UNIQUE INDEX `idx_ticket_number` (`tenant_id`, `ticket_number`),
    CONSTRAINT `fk_tickets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tickets_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tickets_created` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `ticket_id` INT NOT NULL,
    `user_id` INT NULL,
    `customer_id` INT NULL,
    `message` TEXT NOT NULL,
    `is_internal_note` TINYINT(1) NOT NULL DEFAULT 0,
    `attachments` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_ticket` (`ticket_id`),
    CONSTRAINT `fk_ticket_messages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-025: Módulo de Manutenção Preventiva
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `equipment` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) NULL,
    `model` VARCHAR(100) NULL,
    `manufacturer` VARCHAR(100) NULL,
    `serial_number` VARCHAR(100) NULL,
    `location` VARCHAR(150) NULL,
    `sector_id` INT NULL,
    `status` ENUM('active','inactive','maintenance','decommissioned') NOT NULL DEFAULT 'active',
    `purchase_date` DATE NULL,
    `purchase_cost` DECIMAL(12,2) NULL,
    `warranty_end` DATE NULL,
    `notes` TEXT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sector` (`sector_id`),
    CONSTRAINT `fk_equipment_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `equipment_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `frequency_type` ENUM('daily','weekly','monthly','quarterly','yearly','hours') NOT NULL DEFAULT 'monthly',
    `frequency_value` INT NOT NULL DEFAULT 1,
    `last_performed_at` DATETIME NULL,
    `next_due_at` DATETIME NULL,
    `alert_days_before` INT NOT NULL DEFAULT 7,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_equipment` (`equipment_id`),
    INDEX `idx_next_due` (`next_due_at`),
    CONSTRAINT `fk_maint_sched_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_maint_sched_equip` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `equipment_id` INT NOT NULL,
    `schedule_id` INT NULL,
    `type` ENUM('preventive','corrective','predictive') NOT NULL DEFAULT 'preventive',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `performed_by` INT NULL,
    `performed_at` DATETIME NOT NULL,
    `duration_minutes` INT NULL,
    `cost` DECIMAL(12,2) NULL DEFAULT 0,
    `parts_used` JSON NULL,
    `status` ENUM('completed','in_progress','cancelled') NOT NULL DEFAULT 'completed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_equipment` (`equipment_id`),
    INDEX `idx_schedule` (`schedule_id`),
    CONSTRAINT `fk_maint_log_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_maint_log_equip` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_maint_log_sched` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_maint_log_user` FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-026: Gestor de Custos de Produção
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `production_cost_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `sector_id` INT NULL,
    `labor_cost_per_hour` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `overhead_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    `overhead_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_prod_cost_cfg_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_costs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `product_id` INT NULL,
    `material_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `labor_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `overhead_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `estimated_cost` DECIMAL(12,2) NULL,
    `production_time_minutes` INT NULL,
    `calculated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_product` (`product_id`),
    CONSTRAINT `fk_prod_costs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-028: WhatsApp Business Integration
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `whatsapp_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `provider` ENUM('meta_cloud','evolution_api','z_api') NOT NULL DEFAULT 'evolution_api',
    `api_url` VARCHAR(500) NULL,
    `api_key` VARCHAR(500) NULL,
    `instance_name` VARCHAR(100) NULL,
    `phone_number_id` VARCHAR(50) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_wa_config_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'order_confirmed, nfe_issued, payment_reminder, boleto_sent',
    `message_template` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_event` (`event_type`),
    CONSTRAINT `fk_wa_tpl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `template_id` INT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `customer_id` INT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('pending','sent','delivered','read','failed') NOT NULL DEFAULT 'pending',
    `external_id` VARCHAR(100) NULL,
    `error_message` TEXT NULL,
    `sent_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `read_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_customer` (`customer_id`),
    CONSTRAINT `fk_wa_msg_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_wa_msg_tpl` FOREIGN KEY (`template_id`) REFERENCES `whatsapp_templates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-029: Multi-filial
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(20) NULL,
    `cnpj` VARCHAR(18) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(2) NULL,
    `phone` VARCHAR(20) NULL,
    `is_headquarters` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_branches_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-032: Rastreamento de Entregas
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `carriers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `tracking_url_pattern` VARCHAR(500) NULL COMMENT 'URL com {code} placeholder',
    `api_type` ENUM('manual','correios','jadlog','custom') NOT NULL DEFAULT 'manual',
    `api_credentials` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_carriers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `carrier_id` INT NULL,
    `tracking_code` VARCHAR(100) NULL,
    `status` ENUM('preparing','shipped','in_transit','out_for_delivery','delivered','returned') NOT NULL DEFAULT 'preparing',
    `shipped_at` DATETIME NULL,
    `estimated_delivery` DATE NULL,
    `delivered_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_tracking` (`tracking_code`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_shipments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_shipments_carrier` FOREIGN KEY (`carrier_id`) REFERENCES `carriers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipment_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `shipment_id` INT NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `location` VARCHAR(200) NULL,
    `occurred_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_shipment` (`shipment_id`),
    CONSTRAINT `fk_ship_events_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_ship_events_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-033: Gamificação
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `achievements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `icon` VARCHAR(50) NULL DEFAULT 'fas fa-trophy',
    `badge_color` VARCHAR(7) NULL DEFAULT '#ffc107',
    `metric_type` VARCHAR(50) NOT NULL COMMENT 'units_produced, quality_score, on_time_delivery, attendance',
    `threshold_value` DECIMAL(10,2) NOT NULL,
    `points` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_achiev_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_scores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `total_points` INT NOT NULL DEFAULT 0,
    `level` INT NOT NULL DEFAULT 1,
    `period_type` ENUM('weekly','monthly','all_time') NOT NULL DEFAULT 'all_time',
    `period_start` DATE NULL,
    `period_end` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_period` (`period_type`, `period_start`),
    CONSTRAINT `fk_user_scores_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_user_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_achievements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `achievement_id` INT NOT NULL,
    `earned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    UNIQUE INDEX `idx_user_achievement` (`user_id`, `achievement_id`),
    CONSTRAINT `fk_user_achiev_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_user_achiev_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_achiev_achiev` FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- FEAT-034: Sustentabilidade (ESG)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `esg_metrics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `unit` VARCHAR(30) NOT NULL COMMENT 'kWh, m3, kg, tCO2e',
    `category` ENUM('energy','water','waste','emissions','materials') NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_category` (`category`),
    CONSTRAINT `fk_esg_metrics_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `esg_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `metric_id` INT NOT NULL,
    `sector_id` INT NULL,
    `value` DECIMAL(12,4) NOT NULL,
    `reference_date` DATE NOT NULL,
    `notes` TEXT NULL,
    `recorded_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_metric` (`metric_id`),
    INDEX `idx_date` (`reference_date`),
    CONSTRAINT `fk_esg_records_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_esg_records_metric` FOREIGN KEY (`metric_id`) REFERENCES `esg_metrics`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_esg_records_user` FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `esg_targets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `metric_id` INT NOT NULL,
    `target_value` DECIMAL(12,4) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_esg_targets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`),
    CONSTRAINT `fk_esg_targets_metric` FOREIGN KEY (`metric_id`) REFERENCES `esg_metrics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
