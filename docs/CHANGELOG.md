# Akti — CHANGELOG

> Registro de alterações significativas no sistema.

---

## [v1.5.0] — 2026-03-31 — Fases 5, 6 e 7 do Roadmap

### 🎨 Fase 5 — Design System & UX Avançado

#### Design System
- **Criado** `assets/css/design-system.css` — Sistema de tokens (cores, espaçamento, tipografia, sombras, raios), variáveis CSS, utilitários, componentes base, dark mode completo e overrides de acessibilidade.
- **Refatorado** todos os módulos CSS inline → arquivos modulares em `assets/css/modules/`:
  - `customers.css`, `products.css`, `dashboard.css`, `pipeline.css`, `financial.css`, `orders.css`, `stock.css`, `reports.css`, `nfe.css`, `commissions.css`, `notifications.css`, `users.css`, `settings.css`, `home.css`, `production-board.css`
- **Removidos** todos os blocos `<style>` inline das views principais (customers, products, dashboard, pipeline detail, financial, orders, nfe, home, settings, commissions, stock, reports).
- **Auto-inclusão** de CSS por módulo via mapa no `header.php`.

#### Dark Mode
- **Criado** `assets/js/components/theme-toggle.js` — Toggle de tema claro/escuro com persistência em `localStorage`, prevenção de FOUC, e respeito à preferência do sistema.
- **Integrado** botão de toggle no header principal.

#### Toast Notifications
- **Criado** `assets/js/components/toast.js` — Sistema de toasts não-intrusivos (success, error, warning, info) com auto-dismiss, progresso visual e API global `AktiToast`.
- **Substituído** SweetAlert flash messages por Toasts em todas as views: customers, products, dashboard, orders, users, financial (index, payments, payments_new, installments, transactions), commissions, settings (dashboard_widgets).

#### Skeleton Loading
- **Criado** `assets/js/components/skeleton.js` — Animações de carregamento skeleton para tabelas, cards e listas.

#### Keyboard Shortcuts
- **Criado** `assets/js/components/shortcuts.js` — Atalhos de teclado globais (navegação, busca, novo registro, etc.) com indicadores visuais.

#### Command Palette
- **Criado** `assets/js/components/command-palette.js` — Paleta de comandos (Ctrl+K) com busca estática e AJAX, navegação por teclado, e ações rápidas.

#### Breadcrumbs
- **Criado** `app/views/components/breadcrumb.php` — Componente de breadcrumb contextual renderizado automaticamente no header.

#### Empty States
- **Criado** `app/views/components/empty-state.php` — Componente reutilizável para estados vazios.
- **Criados** SVGs em `assets/img/empty/`: `no-customers.svg`, `no-orders.svg`, `no-products.svg`, `no-results.svg`, `no-notifications.svg`, `no-financial.svg`, `no-stock.svg`, `no-reports.svg`, `no-pipeline.svg`, `no-commissions.svg`.
- **Integrado** em todas as views de listagem.

#### Flash Messages
- **Criado** `app/views/components/flash-messages.php` — Handler centralizado de flash messages integrado ao sistema de Toasts.

---

### 🚀 Fase 6 — Expansão de Funcionalidades

#### Notificações Real-Time
- **SQL**: `update_202603311000_notifications_widgets_indexes.sql` — Tabelas `notifications`, `dashboard_widgets`, índices e dados iniciais.
- **Model**: `app/models/Notification.php` — CRUD de notificações com suporte a tenant, tipos, leitura/não-lida.
- **Controller**: `app/controllers/NotificationController.php` — Endpoint AJAX para bell dropdown, contagem, marcar como lida.
- **JS**: `assets/js/components/notification-bell.js` — Bell com contagem, dropdown, polling, e navegação.
- **CSS**: `assets/css/modules/notifications.css` — Estilos do módulo de notificações.
- **View**: `app/views/notifications/index.php` — Página de listagem de notificações.

#### Dashboard Widgets Configuráveis
- **Model**: `app/models/DashboardWidget.php` — CRUD de widgets com ordem, visibilidade, por grupo de usuários.
- **Controller**: `app/controllers/DashboardWidgetController.php` — Endpoint AJAX para lazy load de widgets.
- **JS**: `assets/js/components/dashboard-widgets.js` — Loader AJAX de widgets com skeleton loading.
- **View**: `app/views/settings/dashboard_widgets.php` — Configuração de widgets por grupo (sortable, toggle).

#### Busca Global (API)
- **Controller**: `app/controllers/SearchController.php` — Endpoint AJAX para busca unificada (clientes, pedidos, produtos).
- **Integrado** com Command Palette (`Ctrl+K`) para resultados em tempo real.

#### Rotas
- **Adicionadas** rotas para `notifications`, `search`, `dashboard_widgets` e `health` em `app/config/routes.php`.
- **Bypass AJAX** para rotas de notificações e busca no `index.php`.

---

### 🔧 Fase 7 — DevOps, Monitoramento e Documentação

#### Docker
- **Criado** `docker-compose.yml` — Ambiente completo (PHP+Apache, MySQL, phpMyAdmin).
- **Criado** `docker/Dockerfile` — Imagem PHP 8.2 com extensões necessárias.
- **Criado** `docker/php.ini` — Configurações de PHP para desenvolvimento.

#### Health Check
- **Controller**: `app/controllers/HealthController.php` — Endpoint `/health` com verificação de DB, disco, PHP e uptime.

#### Error Tracking (Sentry)
- **Middleware**: `app/middleware/SentryMiddleware.php` — Integração com Sentry para captura de exceções.

#### Logging Estruturado
- **Core**: `app/core/Log.php` — Wrapper PSR-3 com níveis (debug, info, warning, error, critical), contexto, rotação de logs.
- **Migrado** 100% dos `error_log()` para `Log::` em:
  - Controllers: `NfeDocumentController`, `DashboardController`, `HealthController`
  - Models: `IpGuard`, `NfeCredential`, `PreparationStep`, `Stock`, `OrderPreparation`, `OrderItemLog`
  - Services: `FinancialAuditService`, `FinancialImportService`, `HeaderDataService`, `NfceDanfeGenerator`, `NfeAuditService`, `NfeBackupService`, `NfeContingencyService`, `NfeDanfeCustomizer`, `NfePdfGenerator`, `NfeService`, `NfeStorageService`

#### Backup
- **Script**: `scripts/backup.sh` — Backup automatizado (diário/semanal) de banco e arquivos.
- **Diretórios**: `storage/backups/daily/`, `storage/backups/weekly/`.

#### Documentação
- **Criado** `docs/DEPLOY.md` — Guia completo de deploy (requisitos, variáveis, nginx, SSL, monitoramento, backup).
- **Criado** `docs/API_PHP.md` — Documentação da API PHP interna.
- **Atualizado** `.env.example` — Variáveis para Sentry, API, backup.

---

### 🧪 Testes

- **Criado/Atualizado** testes unitários:
  - `tests/Unit/NotificationTest.php`
  - `tests/Unit/HealthControllerTest.php`
  - `tests/Unit/Core/LogTest.php`
  - `tests/Unit/Middleware/SentryMiddlewareTest.php`
  - `tests/Unit/DashboardWidgetTest.php`
- **Corrigido** `tests/Unit/CustomerFase3Test.php` — Atualizado caminho do CSS para novo módulo.
- **Resultado**: ✅ 1042 testes, 4152 assertions — todos passando.

---

### 📁 Arquivos Criados/Modificados

#### Novos Arquivos (39)
```
assets/css/design-system.css
assets/css/modules/customers.css
assets/css/modules/products.css
assets/css/modules/dashboard.css
assets/css/modules/pipeline.css
assets/css/modules/financial.css
assets/css/modules/orders.css
assets/css/modules/stock.css
assets/css/modules/reports.css
assets/css/modules/nfe.css
assets/css/modules/commissions.css
assets/css/modules/notifications.css
assets/css/modules/users.css
assets/css/modules/settings.css
assets/css/modules/home.css
assets/css/modules/production-board.css
assets/js/components/theme-toggle.js
assets/js/components/toast.js
assets/js/components/skeleton.js
assets/js/components/shortcuts.js
assets/js/components/command-palette.js
assets/js/components/notification-bell.js
assets/js/components/dashboard-widgets.js
assets/img/empty/*.svg (10 files)
app/views/components/breadcrumb.php
app/views/components/empty-state.php
app/views/components/flash-messages.php
app/models/Notification.php
app/models/DashboardWidget.php
app/controllers/NotificationController.php
app/controllers/SearchController.php
app/controllers/DashboardWidgetController.php
app/controllers/HealthController.php
app/core/Log.php
app/middleware/SentryMiddleware.php
docker-compose.yml
docker/Dockerfile
docker/php.ini
scripts/backup.sh
docs/DEPLOY.md
docs/API_PHP.md
docs/CHANGELOG.md
sql/update_202603311000_notifications_widgets_indexes.sql
```

#### Arquivos Modificados (25+)
```
app/views/layout/header.php (design system, CSS map, toggles, bell, breadcrumb)
app/views/layout/footer.php (new JS components)
app/views/customers/index.php (inline CSS removed, toasts, empty state)
app/views/products/index.php (inline CSS removed, toasts, empty state)
app/views/products/create.php (inline CSS removed)
app/views/products/edit.php (inline CSS removed)
app/views/dashboard/index.php (toasts)
app/views/orders/index.php (inline CSS removed, toasts, empty state)
app/views/orders/create.php (inline CSS removed)
app/views/users/index.php (toasts)
app/views/financial/index.php (toasts)
app/views/financial/payments.php (inline CSS removed, toasts)
app/views/financial/payments_new.php (inline CSS removed, toasts)
app/views/financial/installments.php (inline CSS removed, toasts)
app/views/financial/transactions.php (toasts)
app/views/financial/payments_old.php (toasts)
app/views/commissions/index.php (toasts)
app/views/nfe/credentials.php (inline CSS removed)
app/views/nfe/detail.php (inline CSS removed)
app/views/pipeline/detail.php (inline CSS removed)
app/views/pipeline/production_board.php (inline CSS removed)
app/views/settings/dashboard_widgets.php (inline CSS removed, toasts)
app/views/home/index.php (inline CSS removed)
app/config/routes.php (new routes)
.env.example (new variables)
app/controllers/NfeDocumentController.php (error_log → Log)
app/controllers/DashboardController.php (error_log → Log)
app/models/IpGuard.php (error_log → Log)
app/models/NfeCredential.php (error_log → Log)
+ 6 more models, 11 services
```
