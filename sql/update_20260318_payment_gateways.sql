-- ============================================================================
-- Atualização: Integração com Gateways de Pagamento (Strategy Pattern)
-- Data: 2026-03-18
-- Descrição: Cria tabelas para configuração de gateways de pagamento por
--            tenant e log de transações processadas via gateway.
-- ============================================================================

-- 1. Tabela de configuração de gateways por tenant
CREATE TABLE IF NOT EXISTS payment_gateways (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    gateway_slug    VARCHAR(50)  NOT NULL COMMENT 'Identificador do gateway (mercadopago, stripe, pagseguro)',
    display_name    VARCHAR(100) NOT NULL COMMENT 'Nome amigável exibido na UI',
    is_active       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=ativo, 0=inativo',
    is_default      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=gateway padrão para novos pagamentos',
    environment     ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox' COMMENT 'Ambiente atual',
    credentials     TEXT         NULL COMMENT 'JSON criptografado com credenciais (api_key, secret, token, etc.)',
    settings_json   TEXT         NULL COMMENT 'JSON com configurações extras do gateway (webhook_url, pix_enabled, etc.)',
    webhook_secret  VARCHAR(255) NULL COMMENT 'Secret para validação de assinatura dos webhooks',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_gateway_slug (gateway_slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuração de gateways de pagamento por tenant';

-- 2. Tabela de log de transações de gateway (webhook + charges)
CREATE TABLE IF NOT EXISTS payment_gateway_transactions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    gateway_slug        VARCHAR(50)  NOT NULL COMMENT 'Qual gateway processou',
    installment_id      INT          NULL     COMMENT 'FK para order_installments.id (se vinculado)',
    order_id            INT          NULL     COMMENT 'FK para orders.id (se vinculado)',
    external_id         VARCHAR(255) NULL     COMMENT 'ID da transação no gateway (ex: pi_xxx no Stripe)',
    external_status     VARCHAR(100) NULL     COMMENT 'Status retornado pelo gateway (approved, pending, etc.)',
    amount              DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor da transação em BRL',
    currency            VARCHAR(10)  NOT NULL DEFAULT 'BRL',
    payment_method_type VARCHAR(50)  NULL     COMMENT 'Tipo no gateway (pix, credit_card, boleto, etc.)',
    raw_payload         JSON         NULL     COMMENT 'Payload completo do webhook/resposta (para debug)',
    event_type          VARCHAR(100) NULL     COMMENT 'Tipo do evento webhook (payment.updated, charge.succeeded, etc.)',
    processed_at        DATETIME     NULL     COMMENT 'Quando o webhook foi processado',
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_gateway (gateway_slug),
    INDEX idx_external (external_id),
    INDEX idx_installment (installment_id),
    INDEX idx_order (order_id),
    INDEX idx_event (event_type),

    CONSTRAINT fk_pgt_installment FOREIGN KEY (installment_id)
        REFERENCES order_installments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pgt_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de transações processadas por gateways de pagamento';

-- 3. Inserir gateways padrão (inativos, para serem configurados pelo admin)
INSERT INTO payment_gateways (gateway_slug, display_name, is_active, is_default, environment) VALUES
    ('mercadopago', 'Mercado Pago', 0, 0, 'sandbox'),
    ('stripe',      'Stripe',       0, 0, 'sandbox'),
    ('pagseguro',   'PagSeguro',    0, 0, 'sandbox')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);
