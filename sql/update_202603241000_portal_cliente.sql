-- ══════════════════════════════════════════════════════════════════
-- Migration: Portal do Cliente — Fase 1
-- Data: 24/03/2026
-- Descrição: Cria tabelas de autenticação, sessões, mensagens e 
--            configurações do portal do cliente. Altera tabela orders
--            para campos de aprovação.
-- ══════════════════════════════════════════════════════════════════

-- ──────────────────────────────────────────────
-- 1. customer_portal_access — Autenticação
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_portal_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    magic_token VARCHAR(128) DEFAULT NULL,
    magic_token_expires_at DATETIME DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    lang VARCHAR(10) NOT NULL DEFAULT 'pt-br' COMMENT 'Idioma preferido do cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_portal_email (email),
    UNIQUE KEY uq_portal_customer (customer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 2. customer_portal_sessions — Sessões ativas
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_portal_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL,
    device_info VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session_token (session_token),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 3. customer_portal_messages — Mensagens
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_portal_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    sender_type ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    sender_id INT DEFAULT NULL COMMENT 'user_id se admin, NULL se customer',
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME DEFAULT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    KEY idx_portal_msg_customer (customer_id),
    KEY idx_portal_msg_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 4. customer_portal_config — Configurações
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_portal_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT DEFAULT NULL,
    descricao VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO customer_portal_config (config_key, config_value, descricao) VALUES
('portal_enabled', '1', 'Portal do cliente ativo (0=desativado)'),
('allow_self_register', '1', 'Permitir auto-registro do cliente'),
('allow_new_orders', '1', 'Permitir que o cliente crie novos pedidos/orçamentos'),
('allow_order_approval', '1', 'Permitir que o cliente aprove/recuse orçamentos'),
('allow_messages', '1', 'Permitir mensagens no portal'),
('magic_link_expiry_hours', '24', 'Validade do link mágico em horas'),
('show_prices_in_catalog', '1', 'Exibir preços no catálogo de novo pedido'),
('require_password', '0', 'Exigir senha (0=apenas link mágico)')
ON DUPLICATE KEY UPDATE config_key = config_key;

-- ──────────────────────────────────────────────
-- 5. Alteração na tabela orders — aprovação
-- ──────────────────────────────────────────────
-- Usa stored procedure temporária para ALTER condicionais.
-- Compatível com MySQL 5.7+, MariaDB 10.3+ e qualquer cliente SQL.

DELIMITER //

DROP PROCEDURE IF EXISTS _portal_migrate_orders//

CREATE PROCEDURE _portal_migrate_orders()
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

    -- ──────────────────────────────────────────────
    -- 6. Índice para buscas frequentes do portal
    -- ──────────────────────────────────────────────
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = _dbname AND table_name = 'orders' AND index_name = 'idx_orders_customer_portal'
    ) THEN
        ALTER TABLE orders ADD INDEX idx_orders_customer_portal (customer_id, status, pipeline_stage);
    END IF;

END//

DELIMITER ;

-- Executar e limpar
CALL _portal_migrate_orders();
DROP PROCEDURE IF EXISTS _portal_migrate_orders;

-- ══════════════════════════════════════════════════════════════════
-- FIM da Migration
-- ══════════════════════════════════════════════════════════════════
