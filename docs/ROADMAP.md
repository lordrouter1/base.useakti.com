# 🗺️ Akti — Roadmap de Melhorias

> **Gerado em:** 20/03/2026  
> **Versão do sistema analisada:** Akti - Gestão em Produção (Multi-Tenant)  
> **Escopo:** Análise completa da estrutura — backend, frontend, banco de dados, segurança, API, testes, UX, performance e infraestrutura.

---

## 📊 Legenda de Urgência

| Nível | Ícone | Descrição |
|-------|-------|-----------|
| **CRÍTICA** | 🔴 | Risco de segurança, perda de dados ou indisponibilidade. Resolver imediatamente. |
| **ALTA** | 🟠 | Impacta diretamente a experiência do usuário ou a estabilidade. Resolver em 1-2 sprints. |
| **MÉDIA** | 🟡 | Melhoria significativa, mas o sistema funciona sem ela. Planejar para 1-2 meses. |
| **BAIXA** | 🟢 | Nice-to-have, otimização ou feature futura. Planejar para trimestre. |
| **FUTURA** | 🔵 | Visão de longo prazo ou dependente de decisão estratégica. |

---

## 📑 Índice de Categorias

1. [🔒 Segurança](#1--segurança)
2. [🏗️ Arquitetura & Backend](#2-️-arquitetura--backend)
3. [🗄️ Banco de Dados](#3-️-banco-de-dados)
4. [🎨 Frontend & UI/UX](#4--frontend--uiux)
5. [📦 Módulos de Negócio](#5--módulos-de-negócio)
6. [🧪 Testes & Qualidade](#6--testes--qualidade)
7. [⚡ Performance & Cache](#7--performance--cache)
8. [🌐 API Node.js](#8--api-nodejs)
9. [🏢 Multi-Tenancy](#9--multi-tenancy)
10. [📱 Mobile & PWA](#10--mobile--pwa)
11. [📄 Documentação](#11--documentação)
12. [🚀 DevOps & Infraestrutura](#12--devops--infraestrutura)

---

## 1. 🔒 Segurança

### 🔴 CRÍTICA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| S-01 | **Remover credenciais hardcoded** | `tenant.php` contém senha do banco em fallback (`kP9!vR2@mX6#zL5$`). Migrar para `.env` obrigatório com `vlucas/phpdotenv`. | `app/config/tenant.php` |
| S-02 | **Database.php: suprimir erro em produção** | O `echo` em `catch(PDOException)` expõe a mensagem de erro diretamente na tela. Substituir por log + página de erro. | `app/config/database.php` |
| S-03 | **Rate limiting no login (reCAPTCHA)** | As constantes `RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET_KEY` estão vazias. Sem reCAPTCHA ativo, a proteção contra brute force depende apenas do rate-limit por IP, que pode ser contornado. | `app/models/LoginAttempt.php` |
| S-04 | **Sanitização consistente de `$_GET`** | Vários controllers usam `$_GET['id']` sem cast para `(int)`. Possível SQL Injection se prepared statements falharem por má composição. Auditar todos os controllers. | `app/controllers/*.php` |
| S-05 | **Content Security Policy (CSP)** | Nenhum header CSP é enviado. CDNs externas (Bootstrap, SweetAlert, FontAwesome, SortableJS) devem ser whitelistadas explicitamente. | `app/views/layout/header.php`, `index.php` |

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| S-06 | **Regeneração de session ID após login** | Após autenticação bem-sucedida, não há chamada a `session_regenerate_id(true)`. Necessário para prevenir session fixation. | `app/controllers/UserController.php` |
| S-07 | **Validação de MIME type em uploads** | Verificar `finfo_file()` além da extensão. Extensão pode ser falsificada. | Controllers de upload, `Product`, `Settings` |
| S-08 | **Headers de segurança adicionais** | Faltam `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`. Adicionar via middleware ou `.htaccess`. | Criar `app/middleware/SecurityHeadersMiddleware.php` |
| S-09 | **Encriptar dados sensíveis do tenant** | `db_password` trafega em plaintext na sessão. Considerar encriptar com `sodium_crypto_secretbox`. | `app/config/tenant.php` |
| S-10 | **Audit trail para operações críticas** | O `Logger` atual é genérico. Criar audit trail específico para: mudanças de permissão, exclusão de pedidos, estorno financeiro, alterações de dados de clientes. | `app/models/Logger.php` |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| S-11 | **2FA (Autenticação de dois fatores)** | Implementar TOTP (Google Authenticator) ou envio por email para admins. | Novo: `app/models/TwoFactor.php`, `app/views/auth/2fa.php` |
| S-12 | **Política de senhas** | Não há validação de força mínima (comprimento, complexidade). Adicionar regra no `Validator`. | `app/utils/Validator.php`, `app/controllers/UserController.php` |
| S-13 | **Expiração de sessão com multi-device** | Permitir visualizar sessões ativas e revogar sessões remotas (modelo "Onde estou logado"). | Novo: `app/models/UserSession.php` |

---

## 2. 🏗️ Arquitetura & Backend

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| A-01 | **Injeção de Dependência (DI Container)** | Controllers instanciam `new Database()` e models diretamente. Criar um container simples para injetar `$db` automaticamente, facilitando testes e evitando múltiplas conexões. | `app/core/Container.php` (novo), todos os controllers |
| A-02 | **Conexão única por request (Singleton)** | `Database::getConnection()` cria uma nova conexão PDO a cada chamada. Models e controllers chamam isto múltiplas vezes por request. Implementar singleton ou connection pool. | `app/config/database.php` |
| A-03 | **Repository Pattern** | Models misturam lógica de negócio com queries SQL. Separar em `Repositories` (SQL puro) e `Services` (regras de negócio). | Criar `app/repositories/`, refatorar models |
| A-04 | **Tratamento de erros unificado** | O `set_exception_handler` no `index.php` é bom, mas controllers individuais não possuem try-catch consistente. Criar middleware de error handling. | `app/middleware/ErrorMiddleware.php` (novo) |
| A-05 | **Eliminar `echo` no Database.php** | `echo 'Erro na conexão'` no catch do construtor — deve lançar exception em vez de imprimir. | `app/config/database.php` |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| A-06 | **Service Layer completa** | `app/services/` só contém serviços de NF-e. Criar serviços para: `OrderService`, `StockService`, `FinancialService`, `PipelineService` para concentrar regras de negócio complexas. | `app/services/` (novos) |
| A-07 | **DTO / Value Objects** | Dados transitam como arrays associativos sem tipagem. Criar DTOs para `CreateOrderDTO`, `MoveStageDTO`, etc., para documentar e validar estrutura de dados. | Novo: `app/dto/` |
| A-08 | **Middleware Pipeline** | Apenas `CsrfMiddleware` existe. Criar pipeline com: Auth, CORS, RateLimit, Logging, ModuleCheck. O `index.php` ficaria muito mais limpo. | `app/core/MiddlewarePipeline.php` (novo) |
| A-09 | **Queue / Jobs assíncronos** | Operações pesadas (envio de email, geração de PDF NF-e, processamento de webhook) são síncronas. Implementar fila simples (tabela `jobs` + cron worker) ou integrar com Redis. | Novo: `app/core/Queue.php`, `app/jobs/` |
| A-10 | **Notification System** | Não há sistema de notificações persistentes. Criar modelo para notificações in-app (bell icon no navbar) com suporte a leitura/não lida. | Novo: `app/models/Notification.php`, `app/views/components/notifications.php` |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| A-11 | **Command Bus / CQRS leve** | Separar operações de escrita (commands) de leitura (queries) para melhor organização em módulos complexos como Financeiro. | Futuro refactor |
| A-12 | **Plugin System** | `FLUXO_SISTEMA_DE_PLUGINS.md` existe na documentação, mas não há implementação. Criar infraestrutura de plugins com hooks. | `app/core/PluginManager.php` (novo) |

---

## 3. 🗄️ Banco de Dados

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| D-01 | **Sistema de migrations automatizado** | Os arquivos SQL em `/sql` são manuais e não possuem controle de versão executado. Implementar tabela `migrations` com controle de quais scripts já foram aplicados + comando CLI para executar pendentes. | Novo: `scripts/migrate.php`, `sql/migrations/` |
| D-02 | **Índices faltantes** | Queries críticas como `getOrdersByStage()` usam `LEFT JOIN` em `orders`, `customers`, `users` sem garantia de índices compostos otimizados. Auditar e criar índices para: `orders(pipeline_stage, status)`, `order_installments(status, paid_date)`, `stock_items(warehouse_id, product_id)`. | `sql/update_YYYYMMDD_indexes.sql` |
| D-03 | **Foreign Keys & Constraints** | Verificar se todas as relações possuem `FOREIGN KEY` com `ON DELETE CASCADE` ou `SET NULL` adequados. Evitar registros órfãos. | `sql/update_YYYYMMDD_foreign_keys.sql` |
| D-04 | **Soft Delete** | Exclusão de clientes, produtos e pedidos é irreversível. Implementar `deleted_at` para soft delete em tabelas críticas. | Models afetados, SQL migration |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| D-05 | **Backup automatizado** | Apenas um backup manual (`backup140320260909.sql`) existe. Criar script de backup agendado via cron + rotação (manter últimos 7 dias). | Novo: `scripts/backup.sh` |
| D-06 | **Campos `updated_at` automáticos** | Nem todas as tabelas possuem `updated_at` com `ON UPDATE CURRENT_TIMESTAMP`. Padronizar. | SQL migration |
| D-07 | **Otimizar queries do Financial** | `Financial::getSummary()` executa ~10 queries separadas. Consolidar em 1-2 queries com subqueries ou CTE (MySQL 8+). | `app/models/Financial.php` |
| D-08 | **Normalizar tabela de configurações** | `company_settings` armazena muitos dados heterogêneos. Considerar separar em tabelas especializadas (`payment_config`, `nfe_config`, etc.) ou usar padrão key-value mais estruturado. | SQL migration, `app/models/CompanySettings.php` |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| D-09 | **Read Replicas** | Para tenants com alto volume, configurar leitura em réplica. Requer ajuste no `Database.php` para aceitar conexão de read-only. | `app/config/database.php` |
| D-10 | **Particionamento de tabelas** | Tabelas `system_logs`, `login_attempts`, `stock_movements` podem crescer muito. Particionar por data. | SQL migration |

---

## 4. 🎨 Frontend & UI/UX

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| F-01 | **Componentização de views** | Views como `detail.php` do pipeline possuem 5700+ linhas. Extrair em partials/componentes: `_card_financial.php`, `_card_nfe.php`, `_card_shipping.php`, `_section_products.php`, etc. | `app/views/pipeline/detail.php`, criar `app/views/pipeline/partials/` |
| F-02 | **Bundling de assets** | CSS e JS são servidos individualmente sem minificação. Implementar Vite ou Webpack para bundle, minificação, tree-shaking e versionamento (`style.css?v=hash`). | `assets/css/`, `assets/js/`, `package.json` (novo na raiz) |
| F-03 | **Dark Mode** | O `theme.css` já define variáveis CSS. Criar alternância light/dark respeitando `prefers-color-scheme` e com toggle manual no navbar. | `assets/css/theme.css`, `app/views/layout/header.php` |
| F-04 | **Acessibilidade (a11y)** | Auditar ARIA labels, contraste de cores, navegação por teclado, focus indicators, alt text em imagens. Usar Lighthouse para scoring. | Todas as views |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| F-05 | **Notificações em tempo real** | Implementar WebSocket (via API Node.js) ou SSE para: pedidos atrasados, movimentação de pipeline, pagamentos recebidos. Eliminar polling manual. | `api/src/`, `assets/js/script.js` |
| F-06 | **Dashboard customizável** | O sistema de widgets existe (`DashboardWidget`), mas não há drag-and-drop para reorganizar. Implementar grid editável (GridStack ou similar). | `app/views/home/index.php` |
| F-07 | **Formulários multi-step** | Criação de pedidos e produtos pode ser simplificada com wizard step-by-step em vez de form longo. | `app/views/orders/create.php`, `app/views/products/create.php` |
| F-08 | **Skeleton loading states** | Implementar skeleton screens para carregamento de dados em vez de spinners genéricos. | Todas as views com fetch/AJAX |
| F-09 | **Keyboard shortcuts** | Atalhos como `Ctrl+N` (novo pedido), `Ctrl+S` (salvar), `Ctrl+K` (busca rápida global). | `assets/js/script.js` |
| F-10 | **Breadcrumbs** | Navegação hierárquica (Home > Pipeline > Pedido #0012 > Detalhe) para melhorar orientação. | `app/views/layout/header.php`, views individuais |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| F-11 | **Internacionalização (i18n)** | Todo o texto está hardcoded em português. Criar sistema de tradução para expansão futura (inglês, espanhol). | Novo: `app/lang/`, todas as views |
| F-12 | **Temas por tenant** | Permitir que cada tenant customize cores e logo além do que já existe. Implementar CSS variables dinâmicas carregadas da `company_settings`. | `assets/css/theme.css`, `app/models/CompanySettings.php` |
| F-13 | **Tour interativo / Onboarding** | O módulo `walkthrough` existe mas pode ser expandido com tours guiados para novos usuários em cada módulo. | `app/views/walkthrough/` |

---

## 5. 📦 Módulos de Negócio

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| M-01 | **Módulo de Fornecedores** | Não existe gestão de fornecedores. Essencial para controle de compras, custos e estoque. Criar CRUD completo com vínculo a produtos. | Novo: `app/models/Supplier.php`, `app/controllers/SupplierController.php`, `app/views/suppliers/` |
| M-02 | **Módulo de Compras / Pedidos de Compra** | Vincular fornecedores → produtos → entrada de estoque. Workflow de aprovação de compras. | Novo: `app/models/PurchaseOrder.php`, `app/controllers/PurchaseController.php` |
| M-03 | **Relatórios avançados** | `orders/report.php` existe, mas faltam relatórios de: faturamento por período, produtos mais vendidos, tempo médio por etapa do pipeline, performance por responsável, estoque mínimo, inadimplência. | Novo: `app/controllers/ReportController.php`, `app/views/reports/` |
| M-04 | **Impressão térmica (ESC/POS)** | `print_thermal_receipt.php` existe para receipt. Expandir para suportar impressão direta via API JS ou extensão de navegador (ex: QZ Tray). | `app/views/pipeline/print_thermal_receipt.php` |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| M-05 | **Agenda / Calendário integrado** | `orders/agenda.php` existe. Expandir com: visualização de calendário mensal, arrastar para reagendar, integração com Google Calendar. | `app/views/orders/agenda.php` |
| M-06 | **CRM — Histórico de interações** | Registrar histórico de contatos com clientes: ligações, emails, visitas, notas. Vincular ao timeline do pedido. | Novo: `app/models/CustomerInteraction.php` |
| M-07 | **Módulo de Devoluções / Trocas** | Não existe fluxo de RMA (Return Merchandise Authorization). Criar com vínculo ao pedido original e impacto no estoque. | Novo: `app/models/ReturnOrder.php` |
| M-08 | **Custo de Produção** | Calcular custo real de produção por pedido (matéria-prima + mão de obra + overhead) e comparar com preço de venda para margem. | Expansão do Pipeline/Order model |
| M-09 | **Controle de lotes e validades** | Para segmentos de alimentos e farmacêutico. Rastrear lote + data de validade por item de estoque. | Expansão de `app/models/Stock.php` |
| M-10 | **Módulo de Comissões** | Calcular comissão de vendedores baseado em regras configuráveis (% sobre venda, escalonado, por produto). | Novo: `app/models/Commission.php` |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| M-11 | **Kanban Board para outros módulos** | Aplicar a lógica de Kanban do pipeline para: tarefas internas, suporte ao cliente, aprovação de compras. | Genérico: `app/core/KanbanBoard.php` |
| M-12 | **Módulo de Contratos / Assinaturas** | Para serviços recorrentes: contratos com renovação automática, cobrança recorrente. | Novo módulo |
| M-13 | **Integração com transportadoras** | API Melhor Envio, Correios, Jadlog para cálculo automático de frete e rastreamento. Os badges já existem na UI como placeholder. | Novo: `app/services/ShippingService.php` |
| M-14 | **E-commerce / Loja Online** | O catálogo público já existe. Expandir para checkout completo com pagamento online via gateways já integrados. | Expansão do `CatalogController` |

### 🔵 FUTURA

| # | Melhoria | Descrição |
|---|----------|-----------|
| M-15 | **Business Intelligence (BI)** | Dashboard analítico com gráficos avançados, previsões, sazonalidade, ABC de produtos. |
| M-16 | **Integração com ERPs externos** | Exportação/importação para SAP, TOTVS, Bling, Tiny via API. |
| M-17 | **Módulo de RH básico** | Registro de funcionários, controle de horas, banco de horas, vínculo com setores de produção. |

---

## 6. 🧪 Testes & Qualidade

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| T-01 | **Cobertura de testes unitários** | Apenas 2 testes unitários existem (`DashboardWidgetTest`, `EventDispatcherTest`). Criar testes para todos os Models, especialmente: `Order`, `Financial`, `Stock`, `Pipeline`, `User`. | `tests/Unit/` |
| T-02 | **Testes de integração** | Os testes em `tests/Pages/` são HTTP-level. Criar testes de integração que testem Model+DB com banco de teste isolado. | `tests/Integration/` (novo) |
| T-03 | **CI/CD Pipeline** | Não há pipeline de integração contínua. Configurar GitHub Actions para: rodar testes, lint PHP (PHPStan/Psalm), verificar padrões (PHP_CodeSniffer). | `.github/workflows/ci.yml` (novo) |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| T-04 | **Análise estática (PHPStan)** | Introduzir PHPStan nível 5+ para detectar erros de tipo, propriedades indefinidas, métodos inexistentes. | `composer.json`, `phpstan.neon` (novo) |
| T-05 | **Testes de segurança automatizados** | Testes para: CSRF bypass, XSS injection, SQL injection, session fixation. | `tests/Security/` (novo) |
| T-06 | **Testes E2E** | Implementar testes end-to-end com Playwright ou Cypress para fluxos críticos: login → criar pedido → mover pipeline → concluir. | `tests/e2e/` (novo) |
| T-07 | **Testes da API Node.js** | Zero testes na API. Implementar com Jest + Supertest. | `api/tests/` (novo) |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| T-08 | **Code coverage report** | Gerar relatório de cobertura com PHPUnit + HTML output para acompanhar progresso. | `phpunit.xml` |
| T-09 | **Mutation testing** | Usar Infection PHP para verificar qualidade dos testes (não apenas cobertura). | `composer.json` |

---

## 7. ⚡ Performance & Cache

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| P-01 | **Connection pooling / singleton** | `new Database()→getConnection()` cria nova conexão em cada chamada. Em uma request complexa (pipeline detail), isso pode gerar 10+ conexões. Implementar singleton pattern. | `app/config/database.php` |
| P-02 | **Cache de company_settings** | `CompanySettings` é consultado múltiplas vezes por request (session timeout, módulos, configurações de email, etc.). Cachear na sessão ou em arquivo. | `app/models/CompanySettings.php` |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| P-03 | **Cache layer (Redis / APCu)** | Implementar cache para: listagem de categorias, configurações do tenant, dados do dashboard. TTL de 5-15 minutos. | Novo: `app/core/Cache.php` |
| P-04 | **Lazy loading de imagens** | Imagens de produtos no catálogo e listagens devem usar `loading="lazy"` e placeholder de baixa resolução. | Views de produtos e catálogo |
| P-05 | **Minificação e compressão de assets** | CSS/JS não estão minificados. `style.css` tem 1400+ linhas. Implementar build step com minificação. | `assets/css/`, `assets/js/` |
| P-06 | **Paginação server-side universal** | Algumas listagens (`readAll`) carregam todos os registros. Garantir paginação em: produtos, clientes, pedidos, movimentações de estoque, logs. | Models e controllers afetados |
| P-07 | **Otimizar dashboard queries** | `home/index.php` executa muitas queries individuais. Consolidar e cachear dados que não mudam a cada segundo. | `app/views/home/index.php` |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| P-08 | **HTTP/2 Server Push** | Pré-enviar CSS e JS críticos via Server Push para reduzir TTFB. | `.htaccess` ou configuração nginx |
| P-09 | **Service Worker para offline** | `manifest.json` existe, mas sem service worker. Implementar para cache de assets e experiência offline básica. | Novo: `service-worker.js` |

---

## 8. 🌐 API Node.js

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| N-01 | **Expandir endpoints** | Apenas `ProductController` e `WebhookController` existem. Criar endpoints para: Orders, Customers, Pipeline, Financial, Stock. | `api/src/controllers/`, `api/src/routes/` |
| N-02 | **Validação de input** | Não há validação estruturada nos endpoints. Implementar Joi ou Zod para schema validation. | `api/src/middlewares/`, `api/src/controllers/` |
| N-03 | **Documentação da API (Swagger/OpenAPI)** | Zero documentação dos endpoints. Implementar Swagger UI com swagger-jsdoc. | `api/docs/swagger.yaml` (novo) |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| N-04 | **WebSocket para real-time** | Adicionar Socket.io para: notificações de pipeline, atualizações de estoque em tempo real, chat entre operadores. | `api/src/services/SocketService.js` (novo) |
| N-05 | **Testes unitários e de integração** | Nenhum teste na API. Implementar Jest + Supertest. | `api/tests/` (novo) |
| N-06 | **TypeScript migration** | A API está em JavaScript puro. Migrar para TypeScript para melhor type-safety e manutenibilidade. | `api/src/**/*.ts` |
| N-07 | **Webhook retry e dead-letter queue** | Webhooks de gateways de pagamento não possuem mecanismo de retry em caso de falha. | `api/src/services/WebhookRetry.js` (novo) |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| N-08 | **GraphQL** | Alternativa REST para queries flexíveis no frontend (dashboard, relatórios customizáveis). | Novo: `api/src/graphql/` |
| N-09 | **API versioning** | Implementar versionamento (`/api/v1/`, `/api/v2/`) para evitar breaking changes. | `api/src/routes/` |

---

## 9. 🏢 Multi-Tenancy

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| MT-01 | **Painel admin de tenants** | Não há interface para gerenciar tenants (criar, ativar, desativar, configurar módulos). Tudo é feito via banco `akti_master`. Criar admin panel. | Novo: `app/controllers/TenantController.php`, `app/views/admin/tenants/` |
| MT-02 | **Onboarding automatizado** | Criação de novo tenant deve ser automatizada: criar banco, rodar migrations, configurar dados iniciais, criar admin do tenant. | Novo: `scripts/create_tenant.php` |
| MT-03 | **Limites de uso (quotas)** | `max_users`, `max_products`, `max_warehouses`, etc. existem no tenant config, mas a enforcement em runtime deve ser auditada e padronizada. | Controllers afetados |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| MT-04 | **Billing / Assinatura de tenants** | Controlar plano do tenant (free, basic, pro), data de expiração, limites por plano. | `akti_master`, novo modelo |
| MT-05 | **Migração entre planos** | Permitir upgrade/downgrade de plano com ajuste automático de módulos e limites. | Admin panel |
| MT-06 | **Analytics cross-tenant** | Painel do super-admin com métricas agregadas: número de pedidos por tenant, receita estimada, uso de armazenamento. | Novo: `app/views/admin/analytics/` |

---

## 10. 📱 Mobile & PWA

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| PW-01 | **PWA completa** | `manifest.json` existe, mas sem service worker, ícones em todos os tamanhos, e splash screen. Completar para instalação via navegador. | `manifest.json`, novo: `service-worker.js` |
| PW-02 | **Push Notifications** | Notificações push para: pedidos atrasados, pagamentos recebidos, movimentação de pipeline. Via Web Push API. | API Node.js + service worker |
| PW-03 | **Layout mobile-first no pipeline** | O pipeline Kanban em mobile requer scroll horizontal. Considerar vista em lista (card stack) para mobile como alternativa. | `app/views/pipeline/index.php`, `assets/css/style.css` |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| PW-04 | **App nativo (React Native / Flutter)** | Para funcionalidades como leitor de código de barras, câmera para estoque, GPS para entregas. | Novo projeto separado |

---

## 11. 📄 Documentação

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| DC-01 | **Documentação de API endpoints PHP** | Não há documentação dos endpoints AJAX do PHP (moveAjax, checkOrderStock, etc.). Documentar com Postman collection ou similar. | `docs/API_PHP.md` (novo) |
| DC-02 | **Guia de deploy** | Não há documentação de como colocar o sistema em produção (requisitos de servidor, configuração nginx/apache, variáveis de ambiente obrigatórias, migrations). | `docs/DEPLOY.md` (novo) |
| DC-03 | **Guia de contribuição** | Falta `CONTRIBUTING.md` com padrões de código, workflow de PRs, convenção de commits. | `CONTRIBUTING.md` (novo) |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| DC-04 | **Diagrama de banco de dados** | Não há ERD (Entity Relationship Diagram). Gerar com dbdiagram.io ou similar e manter atualizado. | `docs/database_erd.png` (novo) |
| DC-05 | **Diagrama de arquitetura** | Documentar visualmente: fluxo de request, camadas MVC, comunicação PHP↔Node.js, multi-tenancy. | `docs/architecture_diagram.png` (novo) |
| DC-06 | **Changelog** | Não há `CHANGELOG.md` com histórico de versões. Manter log de alterações por release. | `CHANGELOG.md` (novo) |
| DC-07 | **PHPDoc coverage** | Nem todos os métodos possuem PHPDoc. Padronizar com `@param`, `@return`, `@throws` em todos os métodos públicos. | Todos os models e controllers |

---

## 12. 🚀 DevOps & Infraestrutura

### 🟠 ALTA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| DV-01 | **Environment variables (.env)** | Variáveis de ambiente são lidas via `getenv()` com fallback hardcoded. Implementar `.env` obrigatório com `vlucas/phpdotenv` e `.env.example` versionado. | `composer.json`, `.env.example` (novo), `app/config/tenant.php` |
| DV-02 | **Docker / Docker Compose** | Facilitar setup de desenvolvimento com Docker (PHP-FPM + Nginx + MySQL + Node.js). | `Dockerfile`, `docker-compose.yml` (novos) |
| DV-03 | **CI/CD com GitHub Actions** | Automatizar: lint, testes, build, deploy para staging. | `.github/workflows/` |

### 🟡 MÉDIA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| DV-04 | **Logging centralizado** | Logs estão espalhados em `storage/logs/` com diferentes formatos. Padronizar com PSR-3 (Monolog) e rotação automática. | `composer.json`, `app/core/Log.php` (novo) |
| DV-05 | **Health check endpoint PHP** | A API Node.js tem `/health`. Criar equivalente PHP para monitoramento. | `scripts/healthcheck.php` (novo) |
| DV-06 | **Monitoramento (APM)** | Integrar com ferramentas como Sentry (erros), New Relic ou Datadog (performance). | `composer.json`, `index.php` |
| DV-07 | **Ambientes separados** | Garantir configuração separada para: development, staging, production. Atualmente depende apenas de variáveis de ambiente sem validação. | `.env.example`, documentação |

### 🟢 BAIXA

| # | Melhoria | Descrição | Arquivos Afetados |
|---|----------|-----------|-------------------|
| DV-08 | **Auto-scaling** | Para cloud hosting, configurar auto-scale de workers PHP-FPM baseado em carga. | Infra docs |
| DV-09 | **CDN para assets** | Servir CSS, JS e imagens via CDN (CloudFlare, AWS CloudFront) para reduzir latência. | Configuração |

---

## 📅 Roadmap Sugerido por Trimestre

### Q2 2026 (Abril–Junho) — Fundação & Segurança
- 🔴 S-01 a S-05 (Segurança crítica)
- 🟠 A-01, A-02, A-05 (Connection singleton, DI, fix echo)
- 🟠 D-01 (Migrations automatizadas)
- 🟠 T-01, T-03 (Testes unitários + CI)
- 🟠 DV-01, DV-02 (Dotenv + Docker)

### Q3 2026 (Julho–Setembro) — Qualidade & Módulos
- 🟠 S-06 a S-10 (Segurança alta)
- 🟠 D-02, D-03 (Índices + FKs)
- 🟠 M-01, M-02 (Fornecedores + Compras)
- 🟠 M-03 (Relatórios avançados)
- 🟡 A-06, A-08 (Service layer + Middleware pipeline)
- 🟡 F-01, F-02 (Componentização + Bundling)

### Q4 2026 (Outubro–Dezembro) — UX & Expansão
- 🟡 F-03 a F-10 (Dark mode, a11y, notificações, keyboard shortcuts)
- 🟡 M-05, M-06 (Agenda, CRM)
- 🟡 N-01 a N-05 (API expandida, WebSocket, docs)
- 🟡 MT-01, MT-02 (Admin de tenants)
- 🟡 PW-01, PW-02 (PWA + Push)

### Q1 2027 — Maturidade
- 🟢 Itens de baixa prioridade
- 🔵 Itens futuros conforme demanda
- Foco em estabilidade, performance e polimento

---

## 📈 Métricas de Acompanhamento

| Métrica | Estado Atual | Meta Q2 | Meta Q4 |
|---------|-------------|---------|---------|
| Cobertura de testes (PHP) | ~5% (2 unit tests) | 40% | 70% |
| Cobertura de testes (API) | 0% | 30% | 60% |
| PHPStan nível | N/A | Nível 5 | Nível 6 |
| Módulos documentados | 15 (.github/instructions/) | 20 | 25 |
| Vulnerabilidades conhecidas | ~5 | 0 críticas | 0 altas |
| Tempo médio de carregamento | Não medido | <2s | <1.5s |
| Disponibilidade (uptime) | Não monitorado | 99% | 99.5% |

---

## 🏷️ Tags de Referência

- **Codebase PHP:** `app/` — 28 models, 18 controllers, 19 view dirs, 5 core classes
- **API Node.js:** `api/` — Express + Sequelize, 2 controllers, 3 rotas
- **Frontend:** Bootstrap 5, jQuery, SweetAlert2, SortableJS, FontAwesome
- **Banco:** MySQL/MariaDB, 6 migrations manuais em `/sql`
- **Testes:** PHPUnit 9.6, 8 page tests + 2 unit tests
- **Multi-tenancy:** Database-per-tenant via `akti_master`

---

> **Nota:** Este roadmap deve ser revisado mensalmente e repriorizado conforme feedback de usuários, incidentes de produção e objetivos de negócio. Cada item implementado deve seguir o fluxo padrão: SQL migration → Model → Controller → View → Testes → Documentação.
