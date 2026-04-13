-- Migration: Adicionar coluna yield_qty na tabela product_supplies
-- Criado em: 13/04/2026 17:24
-- Sequencial: 12
--
-- Adiciona a coluna yield_qty para suportar relação X:Y entre insumo e produto.
-- Exemplo: quantity=500 (g de tinta), yield_qty=10 (cartões) → 500g produz 10 cartões → 50g/un
-- Fórmula custo unitário: (quantity / yield_qty) * (1 + waste_percent/100) * cost_price

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'product_supplies' 
    AND COLUMN_NAME = 'yield_qty');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `product_supplies` ADD COLUMN `yield_qty` DECIMAL(12,4) NOT NULL DEFAULT 1.0000 COMMENT ''Quantidade de produtos que a relação produz (Y em X:Y)'' AFTER `quantity`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
