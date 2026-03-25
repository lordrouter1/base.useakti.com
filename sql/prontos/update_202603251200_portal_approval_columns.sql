-- ══════════════════════════════════════════════════════════════════
-- Migration: Colunas de aprovação do Portal do Cliente (orders)
-- Data: 25/03/2026 12:00
-- Descrição: Adiciona colunas de aprovação do cliente (customer_approval_status,
--            customer_approval_at, customer_approval_ip, customer_approval_notes,
--            portal_origin) na tabela orders, se ainda não existirem.
--            Atualiza pedidos com link de pagamento ou catálogo de confirmação
--            ativo para customer_approval_status='pendente'.
-- ══════════════════════════════════════════════════════════════════

DELIMITER //

DROP PROCEDURE IF EXISTS _portal_approval_columns//

CREATE PROCEDURE _portal_approval_columns()
BEGIN
    DECLARE _dbname VARCHAR(64) DEFAULT DATABASE();

    -- customer_approval_status
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'orders' AND column_name = 'customer_approval_status'
    ) THEN
        ALTER TABLE orders ADD COLUMN customer_approval_status ENUM('pendente','aprovado','recusado') DEFAULT NULL COMMENT 'Status de aprovação do cliente via portal';
    END IF;

    -- customer_approval_at
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'orders' AND column_name = 'customer_approval_at'
    ) THEN
        ALTER TABLE orders ADD COLUMN customer_approval_at DATETIME DEFAULT NULL COMMENT 'Data/hora da aprovação/recusa pelo cliente';
    END IF;

    -- customer_approval_ip
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'orders' AND column_name = 'customer_approval_ip'
    ) THEN
        ALTER TABLE orders ADD COLUMN customer_approval_ip VARCHAR(45) DEFAULT NULL COMMENT 'IP do cliente no momento da aprovação';
    END IF;

    -- customer_approval_notes
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'orders' AND column_name = 'customer_approval_notes'
    ) THEN
        ALTER TABLE orders ADD COLUMN customer_approval_notes TEXT DEFAULT NULL COMMENT 'Observações do cliente na aprovação/recusa';
    END IF;

    -- portal_origin
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'orders' AND column_name = 'portal_origin'
    ) THEN
        ALTER TABLE orders ADD COLUMN portal_origin TINYINT(1) DEFAULT 0 COMMENT 'Se o pedido foi originado pelo portal do cliente';
    END IF;

    -- Índice para buscas do portal
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = _dbname AND table_name = 'orders' AND index_name = 'idx_orders_customer_portal'
    ) THEN
        ALTER TABLE orders ADD INDEX idx_orders_customer_portal (customer_id, status, pipeline_stage);
    END IF;

END//

DELIMITER ;

CALL _portal_approval_columns();
DROP PROCEDURE IF EXISTS _portal_approval_columns;

-- ──────────────────────────────────────────────
-- Data fix: marcar como 'pendente' pedidos com link de pagamento gerado
-- que ainda não têm status de aprovação
-- ──────────────────────────────────────────────
UPDATE orders
   SET customer_approval_status = 'pendente'
 WHERE payment_link_url IS NOT NULL AND payment_link_url != ''
   AND customer_approval_status IS NULL
   AND status NOT IN ('concluido','cancelado');

-- ──────────────────────────────────────────────
-- Data fix: marcar como 'pendente' pedidos com link de catálogo ativo
-- com require_confirmation = 1 que ainda não têm status de aprovação
-- ──────────────────────────────────────────────
UPDATE orders o
 INNER JOIN catalog_links cl ON cl.order_id = o.id AND cl.is_active = 1 AND cl.require_confirmation = 1
   SET o.customer_approval_status = 'pendente'
 WHERE o.customer_approval_status IS NULL
   AND o.status NOT IN ('concluido','cancelado');

-- ══════════════════════════════════════════════════════════════════
-- FIM
-- ══════════════════════════════════════════════════════════════════
