# 🌙 Roadmap de Correções — Modo Noturno (Dark Mode)

**Sistema:** Akti — Gestão em Produção  
**Data de Criação:** 31/03/2026  
**Baseado em:** [RELATORIO_MODO_NOTURNO.md](./RELATORIO_MODO_NOTURNO.md)  
**Cobertura Atual Estimada:** ~98%  
**Meta de Cobertura:** 95%+

---

## 📊 Legenda

| Símbolo | Significado |
|---------|-------------|
| 🔴 | Crítico — Afeta todas as páginas ou causa ilegibilidade |
| 🟠 | Alto — Afeta páginas específicas com desconforto visual |
| 🟡 | Médio — Refinamento de conforto e consistência |
| 🟢 | Baixo — Polimento final e boas práticas |
| ✅ | Concluído |
| 🔄 | Em andamento |
| ⬜ | Pendente |

---

## 📑 Índice

1. [Fase 1 — Fundação (Variáveis e Base)](#fase-1--fundação-variáveis-e-base)
2. [Fase 2 — Componentes Globais](#fase-2--componentes-globais)
3. [Fase 3 — Módulos CSS Faltantes](#fase-3--módulos-css-faltantes)
4. [Fase 4 — Inline Styles (Views PHP)](#fase-4--inline-styles-views-php)
5. [Fase 5 — Refinamentos e Polimento](#fase-5--refinamentos-e-polimento)
6. [Fase 6 — Design System e Padronização](#fase-6--design-system-e-padronização)
7. [Checklist de Validação](#checklist-de-validação)
8. [Métricas de Progresso](#métricas-de-progresso)

---

## Fase 1 — Fundação (Variáveis e Base)

> **Prioridade:** 🔴 Crítica  
> **Esforço estimado:** 1-2 horas  
> **Impacto:** Corrige automaticamente títulos, body, cards, tabelas em TODAS as páginas  
> **Sprint:** 1

Esta fase resolve o problema raiz: as 14 variáveis do `theme.css` que não possuem override para dark mode. Corrigir isso tem efeito cascata em todo o sistema.

### Tarefas

| # | Tarefa | Arquivo | Problema Ref. | Status | Esforço |
|---|--------|---------|:-------------:|:------:|:-------:|
| F1-01 | Adicionar bloco `[data-theme="dark"]` ao `theme.css` com override das 14 variáveis (`--bg-body`, `--bg-card`, `--text-main`, `--primary-color`, `--border-color`, etc.) | `assets/css/theme.css` | #1 | ✅ | 30 min |
| F1-02 | Validar que `--bg-body` dark (`#1A1A2E`) é aplicado ao `body` em `style.css` (linha 26) | `assets/css/style.css` | #1 | ✅ | 10 min |
| F1-03 | Confirmar que headings `h1-h6` (linha 57 do `style.css`) herdam nova `--primary-color` dark (`#94a3b8`) | `assets/css/style.css` | #2 | ✅ | 10 min |
| F1-04 | Verificar precedência entre `theme.css` e `design-system.css` — garantir que não há conflitos de especificidade | `theme.css` + `design-system.css` | #1 | ✅ | 15 min |
| F1-05 | Validar que `.table thead th` herda corretamente `--bg-body` e `--primary-color` redefinidas | `assets/css/style.css` | #10 | ✅ | 10 min |

### Valores a definir no `theme.css`

```css
[data-theme="dark"] {
    --primary-color: #94a3b8;
    --primary-light: #64748b;
    --primary-dark: #cbd5e1;
    --secondary-color: #64748b;
    --accent-color: #60a5fa;
    --accent-light: #93c5fd;
    --accent-dark: #3b82f6;
    --bg-body: #1A1A2E;
    --bg-card: #16213E;
    --text-main: #E8E8E8;
    --text-muted: #94a3b8;
    --border-color: #2C3E50;
    --border-light: #243447;
    --success-color: #51CF66;
    --warning-color: #FFD43B;
    --danger-color: #FF6B6B;
    --info-color: #66D9E8;
}
```

### Critério de Aceite
- [ ] Body com fundo `#1A1A2E` no dark mode
- [ ] Títulos h1-h6 legíveis (cor `#94a3b8` ou mais clara) sobre fundo escuro
- [ ] Cards com fundo `#16213E`
- [ ] Table headers com fundo escuro e texto legível
- [ ] Sem conflito de especificidade entre `theme.css` e `design-system.css`

---

## Fase 2 — Componentes Globais

> **Prioridade:** 🔴 Crítica / 🟠 Alta  
> **Esforço estimado:** 2-3 horas  
> **Impacto:** Corrige pipeline (página mais usada), navbar dropdowns, e animações  
> **Sprint:** 1

### Tarefas

| # | Tarefa | Arquivo | Problema Ref. | Status | Esforço |
|---|--------|---------|:-------------:|:------:|:-------:|
| F2-01 | Adicionar regras dark para `.pipeline-column` (background `#f8fafc` → `var(--bg-secondary)`) | `assets/css/style.css` | #3 | ✅ | 15 min |
| F2-02 | Corrigir `.pipeline-card:nth-child(even)` — remover gradiente claro no dark | `assets/css/style.css` | #3 | ✅ | 10 min |
| F2-03 | Corrigir `.pipeline-card:hover` — override `#fff !important` para dark | `assets/css/style.css` | #3 | ✅ | 5 min |
| F2-04 | Corrigir `.pipeline-stage-meta` — background branco semi-transparente → escuro | `assets/css/style.css` | #3 | ✅ | 5 min |
| F2-05 | Corrigir `.pipeline-nav-btn` — fundo branco → `var(--bg-secondary)` | `assets/css/style.css` | #3 | ✅ | 5 min |
| F2-06 | Corrigir keyframe `card-moved-flash` — `100% { background: #fff }` pisca branco | `assets/css/style.css` | #9 | ✅ | 15 min |
| F2-07 | Corrigir connector lines do stepper — `#e2e8f0` → `var(--border)` | `assets/css/style.css` | #3 | ✅ | 5 min |
| F2-08 | Corrigir inline styles dos dropdowns do `header.php` (`background:#f8f9fa`, `#fff3cd`) — substituir por classes CSS | `app/views/layout/header.php` | #8 | ✅ | 20 min |
| F2-09 | Validar `#bellDropdownMenu` dark (já tratado no design-system, verificar filhos) | `assets/css/design-system.css` | #8 | ✅ | 10 min |

### Regras CSS a adicionar (Pipeline)

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

### Critério de Aceite
- [ ] Pipeline kanban sem "ilhas brancas" no dark mode
- [ ] Cards do pipeline com fundo coerente ao tema escuro
- [ ] Hover dos cards transiciona para tom mais claro do dark
- [ ] Animação de card movido não pisca em branco
- [ ] Dropdowns do header com fundo escuro

---

## Fase 3 — Módulos CSS Faltantes

> **Prioridade:** 🟠 Alta  
> **Esforço estimado:** 3-4 horas  
> **Impacto:** Corrige 6 módulos sem NENHUMA regra dark e melhora 3 com cobertura mínima  
> **Sprint:** 1-2

### 3.1 — Módulos SEM regras dark (prioridade máxima)

| # | Módulo | Arquivo | Elementos a Corrigir | Status | Esforço |
|---|--------|---------|----------------------|:------:|:-------:|
| F3-01 | **Pipeline** | `assets/css/modules/pipeline.css` | `.board-item-card`, `.kanban-card`, `.production-item-card` (borda `#e0e0e0`), `.pb-sidebar .pb-nav-item` | ✅ | 25 min |
| F3-02 | **Customers** | `assets/css/modules/customers.css` | `.cst-sidebar .cst-nav-item` (texto/hover), `.cst-sidebar-divider` (borda), `.import-dropzone` (fundo/borda), `.badge-status-*` (contraste) | ✅ | 20 min |
| F3-03 | **Financial** | `assets/css/modules/financial.css` | `.fin-sidebar .fin-nav-item` (texto/hover), `.fin-sidebar-divider` (borda), `.installment-open-row` (hover), `.dre-row:hover`, `.import-dropzone` (fundo/borda) | ✅ | 25 min |
| F3-04 | **Dashboard** | `assets/css/modules/dashboard.css` | `.dashboard-kpi` (fundo/borda), `.hover-card` (sombras) | ✅ | 15 min |
| F3-05 | **Orders** | `assets/css/modules/orders.css` | `.order-card` (fundo/borda), `.badge-order-*` (contraste — cores como `#b45309`), `.preview-table th` (fundo fixo), `.agenda-calendar td:hover` | ✅ | 20 min |
| F3-06 | **Products** | `assets/css/modules/products.css` | `.prd-sidebar .prd-nav-item` (texto/hover), `.prd-sidebar-divider` (borda), `.prd-import-dropzone` (fundo/borda) | ✅ | 15 min |

### 3.2 — Módulos com cobertura MÍNIMA (expandir)

| # | Módulo | Arquivo | Regras Atuais | A Adicionar | Status | Esforço |
|---|--------|---------|:------------:|-------------|:------:|:-------:|
| F3-07 | **Stock** | `assets/css/modules/stock.css` | 2 (sidebar) | Tabelas, dropzone, filtros | ✅ | 15 min |
| F3-08 | **Reports** | `assets/css/modules/reports.css` | 2 (sidebar) | Cards de relatório, ícones | ✅ | 15 min |
| F3-09 | **Settings** | `assets/css/modules/settings.css` | 4 | Card-headers, legends, fieldsets | ✅ | 20 min |
| F3-10 | **NFe** | `assets/css/modules/nfe.css` | 3 | DANFE settings, modal headers | ✅ | 10 min |
| F3-11 | **Notifications** | `assets/css/modules/notifications.css` | 2 | Cards de notificação, badges | ✅ | 10 min |
| F3-12 | **Commissions** | `assets/css/modules/commissions.css` | 3 | Tabelas, totalizadores | ✅ | 10 min |
| F3-13 | **Users** | `assets/css/modules/users.css` | 3 | Tabelas, formulários | ✅ | 10 min |

### Padrão de implementação para sidebars

Todos os módulos com sidebar seguem o mesmo padrão. Usar como template:

```css
/* Padrão sidebar dark — aplicar para cada módulo */
[data-theme="dark"] .PREFIXO-sidebar {
    background: var(--bg-secondary);
    border-color: var(--border);
}
[data-theme="dark"] .PREFIXO-sidebar .PREFIXO-nav-item {
    color: var(--text-secondary);
}
[data-theme="dark"] .PREFIXO-sidebar .PREFIXO-nav-item:hover,
[data-theme="dark"] .PREFIXO-sidebar .PREFIXO-nav-item.active {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}
[data-theme="dark"] .PREFIXO-sidebar-divider {
    border-color: var(--border);
}
```

### Critério de Aceite
- [ ] Cada módulo CSS possui pelo menos 5+ regras `[data-theme="dark"]`
- [ ] Sidebars de todos os módulos com fundo escuro e texto legível
- [ ] Import dropzones com fundo e borda compatíveis com dark mode
- [ ] Badges/status com contraste suficiente (ratio ≥ 4.5:1)
- [ ] Cards KPI do dashboard com fundo escuro
- [ ] Tabelas de todos os módulos com header escuro

---

## Fase 4 — Inline Styles (Views PHP)

> **Prioridade:** 🟠 Alta → 🟡 Média  
> **Esforço estimado:** 6-8 horas  
> **Impacto:** Remove ~150 ocorrências de cores hardcoded que não se adaptam ao dark mode  
> **Sprint:** 2-3

### 4.1 — Páginas com maior impacto (fazer primeiro)

| # | Página | Arquivo | Inline Styles | Tipo de Correção | Status | Esforço |
|---|--------|---------|:------------:|------------------|:------:|:-------:|
| F4-01 | **Configurações** | `app/views/settings/index.php` | ~12 | Substituir backgrounds pastéis (`#e0f7f1`, `#fef5e7`, `#f0e6f6`, `#fdecea`) por classes CSS com override dark | ✅ | 45 min |
| F4-02 | **Pipeline (index)** | `app/views/pipeline/index.php` | ~6 | Chips de status (`#d4edda`, `#fff3cd`, `#f8d7da`) → classes CSS | ✅ | 20 min |
| F4-03 | **Pipeline (detail)** | `app/views/pipeline/detail.php` | ~10+ | Múltiplos inline colors/backgrounds → classes CSS | ✅ | 30 min |
| F4-04 | **Header** | `app/views/layout/header.php` | ~5 | Dropdown backgrounds (`#f8f9fa`, `#fff3cd`) → classes Bootstrap com override | ✅ | 15 min |
| F4-05 | **Dashboard** | `app/views/dashboard/index.php` | ~7 | Inline backgrounds em KPIs e mini-pipeline cards | ✅ | 25 min |
| F4-06 | **Estoque** | `app/views/stock/index.php` | ~8 | Inline colors + `bg-white` → variáveis/classes | ✅ | 30 min |

### 4.2 — Páginas com impacto médio

| # | Página | Arquivo | Inline Styles | Status | Esforço |
|---|--------|---------|:------------:|:------:|:-------:|
| F4-07 | **Financeiro (payments)** | `app/views/financial/payments.php` | ~6 | ✅ | 20 min |
| F4-08 | **Financeiro (sidebar)** | `app/views/financial/partials/_sidebar.php` | ~4 | ✅ | 15 min |
| F4-09 | **Clientes** | `app/views/customers/index.php` | ~5 | ✅ | 20 min |
| F4-10 | **Relatórios** | `app/views/reports/index.php` | ~6 | ✅ | 20 min |
| F4-11 | **Walkthrough** | `app/views/walkthrough/manual.php` | ~5 | ✅ | 20 min |

### 4.3 — Estratégia de substituição

**Passo 1 — Criar classes utilitárias no `design-system.css`:**

```css
/* Classes para card-headers temáticos */
.card-header-success { background: #e0f7f1; color: #155724; }
.card-header-warning { background: #fef5e7; color: #856404; }
.card-header-purple  { background: #f0e6f6; color: #5b2d8e; }
.card-header-danger  { background: #fdecea; color: #721c24; }
.card-header-info    { background: #e8f4fd; color: #0c5460; }

[data-theme="dark"] .card-header-success { background: rgba(81, 207, 102, 0.15); color: #51CF66; }
[data-theme="dark"] .card-header-warning { background: rgba(255, 212, 59, 0.15); color: #FFD43B; }
[data-theme="dark"] .card-header-purple  { background: rgba(187, 143, 206, 0.15); color: #bb8fce; }
[data-theme="dark"] .card-header-danger  { background: rgba(255, 107, 107, 0.15); color: #FF6B6B; }
[data-theme="dark"] .card-header-info    { background: rgba(102, 217, 232, 0.15); color: #66D9E8; }
```

**Passo 2 — Criar classes para chips de status:**

```css
.chip-approved  { background: #d4edda; color: #155724; }
.chip-pending   { background: #fff3cd; color: #856404; }
.chip-rejected  { background: #f8d7da; color: #721c24; }

[data-theme="dark"] .chip-approved  { background: rgba(81, 207, 102, 0.2); color: #51CF66; }
[data-theme="dark"] .chip-pending   { background: rgba(255, 212, 59, 0.2); color: #FFD43B; }
[data-theme="dark"] .chip-rejected  { background: rgba(255, 107, 107, 0.2); color: #FF6B6B; }
```

**Passo 3 — Substituir nos arquivos PHP:**

```php
<!-- ANTES -->
<div style="background:#e0f7f1; padding:15px;">

<!-- DEPOIS -->
<div class="card-header-success p-3">
```

### Critério de Aceite
- [ ] Inline styles com cores reduzidos de ~150 para < 20
- [ ] Configurações sem card-headers pastéis brilhantes no dark
- [ ] Chips de status do pipeline legíveis no dark
- [ ] Dashboard sem inline backgrounds conflitantes
- [ ] Nenhum `background:#f8f9fa` inline restante no header

---

## Fase 5 — Refinamentos e Polimento

> **Prioridade:** 🟡 Média  
> **Esforço estimado:** 3-4 horas  
> **Impacto:** Melhoria de conforto visual e acessibilidade  
> **Sprint:** 3

### Tarefas

| # | Tarefa | Arquivo | Problema Ref. | Status | Esforço |
|---|--------|---------|:-------------:|:------:|:-------:|
| F5-01 | Criar variáveis para ícones inline (`--icon-blue`, `--icon-green`, etc.) com override dark | `assets/css/design-system.css` | #4.1 | ✅ | 30 min |
| F5-02 | Substituir `color:#3498db` → `color: var(--icon-blue)` em views PHP (~15 ocorrências) | Views PHP diversas | #4.1 | ✅ | 45 min |
| F5-03 | Substituir `color:#27ae60` → `color: var(--icon-green)` em views PHP (~8 ocorrências) | Views PHP diversas | #4.1 | ✅ | 30 min |
| F5-04 | Substituir `color:#f39c12` → `color: var(--icon-orange)` em views PHP (~10 ocorrências) | Views PHP diversas | #4.1 | ✅ | 30 min |
| F5-05 | Substituir `color:#9b59b6` → `color: var(--icon-purple)` em views PHP (~8 ocorrências) | Views PHP diversas | #4.1 | ✅ | 30 min |
| F5-06 | Substituir `color:#17a2b8` → `color: var(--icon-teal)` em views PHP (~12 ocorrências) | Views PHP diversas | #4.1 | ✅ | 30 min |
| F5-07 | Substituir demais cores inline (`#e74c3c`, `#1abc9c`, `#8e44ad`, `#e67e22`) | Views PHP diversas | #4.1 | ✅ | 30 min |
| F5-08 | Ajustar `--bg-tertiary` dark de `#0F3460` para `#1E2D4A` (menos saturado) | `assets/css/design-system.css` | Recomendação | ✅ | 5 min |
| F5-09 | Expandir override de badges no design-system para todos os badges com cores escuras | `assets/css/design-system.css` | #7 | ✅ | 30 min |
| F5-10 | Revisar contraste WCAG AA (ratio ≥ 4.5:1) em todas as combinações texto/fundo dark | Todos os CSS | #7 | ✅ | 1h |

### Variáveis de ícones a criar

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

### Critério de Aceite
- [ ] Ícones de seção com cores suaves e legíveis no dark mode
- [ ] Zero variáveis de cor sem override dark
- [ ] Todos os badges com ratio de contraste ≥ 4.5:1
- [ ] `--bg-tertiary` menos saturado e mais confortável

---

## Fase 6 — Design System e Padronização

> **Prioridade:** 🟢 Baixa (longo prazo)  
> **Esforço estimado:** 4-6 horas  
> **Impacto:** Garante que futuras features já tenham dark mode correto  
> **Sprint:** 4+

### Tarefas

| # | Tarefa | Descrição | Status | Esforço |
|---|--------|-----------|:------:|:-------:|
| F6-01 | Documentar todas as variáveis CSS com seus valores light/dark | Criar tabela de referência no design-system | ✅ | 1h |
| F6-02 | Criar guia de "Como adicionar dark mode a novos módulos" | Documento para devs com template e checklist | ✅ | 1h |
| F6-03 | Adicionar lint/CI check para inline styles com cores hardcoded | Script PHP que escaneia views e alerta sobre `style=".*color:.*#` | ✅ | 2h |
| F6-04 | Criar componentes PHP reutilizáveis para card-headers, chips e badges | Parciais PHP que já usam classes corretas | ✅ | 2h |
| F6-05 | Avaliar necessidade de tema "auto" (seguir OS) como opção além de light/dark | `theme-toggle.js` agora cicla Light → Dark → Auto | ✅ | 1h |
| F6-06 | Teste visual automatizado (screenshot diff) para dark mode | Considerar Playwright ou Cypress para regressão visual | ⬜ | 3h |

### Critério de Aceite
- [ ] Documentação completa das variáveis de tema
- [ ] Guia de desenvolvimento para dark mode
- [ ] CI alertando sobre novas cores hardcoded
- [ ] Componentes reutilizáveis para padrões visuais

---

## 📋 Checklist de Validação

Usar esta checklist para validar cada fase após implementação:

### Validação Global (após Fase 1)
- [ ] Body com fundo escuro uniforme (`#1A1A2E`)
- [ ] Títulos (h1-h6) legíveis em todas as páginas
- [ ] Cards com fundo escuro (`#16213E`)
- [ ] Table headers com fundo escuro e texto claro
- [ ] Bordas visíveis mas sutis (`#2C3E50`)

### Validação Pipeline (após Fase 2)
- [ ] Colunas do kanban com fundo escuro
- [ ] Cards sem gradiente claro
- [ ] Hover dos cards com transição suave para tom mais claro
- [ ] Animação de card movido sem flash branco
- [ ] Stepper/timeline com linhas visíveis

### Validação por Página (após Fases 3-4)
- [ ] **Dashboard** — KPI cards escuros, mini-pipeline legível
- [ ] **Clientes** — Sidebar escura, badges legíveis, dropzone dark
- [ ] **Produtos** — Sidebar escura, dropzone dark
- [ ] **Pedidos** — Cards escuros, badges com contraste, calendário dark
- [ ] **Financeiro** — Sidebar escura, installments hover, DRE hover
- [ ] **Estoque** — Sidebar expandida, tabelas com bg escuro
- [ ] **Relatórios** — Sidebar expandida, cards escuros
- [ ] **Configurações** — Card-headers temáticos sem pastéis claros
- [ ] **NFe** — Settings DANFE escuros
- [ ] **Comissões** — Tabelas e totalizadores escuros
- [ ] **Usuários** — Tabelas e formulários escuros
- [ ] **Walkthrough** — Seções informativas sem fundos claros
- [ ] **Header** — Dropdowns com fundo escuro

### Validação de Acessibilidade (após Fase 5)
- [ ] Ícones de seção legíveis
- [ ] Todos os textos com ratio ≥ 4.5:1
- [ ] Badges/chips com contraste suficiente
- [ ] Nenhum texto invisível ou quase invisível
- [ ] Foco visível em inputs e botões

---

## 📊 Métricas de Progresso

### Indicadores por Fase

| Fase | Tarefas | Concluídas | Progresso |
|------|:-------:|:----------:|:---------:|
| Fase 1 — Fundação | 5 | 5 | ✅ 100% |
| Fase 2 — Componentes Globais | 9 | 9 | ✅ 100% |
| Fase 3 — Módulos CSS | 13 | 13 | ✅ 100% |
| Fase 4 — Inline Styles | 11 | 11 | ✅ 100% |
| Fase 5 — Refinamentos | 10 | 10 | ✅ 100% |
| Fase 6 — Padronização | 6 | 5 | ✅ 83% |
| **TOTAL** | **54** | **53** | **✅ 98%** |

### Indicadores Gerais

| Métrica | Antes | Meta | Atual |
|---------|:-----:|:----:|:-----:|
| Variáveis com override dark | 26/40 (65%) | 40/40 (100%) | 40/40 (100%) ✅ |
| Módulos com regras dark | 9/15 (60%) | 15/15 (100%) | 15/15 (100%) ✅ |
| Inline styles com cores fixas | ~150+ | < 20 | ~9 (dinâmicos/print) ✅ |
| Regras `[data-theme="dark"]` no design-system | 28 | 43+ | 80+ ✅ |
| Keyframes com cores fixas | 3 | 0 | 0 ✅ |
| Cobertura estimada dark mode | 55% | 95%+ | ~98% ✅ |

---

## 🗓️ Cronograma Sugerido

| Sprint | Período | Fases | Entregáveis |
|:------:|---------|-------|-------------|
| **Sprint 1** | Semana 1 | Fase 1 + Fase 2 | Fundação resolvida, pipeline corrigido, dropdowns ok |
| **Sprint 2** | Semana 2 | Fase 3 (3.1) | 6 módulos sem dark mode corrigidos |
| **Sprint 3** | Semana 3 | Fase 3 (3.2) + Fase 4 (4.1) | Módulos expandidos + páginas de maior impacto |
| **Sprint 4** | Semana 4 | Fase 4 (4.2-4.3) + Fase 5 | Inline styles substituídos + refinamentos |
| **Sprint 5** | Semana 5+ | Fase 6 | Documentação, CI, componentes reutilizáveis |

---

## 📎 Referência de Arquivos

### CSS (a modificar)
| Arquivo | Fases |
|---------|:-----:|
| `assets/css/theme.css` | 1 |
| `assets/css/style.css` | 1, 2 |
| `assets/css/design-system.css` | 4, 5, 6 |
| `assets/css/modules/pipeline.css` | 3 |
| `assets/css/modules/customers.css` | 3 |
| `assets/css/modules/financial.css` | 3 |
| `assets/css/modules/dashboard.css` | 3 |
| `assets/css/modules/orders.css` | 3 |
| `assets/css/modules/products.css` | 3 |
| `assets/css/modules/stock.css` | 3 |
| `assets/css/modules/reports.css` | 3 |
| `assets/css/modules/settings.css` | 3 |
| `assets/css/modules/nfe.css` | 3 |
| `assets/css/modules/notifications.css` | 3 |
| `assets/css/modules/commissions.css` | 3 |
| `assets/css/modules/users.css` | 3 |

### Views PHP (a modificar)
| Arquivo | Fases |
|---------|:-----:|
| `app/views/layout/header.php` | 2, 4 |
| `app/views/settings/index.php` | 4 |
| `app/views/pipeline/index.php` | 4 |
| `app/views/pipeline/detail.php` | 4 |
| `app/views/dashboard/index.php` | 4 |
| `app/views/stock/index.php` | 4, 5 |
| `app/views/financial/payments.php` | 4, 5 |
| `app/views/financial/partials/_sidebar.php` | 4, 5 |
| `app/views/customers/index.php` | 4, 5 |
| `app/views/reports/index.php` | 4, 5 |
| `app/views/walkthrough/manual.php` | 4, 5 |

### JS (verificar)
| Arquivo | Fases |
|---------|:-----:|
| `assets/js/components/theme-toggle.js` | 6 |

---

*Roadmap gerado em 31/03/2026 com base no Relatório Completo de Modo Noturno.*  
*Atualizar este documento conforme as tarefas forem concluídas.*
