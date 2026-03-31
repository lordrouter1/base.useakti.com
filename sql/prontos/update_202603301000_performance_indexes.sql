-- ============================================================
-- Fase 4 — Performance: Criação de índices de banco de dados
-- Índices para otimizar queries frequentes do sistema
-- Idempotente: usa CREATE INDEX IF NOT EXISTS (MariaDB 10.1+)
-- Para MySQL < 8.0, usa procedimento de verificação
-- ============================================================

-- Procedure auxiliar para criar índice se não existir (compatibilidade MySQL 5.7)
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

-- ════════════════════════════════════════════════════════════
-- Tabela: orders
-- ════════════════════════════════════════════════════════════

-- Pipeline/Kanban: filtra por etapa e status
CALL create_index_if_not_exists('orders', 'idx_orders_pipeline_stage_status', 'pipeline_stage, status');

-- JOINs com customers
CALL create_index_if_not_exists('orders', 'idx_orders_customer_id', 'customer_id');

-- Relatórios por período
CALL create_index_if_not_exists('orders', 'idx_orders_created_at', 'created_at');

-- ════════════════════════════════════════════════════════════
-- Tabela: customers
-- ════════════════════════════════════════════════════════════

-- Busca por nome
CALL create_index_if_not_exists('customers', 'idx_customers_name', 'name');

-- Busca por CPF/CNPJ
CALL create_index_if_not_exists('customers', 'idx_customers_document', 'document');

-- Filtro de ativos (listagem padrão)
CALL create_index_if_not_exists('customers', 'idx_customers_status_deleted', 'status, deleted_at');

-- ════════════════════════════════════════════════════════════
-- Tabela: login_attempts
-- ════════════════════════════════════════════════════════════

-- Rate limiting: busca por IP + email + data
CALL create_index_if_not_exists('login_attempts', 'idx_login_attempts_rate', 'ip_address, email, attempted_at');

-- ════════════════════════════════════════════════════════════
-- Tabela: order_installments
-- ════════════════════════════════════════════════════════════

-- Relatório financeiro
CALL create_index_if_not_exists('order_installments', 'idx_installments_status_paid', 'status, paid_date');

-- ════════════════════════════════════════════════════════════
-- Tabela: stock_items
-- ════════════════════════════════════════════════════════════

-- Busca de estoque por depósito + produto
CALL create_index_if_not_exists('stock_items', 'idx_stock_warehouse_product', 'warehouse_id, product_id');

-- ════════════════════════════════════════════════════════════
-- Tabela: system_logs
-- ════════════════════════════════════════════════════════════

-- Consulta de logs por data
CALL create_index_if_not_exists('system_logs', 'idx_system_logs_created', 'created_at');

-- ════════════════════════════════════════════════════════════
-- Tabela: products
-- ════════════════════════════════════════════════════════════

-- Busca por nome (usado em Select2 e listagem)
CALL create_index_if_not_exists('products', 'idx_products_name', 'name');

-- Filtro por categoria
CALL create_index_if_not_exists('products', 'idx_products_category', 'category_id');

-- ════════════════════════════════════════════════════════════
-- Limpar procedure auxiliar
-- ════════════════════════════════════════════════════════════
DROP PROCEDURE IF EXISTS create_index_if_not_exists;
