-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Proteção contra força bruta no login
-- Data: 2026-03-09
-- Descrição:
--   1. Cria tabela login_attempts para registrar tentativas
--   2. Índices para consultas rápidas por IP+email e limpeza
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(191) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índice composto para consulta de tentativas por IP+email (usado no rate-limit)
SET @dbname = DATABASE();
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'login_attempts' AND INDEX_NAME = 'idx_la_ip_email_date') > 0,
    'SELECT 1',
    'CREATE INDEX idx_la_ip_email_date ON login_attempts (ip_address, email, attempted_at)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- Índice para limpeza de registros antigos
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'login_attempts' AND INDEX_NAME = 'idx_la_attempted_at') > 0,
    'SELECT 1',
    'CREATE INDEX idx_la_attempted_at ON login_attempts (attempted_at)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;
