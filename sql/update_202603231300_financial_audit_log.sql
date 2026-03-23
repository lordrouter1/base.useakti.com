-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Tabela de auditoria do módulo financeiro
-- Data: 2026-03-23 13:00
-- Fase 2 do relatório de refatoração
-- Descrição:
--   Cria tabela financial_audit_log para rastrear todas as operações
--   em parcelas, transações e pedidos no módulo financeiro.
-- ═══════════════════════════════════════════════════════════════

SET @dbname = DATABASE();

-- Criar tabela apenas se não existir
CREATE TABLE IF NOT EXISTS financial_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('installment','transaction','order') NOT NULL COMMENT 'Tipo da entidade auditada',
    entity_id INT NOT NULL COMMENT 'ID da entidade',
    action VARCHAR(50) NOT NULL COMMENT 'Ação: created, paid, confirmed, cancelled, updated, deleted, merged, split, imported',
    old_values JSON DEFAULT NULL COMMENT 'Valores anteriores (antes da alteração)',
    new_values JSON DEFAULT NULL COMMENT 'Valores atuais (após a alteração)',
    user_id INT DEFAULT NULL COMMENT 'Usuário que executou a ação',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP do cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de auditoria do módulo financeiro';
