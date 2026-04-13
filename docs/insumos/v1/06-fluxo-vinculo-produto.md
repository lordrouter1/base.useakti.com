# 06 — Fluxo de Vinculação de Insumo a Produto (BOM — Bill of Materials)

## 1. Visão Geral

O BOM (Bill of Materials) define **quais insumos são necessários para fabricar cada produto** e em **que quantidade**. Esta é a ponte central entre o catálogo de produtos e o catálogo de insumos, permitindo:

- Calcular custo de matéria-prima por produto
- Estimar consumo de insumos por pedido
- Planejar compras baseado na demanda (futuro MRP)
- Rastrear composição de produtos para fins fiscais/regulatórios

---

## 2. Fluxo — Diagrama Geral

```
┌────────────────────┐                      ┌──────────────────────┐
│     PRODUTO        │                      │       INSUMO         │
│                    │                      │                      │
│ Camiseta Básica    │      BOM             │ Tecido Algodão       │
│ (products.id=42)   │◄─────────────────────│ (supplies.id=1)      │
│                    │  qty: 1.5 m          │                      │
│                    │  waste: 5%           │                      │
│                    │  efetivo: 1.575 m    │                      │
│                    │                      │                      │
│                    │◄─────────────────────│ Fio Costura          │
│                    │  qty: 3.0 m          │ (supplies.id=7)      │
│                    │  waste: 2%           │                      │
│                    │  efetivo: 3.06 m     │                      │
│                    │                      │                      │
│                    │◄─────────────────────│ Etiqueta             │
│                    │  qty: 1 un           │ (supplies.id=15)     │
│                    │  waste: 0%           │ opcional: true       │
│                    │  efetivo: 1 un       │                      │
└────────────────────┘                      └──────────────────────┘

Custo MP por produto = Σ (qtd_efetiva × custo_unit_insumo)
                     = (1.575 × 12.50) + (3.06 × 2.00) + (1 × 0.30)
                     = 19.6875 + 6.12 + 0.30
                     = R$ 26.11
```

---

## 3. Ponto de Acesso — Na Tela do Produto

A composição de insumos (BOM) é gerenciada na **tela de edição do produto**, como uma nova aba/seção:

```
┌────────────────────────────────────────────────────────────────────┐
│ 📦 Editar Produto: Camiseta Básica                                │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  [Dados] [Fiscal] [E-commerce] [Grades] [Imagens] [Insumos(BOM)] │
│                                                     ^^^^^^^^^^^^^  │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │ Composição de Insumos (BOM)            [+ Adicionar Insumo] │ │
│  │                                                              │ │
│  │ ┌────────────┬──────┬──────┬───────┬───────┬────────┬─────┐ │ │
│  │ │ Insumo     │ Qtd  │ Un.  │%Perda │Efetiv.│Custo   │ ⚙   │ │ │
│  │ ├────────────┼──────┼──────┼───────┼───────┼────────┼─────┤ │ │
│  │ │ Tecido Alg │ 1,50 │ m    │ 5,0%  │ 1,575 │ 19,69  │ ✎🗑 │ │ │
│  │ │ Fio Costura│ 3,00 │ m    │ 2,0%  │ 3,060 │  6,12  │ ✎🗑 │ │ │
│  │ │ Etiqueta ⓘ│ 1,00 │ un   │ 0,0%  │ 1,000 │  0,30  │ ✎🗑 │ │ │
│  │ └────────────┴──────┴──────┴───────┴───────┴────────┴─────┘ │ │
│  │                                                              │ │
│  │ ⓘ = Insumo opcional                                         │ │
│  │                                                              │ │
│  │ ┌────────────────────────────────────────────────────────┐  │ │
│  │ │ Custo Total Matéria-Prima:           R$ 26,11         │  │ │
│  │ │ Custo Total (só obrigatórios):       R$ 25,81         │  │ │
│  │ │ Preço de Venda do Produto:           R$ 59,90         │  │ │
│  │ │ Margem sobre MP:                     56,4%            │  │ │
│  │ └────────────────────────────────────────────────────────┘  │ │
│  └──────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────┘
```

---

## 4. Fluxo de Adição de Insumo ao BOM

```
┌──────────────────────────┐
│ Clica [+ Adicionar Insumo]│
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────────────────────────┐
│      Modal — Adicionar Insumo ao Produto     │
│                                              │
│  Insumo*: [🔍 Buscar insumo...        ▼]   │
│                                              │
│  ┌──────── Info do Insumo Selecionado ────┐ │
│  │ Código: INS-0001                       │ │
│  │ Unidade: m (metro)                     │ │
│  │ Custo Padrão: R$ 12,50/m              │ │
│  │ Estoque Atual: 150 m                  │ │
│  │ % Perda Padrão: 5%                    │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  Quantidade por unidade do produto*: [1,50] │
│  Unidade de medida: [m ▼] (pré-selecionado)│
│  % Perda/Desperdício: [5,00] (do insumo)   │
│  Insumo opcional? [  ] Sim                  │
│  Observações: [________________________]    │
│                                              │
│  ┌── Cálculo Automático ─────────────────┐  │
│  │ Quantidade efetiva: 1,575 m           │  │
│  │ Custo por unidade: R$ 19,69          │  │
│  └───────────────────────────────────────┘  │
│                                              │
│  [Cancelar]              [+ Adicionar]      │
└──────────────┬───────────────────────────────┘
               │
               │ POST AJAX → ?page=supplies&action=addProductSupply
               ▼
┌──────────────────────────────────────────────┐
│           Validação Server                    │
│                                              │
│  ✓ product_id válido e existente             │
│  ✓ supply_id válido e existente              │
│  ✓ Vínculo não duplicado (UNIQUE constraint) │
│  ✓ quantity > 0                              │
│  ✓ waste_percent entre 0 e 100              │
│  ✓ unit_measure valor válido do ENUM        │
│  ✓ CSRF token válido                        │
│                                              │
│  INSERT product_supplies                     │
│  → Retorna JSON {success, data, cost_total}  │
└──────────────────────────────────────────────┘
```

---

## 5. Regras de Negócio

### 5.1 Cálculo de Quantidade Efetiva

```
quantidade_efetiva = quantidade × (1 + waste_percent / 100)

Exemplo:
  quantidade = 1.50 m
  waste_percent = 5%
  efetiva = 1.50 × 1.05 = 1.575 m
```

### 5.2 Cálculo de Custo por Unidade do Produto

```
custo_insumo_no_produto = quantidade_efetiva × cost_price_do_insumo

Custo total MP = Σ custo_insumo_no_produto (para todos insumos obrigatórios)
```

### 5.3 Unicidade

- Um insumo só pode aparecer **uma vez** no BOM de cada produto
- Constraint: UNIQUE(`product_id`, `supply_id`)
- Se precisar de variações (ex: tecido principal vs tecido de forro), usar `notes` para diferenciar, ou usar insumos diferentes

### 5.4 Insumos Opcionais

- Marcados como `is_optional = 1`
- Exibidos com indicador visual (ⓘ) na listagem
- **Não incluídos** no cálculo de custo obrigatório
- Incluídos no custo total (informativo)
- Exemplos: embalagem especial, etiqueta de luxo, acabamento premium

### 5.5 Herança de Unidade

- Ao selecionar um insumo, a unidade de medida é **pré-preenchida** com a do insumo
- O usuário pode alterar (ex: insumo cadastrado em kg, mas BOM usa em g)
- A conversão de unidades é **manual** nesta v1

### 5.6 % Perda

- Pré-preenchido com o `waste_percent` do cadastro do insumo
- Editável por composição (um produto pode ter perda diferente do outro)
- Exemplo: corte de tecido para camiseta tem 5% de perda, mas para vestido tem 8%

---

## 6. Ponto de Acesso Reverso — Na Tela do Insumo

Na edição do insumo (`?page=supplies&action=edit&id=X`), aba "Produtos (BOM)":

```
┌────────────────────────────────────────────────────────────────────┐
│ Produtos que utilizam este insumo: Tecido Algodão Cru              │
│                                                                    │
│ ┌──────────┬──────────────┬──────┬───────┬───────┬──────────────┐ │
│ │ Código   │ Produto      │ Qtd  │%Perda │Efetiv.│ Status Prod. │ │
│ ├──────────┼──────────────┼──────┼───────┼───────┼──────────────┤ │
│ │ PRD-042  │ Camiseta Bás │ 1,50 │ 5,0%  │ 1,575 │ Ativo        │ │
│ │ PRD-043  │ Camiseta Prem│ 1,80 │ 5,0%  │ 1,890 │ Ativo        │ │
│ │ PRD-067  │ Vestido Longo│ 3,20 │ 8,0%  │ 3,456 │ Ativo        │ │
│ └──────────┴──────────────┴──────┴───────┴───────┴──────────────┘ │
│                                                                    │
│ Total de consumo por unidade: 6,921 m                              │
│ → Editar composição na tela do produto                             │
└────────────────────────────────────────────────────────────────────┘
```

---

## 7. Estimativa de Consumo por Pedido (informativo)

Funcionalidade de consulta rápida: dado um produto e uma quantidade de pedido, estimar o consumo de insumos:

```
┌────────────────────────────────────────────────────────────────┐
│ 🧮 Estimativa de Consumo                                       │
│                                                                │
│ Produto: [Camiseta Básica ▼]  Quantidade: [100]  [Calcular]  │
│                                                                │
│ ┌──────────────┬──────────┬────────────┬────────┬───────────┐ │
│ │ Insumo       │ Qtd/Un.  │ Total Nec. │ Estoque│ Situação  │ │
│ ├──────────────┼──────────┼────────────┼────────┼───────────┤ │
│ │ Tecido Alg.  │ 1,575 m  │ 157,50 m   │ 150 m  │ ⚠ Falta  │ │
│ │ Fio Costura  │ 3,060 m  │ 306,00 m   │ 500 m  │ ✅ OK     │ │
│ │ Etiqueta ⓘ   │ 1,000 un │ 100,00 un  │ 2000un │ ✅ OK     │ │
│ └──────────────┴──────────┴────────────┴────────┴───────────┘ │
│                                                                │
│ Custo MP estimado: 100 × R$ 26,11 = R$ 2.611,00              │
│ ⚠ Tecido Algodão: faltam 7,5 m para atender o pedido          │
└────────────────────────────────────────────────────────────────┘
```

---

## 8. Métodos do Model

### No `Supply` Model (métodos de BOM):

```
Supply::getProductSupplies(productId)          → insumos de um produto
Supply::getSupplyProducts(supplyId)            → produtos que usam um insumo
Supply::addProductSupply(data)                 → vincular insumo a produto
Supply::updateProductSupply(id, data)          → atualizar composição
Supply::removeProductSupply(id)                → remover vínculo
Supply::calculateProductCost(productId)        → custo MP total do produto
Supply::estimateConsumption(productId, qty)    → estimar consumo p/ qtd
```

---

## 9. Actions do Controller

| Action | HTTP | Método | Descrição |
|--------|------|--------|-----------|
| `getProductSupplies` | GET | `getProductSupplies()` | Insumos do produto (JSON) |
| `addProductSupply` | POST | `addProductSupply()` | Vincular insumo ao produto |
| `updateProductSupply` | POST | `updateProductSupply()` | Atualizar composição |
| `removeProductSupply` | POST | `removeProductSupply()` | Remover vínculo |
| `estimateConsumption` | GET | `estimateConsumption()` | Calcular consumo estimado (JSON) |
| `getSupplyProducts` | GET | `getSupplyProducts()` | Produtos que usam o insumo (JSON) |

---

## 10. Integração Futura (v2+)

### 10.1 Consumo Automático no Pipeline

Quando um pedido avançar para a etapa de produção no pipeline:
1. Buscar produtos do pedido → buscar BOM de cada produto
2. Calcular insumos necessários × quantidade do pedido
3. Registrar movimentação `consumo_producao` no estoque de insumos
4. Se estoque insuficiente, alertar o operador

### 10.2 MRP Simplificado

Com base nos pedidos em aberto no pipeline:
1. Somar consumo estimado de cada insumo
2. Comparar com estoque atual
3. Gerar relatório de necessidade de compra
4. Sugerir pedidos de compra ao fornecedor preferencial

### 10.3 Custeio Automático

- Atualizar o campo `cost_price` do produto automaticamente com base na soma dos insumos
- Permitir somar custos de mão de obra e overhead
- Calcular margem de lucro real vs preço de venda
