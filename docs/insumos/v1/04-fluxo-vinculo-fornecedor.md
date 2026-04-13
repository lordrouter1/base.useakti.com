# 04 — Fluxo de Vinculação de Insumo a Fornecedor

## 1. Visão Geral

A vinculação entre insumos e fornecedores é uma relação **N:N** (um insumo pode ser fornecido por vários fornecedores, e um fornecedor pode fornecer vários insumos). A tabela pivot `supply_suppliers` armazena metadados da relação: preço, SKU do fornecedor, prazo de entrega, preferência e **fator de conversão de unidade de medida** (UOM).

---

## 2. Fluxo — Diagrama

```
                    ┌─────────────────────────────────┐
                    │   Tela de Edição do Insumo      │
                    │   ?page=supplies&action=edit     │
                    └───────────────┬─────────────────┘
                                    │
                                    ▼
                    ┌─────────────────────────────────┐
                    │      Aba "Fornecedores"         │
                    │   (abaixo do form principal)     │
                    │                                  │
                    │  ┌───────────────────────────┐   │
                    │  │ Fornecedores vinculados:  │   │
                    │  │                           │   │
                    │  │ Forn.A │ R$12 │ 5d │ ★ │✎🗑│  │
                    │  │ Forn.B │ R$14 │ 3d │   │✎🗑│  │
                    │  │                           │   │
                    │  │ [+ Vincular Fornecedor]   │   │
                    │  └───────────────────────────┘   │
                    └───────────────┬─────────────────┘
                                    │
                    Clica em [+ Vincular Fornecedor]
                                    │
                                    ▼
                    ┌─────────────────────────────────┐
                    │   Modal SweetAlert2 / Inline    │
                    │                                  │
                    │  Fornecedor*: [Select2 busca ▼] │
                    │  SKU Fornecedor: [__________]   │
                    │  Nome no Fornecedor: [_______]  │
                    │  Preço Unit.*: [0,0000]         │
                    │  Pedido Mínimo: [1]             │
                    │  Prazo Entrega (dias): [___]    │
                    │  Fator Conversão: [1,0000]      │
                    │   (Ex: 1 cx=50 un → fator=50)   │
                    │  Preferencial: [  ] Sim         │
                    │  Obs: [____________________]    │
                    │                                  │
                    │  [Cancelar]    [Vincular]       │
                    └───────────────┬─────────────────┘
                                    │
                              POST AJAX
                    ?page=supplies&action=linkSupplier
                                    │
                                    ▼
                    ┌─────────────────────────────────┐
                    │       Validação Server          │
                    │                                  │
                    │  ✓ supply_id válido              │
                    │  ✓ supplier_id válido            │
                    │  ✓ Vínculo não duplicado         │
                    │  ✓ Preço >= 0                    │
                    │  ✓ CSRF token                    │
                    │                                  │
                    │  → INSERT supply_suppliers       │
                    │  → Se is_preferred = true,       │
                    │    desmarcar outros preferred    │
                    │    do mesmo supply_id            │
                    │                                  │
                    │  Retorna JSON:                   │
                    │  {success: true, data: {...}}    │
                    └─────────────────────────────────┘
```

---

## 3. Regras de Negócio

### 3.1 Fornecedor Preferencial

- Apenas **um fornecedor** pode ser marcado como preferencial por insumo
- Ao marcar um como preferencial, desmarcar o anterior automaticamente
- O preferencial é exibido com ícone ★ e destaque visual
- Usado como sugestão padrão ao criar pedidos de compra

### 3.2 Unicidade

- Não permitir vincular o mesmo fornecedor ao mesmo insumo duas vezes
- Constraint UNIQUE(`supply_id`, `supplier_id`) no banco
- Validação no controller antes do INSERT

### 3.3 Desativação vs Exclusão

- Desvincular (excluir) remove o registro de `supply_suppliers`
- Opção de **desativar** (`is_active = 0`) mantém histórico mas não aparece em seleções
- Confirmar exclusão via SweetAlert2

### 3.4 Atualização de Custo

- Ao atualizar preço do fornecedor preferencial, **opcionalmente** atualizar o `cost_price` do insumo
- Exibir prompt: _"Deseja atualizar o custo padrão do insumo para R$ X,XX?"_

### 3.5 Fator de Conversão de Unidade (UOM)

- Permite que o insumo seja **comprado numa unidade** e **estocado em outra**
- Exemplo: Fornecedor vende em **Caixa** (cx), mas o estoque controla em **Unidade** (un). Se 1 cx = 50 un, o fator é **50**
- O fator padrão é **1.0000** (mesma unidade de compra e estoque)
- Ao processar uma entrada de compra via `SupplyStockMovementService`, a quantidade estocada será: `qtd_nota × conversion_factor`
- O campo deve ser validado como `> 0`

**Exemplos de conversão:**

| Compra | Estoque | Fator | Nota: 10 cx | Estoque: +500 un |
|--------|---------|-------|-------------|-------------------|
| Caixa | Unidade | 50 | 10 | 500 |
| Rolo (50m) | Metro | 50 | 5 | 250 |
| Galão (3.6L) | Litro | 3.6 | 10 | 36 |
| Kg | Grama | 1000 | 2 | 2000 |

### 3.6 Histórico de Preços

- Toda movimentação de entrada gera um registro em `supply_price_history`
- Permite monitorar a evolução do preço por fornecedor ao longo do tempo
- Na edição do insumo, exibir **gráfico de linha** (Chart.js) com preço nos últimos meses
- O custo médio ponderado do insumo é recalculado a cada entrada

---

## 4. Pontos de Entrada

### 4.1 A partir do Insumo (principal)

Na tela de edição do insumo (`?page=supplies&action=edit&id=X`), seção/aba "Fornecedores":
- Lista fornecedores vinculados ao insumo
- Botão [+ Vincular Fornecedor]
- Editar/remover vínculo existente

### 4.2 A partir do Fornecedor (secundário)

Na tela de edição do fornecedor (`?page=suppliers&action=edit&id=X`), nova aba "Insumos":
- Lista insumos fornecidos por este fornecedor
- Link para o insumo
- Sem edição (somente visualização, edição vai pela tela do insumo)

---

## 5. Métodos do Model (`Supply`)

```
Supply::getSuppliers(supplyId)               → fornecedores vinculados ao insumo
Supply::linkSupplier(data)                   → vincular fornecedor
Supply::updateSupplierLink(id, data)         → atualizar dados do vínculo
Supply::unlinkSupplier(id)                   → remover vínculo
Supply::setPreferredSupplier(supplyId, supplierId) → definir preferencial
Supply::getPreferredSupplier(supplyId)       → retorna fornecedor preferencial
```

---

## 6. Actions do Controller (adicionais ao CRUD)

| Action | HTTP | Método | Descrição |
|--------|------|--------|-----------|
| `getSuppliers` | GET | `getSuppliers()` | Lista fornecedores do insumo (JSON) |
| `linkSupplier` | POST | `linkSupplier()` | Vincular fornecedor ao insumo |
| `updateSupplierLink` | POST | `updateSupplierLink()` | Atualizar dados do vínculo |
| `unlinkSupplier` | POST | `unlinkSupplier()` | Remover vínculo |
| `searchSuppliers` | GET | `searchSuppliers()` | Buscar fornecedores para Select2 (JSON) |

---

## 7. Wireframe — Seção Fornecedores na Edição do Insumo

```
┌────────────────────────────────────────────────────────────────────┐
│ 🧱 Editar Insumo: Tecido Algodão Cru                              │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  [Dados Básicos]  [Fornecedores]  [Estoque]  [Produtos (BOM)]    │
│                    ^^^^^^^^^^^^                                    │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │ Fornecedores vinculados                [+ Vincular Fornec.] │ │
│  │                                                              │ │
│  │ ┌──────────┬──────────┬────────┬────────┬──────┬──────┬──────┬───┐ │ │
│  │ │Fornecedor│ SKU Forn.│ Preço  │Ped.Mín.│Prazo │ UOM  │ Pref │ ⚙ │ │ │
│  │ ├──────────┼──────────┼────────┼────────┼──────┼──────┼──────┼───┤ │ │
│  │ │ Têxtil   │ TC-A001  │ 12,50  │ 50 m   │ 5d   │ ×1   │  ★   │✎🗑│ │ │
│  │ │ Brasil   │          │        │        │      │      │      │   │ │ │
│  │ ├──────────┼──────────┼────────┼────────┼──────┼──────┼──────┼───┤ │ │
│  │ │ Fornec.  │ ALG-200  │ 14,00  │ 100 m  │ 3d   │ ×50  │      │✎🗑│ │ │
│  │ │ Norte    │          │  /cx   │        │      │      │      │   │ │ │
│  │ └──────────┴──────────┴────────┴────────┴──────┴──────┴──────┴───┘ │ │
│  │                                                              │ │
│  │ Nenhum fornecedor vinculado.                                 │ │
│  │ (exibido quando lista vazia)                                 │ │
│  └──────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────┘
```

---

## 8. Wireframe — Modal de Vinculação

```
┌────────────────────────────────────────────────┐
│  ╳  Vincular Fornecedor                        │
├────────────────────────────────────────────────┤
│                                                │
│  Fornecedor*                                   │
│  [🔍 Buscar fornecedor...              ▼]     │
│                                                │
│  ┌────────────────────┬───────────────────┐   │
│  │ SKU no Fornecedor  │ Nome no Fornecedor│   │
│  │ [______________]   │ [_______________] │   │
│  └────────────────────┴───────────────────┘   │
│                                                │
│  ┌────────────────────┬───────────────────┐   │
│  │ Preço Unitário*    │ Pedido Mínimo     │   │
│  │ [R$ 0,0000]       │ [1,0000]          │   │
│  └────────────────────┴───────────────────┘   │
│                                                │
│  ┌────────────────────┬───────────────────┐   │
│  │ Prazo Entrega(dias)│ Preferencial      │   │
│  │ [___]              │ [  ] Sim          │   │
│  └────────────────────┴───────────────────┘   │
│                                                │
│  ┌────────────────────────────────────────┐   │
│  │ Fator de Conversão (UOM)              │   │
│  │ [1,0000]                              │   │
│  │ Ex: 1 cx com 50 un → fator = 50      │   │
│  └────────────────────────────────────────┘   │
│                                                │
│  Observações                                   │
│  [________________________________________]   │
│                                                │
│  [Cancelar]               [💾 Vincular]       │
└────────────────────────────────────────────────┘
```

---

## 9. Visão Reversa — Na Tela do Fornecedor

Na edição do fornecedor, adicionar aba "Insumos Fornecidos" (somente leitura):

```
┌──────────────────────────────────────────────────────────────┐
│ Insumos fornecidos por: Têxtil Brasil Ltda                   │
│                                                              │
│ ┌────────┬──────────────┬──────────┬────────┬──────────────┐│
│ │ Código │ Insumo       │ SKU Forn.│ Preço  │ Preferencial ││
│ ├────────┼──────────────┼──────────┼────────┼──────────────┤│
│ │ INS-01 │ Tecido Algod │ TC-A001  │ 12,50  │ ★            ││
│ │ INS-07 │ Fio Poliéster│ FP-500   │ 8,30   │              ││
│ └────────┴──────────────┴──────────┴────────┴──────────────┘│
│                                                              │
│ → Editar vínculos na tela do insumo                          │
└──────────────────────────────────────────────────────────────┘
```

---

## 10. Histórico de Preços e Evolução de Custo

Na tela de edição do insumo, aba "Fornecedores", abaixo da tabela de vínculos:

```
┌────────────────────────────────────────────────────────────────────┐
│ 📈 Evolução de Preço — Últimos 12 meses                           │
│                                                                    │
│  R$                                                                │
│  16 ┤                                                              │
│  14 ┤         ╭──╮      ╭──────────╮                               │
│  12 ┤────────╯    ╰────╯            ╰──────╮                      │
│  10 ┤                                        ╰────                 │
│   8 ┤                                                              │
│     └──────────────────────────────────────────────── meses        │
│      Mai  Jun  Jul  Ago  Set  Out  Nov  Dez  Jan  Fev  Mar  Abr   │
│                                                                    │
│  ── Têxtil Brasil (preferencial)    ── Fornec. Norte               │
│                                                                    │
│  Custo Médio Ponderado Atual: R$ 11,85                             │
│  Último preço (Têxtil Brasil): R$ 10,20 em 10/04/2026             │
└────────────────────────────────────────────────────────────────────┘
```

### 10.1 Regras do Gráfico

- Renderizado com **Chart.js** (já disponível no sistema)
- Dados carregados via AJAX: `?page=supplies&action=getPriceHistory&id=X`
- Uma linha por fornecedor, com cores distintas
- Fornecedor preferencial em destaque (linha mais grossa)
- Período padrão: últimos 12 meses (configurável: 3, 6, 12, 24 meses)
- Tooltip: exibe preço, fornecedor, data e referência (nº do pedido de compra)

### 10.2 Cálculo do Custo Médio Ponderado

```
CMP = (estoque_atual × custo_atual + qtd_entrada × preço_entrada) / (estoque_atual + qtd_entrada)

Exemplo:
  Estoque atual: 100 un × R$ 12,00 = R$ 1.200,00
  Nova entrada:   50 un × R$ 10,00 =   R$ 500,00
  Novo CMP = (1.200 + 500) / (100 + 50) = R$ 11,33
```

### 10.3 Métodos Adicionais

```
Supply::getPriceHistory(supplyId, months)         → dados para gráfico
Supply::recordPriceHistory(data)                  → registrar preço
Supply::calculateWeightedAverageCost(supplyId)    → recalcular CMP
```
