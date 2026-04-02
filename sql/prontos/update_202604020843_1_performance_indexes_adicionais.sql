-- Migration: Indices adicionais de performance
-- Criado em: 02/04/2026 08:43
-- Sequencial: 1

-- Procedure auxiliar para criar indice se nao existir (compatibilidade MySQL 5.7)
DROP PROCEDURE IF EXISTS create_index_if_not_exists;

DELIMITER //
CREATE PROCEDURE create_index_if_not_exists(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns VARCHAR(255)
)
BEGIN
    DECLARE index_count INT DEFAULT 0;

    SELECT COUNT(*) INTO index_count
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND INDEX_NAME = p_index;

    IF index_count = 0 THEN
        SET @sql = CONCAT('CREATE INDEX ', p_index, ' ON ', p_table, ' (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- customers.email — busca de duplicatas e login
CALL create_index_if_not_exists('customers', 'idx_customers_email', 'email');

-- order_installments (due_date, status) — parcelas vencendo/atrasadas
CALL create_index_if_not_exists('order_installments', 'idx_installments_due_status', 'due_date, status');

-- orders.status — filtro de listagem
CALL create_index_if_not_exists('orders', 'idx_orders_status', 'status');

-- Limpar procedure auxiliar
DROP PROCEDURE IF EXISTS create_index_if_not_exists;
