-- ============================================================================
-- UPDATE: update_20260318_nfe_module.sql
-- Descrição: Módulo NF-e completo — credenciais SEFAZ, documentos, logs
-- Data: 2026-03-18
-- Autor: Sistema Akti
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ══════════════════════════════════════════════════════════════
-- 1. Credenciais SEFAZ por tenant
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_credentials (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cnpj            VARCHAR(18)  NOT NULL DEFAULT '' COMMENT 'CNPJ do emitente (com máscara ou sem)',
    ie              VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'Inscrição Estadual',
    razao_social    VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Razão social do emitente',
    nome_fantasia   VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nome fantasia',
    crt             TINYINT      NOT NULL DEFAULT 1  COMMENT 'Código de Regime Tributário (1=Simples, 2=Simples Excesso, 3=Normal)',
    uf              CHAR(2)      NOT NULL DEFAULT 'RS' COMMENT 'UF do emitente',
    cod_municipio   VARCHAR(10)  NOT NULL DEFAULT '' COMMENT 'Código IBGE do município',
    municipio       VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nome do município',
    logradouro      VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Endereço do emitente',
    numero          VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'Número do endereço',
    bairro          VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Bairro',
    cep             VARCHAR(10)  NOT NULL DEFAULT '' COMMENT 'CEP',
    complemento     VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Complemento do endereço',
    telefone        VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'Telefone',

    -- Certificado digital
    certificate_path     VARCHAR(500) NULL COMMENT 'Caminho do arquivo .pfx no servidor',
    certificate_password VARCHAR(500) NULL COMMENT 'Senha do certificado (criptografada)',
    certificate_expiry   DATE         NULL COMMENT 'Data de expiração do certificado',

    -- Ambiente e numeração
    environment     ENUM('homologacao','producao') NOT NULL DEFAULT 'homologacao' COMMENT 'Ambiente SEFAZ',
    serie_nfe       INT NOT NULL DEFAULT 1 COMMENT 'Série da NF-e',
    proximo_numero  INT NOT NULL DEFAULT 1 COMMENT 'Próximo número de NF-e a emitir',

    -- CSC para NFC-e (opcional)
    csc_id          VARCHAR(10)  NULL COMMENT 'ID do CSC para NFC-e',
    csc_token       VARCHAR(100) NULL COMMENT 'Token CSC para NFC-e',

    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Credenciais SEFAZ para emissão de NF-e por tenant';

-- Inserir registro padrão (vazio) se não existir
INSERT INTO nfe_credentials (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;


-- ══════════════════════════════════════════════════════════════
-- 2. Documentos fiscais emitidos (NF-e)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_documents (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT          NULL     COMMENT 'FK para orders.id (pode ser NULL para NF avulsa)',
    numero          INT          NOT NULL COMMENT 'Número da NF-e',
    serie           INT          NOT NULL DEFAULT 1 COMMENT 'Série da NF-e',
    chave           VARCHAR(44)  NULL     COMMENT 'Chave de acesso (44 dígitos)',
    protocolo       VARCHAR(20)  NULL     COMMENT 'Número do protocolo SEFAZ',
    recibo          VARCHAR(20)  NULL     COMMENT 'Número do recibo (lote)',

    status          ENUM('rascunho','processando','autorizada','rejeitada','cancelada','denegada','corrigida')
                    NOT NULL DEFAULT 'rascunho' COMMENT 'Status atual da NF-e',
    status_sefaz    VARCHAR(10)  NULL     COMMENT 'Código de status SEFAZ (ex: 100, 101, 135)',
    motivo_sefaz    TEXT         NULL     COMMENT 'Motivo retornado pela SEFAZ',

    -- XMLs
    xml_envio       LONGTEXT     NULL     COMMENT 'XML de envio (antes da assinatura completa)',
    xml_autorizado  LONGTEXT     NULL     COMMENT 'XML autorizado (procNFe completo)',
    xml_cancelamento LONGTEXT    NULL     COMMENT 'XML do evento de cancelamento',
    xml_correcao    LONGTEXT     NULL     COMMENT 'XML da carta de correção',
    danfe_path      VARCHAR(500) NULL     COMMENT 'Caminho do PDF DANFE gerado',

    -- Dados do documento
    natureza_op     VARCHAR(100) NOT NULL DEFAULT 'VENDA DE MERCADORIA' COMMENT 'Natureza da operação',
    valor_total     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor total da NF-e',
    valor_produtos  DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor total dos produtos',
    valor_desconto  DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor total de descontos',
    valor_frete     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor do frete',

    -- Destinatário snapshot
    dest_cnpj_cpf   VARCHAR(18)  NULL COMMENT 'CNPJ/CPF do destinatário',
    dest_nome       VARCHAR(255) NULL COMMENT 'Nome/Razão social do destinatário',
    dest_ie         VARCHAR(20)  NULL COMMENT 'IE do destinatário',
    dest_uf         CHAR(2)      NULL COMMENT 'UF do destinatário',

    -- Cancelamento / Correção
    cancel_protocolo VARCHAR(20) NULL COMMENT 'Protocolo de cancelamento',
    cancel_motivo    TEXT        NULL COMMENT 'Justificativa de cancelamento',
    cancel_date      DATETIME    NULL COMMENT 'Data/hora do cancelamento',
    correcao_texto   TEXT        NULL COMMENT 'Texto da carta de correção',
    correcao_seq     INT         NULL DEFAULT 0 COMMENT 'Sequência da carta de correção',
    correcao_date    DATETIME    NULL COMMENT 'Data/hora da carta de correção',

    emitted_at      DATETIME     NULL     COMMENT 'Data/hora de emissão (autorização SEFAZ)',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order (order_id),
    INDEX idx_chave (chave),
    INDEX idx_status (status),
    INDEX idx_numero_serie (numero, serie),

    CONSTRAINT fk_nfe_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Documentos NF-e emitidos pelo sistema';


-- ══════════════════════════════════════════════════════════════
-- 3. Log de comunicação com SEFAZ
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS nfe_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nfe_document_id INT          NULL     COMMENT 'FK para nfe_documents.id',
    order_id        INT          NULL     COMMENT 'FK para orders.id',
    action          VARCHAR(50)  NOT NULL COMMENT 'Ação (emissao, cancelamento, correcao, consulta, status)',
    status          VARCHAR(20)  NOT NULL DEFAULT 'info' COMMENT 'info, success, error, warning',
    code_sefaz      VARCHAR(10)  NULL     COMMENT 'Código retornado pela SEFAZ',
    message         TEXT         NULL     COMMENT 'Mensagem de log',
    xml_request     LONGTEXT     NULL     COMMENT 'XML enviado',
    xml_response    LONGTEXT     NULL     COMMENT 'XML recebido',
    user_id         INT          NULL     COMMENT 'Usuário que executou a ação',
    ip_address      VARCHAR(45)  NULL     COMMENT 'IP do usuário',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_document (nfe_document_id),
    INDEX idx_order (order_id),
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created (created_at),

    CONSTRAINT fk_nfelog_document FOREIGN KEY (nfe_document_id)
        REFERENCES nfe_documents(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de comunicação com SEFAZ para NF-e';


-- ══════════════════════════════════════════════════════════════
-- 4. Alterar tabela orders — vincular NF-e
-- ══════════════════════════════════════════════════════════════

-- Adicionar nfe_id (FK para nfe_documents)
SET @exists_nfe_id = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nfe_id');
SET @sql_nfe_id = IF(@exists_nfe_id = 0,
    'ALTER TABLE orders ADD COLUMN nfe_id INT NULL COMMENT ''FK para nfe_documents.id'' AFTER nf_notes',
    'SELECT ''Column nfe_id already exists''');
PREPARE stmt FROM @sql_nfe_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar nfe_status (status rápido sem join)
SET @exists_nfe_status = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'nfe_status');
SET @sql_nfe_status = IF(@exists_nfe_status = 0,
    'ALTER TABLE orders ADD COLUMN nfe_status VARCHAR(30) NULL COMMENT ''Status da NF-e vinculada (cache)'' AFTER nfe_id',
    'SELECT ''Column nfe_status already exists''');
PREPARE stmt FROM @sql_nfe_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK para nfe_documents (se coluna existir e FK não existir)
SET @exists_fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND CONSTRAINT_NAME = 'fk_orders_nfe');
SET @sql_fk = IF(@exists_fk = 0 AND @exists_nfe_id = 0,
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_nfe FOREIGN KEY (nfe_id) REFERENCES nfe_documents(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''FK fk_orders_nfe already exists or column not added''');
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
