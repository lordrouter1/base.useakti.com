-- ============================================================================
-- UPDATE: update_20260309_ip_blacklist.sql
-- Descrição: Sistema de detecção de ataques via 404 com blacklist automática
--            Tabelas criadas no banco MASTER (akti_master)
-- Data: 2026-03-09
-- Autor: Sistema Akti
-- ============================================================================
-- ATENÇÃO: Este script deve ser executado no banco akti_master, NÃO nos bancos
-- de tenant. As tabelas ip_404_hits e ip_blacklist são globais (cross-tenant).
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────
-- 1. Registro de hits 404 por IP
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ip_404_hits (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    path VARCHAR(2048) NOT NULL,
    user_agent VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índice para contagem rápida por IP na janela de tempo
SET @dbname = DATABASE();
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ip_404_hits' AND INDEX_NAME = 'idx_404_ip_created') > 0,
    'SELECT 1',
    'CREATE INDEX idx_404_ip_created ON ip_404_hits (ip_address, created_at)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- Índice para limpeza de registros antigos
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ip_404_hits' AND INDEX_NAME = 'idx_404_created') > 0,
    'SELECT 1',
    'CREATE INDEX idx_404_created ON ip_404_hits (created_at)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- ─────────────────────────────────────────────────────
-- 2. Blacklist de IPs bloqueados
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ip_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    hits INT NOT NULL DEFAULT 0,
    reason VARCHAR(50) NOT NULL DEFAULT '404_flood',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL COMMENT 'NULL = bloqueio permanente',
    notes TEXT DEFAULT NULL,
    UNIQUE KEY uk_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índice para consulta rápida do Lua/Nginx (IP ativo + expiração)
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ip_blacklist' AND INDEX_NAME = 'idx_bl_active_ip') > 0,
    'SELECT 1',
    'CREATE INDEX idx_bl_active_ip ON ip_blacklist (is_active, ip_address, expires_at)'
));
PREPARE addIndex FROM @preparedStatement;
EXECUTE addIndex;
DEALLOCATE PREPARE addIndex;

-- ─────────────────────────────────────────────────────
-- 3. Usuário MySQL somente-leitura para o Nginx/Lua
--    (executar manualmente como root do MySQL)
-- ─────────────────────────────────────────────────────
-- CREATE USER IF NOT EXISTS 'akti_guard'@'127.0.0.1' IDENTIFIED BY 'GuardR3ad0nly!@2026';
-- GRANT SELECT ON akti_master.ip_blacklist TO 'akti_guard'@'127.0.0.1';
-- FLUSH PRIVILEGES;

SET FOREIGN_KEY_CHECKS = 1;
