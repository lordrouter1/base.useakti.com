# 03 — Fluxo de Cadastro de Insumos

## 1. Visão Geral

O cadastro de insumos segue o padrão CRUD do sistema Akti, com as mesmas práticas de **Product** e **Supplier**: Model com prepared statements, Controller com Input::post, View com Bootstrap 5 e escape via `e()`.

---

## 2. Fluxo — Diagrama

```
┌──────────────────────────────────────────────────────────────────┐
│                    LISTAGEM DE INSUMOS                           │
│                  ?page=supplies&action=index                     │
│                                                                  │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────────────┐   │
│  │ Filtro por  │  │ Busca por    │  │  [+ Novo Insumo]      │   │
│  │ Categoria   │  │ Nome/Código  │  │  → action=create      │   │
│  └─────────────┘  └──────────────┘  └───────────────────────┘   │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │ Código │ Nome │ Categoria │ Unidade │ Custo │ Estoque │ ⚙ │  │
│  │ INS-01 │ Tec..│ Tecido    │ m       │ 12,50 │ 150 m   │ ✎🗑│ │
│  │ INS-02 │ Tin..│ Tinta     │ L       │ 45,00 │ 30 L    │ ✎🗑│ │
│  │ INS-03 │ Par..│ Fixação   │ un      │ 0,15  │ 5000 un │ ✎🗑│ │
│  └───────────────────────────────────────────────────────────┘   │
│                     ◄ 1 2 3 ... ►                               │
└──────────────────────────────────────────────────────────────────┘
          │                                    │
          │ Clica "Editar"                     │ Clica "Novo Insumo"
          ▼                                    ▼
┌──────────────────────────────────────────────────────────────────┐
│              FORMULÁRIO DE INSUMO (create/edit)                  │
│         ?page=supplies&action=create|edit&id=X                   │
│                                                                  │
│  ┌─ Dados Básicos ──────────────────────────────────────────┐   │
│  │ Código*: [INS-0004]  (auto-gerado, editável)            │   │
│  │ Nome*:   [___________________________]                    │   │
│  │ Categoria: [Selecione ▼] [+ Nova]                        │   │
│  │ Descrição: [___________________________]                  │   │
│  │ Unidade de Medida*: [Selecione ▼]                        │   │
│  │ Status: [● Ativo  ○ Inativo]                             │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─ Custos e Estoque ──────────────────────────────────────┐    │
│  │ Custo Padrão:    [0,0000]                                │   │
│  │ Estoque Mínimo:  [0,0000]                                │   │
│  │ Ponto de Pedido: [0,0000]                                │   │
│  │ % Perda/Desperdício: [0,00]                              │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─ Dados Fiscais (collapse) ──────────────────────────────┐    │
│  │ NCM:     [________]  CEST: [________]                    │   │
│  │ Origem:  [_]          Unidade Fiscal: [____]             │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─ Observações ───────────────────────────────────────────┐    │
│  │ [                                                        ]   │
│  │ [                                                        ]   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  [Cancelar]                                    [Salvar Insumo]  │
└──────────────────────────────────────────────────────────────────┘
          │
          │ POST → action=store|update
          ▼
┌──────────────────────────────────┐
│        VALIDAÇÃO SERVER          │
│                                  │
│ ✓ Nome obrigatório              │
│ ✓ Código único                  │
│ ✓ Unidade de medida válida      │
│ ✓ Valores numéricos >= 0        │
│ ✓ CSRF token válido             │
│                                  │
│   Sucesso → flash_success       │
│   → redirect ?page=supplies     │
│                                  │
│   Erro → flash_error            │
│   → redirect ?page=supplies     │
│     &action=create|edit         │
└──────────────────────────────────┘
```

---

## 3. Regras de Negócio

### 3.1 Código do Insumo

- Formato sugerido: `INS-XXXX` (auto-incremento com padding de 4 dígitos)
- Geração automática no formulário (campo editável)
- Validação de unicidade no `store()` antes do INSERT
- Método no Model: `generateNextCode(): string`

### 3.2 Validações

| Campo | Regra | Mensagem |
|-------|-------|----------|
| `name` | Obrigatório, max 200 chars | "Nome do insumo é obrigatório." |
| `code` | Obrigatório, único, max 50 chars | "Código já existe." |
| `unit_measure` | Obrigatório, deve ser valor válido do ENUM | "Unidade de medida inválida." |
| `cost_price` | Numérico, >= 0 | "Custo deve ser um valor válido." |
| `min_stock` | Numérico, >= 0 | "Estoque mínimo deve ser >= 0." |
| `waste_percent` | Numérico, 0-100 | "% perda deve estar entre 0 e 100." |

### 3.3 Soft Delete

- Ao deletar, gravar `deleted_at = NOW()` em vez de apagar
- Insumos com BOM vinculado **podem** ser desativados (soft delete), mas exibir alerta:
  - _"Este insumo está vinculado a X produto(s). Deseja desativá-lo mesmo assim?"_
- Insumos desativados não aparecem em seleções, mas mantêm histórico

### 3.4 Categorias Inline

- Possibilidade de criar categoria direto do formulário (AJAX, mesmo padrão de `createCategoryAjax` do ProductController)
- Endpoint: `?page=supplies&action=createCategoryAjax`
- Retorna JSON: `{ success: true, id: N, name: "..." }`

---

## 4. Métodos do Model (`Supply`)

```
Supply::readAll()                          → todos os insumos ativos
Supply::readPaginated(page, perPage, filters) → paginado com filtros
Supply::readOne(id)                        → um insumo pelo ID
Supply::create(data)                       → inserir novo insumo
Supply::update(id, data)                   → atualizar insumo
Supply::delete(id)                         → soft delete
Supply::countAll(filters)                  → total para paginação
Supply::generateNextCode()                 → próximo código INS-XXXX
Supply::codeExists(code, excludeId)        → verificar unicidade
Supply::getStockSummary(supplyId)          → total em estoque (sum)
```

---

## 5. Actions do Controller (`SupplyController`)

| Action | HTTP | Método | Descrição |
|--------|------|--------|-----------|
| `index` | GET | `index()` | Listagem paginada com filtros |
| `create` | GET | `create()` | Formulário de criação |
| `store` | POST | `store()` | Processar criação |
| `edit` | GET | `edit()` | Formulário de edição |
| `update` | POST | `update()` | Processar edição |
| `delete` | POST | `delete()` | Soft delete |
| `createCategoryAjax` | POST | `createCategoryAjax()` | Criar categoria via AJAX |
| `getCategoriesAjax` | GET | `getCategoriesAjax()` | Listar categorias (JSON) |

---

## 6. Wireframe — Listagem

```
┌────────────────────────────────────────────────────────────────┐
│ 🧱 Insumos                                    [+ Novo Insumo] │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  Categoria: [Todas ▼]   Busca: [____________] [🔍]           │
│  Status: [● Ativos  ○ Inativos  ○ Todos]                     │
│                                                                │
│  ┌─────┬──────────┬────────────┬──────┬────────┬──────┬─────┐ │
│  │ Cód │ Nome     │ Categoria  │ Un.  │ Custo  │ Est. │ Ações│ │
│  ├─────┼──────────┼────────────┼──────┼────────┼──────┼─────┤ │
│  │ 001 │ Tecido   │ Tecido     │ m    │ 12,50  │ 150  │ ✎ 🗑│ │
│  │ 002 │ Tinta AZ │ Tinta      │ L    │ 45,00  │ ⚠ 5  │ ✎ 🗑│ │
│  │ 003 │ Parafuso │ Fixação    │ un   │ 0,15   │ 5000 │ ✎ 🗑│ │
│  └─────┴──────────┴────────────┴──────┴────────┴──────┴─────┘ │
│                                                                │
│  ⚠ = Abaixo do estoque mínimo (destaque visual em vermelho)   │
│                                                                │
│  Mostrando 1-20 de 47              ◄ 1 [2] 3 ►               │
└────────────────────────────────────────────────────────────────┘
```

---

## 7. Wireframe — Formulário

```
┌────────────────────────────────────────────────────────────────┐
│ 🧱 Novo Insumo                                                │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌─ Dados Básicos ─────────────────────────────┬─ Custos ────┐│
│  │                                              │             ││
│  │ Código*       Unidade*                       │ Custo       ││
│  │ [INS-0004]    [Selecione ▼]                  │ [0,0000]    ││
│  │                                              │             ││
│  │ Nome*                                        │ Est. Mín.   ││
│  │ [________________________________]           │ [0,0000]    ││
│  │                                              │             ││
│  │ Categoria                                    │ Pto Pedido  ││
│  │ [Selecione ▼] [+ Nova]                      │ [0,0000]    ││
│  │                                              │             ││
│  │ Descrição                                    │ % Perda     ││
│  │ [________________________________]           │ [0,00]      ││
│  │ [________________________________]           │             ││
│  │                                              │ Status      ││
│  │                                              │ [✓] Ativo   ││
│  └──────────────────────────────────────────────┴─────────────┘│
│                                                                │
│  ▸ Dados Fiscais (expandir)                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ NCM: [________] CEST: [________] Origem: [_] Un.: [____]│  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                │
│  Observações                                                   │
│  [____________________________________________________________]│
│  [____________________________________________________________]│
│                                                                │
│  [Cancelar]                                   [💾 Salvar]      │
└────────────────────────────────────────────────────────────────┘
```
