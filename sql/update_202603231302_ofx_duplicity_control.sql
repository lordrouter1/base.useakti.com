-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Controle de duplicidade para importação OFX
-- Data: 2026-03-23 13:02
-- Fase 2 do relatório de refatoração
-- Descrição:
--   Cria tabela ofx_imported_transactions para evitar importação
--   duplicada de transações OFX via FITID + conta bancária.
-- ═══════════════════════════════════════════════════════════════

SET @dbname = DATABASE();

-- Criar tabela apenas se não existir
CREATE TABLE IF NOT EXISTS ofx_imported_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fitid VARCHAR(100) NOT NULL COMMENT 'Identificador único da transação no OFX (FITID)',
    bank_account VARCHAR(50) DEFAULT NULL COMMENT 'Número/identificador da conta bancária',
    transaction_date DATE NOT NULL COMMENT 'Data da transação',
    amount DECIMAL(12,2) NOT NULL COMMENT 'Valor da transação',
    description VARCHAR(500) DEFAULT NULL COMMENT 'Descrição/memo da transação',
    financial_transaction_id INT DEFAULT NULL COMMENT 'FK para financial_transactions (se importada)',
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora da importação',
    UNIQUE KEY uk_ofx_fitid_account (fitid, bank_account),
    INDEX idx_ofx_date (transaction_date),
    INDEX idx_ofx_ft_id (financial_transaction_id),
    CONSTRAINT fk_ofx_financial_tx
        FOREIGN KEY (financial_transaction_id) REFERENCES financial_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Controle de duplicidade para importação OFX';
