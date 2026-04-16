# 02 — Modelo de Dados

## 1. Tabelas Existentes (v1) — Alterações Necessárias

### 1.1 `supplies` — Adicionar colunas

```sql
ALTER TABLE supplies
    ADD COLUMN permite_fracionamento TINYINT(1) NOT NULL DEFAULT 1
        COMMENT 'Se 0, consumo é arredondado para cima (CEIL)'
        AFTER waste_percent,
    ADD COLUMN decimal_precision TINYINT NOT NULL DEFAULT 4
        COMMENT 'Casas decimais para cálculos de consumo (2-6)'
        AFTER permite_fracionamento;
```

**Justificativa:**
- `permite_fracionamento`: Flag boolean que controla se o insumo pode ser consumido em frações. Parafusos (un) = 0, tinta (L) = 1.
- `decimal_precision`: Precisão de arredondamento para cada insumo (default 4 casas).

### 1.2 `product_supplies` — Adicionar colunas

```sql
ALTER TABLE product_supplies
    ADD COLUMN variation_id INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Se preenchido, aplica-se a esta variação; se NULL, aplica ao produto pai'
        AFTER product_id,
    ADD COLUMN loss_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00
        COMMENT 'Percentual de perda específico deste vínculo (override do waste_percent do insumo)'
        AFTER waste_percent,
    DROP INDEX idx_product_supply,
    ADD UNIQUE INDEX idx_product_variation_supply (product_id, variation_id, supply_id);
```

**Justificativa:**
- `variation_id`: Permite BOM específico por variação de produto. Se `NULL`, o consumo vale para o produto pai (e todas as variações herdam).
- `loss_percent`: Fator de perda específico deste vínculo (sobrescreve o `waste_percent` do insumo).
- Índice único agora inclui `variation_id` para permitir insumos diferentes por variação.

---

## 2. Novas Tabelas

### 2.1 `supply_substitutes` — Insumos Substitutos

```sql
CREATE TABLE IF NOT EXISTS supply_substitutes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supply_id       INT UNSIGNED NOT NULL COMMENT 'Insumo principal',
    substitute_id   INT UNSIGNED NOT NULL COMMENT 'Insumo substituto',
    conversion_rate DECIMAL(12,6) NOT NULL DEFAULT 1.000000
                    COMMENT 'Proporção: 1 un do principal = X un do substituto',
    priority        TINYINT UNSIGNED NOT NULL DEFAULT 1
                    COMMENT 'Prioridade de substituição (1 = mais prioritário)',
    notes           TEXT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    tenant_id       INT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_supply_id (supply_id),
    INDEX idx_substitute_id (substitute_id),
    INDEX idx_tenant (tenant_id),
    UNIQUE INDEX idx_supply_substitute (supply_id, substitute_id),

    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_id) REFERENCES supplies(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES `akti_master`.`tenant_clients`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos substitutos de emergência com prioridade';
```

**Campos-chave:**
- `conversion_rate`: Se 1 litro de Tinta A equivale a 1.2 litros de Tinta B, o rate é 1.2
- `priority`: Menor número = maior prioridade. O sistema sugere na ordem.

### 2.2 `supply_cost_alerts` — Alertas de Custo e Margem

```sql
CREATE TABLE IF NOT EXISTS supply_cost_alerts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    supply_id       INT UNSIGNED NOT NULL,
    old_cost        DECIMAL(12,4) NOT NULL COMMENT 'Custo anterior do insumo',
    new_cost        DECIMAL(12,4) NOT NULL COMMENT 'Novo custo após recálculo CMP',
    old_product_cost DECIMAL(12,4) NOT NULL COMMENT 'Custo de produção anterior do produto',
    new_product_cost DECIMAL(12,4) NOT NULL COMMENT 'Novo custo de produção do produto',
    current_price   DECIMAL(12,4) NOT NULL COMMENT 'Preço de venda atual do produto',
    old_margin      DECIMAL(5,2) NOT NULL COMMENT 'Margem anterior (%)',
    new_margin      DECIMAL(5,2) NOT NULL COMMENT 'Nova margem (%)',
    margin_threshold DECIMAL(5,2) NOT NULL COMMENT 'Limite mínimo configurado',
    suggested_price DECIMAL(12,4) NULL COMMENT 'Preço sugerido para manter margem mínima',
    status          ENUM('pending','acknowledged','applied','dismissed') NOT NULL DEFAULT 'pending',
    acknowledged_by INT UNSIGNED NULL,
    acknowledged_at DATETIME NULL,
    tenant_id       INT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_product (product_id),
    INDEX idx_supply (supply_id),
    INDEX idx_status (status),
    INDEX idx_tenant (tenant_id),

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES `akti_master`.`tenant_clients`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alertas de impacto de custo na margem do produto';
```

### 2.3 `production_consumption_log` — Apontamento de Consumo Real

```sql
CREATE TABLE IF NOT EXISTS production_consumption_log (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id            INT UNSIGNED NOT NULL COMMENT 'Pedido/ordem de produção',
    product_id          INT UNSIGNED NOT NULL,
    variation_id        INT UNSIGNED NULL,
    supply_id           INT UNSIGNED NOT NULL,
    warehouse_id        INT UNSIGNED NOT NULL,
    planned_quantity    DECIMAL(12,4) NOT NULL COMMENT 'Quantidade calculada (ratio × lote)',
    actual_quantity     DECIMAL(12,4) NULL COMMENT 'Quantidade real apontada pelo operador',
    batch_number        VARCHAR(50) NULL COMMENT 'Lote consumido',
    variance            DECIMAL(12,4) GENERATED ALWAYS AS (actual_quantity - planned_quantity) STORED
                        COMMENT 'Diferença: positivo = desperdício, negativo = economia',
    variance_percent    DECIMAL(8,4) GENERATED ALWAYS AS (
                            CASE WHEN planned_quantity > 0
                                THEN ((actual_quantity - planned_quantity) / planned_quantity) * 100
                                ELSE 0
                            END
                        ) STORED COMMENT 'Variação percentual',
    notes               TEXT NULL,
    created_by          INT UNSIGNED NOT NULL,
    tenant_id           INT NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    INDEX idx_supply (supply_id),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (tenant_id) REFERENCES `akti_master`.`tenant_clients`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de consumo real vs planejado por ordem de produção';
```

**Campos calculados:**
- `variance`: Coluna GENERATED — positivo indica desperdício, negativo indica economia
- `variance_percent`: % de variação para o dashboard de eficiência

### 2.4 `supply_rupture_forecasts` — Cache de Previsão de Ruptura

```sql
CREATE TABLE IF NOT EXISTS supply_rupture_forecasts (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supply_id           INT UNSIGNED NOT NULL,
    warehouse_id        INT UNSIGNED NULL COMMENT 'NULL = total consolidado',
    current_stock       DECIMAL(12,4) NOT NULL,
    committed_quantity  DECIMAL(12,4) NOT NULL COMMENT 'Soma dos pedidos em aberto',
    available_stock     DECIMAL(12,4) GENERATED ALWAYS AS (current_stock - committed_quantity) STORED,
    days_to_rupture     INT NULL COMMENT 'Dias estimados até ruptura (baseado em média de consumo)',
    status              ENUM('ok','warning','critical','ruptured') NOT NULL DEFAULT 'ok',
    last_calculated_at  DATETIME NOT NULL,
    tenant_id           INT NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_supply (supply_id),
    INDEX idx_status (status),
    INDEX idx_tenant (tenant_id),
    UNIQUE INDEX idx_supply_warehouse (supply_id, warehouse_id),

    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES `akti_master`.`tenant_clients`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cache de previsão de ruptura de estoque por insumo';
```

### 2.5 `supply_settings` — Configurações do Módulo por Tenant

```sql
CREATE TABLE IF NOT EXISTS supply_settings (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    min_margin_threshold        DECIMAL(5,2) NOT NULL DEFAULT 15.00
                                COMMENT 'Margem mínima (%) para gerar alerta de custo',
    forecast_calculation_method ENUM('average','weighted','last_30_days') NOT NULL DEFAULT 'weighted'
                                COMMENT 'Método de cálculo para previsão de ruptura',
    allow_negative_stock        TINYINT(1) NOT NULL DEFAULT 0,
    default_fefo_strategy       ENUM('fefo','fifo','manual') NOT NULL DEFAULT 'fefo',
    auto_recalculate_cmp        TINYINT(1) NOT NULL DEFAULT 1,
    default_decimal_precision   TINYINT NOT NULL DEFAULT 4,
    tenant_id                   INT NOT NULL,
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_tenant (tenant_id),

    FOREIGN KEY (tenant_id) REFERENCES `akti_master`.`tenant_clients`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configurações do módulo de insumos por tenant';
```

---

## 3. Diagrama de Relacionamento (ERD)

```
┌────────────────────┐          ┌──────────────────────┐
│  supply_categories │          │  suppliers           │
│  (v1 - sem mudança)│          │  (existente)         │
└────────┬───────────┘          └──────────┬───────────┘
         │ 1:N                             │ N:M
         ▼                                 ▼
┌────────────────────────────────────────────────────────┐
│                    supplies (v1 + v2)                   │
│  + permite_fracionamento (v2)                          │
│  + decimal_precision (v2)                              │
├────────────────────────────────────────────────────────┤
│  id, code, name, category_id, unit_measure,            │
│  cost_price, min_stock, reorder_point, waste_percent,  │
│  permite_fracionamento*, decimal_precision*,            │
│  fiscal_ncm, ..., deleted_at                           │
└─────┬──────────┬──────────┬───────────┬───────────────┘
      │          │          │           │
      │ 1:N      │ 1:N      │ N:M       │ 1:N
      ▼          ▼          ▼           ▼
┌──────────┐ ┌────────┐ ┌──────────┐ ┌──────────────────┐
│ supply_  │ │supply_ │ │product_  │ │supply_substitutes│
│ stock_   │ │price_  │ │supplies  │ │  (v2 NOVA)       │
│ items    │ │history │ │(v1 + v2) │ │  supply_id       │
│ (v1)     │ │ (v1)   │ │+variation│ │  substitute_id   │
└────┬─────┘ └────────┘ │+loss_%   │ │  conversion_rate │
     │                  └─────┬────┘ │  priority        │
     │ 1:N                    │      └──────────────────┘
     ▼                        │
┌──────────────┐              │         ┌──────────────────────┐
│ supply_stock │              │         │ production_           │
│ _movements   │              ▼         │ consumption_log      │
│ (v1)         │         ┌─────────┐   │  (v2 NOVA)           │
└──────────────┘         │products │   │  planned_quantity    │
                         │+variação│   │  actual_quantity     │
                         └─────────┘   │  variance (computed) │
                                       └──────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐
│ supply_cost_alerts   │     │ supply_rupture_       │
│  (v2 NOVA)           │     │ forecasts (v2 NOVA)   │
│  product_id          │     │  supply_id            │
│  old/new cost/margin │     │  current/committed    │
│  suggested_price     │     │  days_to_rupture      │
│  status              │     │  status               │
└──────────────────────┘     └──────────────────────┘

┌──────────────────────┐
│ supply_settings      │
│  (v2 NOVA)           │
│  1 row per tenant    │
│  min_margin_threshold│
│  fefo_strategy       │
│  allow_neg_stock     │
└──────────────────────┘
```

---

## 4. Resumo de Impacto

| Ação | Tabela | Tipo |
|------|--------|------|
| ALTER (add 2 cols) | `supplies` | Modificação |
| ALTER (add 2 cols, mod index) | `product_supplies` | Modificação |
| CREATE | `supply_substitutes` | Nova |
| CREATE | `supply_cost_alerts` | Nova |
| CREATE | `production_consumption_log` | Nova |
| CREATE | `supply_rupture_forecasts` | Nova |
| CREATE | `supply_settings` | Nova |

**Total:** 2 tabelas modificadas + 5 tabelas novas

---

## 5. Observações de Precisão

- Todos os campos de quantidade usam `DECIMAL(12,4)` — 4 casas decimais
- Fator de conversão usa `DECIMAL(12,6)` — 6 casas decimais para precisão em proporções
- Percentuais usam `DECIMAL(5,2)` — até 999.99%
- Colunas `GENERATED ALWAYS AS ... STORED` para cálculos automáticos no MySQL 5.7+

---

*Anterior: [01 — Visão Geral](01-visao-geral.md) | Próximo: [03 — Arquitetura Backend](03-arquitetura-backend.md)*
