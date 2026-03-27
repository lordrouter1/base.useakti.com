-- ============================================================
-- Migration CORRETIVA SERVIDOR: Todos os pendentes consolidados
-- Data: 2026-03-26 18:30
-- Arquivo: update_202603261830_fix_definitivo_servidor.sql
--
-- Este arquivo consolida TODAS as atualizações pendentes que
-- foram executadas no local mas não no servidor.
-- Inclui correções para os erros dos arquivos:
--   - update_202603261200_nfe_tables.sql (FK type mismatch)
--   - update_202603281200_fase4_ibptax_integracoes.sql (colunas inexistentes)
-- E TODAS as migrations de sql/prontos/ que estavam pendentes.
--
-- IDEMPOTENTE: pode ser executado múltiplas vezes sem erro.
-- ============================================================


-- ══════════════════════════════════════════════════════════════
-- PARTE A: PORTAL DO CLIENTE (prontos pendentes)
-- Arquivos originais:
--   update_202506251200_portal_fases345.sql
--   update_202506261200_portal_admin.sql
--   update_202503251200_portal_fase7.sql
--   update_202603241000_portal_cliente.sql
--   update_202603241200_portal_fase1a.sql
--   update_202603251200_portal_approval_columns.sql
-- ══════════════════════════════════════════════════════════════

-- A.1 Portal Autenticação (Fase 1)
CREATE TABLE IF NOT EXISTS `customer_portal_access` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `magic_token` VARCHAR(128) DEFAULT NULL,
    `magic_token_expires_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `failed_attempts` INT NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `lang` VARCHAR(10) NOT NULL DEFAULT 'pt-br',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_portal_email` (`email`),
    UNIQUE KEY `uq_portal_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A.1a Reset de senha (Fase 1A)
ALTER TABLE `customer_portal_access`
    ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(128) DEFAULT NULL COMMENT 'Token de recuperação de senha',
    ADD COLUMN IF NOT EXISTS `reset_token_expires_at` DATETIME DEFAULT NULL COMMENT 'Validade do token de reset';

-- A.2 Sessões do Portal (estrutura Fase 1 + Fase 6)
CREATE TABLE IF NOT EXISTS `customer_portal_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `session_token` VARCHAR(128) NOT NULL,
    `device_info` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colunas adicionais da Fase 6/7 em customer_portal_sessions
ALTER TABLE `customer_portal_sessions`
    ADD COLUMN IF NOT EXISTS `access_id` INT UNSIGNED DEFAULT NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `session_id` VARCHAR(128) DEFAULT NULL AFTER `session_token`,
    ADD COLUMN IF NOT EXISTS `user_agent` VARCHAR(500) DEFAULT NULL AFTER `ip_address`,
    ADD COLUMN IF NOT EXISTS `last_activity` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `user_agent`;

CREATE INDEX IF NOT EXISTS `idx_access_sessions` ON `customer_portal_sessions` (`access_id`);
CREATE INDEX IF NOT EXISTS `idx_session_id` ON `customer_portal_sessions` (`session_id`);
CREATE INDEX IF NOT EXISTS `idx_last_activity` ON `customer_portal_sessions` (`last_activity`);

-- A.3 Mensagens do Portal (Fase 5)
CREATE TABLE IF NOT EXISTS `customer_portal_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `sender_type` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `sender_id` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `attachment_path` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_customer_messages` (`customer_id`, `created_at`),
    INDEX `idx_order_messages` (`order_id`, `created_at`),
    INDEX `idx_unread` (`customer_id`, `sender_type`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A.4 Documentos do Portal (Fase 5)
CREATE TABLE IF NOT EXISTS `customer_portal_documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50) DEFAULT NULL,
    `file_size` INT DEFAULT NULL,
    `uploaded_by` ENUM('admin','customer') NOT NULL DEFAULT 'admin',
    `uploaded_by_id` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_doc_customer` (`customer_id`),
    INDEX `idx_doc_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A.5 Configurações do Portal
CREATE TABLE IF NOT EXISTS `customer_portal_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL,
    `config_value` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_portal_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `customer_portal_config` (`config_key`, `config_value`) VALUES
    ('portal_enabled', '1'),
    ('require_password', '0'),
    ('allow_self_register', '0'),
    ('allow_order_approval', '1'),
    ('allow_messages', '1'),
    ('magic_link_expiry_hours', '24'),
    ('session_timeout_minutes', '120'),
    ('allow_order_creation', '0'),
    ('allow_document_upload', '0'),
    ('show_financial', '1'),
    ('show_tracking', '1'),
    ('enable_2fa', '0'),
    ('enable_avatar_upload', '0'),
    ('rate_limit_portal_max', '30'),
    ('rate_limit_portal_window', '60');

-- A.6 2FA + Avatar (Fase 7) em customer_portal_access
ALTER TABLE `customer_portal_access`
    ADD COLUMN IF NOT EXISTS `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lang`,
    ADD COLUMN IF NOT EXISTS `two_factor_code` VARCHAR(6) DEFAULT NULL AFTER `two_factor_enabled`,
    ADD COLUMN IF NOT EXISTS `two_factor_expires_at` DATETIME DEFAULT NULL AFTER `two_factor_code`,
    ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) DEFAULT NULL AFTER `two_factor_expires_at`;

-- A.7 Colunas de aprovação em orders
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `customer_approval_status` ENUM('pendente','aprovado','recusado') DEFAULT NULL COMMENT 'Status de aprovação do cliente',
    ADD COLUMN IF NOT EXISTS `customer_approval_at` DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `customer_approval_ip` VARCHAR(45) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `customer_approval_notes` TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `portal_origin` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=pedido veio do portal';

-- A.8 Rastreamento em orders
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `tracking_code` VARCHAR(100) DEFAULT NULL COMMENT 'Código de rastreamento',
    ADD COLUMN IF NOT EXISTS `tracking_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL de rastreamento';


-- ══════════════════════════════════════════════════════════════
-- PARTE B: COMISSÕES (prontos pendentes)
-- Arquivos originais:
--   update_202603231500_order_seller_commission.sql
--   update_202603231600_commission_release_criteria.sql
-- ══════════════════════════════════════════════════════════════

-- B.1 Vendedor no pedido
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `seller_id` INT NULL COMMENT 'FK users.id (vendedor/comissionado)' AFTER `assigned_to`;

CREATE INDEX IF NOT EXISTS `idx_seller` ON `orders` (`seller_id`);

-- B.2 Critério de liberação de comissão
-- (Verifica se a tabela existe antes)
SET @comissao_config_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'comissao_config');

SET @sql_cc = IF(@comissao_config_exists > 0, 
    'INSERT IGNORE INTO comissao_config (config_key, config_value, descricao) VALUES (''criterio_liberacao_comissao'', ''pagamento_total'', ''Criterio de liberacao: sem_confirmacao, primeira_parcela, pagamento_total'')',
    'SELECT 1');
PREPARE stmt FROM @sql_cc;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- B.3 ENUM de status em comissoes_registradas (adicionar aguardando_pagamento)
SET @comissao_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'comissoes_registradas');

SET @sql_comissao = IF(@comissao_exists > 0,
    'ALTER TABLE comissoes_registradas MODIFY COLUMN status ENUM(''calculada'',''aprovada'',''aguardando_pagamento'',''paga'',''cancelada'') NOT NULL DEFAULT ''calculada''',
    'SELECT 1');
PREPARE stmt FROM @sql_comissao;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ══════════════════════════════════════════════════════════════
-- PARTE C: AUDITORIA FINANCEIRA (prontos pendentes)
-- Arquivo original: update_202506151200_audit_reason_and_report.sql
-- ══════════════════════════════════════════════════════════════

-- C.1 Criar tabela se não existir (no original só fazia ALTER)
CREATE TABLE IF NOT EXISTS `financial_audit_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT DEFAULT NULL COMMENT 'Usuário que realizou a ação',
    `action` VARCHAR(50) NOT NULL COMMENT 'Tipo da ação: create, update, delete, confirm, reverse',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'Tipo da entidade: transaction, installment, etc.',
    `entity_id` INT DEFAULT NULL COMMENT 'ID do registro afetado',
    `old_values` TEXT DEFAULT NULL COMMENT 'Valores anteriores (JSON)',
    `new_values` TEXT DEFAULT NULL COMMENT 'Valores novos (JSON)',
    `reason` VARCHAR(500) DEFAULT NULL COMMENT 'Motivo informado pelo usuário',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fal_user` (`user_id`),
    INDEX `idx_fal_entity` (`entity_type`, `entity_id`),
    INDEX `idx_fal_created_action` (`created_at` DESC, `action`),
    INDEX `idx_fal_entity_type` (`entity_type`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de auditoria de operações financeiras';

-- C.2 Adicionar coluna reason se tabela já existia sem ela
ALTER TABLE `financial_audit_log`
    ADD COLUMN IF NOT EXISTS `reason` VARCHAR(500) DEFAULT NULL COMMENT 'Motivo informado pelo usuário' AFTER `new_values`;


-- ══════════════════════════════════════════════════════════════
-- PARTE D: FASE 2 — CAMPOS FISCAIS (prontos pendentes)
-- Arquivo original: update_202603271200_fase2_fiscal.sql
-- ══════════════════════════════════════════════════════════════

-- D.1 Campos fiscais em products
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `fiscal_ncm` VARCHAR(10) DEFAULT NULL COMMENT 'NCM' AFTER `use_stock_control`,
    ADD COLUMN IF NOT EXISTS `fiscal_cest` VARCHAR(10) DEFAULT NULL COMMENT 'CEST' AFTER `fiscal_ncm`,
    ADD COLUMN IF NOT EXISTS `fiscal_cfop` VARCHAR(10) DEFAULT NULL COMMENT 'CFOP' AFTER `fiscal_cest`,
    ADD COLUMN IF NOT EXISTS `fiscal_cfop_interestadual` VARCHAR(4) DEFAULT NULL COMMENT 'CFOP interestadual' AFTER `fiscal_cfop`,
    ADD COLUMN IF NOT EXISTS `fiscal_cst_icms` VARCHAR(5) DEFAULT NULL AFTER `fiscal_cfop_interestadual`,
    ADD COLUMN IF NOT EXISTS `fiscal_csosn` VARCHAR(5) DEFAULT NULL AFTER `fiscal_cst_icms`,
    ADD COLUMN IF NOT EXISTS `fiscal_cst_pis` VARCHAR(5) DEFAULT NULL AFTER `fiscal_csosn`,
    ADD COLUMN IF NOT EXISTS `fiscal_cst_cofins` VARCHAR(5) DEFAULT NULL AFTER `fiscal_cst_pis`,
    ADD COLUMN IF NOT EXISTS `fiscal_cst_ipi` VARCHAR(5) DEFAULT NULL AFTER `fiscal_cst_cofins`,
    ADD COLUMN IF NOT EXISTS `fiscal_origem` VARCHAR(2) DEFAULT '0' AFTER `fiscal_cst_ipi`,
    ADD COLUMN IF NOT EXISTS `fiscal_unidade` VARCHAR(10) DEFAULT 'UN' AFTER `fiscal_origem`,
    ADD COLUMN IF NOT EXISTS `fiscal_ean` VARCHAR(14) DEFAULT NULL AFTER `fiscal_unidade`,
    ADD COLUMN IF NOT EXISTS `fiscal_aliq_icms` DECIMAL(5,2) DEFAULT NULL AFTER `fiscal_ean`,
    ADD COLUMN IF NOT EXISTS `fiscal_icms_reducao_bc` DECIMAL(5,2) DEFAULT NULL AFTER `fiscal_aliq_icms`,
    ADD COLUMN IF NOT EXISTS `fiscal_aliq_ipi` DECIMAL(5,2) DEFAULT NULL AFTER `fiscal_icms_reducao_bc`,
    ADD COLUMN IF NOT EXISTS `fiscal_aliq_pis` DECIMAL(5,4) DEFAULT NULL AFTER `fiscal_aliq_ipi`,
    ADD COLUMN IF NOT EXISTS `fiscal_aliq_cofins` DECIMAL(5,4) DEFAULT NULL AFTER `fiscal_aliq_pis`,
    ADD COLUMN IF NOT EXISTS `fiscal_beneficio` VARCHAR(20) DEFAULT NULL AFTER `fiscal_aliq_cofins`,
    ADD COLUMN IF NOT EXISTS `fiscal_info_adicional` TEXT DEFAULT NULL AFTER `fiscal_beneficio`;

CREATE INDEX IF NOT EXISTS `idx_product_ncm` ON `products` (`fiscal_ncm`);

-- D.2 Shipping e tipo de venda em orders
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `shipping_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor do frete' AFTER `discount`,
    ADD COLUMN IF NOT EXISTS `sale_type` VARCHAR(20) DEFAULT 'presencial' COMMENT 'Tipo de venda' AFTER `shipping_type`;


-- ══════════════════════════════════════════════════════════════
-- PARTE E: NF-e TABELAS (CORRIGIDAS)
-- Arquivo original: update_202603261200_nfe_tables.sql
-- CORREÇÃO: FKs usam INT signed (match com nfe_documents.id int(11))
-- ══════════════════════════════════════════════════════════════

-- E.1 nfe_credentials
CREATE TABLE IF NOT EXISTS `nfe_credentials` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cnpj`                  VARCHAR(18)  DEFAULT NULL,
    `ie`                    VARCHAR(20)  DEFAULT NULL,
    `razao_social`          VARCHAR(255) DEFAULT NULL,
    `nome_fantasia`         VARCHAR(255) DEFAULT NULL,
    `crt`                   TINYINT      NOT NULL DEFAULT 1,
    `uf`                    CHAR(2)      DEFAULT NULL,
    `cod_municipio`         VARCHAR(7)   DEFAULT NULL,
    `municipio`             VARCHAR(100) DEFAULT NULL,
    `logradouro`            VARCHAR(255) DEFAULT NULL,
    `numero`                VARCHAR(20)  DEFAULT NULL,
    `complemento`           VARCHAR(100) DEFAULT NULL,
    `bairro`                VARCHAR(100) DEFAULT NULL,
    `cep`                   VARCHAR(10)  DEFAULT NULL,
    `telefone`              VARCHAR(20)  DEFAULT NULL,
    `certificate_path`      VARCHAR(500) DEFAULT NULL,
    `certificate_password`  TEXT         DEFAULT NULL,
    `certificate_expiry`    DATE         DEFAULT NULL,
    `environment`           VARCHAR(20)  NOT NULL DEFAULT 'homologacao',
    `serie_nfe`             INT UNSIGNED NOT NULL DEFAULT 1,
    `proximo_numero`        INT UNSIGNED NOT NULL DEFAULT 1,
    `serie_nfce`            INT UNSIGNED NOT NULL DEFAULT 1,
    `proximo_numero_nfce`   INT UNSIGNED NOT NULL DEFAULT 1,
    `csc_id`                VARCHAR(10)  DEFAULT NULL,
    `csc_token`             VARCHAR(100) DEFAULT NULL,
    `tp_emis`               TINYINT      NOT NULL DEFAULT 1,
    `contingencia_justificativa` TEXT    DEFAULT NULL,
    `contingencia_ativada_em`    DATETIME DEFAULT NULL,
    `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro padrão
INSERT INTO `nfe_credentials` (`id`) VALUES (1)
ON DUPLICATE KEY UPDATE `id` = 1;

-- Campos faltantes
ALTER TABLE `nfe_credentials`
    ADD COLUMN IF NOT EXISTS `serie_nfce` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `proximo_numero`,
    ADD COLUMN IF NOT EXISTS `proximo_numero_nfce` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `serie_nfce`,
    ADD COLUMN IF NOT EXISTS `tp_emis` TINYINT NOT NULL DEFAULT 1 AFTER `csc_token`,
    ADD COLUMN IF NOT EXISTS `contingencia_justificativa` TEXT DEFAULT NULL AFTER `tp_emis`,
    ADD COLUMN IF NOT EXISTS `contingencia_ativada_em` DATETIME DEFAULT NULL AFTER `contingencia_justificativa`;

-- E.2 nfe_documents
CREATE TABLE IF NOT EXISTS `nfe_documents` (
    `id`                    INT NOT NULL AUTO_INCREMENT,
    `order_id`              INT DEFAULT NULL,
    `modelo`                TINYINT      NOT NULL DEFAULT 55,
    `numero`                INT NOT NULL,
    `serie`                 INT NOT NULL DEFAULT 1,
    `chave`                 VARCHAR(44)  DEFAULT NULL,
    `protocolo`             VARCHAR(20)  DEFAULT NULL,
    `recibo`                VARCHAR(20)  DEFAULT NULL,
    `status`                ENUM('rascunho','processando','autorizada','rejeitada','cancelada','denegada','corrigida','inutilizada') NOT NULL DEFAULT 'rascunho',
    `status_sefaz`          VARCHAR(10)  DEFAULT NULL,
    `motivo_sefaz`          TEXT         DEFAULT NULL,
    `natureza_op`           VARCHAR(100) NOT NULL DEFAULT 'VENDA DE MERCADORIA',
    `valor_total`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_produtos`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_desconto`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_frete`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `dest_cnpj_cpf`         VARCHAR(18)  DEFAULT NULL,
    `dest_nome`             VARCHAR(255) DEFAULT NULL,
    `dest_ie`               VARCHAR(20)  DEFAULT NULL,
    `dest_uf`               CHAR(2)      DEFAULT NULL,
    `tp_emis`               TINYINT      NOT NULL DEFAULT 1,
    `contingencia_justificativa` TEXT    DEFAULT NULL,
    `xml_envio`             LONGTEXT     DEFAULT NULL,
    `xml_autorizado`        LONGTEXT     DEFAULT NULL,
    `xml_cancelamento`      LONGTEXT     DEFAULT NULL,
    `xml_correcao`          LONGTEXT     DEFAULT NULL,
    `xml_path`              VARCHAR(500) DEFAULT NULL,
    `danfe_path`            VARCHAR(500) DEFAULT NULL,
    `cancel_protocolo`      VARCHAR(20)  DEFAULT NULL,
    `cancel_motivo`         TEXT         DEFAULT NULL,
    `cancel_date`           DATETIME     DEFAULT NULL,
    `correcao_texto`        TEXT         DEFAULT NULL,
    `correcao_seq`          INT UNSIGNED NOT NULL DEFAULT 0,
    `correcao_date`         DATETIME     DEFAULT NULL,
    `emitted_at`            DATETIME     DEFAULT NULL,
    `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_nfe_order_id` (`order_id`),
    INDEX `idx_nfe_chave` (`chave`),
    INDEX `idx_nfe_status` (`status`),
    INDEX `idx_nfe_numero_serie` (`numero`, `serie`),
    INDEX `idx_nfe_modelo` (`modelo`),
    INDEX `idx_nfe_emitted_at` (`emitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos faltantes em nfe_documents
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `modelo` TINYINT NOT NULL DEFAULT 55 AFTER `order_id`,
    ADD COLUMN IF NOT EXISTS `tp_emis` TINYINT NOT NULL DEFAULT 1 AFTER `dest_uf`,
    ADD COLUMN IF NOT EXISTS `contingencia_justificativa` TEXT DEFAULT NULL AFTER `tp_emis`,
    ADD COLUMN IF NOT EXISTS `xml_path` VARCHAR(500) DEFAULT NULL AFTER `xml_correcao`,
    ADD COLUMN IF NOT EXISTS `danfe_path` VARCHAR(500) DEFAULT NULL AFTER `xml_path`,
    ADD COLUMN IF NOT EXISTS `cancel_xml_path` VARCHAR(500) DEFAULT NULL AFTER `danfe_path`;

-- Valores fiscais em nfe_documents (Fase 2)
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `valor_icms` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `valor_frete`,
    ADD COLUMN IF NOT EXISTS `valor_pis` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `valor_icms`,
    ADD COLUMN IF NOT EXISTS `valor_cofins` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `valor_pis`,
    ADD COLUMN IF NOT EXISTS `valor_ipi` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `valor_cofins`,
    ADD COLUMN IF NOT EXISTS `valor_tributos_aprox` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `valor_ipi`;

-- Corrigir ENUM status (adicionar inutilizada)
ALTER TABLE `nfe_documents`
    MODIFY COLUMN `status` ENUM('rascunho','processando','autorizada','rejeitada','cancelada','denegada','corrigida','inutilizada') NOT NULL DEFAULT 'rascunho';

-- Índices
CREATE INDEX IF NOT EXISTS `idx_nfe_order_id`     ON `nfe_documents` (`order_id`);
CREATE INDEX IF NOT EXISTS `idx_nfe_chave`        ON `nfe_documents` (`chave`);
CREATE INDEX IF NOT EXISTS `idx_nfe_status`       ON `nfe_documents` (`status`);
CREATE INDEX IF NOT EXISTS `idx_nfe_numero_serie`  ON `nfe_documents` (`numero`, `serie`);
CREATE INDEX IF NOT EXISTS `idx_nfe_modelo`       ON `nfe_documents` (`modelo`);
CREATE INDEX IF NOT EXISTS `idx_nfe_emitted_at`   ON `nfe_documents` (`emitted_at`);
CREATE INDEX IF NOT EXISTS `idx_nfe_doc_status_emitted` ON `nfe_documents` (`status`, `emitted_at`);
CREATE INDEX IF NOT EXISTS `idx_nfe_doc_status_created` ON `nfe_documents` (`status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_nfe_doc_order`    ON `nfe_documents` (`order_id`);

-- E.3 nfe_logs
CREATE TABLE IF NOT EXISTS `nfe_logs` (
    `id`                INT NOT NULL AUTO_INCREMENT,
    `nfe_document_id`   INT DEFAULT NULL COMMENT 'FK para nfe_documents.id',
    `order_id`          INT DEFAULT NULL,
    `action`            VARCHAR(50)  NOT NULL DEFAULT 'info',
    `status`            VARCHAR(20)  NOT NULL DEFAULT 'info',
    `code_sefaz`        VARCHAR(10)  DEFAULT NULL,
    `message`           TEXT         DEFAULT NULL,
    `xml_request`       LONGTEXT     DEFAULT NULL,
    `xml_response`      LONGTEXT     DEFAULT NULL,
    `user_id`           INT DEFAULT NULL,
    `ip_address`        VARCHAR(45)  DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_nfelog_document_id` (`nfe_document_id`),
    INDEX `idx_nfelog_order_id` (`order_id`),
    INDEX `idx_nfelog_action` (`action`),
    INDEX `idx_nfelog_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- E.4 nfe_document_items (CORRIGIDA — INT signed para FK)
CREATE TABLE IF NOT EXISTS `nfe_document_items` (
    `id`                INT NOT NULL AUTO_INCREMENT,
    `nfe_document_id`   INT NOT NULL COMMENT 'FK para nfe_documents.id',
    `nItem`             INT UNSIGNED NOT NULL,
    `cProd`             VARCHAR(60)  DEFAULT NULL,
    `xProd`             VARCHAR(255) DEFAULT NULL,
    `ncm`               VARCHAR(8)   DEFAULT NULL,
    `cest`              VARCHAR(7)   DEFAULT NULL,
    `cfop`              VARCHAR(4)   NOT NULL DEFAULT '5102',
    `uCom`              VARCHAR(6)   DEFAULT 'UN',
    `qCom`              DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `vUnCom`            DECIMAL(21,10) NOT NULL DEFAULT 0.0000000000,
    `vProd`             DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `vDesc`             DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `vFrete`            DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `origem`            TINYINT      NOT NULL DEFAULT 0,
    `icms_cst`          VARCHAR(3)   DEFAULT NULL,
    `icms_csosn`        VARCHAR(3)   DEFAULT NULL,
    `icms_vbc`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `icms_aliquota`     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `icms_valor`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `icms_reducao_bc`   DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `pis_cst`           VARCHAR(2)   DEFAULT NULL,
    `pis_vbc`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `pis_aliquota`      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `pis_valor`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `cofins_cst`        VARCHAR(2)   DEFAULT NULL,
    `cofins_vbc`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `cofins_aliquota`   DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `cofins_valor`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `ipi_cst`           VARCHAR(2)   DEFAULT NULL,
    `ipi_vbc`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `ipi_aliquota`      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `ipi_valor`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `vTotTrib`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_nfeitem_document_id` (`nfe_document_id`),
    INDEX `idx_nfeitem_ncm` (`ncm`),
    INDEX `idx_nfeitem_cfop` (`cfop`),
    CONSTRAINT `fk_nfe_items_document`
        FOREIGN KEY (`nfe_document_id`) REFERENCES `nfe_documents`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- E.5 nfe_correction_history (CORRIGIDA — INT signed para FK)
CREATE TABLE IF NOT EXISTS `nfe_correction_history` (
    `id`                INT NOT NULL AUTO_INCREMENT,
    `nfe_document_id`   INT NOT NULL COMMENT 'FK para nfe_documents.id',
    `seq_evento`        INT UNSIGNED NOT NULL,
    `texto_correcao`    TEXT         NOT NULL,
    `protocolo`         VARCHAR(20)  DEFAULT NULL,
    `code_sefaz`        VARCHAR(10)  DEFAULT NULL,
    `motivo_sefaz`      TEXT         DEFAULT NULL,
    `xml_correcao`      LONGTEXT     DEFAULT NULL,
    `user_id`           INT UNSIGNED DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_nfecce_document_id` (`nfe_document_id`),
    INDEX `idx_nfecce_seq` (`nfe_document_id`, `seq_evento`),
    CONSTRAINT `fk_nfe_cce_document`
        FOREIGN KEY (`nfe_document_id`) REFERENCES `nfe_documents`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ══════════════════════════════════════════════════════════════
-- PARTE F: FASE 4 — IBPTax + Notificações + Integrações (CORRIGIDO)
-- Arquivo original: update_202603281200_fase4_ibptax_integracoes.sql
-- CORREÇÃO: INSERT em company_settings sem colunas inexistentes
-- ══════════════════════════════════════════════════════════════

-- F.1 Tabela tax_ibptax
CREATE TABLE IF NOT EXISTS `tax_ibptax` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ncm` VARCHAR(10) NOT NULL,
    `ex` VARCHAR(5) DEFAULT NULL,
    `tipo` TINYINT NOT NULL DEFAULT 0,
    `descricao` VARCHAR(500) NOT NULL DEFAULT '',
    `aliq_nacional` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    `aliq_importados` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    `aliq_estadual` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    `aliq_municipal` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    `vigencia_inicio` DATE DEFAULT NULL,
    `vigencia_fim` DATE DEFAULT NULL,
    `versao` VARCHAR(20) DEFAULT NULL,
    `fonte` VARCHAR(100) DEFAULT 'IBPT',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_ibptax_ncm_ex` (`ncm`, `ex`),
    INDEX `idx_ibptax_ncm` (`ncm`),
    INDEX `idx_ibptax_vigencia` (`vigencia_inicio`, `vigencia_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F.2 Tabela notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `link` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_notif_user` (`user_id`, `is_read`),
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F.3 Campos em order_installments
ALTER TABLE `order_installments`
    ADD COLUMN IF NOT EXISTS `nfe_faturada` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_confirmed`,
    ADD COLUMN IF NOT EXISTS `nfe_document_id` INT UNSIGNED DEFAULT NULL AFTER `nfe_faturada`;

CREATE INDEX IF NOT EXISTS `idx_installment_nfe` ON `order_installments` (`nfe_document_id`);

-- F.4 Configurações NF-e (CORRIGIDO — sem colunas setting_type e description)
INSERT IGNORE INTO `company_settings` (`setting_key`, `setting_value`)
VALUES 
    ('nfe_auto_email', '1'),
    ('nfe_ibptax_enabled', '1'),
    ('nfe_pipeline_stage_emit', ''),
    ('nfe_stock_auto_debit', '1'),
    ('nfe_financial_auto_faturar', '1');


-- ══════════════════════════════════════════════════════════════
-- PARTE G: CAMPOS NF-e EM ORDERS
-- ══════════════════════════════════════════════════════════════

ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `nfe_id` INT DEFAULT NULL COMMENT 'FK para nfe_documents.id',
    ADD COLUMN IF NOT EXISTS `nfe_status` VARCHAR(30) DEFAULT NULL COMMENT 'Status da NF-e vinculada',
    ADD COLUMN IF NOT EXISTS `nf_number` VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `nf_series` VARCHAR(5) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `nf_status` ENUM('nao_emitida','emitida','cancelada') DEFAULT 'nao_emitida',
    ADD COLUMN IF NOT EXISTS `nf_access_key` VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `nf_notes` TEXT DEFAULT NULL;


-- ══════════════════════════════════════════════════════════════
-- FIM — SQL Consolidado para Servidor
-- Execute no servidor de produção via phpMyAdmin ou mysql CLI.
-- ══════════════════════════════════════════════════════════════
