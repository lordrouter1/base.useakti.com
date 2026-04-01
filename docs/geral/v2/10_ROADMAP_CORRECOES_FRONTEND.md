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
- **Módulos com lacunas:**
  - `assets/css/modules/users.css` — Linhas 72-88: apenas headers/borders em dark
  - `assets/css/modules/pipeline.css` — Parcial
  - `assets/css/walkthrough.css` — Parcial
- **Correção:** Completar variáveis CSS `[data-theme="dark"]` em cada módulo
- **Teste:** Alternar dark mode e verificar visualmente cada módulo
- **Status:** ⬜ Pendente

### FE-002: Extrair Scripts Inline para Arquivos JS
- **Arquivos com scripts inline grandes:**
  - `app/views/stock/index.php` — ~300 linhas de JS inline (linhas 866-1200)
  - `app/views/pipeline/index.php` — ~200 linhas de JS inline
- **Problema:** Dificulta manutenção, impossibilita minificação, viola CSP strict
- **Correção:** Extrair para `assets/js/modules/stock.js` e `assets/js/modules/pipeline.js`
- **Status:** ⬜ Pendente

### FE-003: Acessibilidade — ARIA Labels em Botões de Ação
- **Problema:** Botões de editar/excluir/visualizar sem `aria-label`
- **Módulos afetados:** Products, Orders, Stock, Users, Financial, NF-e
- **Correção:**
  ```html
  <!-- Antes -->
  <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
  
  <!-- Depois -->
  <button class="btn btn-sm btn-primary" aria-label="Editar produto {{ nome }}">
      <i class="fas fa-edit" aria-hidden="true"></i>
  </button>
  ```
- **Status:** ⬜ Pendente

### FE-004: Acessibilidade — Caption em Tabelas
- **Problema:** Tabelas de dados sem `<caption>` — screen readers não anunciam propósito
- **Correção:** Adicionar `<caption class="visually-hidden">` em todas as tabelas de listagem
- **Status:** ⬜ Pendente

---

## Prioridade MÉDIA (2-4 semanas)

### FE-005: Fetch Timeout com AbortController
- **Problema:** Chamadas `fetch()` sem timeout — podem ficar penduradas
- **Correção:** Helper global:
  ```javascript
  function fetchWithTimeout(url, options = {}, timeout = 30000) {
      const controller = new AbortController();
      const id = setTimeout(() => controller.abort(), timeout);
      return fetch(url, { ...options, signal: controller.signal })
          .finally(() => clearTimeout(id));
  }
  ```
- **Status:** ⬜ Pendente

### FE-006: Acessibilidade — aria-describedby em Forms
- **Problema:** Mensagens de erro de validação não conectadas ao campo via `aria-describedby`
- **Correção:**
  ```html
  <input type="email" id="email" aria-describedby="email-error">
  <div id="email-error" class="invalid-feedback" role="alert">Email inválido</div>
  ```
- **Status:** ⬜ Pendente

### FE-007: Verificar Contraste Dark Mode
- **Problema:** Texto `muted` em dark mode pode violar WCAG AA (4.5:1 ratio)
- **Correção:** Usar ferramenta de contraste (axe, Lighthouse) e ajustar variáveis CSS
- **Status:** ⬜ Pendente

### FE-008: Substituir innerHTML por Alternativas Seguras
- **Arquivos:**
  - `financial-payments.js` — 4 locais
  - `stock/index.php` inline — 1 local
  - `pipeline/index.php` inline — 3 locais
- **Correção preferida:**
  ```javascript
  // Em vez de innerHTML com escHtml()
  element.textContent = data.name; // Para texto puro
  
  // Ou template element para HTML complexo
  const template = document.getElementById('row-template');
  const clone = template.content.cloneNode(true);
  clone.querySelector('.name').textContent = data.name;
  container.appendChild(clone);
  ```
- **Status:** ⬜ Pendente

### FE-009: Session Timeout — Extrair para Módulo Dedicado
- **Arquivo:** `app/views/layout/footer.php`
- **Problema:** Script de session timeout inline no footer com variáveis PHP injetadas
- **Correção:** Extrair para `assets/js/components/session-timeout.js` com configuração via data attributes
- **Status:** ⬜ Pendente

---

## Prioridade BAIXA (1-2 meses)

### FE-010: Build Tool (Minificação/Bundle)
- **Problema:** JS e CSS servidos sem minificação
- **Correção:** Implementar Vite ou esbuild para:
  - Concatenar JS por módulo
  - Minificar CSS
  - Tree-shaking de código não utilizado
  - Source maps para debug
- **Status:** ⬜ Pendente

### FE-011: Critical CSS Inline
- **Problema:** Todas CSS incluídas via `<link>` — above-the-fold sem estilo até load
- **Correção:** Inline CSS crítico (navbar, header, background) no `<head>`
- **Status:** ⬜ Pendente

### FE-012: Image Optimization
- **Problema:** Imagens em formatos tradicionais (PNG, JPEG)
- **Correção:** Converter para WebP com fallback; lazy loading via IntersectionObserver
- **Status:** ⬜ Pendente

### FE-013: Script Loading (defer/async)
- **Problema:** Scripts carregam synchronously — bloqueiam render
- **Correção:** Adicionar `defer` em scripts não-críticos:
  ```html
  <script src="assets/js/walkthrough.js" defer></script>
  <script src="assets/js/components/command-palette.js" defer></script>
  ```
- **Status:** ⬜ Pendente

### FE-014: Loja/Storefront — Service Worker
- **Problema:** Storefront sem PWA offline (diferente do portal)
- **Correção:** Implementar service worker com cache-first para assets estáticos
- **Status:** ⬜ Pendente

### FE-015: Navigação por Teclado em Todas as Páginas
- **Problema:** Tab order inconsistente em modais e dropdowns
- **Correção:** Testar com Tab/Shift+Tab em todos os fluxos CRUD e corrigir focus trapping
- **Status:** ⬜ Pendente

---

## Checklist de Progresso

| ID | Prioridade | Status | Item |
|---|---|---|---|
| FE-001 | ALTA | ⬜ | Dark mode completo |
| FE-002 | ALTA | ⬜ | Extrair scripts inline |
| FE-003 | ALTA | ⬜ | ARIA labels em botões |
| FE-004 | ALTA | ⬜ | Caption em tabelas |
| FE-005 | MÉDIA | ⬜ | Fetch timeout |
| FE-006 | MÉDIA | ⬜ | aria-describedby em forms |
| FE-007 | MÉDIA | ⬜ | Contraste dark mode |
| FE-008 | MÉDIA | ⬜ | Substituir innerHTML |
| FE-009 | MÉDIA | ⬜ | Session timeout módulo |
| FE-010 | BAIXA | ⬜ | Build tool |
| FE-011 | BAIXA | ⬜ | Critical CSS |
| FE-012 | BAIXA | ⬜ | Image optimization |
| FE-013 | BAIXA | ⬜ | Script defer/async |
| FE-014 | BAIXA | ⬜ | Loja service worker |
| FE-015 | BAIXA | ⬜ | Navegação por teclado |

**Total:** 15 itens (4 altos, 5 médios, 6 baixos)
