---
name: auditoria-geral
description: "Auditoria completa do sistema Akti. Use when: auditar sistema, gerar relatório de auditoria, auditoria geral, análise completa do código, audit report, revisão de segurança/arquitetura/frontend/banco/API/testes. Gera documentação versionada em docs/geral/v<N> com auditorias detalhadas, roadmaps de correção e roadmap de funcionalidades."
argument-hint: "Versão opcional (ex: v3). Se omitido, auto-detecta a próxima versão."
---

# Auditoria Geral do Sistema Akti

Skill para gerar uma auditoria completa e versionada de todo o sistema, cobrindo segurança, arquitetura, banco de dados, frontend, API/integrações, testes/qualidade e implementações. Gera também roadmaps de correção e novas funcionalidades.

## Quando Usar

- Revisão periódica do sistema (mensal/trimestral)
- Antes de releases importantes
- Após grandes refatorações ou adição de módulos
- Quando solicitado "auditoria", "análise completa", "revisão geral"

## Saída Esperada

Pasta `docs/geral/v<N>/` contendo **13 arquivos**:

| # | Arquivo | Conteúdo |
|---|---------|----------|
| 00 | `README.md` | Índice, resumo executivo, métricas, como usar |
| 01 | `01_AUDITORIA_SEGURANCA.md` | CSRF, XSS, SQLi, uploads, headers, auth, API |
| 02 | `02_AUDITORIA_ARQUITETURA.md` | MVC, PSR-4, roteamento, eventos, multi-tenant |
| 03 | `03_AUDITORIA_BANCO_DADOS.md` | Models, queries, transações, migrations, schemas |
| 04 | `04_AUDITORIA_FRONTEND.md` | JS, CSS, Design System, dark mode, responsividade, PWA |
| 05 | `05_AUDITORIA_API_INTEGRACOES.md` | API Node.js, gateways, NF-e, webhooks |
| 06 | `06_AUDITORIA_TESTES_QUALIDADE.md` | PHPUnit, cobertura, PHPStan, CI/CD |
| 07 | `07_AUDITORIA_IMPLEMENTACOES.md` | Módulos, CRUD compliance, code smells, i18n |
| 08 | `08_ROADMAP_CORRECOES_SEGURANCA.md` | Itens SEC-XXX priorizados |
| 09 | `09_ROADMAP_CORRECOES_ARQUITETURA.md` | Itens ARCH-XXX priorizados |
| 10 | `10_ROADMAP_CORRECOES_FRONTEND.md` | Itens FE-XXX priorizados |
| 11 | `11_ROADMAP_CORRECOES_BANCO_TESTES.md` | Itens DB-XXX e TEST-XXX |
| 12 | `12_ROADMAP_NOVAS_FUNCIONALIDADES.md` | Itens FEAT-XXX em 4 fases |

## Procedimento

### Fase 0 — Preparação e Versionamento

1. **Detectar versão:** Listar subpastas de `docs/geral/` para encontrar a última versão (v1, v2, ...). A nova versão é `v<último+1>`, a menos que o usuário especifique.
2. **Criar pasta:** `docs/geral/v<N>/`
3. **Carregar versão anterior:** Ler o `README.md` da versão anterior para obter métricas de referência (notas, contagem de issues, testes). Isso será usado na seção de evolução comparativa.
4. **Carregar referências da skill:** Ler os templates e checklists em [./references/](./references/) para guiar cada documento.

### Fase 1 — Coleta de Dados (Read-Only)

Usar subagentes `Explore` em paralelo para máxima eficiência. **Não editar nenhum arquivo nesta fase.**

#### 1.1 — Inventário do Sistema
- Contar: controllers, models, services, views, JS files, CSS files, test files, test methods
- Listar todas as rotas (`app/config/routes.php`)
- Mapear middleware pipeline (`index.php`)
- Checar `composer.json` para dependências e autoload

#### 1.2 — Segurança
Consultar o checklist completo em [./references/checklist-seguranca.md](./references/checklist-seguranca.md).

Resumo dos pontos a verificar:
- CSRF: token generation, validation, grace period, middleware, AJAX setup
- XSS: uso de `e()`, `Escape::html()`, `htmlspecialchars()` nas views; `innerHTML` sem DOMPurify nos JS
- SQL Injection: buscar interpolação de variáveis em queries (`"$var"`, `{$var}` dentro de SQL)
- Information Disclosure: `$e->getMessage()` em JSON responses
- File Upload: MIME type validation, magic bytes, extensão, diretório fora do webroot
- Session: flags `httponly`, `samesite`, `secure`, `strict_mode`
- HTTP Headers: CSP, X-Frame-Options, HSTS, X-Content-Type-Options
- Rate Limiting: LoginAttempt, RateLimitMiddleware
- Auth: password hashing (bcrypt/argon2), must_change_password
- Open Redirects: `header('Location: ' . $userInput)` sem validação
- API Security: JWT validation, token expiry, CORS config
- Dependencies: `composer audit`, versões desatualizadas

#### 1.3 — Arquitetura
Consultar [./references/checklist-arquitetura.md](./references/checklist-arquitetura.md).

Resumo:
- Separação MVC: controllers sem SQL, models sem echo/HTML, views sem lógica de BD
- PSR-4: namespace declarations, autoloader, `use` statements
- Roteamento: mapa declarativo, actions padronizadas
- Eventos: EventDispatcher, listeners registrados
- Multi-tenant: TenantManager, isolamento por subdomain, database switching
- Patterns: Strategy (gateways), Observer (eventos), Singleton (Database)
- Métricas: cyclomatic complexity, LOC por controller/model

#### 1.4 — Banco de Dados
Consultar [./references/checklist-banco.md](./references/checklist-banco.md).

Resumo:
- PDO config: `ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false`
- Models: inventariar todos, verificar métodos CRUD, retornos consistentes
- Prepared statements: buscar `query()` sem bind
- Paginação: `LIMIT`/`OFFSET` parametrizados
- Transações: `beginTransaction()`/`commit()`/`rollback()` em operações compostas
- Migrations: pasta `sql/`, naming convention, idempotência
- Multi-tenant: todas as queries filtram por `tenant_id`

#### 1.5 — Frontend
Consultar [./references/checklist-frontend.md](./references/checklist-frontend.md).

Resumo:
- JS files: inventariar, verificar segurança (innerHTML, eval, document.write)
- CSS files: inventariar, verificar design-system, dark mode coverage, responsividade
- Design System: variáveis CSS, componentes reutilizáveis, consistência
- Dark Mode: `[data-theme="dark"]` overrides, variáveis com/sem override, inline styles hardcoded
- Acessibilidade: ARIA labels, roles, focus management, contraste
- PWA: manifest.json, service worker, offline capability
- Performance: CDN vs local, bundle/minify, lazy loading

#### 1.6 — API e Integrações
Consultar [./references/checklist-api.md](./references/checklist-api.md).

Resumo:
- API Node.js: Express routes, Sequelize models, JWT, CORS, rate limiting
- Payment Gateways: strategy pattern, tokenização, webhooks, PCI compliance
- NF-e/NFC-e: services inventariados, certificado digital, SEFAZ communication
- Webhooks: validação de origem, idempotência, retry

#### 1.7 — Testes e Qualidade
Consultar [./references/checklist-testes.md](./references/checklist-testes.md).

Resumo:
- PHPUnit: suites configuradas, bootstrap, total tests/assertions
- Cobertura: quais módulos têm testes, quais não têm
- PHPStan: nível configurado, erros pendentes
- CI/CD: pipeline existente, stages, deploy automatizado
- Linting: ESLint para JS, PHP_CodeSniffer

#### 1.8 — Implementações e Padrões
- Inventariar todos os módulos/páginas
- Verificar compliance CRUD (cada módulo tem index/create/store/edit/update/delete?)
- Code smells: arquivos >500 linhas, métodos >100 linhas, controllers com SQL
- Documentação: README, MANUAL, CHANGELOG, docs/ atualizados
- i18n: uso de `Akti\Lang`, cobertura de tradução

### Fase 2 — Análise e Redação

Para cada documento (01-07), seguir esta estrutura padrão:

```markdown
# [Título] — Akti v<N>

> **Data da Auditoria:** DD/MM/YYYY
> **Escopo:** [descrição do escopo]
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo
[Tabela com notas por aspecto + parágrafos de contexto]

## 2-N. Seções de Análise
[Uma seção por tópico, com:]
- Status geral (✅/⚠️/❌)
- Evidências com arquivo:linha
- Código vulnerável/problemático citado
- Impacto e risco
- Correção sugerida com código

## N+1. Evolução vs. Versão Anterior
[Tabela comparativa com métricas da versão anterior]
[Issues resolvidas desde a última auditoria]
[Novas issues encontradas]
```

### Fase 3 — Roadmaps de Correção (08-11)

Para cada roadmap:

```markdown
# Roadmap de Correções — [Categoria] — Akti v<N>

> ## Por que este Roadmap existe?
> [Justificativa contextual]

---

## Prioridade CRÍTICA (Corrigir Imediatamente)
### [ID]-001: [Título]
- **Arquivo:** [caminho:linha]
- **Problema:** [descrição]
- **Risco:** [impacto]
- **Correção:** [código sugerido]
- **Teste:** [como validar]
- **Status:** ⬜ Pendente | ✅ Corrigido (vN-1)

## Prioridade ALTA | MÉDIA | BAIXA
[mesmo formato]
```

**IDs por categoria:**
- `SEC-XXX` — Segurança
- `ARCH-XXX` — Arquitetura
- `FE-XXX` — Frontend
- `DB-XXX` — Banco de Dados
- `TEST-XXX` — Testes
- `FEAT-XXX` — Funcionalidades

**Comparação com versão anterior:**
- Marcar itens que foram `✅ Corrigido (v<N-1>)` com evidência
- Manter itens pendentes com `⬜ Pendente`
- Adicionar novos itens identificados nesta versão

### Fase 4 — Roadmap de Funcionalidades (12)

Estruturar em 4 fases:
1. **Foundation** — Features de infraestrutura que habilitam outras
2. **Core Business** — Features de valor direto ao operador
3. **Advanced** — Features diferenciadoras no mercado
4. **Innovation** — Features exploratórias de longo prazo

Cada feature:
```markdown
### FEAT-XXX: [Nome]
- **Descrição:** [o que faz]
- **Benefício:** [por que importa]
- **Complexidade:** Alta | Média | Baixa
- **Dependências:** [FEAT-YYY, SEC-ZZZ, etc.]
- **Implementação sugerida:** [abordagem técnica]
- **Escopo:** [checklist de sub-tarefas]
- **Status:** ⬜ Planejado | 🔄 Em andamento | ✅ Implementado
```

### Fase 5 — README e Finalização

1. Gerar `README.md` com:
   - Métricas consolidadas (total de arquivos analisados, controllers, models, etc.)
   - Índice linkado para todos os documentos
   - Tabela de severidade cross-documento
   - Seção "Sprint Imediato" com top 5-10 itens críticos
   - Seção "Evolução" comparando com versão anterior
   - Guia "Como usar esta documentação" por persona (Dev, Tech Lead, QA)

2. Validar que todos os 13 arquivos existem e estão linkados

3. Reportar ao usuário:
   - Versão gerada
   - Total de issues encontradas por severidade
   - Top 3 itens mais críticos
   - Comparação resumida com versão anterior

## Regras e Restrições

- **NÃO modificar código-fonte** — a skill é apenas de análise e documentação
- **NÃO executar código** — usar apenas análise estática (grep, read_file, semantic_search)
- **NÃO criar arquivos .sql** — conforme regra do copilot-instructions.md, testes não devem verificar .sql
- **Citar sempre arquivo:linha** — toda constatação deve ter evidência rastreável
- **Usar subagentes Explore** para coleta de dados paralela quando possível
- **Preservar IDs** — manter mesmos IDs (SEC-001, ARCH-001...) entre versões para tracking
- **Marcar evolução** — itens corrigidos desde a última versão devem ser marcados com ✅
