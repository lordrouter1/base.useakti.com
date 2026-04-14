# Roadmap de Novas Funcionalidades — Akti v3

> ## Contexto
> Todas as 18 features planejadas no roadmap v2 foram **implementadas com sucesso** (commit `c3e0fd5`). O sistema agora possui notificações, permissões granulares, anexos, audit log, fornecedores, orçamentos, calendário, relatórios customizáveis, workflows, email marketing, qualidade, site builder, dashboard widgets, e mais.
>
> Este novo roadmap propõe features para o próximo ciclo de evolução, focando em maturidade operacional, inteligência de dados e expansão comercial.

---

## Status das Features v2 (Todas ✅ Implementadas)

| ID | Feature | Status |
|----|---------|--------|
| FEAT-001 | Sistema de Notificações em Tempo Real | ✅ Implementado |
| FEAT-002 | Sistema de Permissões Granular (RBAC v2) | ✅ Implementado |
| FEAT-003 | Sistema de Anexos/Documentos | ✅ Implementado |
| FEAT-004 | Auditoria Completa (Audit Log) | ✅ Implementado |
| FEAT-005 | Módulo de Compras / Fornecedores | ✅ Implementado |
| FEAT-006 | Módulo de Orçamentos Avançado | ✅ Implementado |
| FEAT-007 | Agenda/Calendário Integrado | ✅ Implementado |
| FEAT-008 | Relatórios Customizáveis | ✅ Implementado |
| FEAT-009 | Multi-Currency e Multi-Language | ✅ Implementado |
| FEAT-010 | Automação de Workflow (Regras de Negócio) | ✅ Implementado |
| FEAT-011 | App Mobile (PWA) | ✅ Implementado |
| FEAT-012 | Expansão da API REST | ✅ Implementado |
| FEAT-013 | Email Marketing / CRM Avançado | ✅ Implementado |
| FEAT-014 | Integração com Marketplaces | ✅ Implementado |
| FEAT-015 | IA para Previsão de Demanda | ✅ Implementado |
| FEAT-016 | Dashboard em Tempo Real | ✅ Implementado |
| FEAT-017 | Módulo de Qualidade (ISO 9001) | ✅ Implementado |
| FEAT-018 | Integração com Contabilidade (SPED) | ✅ Implementado |

---

## Novas Features — Ciclo v3

### Fase 1: Foundation (Infraestrutura)

### FEAT-019: Build Pipeline Frontend (Vite/Webpack)
- **Descrição:** Implementar bundler para JS/CSS com minificação, tree-shaking, code splitting e source maps.
- **Benefício:** Redução de 40-60% no tamanho dos assets, melhor performance, habilita remoção de `unsafe-inline` do CSP.
- **Complexidade:** Média
- **Dependências:** FE-003 (extração de scripts inline)
- **Implementação sugerida:** Vite com configuração para multi-entry (um bundle por módulo). Assets compilados em `assets/dist/`.
- **Escopo:**
  - [ ] Configurar Vite com entry points por módulo
  - [ ] Migrar scripts inline para módulos JS
  - [ ] Configurar minificação CSS/JS
  - [ ] Gerar hashes para cache busting
  - [ ] Atualizar `header.php`/`footer.php` para carregar bundles
- **Status:** ⬜ Planejado

### FEAT-020: Sistema de Cache Centralizado
- **Descrição:** Implementar camada de cache (`CacheManager`) com driver file/Redis para queries pesadas, contadores, configurações.
- **Benefício:** Redução de carga no banco de dados, melhora de performance em listagens com 10k+ registros.
- **Complexidade:** Média
- **Dependências:** —
- **Implementação sugerida:**
  ```php
  class CacheManager {
      public function remember(string $key, int $ttl, callable $callback): mixed;
      public function forget(string $key): void;
      public function flush(string $prefix = ''): void;
  }
  ```
- **Escopo:**
  - [ ] Criar `app/services/CacheManager.php` com driver file
  - [ ] Adicionar driver Redis opcional
  - [ ] Integrar com paginação (cache de count)
  - [ ] Integrar com dashboard (cache de métricas)
  - [ ] Invalidação automática em write operations
- **Status:** ⬜ Planejado

### FEAT-021: Migration Runner Automatizado
- **Descrição:** Script que detecta, aplica e registra migrations automaticamente, com suporte a rollback.
- **Benefício:** Deploy mais seguro, rastreabilidade de mudanças de schema, eliminação de aplicação manual.
- **Complexidade:** Média
- **Dependências:** DB-003 (tabela schema_migrations)
- **Implementação sugerida:**
  ```php
  class MigrationRunner {
      public function pending(): array;  // Lista pendentes
      public function run(): int;        // Aplica pendentes, retorna count
      public function rollback(): void;  // Reverte última batch
  }
  ```
- **Escopo:**
  - [ ] Criar tabela `schema_migrations`
  - [ ] Implementar `MigrationRunner` service
  - [ ] Integrar com `scripts/run_migration.php`
  - [ ] Adicionar comando no master panel
  - [ ] Logging de cada migration aplicada
- **Status:** ⬜ Planejado

### FEAT-022: Logging Estruturado
- **Descrição:** Substituir `error_log()` por sistema de logging estruturado com níveis, contexto e rotação.
- **Benefício:** Diagnóstico mais rápido, auditabilidade, integração com monitoramento.
- **Complexidade:** Média
- **Dependências:** —
- **Implementação sugerida:** Logger PSR-3 compatible com output para `storage/logs/`.
- **Escopo:**
  - [ ] Criar `app/services/Logger.php` (PSR-3)
  - [ ] Níveis: DEBUG, INFO, WARNING, ERROR, CRITICAL
  - [ ] Contexto automático: tenant_id, user_id, request_id
  - [ ] Rotação diária de arquivos
  - [ ] Integrar com exception handler
  - [ ] Viewer no master panel
- **Status:** ⬜ Planejado

---

### Fase 2: Core Business (Valor Direto)

### FEAT-023: Portal do Cliente Self-Service
- **Descrição:** Expandir o portal público para que clientes consultem pedidos, parcelas, documentos fiscais e abram tickets de suporte.
- **Benefício:** Redução de 30-50% no volume de atendimento ao cliente. Autonomia para o cliente final.
- **Complexidade:** Alta
- **Dependências:** FEAT-003 (anexos), FEAT-011 (PWA)
- **Implementação sugerida:** Área autenticada no portal com JWT, views separadas em `app/views/portal/`.
- **Escopo:**
  - [ ] Autenticação de cliente (email + token/senha)
  - [ ] Dashboard do cliente (pedidos, parcelas)
  - [ ] Consulta de NF-e e boletos
  - [ ] Download de documentos/anexos
  - [ ] Abertura de tickets de suporte
  - [ ] Notificações por email de atualização
- **Status:** ⬜ Planejado

### FEAT-024: Módulo de Tickets / Help Desk
- **Descrição:** Sistema de tickets para suporte interno e externo, com prioridades, SLA, categorias e integração com email.
- **Benefício:** Rastreabilidade de problemas, métricas de atendimento, SLA medido.
- **Complexidade:** Alta
- **Dependências:** FEAT-001 (notificações), FEAT-023 (portal)
- **Implementação sugerida:** Model `Ticket`, `TicketMessage`, `TicketCategory`. Kanban de tickets.
- **Escopo:**
  - [ ] CRUD de tickets (abertura, resposta, fechamento)
  - [ ] Categorias e prioridades configuráveis
  - [ ] SLA por categoria (tempo de resposta/resolução)
  - [ ] Dashboard de métricas de suporte
  - [ ] Integração com email (receber tickets por email)
  - [ ] Portal do cliente: abertura via portal
- **Status:** ⬜ Planejado

### FEAT-025: Módulo de Manutenção Preventiva
- **Descrição:** Cadastro de equipamentos de produção com agenda de manutenção preventiva, alerts, histórico e custos.
- **Benefício:** Redução de paradas não planejadas, controle de custos de manutenção.
- **Complexidade:** Média
- **Dependências:** FEAT-007 (calendário)
- **Implementação sugerida:** Models `Equipment`, `MaintenanceSchedule`, `MaintenanceLog`.
- **Escopo:**
  - [ ] Cadastro de equipamentos (nome, modelo, local)
  - [ ] Agenda de manutenção preventiva
  - [ ] Alerts automáticos (N dias antes)
  - [ ] Registro de manutenção realizada (custo, peças, tempo)
  - [ ] Dashboard de disponibilidade de equipamentos
  - [ ] Integração com pipeline (bloquear etapa se equipamento indisponível)
- **Status:** ⬜ Planejado

### FEAT-026: Gestor de Custos de Produção
- **Descrição:** Cálculo automático de custo unitário de produção baseado em insumos, mão de obra, overhead, e tempo de produção real.
- **Benefício:** Precificação precisa, margem de lucro real calculada.
- **Complexidade:** Alta
- **Dependências:** FEAT-005 (fornecedores/insumos), Pipeline
- **Implementação sugerida:** Service `ProductionCostService` que agrega dados de pipeline timing, supply consumption e hora-homem.
- **Escopo:**
  - [ ] Configuração de custo hora-homem por setor/etapa
  - [ ] Custo de insumos por produto (BOM - Bill of Materials)
  - [ ] Overhead configurável (% ou fixo)
  - [ ] Cálculo automático por pedido produzido
  - [ ] Comparativo: custo estimado vs custo real
  - [ ] Relatório de margem de contribuição
- **Status:** ⬜ Planejado

---

### Fase 3: Advanced (Diferenciação)

### FEAT-027: BI — Business Intelligence Dashboard
- **Descrição:** Dashboards avançados com gráficos interativos, drill-down, filtros temporais e exportação em PDF/Excel.
- **Benefício:** Visão estratégica para gestores, decisões baseadas em dados.
- **Complexidade:** Alta
- **Dependências:** FEAT-016 (dashboard), FEAT-008 (relatórios)
- **Implementação sugerida:** Expandir Chart.js com drill-down. Predefined dashboards + custom.
- **Escopo:**
  - [ ] Dashboard de vendas (faturamento, ticket médio, conversão)
  - [ ] Dashboard de produção (throughput, gargalos, OEE)
  - [ ] Dashboard financeiro (fluxo de caixa, inadimplência, DRE)
  - [ ] Filtros por período, setor, vendedor, produto
  - [ ] Exportação PDF/Excel
  - [ ] Drill-down em gráficos (clique → detalhamento)
- **Status:** ⬜ Planejado

### FEAT-028: Integração com WhatsApp Business API
- **Descrição:** Envio automatizado de notificações via WhatsApp: confirmação de pedido, boleto, NF-e, lembrete de pagamento.
- **Benefício:** Canal de comunicação com 98% de abertura. Redução de inadimplência com lembretes.
- **Complexidade:** Alta
- **Dependências:** FEAT-001 (notificações), FEAT-010 (workflows)
- **Implementação sugerida:** Integração via WhatsApp Cloud API ou provedor (Z-API, Evolution API). Templates pré-aprovados.
- **Escopo:**
  - [ ] Configuração de credenciais por tenant
  - [ ] Templates de mensagem (pedido, NF-e, boleto, lembrete)
  - [ ] Envio automático via workflow triggers
  - [ ] Log de mensagens enviadas
  - [ ] Opt-in/opt-out de clientes
  - [ ] Dashboard de entregas e leituras
- **Status:** ⬜ Planejado

### FEAT-029: Multi-filial / Multi-unidade
- **Descrição:** Suporte a múltiplas unidades/filiais por tenant, com estoque, produção e financeiro separados mas consolidáveis.
- **Benefício:** Empresas com múltiplas plantas podem gerenciar tudo em um único sistema.
- **Complexidade:** Muito Alta
- **Dependências:** Multi-tenant (existente)
- **Implementação sugerida:** Coluna `branch_id` em tabelas de dados, com consolidação no dashboard.
- **Escopo:**
  - [ ] Cadastro de filiais por tenant
  - [ ] Estoque por filial
  - [ ] Produção por filial
  - [ ] Transferência entre filiais
  - [ ] Financeiro consolidável e por filial
  - [ ] Dashboard com toggle filial/consolidado
- **Status:** ⬜ Planejado

### FEAT-030: API Pública Documentada (OpenAPI)
- **Descrição:** Documentação Swagger/OpenAPI para a API REST, com sandbox para desenvolvedores parceiros.
- **Benefício:** Ecossistema de integrações, parcerias tecnológicas.
- **Complexidade:** Média
- **Dependências:** FEAT-012 (API REST)
- **Implementação sugerida:** Swagger UI + auto-geração de spec a partir de anotações nos controllers.
- **Escopo:**
  - [ ] Geração de especificação OpenAPI 3.0
  - [ ] Swagger UI acessível via `/api/docs`
  - [ ] Autenticação API key por tenant
  - [ ] Rate limiting por API key
  - [ ] Sandbox para testes
  - [ ] Webhook subscriptions para terceiros
- **Status:** ⬜ Planejado

---

### Fase 4: Innovation (Exploratório)

### FEAT-031: Assistente IA para Operadores
- **Descrição:** Chat assistant integrado no sistema que responde perguntas sobre pedidos, clientes, produção usando NLP + dados do sistema.
- **Benefício:** Acesso rápido a informações sem navegar pelo sistema. Produtividade operacional.
- **Complexidade:** Muito Alta
- **Dependências:** FEAT-012 (API), dados consolidados
- **Implementação sugerida:** Integração com OpenAI/Claude API. Context window com dados do tenant.
- **Escopo:**
  - [ ] Chat widget no sistema
  - [ ] Queries em linguagem natural → SQL seguro
  - [ ] Resumo de pedidos, clientes, produção
  - [ ] Sugestões de ação (lembrar cobranças, alertar atrasos)
  - [ ] Guardrails de segurança (restringir acesso a dados sensíveis)
- **Status:** ⬜ Exploratório

### FEAT-032: Módulo de Rastreamento de Entregas
- **Descrição:** Rastreamento de entregas integrado com transportadoras, com timeline de status e notificação ao cliente.
- **Benefício:** Visibilidade completa do ciclo pedido→entrega.
- **Complexidade:** Alta
- **Dependências:** FEAT-023 (portal), FEAT-028 (WhatsApp)
- **Implementação sugerida:** Integração com APIs de transportadoras (Correios, Jadlog, etc.) + tracking page.
- **Escopo:**
  - [ ] Cadastro de transportadoras
  - [ ] Geração de código de rastreio
  - [ ] Consulta automática de status
  - [ ] Timeline de entrega no pedido
  - [ ] Notificação ao cliente (email/WhatsApp)
  - [ ] Dashboard de entregas pendentes
- **Status:** ⬜ Exploratório

### FEAT-033: Gamificação de Produtividade
- **Descrição:** Sistema de pontuação, rankings e conquistas para operadores de produção baseado em metas de produtividade e qualidade.
- **Benefício:** Engajamento da equipe, melhoria contínua gamificada.
- **Complexidade:** Média
- **Dependências:** Pipeline, FEAT-017 (qualidade)
- **Implementação sugerida:** Models `Achievement`, `UserScore`, `Leaderboard`. Badges visuais no perfil.
- **Escopo:**
  - [ ] Métricas rastreáveis (unidades produzidas, tempo, qualidade)
  - [ ] Sistema de pontos e níveis
  - [ ] Conquistas/badges por milestones
  - [ ] Ranking semanal/mensal por setor
  - [ ] Dashboard de gamificação
- **Status:** ⬜ Exploratório

### FEAT-034: Módulo de Sustentabilidade (ESG)
- **Descrição:** Rastreamento de métricas ambientais: consumo de energia, água, desperdício de materiais, emissões de carbono por produto.
- **Benefício:** Compliance ESG, certificações ambientais, diferenciação de mercado.
- **Complexidade:** Alta
- **Dependências:** FEAT-026 (custos de produção)
- **Implementação sugerida:** Models `ResourceConsumption`, `WasteLog`, `CarbonMetric`.
- **Escopo:**
  - [ ] Cadastro de métricas ambientais por setor
  - [ ] Input de consumo (energia, água, materiais)
  - [ ] Cálculo de pegada de carbono por produto
  - [ ] Relatório ESG automático
  - [ ] Metas e alertas de consumo
  - [ ] Dashboard de sustentabilidade
- **Status:** ⬜ Exploratório

---

## Resumo por Fase

| Fase | Features | Complexidade Média | Foco |
|------|----------|--------------------|------|
| 1 — Foundation | FEAT-019 a FEAT-022 (4) | Média | Build, cache, migrations, logging |
| 2 — Core Business | FEAT-023 a FEAT-026 (4) | Alta | Portal, tickets, manutenção, custos |
| 3 — Advanced | FEAT-027 a FEAT-030 (4) | Alta | BI, WhatsApp, multi-filial, API docs |
| 4 — Innovation | FEAT-031 a FEAT-034 (4) | Muito Alta | IA, rastreamento, gamificação, ESG |
| **Total** | **16 features** | | |

## Priorização Recomendada

### Sprint Imediato (próximas 2-4 semanas)
1. **FEAT-019** (Build Pipeline) — habilita SEC-006 (remoção unsafe-inline)
2. **FEAT-022** (Logging Estruturado) — habilita diagnóstico e auditing
3. **FEAT-021** (Migration Runner) — habilita DB-003/DB-004

### Próximo Trimestre
4. **FEAT-020** (Cache) — performance
5. **FEAT-023** (Portal Self-Service) — valor para clientes
6. **FEAT-024** (Tickets/Help Desk) — suporte

### Semestre
7-10. FEAT-025 a FEAT-028 — diferenciação
