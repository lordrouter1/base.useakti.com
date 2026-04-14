# Auditoria de Frontend — Akti v3

> **Data da Auditoria:** 14/04/2025
> **Escopo:** JavaScript, CSS, Design System, dark mode, responsividade, acessibilidade, PWA
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

| Aspecto | Nota | Tendência vs v2 |
|---------|------|------------------|
| JavaScript Inventory | ✅ B+ | ↑ +5 arquivos |
| JS Security | ⚠️ C | = Mantido |
| CSS / Design System | ✅ A- | ↑ Melhorado |
| Dark Mode | ⚠️ B- | ↑ Parcialmente melhorado |
| Responsividade | ✅ B+ | = Mantido |
| Acessibilidade | ⚠️ C | = Mantido |
| PWA | ✅ A | = Mantido |
| Performance | ⚠️ B | = Mantido |

**Nota Geral: B** (v2: C+)

O design system com CSS variables e suporte a dark mode evoluiu significativamente. PWA completa com service worker e push notifications. Principal gap: 5 arquivos JS com `innerHTML` sem DOMPurify e 10+ views com inline scripts.

---

## 2. JavaScript Inventory

### 2.1 Arquivos em `assets/js/` (17 arquivos)

| Arquivo | Linhas | Módulo | Segurança |
|---------|--------|--------|-----------|
| `checkout.js` | ~200 | Checkout portal | ⚠️ Revisar |
| `customer-autosave.js` | ~100 | Clientes | ✅ |
| `customer-completeness.js` | ~80 | Clientes | ✅ |
| `customer-masks.js` | ~60 | Clientes | ✅ |
| `customer-select2.js` | ~150 | Clientes | ⚠️ innerHTML L23 |
| `customer-shortcuts.js` | ~250 | Clientes | ⚠️ innerHTML L220 |
| `customer-tags.js` | ~120 | Clientes | ✅ |
| `customer-validation.js` | ~100 | Clientes | ✅ |
| `customer-wizard.js` | ~200 | Clientes | ✅ |
| `financial-payments.js` | ~150 | Financeiro | ⚠️ Revisar |
| `master.js` | ~300 | Master panel | ✅ |
| `portal.js` | ~400 | Portal | ⚠️ innerHTML L205-210, L355 |
| `product-select2.js` | ~100 | Produtos | ⚠️ innerHTML L27 |
| `script.js` | ~500 | Core utilities | ✅ |
| `walkthrough.js` | ~450 | Onboarding | 🔴 innerHTML L169, L332, L346, L420 |

### 2.2 Segurança JavaScript — innerHTML

**5 arquivos com `innerHTML`:**

| Arquivo | Linha | Risco | Contexto |
|---------|-------|-------|----------|
| `customer-select2.js` | L23 | 🟡 MÉDIO | `return div.innerHTML` — template de Select2 |
| `customer-shortcuts.js` | L220 | 🟢 BAIXO | `hint.innerHTML = '<kbd>...'` — conteúdo estático |
| `portal.js` | L205-210 | 🟢 BAIXO | `submitBtn.innerHTML = originalText` — texto do botão |
| `product-select2.js` | L27 | 🟡 MÉDIO | Template de Select2 |
| **`walkthrough.js`** | **L169, L332, L346, L420** | **🔴 CRÍTICO** | SVG e HTML sem DOMPurify |

**Recomendação para `walkthrough.js`:** Adicionar DOMPurify antes de qualquer `innerHTML`:
```javascript
import DOMPurify from 'dompurify';
element.innerHTML = DOMPurify.sanitize(htmlContent);
```

**Recomendação geral:** Para Select2 templates, usar `text()` do jQuery em vez de `innerHTML` quando possível.

---

## 3. CSS Inventory

### 3.1 Arquivos em `assets/css/` (9 arquivos)

| Arquivo | Tamanho | Dark Mode | Módulo |
|---------|---------|-----------|--------|
| `checkout.css` | Pequeno | ❌ | Portal checkout |
| `customers.css` | Pequeno | ❌ | Clientes |
| `design-system.css` | Grande | ✅ L58+ | Core design |
| `master.css` | Médio | ❌ | Master panel |
| `portal.css` | Médio | ❌ | Portal |
| `style.css` | Grande | ❌ | Legado/global |
| `summernote-fix.css` | Pequeno | ❌ | Editor WYSIWYG |
| `theme.css` | Médio | ✅ L51+ | Tema global |
| `walkthrough.css` | Pequeno | ❌ | Onboarding |

### 3.2 Design System — CSS Variables

**Implementação em `design-system.css`:**

**Light Mode (`:root`)** — L9-53:
- ✅ 30+ custom properties definidas
- ✅ Variáveis para backgrounds, text, accent, semantic colors
- ✅ Variáveis RGB para `rgba()` usage
- ✅ Shadows, border-radius, transitions

**Dark Mode (`[data-theme="dark"]`)** — L58+:
- ✅ Override completo de cores de fundo
- ✅ Override completo de cores de texto
- ✅ Override de acentos e cores semânticas
- ✅ Override de sombras

**Complemento em `theme.css`** — L51+:
- ✅ Paleta dark completa adicional
- ✅ Overrides específicos de componentes

---

## 4. Dark Mode Coverage

### Status: ⚠️ B- — 10+ views com cores hardcoded

**Views com classes Bootstrap hardcoded (sem override dark):**

| Arquivo | Linhas | Classes Problemáticas |
|---------|--------|-----------------------|
| `app/views/workflows/index.php` | L52 | `bg-light text-dark` |
| `app/views/workflows/form.php` | L91 | `bg-light text-dark` |
| `app/views/walkthrough/manual.php` | L132, L204, L401, L446, L474, L485 | `bg-warning text-dark`, `bg-white` |
| `app/views/users/profile.php` | L20 | `bg-light text-dark` |
| `app/views/users/index.php` | L29, L32 | `bg-white`, `bg-light` |
| `app/views/users/groups.php` | L72, L101, L132 | `bg-light` |
| `app/views/supply_stock/movements.php` | L15, L58 | `bg-white`, `bg-light` |

**Impacto:** Em dark mode, estas views terão áreas com fundo claro/branco que quebram a experiência visual.

**Correção:** Substituir classes hardcoded por variáveis do design system:
```html
<!-- ❌ Hardcoded -->
<div class="bg-white text-dark">

<!-- ✅ Dark mode compatible -->
<div class="bg-body text-body">
```

---

## 5. Inline Scripts

### Status: ⚠️ MÉDIO — 10+ views com blocos >50 linhas

**Views com `<script>` inline extenso:**

| View | Início | Linhas Estimadas | Conteúdo |
|------|--------|------------------|----------|
| `workflows/index.php` | L75 | ~100 | SortableJS integration |
| `workflows/form.php` | L73 | ~150 | Workflow builder |
| `users/index.php` | L85 | ~80 | Table actions |
| `users/groups.php` | L282 | ~200 | Permission matrix |
| `users/edit.php` | L57 | ~60 | Form validation |
| `users/create.php` | L54 | ~60 | Form handling |
| `master/logs/index.php` | L10 | ~100 | Log viewer |
| `master/migrations/index.php` | L10 | ~80 | Migration runner |
| `master/git/index.php` | L21 | ~100 | Git dashboard |

**Impacto:**
1. Bloqueia migração para CSP sem `'unsafe-inline'`
2. Dificulta caching de JavaScript
3. Aumenta tamanho da página HTML

**Recomendação:** Extrair scripts inline para `assets/js/modules/<nome>.js` com pattern de inicialização:
```javascript
// assets/js/modules/workflows-index.js
document.addEventListener('DOMContentLoaded', () => {
    const config = JSON.parse(document.getElementById('page-config').textContent);
    // ... lógica
});
```

---

## 6. Responsividade

### Status: ✅ B+

**Verificações:**
- ✅ `table-responsive` presente nas tabelas principais
- ✅ Grid Bootstrap (`col-lg-`, `col-md-`, `col-sm-`) usado consistentemente
- ✅ Forms com `form-floating` e layouts responsivos
- ✅ Navbar collapsa corretamente em mobile
- ⚠️ Algumas modais podem ser largas demais em telas pequenas

---

## 7. Acessibilidade

### Status: ⚠️ C

#### 7.1 Gaps Encontrados

| Issue | Severidade | Detalhes |
|-------|-----------|----------|
| `aria-label` em botões de ação | 🟡 MÉDIO | Botões com ícones sem texto acessível |
| `<caption>` em tabelas | 🟡 MÉDIO | Tabelas de dados sem descrição |
| `aria-describedby` em forms | 🟡 MÉDIO | Campos sem associação a mensagens de validação |
| Contraste em dark mode | 🟡 MÉDIO | Alguns textos sem contraste suficiente |
| Focus management em modais | 🟢 BAIXO | SweetAlert2 gerencia automaticamente |

#### 7.2 Pontos Positivos

- ✅ `<label>` associado a `<input>` na maioria dos forms
- ✅ Semântica HTML5 (`<main>`, `<nav>`, `<section>`)
- ✅ `alt` em imagens principais
- ✅ Skip navigation via estrutura de headings

---

## 8. PWA (Progressive Web App)

### Status: ✅ A

**manifest.json:**
- ✅ Nome: "Akti — Gestão em Produção"
- ✅ Icons: SVG + ICO
- ✅ Theme color: `#2c3e50`
- ✅ Display: `standalone`
- ✅ Start URL configurada

**Service Worker (`sw.js`):**
- ✅ Cache strategy: Network-first com fallback para cache
- ✅ Static assets pré-cacheados (L11-18)
- ✅ Push notification handler (L80-92)
- ✅ Install + activate events

**Portal Service Worker (`portal-sw.js`):**
- ✅ Versão específica para portal público

---

## 9. Performance

### Status: ⚠️ B

| Aspecto | Status | Nota |
|---------|--------|------|
| CDN para bibliotecas | ✅ | Bootstrap, jQuery, FA via CDN |
| SRI (Subresource Integrity) | ✅ | Todos CDN com `integrity` |
| Minificação JS/CSS | ❌ | Assets não minificados |
| Bundle/Tree-shaking | ❌ | Sem bundler (Webpack/Vite) |
| Lazy loading imagens | ⚠️ | Parcial |
| Font Awesome subset | ❌ | Carrega fonte completa |
| Critical CSS | ❌ | Sem inline critical CSS |

---

## 10. Evolução vs. v2

### Issues Resolvidas

| ID v2 | Descrição | Status v3 |
|--------|-----------|-----------|
| FE-001 | Ausência de CSP Header | ✅ CSP implementado via SecurityHeadersMiddleware |
| FE-002 | CDN sem SRI | ✅ Todos os CDN com `integrity` hash |
| FE-003 | XSS via popoverContent | ✅ Parcialmente corrigido |

### Issues Mantidas

| ID | Descrição | Severidade |
|----|-----------|-----------|
| FE-004 | Dark Mode gaps em users views | 🟡 MÉDIO |
| FE-005 | Dark Mode gaps em pipeline views | 🟡 MÉDIO |
| FE-006 | Dark Mode gaps em walkthrough views | 🟡 MÉDIO |
| FE-007 | Inline scripts em views | 🟡 MÉDIO |
| FE-008 | Acessibilidade: falta aria-label em botões | 🟡 MÉDIO |
| FE-009 | Acessibilidade: tabelas sem caption | 🟡 MÉDIO |
| FE-010 | Acessibilidade: forms sem aria-describedby | 🟡 MÉDIO |
| FE-011 | Acessibilidade: contraste em dark mode | 🟡 MÉDIO |
| FE-012 | Sem minificação JS/CSS | 🟡 MÉDIO |
| FE-013 | Sem tree-shaking | 🟢 BAIXO |
| FE-014 | Fetch sem AbortController | 🟢 BAIXO |

### Novas Issues

| ID | Descrição | Severidade |
|----|-----------|-----------|
| FE-015 | `walkthrough.js` innerHTML sem DOMPurify (4 locais) | 🔴 CRÍTICO |
| FE-016 | `customer-select2.js` e `product-select2.js` innerHTML | 🟡 MÉDIO |
| FE-017 | Dark mode gaps em workflows views (novo módulo) | 🟡 MÉDIO |
| FE-018 | Dark mode gaps em supply_stock views (novo módulo) | 🟡 MÉDIO |

### Métricas Comparativas

| Métrica | v2 | v3 | Δ |
|---------|----|----|---|
| JS files | ~12 | 17 | +5 |
| CSS files | ~6 | 9 | +3 |
| Design System vars | ~15 | 30+ | +15 |
| Dark mode coverage | ~40% | ~65% | +25% |
| PWA | ✅ | ✅ | = |
| SRI | ❌ | ✅ | ↑ |
| CSP | ❌ | ✅ | ↑ |
| Issues CRÍTICAS | 1 | 1 | = |
| Total Issues | 14 | 18 | +4 |
