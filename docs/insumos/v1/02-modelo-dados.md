# 02 — Modelo de Dados (ERD)

## 1. Diagrama de Relacionamento

```
┌──────────────────────────┐
│    supply_categories     │
├──────────────────────────┤
│ id           PK          │
│ name         VARCHAR(100)│
│ description  TEXT NULL    │
│ is_active    TINYINT(1)  │
│ sort_order   INT         │
│ created_at   DATETIME    │
│ updated_at   DATETIME    │
└────────────┬─────────────┘
             │ 1
             │
             │ N
┌────────────┴─────────────────────────────────────────────────────┐
│                          supplies                                 │
├───────────────────────────────────────────────────────────────────┤
│ id                PK                                              │
│ category_id       FK → supply_categories(id) NULL                 │
│ code              VARCHAR(50)  UNIQUE — código interno            │
│ name              VARCHAR(200) NOT NULL                            │
│ description       TEXT NULL                                        │
│ unit_measure      ENUM('un','kg','g','mg','L','mL','m','cm',     │
│                        'mm','m2','m3','pc','cx','rl','fl','par')  │
│ cost_price        DECIMAL(12,4) DEFAULT 0                         │
│ min_stock         DECIMAL(12,4) DEFAULT 0                         │
│ reorder_point     DECIMAL(12,4) DEFAULT 0                         │
│ waste_percent     DECIMAL(5,2) DEFAULT 0 — % perda esperada      │
│ is_active         TINYINT(1) DEFAULT 1                            │
│ notes             TEXT NULL                                        │
│ fiscal_ncm        VARCHAR(20) NULL                                │
│ fiscal_cest       VARCHAR(20) NULL                                │
│ fiscal_origem     VARCHAR(5) NULL                                  │
│ fiscal_unidade    VARCHAR(10) NULL                                 │
│ created_at        DATETIME DEFAULT CURRENT_TIMESTAMP              │
│ updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE    │
│ deleted_at        DATETIME NULL — soft delete                     │
└────────┬────────────────────────────────────┬─────────────────────┘
         │ 1                                  │ 1
         │                                    │
         │ N                                  │ N
┌────────┴──────────────────────┐    ┌────────────┴──────────────────────┐
│   supply_suppliers            │    │     product_supplies (BOM)        │
├───────────────────────────────┤    ├───────────────────────────────────┤
│ id            PK              │    │ id              PK                │
│ supply_id     FK → supplies   │    │ product_id      FK → products    │
│ supplier_id   FK → suppliers  │    │ supply_id       FK → supplies    │
│ supplier_sku  VARCHAR(100)    │    │ quantity         DECIMAL(12,4)    │
│ supplier_name VARCHAR(200)    │    │ unit_measure     same ENUM        │
│ unit_price    DECIMAL(12,4)   │    │ waste_percent    DECIMAL(5,2)     │
│ min_order_qty DECIMAL(12,4)   │    │ is_optional      TINYINT(1)      │
│ lead_time_days INT NULL       │    │ notes            TEXT NULL        │
│ conversion_factor DEC(12,4)   │    │ sort_order       INT DEFAULT 0   │
│ is_preferred  TINYINT(1)      │    │ created_at       DATETIME        │
│ is_active     TINYINT(1)      │    │ updated_at       DATETIME        │
│ notes         TEXT NULL       │    └───────────────────────────────────┘
│ created_at    DATETIME        │
│ updated_at    DATETIME        │
└───────────────────────────────┘

┌───────────────────────────────────────────┐
│         supply_stock_items                │
├───────────────────────────────────────────┤
│ id              PK                        │
│ warehouse_id    FK → warehouses           │
│ supply_id       FK → supplies             │
│ quantity        DECIMAL(12,4) DEFAULT 0   │
│ min_quantity    DECIMAL(12,4) DEFAULT 0   │
│ batch_number    VARCHAR(100) NULL         │
│ expiry_date     DATE NULL                 │
│ location_code   VARCHAR(50) NULL          │
│ last_updated    DATETIME                  │
│ created_at      DATETIME                  │
│ updated_at      DATETIME                  │
└───────────────────────────────────────────┘

┌───────────────────────────────────────────┐
│       supply_stock_movements              │
├───────────────────────────────────────────┤
│ id              PK                        │
│ warehouse_id    FK → warehouses           │
│ supply_id       FK → supplies             │
│ type            ENUM('entrada','saida',   │
│                      'ajuste',            │
│                      'transferencia',     │
│                      'consumo_producao')  │
│ quantity        DECIMAL(12,4) NOT NULL    │
│ unit_price      DECIMAL(12,4) NULL        │
│ batch_number    VARCHAR(100) NULL         │
│ reason          VARCHAR(255) NULL         │
│ reference_type  VARCHAR(50) NULL          │
│ reference_id    INT NULL                  │
│ created_by      INT FK → users            │
│ created_at      DATETIME                  │
└───────────────────────────────────────────┘

┌───────────────────────────────────────────┐
│       supply_price_history                │
├───────────────────────────────────────────┤
│ id              PK                        │
│ supply_id       FK → supplies             │
│ supplier_id     FK → suppliers NULL       │
│ price           DECIMAL(12,4) NOT NULL    │
│ movement_id     FK → supply_stock_mov.    │
│ created_at      DATETIME                  │
└───────────────────────────────────────────┘
```

---

## 2. Descrição das Tabelas

### 2.1 `supply_categories`

Categorias para organização dos insumos (ex: Tecido, Tinta, Parafuso, Embalagem, Químico).

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `name` | VARCHAR(100) | N | Nome da categoria |
| `description` | TEXT | S | Descrição opcional |
| `is_active` | TINYINT(1) | N | Ativa/inativa (default 1) |
| `sort_order` | INT | N | Ordem de exibição (default 0) |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | N | ON UPDATE CURRENT_TIMESTAMP |

---

### 2.2 `supplies`

Tabela principal de insumos/matérias-primas.

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `category_id` | INT | S | FK → supply_categories(id) |
| `code` | VARCHAR(50) | N | Código interno único (ex: INS-0001) |
| `name` | VARCHAR(200) | N | Nome do insumo |
| `description` | TEXT | S | Descrição detalhada |
| `unit_measure` | ENUM(...) | N | Unidade de medida padrão |
| `cost_price` | DECIMAL(12,4) | N | Custo médio/padrão (default 0) |
| `min_stock` | DECIMAL(12,4) | N | Estoque mínimo global (default 0) |
| `reorder_point` | DECIMAL(12,4) | N | Ponto de pedido (default 0) |
| `waste_percent` | DECIMAL(5,2) | N | % de perda/desperdício padrão (default 0) |
| `is_active` | TINYINT(1) | N | Ativo/inativo (default 1) |
| `notes` | TEXT | S | Observações internas |
| `fiscal_ncm` | VARCHAR(20) | S | NCM fiscal |
| `fiscal_cest` | VARCHAR(20) | S | CEST fiscal |
| `fiscal_origem` | VARCHAR(5) | S | Origem fiscal |
| `fiscal_unidade` | VARCHAR(10) | S | Unidade fiscal |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | N | ON UPDATE CURRENT_TIMESTAMP |
| `deleted_at` | DATETIME | S | Soft delete |

**Índices:**
- UNIQUE(`code`)
- INDEX(`category_id`)
- INDEX(`name`)
- INDEX(`is_active`)
- INDEX(`deleted_at`)

---

### 2.3 `supply_suppliers` (Pivot Insumo ↔ Fornecedor)

Relação N:N entre insumos e fornecedores, com dados de negociação.

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `supply_id` | INT | N | FK → supplies(id) |
| `supplier_id` | INT | N | FK → suppliers(id) |
| `supplier_sku` | VARCHAR(100) | S | Código do item no catálogo do fornecedor |
| `supplier_name` | VARCHAR(200) | S | Nome do item no catálogo do fornecedor |
| `unit_price` | DECIMAL(12,4) | N | Preço unitário negociado (default 0) |
| `min_order_qty` | DECIMAL(12,4) | N | Pedido mínimo (default 1) |
| `lead_time_days` | INT | S | Prazo de entrega em dias |
| `conversion_factor` | DECIMAL(12,4) | N | Fator de conversão UOM compra→estoque (default 1). Ex: 1 cx = 50 un → fator = 50 |
| `is_preferred` | TINYINT(1) | N | Fornecedor preferencial (default 0) |
| `is_active` | TINYINT(1) | N | Vínculo ativo (default 1) |
| `notes` | TEXT | S | Observações |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | N | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- UNIQUE(`supply_id`, `supplier_id`) — evita vínculo duplicado
- INDEX(`supplier_id`)
- INDEX(`is_preferred`)

---

### 2.4 `product_supplies` (BOM — Bill of Materials)

Define a composição de insumos por produto.

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `product_id` | INT | N | FK → products(id) |
| `supply_id` | INT | N | FK → supplies(id) |
| `quantity` | DECIMAL(12,4) | N | Qtd necessária por unidade do produto |
| `unit_measure` | ENUM(...) | N | Unidade para esta composição |
| `waste_percent` | DECIMAL(5,2) | N | % de perda nesta composição (default 0) |
| `is_optional` | TINYINT(1) | N | Insumo opcional? (default 0) |
| `notes` | TEXT | S | Observações (ex: "usar apenas no modelo X") |
| `sort_order` | INT | N | Ordem de exibição (default 0) |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | N | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- UNIQUE(`product_id`, `supply_id`) — evita duplicação
- INDEX(`supply_id`)

---

### 2.5 `supply_stock_items`

Posição de estoque de insumos por armazém. Reutiliza a tabela `warehouses` existente.

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `warehouse_id` | INT | N | FK → warehouses(id) |
| `supply_id` | INT | N | FK → supplies(id) |
| `quantity` | DECIMAL(12,4) | N | Estoque atual (default 0) |
| `min_quantity` | DECIMAL(12,4) | N | Mínimo neste armazém (default 0) |
| `batch_number` | VARCHAR(100) | S | Número do lote (rastreabilidade) |
| `expiry_date` | DATE | S | Data de validade do lote |
| `location_code` | VARCHAR(50) | S | Localização no armazém (prateleira, corredor) |
| `last_updated` | DATETIME | S | Última atualização de estoque |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | N | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- UNIQUE(`warehouse_id`, `supply_id`)
- INDEX(`supply_id`)
- INDEX(`quantity`) — para consultas de estoque baixo
- INDEX(`batch_number`)
- INDEX(`expiry_date`) — para consultas FEFO

---

### 2.6 `supply_stock_movements`

Histórico de movimentações de insumos.

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `warehouse_id` | INT | N | FK → warehouses(id) |
| `supply_id` | INT | N | FK → supplies(id) |
| `type` | ENUM('entrada','saida','ajuste','transferencia','consumo_producao') | N | Tipo de movimentação |
| `quantity` | DECIMAL(12,4) | N | Quantidade movimentada |
| `unit_price` | DECIMAL(12,4) | S | Custo unitário nesta movimentação |
| `batch_number` | VARCHAR(100) | S | Lote relacionado à movimentação |
| `reason` | VARCHAR(255) | S | Motivo (compra, consumo, ajuste inventário) |
| `reference_type` | VARCHAR(50) | S | Tipo de referência (purchase_order, order, manual) |
| `reference_id` | INT | S | ID do registro de referência |
| `created_by` | INT | S | FK → users(id) |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- INDEX(`warehouse_id`)
- INDEX(`supply_id`)
- INDEX(`type`)
- INDEX(`reference_type`, `reference_id`)
- INDEX(`created_at`)

---

### 2.7 `supply_price_history`

Histórico de preços de insumos para acompanhar variação de custo ao longo do tempo. Alimentada automaticamente a cada movimentação de entrada.

| Coluna | Tipo | Null | Descrição |
|--------|------|------|-----------|
| `id` | INT AUTO_INCREMENT | N | PK |
| `supply_id` | INT | N | FK → supplies(id) |
| `supplier_id` | INT | S | FK → suppliers(id) — NULL se ajuste manual |
| `price` | DECIMAL(12,4) | N | Preço unitário registrado |
| `movement_id` | INT | S | FK → supply_stock_movements(id) |
| `created_at` | DATETIME | N | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- INDEX(`supply_id`)
- INDEX(`supplier_id`)
- INDEX(`created_at`)
- INDEX(`supply_id`, `created_at`) — para consultas de evolução de preço

---

## 3. Relacionamentos Resumidos

```
supply_categories  1 ──── N  supplies
supplies           N ──── N  suppliers      (via supply_suppliers)
supplies           N ──── N  products       (via product_supplies / BOM)
supplies           1 ──── N  supply_stock_items
supplies           1 ──── N  supply_stock_movements
supplies           1 ──── N  supply_price_history
warehouses         1 ──── N  supply_stock_items
warehouses         1 ──── N  supply_stock_movements
users              1 ──── N  supply_stock_movements  (created_by)
suppliers          1 ──── N  supply_price_history
```

---

## 4. Unidades de Medida Suportadas

| Código | Descrição | Uso Típico |
|--------|-----------|------------|
| `un` | Unidade | Parafusos, botões, etiquetas |
| `kg` | Quilograma | Matérias-primas a granel |
| `g` | Grama | Pequenas quantidades |
| `mg` | Miligrama | Materiais de precisão |
| `L` | Litro | Tintas, solventes, líquidos |
| `mL` | Mililitro | Aditivos, corantes |
| `m` | Metro | Tecidos, fios, cabos |
| `cm` | Centímetro | Peças pequenas |
| `mm` | Milímetro | Chapas, placas finas |
| `m2` | Metro quadrado | Chapas, tecidos planos |
| `m3` | Metro cúbico | Madeira, areia |
| `pc` | Peça | Componentes avulsos |
| `cx` | Caixa | Embalagens por caixa |
| `rl` | Rolo | Fitas, filmes |
| `fl` | Folha | Papel, laminados |
| `par` | Par | Calçados, luvas |
