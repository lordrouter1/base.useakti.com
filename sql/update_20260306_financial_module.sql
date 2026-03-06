-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Módulo Financeiro — Entradas, Saídas e Registros
-- Data: 2026-03-06
-- Descrição:
--   1. Cria tabela order_installments (parcelas de pagamento)
--   2. Cria tabela financial_transactions (transações do caixa)
--   3. Altera coluna type de ENUM para VARCHAR(20) caso já exista
--      (necessário para suportar o novo tipo 'registro')
--   4. Converte estornos existentes: type='saida' com
--      category='estorno_pagamento' → type='registro'
-- ═══════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────
-- 1. Tabela de parcelas de pagamento dos pedidos
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    installment_number INT NOT NULL DEFAULT 1,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_date DATE NOT NULL,
    status ENUM('pendente','pago','atrasado','cancelado') DEFAULT 'pendente',
    paid_date DATE DEFAULT NULL,
    paid_amount DECIMAL(12,2) DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    is_confirmed TINYINT(1) DEFAULT 0,
    confirmed_by INT DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- 2. Tabela de transações financeiras (entradas, saídas, registros)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL DEFAULT 'entrada' COMMENT 'entrada, saida ou registro',
    category VARCHAR(50) NOT NULL DEFAULT 'outra_entrada',
    description VARCHAR(500) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    transaction_date DATE NOT NULL,
    reference_type VARCHAR(50) DEFAULT NULL COMMENT 'manual, installment, ofx',
    reference_id INT DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    is_confirmed TINYINT(1) DEFAULT 1,
    confirmed_by INT DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    user_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- 3. Para bancos que já possuem financial_transactions com
--    type como ENUM('entrada','saida'), converter para VARCHAR(20)
--    para suportar o novo tipo 'registro'
-- ─────────────────────────────────────────────────────
SET @dbname = DATABASE();
SET @tablename = 'financial_transactions';
SET @columnname = 'type';

-- Verifica se a coluna type é do tipo ENUM e, se for, altera para VARCHAR(20)
SET @preparedStatement = (SELECT IF(
    (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) = 'enum',
    'ALTER TABLE financial_transactions MODIFY COLUMN type VARCHAR(20) NOT NULL DEFAULT \'entrada\' COMMENT \'entrada, saida ou registro\'',
    'SELECT 1'
));
PREPARE alterIfEnum FROM @preparedStatement;
EXECUTE alterIfEnum;
DEALLOCATE PREPARE alterIfEnum;

-- ─────────────────────────────────────────────────────
-- 4. Converter estornos existentes para tipo 'registro'
--    (estornos antigos foram salvos como type='saida')
-- ─────────────────────────────────────────────────────
UPDATE financial_transactions
SET type = 'registro'
WHERE category = 'estorno_pagamento' AND type = 'saida';

-- ─────────────────────────────────────────────────────
-- 5. Índices para performance em consultas frequentes
-- ─────────────────────────────────────────────────────
-- Índice na coluna type + transaction_date (filtros de listagem)
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'financial_transactions' AND INDEX_NAME = 'idx_ft_type_date') > 0,
    'SELECT 1',
    'CREATE INDEX idx_ft_type_date ON financial_transactions (type, transaction_date)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- Índice na coluna category (filtros por categoria)
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'financial_transactions' AND INDEX_NAME = 'idx_ft_category') > 0,
    'SELECT 1',
    'CREATE INDEX idx_ft_category ON financial_transactions (category)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- Índice na referência (para localizar transações associadas a parcelas/ofx)
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'financial_transactions' AND INDEX_NAME = 'idx_ft_reference') > 0,
    'SELECT 1',
    'CREATE INDEX idx_ft_reference ON financial_transactions (reference_type, reference_id)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- Índice em order_installments por order_id e status
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'order_installments' AND INDEX_NAME = 'idx_oi_order_status') > 0,
    'SELECT 1',
    'CREATE INDEX idx_oi_order_status ON order_installments (order_id, status)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;
