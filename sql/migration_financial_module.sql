-- ============================================================================
-- MIGRATION: Módulo Financeiro - Controle de Pagamentos e Parcelas
-- Data: 2026-03-04
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Adicionar colunas faltantes na tabela orders
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `down_payment` decimal(10,2) DEFAULT 0.00 AFTER `discount`,
    ADD COLUMN IF NOT EXISTS `nf_number` varchar(20) DEFAULT NULL AFTER `stock_warehouse_id`,
    ADD COLUMN IF NOT EXISTS `nf_series` varchar(5) DEFAULT NULL AFTER `nf_number`,
    ADD COLUMN IF NOT EXISTS `nf_status` enum('nao_emitida','emitida','cancelada') DEFAULT 'nao_emitida' AFTER `nf_series`,
    ADD COLUMN IF NOT EXISTS `nf_access_key` varchar(50) DEFAULT NULL AFTER `nf_status`,
    ADD COLUMN IF NOT EXISTS `nf_notes` text DEFAULT NULL AFTER `nf_access_key`;

-- 2. Tabela de parcelas individuais de pedidos
DROP TABLE IF EXISTS `order_installments`;
CREATE TABLE `order_installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `installment_number` int(11) NOT NULL COMMENT 'Número da parcela (1, 2, 3...)',
  `amount` decimal(10,2) NOT NULL COMMENT 'Valor da parcela',
  `due_date` date NOT NULL COMMENT 'Data de vencimento',
  `paid_date` date DEFAULT NULL COMMENT 'Data em que foi pago',
  `paid_amount` decimal(10,2) DEFAULT NULL COMMENT 'Valor efetivamente pago',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Método usado no pagamento desta parcela',
  `status` enum('pendente','pago','atrasado','cancelado') NOT NULL DEFAULT 'pendente',
  `is_confirmed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=confirmado manualmente, 0=aguardando confirmação',
  `confirmed_by` int(11) DEFAULT NULL COMMENT 'Usuário que confirmou',
  `confirmed_at` datetime DEFAULT NULL,
  `gateway_reference` varchar(255) DEFAULT NULL COMMENT 'Referência do gateway (se pago online)',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_installment` (`order_id`, `installment_number`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_confirmed` (`is_confirmed`),
  CONSTRAINT `order_installments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_installments_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabela de log de transações financeiras (entradas e saídas)
DROP TABLE IF EXISTS `financial_transactions`;
CREATE TABLE `financial_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('entrada','saida') NOT NULL,
  `category` varchar(100) NOT NULL COMMENT 'Ex: pagamento_pedido, entrada, despesa_fixa, material, etc.',
  `description` varchar(500) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, installment, manual',
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID do pedido ou parcela',
  `payment_method` varchar(50) DEFAULT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=confirmado, 0=pendente confirmação',
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Quem registrou',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_confirmed` (`is_confirmed`),
  CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Adicionar permissão 'financial' ao grupo Administradores (se existir)
INSERT IGNORE INTO `group_permissions` (`group_id`, `page_name`)
SELECT id, 'financial' FROM `user_groups` WHERE name = 'Administradores' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FIM DA MIGRATION
-- ============================================================================
