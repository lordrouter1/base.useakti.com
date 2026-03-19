-- ============================================================================
-- Atualização: Adicionar campo de link de pagamento ao pedido
-- Data: 2026-03-18
-- Descrição: Persiste a URL do link de pagamento gerado pelo gateway no pedido,
--            permitindo reenvio e exibição no card financeiro do pipeline.
-- ============================================================================

-- 1. payment_link_url — URL completa do link de pagamento
SET @exists_link_url = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_link_url');
SET @sql_link_url = IF(@exists_link_url = 0,
    'ALTER TABLE orders ADD COLUMN payment_link_url VARCHAR(1000) DEFAULT NULL COMMENT ''URL do link de pagamento gerado pelo gateway'' AFTER down_payment',
    'SELECT ''Column payment_link_url already exists''');
PREPARE stmt FROM @sql_link_url;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. payment_link_gateway — slug do gateway que gerou o link
SET @exists_link_gw = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_link_gateway');
SET @sql_link_gw = IF(@exists_link_gw = 0,
    'ALTER TABLE orders ADD COLUMN payment_link_gateway VARCHAR(50) DEFAULT NULL COMMENT ''Slug do gateway que gerou o link'' AFTER payment_link_url',
    'SELECT ''Column payment_link_gateway already exists''');
PREPARE stmt FROM @sql_link_gw;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. payment_link_method — método de pagamento do link (pix, credit_card, boleto, debit_card)
SET @exists_link_method = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_link_method');
SET @sql_link_method = IF(@exists_link_method = 0,
    'ALTER TABLE orders ADD COLUMN payment_link_method VARCHAR(50) DEFAULT NULL COMMENT ''Método de pagamento do link gerado'' AFTER payment_link_gateway',
    'SELECT ''Column payment_link_method already exists''');
PREPARE stmt FROM @sql_link_method;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. payment_link_created_at — quando o link foi gerado
SET @exists_link_created = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_link_created_at');
SET @sql_link_created = IF(@exists_link_created = 0,
    'ALTER TABLE orders ADD COLUMN payment_link_created_at DATETIME DEFAULT NULL COMMENT ''Data/hora em que o link foi gerado'' AFTER payment_link_method',
    'SELECT ''Column payment_link_created_at already exists''');
PREPARE stmt FROM @sql_link_created;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
