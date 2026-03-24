-- =======================================================
-- Migração: Adicionar campo reason na tabela de auditoria
-- e índices para relatório de auditoria financeira.
-- Data: 2025-06-15
-- =======================================================

-- 1. Adicionar coluna 'reason' (motivo de exclusão/ação) na tabela financial_audit_log
ALTER TABLE financial_audit_log
    ADD COLUMN reason VARCHAR(500) NULL DEFAULT NULL COMMENT 'Motivo informado pelo usuário (obrigatório em exclusões)' AFTER new_values;

-- 2. Índice para consultas de relatório por data + ação
CREATE INDEX idx_fal_created_action ON financial_audit_log (created_at DESC, action);

-- 3. Índice para consultas filtradas por entity_type
CREATE INDEX idx_fal_entity_type ON financial_audit_log (entity_type, created_at DESC);
