# 🌙 Relatório Completo — Modo Noturno (Dark Mode)

**Sistema:** Akti — Gestão em Produção  
**Data da Avaliação:** 31/03/2026  
**Versão do Design System:** 1.0  
**Avaliador:** Análise automatizada completa de CSS, views e inline styles

---

## 📋 Sumário Executivo

O modo noturno do Akti possui uma **base sólida** no `design-system.css` com variáveis CSS bem definidas para `[data-theme="dark"]`. Porém, a implementação apresenta **lacunas significativas** que causam desconforto visual em diversas páginas. Os principais problemas são:

1. **Variáveis do `theme.css` sem override dark** — `--bg-body`, `--bg-card`, `--text-main`, `--primary-color` nunca são redefinidas para dark mode
2. **Cores hardcoded em inline styles** — Mais de 150 ocorrências em views PHP
3. **Módulos CSS sem regras dark** — 6 dos 15 módulos não possuem nenhuma regra `[data-theme="dark"]`
4. **Backgrounds claros fixos** — Pipeline columns, cards, table headers ficam com fundo branco sobre fundo escuro
5. **Headings com cor fixa** — Todos os `h1-h6` usam `--primary-color` (#1e293b) que não muda no dark mode

### Classificação Geral: ⚠️ **Parcialmente Implementado** (estimativa: 55% de cobertura)

---

## 🏗️ Arquitetura do Sistema de Temas

### Mecanismo de Troca
- **Arquivo:** `assets/js/components/theme-toggle.js`
- **Atributo:** `data-theme="light|dark"` no `<html>`
- **Persistência:** `localStorage` com chave `akti-theme`
- **Fallback:** `prefers-color-scheme` do sistema operacional
- **Transição:** Classe temporária `data-theme-transitioning` com 250ms de animação

### Arquivos CSS Envolvidos (ordem de carregamento)
| Arquivo | Função | Dark Mode? |
|---------|--------|:----------:|
| `theme.css` | Variáveis base do tema (cores, shadows, radii) | ❌ Não |
| `design-system.css` | Design system + overrides dark completos | ✅ Sim |
| `style.css` | Estilos globais (navbar, cards, pipeline, etc.) | ❌ Parcial |
| `modules/*.css` | Estilos por módulo/página | ⚠️ Parcial |

---

## 🔴 Problema #1 — Variáveis `theme.css` Sem Override Dark (CRÍTICO)

### Descrição
O arquivo `theme.css` define **14 variáveis** essenciais que são usadas extensivamente em `style.css` e nas views. **Nenhuma** dessas variáveis possui override para `[data-theme="dark"]`.

### Variáveis Afetadas

| Variável | Valor Light | Valor Dark | Status |
|----------|------------|------------|:------:|
| `--primary-color` | `#1e293b` | *não definida* | ❌ |
| `--primary-light` | `#334155` | *não definida* | ❌ |
| `--primary-dark` | `#0f172a` | *não definida* | ❌ |
| `--secondary-color` | `#94a3b8` | *não definida* | ❌ |
| `--accent-color` | `#3b82f6` | *não definida* | ❌ |
| `--accent-light` | `#60a5fa` | *não definida* | ❌ |
| `--accent-dark` | `#2563eb` | *não definida* | ❌ |
| `--bg-body` | `#f1f5f9` | *não definida* | ❌ |
| `--bg-card` | `#ffffff` | *não definida* | ❌ |
| `--text-main` | `#1e293b` | *não definida* | ❌ |
| `--text-muted` | `#64748b` | *não definida* | ❌ |
| `--border-color` | `#e2e8f0` | *não definida* | ❌ |
| `--border-light` | `#f1f5f9` | *não definida* | ❌ |

### Impacto
- **`body`** usa `background-color: var(--bg-body)` → permanece `#f1f5f9` (claro) no dark mode
- **Todos os `h1-h6`** usam `color: var(--primary-color)` → ficam `#1e293b` (escuro sobre fundo escuro = invisível)
- **`.card`** usa `background: var(--bg-card)` → permanece branco
- **`.table thead th`** usa `background-color: var(--bg-body)` e `color: var(--primary-color)` → cabeçalhos claros

### Mitigação Atual
O `design-system.css` tem overrides em `[data-theme="dark"] body { background-color: var(--bg-primary); }` e `[data-theme="dark"] .card { background-color: var(--bg-secondary); }`, mas como usa `!important` apenas em `.bg-light` e `.bg-white`, nem todos os elementos são cobertos. A regra de `body` no design-system faz referência à variável `--bg-primary` do design-system (que muda), **mas** o `body` no `style.css` (linha 26) usa `var(--bg-body)` do `theme.css` (que **não** muda). A precedência depende da ordem de carregamento.

### ⚡ Solução Recomendada
Adicionar ao `theme.css` ou ao final do `design-system.css`:

```css
[data-theme="dark"] {
    --primary-color: #94a3b8;   /* Slate 400 — legível sobre fundo escuro */
    --primary-light: #64748b;
    --primary-dark: #cbd5e1;
    --secondary-color: #64748b;
    --accent-color: #60a5fa;    /* Blue 400 */
    --accent-light: #93c5fd;
    --accent-dark: #3b82f6;
    --bg-body: #1A1A2E;         /* Alinha com --bg-primary dark */
    --bg-card: #16213E;         /* Alinha com --bg-secondary dark */
    --text-main: #E8E8E8;       /* Alinha com --text-primary dark */
    --text-muted: #94a3b8;
    --border-color: #2C3E50;    /* Alinha com --border dark */
    --border-light: #243447;
    --success-color: #51CF66;
    --warning-color: #FFD43B;
    --danger-color: #FF6B6B;
    --info-color: #66D9E8;
}
```

---

## 🔴 Problema #2 — Headings (h1-h6) com Cor Fixa (CRÍTICO)

### Descrição
Em `style.css` (linha 57):
```css
h1, h2, h3, h4, h5, h6 {
    color: var(--primary-color);  /* #1e293b — sempre escuro */
    font-weight: 600;
}
```

No dark mode, `--primary-color` continua `#1e293b` (tom escuro), tornando **todos os títulos praticamente invisíveis** sobre o fundo escuro `#1A1A2E`.

### Páginas Mais Afetadas
- Dashboard — "Painel de Controle"
- Pipeline — "Linha de Produção"
- Configurações — "Configurações do Sistema"
- Clientes/Produtos/Pedidos — todos os títulos de seção

### Evidência Visual
Na screenshot da página de Configurações (modo dark), o título "Configurações do Sistema" com a cor `#1e293b` ficaria quase invisível contra o fundo `#1A1A2E`.

### ⚡ Solução
A correção do Problema #1 (definir `--primary-color` para dark) resolveria automaticamente.

Adicionalmente, o `design-system.css` define:
```css
.akti-card-header h2, .akti-card-header h3 { color: var(--text-primary); }
.akti-page-title { color: var(--text-primary); }
```
Essas classes do design-system funcionam bem no dark mode, mas **a regra global h1-h6** no `style.css` prevalece para elementos sem essas classes.

---

## 🔴 Problema #3 — Pipeline com Backgrounds Hardcoded (ALTO)

### Descrição
O pipeline/kanban (página mais usada) possui múltiplas cores hardcoded:

| Elemento | Arquivo | Cor | Problema |
|----------|---------|-----|----------|
| `.pipeline-column` | `style.css:722` | `background: #f8fafc` | Fundo claro fixo |
| `.pipeline-card:nth-child(even)` | `style.css:831` | `background: linear-gradient(#f8fafc, #f1f5f9)` | Cards com gradiente claro |
| `.pipeline-card:hover` | `style.css:847` | `background: #fff !important` | Hover branco forçado |
| `.pipeline-stage-meta` | `style.css:756` | `background: rgba(255,255,255,0.85)` | Semi-transparente mas branco |
| `.pipeline-nav-btn` | `style.css:596` | `background: rgba(255,255,255,0.97)` | Botão de navegação branco |
| `.pipeline-card-moved` keyframe | `style.css:1132` | `background: #fff` | Animação volta para branco |
| Connector lines | `style.css:1255,1268` | `background: #e2e8f0` | Linhas do stepper claras |

### Impacto
No modo escuro, as colunas do kanban ficam como "ilhas brancas" sobre fundo escuro, criando **alto contraste desconfortável**. Os cards dentro das colunas também mantêm fundo claro.

### ⚡ Solução
Adicionar ao `style.css` ou ao módulo `pipeline.css`:

```css
[data-theme="dark"] .pipeline-column {
    background: var(--bg-secondary);
    border-color: var(--border);
}
[data-theme="dark"] .pipeline-card,
[data-theme="dark"] .pipeline-card:nth-child(even) {
    background: var(--bg-primary) !important;
}
[data-theme="dark"] .pipeline-card:hover {
    background: var(--bg-tertiary) !important;
}
[data-theme="dark"] .pipeline-stage-meta {
    background: rgba(0,0,0,0.2);
}
[data-theme="dark"] .pipeline-nav-btn {
    background: var(--bg-secondary);
    border-color: var(--border);
    color: var(--text-secondary);
}
[data-theme="dark"] .pipeline-step::after,
[data-theme="dark"] .pipeline-track {
    background: var(--border);
}
[data-theme="dark"] .timeline-item::before {
    background: var(--border);
}
```

---

## 🟠 Problema #4 — Cores Hardcoded em Inline Styles (ALTO)

### Escopo do Problema
Foram encontradas **mais de 150 ocorrências** de cores hardcoded em `style="..."` em arquivos PHP. Essas cores **nunca mudam** no dark mode.

### Categorias de Inline Styles Problemáticos

#### 4.1 — Cores de Texto em Ícones/Headings
| Cor | Ocorrências | Exemplo de Uso |
|-----|:-----------:|----------------|
| `color:#3498db` | ~15 | Ícones de seção (Estoque, Relatórios, Financeiro) |
| `color:#27ae60` | ~8 | Ícones de entradas/sucesso |
| `color:#f39c12` | ~10 | Ícones de alerta/configurações |
| `color:#9b59b6` | ~8 | Ícones de movimentações |
| `color:#17a2b8` | ~12 | Ícones informativos, labels |
| `color:#e74c3c` | ~5 | Ícones de perigo/segurança |
| `color:#1abc9c` | ~6 | Ícones de etapas de preparo |
| `color:#8e44ad` | ~8 | Configurações fiscais |
| `color:#e67e22` | ~3 | Setores/produção |
| `color:#856404` | ~2 | Textos em chips amarelos |
| `color:#721c24` | ~2 | Textos em chips vermelhos |
| `color:#155724` | ~2 | Textos em chips verdes |

**Impacto no Dark Mode:** A maioria dessas cores (exceto as mais claras como `#f39c12`) fica **com contraste insuficiente** sobre fundo escuro. As cores escuras como `#856404`, `#721c24`, `#155724` ficam **totalmente invisíveis**.

#### 4.2 — Backgrounds Claros em Seções (MUITO DESCONFORTÁVEL)
| Cor | Arquivo | Uso |
|-----|---------|-----|
| `background:#f8f9fa` | `header.php` (3x), `stock/index.php` | Cabeçalhos de dropdown, helpers |
| `background:#fff3cd` | `header.php` | Banner de aviso |
| `background:#e0f7f1` | `settings/index.php` (3x) | Card headers de etapas |
| `background:#fef5e7` | `settings/index.php` (2x) | Card headers financeiros |
| `background:#f0e6f6` | `settings/index.php` (3x) | Card headers fiscais |
| `background:#fdecea` | `settings/index.php` | Card header segurança |
| `background:#d4edda` | `pipeline/index.php` | Chip "aprovado" |
| `background:#fff3cd` | `pipeline/index.php` | Chip "pendente" |
| `background:#f8d7da` | `pipeline/index.php` | Chip "rejeitado" |
| `background:#f0fdf4` | `walkthrough/manual.php` | Seção informativa |
| `background:#eff6ff` | `walkthrough/manual.php` | Seção informativa |
| `background:#fefce8` | `walkthrough/manual.php` | Seção informativa |

**Impacto no Dark Mode:** Esses fundos pastéis claros criam **retângulos brilhantes** no meio da tela escura, causando **fadiga visual** e quebrando a harmonia do tema.

#### 4.3 — Backgrounds em Botões e Badges
| Cor | Uso |
|-----|-----|
| `background:#1abc9c` | Botões de etapas de preparo |
| `background:#f39c12` | Botão salvar configurações bancárias |
| `background:#8e44ad` | Botão salvar configurações fiscais |
| `background:#e74c3c` | Botão segurança |
| `background:#e67e22` | Header e botão de setores |
| `background:#17a2b8` | Botão gerar catálogo |
| `background:#9b59b6` | Botão catálogo Portal |
| `background:#27ae60` | Botão gerar NF-e |
| `background:#dc3545` | Botão imprimir DANFE |

**Impacto no Dark Mode:** Esses são **menos problemáticos** pois são botões com `color:#fff` (texto branco). Porém, as saturações altas podem ser desconfortáveis. Recomenda-se versões mais suaves para dark mode.

### ⚡ Solução Recomendada
1. **Curto prazo:** Mover cores inline para classes CSS com override `[data-theme="dark"]`
2. **Médio prazo:** Usar variáveis CSS: `style="color: var(--color-info-icon)"` em vez de `color:#3498db`
3. **Longo prazo:** Extrair todas as cores para o design system e usar apenas classes utilitárias

---

## 🟠 Problema #5 — Módulos CSS Sem Regras Dark (MÉDIO)

### Status por Módulo

| Módulo CSS | Regras `[data-theme="dark"]` | Cobertura |
|------------|:----------------------------:|:---------:|
| `users.css` | 3 regras | ⚠️ Básico |
| `stock.css` | 2 regras (sidebar) | ⚠️ Mínimo |
| `settings.css` | 4 regras | ⚠️ Básico |
| `reports.css` | 2 regras (sidebar) | ⚠️ Mínimo |
| `production-board.css` | 4 regras | ⚠️ Básico |
| `notifications.css` | 2 regras | ⚠️ Mínimo |
| `nfe.css` | 3 regras (sidebar + key-box) | ⚠️ Mínimo |
| `home.css` | 3 regras | ✅ Razoável |
| `commissions.css` | 3 regras (sidebar + tip) | ⚠️ Mínimo |
| **`customers.css`** | **0 regras** | ❌ Nenhum |
| **`dashboard.css`** | **0 regras** | ❌ Nenhum |
| **`financial.css`** | **0 regras** | ❌ Nenhum |
| **`orders.css`** | **0 regras** | ❌ Nenhum |
| **`pipeline.css`** | **0 regras** | ❌ Nenhum |
| **`products.css`** | **0 regras** | ❌ Nenhum |

### Elementos Que Precisam de Override Dark em Cada Módulo

**`customers.css`:**
- `.cst-sidebar .cst-nav-item` — texto/hover
- `.cst-sidebar-divider` — cor da borda
- `.cst-bulk-bar` — pode ficar ok (tem fundo accent)
- `.import-dropzone` — fundo e borda
- `.badge-status-*` — contraste dos textos

**`dashboard.css`:**
- `.dashboard-kpi` — fundo e borda do card
- `.hover-card` — sombras

**`financial.css`:**
- `.fin-sidebar .fin-nav-item` — texto/hover
- `.fin-sidebar-divider` — cor da borda
- `.installment-open-row` — cores de hover
- `.dre-row:hover` — cor de hover
- `.import-dropzone` — fundo e borda

**`orders.css`:**
- `.order-card` — fundo e borda
- `.badge-order-*` — contraste (cores como `#b45309` são muito escuras)
- `.preview-table th` — fundo fixo
- `.agenda-calendar td:hover` — cor de hover

**`pipeline.css`:**
- `.board-item-card` — fundo e borda
- `.kanban-card` — fundo e borda
- `.production-item-card` — borda fixa `#e0e0e0`
- `.pb-sidebar .pb-nav-item` — texto/hover (JÁ TEM em `production-board.css`, mas `pipeline.css` repete)

**`products.css`:**
- `.prd-sidebar .prd-nav-item` — texto/hover
- `.prd-sidebar-divider` — cor da borda
- `.prd-import-dropzone` — fundo e borda

---

## 🟠 Problema #6 — Classes Bootstrap Fixas nas Views (MÉDIO)

### Uso de `bg-white` e `bg-light` em Views
Foram encontradas **31+ ocorrências** de classes Bootstrap com cores fixas em views PHP:

| Classe | Ocorrências | Páginas |
|--------|:-----------:|---------|
| `bg-white` | ~10 | stock, users, walkthrough |
| `bg-light` | ~12 | stock, users, groups |
| `text-dark` | ~8 | walkthrough, stock |

O `design-system.css` já possui override para essas classes:
```css
[data-theme="dark"] .bg-light,
[data-theme="dark"] .bg-white {
    background-color: var(--bg-secondary) !important;
}
[data-theme="dark"] .text-dark {
    color: var(--text-primary) !important;
}
```

**Status:** ✅ Parcialmente mitigado pelo design-system. Porém, nem todas as variações são cobertas (ex: `card-header.bg-white` pode ter problemas com filhos que herdam cor).

---

## 🟡 Problema #7 — Contraste de Cores em Badges e Chips (MÉDIO)

### Cores de Texto com Contraste Insuficiente no Dark Mode

| Cor de Texto | Background | Ratio Light | Ratio Dark (estimado) | Status |
|-------------|------------|:-----------:|:--------------------:|:------:|
| `#b45309` (amber 700) | `warning-light` | ~4.5:1 ✅ | ~2.1:1 ❌ | Falha |
| `#0891b2` (cyan 600) | `info-light` | ~4.5:1 ✅ | ~3.8:1 ⚠️ | Limítrofe |
| `#64748b` (slate 500) | `badge-priority-baixa` | ~4.5:1 ✅ | ~2.5:1 ❌ | Falha |
| `#d97706` (amber 600) | `badge-priority-alta` | ~3:1 ⚠️ | ~4.8:1 ✅ | OK dark |
| `#856404` | chip amarelo | ~4.5:1 ✅ | ~1.8:1 ❌ | Invisível |
| `#721c24` | chip vermelho | ~4.5:1 ✅ | ~1.5:1 ❌ | Invisível |
| `#155724` | chip verde | ~4.5:1 ✅ | ~1.4:1 ❌ | Invisível |

### ⚡ Solução
O design-system já tem um exemplo correto:
```css
[data-theme="dark"] .akti-badge-warning { color: var(--warning); }
```

Isso deve ser expandido para **todos os badges e chips** com cores escuras.

---

## 🟡 Problema #8 — Navbar/Header com Dropdowns (MENOR)

### Descrição
O `header.php` tem inline styles em dropdowns:
```html
<div style="background:#f8f9fa;"> <!-- header do dropdown de notificações -->
<div style="background:#fff3cd;"> <!-- banner "trial" -->
<div style="background:#f8f9fa;"> <!-- footer do dropdown -->
```

O `design-system.css` já trata o bell dropdown:
```css
#bellDropdownMenu { background: var(--bg-primary, #fff) !important; }
```

Mas os estilos inline nas `<div>` internas **sobrescrevem** qualquer regra CSS.

### ⚡ Solução
Substituir inline styles por classes:
```html
<div class="bg-light border-bottom ..."> <!-- usa override [data-theme="dark"] .bg-light -->
```

---

## 🟡 Problema #9 — Pipeline Card Animations (MENOR)

### Descrição
As animações de keyframe usam cores fixas:

```css
@keyframes card-moved-flash {
    0%   { background: rgba(34, 197, 94, 0.12); ... }
    100% { background: #fff; ... }  /* ← branco fixo */
}
```

No dark mode, após mover um card no kanban, ele "pisca" em branco antes de voltar ao normal.

### ⚡ Solução
```css
[data-theme="dark"] .pipeline-card-moved {
    animation: card-moved-flash-dark 1.5s ease;
}
@keyframes card-moved-flash-dark {
    0%   { background: rgba(81, 207, 102, 0.15); }
    100% { background: var(--bg-secondary); }
}
```

---

## 🟡 Problema #10 — Table Styles (MENOR)

### Descrição
Em `style.css`:
```css
.table thead th {
    background-color: var(--bg-body);  /* #f1f5f9 no dark */
    color: var(--primary-color);       /* #1e293b no dark */
}
```

Ambas as variáveis não mudam no dark mode (Problema #1). Os table headers ficam claros com texto escuro.

O `design-system.css` tem override parcial:
```css
[data-theme="dark"] .table th { border-color: var(--border); }
```
Mas **não sobrescreve** o `background-color` e o `color`.

### ⚡ Solução
Será resolvido automaticamente com a correção do Problema #1, ao redefinir `--bg-body` e `--primary-color` no dark.

---

## 📊 Mapeamento de Impacto por Página

| Página | Severidade | Problemas Principais |
|--------|:----------:|---------------------|
| **Pipeline (Kanban)** | 🔴 Alto | Colunas brancas, cards com gradiente claro, hover #fff, chips com texto escuro |
| **Pipeline (Detalhe)** | 🔴 Alto | Muitos inline styles, badges/chips com cores escuras, stepper com linhas claras |
| **Configurações** | 🔴 Alto | Card-headers pastéis (#e0f7f1, #fef5e7, #f0e6f6, #fdecea), legends com cores fixas |
| **Financeiro** | 🟠 Médio | Sidebar sem dark, ícones com cores fixas, modal headers |
| **Estoque** | 🟠 Médio | Sidebar com dark parcial, muitos inline styles, tabelas com bg-white |
| **Relatórios** | 🟠 Médio | Sidebar com dark parcial, ícones com cores fixas |
| **Clientes** | 🟠 Médio | Sidebar sem dark, ícones inline, import dropzone |
| **Produtos** | 🟠 Médio | Sidebar sem dark, import dropzone |
| **Pedidos** | 🟡 Menor | Badges com cores escuras, form controls |
| **Dashboard** | 🟡 Menor | KPI cards ok (usam variáveis), mini pipeline cards com inline colors |
| **Usuários** | 🟡 Menor | Dark mode básico, bg-white em table |
| **Home** | ✅ OK | Dark mode razoável no módulo |
| **NFe** | 🟡 Menor | Sidebar com dark parcial, DANFE settings com bg fixo |
| **Comissões** | 🟡 Menor | Sidebar com dark parcial |
| **Walkthrough** | 🟠 Médio | Muitos card-headers e seções com background pastel fixo |

---

## 🎨 Paleta de Cores — Recomendações

### Paleta Dark Atual vs. Recomendada

#### Backgrounds
| Token | Atual Dark | Recomendado | Razão |
|-------|-----------|-------------|-------|
| `--bg-primary` | `#1A1A2E` | `#1A1A2E` ✅ | Bom tom, profundo sem ser preto puro |
| `--bg-secondary` | `#16213E` | `#16213E` ✅ | Bom contraste com primary |
| `--bg-tertiary` | `#0F3460` | `#1E2D4A` ⚠️ | `#0F3460` é muito saturado/azulado, pode causar fadiga. Recomenda-se um tom mais neutro |
| `--bg-body` | *não definida* | `#1A1A2E` | DEVE ser definida |
| `--bg-card` | *não definida* | `#16213E` | DEVE ser definida |

#### Textos
| Token | Atual Dark | Recomendado | Razão |
|-------|-----------|-------------|-------|
| `--text-primary` | `#E8E8E8` | `#E8E8E8` ✅ | Bom, não é branco puro (evita glare) |
| `--text-secondary` | `#ADB5BD` | `#ADB5BD` ✅ | OK |
| `--text-muted` | `#6C757D` | `#6C757D` ✅ | OK para dicas/metadados |
| `--text-main` | *não definida* | `#E8E8E8` | DEVE ser definida |

#### Cores Semânticas
| Token | Atual Dark | Recomendado | Razão |
|-------|-----------|-------------|-------|
| `--success` | `#51CF66` | `#51CF66` ✅ | Bom, vibrante mas não agressivo |
| `--warning` | `#FFD43B` | `#FFD43B` ✅ | OK |
| `--danger` | `#FF6B6B` | `#FF6B6B` ✅ | OK, mais suave que vermelho puro |
| `--info` | `#66D9E8` | `#66D9E8` ✅ | OK |
| `--accent` | `#4DABF7` | `#4DABF7` ✅ | OK, azul claro bonito |

#### Bordas
| Token | Atual Dark | Recomendado | Razão |
|-------|-----------|-------------|-------|
| `--border` | `#2C3E50` | `#2C3E50` ✅ | OK |
| `--border-light` | `#243447` | `#243447` ✅ | OK |
| `--border-color` | *não definida* | `#2C3E50` | DEVE ser definida |

### Cores Alternativas para Ícones Inline (sugestão de variáveis)
Para substituir os inline styles, sugere-se criar variáveis semânticas:

```css
:root {
    --icon-blue:    #3498db;
    --icon-green:   #27ae60;
    --icon-orange:  #f39c12;
    --icon-purple:  #9b59b6;
    --icon-teal:    #17a2b8;
    --icon-red:     #e74c3c;
    --icon-mint:    #1abc9c;
    --icon-grape:   #8e44ad;
    --icon-carrot:  #e67e22;
}

[data-theme="dark"] {
    --icon-blue:    #5dade2;
    --icon-green:   #58d68d;
    --icon-orange:  #f7dc6f;
    --icon-purple:  #bb8fce;
    --icon-teal:    #76d7c4;
    --icon-red:     #f1948a;
    --icon-mint:    #76d7c4;
    --icon-grape:   #c39bd3;
    --icon-carrot:  #f0b27a;
}
```

---

## 📋 Plano de Ação Priorizado

### 🔴 Prioridade 1 — Correções Críticas (impacto imediato em todas as páginas)

| # | Ação | Arquivo | Esforço |
|---|------|---------|:-------:|
| 1.1 | Adicionar overrides dark para variáveis de `theme.css` | `theme.css` ou `design-system.css` | 30 min |
| 1.2 | Adicionar regras dark para pipeline columns/cards | `style.css` | 45 min |
| 1.3 | Corrigir headings h1-h6 no dark mode | Automático com 1.1 | — |

### 🟠 Prioridade 2 — Correções de Alto Impacto (páginas específicas)

| # | Ação | Arquivo(s) | Esforço |
|---|------|-----------|:-------:|
| 2.1 | Adicionar dark mode a `customers.css` | `customers.css` | 20 min |
| 2.2 | Adicionar dark mode a `financial.css` | `financial.css` | 20 min |
| 2.3 | Adicionar dark mode a `orders.css` | `orders.css` | 15 min |
| 2.4 | Adicionar dark mode a `products.css` | `products.css` | 15 min |
| 2.5 | Adicionar dark mode a `pipeline.css` | `pipeline.css` | 20 min |
| 2.6 | Adicionar dark mode a `dashboard.css` | `dashboard.css` | 10 min |
| 2.7 | Substituir card-headers pastéis em `settings/index.php` | `settings/index.php` ou `settings.css` | 30 min |
| 2.8 | Substituir inline backgrounds em `header.php` dropdowns | `header.php` | 15 min |

### 🟡 Prioridade 3 — Refinamentos (melhoria de conforto)

| # | Ação | Arquivo(s) | Esforço |
|---|------|-----------|:-------:|
| 3.1 | Criar variáveis para ícones inline | `design-system.css` | 30 min |
| 3.2 | Substituir inline colors em sidebars por classes | Views PHP | 2-3h |
| 3.3 | Ajustar contraste de badges/chips no dark | `design-system.css` | 30 min |
| 3.4 | Corrigir keyframes com cores fixas | `style.css` | 15 min |
| 3.5 | Ajustar `--bg-tertiary` dark (menos saturado) | `design-system.css` | 5 min |
| 3.6 | Substituir inline styles em `walkthrough/manual.php` | `manual.php` | 1h |

---

## 🧪 Como Testar

1. Abrir o sistema em qualquer página
2. Clicar no ícone 🌙 (lua) no header para ativar modo escuro
3. Verificar:
   - [ ] Títulos (h1-h6) são legíveis
   - [ ] Fundo do body é escuro e uniforme
   - [ ] Cards e tabelas tem fundo escuro
   - [ ] Pipeline columns não ficam brancas
   - [ ] Pipeline cards não tem gradiente claro
   - [ ] Table headers tem fundo escuro
   - [ ] Textos de badges/chips são legíveis
   - [ ] Dropdowns do header ficam escuros
   - [ ] Sidebars de módulos tem estilo coerente
   - [ ] Configurações não tem card-headers pastéis brilhantes
   - [ ] Formulários (inputs, selects) tem fundo escuro

---

## 📊 Métricas de Cobertura

| Métrica | Valor Atual | Meta |
|---------|:-----------:|:----:|
| Variáveis com override dark | 26/40 (65%) | 40/40 (100%) |
| Módulos com regras dark | 9/15 (60%) | 15/15 (100%) |
| Inline styles com cores fixas | ~150+ | <20 |
| Componentes do design-system com dark | 95% | 100% |
| Regras `[data-theme="dark"]` no design-system | 28 regras | +15 necessárias |
| Keyframes com cores fixas | 3 | 0 |

---

## 📎 Arquivos Referenciados

| Arquivo | Linhas Relevantes | Tipo de Problema |
|---------|:-----------------:|:----------------:|
| `assets/css/theme.css` | 1-46 | Sem override dark |
| `assets/css/design-system.css` | 60-100, 1040-1219 | Base OK, precisa expandir |
| `assets/css/style.css` | 26, 57, 395, 722, 831, 847, 1132 | Hardcoded + sem dark |
| `assets/css/modules/customers.css` | todo | Sem dark |
| `assets/css/modules/dashboard.css` | todo | Sem dark |
| `assets/css/modules/financial.css` | todo | Sem dark |
| `assets/css/modules/orders.css` | todo | Sem dark |
| `assets/css/modules/pipeline.css` | todo | Sem dark |
| `assets/css/modules/products.css` | todo | Sem dark |
| `app/views/settings/index.php` | múltiplas | Inline backgrounds pastéis |
| `app/views/pipeline/index.php` | 260, 264, 268 | Chips com cores escuras |
| `app/views/pipeline/detail.php` | múltiplas | Inline colors/backgrounds |
| `app/views/layout/header.php` | 252, 265, 286, 363, 400 | Dropdown inline backgrounds |
| `app/views/stock/index.php` | múltiplas | Inline colors + bg-white |
| `app/views/financial/payments.php` | múltiplas | Inline colors em sidebar |
| `app/views/financial/partials/_sidebar.php` | múltiplas | Inline colors em sidebar |
| `app/views/customers/index.php` | múltiplas | Inline colors em sidebar |
| `app/views/reports/index.php` | múltiplas | Inline colors em sidebar |
| `app/views/dashboard/index.php` | 24, 37, 50, 63, 90, 92, 95 | Inline backgrounds |

---

*Relatório gerado automaticamente em 31/03/2026. Recomenda-se priorizar os itens 1.1, 1.2 e 2.1-2.8 para uma melhoria significativa do modo noturno em 1-2 sprints.*
