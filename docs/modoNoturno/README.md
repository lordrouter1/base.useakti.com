# 📁 docs/modoNoturno

Pasta de documentação referente ao **Modo Noturno (Dark Mode)** do sistema Akti.

## Documentos

| Arquivo | Descrição |
|---------|-----------|
| [`RELATORIO_MODO_NOTURNO.md`](./RELATORIO_MODO_NOTURNO.md) | Relatório completo de avaliação do modo noturno com análise de paletas, problemas encontrados, impacto por página, e plano de ação priorizado |
| [`ROADMAP_DARK_MODE.md`](./ROADMAP_DARK_MODE.md) | Roadmap detalhado de correções com 6 fases, 54 tarefas, cronograma por sprint e checklists de validação |
| [`GUIA_DARK_MODE_DEV.md`](./GUIA_DARK_MODE_DEV.md) | Guia prático para desenvolvedores: como adicionar dark mode em novos componentes |

## Status Atual (31/03/2026)

### ✅ Fases Concluídas

| Fase | Descrição | Status |
|------|-----------|--------|
| **Fase 1** | Variáveis & Design Tokens — Overrides dark em `theme.css` | ✅ Concluída |
| **Fase 2** | Componentes Globais — `style.css` e pipeline/kanban | ✅ Concluída |
| **Fase 3** | Módulos CSS — Dark mode em todos os `assets/css/modules/*.css` | ✅ Concluída |
| **Fase 4** | Inline Styles — Utility classes + substituição em views PHP | ✅ Concluída |
| **Fase 5** | Acessibilidade — Cores de ícones, badges e contraste | ✅ Concluída |
| **Fase 6** | Documentação, CI, Componentes, Tema Auto | ✅ 83% (5/6 — falta apenas testes visuais automatizados) |

### Cobertura Estimada: **~98%**

- **~200+ inline styles** substituídos por utility classes com dark mode
- **Todos os módulos CSS** possuem `[data-theme="dark"]` overrides
- **Todas as variáveis** de `theme.css` têm override dark
- **design-system.css** possui 25+ famílias de utility classes temáticas (80+ regras dark)
- **GUIA_DARK_MODE_DEV.md** criado com referência completa para desenvolvedores
- **53/54 tarefas** do roadmap concluídas (98%)
- **Lint script** (`scripts/lint_dark_mode.php`) para CI — detecta cores hardcoded
- **Componentes PHP reutilizáveis** em `app/views/components/` (icon-circle, section-header, summary-card, badge, chip, card-header)
- **Tema "Auto"** — toggle agora cicla: Claro → Escuro → Auto (segue OS)

## Componentes PHP Reutilizáveis

| Componente | Arquivo | Descrição |
|------------|---------|-----------|
| Icon Circle | `app/views/components/icon-circle.php` | Ícone arredondado com cor temática |
| Section Header | `app/views/components/section-header.php` | Cabeçalho de seção com ícone e badge |
| Summary Card | `app/views/components/summary-card.php` | Card KPI/resumo com ícone e valor |
| Badge | `app/views/components/badge.php` | Badge temático (light ou solid) |
| Chip | `app/views/components/chip.php` | Chip de status (aprovado/pendente/rejeitado) |
| Card Header | `app/views/components/card-header.php` | Header de card com gradiente temático |

## Utility Classes Disponíveis (design-system.css)

| Família | Exemplos | Uso |
|---------|----------|-----|
| `card-header-*` | `card-header-blue`, `card-header-green`, `card-header-grape` | Card headers com gradiente |
| `card-header-*-light` | `card-header-info-light`, `card-header-green-light` | Card headers com gradiente sutil |
| `card-header-nfe-*` | `card-header-nfe-dark`, `card-header-nfe-danger` | Card headers sólidos (NF-e, modais) |
| `nav-icon-*` | `nav-icon-blue`, `nav-icon-orange`, `nav-icon-purple` | Ícones de sidebar navigation |
| `icon-circle` | `icon-circle icon-circle-blue icon-circle-lg` | Círculos de ícone em seções |
| `fieldset-*` | `fieldset-blue`, `fieldset-green`, `fieldset-purple` | Bordas coloridas em fieldsets |
| `section-bg-*` | `section-bg-green`, `section-bg-danger`, `section-bg-purple` | Backgrounds de seções CTA/empty-state |
| `text-*` | `text-blue`, `text-green`, `text-carrot`, `text-info-alt` | Cores de texto temáticas |
| `badge-*-light` | `badge-danger-light`, `badge-success-light`, `badge-blue-light` | Badges com background sutil |
| `kpi-*` | `kpi-blue`, `kpi-green`, `kpi-red`, `kpi-orange` | Cards KPI com gradiente |
| `btn-*` | `btn-mint`, `btn-carrot`, `btn-grape`, `btn-navy` | Botões com cores temáticas |
| `chip-*` | `chip-approved`, `chip-pending`, `chip-rejected` | Chips de status |
| `icon-*` | `icon-success`, `icon-warning`, `icon-navy` | Cores de ícones |
| `bg-*` | `bg-purple`, `bg-blue-ds`, `bg-green-ds` | Backgrounds sólidos |
| `preview-area` | `preview-area` | Área de preview com fundo neutro |
| `dropdown-themed` | `dropdown-themed` | Dropdowns com dark mode |
| `form-label-muted` | `form-label-muted` | Labels de formulário sutis |

## CI / Lint

```bash
# Detectar cores hardcoded nas views PHP
php scripts/lint_dark_mode.php

# Com sugestões de correção
php scripts/lint_dark_mode.php --fix-hint

# Modo estrito (exit code 1 se houver violações) — para CI
php scripts/lint_dark_mode.php --strict
```

## Tema "Auto" (Seguir OS)

O toggle de tema no header agora suporta 3 modos:

| Modo | Ícone | Comportamento |
|------|-------|---------------|
| ☀️ Claro | `fa-moon` | Sempre tema claro |
| 🌙 Escuro | `fa-sun` | Sempre tema escuro |
| 🔄 Auto | `fa-circle-half-stroke` | Segue preferência do sistema operacional |

API JavaScript:
```javascript
AktiTheme.toggle();        // Cicla: Claro → Escuro → Auto
AktiTheme.set('auto');     // Define modo automático
AktiTheme.getMode();       // Retorna: 'light', 'dark', ou 'auto'
AktiTheme.get();           // Retorna tema efetivo: 'light' ou 'dark'
```

## Regra para Novos Desenvolvimentos

> ⚠️ **Nunca use `style="color:#hex"` ou `style="background:#hex"` em views PHP.**
> 
> Sempre use as utility classes do `design-system.css` ou os componentes de `app/views/components/`.
> Se a classe não existir, crie-a com override `[data-theme="dark"]`.
> Execute `php scripts/lint_dark_mode.php` para validar antes do commit.
