-- ══════════════════════════════════════════════════════════════════
-- Migration: Portal do Cliente — Fase 1A (Correções Críticas & Segurança)
-- Data: 24/03/2026 12:00
-- Descrição: Adiciona colunas de reset de senha à tabela
--            customer_portal_access para suportar fluxo de
--            "Esqueci minha senha" e recuperação via token.
-- ══════════════════════════════════════════════════════════════════

DELIMITER //

DROP PROCEDURE IF EXISTS _portal_migrate_fase1a//

CREATE PROCEDURE _portal_migrate_fase1a()
BEGIN
    DECLARE _dbname VARCHAR(64) DEFAULT DATABASE();

    -- reset_token — token de recuperação de senha
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'customer_portal_access' AND column_name = 'reset_token'
    ) THEN
        ALTER TABLE customer_portal_access
            ADD COLUMN reset_token VARCHAR(128) DEFAULT NULL COMMENT 'Token de recuperação de senha';
    END IF;

    -- reset_token_expires_at — validade do token de reset
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = _dbname AND table_name = 'customer_portal_access' AND column_name = 'reset_token_expires_at'
    ) THEN
        ALTER TABLE customer_portal_access
            ADD COLUMN reset_token_expires_at DATETIME DEFAULT NULL COMMENT 'Data/hora de expiração do token de reset';
    END IF;

END//

DELIMITER ;

-- Executar e limpar
CALL _portal_migrate_fase1a();
DROP PROCEDURE IF EXISTS _portal_migrate_fase1a;

-- ══════════════════════════════════════════════════════════════════
-- FIM da Migration — Fase 1A
-- ══════════════════════════════════════════════════════════════════
