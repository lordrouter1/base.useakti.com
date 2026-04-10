# Checkout Transparente — Especificação Técnica de Banco de Dados

> Detalhamento completo das alterações de banco necessárias para o módulo de checkout transparente.

---

## 1. Nova Tabela: `checkout_tokens`

### DDL Completo

```sql
CREATE TABLE IF NOT EXISTS checkout_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL,
    order_id INT NOT NULL,
    installment_id INT NULL,
    gateway_slug VARCHAR(50) NULL COMMENT 'NULL = usar gateway padrão do tenant',
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'BRL',
    allowed_methods JSON NULL COMMENT 'Array de métodos permitidos. NULL = todos suportados pelo gateway',
    status ENUM('active','used','expired','cancelled') NOT NULL DEFAULT 'active',
    customer_name VARCHAR(255) NULL,
    customer_email VARCHAR(255) NULL,
    customer_document VARCHAR(20) NULL,
    metadata JSON NULL COMMENT 'Dados extras (referência interna, notas)',
    ip_address VARCHAR(45) NULL COMMENT 'IP do visitante ao acessar o checkout',
    payment_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de tentativas de pagamento',
    last_attempt_at DATETIME NULL COMMENT 'Última tentativa de pagamento',
    used_method VARCHAR(50) NULL COMMENT 'Método efetivamente usado no pagamento',
    external_id VARCHAR(255) NULL COMMENT 'ID da transação no gateway',
    used_at DATETIME NULL COMMENT 'Quando o pagamento foi confirmado',
    expires_at DATETIME NOT NULL COMMENT 'Data/hora de expiração do link',
    created_by INT NULL COMMENT 'ID do usuário que gerou o link',
    tenant_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    UNIQUE KEY uk_token (token),
    INDEX idx_order_id (order_id),
    INDEX idx_installment_id (installment_id),
    INDEX idx_status_expires (status, expires_at),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_created_by (created_by),

    -- Foreign Keys
    CONSTRAINT fk_checkout_tokens_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_checkout_tokens_installment FOREIGN KEY (installment_id) REFERENCES order_installments(id) ON DELETE SET NULL,
    CONSTRAINT fk_checkout_tokens_tenant FOREIGN KEY (tenant_id) REFERENCES akti_master.tenant_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens de checkout transparente para pagamento público';
```

### Dicionário de Campos

| # | Coluna | Tipo | Null | Default | Descrição |
|---|--------|------|------|---------|-----------|
| 1 | `id` | INT PK AI | N | auto | Identificador único |
| 2 | `token` | VARCHAR(64) UQ | N | — | Token criptográfico (hex de 32 bytes) |
| 3 | `order_id` | INT FK | N | — | Pedido associado |
| 4 | `installment_id` | INT FK | S | NULL | Parcela específica (NULL = pedido inteiro) |
| 5 | `gateway_slug` | VARCHAR(50) | S | NULL | Gateway fixo (NULL = padrão do tenant) |
| 6 | `amount` | DECIMAL(12,2) | N | — | Valor a cobrar |
| 7 | `currency` | VARCHAR(3) | N | 'BRL' | Código da moeda |
| 8 | `allowed_methods` | JSON | S | NULL | `["pix","credit_card","boleto"]` ou NULL |
| 9 | `status` | ENUM | N | 'active' | Estado do token |
| 10 | `customer_name` | VARCHAR(255) | S | NULL | Nome do pagador |
| 11 | `customer_email` | VARCHAR(255) | S | NULL | Email do pagador |
| 12 | `customer_document` | VARCHAR(20) | S | NULL | CPF/CNPJ |
| 13 | `metadata` | JSON | S | NULL | Dados arbitrários extras |
| 14 | `ip_address` | VARCHAR(45) | S | NULL | IPv4 ou IPv6 do visitante |
| 15 | `payment_attempts` | SMALLINT | N | 0 | Contador de tentativas |
| 16 | `last_attempt_at` | DATETIME | S | NULL | Timestamp da última tentativa |
| 17 | `used_method` | VARCHAR(50) | S | NULL | Método que efetivou o pagamento |
| 18 | `external_id` | VARCHAR(255) | S | NULL | ID da transação no gateway |
| 19 | `used_at` | DATETIME | S | NULL | Quando o pagamento foi confirmado |
| 20 | `expires_at` | DATETIME | N | — | Expiração do link |
| 21 | `created_by` | INT | S | NULL | Quem gerou o link |
| 22 | `tenant_id` | INT FK | N | — | Tenant proprietário |
| 23 | `created_at` | DATETIME | N | CURRENT_TIMESTAMP | Criação |
| 24 | `updated_at` | DATETIME | N | CURRENT_TIMESTAMP | Última atualização |

### Ciclo de Vida do Status

```
  active ──→ used       (pagamento confirmado)
    │
    ├──→ expired    (expires_at ultrapassado, via cron)
    │
    └──→ cancelled  (cancelado manualmente pelo operador)
```

**Regras:**
- Apenas tokens `active` podem ser acessados na página de checkout
- Token `used` nunca volta a `active`
- Token `expired` pode ser "renovado" criando um **novo** token
- Token `cancelled` é final

---

## 2. Alteração na Tabela `orders`

### DDL

```sql
ALTER TABLE orders
    ADD COLUMN checkout_token_id INT NULL COMMENT 'Último token de checkout transparente gerado'
        AFTER payment_link_created_at,
    ADD CONSTRAINT fk_orders_checkout_token
        FOREIGN KEY (checkout_token_id) REFERENCES checkout_tokens(id)
        ON DELETE SET NULL;
```

### Impacto

- Campo opcional, não afeta registros existentes
- ON DELETE SET NULL garante integridade se token for removido
- Permite acesso rápido ao token ativo do pedido sem query extra

---

## 3. Índices e Performance

### Queries Esperadas e Índices

| Query | Índice Utilizado |
|-------|-----------------|
| `WHERE token = ?` | `uk_token` (UNIQUE) |
| `WHERE order_id = ?` | `idx_order_id` |
| `WHERE installment_id = ?` | `idx_installment_id` |
| `WHERE status = 'active' AND expires_at < NOW()` | `idx_status_expires` (para cron) |
| `WHERE tenant_id = ? AND status = 'active'` | `idx_tenant_id` + `idx_status_expires` |
| `WHERE created_by = ?` | `idx_created_by` |

### Volume Estimado

- ~10-50 tokens por tenant por mês (uso normal)
- Tokens expirados podem ser limpos após 90 dias (cron de manutenção)
- Recomendado: partição ou archival após 1 ano

---

## 4. Queries Principais do Model

### Criar Token

```sql
INSERT INTO checkout_tokens
    (token, order_id, installment_id, gateway_slug, amount, currency,
     allowed_methods, status, customer_name, customer_email, customer_document,
     metadata, expires_at, created_by, tenant_id)
VALUES
    (:token, :order_id, :installment_id, :gateway_slug, :amount, :currency,
     :allowed_methods, 'active', :customer_name, :customer_email, :customer_document,
     :metadata, :expires_at, :created_by, :tenant_id)
```

### Buscar por Token (com dados do pedido)

```sql
SELECT ct.*,
       o.total_amount AS order_total,
       o.discount AS order_discount,
       o.description AS order_description,
       o.customer_id,
       oi.installment_number,
       oi.due_date AS installment_due_date,
       oi.status AS installment_status
FROM checkout_tokens ct
JOIN orders o ON o.id = ct.order_id
LEFT JOIN order_installments oi ON oi.id = ct.installment_id
WHERE ct.token = :token
  AND ct.status = 'active'
  AND ct.expires_at > NOW()
LIMIT 1
```

### Marcar como Usado

```sql
UPDATE checkout_tokens
SET status = 'used',
    used_at = NOW(),
    used_method = :method,
    external_id = :external_id
WHERE id = :id AND status = 'active'
```

### Registrar Tentativa

```sql
UPDATE checkout_tokens
SET payment_attempts = payment_attempts + 1,
    last_attempt_at = NOW()
WHERE id = :id
```

### Expirar Tokens Vencidos (Cron)

```sql
UPDATE checkout_tokens
SET status = 'expired'
WHERE status = 'active' AND expires_at < NOW()
```

### Cancelar Token

```sql
UPDATE checkout_tokens
SET status = 'cancelled'
WHERE id = :id AND status = 'active'
```

---

## 5. Rollback

Em caso de necessidade de reverter:

```sql
-- Remover FK da tabela orders
ALTER TABLE orders DROP FOREIGN KEY fk_orders_checkout_token;
ALTER TABLE orders DROP COLUMN checkout_token_id;

-- Remover tabela
DROP TABLE IF EXISTS checkout_tokens;
```

---

*Especificação de banco — Checkout Transparente v1 — 2026-04-08*
