# Checklist de Auditoria — Frontend (Views, JS, CSS, UX)

## JavaScript
- [ ] Inventariar todos os arquivos JS com propósito e tamanho
- [ ] Buscar `innerHTML` sem DOMPurify
- [ ] Buscar `eval()`, `document.write()`, `Function()`
- [ ] Buscar `setTimeout/setInterval` com string (em vez de function)
- [ ] AJAX com CSRF token em todos os POSTs
- [ ] Fetch com AbortController/timeout
- [ ] Error handling em promises (catch)
- [ ] Sem credenciais/tokens hardcoded

## CSS
- [ ] Inventariar todos os arquivos CSS com propósito
- [ ] Design System com variáveis CSS (`:root`)
- [ ] Módulos CSS mapeados no header.php
- [ ] Cada página carrega apenas seus módulos (não todos)

## Dark Mode
- [ ] Toggle funcional (JS + localStorage)
- [ ] Atributo `data-theme="dark"` no `<html>`
- [ ] Variáveis do design-system com override `[data-theme="dark"]`
- [ ] Variáveis do theme.css com override dark
- [ ] Módulos CSS com regras `[data-theme="dark"]`
- [ ] Sem inline styles com cores hardcoded (#xxx)
- [ ] Sem classes Bootstrap fixas (bg-white, bg-light) sem override
- [ ] Keyframes de animação com versão dark
- [ ] Table headers com cores adaptáveis
- [ ] Contraste WCAG AA em todos os badges/chips

## Responsividade
- [ ] Breakpoints definidos (mobile, tablet, desktop)
- [ ] Sidebar colapsa em mobile
- [ ] Tabelas scrolláveis em mobile (`table-responsive`)
- [ ] Formulários empilham em mobile
- [ ] Botões com tamanho touch adequado (>=44px)
- [ ] Menus: hamburger em mobile
- [ ] Pipeline/Kanban: scroll horizontal em mobile

## Acessibilidade (WCAG 2.1)
- [ ] ARIA labels em botões de ícone
- [ ] ARIA roles em componentes dinâmicos (dialog, alert, tab)
- [ ] Focus management em modais
- [ ] Skip navigation link
- [ ] Contraste texto/fundo >= 4.5:1 (AA)
- [ ] Form labels vinculados a inputs (`for`/`id`)
- [ ] Tabelas com `<caption>` ou `aria-label`

## PWA
- [ ] `manifest.json` completo (name, icons, theme_color)
- [ ] Service worker registrado
- [ ] Offline fallback page
- [ ] Ícones em múltiplos tamanhos

## Performance
- [ ] CDN resources com SRI
- [ ] CSS/JS minificados em produção (ou build tool)
- [ ] Imagens otimizadas (WebP, lazy loading)
- [ ] Sem scripts inline grandes (>50 linhas)
- [ ] Cache headers configurados para assets estáticos

## Componentes Reutilizáveis
- [ ] Inventariar componentes PHP (`app/views/components/`)
- [ ] Toast notifications com ARIA
- [ ] Skeleton loaders para AJAX
- [ ] Command palette funcional
- [ ] Atalhos de teclado documentados
