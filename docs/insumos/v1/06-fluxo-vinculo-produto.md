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

## 6. Ponto de Acesso Reverso — "Onde é Usado" (Where Used)

Na edição do insumo (`?page=supplies&action=edit&id=X`), aba "Produtos (BOM)":

```
┌────────────────────────────────────────────────────────────────────┐
│ Produtos que utilizam este insumo: Tecido Algodão Cru              │
│ Custo Médio Ponderado (CMP): R$ 12,80/m                           │
│                                                                    │
│ ┌──────────┬──────────────┬──────┬───────┬───────┬────────┬──────┐│
│ │ Código   │ Produto      │ Qtd  │%Perda │Efetiv.│CustoMP │Marg. ││
│ ├──────────┼──────────────┼──────┼───────┼───────┼────────┼──────┤│
│ │ PRD-042  │ Camiseta Bás │ 1,50 │ 5,0%  │ 1,575 │ 26,11  │56,4%││
│ │ PRD-043  │ Camiseta Prem│ 1,80 │ 5,0%  │ 1,890 │ 38,50  │48,2%││
│ │ PRD-067  │ Vestido Longo│ 3,20 │ 8,0%  │ 3,456 │ 85,30  │37,8%││
│ └──────────┴──────────────┴──────┴───────┴───────┴────────┴──────┘│
│                                                                    │
│ Total de consumo por unidade: 6,921 m                              │
│ → Editar composição na tela do produto                             │
└────────────────────────────────────────────────────────────────────┘
```

### 6.1 Análise de Impacto de Preço (Where-Used Impact)

Quando o custo de um insumo muda (novo CMP calculado ou preço manual), o sistema mostra o impacto em todos os produtos afetados:

```
┌────────────────────────────────────────────────────────────────────┐
│ ⚠ Análise de Impacto — Alteração de Preço                         │
│                                                                    │
│ Insumo: Tecido Algodão Cru                                        │
│ CMP Anterior: R$ 12,50/m → CMP Novo: R$ 14,00/m (+12,0%)        │
│                                                                    │
│ ┌──────────────┬──────────┬──────────┬──────────┬────────────────┐│
│ │ Produto      │Custo Ant.│Custo Novo│ Variação │Margem Nova     ││
│ ├──────────────┼──────────┼──────────┼──────────┼────────────────┤│
│ │ Camiseta Bás │  26,11   │  28,47   │ +2,36    │ 52,5% (era 56)││
│ │ Camiseta Prem│  38,50   │  41,36   │ +2,86    │ 44,5% (era 48)││
│ │ Vestido Longo│  85,30   │  91,68   │ +6,38    │ 33,2% (era 38)││
│ └──────────────┴──────────┴──────────┴──────────┴────────────────┘│
│                                                                    │
│ Total de produtos impactados: 3                                    │
│ Variação média de custo: +4,0%                                     │
│                                                                    │
│ [Cancelar]  [Atualizar apenas CMP]  [Atualizar CMP + Custos BOM] │
└────────────────────────────────────────────────────────────────────┘
```

### 6.2 Regras da Análise de Impacto

- **Trigger:** Após inserção em `supply_price_history` com novo CMP diferente do anterior
- **Cálculo:** Para cada produto no BOM: `novo_custo = calculateProductCost(productId)` usando o novo CMP
- **Margem:** `margem = (preco_venda - custo_mp) / preco_venda × 100`
- **Ações disponíveis:**
  - **Cancelar:** Mantém CMP anterior
  - **Atualizar apenas CMP:** Grava o CMP no `supplies.cost_price`, não recalcula BOM
  - **Atualizar CMP + Custos BOM:** Grava CMP e executa `Product::updateBaseCostFromBOM()` para cada produto afetado
- **Descontinuação:** Se insumo for desativado (`is_active = 0`), alertar todos os produtos que o utilizam

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
Supply::calculateProductCost(productId)        → custo MP total do produto (usa CMP)
Supply::estimateConsumption(productId, qty)    → estimar consumo p/ qtd
Supply::getWhereUsedImpact(supplyId, newCMP)   → análise de impacto em produtos
Supply::getAffectedProducts(supplyId)          → IDs de produtos afetados
```

### No `Product` Model (custeio automático BOM):

```
Product::updateBaseCostFromBOM(productId)      → recalcula cost_price baseado na soma dos insumos
Product::getMarginAnalysis(productId)          → retorna {custo_mp, preco_venda, margem_percent}
Product::bulkUpdateBOMCosts(productIds)        → atualiza cost_price de vários produtos
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
| `getWhereUsedImpact` | GET | `getWhereUsedImpact()` | Análise de impacto — variação de custo/margem (JSON) |
| `applyBOMCostUpdate` | POST | `applyBOMCostUpdate()` | Executa atualização de custo BOM nos produtos afetados |

---

## 10. Custeio Automático de Produto por BOM

> **Promovido para v1** — o custeio de produto baseado nos insumos do BOM agora faz parte do escopo principal.

### 10.1 Conceito

O custo de matéria-prima (`cost_price`) do produto é calculado automaticamente pela soma dos custos dos insumos definidos no BOM, utilizando o **Custo Médio Ponderado (CMP)** de cada insumo.

```
CustoMP(produto) = Σ [ qtd_efetiva(insumo_i) × CMP(insumo_i) ]

Onde:
  qtd_efetiva = quantity × (1 + waste_percent / 100)
  CMP = supplies.cost_price (atualizado pelo SupplyStockMovementService)
```

### 10.2 Quando Recalcular

| Trigger | Ação |
|---------|------|
| Entrada de estoque (novo CMP calculado) | Oferecer atualização via análise de impacto |
| Adicionar insumo ao BOM | Recalcular custo do produto automaticamente |
| Alterar quantidade/perda no BOM | Recalcular custo do produto automaticamente |
| Remover insumo do BOM | Recalcular custo do produto automaticamente |
| Comando manual | Botão "Recalcular Custo" na tela do produto |

### 10.3 Fluxo em Evento

```
1. SupplyStockMovementService::processEntry()
   → Calcula novo CMP
   → Atualiza supplies.cost_price
   → Insere supply_price_history
   → Dispara evento: model.supply.price_changed {supply_id, old_cmp, new_cmp}

2. EventListener (registrado em app/bootstrap/events.php):
   → Recebe model.supply.price_changed
   → Supply::getAffectedProducts(supplyId) → lista de product_ids
   → Para cada product_id: calcula novo custo e compara com o atual
   → Armazena resultado em sessão ou cache para exibição no popup de impacto
   → Se configurado auto-update: Product::bulkUpdateBOMCosts(productIds)
```

### 10.4 Método `Product::updateBaseCostFromBOM()`

```php
/**
 * Recalcula o cost_price do produto baseado no BOM (Bill of Materials).
 *
 * @param int $productId  ID do produto
 * @return float  Novo custo calculado
 *
 * Lógica:
 *   1. SELECT ps.quantity, ps.waste_percent, ps.is_optional, s.cost_price AS cmp
 *      FROM product_supplies ps
 *      JOIN supplies s ON s.id = ps.supply_id
 *      WHERE ps.product_id = :productId AND ps.is_optional = 0
 *
 *   2. Para cada insumo obrigatório:
 *      custo += quantity * (1 + waste_percent/100) * cmp
 *
 *   3. UPDATE products SET cost_price = :custo WHERE id = :productId
 *
 *   4. Disparar evento: model.product.cost_updated {product_id, old_cost, new_cost}
 */
```

### 10.5 Card de Resumo de Custo no Produto

```
┌────────────────────────────────────────────────────────────────────┐
│ 💰 Custeio do Produto: Camiseta Básica                            │
│                                                                    │
│  Custo MP (BOM):            R$ 26,11  ← calculado do BOM          │
│  Mão de Obra (manual):      R$  8,00  ← (input futuro v2)        │
│  Overhead (manual):          R$  3,50  ← (input futuro v2)        │
│  ─────────────────────────────────────                             │
│  Custo Total Produção:      R$ 37,61                               │
│                                                                    │
│  Preço de Venda:            R$ 59,90                               │
│  Margem Bruta:              37,2%                                  │
│                                                                    │
│  Último recálculo: 13/04/2026 14:30    [🔄 Recalcular Agora]     │
└────────────────────────────────────────────────────────────────────┘
```

---

## 11. Integração Futura (v2+)

### 11.1 Consumo Automático no Pipeline

Quando um pedido avançar para a etapa de produção no pipeline:
1. Buscar produtos do pedido → buscar BOM de cada produto
2. Calcular insumos necessários × quantidade do pedido
3. Registrar movimentação `consumo_producao` no estoque de insumos
4. Se estoque insuficiente, alertar o operador

### 11.2 MRP Avançado

Com base nos pedidos em aberto no pipeline:
1. Somar consumo estimado de cada insumo
2. Comparar com estoque atual
3. Gerar relatório de necessidade de compra
4. Sugerir pedidos de compra ao fornecedor preferencial

### 11.3 Custos Adicionais de Produção

- Campos de mão de obra e overhead por produto
- Margem de lucro real considerando todos os custos
