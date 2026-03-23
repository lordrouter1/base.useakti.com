-- ══════════════════════════════════════════════════════════════
-- Migration: Transações Recorrentes
-- Data: 2026-03-23
-- Fase: 4 — Novas Funcionalidades
-- Descrição: Cria tabela financial_recurring_transactions para
--            gerenciamento de receitas e despesas fixas mensais.
-- ══════════════════════════════════════════════════════════════

-- Tabela de recorrências
CREATE TABLE IF NOT EXISTS financial_recurring_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('entrada','saida') NOT NULL DEFAULT 'saida',
    category VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    due_day TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Dia do mês para vencimento (1-28)',
    payment_method VARCHAR(30) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    start_month DATE NOT NULL COMMENT 'Primeiro mês de geração (YYYY-MM-01)',
    end_month DATE DEFAULT NULL COMMENT 'Último mês de geração (NULL = sem fim)',
    last_generated_month DATE DEFAULT NULL COMMENT 'Último mês em que a transação foi gerada',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    user_id INT DEFAULT NULL COMMENT 'Usuário que criou a recorrência',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_frt_active (is_active),
    INDEX idx_frt_type (type),
    INDEX idx_frt_next (is_active, last_generated_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna de referência à recorrência em financial_transactions
-- para vincular transações geradas automaticamente à sua recorrência de origem
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'financial_transactions'
      AND COLUMN_NAME = 'recurring_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financial_transactions ADD COLUMN recurring_id INT DEFAULT NULL AFTER reference_id, ADD INDEX idx_ft_recurring (recurring_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
