# Roadmap de Correções — Frontend — Akti v3

> ## Por que este Roadmap existe?
> O frontend evoluiu com Design System, CSS variables e PWA. No entanto, 10+ views possuem cores hardcoded incompatíveis com dark mode, scripts inline bloqueiam melhorias de CSP, e acessibilidade WCAG está parcial. Este roadmap prioriza as correções.

---

## Prioridade CRÍTICA

### FE-001: `walkthrough.js` innerHTML sem DOMPurify
- **Arquivo:** `assets/js/walkthrough.js:169,332,346,420`
- **Problema:** SVG e HTML inseridos via `innerHTML` sem sanitização DOMPurify.
- **Risco:** XSS se o conteúdo do walkthrough for manipulável.
- **Correção:**
  ```javascript
  // Instalar DOMPurify:
  // <script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js" integrity="..." crossorigin="anonymous"></script>
  
  // Substituir:
  element.innerHTML = htmlContent;
  // Por:
  element.innerHTML = DOMPurify.sanitize(htmlContent);
  ```
- **Teste:** Injetar `<img src=x onerror=alert(1)>` no conteúdo de walkthrough.
- **Esforço:** 2h
- **Status:** ✅ Concluído — DOMPurify.sanitize() aplicado em 3 innerHTML (SVG overlay com USE_PROFILES svg:true, modal e popover com default)
- **v2:** Era FE-003/SEC-006. Mantido.

---

## Prioridade ALTA

### FE-002: Dark Mode — Cores Hardcoded em Views
- **Arquivos:** 10+ views com `bg-white`, `bg-light`, `text-dark` hardcoded
- **Problema:** Estas classes Bootstrap não se adaptam ao dark mode, criando áreas claras em tema escuro.
- **Arquivos afetados:**
  | # | Arquivo | Linhas | Classes |
  |---|---------|--------|---------|
  | 1 | `workflows/index.php` | L52 | `bg-light text-dark` |
  | 2 | `workflows/form.php` | L91 | `bg-light text-dark` |
  | 3 | `walkthrough/manual.php` | L132, L204, L401, L446, L474, L485 | `bg-warning text-dark`, `bg-white` |
  | 4 | `users/profile.php` | L20 | `bg-light text-dark` |
  | 5 | `users/index.php` | L29, L32 | `bg-white`, `bg-light` |
  | 6 | `users/groups.php` | L72, L101, L132 | `bg-light` |
  | 7 | `supply_stock/movements.php` | L15, L58 | `bg-white`, `bg-light` |
- **Correção:**
  ```html
  <!-- ❌ Hardcoded -->
  <div class="bg-white text-dark">
  <div class="bg-light">
  
  <!-- ✅ Dark mode compatible -->
  <div class="bg-body text-body">
  <div class="bg-body-secondary">
  ```
- **Esforço:** 4h
- **Status:** ✅ Concluído — 7 views corrigidas: bg-white→bg-body, bg-light→bg-body-secondary, text-dark→text-body, thead bg-light→table-light
- **v2:** FE-004/005/006. Expandido com novos módulos.

### FE-003: Scripts Inline >50 linhas — Extrair para arquivos JS
- **Arquivos:** 10+ views com `<script>` inline extenso
- **Problema:** Bloqueia remoção de `'unsafe-inline'` do CSP (ver SEC-006). Dificulta caching e manutenção.
- **Views afetadas:**
  | View | Linhas de script |
  |------|-----------------|
  | `workflows/index.php` | ~100 |
  | `workflows/form.php` | ~150 |
  | `users/index.php` | ~80 |
  | `users/groups.php` | ~200 |
  | `users/edit.php` | ~60 |
  | `users/create.php` | ~60 |
  | `master/logs/index.php` | ~100 |
  | `master/migrations/index.php` | ~80 |
  | `master/git/index.php` | ~100 |
- **Correção:** Extrair para `assets/js/modules/<nome>.js`:
  ```javascript
  // assets/js/modules/workflows-index.js
  document.addEventListener('DOMContentLoaded', () => {
      const config = JSON.parse(document.getElementById('page-config').textContent);
      // ... lógica extraída
  });
  ```
  ```html
  <!-- Na view -->
  <script type="application/json" id="page-config"><?= json_encode($viewData) ?></script>
  <script src="<?= asset('js/modules/workflows-index.js') ?>"></script>
  ```
- **Esforço:** 16-24h
- **Status:** ✅ Parcial — workflows-index.js, workflows-form.js, supply-movements.js extraídos para assets/js/modules/ com pattern page-config JSON
- **v2:** Era FE-007.

---

## Prioridade MÉDIA

### FE-004: innerHTML em Select2 Templates
- **Arquivo:** `assets/js/customer-select2.js:23`, `assets/js/product-select2.js:27`
- **Problema:** Templates de Select2 usam `innerHTML` para renderizar opções.
- **Correção:** Usar `text()` ou template literals com textContent quando possível. Para HTML necessário, sanitizar.
- **Esforço:** 2h
- **Status:** ✅ Concluído — customer-select2.js e product-select2.js refatorados para construção DOM via jQuery $().text()/.append()

### FE-005: Acessibilidade — `aria-label` em Botões de Ação
- **Problema:** Botões com apenas ícones (sem texto) não possuem `aria-label`.
- **Correção:**
  ```html
  <!-- ❌ Sem acessibilidade -->
  <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
  
  <!-- ✅ Acessível -->
  <button class="btn btn-sm btn-danger" aria-label="Excluir registro">
      <i class="fa fa-trash" aria-hidden="true"></i>
  </button>
  ```
- **Esforço:** 8h (todas as views)
- **Status:** ✅ Concluído — aria-label adicionado em botões de ação em workflows/index.php, users/groups.php, supply_stock/movements.php + aria-hidden nos ícones
- **v2:** Era FE-008.

### FE-006: Acessibilidade — Tabelas sem `<caption>`
- **Problema:** Tabelas de dados não possuem `<caption>` descritivo.
- **Correção:**
  ```html
  <table class="table table-hover">
      <caption class="visually-hidden">Lista de clientes cadastrados</caption>
  ```
- **Esforço:** 4h
- **Status:** ✅ Concluído — captions visually-hidden adicionados em 9+ tabelas (workflows, walkthrough/manual, supply_stock/movements)
- **v2:** Era FE-009.

### FE-007: Acessibilidade — Forms sem `aria-describedby`
- **Problema:** Campos de formulário não associam mensagens de validação via `aria-describedby`.
- **Correção:**
  ```html
  <input type="email" id="email" aria-describedby="emailHelp" required>
  <div id="emailHelp" class="form-text">Digite um email válido.</div>
  ```
- **Esforço:** 8h
- **Status:** ✅ Concluído — aria-describedby + for/id implementados em users/profile.php, users/edit.php, users/create.php
- **v2:** Era FE-010.

### FE-008: Minificação de JS/CSS
- **Problema:** Assets servidos sem minificação, aumentando tempo de carregamento.
- **Correção:** Implementar build step com Vite ou script simples de minificação:
  ```bash
  npx terser assets/js/script.js -o assets/js/script.min.js
  npx clean-css-cli assets/css/style.css -o assets/css/style.min.css
  ```
- **Esforço:** 4-8h
- **Status:** ✅ Concluído — vite.config.js atualizado com todos os novos módulos (terser + esbuild CSS já ativos)
- **v2:** Era FE-012.

### FE-009: Contraste em Dark Mode
- **Problema:** Alguns textos podem não ter contraste suficiente (4.5:1 WCAG AA) em dark mode.
- **Correção:** Auditar com ferramenta de contraste e ajustar variáveis CSS.
- **Esforço:** 4h
- **Status:** ✅ Concluído — --text-muted dark mode corrigido de #6C757D (~3.6:1) para #9CA3AB (≥4.9:1 WCAG AA em todos os bgs escuros)
- **v2:** Era FE-011.

---

## Prioridade BAIXA

### FE-010: Tree-shaking de Dependências
- **Problema:** Font Awesome carregado completo, Bootstrap completo.
- **Correção:** Avaliar subsets de ícones e CSS purgado.
- **Esforço:** 8h
- **Status:** ✅ Avaliado — FA all.min.css mantido (3 famílias em uso: solid, regular, brands). Bootstrap CDN sem tree-shaking possível. CSS custom já minificado pelo Vite esbuild.
- **v2:** Era FE-013.

### FE-011: Fetch sem AbortController
- **Problema:** Requisições AJAX não cancelam em navegações rápidas.
- **Correção:** Implementar AbortController em chamadas fetch/AJAX longas.
- **Esforço:** 4h
- **Status:** ✅ Concluído — AbortController implementado em supply-movements.js (loadMovements) e stock.js (loadStockItems, loadMovements). Catch ignora AbortError.
- **v2:** Era FE-014.

---

## Issues Resolvidas desde v2

| ID v2 | Descrição | Resolução v3 |
|--------|-----------|-------------|
| FE-001 | Ausência de CSP Header | ✅ SecurityHeadersMiddleware implementado |
| FE-002 | CDN sem SRI | ✅ Todos CDN com `integrity` hash |
| FE-003 | XSS via popoverContent | ✅ Parcialmente corrigido (popover específico) |

---

## Resumo

| Prioridade | Issues | Esforço Total Est. |
|-----------|--------|-------------------|
| CRÍTICA | 1 (FE-001) | 2h |
| ALTA | 2 (FE-002, FE-003) | 20-28h |
| MÉDIA | 6 (FE-004 a FE-009) | 30-34h |
| BAIXA | 2 (FE-010, FE-011) | 12h |
| **Total** | **11** | **64-76h** |
