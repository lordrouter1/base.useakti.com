-- ══════════════════════════════════════════════════════════════
-- Portal do Cliente — Fase 6 (complemento) + Fase 7
-- Integração de sessões + 2FA + avatar + rate limiting portal
-- ══════════════════════════════════════════════════════════════

-- ── 1. Coluna 2FA no customer_portal_access ──
ALTER TABLE `customer_portal_access`
    ADD COLUMN IF NOT EXISTS `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lang`,
    ADD COLUMN IF NOT EXISTS `two_factor_code` VARCHAR(6) DEFAULT NULL AFTER `two_factor_enabled`,
    ADD COLUMN IF NOT EXISTS `two_factor_expires_at` DATETIME DEFAULT NULL AFTER `two_factor_code`;

-- ── 2. Coluna avatar no customer_portal_access ──
ALTER TABLE `customer_portal_access`
    ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) DEFAULT NULL AFTER `two_factor_expires_at`;

-- ── 3. Índice para sessões ativas (já devem existir mas garantir) ──
-- A tabela customer_portal_sessions já foi criada na Fase 6
-- Adicionar índice para expiração (limpeza automática)
ALTER TABLE `customer_portal_sessions`
    ADD COLUMN IF NOT EXISTS `expires_at` DATETIME DEFAULT NULL AFTER `last_activity`;

-- ── 4. Configs novas da Fase 7 ──
INSERT IGNORE INTO `customer_portal_config` (`config_key`, `config_value`) VALUES
    ('enable_2fa', '0'),
    ('enable_avatar_upload', '0'),
    ('rate_limit_portal_max', '30'),
    ('rate_limit_portal_window', '60');
