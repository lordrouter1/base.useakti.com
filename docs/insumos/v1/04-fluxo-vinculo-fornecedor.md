# 04 — Fluxo de Vinculação de Insumo a Fornecedor

## 1. Visão Geral

A vinculação entre insumos e fornecedores é uma relação **N:N** (um insumo pode ser fornecido por vários fornecedores, e um fornecedor pode fornecer vários insumos). A tabela pivot `supply_suppliers` armazena metadados da relação: preço, SKU do fornecedor, prazo de entrega e preferência.

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
│  │ ┌──────────┬──────────┬────────┬────────┬──────┬──────┬───┐ │ │
│  │ │Fornecedor│ SKU Forn.│ Preço  │Ped.Mín.│Prazo │ Pref │ ⚙ │ │ │
│  │ ├──────────┼──────────┼────────┼────────┼──────┼──────┼───┤ │ │
│  │ │ Têxtil   │ TC-A001  │ 12,50  │ 50 m   │ 5d   │  ★   │✎🗑│ │ │
│  │ │ Brasil   │          │        │        │      │      │   │ │ │
│  │ ├──────────┼──────────┼────────┼────────┼──────┼──────┼───┤ │ │
│  │ │ Fornec.  │ ALG-200  │ 14,00  │ 100 m  │ 3d   │      │✎🗑│ │ │
│  │ │ Norte    │          │        │        │      │      │   │ │ │
│  │ └──────────┴──────────┴────────┴────────┴──────┴──────┴───┘ │ │
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
