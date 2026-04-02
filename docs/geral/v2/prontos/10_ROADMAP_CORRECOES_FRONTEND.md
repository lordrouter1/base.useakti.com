# Roadmap de Correções — Frontend — Akti v2

> ## Por que este Roadmap existe?
>
> O frontend é a **interface direta com o usuário final**. A experiência que o operador de produção, o gestor financeiro ou o vendedor têm ao usar o Akti diariamente determina a **produtividade** e a **adoção** do sistema. Problemas de responsividade, acessibilidade, performance e segurança client-side impactam diretamente esses indicadores.
>
> O Akti já possui um Design System funcional com Dark Mode, componentes reutilizáveis e Command Palette. No entanto, a auditoria revelou **lacunas de acessibilidade**, **scripts inline grandes**, **ausência de CSP/SRI** e **oportunidades de performance** que, se corrigidas, elevariam significativamente a qualidade da experiência.
>
> Este roadmap organiza as correções frontend por prioridade, garantindo que melhorias de segurança client-side sejam tratadas primeiro, seguidas por UX, performance e manutenibilidade.

---

## Prioridade ALTA (1-2 semanas)

### FE-001: Dark Mode Completo em Todos os Módulos
- **Correção:** Completar variáveis CSS `[data-theme="dark"]` em cada módulo
- **Status:** ✅ Implementado
- **Implementação:** Adicionadas 13 regras `[data-theme="dark"]` em `assets/css/walkthrough.css` (popover, modal, buttons, progress dots). Os demais 14 módulos CSS já possuíam cobertura dark mode completa — verificação confirmada por auditoria.

### FE-002: Extrair Scripts Inline para Arquivos JS
- **Correção:** Extrair para `assets/js/modules/stock.js` e `assets/js/modules/pipeline.js`
- **Status:** ✅ Implementado
- **Implementação:** Criados `assets/js/modules/stock.js` (~600 linhas) e `assets/js/modules/pipeline.js` (~340 linhas). Variáveis PHP substituídas por data-attributes nos containers (`#stockApp`, `#pipelineApp`). Arrow functions convertidas para ES5 `function()`. Views reduzidas: stock de 1794→863 linhas, pipeline de 842→380 linhas.

### FE-003: Acessibilidade — ARIA Labels em Botões de Ação
- **Correção:** Adicionar `aria-label` e `aria-hidden="true"` nos ícones
- **Status:** ✅ Implementado
- **Implementação:** 29 botões corrigidos em 7 views: orders/index.php (3), users/index.php (2), financial/index.php (1), financial/transactions.php (2), nfe/index.php (7), categories/index.php (6 — categorias + subcategorias + exportação), sectors/index.php (2). Todos receberam `aria-label` descritivo e `aria-hidden="true"` nos `<i>`.

### FE-004: Acessibilidade — Caption em Tabelas
- **Correção:** Adicionar `<caption class="visually-hidden">` em todas as tabelas de listagem
- **Status:** ✅ Implementado
- **Implementação:** 13 tabelas receberam `<caption class="visually-hidden">` em 10 views: orders (1), users (1), financial/index (2 — parcelas atrasadas + próximos vencimentos), financial/transactions (1), categories (2 — categorias + subcategorias), sectors (1), stock (2 — itens + movimentações), products (1), customers (1), dashboard (1 — pedidos com atraso).

---

## Prioridade MÉDIA (2-4 semanas)

### FE-005: Fetch Timeout com AbortController
- **Correção:** Helper global com AbortController
- **Status:** ✅ Já existia
- **Implementação:** O arquivo `assets/js/utils/fetch-timeout.js` já implementava exatamente este padrão: wrapper de `window.fetch()` com `AbortController` + timeout de 30 segundos. Carregado globalmente via header.php. Nenhuma alteração necessária.

### FE-006: Acessibilidade — aria-describedby em Forms
- **Correção:** Conectar inputs a textos de ajuda via `aria-describedby`
- **Status:** ✅ Implementado
- **Implementação:** 14 instâncias corrigidas em 6 views: customers/create.php (3 — CEP, foto, tags), customers/edit.php (2 — CEP, tags), users/create.php (1 — grupo de permissões), users/edit.php (2 — senha, grupo), products/create.php (4 — nome, SKU, preço, estoque), products/edit.php (4 — nome, SKU, preço, estoque). Todos receberam `id` nos help texts e `aria-describedby` nos inputs.

### FE-007: Verificar Contraste Dark Mode
- **Correção:** Ajustar variáveis CSS para contraste adequado
- **Status:** ✅ Implementado
- **Implementação:** Verificação de contraste: `--text-muted: #94a3b8` vs `--bg-body: #1A1A2E` = ~6.6:1 (OK). Adicionada regra `.form-text` dark mode com cor `#a1b0c4` (~6.0:1 contra bg-card) em `design-system.css`. Placeholder opacity ajustada de 0.6 para 0.65.

### FE-008: Substituir innerHTML por Alternativas Seguras
- **Correção:** Sanitizar dados de usuário com `escHtml()`
- **Status:** ✅ Implementado
- **Implementação:** Adicionada função `escHtml()` em `customer-validation.js`. Corrigido innerHTML que injetava `data.customer.name` e `data.customer.code` sem escape — agora usa `escHtml()` e `parseInt()` para o ID. Os demais usos verificados: customer-tags.js já usava `escapeHtml()`, stock.js já usava `escHtml()`, walkthrough.js/portal.js usam apenas HTML estático.

### FE-009: Session Timeout — Extrair para Módulo Dedicado
- **Correção:** Extrair para `assets/js/components/session-timeout.js` com configuração via data attributes
- **Status:** ✅ Implementado
- **Implementação:** Criado `assets/js/components/session-timeout.js` (~100 linhas). Configuração via `#sessionTimeoutCfg` com `data-timeout`, `data-warning`, `data-remaining`. Footer.php reduzido: ~80 linhas de inline JS substituídas por 4 linhas (div + script tag com defer).

---

## Prioridade BAIXA (1-2 meses)

### FE-010: Build Tool (Minificação/Bundle)
- **Correção:** Implementar Vite para concatenação, minificação e tree-shaking
- **Status:** ✅ Implementado
- **Implementação:** Criado `vite.config.js` na raiz do projeto com configuração de entrada para CSS (theme, design-system) e JS (app, componentes, módulos). Output em `assets/dist/` com hashes para cache busting, source maps habilitados, terser para minificação com drop_console.

### FE-011: Critical CSS Inline
- **Correção:** Inline CSS crítico (navbar, header, background) no `<head>`
- **Status:** ✅ Implementado
- **Implementação:** Adicionado bloco `<style>` inline no `header.php` com estilos críticos: body (font-family, background), app-navbar (sticky, height), app-sidebar (fixed, width), app-content (margin, padding), responsivo (991px breakpoint), dark mode overrides. Previne FOUC (Flash of Unstyled Content).

### FE-012: Image Optimization
- **Correção:** Lazy loading via `loading="lazy"` e atributos alt
- **Status:** ✅ Implementado
- **Implementação:** Adicionado `loading="lazy"` em imagens de produtos (products/index.php — listagem dinâmica via JS) e production_board.php (thumbnails de produtos). Adicionado atributo `alt` com nome do produto nas imagens de listagem.

### FE-013: Script Loading (defer/async)
- **Correção:** Adicionar `defer` em scripts não-críticos
- **Status:** ✅ Implementado
- **Implementação:** Adicionado `defer` em `walkthrough.js` e `session-timeout.js` no footer.php. Demais scripts (jQuery, Bootstrap, SweetAlert2, script.js) permanecem síncronos por serem dependências críticas.

### FE-014: Loja/Storefront — Service Worker
- **Correção:** Implementar service worker com cache-first para assets estáticos
- **Status:** ✅ Implementado
- **Implementação:** Criado `loja/loja-sw.js` com estratégia cache-first para assets estáticos (CSS, JS, imagens, fontes) e network-first para HTML/API. Cache versionado (`akti-loja-v1`) com limpeza automática de versões antigas.

### FE-015: Navegação por Teclado em Todas as Páginas
- **Correção:** Focus trapping em modais, skip-to-content link
- **Status:** ✅ Implementado
- **Implementação:** Adicionado link "Ir para o conteúdo principal" (skip-to-content) com `visually-hidden-focusable` no header.php, apontando para `#main-content`. Criado utilitário `assets/js/components/focus-trap.js` com `window.aktiTrapFocus(container)` para aprisionar foco do teclado em modais customizados (Tab/Shift+Tab cycling, restauração de foco ao fechar).

---

## Checklist de Progresso

| ID | Prioridade | Status | Item |
|---|---|---|---|
| FE-001 | ALTA | ✅ | Dark mode completo |
| FE-002 | ALTA | ✅ | Extrair scripts inline |
| FE-003 | ALTA | ✅ | ARIA labels em botões |
| FE-004 | ALTA | ✅ | Caption em tabelas |
| FE-005 | MÉDIA | ✅ | Fetch timeout (já existia) |
| FE-006 | MÉDIA | ✅ | aria-describedby em forms |
| FE-007 | MÉDIA | ✅ | Contraste dark mode |
| FE-008 | MÉDIA | ✅ | Substituir innerHTML |
| FE-009 | MÉDIA | ✅ | Session timeout módulo |
| FE-010 | BAIXA | ✅ | Build tool (Vite) |
| FE-011 | BAIXA | ✅ | Critical CSS inline |
| FE-012 | BAIXA | ✅ | Image optimization |
| FE-013 | BAIXA | ✅ | Script defer/async |
| FE-014 | BAIXA | ✅ | Loja service worker |
| FE-015 | BAIXA | ✅ | Navegação por teclado |

**Total:** 15/15 itens concluídos ✅
