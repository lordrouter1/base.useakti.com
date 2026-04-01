# Auditoria de Implementações e Padrões — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** Padrões de código, consistência entre módulos, funcionalidades implementadas, funcionalidades incompletas, code smells  
> **Referência:** PSR-12, Clean Code, SOLID, DRY

---

## 1. Resumo Executivo

O Akti é um sistema **maduro e funcional** com 31 módulos implementados, cobrindo CRM, estoque, financeiro, NF-e, pipeline de produção, portal do cliente e site builder. A implementação é **consistente nos novos módulos** (Fases 2-5) mas apresenta **debt técnico nos módulos legados** (User, Category). As principais áreas de melhoria são padronização de controllers, extração de BaseController e modernização de retornos dos models.

| Aspecto | Nota | Observação |
|---|---|---|
| Funcionalidades implementadas | ⭐⭐⭐⭐⭐ | 31 módulos completos |
| Consistência de código | ⭐⭐⭐ | Novos módulos excelentes, legados inconsistentes |
| Code smells | ⭐⭐⭐ | Duplicação em controllers, models legados |
| Documentação interna | ⭐⭐⭐⭐ | .github/instructions/ detalhado |
| Convention compliance | ⭐⭐⭐⭐ | PSR-4 100%, PSR-12 parcial |

---

## 2. Funcionalidades Implementadas

### 2.1 Módulos Completos (31)

| # | Módulo | Status | Complexidade | Notas |
|---|---|---|---|---|
| 1 | **Autenticação** | ✅ Completo | Média | Login, sessão, JWT (API), 2FA preparado |
| 2 | **Usuários** | ✅ Completo | Alta | CRUD, grupos, permissões granulares |
| 3 | **Clientes** | ✅ Completo | Alta | CRUD, wizard, auto-save, tags, export, validação CPF/CNPJ |
| 4 | **Produtos** | ✅ Completo | Alta | CRUD, grades, categorias, subcategorias, imagens, fiscal, import |
| 5 | **Pedidos** | ✅ Completo | Alta | CRUD, items, agenda, print, cotação, status |
| 6 | **Pipeline** | ✅ Completo | Muito Alta | Kanban drag-drop, stages configuráveis, SLA, alertas, produção |
| 7 | **Estoque** | ✅ Completo | Alta | Armazéns, movements, entrada, transferência, multi-warehouse |
| 8 | **Financeiro** | ✅ Completo | Muito Alta | Parcelas, transações, DRE, fluxo de caixa, recorrentes, import |
| 9 | **Comissões** | ✅ Completo | Média | Formas, cálculo, registro, status |
| 10 | **NF-e** | ✅ Completo | Muito Alta | 24 services, emissão, cancelamento, contingência, SPED |
| 11 | **Categorias** | ✅ Completo | Baixa | CRUD com subcategorias |
| 12 | **Setores** | ✅ Completo | Baixa | Setores de produção |
| 13 | **Relatórios** | ✅ Completo | Alta | Vendas, receita, DRE, balanço, export PDF/Excel |
| 14 | **Dashboard** | ✅ Completo | Média | Widgets configuráveis, lazy-load, aprovação por grupo |
| 15 | **Configurações** | ✅ Completo | Média | Empresa, tabelas de preço, segurança, preparação |
| 16 | **Portal Cliente** | ✅ Completo | Alta | PWA, pedidos, orçamentos, catálogo, login separado |
| 17 | **Catálogo Público** | ✅ Completo | Média | Sem auth, carrinho, cotação |
| 18 | **Site Builder** | 🔄 Em progresso | Alta | Páginas, seções, componentes, preview, temas |
| 19 | **Payment Gateways** | ✅ Completo | Alta | Stripe, MercadoPago, PagSeguro, webhooks |
| 20 | **Notificações** | ✅ Completo | Média | Bell, polling, mark read |
| 21 | **Busca Global** | ✅ Completo | Média | Command palette (Ctrl+K), multi-entity |
| 22 | **Walkthrough** | ✅ Completo | Média | Onboarding tour, resume, multi-page |
| 23 | **Health Check** | ✅ Completo | Baixa | DB, extensions, disk, filesystem |
| 24 | **API REST** | ✅ Completo | Alta | Node.js, JWT, multi-tenant, products CRUD |
| 25 | **Import/Export** | ✅ Completo | Alta | Excel, CSV, mapeamento de perfis |
| 26 | **IBPTAX** | ✅ Completo | Média | Tabela tributária, import |
| 27 | **Perfil** | ✅ Completo | Baixa | Avatar, dados pessoais |
| 28 | **Loja/Storefront** | 🔄 Em progresso | Média | Templates Twig, seções do site builder |
| 29 | **Dark Mode** | ✅ Completo | Média | CSS variables, toggle, sem FOUC |
| 30 | **Session Guard** | ✅ Completo | Média | Timeout, countdown modal, renovação |
| 31 | **Multi-Tenant** | ✅ Completo | Alta | Subdomain resolution, DB isolation, limits |

### 2.2 Funcionalidades Em Progresso

| Funcionalidade | Status | O que falta |
|---|---|---|
| Site Builder | 🔄 70% | Componentes drag-drop, edição inline, media library |
| Loja/Storefront | 🔄 40% | Integração com Site Builder, checkout, SEO |
| API REST | 🔄 60% | Expandir para mais entities (orders, customers, financial) |

---

## 3. Padrões de Código por Geração

### 3.1 Geração Legada (Módulos iniciais)

**Exemplos:** User, Category, Subcategory, UserGroup

| Padrão | Implementação |
|---|---|
| Model return | `PDOStatement` (não processado) |
| Controller | Inline permission check, sem helper |
| Query style | Mistura de `prepare()` e `query()` |
| Error handling | `try/catch` inconsistente |
| Eventos | Não dispara eventos |

### 3.2 Geração Intermediária (Fase 2-3)

**Exemplos:** Customer, Product, Order, Pipeline

| Padrão | Implementação |
|---|---|
| Model return | `array` via `fetchAll(FETCH_ASSOC)` |
| Controller | Permission check padronizado |
| Query style | 100% `prepare()` + `bindValue()` |
| Paginação | `readPaginated()` com count separado |
| Eventos | Dispara mas sem listeners |

### 3.3 Geração Moderna (Fase 4-5)

**Exemplos:** Financial, Installment, NfeDocument, Commission, SiteBuilder

| Padrão | Implementação |
|---|---|
| Model return | `array` tipado com metadados |
| Controller | Padronizado com JSON response |
| Query style | 100% prepared, transações explícitas |
| Paginação | Avançada com filtros e metadata |
| Eventos | Disparados e ouvidos (NF-e) |
| Validação | Via Validator utility class |
| Sanitização | Via Sanitizer utility class |

---

## 4. Code Smells Identificados

### 4.1 Duplicação de Código

| Smell | Onde | Impacto | Solução |
|---|---|---|---|
| `new Database()` em todos controllers | 31 controllers | Alto | BaseController com DI |
| Permission check inline | 25+ controllers | Médio | BaseController `requirePermission()` |
| JSON response pattern | 15+ controllers | Médio | BaseController `json()` |
| Redirect pattern | 31 controllers | Baixo | BaseController `redirect()` |
| View render pattern | 31 controllers | Baixo | BaseController `render()` |

### 4.2 Complexidade Excessiva

| Arquivo | Métrica | Observação |
|---|---|---|
| `index.php` (root) | ~280 linhas | Bootstrap + dispatch + error handler — extrair para Application class |
| `Financial.php` (model) | 1700+ linhas | Precisa de decomposição em sub-services |
| `PipelineController.php` | 20+ actions | Considerar split em sub-controllers |
| `PortalController.php` | 27+ actions | Idem |
| `NfeDocumentController.php` | 25+ actions | Idem |
| `financial-payments.js` | 900+ linhas | Decompor em módulos menores |

### 4.3 Acoplamento

| Problema | Onde | Impacto |
|---|---|---|
| Controllers instanciam models diretamente | Todos | Dificulta testes e mock |
| Services recebem PDO diretamente | Maioria | Limite de abstração |
| Views acessam `$_SESSION` direto | Algumas | Violação MVC |

### 4.4 Arquivos de Backup

| Arquivo | Observação |
|---|---|
| `app/views/customers/*.bak` | Backups de views devem ser removidos |
| `.bak` files | Devem estar no `.gitignore` (já estão) |

---

## 5. Padrões CRUD por Módulo

### 5.1 Padrão Ideal (Fase 4+)

```
Controller:
  index()  → leitura paginada + filtros → view list
  create() → form vazio → view form
  store()  → validação → sanitização → model.create → redirect
  edit()   → model.readOne → view form
  update() → validação → sanitização → model.update → redirect
  delete() → model.delete → redirect

Model:
  readPaginated(page, perPage, filters) → array['data', 'total', 'pages']
  readOne(id) → array|null
  create(data) → int (ID)
  update(id, data) → bool
  delete(id) → bool
```

### 5.2 Aderência ao Padrão

| Módulo | Index | Create/Store | Edit/Update | Delete | Paginação | Filtros |
|---|---|---|---|---|---|---|
| Customers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Products | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Orders | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Financial | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Users | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Categories | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Setores | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| NF-e | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Stock | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Pipeline | ✅ | N/A | N/A | N/A | N/A | ✅ |

---

## 6. Sistema de Eventos — Cobertura

### 6.1 Eventos Disparados

| Evento | Disparo | Listener Ativo |
|---|---|---|
| `model.nfe_document.authorized` | ✅ | ✅ Gera DANFE |
| `model.user.created` | ✅ | ❌ Sem listener |
| `model.user.updated` | ✅ | ❌ |
| `model.user.deleted` | ✅ | ❌ |
| `model.customer.created` | ✅ | ❌ |
| `model.customer.updated` | ✅ | ❌ |
| `model.customer.deleted` | ✅ | ❌ |
| `model.order.created` | ✅ | ❌ |
| `model.order.updated` | ✅ | ❌ |
| `middleware.csrf.failed` | ✅ | ❌ |
| `core.security.access_denied` | ✅ | ❌ |
| `auth.login.success` | ✅ | ❌ |
| `auth.login.failed` | ✅ | ❌ |

### 6.2 Oportunidades de Listeners

| Evento | Listener Sugerido | Benefício |
|---|---|---|
| `model.order.created` | Notificação ao vendedor | UX |
| `model.order.created` | Auto-iniciar pipeline | Automação |
| `model.customer.created` | Email de boas-vindas | Engajamento |
| `auth.login.failed` | Log de tentativas | Segurança |
| `middleware.csrf.failed` | Alerta de segurança | Segurança |

---

## 7. Internacionalização (i18n)

### 7.1 Estado Atual

- **Diretório:** `app/lang/` — existe mas conteúdo limitado
- **Idioma hardcoded:** pt_BR em views, mensagens, labels
- **Formato de data:** Brasileiro (d/m/Y) hardcoded
- **Moeda:** Real (R$) hardcoded

### 7.2 Preparação para i18n

| Aspecto | Status |
|---|---|
| Diretório de strings | ✅ Existe |
| Helper de tradução | ❌ Não implementado |
| Strings em views | ❌ Hardcoded em pt_BR |
| Formato de data configurável | ❌ Hardcoded |
| Moeda configurável | ❌ Hardcoded |

---

## 8. Documentação Interna

### 8.1 Instruções (.github/instructions/)

| Arquivo | Conteúdo | Qualidade |
|---|---|---|
| `architecture.md` | PSR-4, multi-tenant, bootstrap | ⭐⭐⭐⭐ |
| `security.md` | Sanitização, escape, CSRF | ⭐⭐⭐⭐⭐ |
| `database.md` | Migrations, convenções | ⭐⭐⭐⭐ |
| `pipeline.md` | Conceito kanban, fluxo | ⭐⭐⭐⭐ |
| `events.md` | EventDispatcher, convenções | ⭐⭐⭐⭐ |
| `extras.md` | Frontend, componentes | ⭐⭐⭐ |
| `modulo-grade_categoria_subcategoria.md` | Grades, heranças | ⭐⭐⭐⭐ |
| `upload.md` | Upload multi-tenant | ⭐⭐⭐ |
| `modulo-financeiro.md` | Financeiro completo | ⭐⭐⭐⭐ |
| `Bootloader.md` | Carregamento de módulos | ⭐⭐⭐⭐ |
| `funcoes.md` | Funções utilitárias | ⭐⭐⭐ |
| `nodejs-api` | API Node.js | ⭐⭐⭐⭐ |

### 8.2 Documentação do Projeto (docs/)

| Arquivo | Conteúdo |
|---|---|
| `API_PHP.md` | Documentação da API PHP |
| `CHANGELOG.md` | Histórico de mudanças |
| `DEPLOY.md` | Procedimento de deploy |
| `MANUAL_DO_SISTEMA.md` | Manual do usuário |
| `ROADMAP.md` | Roadmap geral |

---

## 9. Conclusões e Prioridades

### Forças
1. ✅ 31 módulos completos cobrindo todo o ciclo operacional
2. ✅ Qualidade crescente entre gerações de código (Legado → Moderno)
3. ✅ Documentação interna abrangente
4. ✅ Sistema de eventos funcional e extensível
5. ✅ Utils robustos (Validator, Sanitizer, Escape, Input)

### Prioridades de Melhoria

| Prioridade | Item | Esforço | Impacto |
|---|---|---|---|
| 1 | Criar BaseController (eliminar duplicação) | Médio | Alto |
| 2 | Modernizar models legados (array em vez de PDOStatement) | Médio | Alto |
| 3 | Decompor Financial.php (1700+ linhas) | Alto | Alto |
| 4 | Ativar listeners de eventos | Médio | Médio |
| 5 | Adicionar paginação nos módulos Users/Categories | Baixo | Médio |
| 6 | Remover arquivos .bak | Baixo | Baixo |
| 7 | Preparar i18n (helper de tradução) | Alto | Médio |
| 8 | Extrair scripts inline de views | Médio | Médio |
