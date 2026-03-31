# 🌙 Guia de Dark Mode para Desenvolvedores — Akti

> **Versão:** 1.1 — 31/03/2026  
> **Responsável:** Equipe Akti  
> **Cobertura estimada:** ~98%

---

## 📌 Regras Gerais

### ❌ NUNCA faça isso:
```html
<!-- ERRADO — cor hardcoded que não se adapta ao dark mode -->
<span style="color:#3498db;">Texto</span>
<div style="background:#f8f9fa;">Conteúdo</div>
<i class="fas fa-user" style="color:#27ae60;"></i>
```

### ✅ SEMPRE faça isso:
```html
<!-- CORRETO — usa utility class com override dark automático -->
<span class="text-blue">Texto</span>
<div class="section-bg-gray">Conteúdo</div>
<i class="fas fa-user text-green"></i>
```

---

## 📋 Referência Completa de Utility Classes

### 1. Cores de Texto (`text-*`)

| Classe | Light | Dark | Uso |
|--------|-------|------|-----|
| `text-blue` | `#3498db` | `#4DABF7` | Links, ícones primários |
| `text-green` | `#27ae60` | `#51CF66` | Sucesso, ícones verdes |
| `text-red` | `#e74c3c` | `#FF6B6B` | Erros, alertas |
| `text-orange` | `#f39c12` | `#FFD43B` | Avisos, importação |
| `text-carrot` | `#e67e22` | `#FFA94D` | Ícones laranja |
| `text-purple` | `#9b59b6` | `#bb8fce` | Agenda, contatos |
| `text-grape` | `#8e44ad` | `#c39bd3` | Auditoria, seções roxas |
| `text-teal` | `#16a085` | `#76d7c4` | Ícones teal |
| `text-info-alt` | `#17a2b8` | `#66D9E8` | Dicas, informações |
| `text-navy` | `#2980b9` | `#5dade2` | Links secundários |
| `text-mint` | `#2ecc71` | `#51CF66` | Fluxo de caixa |
| `text-yellow` | `#f1c40f` | `#FFD43B` | Recorrências |
| `text-dark-alt` | `#343a40` | `#adb5bd` | Texto escuro alternativo |

### 2. Nav-Icon (Sidebar Navigation Icons)

Usados nas sidebars dos módulos. Definem `background` + `color` automaticamente.

```html
<span class="fin-nav-icon nav-icon-blue">
    <i class="fas fa-file-invoice-dollar"></i>
</span>
```

| Classe | Cor Light | Cor Dark |
|--------|-----------|----------|
| `nav-icon-blue` | `#3498db` | `#4DABF7` |
| `nav-icon-green` | `#27ae60` | `#51CF66` |
| `nav-icon-orange` | `#f39c12` | `#FFD43B` |
| `nav-icon-red` | `#e74c3c` | `#FF6B6B` |
| `nav-icon-purple` | `#9b59b6` | `#bb8fce` |
| `nav-icon-info` | `#17a2b8` | `#66D9E8` |
| `nav-icon-grape` | `#8e44ad` | `#c39bd3` |
| `nav-icon-carrot` | `#e67e22` | `#FFA94D` |
| `nav-icon-mint` | `#2ecc71` | `#51CF66` |
| `nav-icon-yellow` | `#f1c40f` | `#FFD43B` |
| `nav-icon-red-alt` | `#e74c3c` | `#FF6B6B` |
| `nav-icon-navy` | `#2980b9` | `#5dade2` |
| `nav-icon-teal` | `#16a085` | `#76d7c4` |
| `nav-icon-dark` | `#343a40` | `#adb5bd` |
| `nav-icon-success` | `#28a745` | `#51CF66` |
| `nav-icon-danger` | `#dc3545` | `#FF6B6B` |

### 3. Icon-Circle (Section Header Icons)

Para ícones arredondados com fundo sutil nas seções:

```html
<div class="icon-circle icon-circle-blue me-2">
    <i class="fas fa-users text-blue" style="font-size:.85rem;"></i>
</div>
```

| Classe | Tamanho | Uso |
|--------|---------|-----|
| `icon-circle` | 34×34px | Padrão |
| `icon-circle icon-circle-sm` | 28×28px | Pequeno |
| `icon-circle icon-circle-lg` | 42×42px | Cards KPI |
| `icon-circle icon-circle-xl` | 44×44px | Cards resumo |

Cores: `icon-circle-blue`, `icon-circle-green`, `icon-circle-info`, `icon-circle-purple`, `icon-circle-grape`, `icon-circle-orange`, `icon-circle-carrot`, `icon-circle-red`, `icon-circle-mint`, `icon-circle-success`, `icon-circle-warning`, `icon-circle-primary`, `icon-circle-danger`

### 4. Badge Utilities

Para badges com fundo sutil e texto colorido:

```html
<span class="badge badge-danger-light">3 em atraso</span>
```

Cores: `badge-danger-light`, `badge-success-light`, `badge-blue-light`, `badge-info-light`, `badge-orange-light`, `badge-purple-light`, `badge-teal-light`, `badge-grape-light`, `badge-green-light`, `badge-carrot-light`

### 5. Card Headers

#### Solid (com gradiente e texto branco):
```html
<div class="card-header card-header-nfe-dark">...</div>
```

Cores: `card-header-nfe-dark`, `card-header-nfe-orange`, `card-header-nfe-green`, `card-header-nfe-blue`, `card-header-nfe-purple`, `card-header-nfe-danger`, `card-header-green-alt`

#### Light (fundo sutil/transparente):
```html
<div class="card-header card-header-info-light">...</div>
```

Cores: `card-header-info-light`, `card-header-blue-light`, `card-header-orange-light`, `card-header-purple-light`, `card-header-green-light`, `card-header-grape-light`, `card-header-carrot-light`, `card-header-navy-light`, `card-header-emerald-light`, `card-header-gray-light`, `card-header-danger-light`

### 6. KPI Cards

Para cards de métricas com gradiente sutil:

```html
<div class="card kpi-blue p-3 border-0">...</div>
```

Cores: `kpi-blue`, `kpi-green`, `kpi-red`, `kpi-blue-alt`, `kpi-orange`, `kpi-purple`

### 7. Section Backgrounds

Para blocos de CTA ou empty-state:

```html
<div class="section-bg-green p-4 text-center">...</div>
```

Cores: `section-bg-green`, `section-bg-purple`, `section-bg-danger`, `section-bg-warning`, `section-bg-grape`, `section-bg-gray`, `section-bg-info`, `section-bg-carrot`, `section-bg-pink`, `section-bg-teal-light`, `section-bg-purple-light`

### 8. Solid Backgrounds

Para elementos com cor sólida (botões, avatares):

```html
<div class="bg-purple text-white rounded-circle" style="width:40px;height:40px;">...</div>
```

Cores: `bg-purple`, `bg-blue-ds`, `bg-green-ds`, `bg-carrot-ds`, `bg-grape-ds`, `bg-teal-ds`, `bg-mint-ds`

### 9. Dropdown Themed

```html
<div class="dropdown-menu dropdown-themed">...</div>
```

### 10. Form Label Muted

```html
<label class="form-label-muted">Campo</label>
```

### 11. Fieldset Utilities

Para fieldsets com bordas coloridas (formulários seccionados):

```html
<fieldset class="p-4 mb-4 fieldset-blue">
    <legend class="float-none w-auto px-2 fs-5">Seção Azul</legend>
    ...
</fieldset>
```

Cores: `fieldset-gray`, `fieldset-purple`, `fieldset-green`, `fieldset-carrot`, `fieldset-teal`, `fieldset-blue`, `fieldset-red`, `fieldset-orange`, `fieldset-info`, `fieldset-navy`

### 12. Preview Area

Para áreas de preview de conteúdo (DANFE, code blocks, etc.):

```html
<div class="preview-area rounded p-3" style="min-height: 400px;">
    <!-- conteúdo de preview -->
</div>
```

---

## 🧩 Componentes PHP Reutilizáveis

Os componentes estão em `app/views/components/` e já usam as utility classes corretas.

### Icon Circle

```php
$iconCircle = ['icon' => 'fas fa-users', 'color' => 'blue', 'size' => 'lg'];
require 'app/views/components/icon-circle.php';
```

### Section Header

```php
$sectionHeader = [
    'icon'  => 'fas fa-list',
    'color' => 'green',
    'title' => 'Listagem de Produtos',
    'badge' => ['text' => '42 itens', 'color' => 'success'],
];
require 'app/views/components/section-header.php';
```

### Summary Card

```php
$summaryCard = [
    'icon'  => 'fas fa-dollar-sign',
    'color' => 'primary',
    'value' => 'R$ 12.500,00',
    'label' => 'Receita Total',
];
require 'app/views/components/summary-card.php';
```

### Badge

```php
$badge = ['text' => '3 em atraso', 'color' => 'danger', 'icon' => 'fas fa-exclamation'];
require 'app/views/components/badge.php';
```

### Chip (Status)

```php
$chip = ['status' => 'approved']; // ou 'pending', 'rejected', 'active', 'overdue', etc.
require 'app/views/components/chip.php';
```

### Card Header

```php
$cardHeader = [
    'title' => 'Detalhes',
    'color' => 'blue', // auto-appends -light
    'icon'  => 'fas fa-info-circle',
];
require 'app/views/components/card-header.php';
```

---

## 🔧 Como Adicionar Dark Mode a Novos Componentes

### Passo 1: Verifique se já existe utility class

Antes de criar uma nova classe, verifique o `design-system.css`. É provável que já exista.

### Passo 2: Crie a classe (se necessário)

Adicione no `assets/css/design-system.css`, seguindo o padrão:

```css
/* Classe light */
.my-new-class { 
    background: #hex-light; 
    color: #hex-text-light; 
}

/* Override dark */
[data-theme="dark"] .my-new-class { 
    background: rgba(R,G,B, 0.12); 
    color: #hex-text-dark; 
}
```

**Regras para cores dark:**
- Fundos: use `rgba()` com opacidade 0.08-0.15 (nunca cores sólidas brilhantes)
- Textos: use versões mais claras/dessaturadas das cores light
- Contraste mínimo: ratio WCAG AA ≥ 4.5:1

### Passo 3: Use a classe na view

```php
<!-- Use a utility class -->
<div class="my-new-class p-3">Conteúdo</div>
```

### Passo 4: Valide

1. Ative o dark mode no sistema (toggle no header)
2. Verifique que o componente ficou legível
3. Verifique contraste com ferramenta do navegador (DevTools → Accessibility)
4. Execute o lint: `php scripts/lint_dark_mode.php` — deve retornar 0 violações

---

## 🎨 Paleta de Cores Dark Mode

### Variáveis CSS (theme.css)

| Variável | Light | Dark |
|----------|-------|------|
| `--bg-body` | `#f8fafc` | `#1A1A2E` |
| `--bg-card` | `#ffffff` | `#16213E` |
| `--text-main` | `#2C3E50` | `#E8E8E8` |
| `--text-muted` | `#6c757d` | `#94a3b8` |
| `--border-color` | `#dee2e6` | `#2C3E50` |
| `--primary-color` | `#2C3E50` | `#94a3b8` |
| `--accent-color` | `#3498db` | `#60a5fa` |
| `--success-color` | `#27ae60` | `#51CF66` |
| `--warning-color` | `#f39c12` | `#FFD43B` |
| `--danger-color` | `#e74c3c` | `#FF6B6B` |

### Variáveis Design System

| Variável | Light | Dark |
|----------|-------|------|
| `--bg-primary` | `#ffffff` | `#16213E` |
| `--bg-secondary` | `#f8f9fa` | `#1a2332` |
| `--bg-tertiary` | `#e9ecef` | `#1E2D4A` |
| `--text-primary` | `#212529` | `#e8e8e8` |
| `--text-secondary` | `#6c757d` | `#a0aec0` |
| `--border` | `#dee2e6` | `#2d3748` |

---

## 📝 Checklist para Novos Módulos

- [ ] **CSS do módulo** tem bloco `[data-theme="dark"]` com ≥ 5 regras
- [ ] **Sidebars** usam `nav-icon-*` utility classes
- [ ] **Card headers** usam `card-header-*` utility classes
- [ ] **Ícones de seção** usam `icon-circle` + `text-*` classes
- [ ] **Badges** usam `badge-*-light` ou Bootstrap `bg-*` classes
- [ ] **Nenhuma cor hardcoded** em inline styles (`style="color:#..."`)
- [ ] **Summary cards** usam `icon-circle` para ícones arredondados
- [ ] **Dropdowns** usam `dropdown-themed`
- [ ] **Contraste** verificado (ratio ≥ 4.5:1)
- [ ] **Testado** alternando entre light e dark mode

---

## 📂 Estrutura de Arquivos CSS

```
assets/css/
├── theme.css              ← Variáveis globais (light + dark overrides)
├── style.css              ← Estilos globais + componentes de pipeline
├── design-system.css      ← Utility classes (nav-icon, badge, text, icon-circle, etc.)
└── modules/
    ├── pipeline.css       ← Kanban board, cards
    ├── customers.css      ← Módulo clientes
    ├── financial.css      ← Módulo financeiro
    ├── dashboard.css      ← Dashboard KPI
    ├── orders.css         ← Módulo pedidos
    ├── products.css       ← Módulo produtos
    ├── stock.css          ← Módulo estoque
    ├── reports.css        ← Módulo relatórios
    ├── settings.css       ← Configurações
    ├── nfe.css            ← Nota fiscal eletrônica
    ├── notifications.css  ← Notificações
    ├── commissions.css    ← Comissões
    └── users.css          ← Gestão de usuários
```

---

## 🔍 Como Verificar Inline Styles Restantes

### Usando o Lint Script (recomendado)

```bash
# Escaneia todas as views e reporta violações
php scripts/lint_dark_mode.php

# Com sugestões de correção automáticas
php scripts/lint_dark_mode.php --fix-hint

# Para CI — retorna exit code 1 se houver violações
php scripts/lint_dark_mode.php --strict
```

### Busca manual (alternativa)

```bash
# Buscar cores hardcoded restantes
grep -rn "style=\".*color:#" app/views/ --include="*.php"
grep -rn "style=\".*background:#" app/views/ --include="*.php"

# Excluir falsos positivos (cores dinâmicas de PHP):
# - $sectorColor, $stageColor — cores de setores/etapas do banco
# - $orderCardColor — cor do card baseada em status
# - print pages — páginas de impressão não precisam de dark mode
```

---

## 🔄 Tema Automático (Auto)

O toggle de tema agora suporta 3 modos, ciclando com cada clique:

**Claro** (`fa-moon`) → **Escuro** (`fa-sun`) → **Auto** (`fa-circle-half-stroke`) → **Claro** ...

No modo **Auto**, o tema segue a preferência do sistema operacional (`prefers-color-scheme`).

### API JavaScript

```javascript
AktiTheme.toggle();        // Cicla entre os 3 modos
AktiTheme.set('auto');     // Define modo automático
AktiTheme.set('dark');     // Define modo escuro
AktiTheme.set('light');    // Define modo claro
AktiTheme.getMode();       // Retorna: 'light', 'dark', ou 'auto'
AktiTheme.get();           // Retorna o tema EFETIVO: 'light' ou 'dark'
```

### Evento de mudança

```javascript
window.addEventListener('akti-theme-changed', function(e) {
    console.log('Tema:', e.detail.theme);  // 'light' ou 'dark'
    console.log('Modo:', e.detail.mode);   // 'light', 'dark' ou 'auto'
});
```

---

*Atualizado em 31/03/2026*
