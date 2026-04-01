# Auditoria Frontend — Views, JavaScript, CSS e UX — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** Camada de apresentação completa — Views PHP, JavaScript, CSS, Design System, Acessibilidade, Responsividade, Dark Mode, PWA  
> **Referência:** Bootstrap 5, WCAG 2.1, Content Security Policy, OWASP XSS Prevention

---

## 1. Resumo Executivo

O frontend do Akti é construído sobre **Bootstrap 5 + jQuery + componentes customizados**, com um Design System baseado em CSS variables, Dark Mode completo e responsividade em 3 breakpoints. A UX é consistente graças ao sistema de componentes reutilizáveis (9 componentes PHP) e módulos JS especializados. As principais lacunas estão em acessibilidade seletiva, scripts inline grandes e ausência de CSP header.

| Aspecto | Nota | Observação |
|---|---|---|
| Design System | ⭐⭐⭐⭐ | CSS variables + componentes reutilizáveis |
| Dark Mode | ⭐⭐⭐⭐ | ~90% cobertura, algumas lacunas em módulos |
| Responsividade | ⭐⭐⭐⭐ | Mobile-first, 3 breakpoints |
| Acessibilidade | ⭐⭐⭐ | Forte em customers, básico nos demais |
| Segurança XSS | ⭐⭐⭐⭐ | htmlspecialchars + escHtml(), poucos riscos |
| Performance | ⭐⭐⭐ | CDN dependencies, sem bundle/minify |

---

## 2. Arquivos JavaScript (21 arquivos)

### 2.1 Scripts Globais

| Arquivo | Propósito | Tamanho | Funcionalidades-Chave |
|---|---|---|---|
| `assets/js/script.js` | Inicialização global, CSRF, máscaras, atalhos | ~150 linhas | CSRF via meta tag → $.ajaxSetup; Máscaras phone/CPF-CNPJ; Ctrl+K, N, ? |
| `assets/js/walkthrough.js` | Tour onboarding com overlay SVG | ~630 linhas | innerHTML para overlay SVG (estático); sessionStorage para resume |
| `assets/js/portal.js` | Portal do cliente (PWA-like) | ~600 linhas | innerHTML para ícones (estático); Fetch API + CSRF; Upload avatar via FormData |

### 2.2 Scripts de Módulos

| Arquivo | Módulo | Propósito | Segurança |
|---|---|---|---|
| `assets/js/customer-select2.js` | Clientes | Select2 AJAX com busca | ✅ `escapeHtml()` helper |
| `assets/js/customer-autosave.js` | Clientes | Auto-save localStorage (30s, 24h expiry) | ✅ Sem PII; verifica modo edição |
| `assets/js/customer-validation.js` | Clientes | Validação CPF/CNPJ/email em tempo real | ✅ Fetch para checkDuplicate, searchCep, searchCnpj |
| `assets/js/customer-masks.js` | Clientes | Máscaras de input (fone, CEP, doc) | ✅ jQuery mask plugin |
| `assets/js/customer-tags.js` | Clientes | Tags autocomplete com chips coloridos | ✅ Hash para cor dinâmica |
| `assets/js/customer-wizard.js` | Clientes | Formulário multi-step wizard | ✅ localStorage para tracking |
| `assets/js/customer-shortcuts.js` | Clientes | Atalhos teclado (Ctrl+D duplicar) | ✅ Context-aware |
| `assets/js/product-select2.js` | Produtos | Select2 com preço/SKU | ✅ `escapeHtml()` |
| `assets/js/financial-payments.js` | Financeiro | Parcelas, pagamentos, DRE, fluxo de caixa | ⚠️ `innerHTML` extenso com `escHtml()` |

### 2.3 Scripts de Componentes (`assets/js/components/`)

| Arquivo | Componente | Funcionalidade | Detalhes |
|---|---|---|---|
| `theme-toggle.js` | Dark/Light toggle | Alternância de tema com persistência | localStorage `akti-theme`; Evento `akti-theme-changed`; `data-theme` no `<html>` |
| `toast.js` | Toast notifications | Alertas não-intrusivos | ARIA `role="status"` + `aria-live="polite"`; Auto-dismiss 3-5s |
| `skeleton.js` | Skeleton loaders | Placeholders durante AJAX | Funções: `table()`, `cards()`, `form()`, `remove()` |
| `notification-bell.js` | Sino de notificações | Poll a cada 60s | `data-delayed-count`; ícones por tipo com cores |
| `dashboard-widgets.js` | Widgets do dashboard | Lazy-load por grupo de usuário | Skeleton placeholders durante carregamento |
| `command-palette.js` | Command palette | Ctrl+K busca global (VS Code-like) | Lista estática + AJAX; prefixo `>` para ações |
| `shortcuts.js` | Atalhos globais | Handler + modal de ajuda | Ctrl+K, Ctrl+S, Esc, ?, N |

### 2.4 Padrões AJAX

Todas as chamadas seguem o padrão:

```javascript
fetch('?page=PAGE&action=ACTION', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
    }
})
```

- ✅ URLs relativas (sem hardcode de domínio)
- ✅ Token CSRF em todas as requisições POST
- ✅ Sem credenciais expostas
- ⚠️ Sem timeout via AbortController
- ⚠️ Sem retry automático para falhas de rede

---

## 3. Arquivos CSS (22 arquivos)

### 3.1 Design System

| Arquivo | Propósito | Detalhes |
|---|---|---|
| `assets/css/design-system.css` | Fundação do design system | ~150 linhas; cores semânticas; sombras; skeleton animations |
| `assets/css/theme.css` | CSS custom properties + dark mode | ~200 linhas; 25+ variáveis; `:root` (light) + `[data-theme="dark"]` |
| `assets/css/style.css` | Estilos principais da aplicação | ~1460 linhas; navbar, cards, tables; breakpoints 576px, 991.98px |

### 3.2 Módulos CSS

| Módulo | Arquivo | Breakpoints | Dark Mode |
|---|---|---|---|
| Dashboard | `dashboard.css` | Tables responsive | ✅ `[data-theme="dark"]` |
| Pipeline | `pipeline.css` | 576px (kanban) | Parcial |
| Financeiro | `financial.css` | Sidebar SPA | ✅ Completo |
| Produtos | `products.css` | Grid responsive | ✅ Completo |
| Pedidos | `orders.css` | Tables + modals | ✅ Completo |
| Estoque | `stock.css` | ~141 linhas, 991.98px | ✅ Completo |
| Usuários | `users.css` | Hover effects | ⚠️ Parcial (linhas 72-88 apenas headers/borders) |
| NF-e | `nfe.css` | Dynamic forms | ✅ Completo |
| Notificações | `notifications.css` | Toast/dropdown | ✅ Completo |
| Produção | `production-board.css` | Kanban board | ✅ Completo |
| Walkthrough | `walkthrough.css` | 768px, 576px | Parcial |
| Portal | `portal.css` | 480px, 576px, 768px | ✅ Extenso |
| Clientes | `customers.css` | Sidebar layout | ✅ Completo |

### 3.3 Dark Mode — Implementação

**Estratégia:** Override de CSS variables via seletor `[data-theme="dark"]`

```css
:root {
    --bg-body: #f1f5f9;
    --text-main: #1e293b;
    --card-bg: #ffffff;
    --border-color: #e2e8f0;
}

[data-theme="dark"] {
    --bg-body: #1A1A2E;
    --text-main: #E8E8E8;
    --card-bg: #16213E;
    --border-color: #2A2A4E;
}
```

**Cobertura:**
- ✅ Cores primárias, secundárias, semânticas (success, warning, danger, info)
- ✅ Backgrounds (body, cards, inputs, modals)
- ✅ Texto (main, muted, secondary)
- ✅ Bordas (variantes escuras)
- ✅ Sombras (intensificadas para contraste)
- ⚠️ **Lacunas:** users.css (apenas headers/borders), pipeline.css (parcial)

**Prevenção de FOUC:** Script inline no `<head>` lê localStorage e aplica `data-theme` antes do render do body.

### 3.4 Breakpoints Responsivos

| Breakpoint | Uso | Módulos |
|---|---|---|
| **480px** | Extra-small mobile | Portal |
| **576px** | Small mobile | Global, Pipeline, Walkthrough |
| **768px** | Tablet | Portal, Walkthrough, Loja |
| **992px** / **991.98px** | Desktop | Global, Estoque |

### 3.5 Carregamento Condicional de CSS

`header.php` implementa um mapa `$__moduleCssMap` com 28 páginas → CSS:

```php
$__moduleCssMap = [
    'dashboard' => 'assets/css/modules/dashboard.css',
    'pipeline'  => 'assets/css/modules/pipeline.css',
    'products'  => 'assets/css/modules/products.css',
    // ... 28 entradas
];
```

**Vantagem:** Apenas o CSS do módulo atual é carregado.

---

## 4. Views PHP (15+ módulos)

### 4.1 Estrutura de Diretórios

| Módulo | Arquivos | Notas |
|---|---|---|
| **customers** | index, create, edit, view (+.bak) | Sidebar unificado (Fase 3) |
| **products** | index, create, edit, _fiscal_partial, _grades_partial | Partials para dados fiscais e grades |
| **orders** | index, create, edit, agenda, print_order, print_quote, report | Agenda calendário + templates de impressão |
| **pipeline** | index, detail, production_board, print_production_order, print_thermal_receipt, settings | Kanban + board de produção |
| **financial** | Seções dinâmicas via AJAX | Sidebar SPA com payments, transactions, recurring, audit |
| **dashboard** | Widget-based via AJAX | Lazy-load de widgets |
| **users** | index, create, edit, groups, profile | Admin + gestão de permissões |
| **stock** | index, warehouses | Gestão de armazéns + histórico de movimentos |
| **settings** | index | Configurações globais |
| **categories** | index | Gestão de categorias |
| **catalog** | Views de catálogo público | Sem auth |
| **site_builder** | index, preview | Editor de site + iframe preview |
| **nfe** | Múltiplas views NF-e | Emissão, consulta, configuração |
| **components** | 9 componentes reutilizáveis | Badges, cards, breadcrumbs, etc. |
| **layout** | header.php, footer.php, pagination.php | Layout base |
| **auth** | login, register | Autenticação |

### 4.2 Layout Base

#### header.php

| Elemento | Detalhe |
|---|---|
| Meta Tags | charset UTF-8, viewport, theme-color (#2c3e50), robots noindex |
| CSRF | `csrf_meta()` helper |
| API URL | `getenv('AKTI_API_URL')` em meta |
| OG/SEO | Facebook og:*, Twitter card, locale pt_BR |
| PWA | manifest.json, apple-touch-icon, mobile-web-app-capable |
| CSS Global | Bootstrap 5.3.0, Google Fonts (Inter), Font Awesome 6.4.0, SweetAlert2, Select2 |
| Design System | design-system.css → theme.css → style.css (ordem garante sem FOUC) |

#### footer.php

| Elemento | Detalhe |
|---|---|
| Logo | akti-logo-light-nBg.svg |
| Scripts | jQuery 3.7.1, Bootstrap 5.3.0, SweetAlert2, componentes do design system |
| Session | Timeout countdown com modal SweetAlert2 para renovação |
| Walkthrough | Auto-load + botão no footer |
| Select2 | product-select2.js, customer-select2.js globalmente |

### 4.3 Componentes Reutilizáveis (`app/views/components/`)

| Componente | Propósito | Escaping | Parâmetros |
|---|---|---|---|
| `badge.php` | Badges de status/tag | `htmlspecialchars()` | $text, $color, $icon |
| `breadcrumb.php` | Navegação breadcrumb | `htmlspecialchars()` | Array de items |
| `card-header.php` | Cabeçalhos de cards | `htmlspecialchars()` | $title, $icon, $actions |
| `chip.php` | Chips pequenos | `htmlspecialchars()` | $text, $color |
| `empty-state.php` | Mensagem "sem resultados" | `htmlspecialchars()` | $message, $icon |
| `flash-messages.php` | Alertas Bootstrap | `htmlspecialchars()` | $_SESSION['flash'] |
| `icon-circle.php` | Ícones circulares | `htmlspecialchars()` | $icon, $color, $size |
| `section-header.php` | Títulos de seção | `htmlspecialchars()` | $title, $subtitle, $badge |
| `summary-card.php` | Cards de KPI | `htmlspecialchars()` | $value, $label, $trend |

**Segurança dos componentes:** ✅ Todos usam `htmlspecialchars()` em variáveis de text/label.

---

## 5. Segurança XSS — Análise Detalhada

### 5.1 Views — Achados

| Arquivo | Linha | Padrão | Veredicto |
|---|---|---|---|
| `pipeline/settings.php` | 90 | `echo $descriptions[$sKey] ?? ''` | ✅ SEGURO — array de config |
| `orders/print_quote.php` | 147 | `echo $prioMap[...]` | ✅ SEGURO — mapa estático |
| `orders/print_order.php` | 171 | `echo $prioMap[...]` | ✅ SEGURO — mapa estático |
| `orders/create.php` | 308 | `echo $popoverContent` | ⚠️ **REVISAR** — origem não verificada |
| `nfe/backup_settings.php` | 238 | `echo $bytes . ' B'` | ✅ SEGURO — cálculo numérico |
| `settings/index.php` | 1124 | `echo $crtMap[...]` | ✅ SEGURO — mapa estático |

### 5.2 JavaScript — innerHTML

| Arquivo | Linhas | Contexto | Risco |
|---|---|---|---|
| `walkthrough.js` | 169, 332, 346, 420 | SVG overlay template (hardcoded) | ✅ SEGURO — conteúdo estático |
| `portal.js` | 205-210 | Ícones FontAwesome | ✅ SEGURO — conteúdo estático |
| `financial-payments.js` | 103, 114-125, 197+ | Tabelas de dados | ⚠️ **MITIGADO** — usa `escHtml()` |
| `stock/index.php` (inline) | 1103 | Tabela de movimentos | ⚠️ **MITIGADO** — dados do servidor |
| `pipeline/index.php` (inline) | 615, 690, 705 | Stock checks | ⚠️ **MITIGADO** — dados do servidor |

### 5.3 Recomendações de Segurança

1. **Verificar `$popoverContent`** em `orders/create.php:308` — garantir escape no controller
2. **Adicionar CSP header** no index.php ou header.php:
   ```
   Content-Security-Policy: script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com code.jquery.com fonts.googleapis.com;
   ```
3. **Extrair scripts inline** de stock/index.php e pipeline/index.php
4. **Adicionar SRI** (Subresource Integrity) nos CDN links
5. **Implementar AbortController** para timeout em fetch requests

---

## 6. Dependências CDN

| Biblioteca | Versão | CDN | SRI |
|---|---|---|---|
| Bootstrap CSS | 5.3.0 | cdn.jsdelivr.net | ❌ |
| Bootstrap JS Bundle | 5.3.0 | cdn.jsdelivr.net | ❌ |
| jQuery | 3.7.1 | code.jquery.com | ❌ |
| jQuery Mask Plugin | 1.14.16 | cdnjs.cloudflare.com | ❌ |
| Font Awesome | 6.4.0 | cdnjs.cloudflare.com | ❌ |
| SweetAlert2 | 11 | cdn.jsdelivr.net | ❌ |
| Select2 | 4.1.0-rc.0 | cdn.jsdelivr.net | ❌ |
| Select2 Bootstrap Theme | 1.3.0 | cdn.jsdelivr.net | ❌ |
| Google Fonts (Inter) | — | fonts.googleapis.com | N/A |
| SortableJS | 1.15.6 | cdn.jsdelivr.net (inline) | ❌ |

**Avaliação:** Nenhum hash de integridade (SRI) configurado. CDN comprometido = risco de supply chain attack.

**Recomendação:** Adicionar `integrity` e `crossorigin` em TODOS os recursos CDN:
```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQAtTT..."
        crossorigin="anonymous"></script>
```

---

## 7. PWA e Manifesto

### 7.1 manifest.json

- **name:** "Akti - Gestão em Produção"
- **short_name:** "Akti"
- **start_url:** "/?page=dashboard"
- **display:** "standalone"
- **theme_color:** "#2c3e50"
- **background_color:** "#ffffff"

### 7.2 Portal Manifest (portal-manifest.json)

- **name:** "Portal do Cliente - Akti"
- **start_url:** "/?page=portal"
- **display:** "standalone"

### 7.3 Service Worker (portal-sw.js)

- PWA offline capability para o portal do cliente
- Cache-first strategy para assets estáticos

---

## 8. Acessibilidade (WCAG 2.1)

### 8.1 Achados Positivos

**Módulo de Clientes (view.php)** — Melhor implementação:
- `aria-label="Voltar para lista de clientes"` (L47)
- `<nav aria-label="Navegação">` (L48)
- `role="tablist"` com `role="tab"` + `aria-controls` + `aria-selected` (L140-162)
- `role="tabpanel"` com `aria-labelledby` (L140-162)

**Layout:**
- `<nav>` semântico para navegação
- `<main>` wrapper
- `<footer>` com landmark role
- Heading hierarchy (h1 → h2 → h3)

### 8.2 Lacunas

| Lacuna | Impacto | Módulos Afetados |
|---|---|---|
| Falta `aria-label` em botões de ação | Screen readers perdem contexto | Products, Orders, Stock |
| Tabelas sem `<caption>` | Propósito da tabela não anunciado | Todos com tabelas |
| Forms sem `aria-describedby` para erros | Erros não conectados ao campo | Todos os formulários |
| Modais sem `aria-modal="true"` | Screen readers não detectam modal | SweetAlert2 dependente |
| Contraste em dark mode | Possível WCAG AA violation | Texto muted em dark mode |

### 8.3 Recomendações

1. Adicionar `aria-label` em todos os botões de ação (editar, excluir, visualizar)
2. Usar `<caption>` em todas as tabelas de dados
3. Implementar `aria-describedby` para mensagens de erro em forms
4. Verificar contraste de cores no dark mode com ferramenta automatizada
5. Testar com NVDA/VoiceOver para navegação por teclado

---

## 9. Performance Frontend

### 9.1 Achados

| Aspecto | Status | Observação |
|---|---|---|
| Bundle/Minificação | ❌ | JS e CSS servidos sem minificação |
| Tree-shaking | ❌ | Sem build tool (webpack, vite, etc.) |
| Lazy loading de imagens | ✅ | IntersectionObserver no loja/theme.js |
| Skeleton loaders | ✅ | Componente dedicado para AJAX loads |
| CSS condicional por módulo | ✅ | Apenas CSS do módulo atual carregado |
| Font preconnect | ✅ | Google Fonts com preconnect |
| CDN | ✅ | Todas libs via CDN (cache compartilhado) |

### 9.2 Oportunidades de Otimização

1. **Minificação:** Adicionar processo de build para concatenar/minificar JS e CSS
2. **Cache headers:** Configurar ETags e Cache-Control para assets estáticos
3. **Image optimization:** Converter imagens para WebP
4. **Critical CSS:** Inline CSS crítico no `<head>` para above-the-fold
5. **Defer/Async:** Marcar scripts não-críticos com defer

---

## 10. Storefront (Loja)

### 10.1 Estrutura

```
loja/
├── assets/
│   ├── css/theme.css     # Variáveis CSS da loja (~100 linhas)
│   ├── js/theme.js       # Smooth scroll + lazy loading (~50 linhas)
│   └── images/
├── layouts/
│   └── base.html.twig    # Template base Twig
├── templates/
│   ├── pages/
│   ├── sections/
│   └── snippets/
└── config/
```

### 10.2 Avaliação

- ✅ **Twig templates** — auto-escaping por padrão (seguro contra XSS)
- ✅ **Vanilla JS** — sem dependências externas
- ✅ **Responsive** — breakpoint 768px
- ✅ **CSS variables** — fácil customização por tenant
- ⚠️ **Sem Service Worker** dedicado (diferente do portal)

---

## 11. Assets Estáticos

### 11.1 Logos (`assets/logos/`)

12 arquivos SVG com variantes completas:
- Ícones: dark/light (SVG + ICO)
- Logos: dark/light com e sem fundo
- Quadrados: dark/light com e sem fundo

### 11.2 Uploads (`assets/uploads/`)

- Estrutura: `tenant_name/products/` — upload isolado por tenant
- Tipo: Imagens de produtos

### 11.3 Imagens (`assets/img/`)

- Imagens estáticas do sistema (backgrounds, ilustrações)

---

## 12. Conclusões e Prioridades

### Forças
1. ✅ Design System CSS com variáveis customizáveis
2. ✅ Dark Mode quase completo com prevenção de FOUC
3. ✅ Componentes PHP reutilizáveis com escape adequado
4. ✅ CSRF em 100% dos formulários e chamadas AJAX
5. ✅ Carregamento condicional de CSS por módulo
6. ✅ PWA com manifest e service worker (portal)
7. ✅ Command palette VS Code-like (Ctrl+K)

### Prioridades de Melhoria

| Prioridade | Item | Esforço | Impacto |
|---|---|---|---|
| 1 | Implementar CSP header | Baixo | Alto (segurança) |
| 2 | Adicionar SRI em recursos CDN | Baixo | Alto (segurança) |
| 3 | Extrair scripts inline para arquivos | Médio | Médio (manutenção) |
| 4 | Completar dark mode em todos módulos CSS | Médio | Médio (UX) |
| 5 | Verificar $popoverContent XSS | Baixo | Alto (segurança) |
| 6 | Melhorar acessibilidade ARIA | Alto | Alto (inclusão) |
| 7 | Implementar build tool (minificação/bundle) | Alto | Médio (performance) |
| 8 | Adicionar fetch timeout (AbortController) | Baixo | Baixo (resiliência) |
