-- ============================================================
-- Migration Fase 4: Segurança, Relatórios e Dashboard
-- Data: 28/03/2026
-- Itens: FASE4-01 (Rate Limiting), FASE4-02 (CC-e Report)
-- ============================================================

-- ── FASE4-01: Tabela de Rate Limiting ──
CREATE TABLE IF NOT EXISTS `rate_limit` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'Ação limitada (ex: nfe_emit, nfe_cancel)',
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rate_limit_user_action` (`user_id`, `action`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Controle de rate limiting por usuário e ação';

-- Limpeza automática de registros antigos (> 24h) — pode ser chamado por cron
-- DELETE FROM rate_limit WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- ── FASE4-02: Índice para relatório de CC-e por período ──
-- Otimizar consulta de nfe_correction_history por data
ALTER TABLE `nfe_correction_history`
    ADD INDEX IF NOT EXISTS `idx_correction_history_created` (`created_at`);

-- ── FASE4-04: Índice para auditoria de credenciais ──
-- Otimizar consulta de logs de auditoria por entity_type
ALTER TABLE `nfe_audit_log`
    ADD INDEX IF NOT EXISTS `idx_audit_entity_type` (`entity_type`, `created_at`);
