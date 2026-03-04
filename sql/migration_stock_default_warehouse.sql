-- ============================================================
-- MIGRAÇÃO: Armazém padrão + Controle de estoque no pipeline
-- Data: 04/03/2026
-- ============================================================

-- 1. Adicionar coluna is_default na tabela warehouses
ALTER TABLE `warehouses` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- 2. Tabela para rastrear deduções de estoque feitas ao mover pedidos para preparação
-- Permite reverter o estoque caso o pedido volte a uma etapa anterior
CREATE TABLE IF NOT EXISTS `order_stock_deductions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `order_item_id` INT(11) NOT NULL,
    `warehouse_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `combination_id` INT(11) DEFAULT NULL,
    `quantity` DECIMAL(12,2) NOT NULL,
    `movement_id` INT(11) DEFAULT NULL COMMENT 'ID da movimentação de saída no stock_movements',
    `status` ENUM('deducted','reversed') NOT NULL DEFAULT 'deducted',
    `deducted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `reversed_at` DATETIME DEFAULT NULL,
    `reversed_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `osd_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `osd_item_fk` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `osd_warehouse_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `osd_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Adicionar coluna warehouse_id na tabela orders para registrar de qual armazém saiu o estoque
ALTER TABLE `orders` ADD COLUMN `stock_warehouse_id` INT(11) DEFAULT NULL AFTER `tracking_code`;
