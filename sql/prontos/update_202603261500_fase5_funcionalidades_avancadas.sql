-- ═══════════════════════════════════════════════════════════════
-- FASE 5 — Funcionalidades Avançadas (Módulo NF-e)
-- Data: 2026-03-26
-- Descrição: Migration para funcionalidades avançadas do módulo
--            fiscal: fila de emissão, webhooks, auditoria,
--            multi-filial, tabelas fiscais de referência,
--            manifestação do destinatário, DistDFe.
-- ═══════════════════════════════════════════════════════════════

-- ══════════════════════════════════════════════════════════════
-- A) FILA DE EMISSÃO ASSÍNCRONA (5.4)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_queue (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT DEFAULT NULL,
    modelo          TINYINT DEFAULT 55 COMMENT '55=NF-e, 65=NFC-e',
    status          ENUM('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
    priority        TINYINT DEFAULT 5 COMMENT '1=alta, 5=normal, 10=baixa',
    attempts        INT DEFAULT 0,
    max_attempts    INT DEFAULT 3,
    nfe_document_id INT DEFAULT NULL COMMENT 'Preenchido após emissão bem-sucedida',
    error_message   TEXT DEFAULT NULL,
    scheduled_at    DATETIME DEFAULT NULL COMMENT 'Se NULL, processa imediatamente',
    started_at      DATETIME DEFAULT NULL,
    completed_at    DATETIME DEFAULT NULL,
    user_id         INT DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nfe_queue_status (status),
    INDEX idx_nfe_queue_priority (priority),
    INDEX idx_nfe_queue_scheduled (scheduled_at),
    INDEX idx_nfe_queue_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- B) WEBHOOKS PARA INTEGRAÇÕES EXTERNAS (5.6)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_webhooks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    url             VARCHAR(500) NOT NULL,
    secret          VARCHAR(255) DEFAULT NULL COMMENT 'Chave para assinatura HMAC',
    events          TEXT NOT NULL COMMENT 'JSON array de eventos assinados',
    headers         TEXT DEFAULT NULL COMMENT 'JSON de headers customizados',
    is_active       TINYINT(1) DEFAULT 1,
    retry_count     INT DEFAULT 3,
    timeout_seconds INT DEFAULT 10,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nfe_webhooks_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nfe_webhook_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id      INT NOT NULL,
    event_name      VARCHAR(100) NOT NULL,
    payload         TEXT DEFAULT NULL COMMENT 'JSON do payload enviado',
    response_code   INT DEFAULT NULL,
    response_body   TEXT DEFAULT NULL,
    status          ENUM('success','failed','pending','retrying') DEFAULT 'pending',
    attempt         INT DEFAULT 1,
    error_message   TEXT DEFAULT NULL,
    sent_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_logs_webhook (webhook_id),
    INDEX idx_webhook_logs_event (event_name),
    INDEX idx_webhook_logs_status (status),
    FOREIGN KEY (webhook_id) REFERENCES nfe_webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- C) AUDITORIA COMPLETA DE ACESSOS (5.8)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_audit_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT DEFAULT NULL,
    user_name       VARCHAR(100) DEFAULT NULL,
    action          VARCHAR(50) NOT NULL COMMENT 'view, emit, cancel, correct, download_xml, download_danfe, credentials_update, etc.',
    entity_type     VARCHAR(50) NOT NULL COMMENT 'nfe_document, nfe_credential, nfe_report',
    entity_id       INT DEFAULT NULL,
    description     TEXT DEFAULT NULL,
    ip_address      VARCHAR(45) DEFAULT NULL,
    user_agent      TEXT DEFAULT NULL,
    extra_data      TEXT DEFAULT NULL COMMENT 'JSON com dados adicionais',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nfe_audit_user (user_id),
    INDEX idx_nfe_audit_action (action),
    INDEX idx_nfe_audit_entity (entity_type, entity_id),
    INDEX idx_nfe_audit_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- D) MULTI-FILIAL / MULTI-CNPJ (5.9)
-- Alterar nfe_credentials para suportar múltiplos registros.
-- ══════════════════════════════════════════════════════════════

-- Adicionar campo filial_id e is_active à tabela de credenciais
ALTER TABLE nfe_credentials
    ADD COLUMN IF NOT EXISTS filial_id VARCHAR(50) DEFAULT NULL COMMENT 'Identificador da filial (ex: Matriz, Filial SP)',
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS ultimo_nsu VARCHAR(25) DEFAULT '0' COMMENT 'Último NSU consultado via DistDFe';

-- Índice para buscar credenciais ativas
ALTER TABLE nfe_credentials
    ADD INDEX IF NOT EXISTS idx_nfe_cred_active (is_active);

-- ══════════════════════════════════════════════════════════════
-- E) MANIFESTAÇÃO DO DESTINATÁRIO (5.1) + DISTDFE (5.2)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_received_documents (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nsu             VARCHAR(25) NOT NULL COMMENT 'NSU do documento',
    schema_type     VARCHAR(50) DEFAULT NULL COMMENT 'resNFe, resEvento, procNFe, etc.',
    chave           VARCHAR(44) DEFAULT NULL,
    cnpj_emitente   VARCHAR(14) DEFAULT NULL,
    nome_emitente   VARCHAR(200) DEFAULT NULL,
    ie_emitente     VARCHAR(20) DEFAULT NULL,
    data_emissao    DATETIME DEFAULT NULL,
    tipo_nfe        TINYINT DEFAULT NULL COMMENT '0=entrada, 1=saída',
    valor_total     DECIMAL(15,2) DEFAULT 0,
    situacao        VARCHAR(20) DEFAULT NULL COMMENT 'autorizada, cancelada, denegada',
    manifestation_status ENUM('pendente','ciencia','confirmada','desconhecida','nao_realizada') DEFAULT 'pendente',
    manifestation_date   DATETIME DEFAULT NULL,
    manifestation_protocol VARCHAR(50) DEFAULT NULL,
    xml_content     LONGTEXT DEFAULT NULL COMMENT 'XML completo (quando disponível via DistDFe)',
    summary_xml     TEXT DEFAULT NULL COMMENT 'Resumo XML (resNFe)',
    imported        TINYINT(1) DEFAULT 0 COMMENT 'Se os dados foram importados para o sistema',
    credential_id   INT DEFAULT NULL COMMENT 'Credencial que consultou',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_nfe_received_nsu (nsu),
    INDEX idx_nfe_received_chave (chave),
    INDEX idx_nfe_received_cnpj (cnpj_emitente),
    INDEX idx_nfe_received_status (manifestation_status),
    INDEX idx_nfe_received_date (data_emissao),
    INDEX idx_nfe_received_imported (imported)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- F) TABELAS FISCAIS DE REFERÊNCIA (5.10)
-- ══════════════════════════════════════════════════════════════

-- Tabela de NCMs com descrição
CREATE TABLE IF NOT EXISTS tax_ncm (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ncm         VARCHAR(8) NOT NULL,
    descricao   VARCHAR(500) NOT NULL,
    unidade     VARCHAR(10) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tax_ncm (ncm),
    INDEX idx_tax_ncm_desc (descricao(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de CFOPs com descrição
CREATE TABLE IF NOT EXISTS tax_cfop (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    cfop        VARCHAR(4) NOT NULL,
    descricao   VARCHAR(500) NOT NULL,
    tipo        ENUM('entrada','saida') DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tax_cfop (cfop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de CESTs vinculados a NCMs
CREATE TABLE IF NOT EXISTS tax_cest (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    cest        VARCHAR(7) NOT NULL,
    ncm         VARCHAR(8) DEFAULT NULL,
    descricao   VARCHAR(500) NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tax_cest (cest),
    INDEX idx_tax_cest_ncm (ncm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de alíquotas interestaduais UF×UF
CREATE TABLE IF NOT EXISTS tax_icms_interstate (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    uf_origem   CHAR(2) NOT NULL,
    uf_destino  CHAR(2) NOT NULL,
    aliquota    DECIMAL(5,2) NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tax_icms_inter (uf_origem, uf_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de municípios IBGE
CREATE TABLE IF NOT EXISTS tax_municipios_ibge (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo_ibge     VARCHAR(7) NOT NULL,
    nome            VARCHAR(200) NOT NULL,
    uf              CHAR(2) NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tax_mun_ibge (codigo_ibge),
    INDEX idx_tax_mun_uf (uf),
    INDEX idx_tax_mun_nome (nome(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- G) POPULAR CFOP BÁSICOS (referência)
-- ══════════════════════════════════════════════════════════════

INSERT IGNORE INTO tax_cfop (cfop, descricao, tipo) VALUES
-- Saídas internas (5xxx)
('5101', 'Venda de produção do estabelecimento', 'saida'),
('5102', 'Venda de mercadoria adquirida ou recebida de terceiros', 'saida'),
('5103', 'Venda de produção, efetuada fora do estabelecimento', 'saida'),
('5104', 'Venda de mercadoria adquirida, efetuada fora do estabelecimento', 'saida'),
('5115', 'Venda de mercadoria adquirida de terceiros, recebida anteriormente em consignação mercantil', 'saida'),
('5116', 'Venda de produção do estabelecimento originada de encomenda para entrega futura', 'saida'),
('5117', 'Venda de mercadoria adquirida de terceiros originada de encomenda para entrega futura', 'saida'),
('5118', 'Venda de produção do estabelecimento entregue ao destinatário por conta e ordem do adquirente originário', 'saida'),
('5119', 'Venda de mercadoria adquirida de terceiros, entregue ao destinatário por conta e ordem do adquirente originário', 'saida'),
('5120', 'Venda de mercadoria de terceiros, entregue ao destinatário pelo vendedor remetente', 'saida'),
('5122', 'Venda de produção remetida para industrialização, por conta e ordem do adquirente, sem transitar pelo estabelecimento', 'saida'),
('5124', 'Industrialização efetuada para outra empresa', 'saida'),
('5125', 'Industrialização efetuada para outra empresa quando a mercadoria recebida para utilização no processo de industrialização não transitar pelo estabelecimento adquirente da mercadoria', 'saida'),
('5151', 'Transferência de produção do estabelecimento', 'saida'),
('5152', 'Transferência de mercadoria adquirida ou recebida de terceiros', 'saida'),
('5201', 'Devolução de compra para industrialização', 'saida'),
('5202', 'Devolução de compra para comercialização', 'saida'),
('5401', 'Venda de produção do estabelecimento em operação com produto sujeito ao regime de substituição tributária', 'saida'),
('5402', 'Venda de produção do estabelecimento de produto sujeito ao regime de substituição tributária, em operação entre contribuintes substitutos do mesmo produto', 'saida'),
('5403', 'Venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária', 'saida'),
('5405', 'Venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária, na condição de contribuinte substituído', 'saida'),
('5501', 'Remessa de produção do estabelecimento, com fim específico de exportação', 'saida'),
('5551', 'Venda de bem do ativo imobilizado', 'saida'),
('5910', 'Remessa em bonificação, doação ou brinde', 'saida'),
('5911', 'Remessa de amostra grátis', 'saida'),
('5920', 'Remessa de vasilhame ou sacaria', 'saida'),
('5929', 'Lançamento efetuado em decorrência de emissão de documento fiscal relativo a operação ou prestação também registrada em equipamento Emissor de Cupom Fiscal', 'saida'),
('5949', 'Outra saída de mercadoria ou prestação de serviço não especificado', 'saida'),
-- Saídas interestaduais (6xxx)
('6101', 'Venda de produção do estabelecimento', 'saida'),
('6102', 'Venda de mercadoria adquirida ou recebida de terceiros', 'saida'),
('6103', 'Venda de produção, efetuada fora do estabelecimento', 'saida'),
('6104', 'Venda de mercadoria adquirida, efetuada fora do estabelecimento', 'saida'),
('6108', 'Venda de mercadoria adquirida ou recebida de terceiros, destinada a não contribuinte', 'saida'),
('6116', 'Venda de produção do estabelecimento originada de encomenda para entrega futura', 'saida'),
('6401', 'Venda de produção do estabelecimento em operação com produto sujeito ao regime de substituição tributária', 'saida'),
('6403', 'Venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária', 'saida'),
('6949', 'Outra saída de mercadoria ou prestação de serviço não especificado', 'saida'),
-- Entradas internas (1xxx)
('1101', 'Compra para industrialização', 'entrada'),
('1102', 'Compra para comercialização', 'entrada'),
('1124', 'Industrialização efetuada por outra empresa', 'entrada'),
('1151', 'Transferência para industrialização', 'entrada'),
('1152', 'Transferência para comercialização', 'entrada'),
('1201', 'Devolução de venda de produção do estabelecimento', 'entrada'),
('1202', 'Devolução de venda de mercadoria adquirida ou recebida de terceiros', 'entrada'),
('1401', 'Compra para industrialização em operação com mercadoria sujeita ao regime de substituição tributária', 'entrada'),
('1403', 'Compra para comercialização em operação com mercadoria sujeita ao regime de substituição tributária', 'entrada'),
('1501', 'Entrada de mercadoria recebida com fim específico de exportação', 'entrada'),
('1551', 'Compra de bem para o ativo imobilizado', 'entrada'),
('1556', 'Compra de material para uso ou consumo', 'entrada'),
('1910', 'Entrada de bonificação, doação ou brinde', 'entrada'),
('1949', 'Outra entrada de mercadoria ou prestação de serviço não especificada', 'entrada'),
-- Entradas interestaduais (2xxx)
('2101', 'Compra para industrialização', 'entrada'),
('2102', 'Compra para comercialização', 'entrada'),
('2201', 'Devolução de venda de produção do estabelecimento', 'entrada'),
('2202', 'Devolução de venda de mercadoria adquirida ou recebida de terceiros', 'entrada'),
('2401', 'Compra para industrialização em operação com mercadoria sujeita ao regime de substituição tributária', 'entrada'),
('2403', 'Compra para comercialização em operação com mercadoria sujeita ao regime de substituição tributária', 'entrada'),
('2551', 'Compra de bem para o ativo imobilizado', 'entrada'),
('2556', 'Compra de material para uso ou consumo', 'entrada'),
('2949', 'Outra entrada de mercadoria ou prestação de serviço não especificada', 'entrada');

-- ══════════════════════════════════════════════════════════════
-- H) POPULAR ALÍQUOTAS INTERESTADUAIS DE ICMS
-- ══════════════════════════════════════════════════════════════

-- Alíquotas padrão: 7% (Sul/Sudeste→Norte/Nordeste/Centro-Oeste) ou 12% (demais)
-- Exceção: 4% para produtos importados (tratado no TaxCalculator)

INSERT IGNORE INTO tax_icms_interstate (uf_origem, uf_destino, aliquota)
SELECT uo.uf AS uf_origem, ud.uf AS uf_destino,
    CASE
        WHEN uo.uf = ud.uf THEN 0 -- intraestadual (não se aplica)
        WHEN uo.uf IN ('SP','RJ','MG','PR','SC','RS') AND ud.uf IN ('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MT','MS','PA','PB','PE','PI','RN','RO','RR','SE','TO') THEN 7.00
        ELSE 12.00
    END AS aliquota
FROM 
    (SELECT 'AC' AS uf UNION SELECT 'AL' UNION SELECT 'AM' UNION SELECT 'AP' UNION SELECT 'BA' 
     UNION SELECT 'CE' UNION SELECT 'DF' UNION SELECT 'ES' UNION SELECT 'GO' UNION SELECT 'MA'
     UNION SELECT 'MG' UNION SELECT 'MS' UNION SELECT 'MT' UNION SELECT 'PA' UNION SELECT 'PB'
     UNION SELECT 'PE' UNION SELECT 'PI' UNION SELECT 'PR' UNION SELECT 'RJ' UNION SELECT 'RN'
     UNION SELECT 'RO' UNION SELECT 'RR' UNION SELECT 'RS' UNION SELECT 'SC' UNION SELECT 'SE'
     UNION SELECT 'SP' UNION SELECT 'TO') uo
CROSS JOIN
    (SELECT 'AC' AS uf UNION SELECT 'AL' UNION SELECT 'AM' UNION SELECT 'AP' UNION SELECT 'BA' 
     UNION SELECT 'CE' UNION SELECT 'DF' UNION SELECT 'ES' UNION SELECT 'GO' UNION SELECT 'MA'
     UNION SELECT 'MG' UNION SELECT 'MS' UNION SELECT 'MT' UNION SELECT 'PA' UNION SELECT 'PB'
     UNION SELECT 'PE' UNION SELECT 'PI' UNION SELECT 'PR' UNION SELECT 'RJ' UNION SELECT 'RN'
     UNION SELECT 'RO' UNION SELECT 'RR' UNION SELECT 'RS' UNION SELECT 'SC' UNION SELECT 'SE'
     UNION SELECT 'SP' UNION SELECT 'TO') ud
WHERE uo.uf != ud.uf;

-- ══════════════════════════════════════════════════════════════
-- I) CONFIGURAÇÕES DA FASE 5 (company_settings)
-- ══════════════════════════════════════════════════════════════

INSERT IGNORE INTO company_settings (setting_key, setting_value) VALUES
('nfe_danfe_logo_path', ''),
('nfe_danfe_custom_footer', ''),
('nfe_webhook_enabled', '0'),
('nfe_batch_limit', '50'),
('nfe_queue_enabled', '0'),
('nfe_distdfe_enabled', '0'),
('nfe_distdfe_auto_interval', '60'),
('nfe_manifestation_auto_ciencia', '0'),
('nfe_audit_enabled', '1'),
('nfe_multi_filial_enabled', '0'),
('nfe_sped_fiscal_enabled', '0');

-- ══════════════════════════════════════════════════════════════
-- J) CAMPOS ADICIONAIS EM nfe_documents PARA EMISSÃO EM LOTE
-- ══════════════════════════════════════════════════════════════

ALTER TABLE nfe_documents
    ADD COLUMN IF NOT EXISTS queue_id INT DEFAULT NULL COMMENT 'Referência à fila de emissão',
    ADD COLUMN IF NOT EXISTS batch_id VARCHAR(50) DEFAULT NULL COMMENT 'ID do lote de emissão',
    ADD COLUMN IF NOT EXISTS credential_id INT DEFAULT NULL COMMENT 'ID da credencial usada (multi-filial)';

ALTER TABLE nfe_documents
    ADD INDEX IF NOT EXISTS idx_nfe_doc_batch (batch_id),
    ADD INDEX IF NOT EXISTS idx_nfe_doc_credential (credential_id);
