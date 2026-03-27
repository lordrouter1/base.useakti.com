-- ══════════════════════════════════════════════════════════════
-- Migration: Fase 5 — Funcionalidades Novas do Módulo NF-e
-- Data: 29/03/2026
-- Itens: NFC-e, Contingência, Download Lote, SPED, SINTEGRA,
--        Livros de Registro, Backup XML
-- ══════════════════════════════════════════════════════════════

-- ──────────────────────────────────────────────
-- FASE5-01: Suporte a NFC-e (Modelo 65)
-- Garantir colunas CSC na tabela de credenciais
-- ──────────────────────────────────────────────

ALTER TABLE `nfe_credentials`
    ADD COLUMN IF NOT EXISTS `serie_nfce` INT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Série da NFC-e' AFTER `proximo_numero`,
    ADD COLUMN IF NOT EXISTS `proximo_numero_nfce` INT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Próximo número NFC-e' AFTER `serie_nfce`,
    ADD COLUMN IF NOT EXISTS `csc_id` VARCHAR(10) DEFAULT NULL
        COMMENT 'ID do CSC para QR Code NFC-e' AFTER `proximo_numero_nfce`,
    ADD COLUMN IF NOT EXISTS `csc_token` VARCHAR(50) DEFAULT NULL
        COMMENT 'Token CSC para QR Code NFC-e' AFTER `csc_id`;

-- Garantir coluna modelo na tabela de documentos (pode já existir)
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `modelo` INT NOT NULL DEFAULT 55
        COMMENT '55=NF-e, 65=NFC-e' AFTER `order_id`,
    ADD COLUMN IF NOT EXISTS `qrcode_url` TEXT DEFAULT NULL
        COMMENT 'URL QR Code da NFC-e' AFTER `xml_correcao`;

-- Índice para busca por modelo
CREATE INDEX IF NOT EXISTS `idx_nfe_documents_modelo` ON `nfe_documents` (`modelo`);

-- ──────────────────────────────────────────────
-- FASE5-02: Contingência Automática
-- ──────────────────────────────────────────────

ALTER TABLE `nfe_credentials`
    ADD COLUMN IF NOT EXISTS `tp_emis` TINYINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT '1=Normal, 6=SVC-AN, 7=SVC-RS, 9=Offline NFC-e' AFTER `environment`,
    ADD COLUMN IF NOT EXISTS `contingencia_justificativa` TEXT DEFAULT NULL
        COMMENT 'Justificativa da contingência' AFTER `tp_emis`,
    ADD COLUMN IF NOT EXISTS `contingencia_ativada_em` DATETIME DEFAULT NULL
        COMMENT 'Data/hora de ativação da contingência' AFTER `contingencia_justificativa`,
    ADD COLUMN IF NOT EXISTS `contingencia_auto_enabled` TINYINT(1) NOT NULL DEFAULT 1
        COMMENT 'Habilita ativação automática de contingência' AFTER `contingencia_ativada_em`;

-- Tabela de log de contingência
CREATE TABLE IF NOT EXISTS `nfe_contingency_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `credential_id` INT UNSIGNED DEFAULT NULL,
    `tipo` ENUM('ativacao','desativacao','sincronizacao') NOT NULL,
    `tp_emis_anterior` TINYINT UNSIGNED DEFAULT NULL,
    `tp_emis_novo` TINYINT UNSIGNED DEFAULT NULL,
    `justificativa` TEXT DEFAULT NULL,
    `nfes_pendentes` INT UNSIGNED DEFAULT 0 COMMENT 'Qtd de NF-e pendentes de sincronização',
    `nfes_sincronizadas` INT UNSIGNED DEFAULT 0 COMMENT 'Qtd sincronizadas neste evento',
    `user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contingency_log_tipo` (`tipo`),
    INDEX `idx_contingency_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de ativações/desativações de contingência NF-e';

-- Coluna para marcar NF-e emitidas em contingência
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `emitida_contingencia` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Se emitida em contingência' AFTER `tp_emis`,
    ADD COLUMN IF NOT EXISTS `contingencia_sincronizada` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Se já foi sincronizada após contingência' AFTER `emitida_contingencia`;

-- ──────────────────────────────────────────────
-- FASE5-03: Download XML em Lote (ZIP)
-- Nenhuma tabela necessária — funcionalidade no controller
-- ──────────────────────────────────────────────

-- ──────────────────────────────────────────────
-- FASE5-04/05/06/07: SPED Fiscal, SINTEGRA, Livros de Registro
-- Tabela de configuração fiscal para geração de arquivos
-- ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `nfe_fiscal_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL,
    `config_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_fiscal_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configurações para geração de arquivos fiscais (SPED, SINTEGRA)';

-- Inserir configurações padrão
INSERT IGNORE INTO `nfe_fiscal_config` (`config_key`, `config_value`, `description`) VALUES
    ('sped_finalidade', '0', 'Finalidade do arquivo SPED: 0=Remessa, 1=Retificação'),
    ('sped_perfil', 'A', 'Perfil SPED: A=Completo, B=Intermediário, C=Simplificado'),
    ('sped_atividade', '0', 'Atividade: 0=Industrial/Equiparado, 1=Outros'),
    ('sped_cod_convenio', '', 'Código do convênio (se aplicável)'),
    ('sintegra_cod_finalidade', '1', 'Código da finalidade SINTEGRA: 1=Normal, 2=Retificação, 3=Dados parciais, 5=Desfazimento'),
    ('sintegra_cod_natureza', '3', 'Código natureza SINTEGRA: 1=Interestad., 2=Intermunic., 3=Ambas'),
    ('livro_saidas_modelo', '2', 'Modelo do livro de saídas: 2=P2'),
    ('livro_entradas_modelo', '1', 'Modelo do livro de entradas: 1=P1');

-- ──────────────────────────────────────────────
-- FASE5-08: Backup Automático de XMLs
-- ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `nfe_backup_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tipo` ENUM('local','s3','ftp') NOT NULL DEFAULT 'local',
    `periodo_inicio` DATE DEFAULT NULL,
    `periodo_fim` DATE DEFAULT NULL,
    `total_arquivos` INT UNSIGNED NOT NULL DEFAULT 0,
    `tamanho_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `arquivo_destino` VARCHAR(500) DEFAULT NULL COMMENT 'Path ou URL do backup',
    `status` ENUM('executando','sucesso','erro') NOT NULL DEFAULT 'executando',
    `mensagem_erro` TEXT DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    INDEX `idx_backup_log_status` (`status`),
    INDEX `idx_backup_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de backups de XMLs NF-e';

-- Configurações de backup externo
INSERT IGNORE INTO `nfe_fiscal_config` (`config_key`, `config_value`, `description`) VALUES
    ('backup_auto_enabled', '0', 'Habilita backup automático diário de XMLs'),
    ('backup_tipo', 'local', 'Tipo de backup: local, s3, ftp'),
    ('backup_s3_bucket', '', 'Nome do bucket S3'),
    ('backup_s3_region', '', 'Região AWS S3'),
    ('backup_s3_key', '', 'AWS Access Key'),
    ('backup_s3_secret', '', 'AWS Secret Key'),
    ('backup_ftp_host', '', 'Host FTP para backup'),
    ('backup_ftp_user', '', 'Usuário FTP'),
    ('backup_ftp_password', '', 'Senha FTP'),
    ('backup_ftp_path', '/backups/nfe/', 'Diretório FTP para backup'),
    ('backup_retention_days', '365', 'Dias de retenção de backups');

-- ──────────────────────────────────────────────
-- FIM da Migration Fase 5
-- ──────────────────────────────────────────────
