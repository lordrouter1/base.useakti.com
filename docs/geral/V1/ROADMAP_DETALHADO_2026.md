# 🗺️ Roadmap Detalhado de Correções e Melhorias — Akti

**Data:** 30/03/2026  
**Referência:** Auditoria Geral 2026, Relatório de Segurança, Melhorias e Correções, Inventário do Sistema  
**Classificação:** Interno — Planejamento Estratégico  
**Foco:** Correção de erros, segurança, estabilidade e UX minimalista avançada

---

## 📑 Índice

1. [Filosofia e Princípios do Roadmap](#1-filosofia-e-princípios-do-roadmap)
2. [Fase 1 — Blindagem e Estabilidade](#2-fase-1--blindagem-e-estabilidade)
3. [Fase 2 — Arquitetura e Fundações](#3-fase-2--arquitetura-e-fundações)
4. [Fase 3 — Qualidade e Confiabilidade](#4-fase-3--qualidade-e-confiabilidade)
5. [Fase 4 — Performance e Otimização](#5-fase-4--performance-e-otimização)
6. [Fase 5 — UX Minimalista Avançada](#6-fase-5--ux-minimalista-avançada)
7. [Fase 6 — Expansão Inteligente](#7-fase-6--expansão-inteligente)
8. [Fase 7 — Maturidade Operacional](#8-fase-7--maturidade-operacional)
9. [Dependências entre Fases](#9-dependências-entre-fases)
10. [Métricas de Sucesso por Fase](#10-métricas-de-sucesso-por-fase)
11. [Princípios de UX Minimalista Aplicados](#11-princípios-de-ux-minimalista-aplicados)
12. [Gestão de Riscos](#12-gestão-de-riscos)

---

## 1. Filosofia e Princípios do Roadmap

### 1.1 Filosofia Geral

Este roadmap segue uma abordagem **inside-out**: corrigir primeiro o que está invisível para o usuário mas é crítico (segurança, dados, estabilidade), depois melhorar o que o usuário vê e sente (performance, UX). Cada fase é construída sobre a anterior — não avançamos para UX avançada enquanto o sistema não estiver blindado e estável.

### 1.2 Princípios Orientadores

| Princípio | Descrição |
|-----------|-----------|
| **Segurança Primeiro** | Nenhuma feature nova deve ser implementada sobre uma base vulnerável |
| **Zero Regressão** | Cada mudança deve ser coberta por teste antes ou imediatamente após |
| **Minimalismo Funcional** | Cada tela mostra exatamente o que o usuário precisa, nada mais |
| **Consistência Radical** | Um padrão adotado se aplica em 100% dos casos, sem exceções |
| **Progressividade** | Entregar valor incremental — cada item completo da fase é deployável |

### 1.3 Critérios de "Pronto" (Definition of Done)

Cada item deste roadmap só é considerado completo quando:

- [x] Código implementado e revisado
- [x] Sem erros no PHPStan nível 5+
- [x] Testes unitários escritos (quando aplicável)
- [x] Documentação atualizada (PHPDoc + docs/ se necessário)
- [x] Testado manualmente em ambiente de desenvolvimento
- [x] Sem regressão nos testes existentes

---

## 2. Fase 1 — Blindagem e Estabilidade

> **Objetivo:** Eliminar todas as vulnerabilidades críticas e altas. O sistema deve ser seguro antes de qualquer outra melhoria.  
> **Pré-requisito:** Nenhum  
> **Complexidade:** Baixa a Média  
> **Risco se não feito:** 🔴 Comprometimento de dados, acesso não autorizado, exposição de infraestrutura

### 2.1 Eliminar Credenciais Hardcoded

**Origem:** SEV-01 (CVSS 9.1) — AUDITORIA item C1

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Senha do banco `kP9!vR2@mX6#zL5$` em texto plano como fallback em `tenant.php` (L72, L85) e `IpGuard.php` (L57) |
| **Risco** | Exposição do repositório = acesso total ao banco de dados de todos os tenants |
| **Arquivos** | `app/config/tenant.php`, `app/models/IpGuard.php` |
| **Solução** | Remover todo fallback hardcoded. Lançar `RuntimeException` se variável de ambiente não estiver definida. Instalar `vlucas/phpdotenv` para gestão de `.env` |

**Etapas de implementação:**

1. Instalar `vlucas/phpdotenv` via Composer
2. Criar `.env.example` com todas as variáveis necessárias (sem valores reais)
3. Configurar carregamento do `.env` no `index.php` (antes de qualquer acesso a `getenv()`)
4. Em `tenant.php`: substituir fallback de senha por exceção:
   ```php
   'password' => getenv('AKTI_DB_PASS') ?: (function() {
       throw new \RuntimeException('AKTI_DB_PASS não configurada.');
   })(),
   ```
5. Em `IpGuard.php`: mesma abordagem — exceção em vez de fallback
6. Garantir que `.env` está no `.gitignore` (já está, verificar)
7. Documentar todas as variáveis obrigatórias em `docs/DEPLOY.md`

**Validação:**
- Remover `.env` temporariamente → sistema deve lançar exceção clara
- Configurar `.env` corretamente → sistema funciona normalmente
- Repositório não contém nenhuma senha em texto plano (`grep -r "kP9\!" .`)

---

### 2.2 Corrigir Exposição de Erro do Banco de Dados

**Origem:** SEV-02 (CVSS 7.5) — AUDITORIA item C2

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | `Database::getConnection()` faz `echo` da mensagem PDOException, expondo hostname, porta e nome do banco |
| **Risco** | Information disclosure para atacantes |
| **Arquivo** | `app/config/database.php` (L41) |
| **Solução** | Substituir `echo` por `error_log()` + `throw RuntimeException` com mensagem genérica |

**Etapas de implementação:**

1. No bloco `catch(PDOException)` de `getConnection()`:
   - Remover `echo 'Erro na conexão: ' . $exception->getMessage();`
   - Adicionar `error_log('[Database] Connection failed: ' . $exception->getMessage());`
   - Adicionar `throw new \RuntimeException('Falha na conexão com o banco de dados.');`
2. Criar página de erro genérica em `app/views/errors/500.php`
3. Garantir que o `set_exception_handler` no `index.php` capture essa exceção e exiba a página de erro

**Validação:**
- Forçar credencial errada → deve logar o erro real e mostrar página genérica ao usuário
- Verificar que nenhum detalhe interno aparece na resposta HTTP
- Verificar que o erro real está em `storage/logs/` ou `error_log`

---

### 2.3 Prevenir Session Fixation

**Origem:** SEV-03 (CVSS 7.1) — AUDITORIA item C3

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Após login bem-sucedido, o session ID não é regenerado |
| **Risco** | Atacante que conhece o session ID pré-login pode sequestrar a sessão |
| **Arquivo** | `app/controllers/UserController.php` (método de login) |
| **Solução** | Chamar `session_regenerate_id(true)` imediatamente após autenticação bem-sucedida |

**Etapas de implementação:**

1. No `UserController`, no método que processa login (após `$user->login()` retornar true):
   - Adicionar `session_regenerate_id(true);` ANTES de definir `$_SESSION['user_id']`
2. Considerar também regenerar na troca de privilégios (ex: admin impersonando outro usuário)

**Validação:**
- Capturar cookie `AKTI_SID` antes do login → fazer login → verificar que o cookie mudou
- Testar que a sessão anterior é invalidada (destruída no servidor)

---

### 2.4 Adicionar Security Headers HTTP

**Origem:** SEV-04 (CVSS 5.3) — AUDITORIA item C4

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Ausência total de headers de segurança HTTP |
| **Risco** | Clickjacking, MIME sniffing, referrer leaks |
| **Arquivo** | `index.php` (ou novo middleware) |
| **Solução** | Criar `SecurityHeadersMiddleware` ou adicionar no `index.php` |

**Etapas de implementação:**

1. Criar `app/middleware/SecurityHeadersMiddleware.php`:
   ```
   Namespace: Akti\Middleware
   Método: handle()
   Headers a enviar:
     - X-Content-Type-Options: nosniff
     - X-Frame-Options: SAMEORIGIN
     - Referrer-Policy: strict-origin-when-cross-origin
     - Permissions-Policy: camera=(), microphone=(), geolocation=()
     - X-XSS-Protection: 0  (desabilitado — obsoleto, evitar falsos positivos)
     - Strict-Transport-Security: max-age=31536000; includeSubDomains (apenas se HTTPS)
   ```
2. Registrar o middleware no fluxo de request do `index.php`
3. Preparar a base para futura Content Security Policy (CSP) — não ativar agora pois requer auditoria de todos os scripts inline e CDNs

**Validação:**
- Inspecionar response headers no DevTools → todos os headers devem estar presentes
- Testar em https://securityheaders.com/ → nota deve subir de F para B+ ou A
- Verificar que nenhuma funcionalidade quebrou (especialmente iframes internos, se houver)

---

### 2.5 Remover Arquivos de Backup e Debug

**Origem:** SEV-05 (CVSS 5.0), SEV-06 (CVSS 6.5) — AUDITORIA item C5

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Arquivos `.bak`, `.new` acessíveis via web; diretório `scripts/` com ferramentas de debug expostas |
| **Risco** | Exposição de código legado com possíveis vulnerabilidades; ferramentas de debug podem alterar dados |
| **Arquivos** | `app/controllers/FinancialController.php.bak`, `.php.new`, `scripts/*.php` |
| **Solução** | Remover backups; proteger `scripts/` |

**Etapas de implementação:**

1. Excluir arquivos:
   - `app/controllers/FinancialController.php.bak`
   - `app/controllers/FinancialController.php.new`
2. Adicionar ao `.gitignore`:
   ```
   *.bak
   *.new
   *.orig
   scripts/
   ```
3. Criar `.htaccess` em `scripts/` (para Apache):
   ```apache
   Deny from all
   ```
4. Criar regra Nginx equivalente na documentação de deploy:
   ```nginx
   location /scripts/ { deny all; return 403; }
   ```
5. Adicionar regra no `.htaccess` raiz para bloquear extensões perigosas:
   ```apache
   <FilesMatch "\.(bak|new|orig|sql|log|env)$">
       Deny from all
   </FilesMatch>
   ```

**Validação:**
- Tentar acessar `https://dominio/scripts/diagnostico_completo.php` → deve retornar 403
- Tentar acessar `https://dominio/app/controllers/FinancialController.php.bak` → deve retornar 404
- `git status` não mostra os arquivos removidos como untracked

---

### 2.6 Proteger Credenciais de Teste

**Origem:** AUDITORIA item A7 — Credenciais no phpunit.xml

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | `phpunit.xml` contém `AKTI_TEST_USER_PASSWORD=admin123` e está versionado |
| **Risco** | Exposição de credencial padrão |
| **Arquivo** | `phpunit.xml` |
| **Solução** | Renomear para `phpunit.xml.dist` no repositório; `.gitignore` no `phpunit.xml` local |

**Etapas de implementação:**

1. Copiar `phpunit.xml` → `phpunit.xml.dist` (sem credenciais reais, usar placeholders)
2. Adicionar `phpunit.xml` ao `.gitignore`
3. Documentar no README que o desenvolvedor deve copiar `.dist` e configurar

---

### 2.7 Remover Sanitização Dupla no Model

**Origem:** SEV-08 (CVSS 3.1) — Sanitização dupla corrompendo dados

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | `User.php` aplica `htmlspecialchars(strip_tags())` ao salvar, corrompendo nomes como "O'Brien" → "O&#039;Brien" no banco |
| **Risco** | Corrupção silenciosa de dados |
| **Arquivo** | `app/models/User.php` (L121-122) |
| **Solução** | Remover sanitização HTML do Model. A sanitização de ENTRADA fica no Controller (via `Input`), e o ESCAPE de saída fica na View (via `e()`) |

**Etapas de implementação:**

1. No `User::create()` e `User::update()`:
   - Remover `$this->name = htmlspecialchars(strip_tags($this->name));`
   - Remover `$this->email = htmlspecialchars(strip_tags($this->email));`
2. Verificar se o Controller já usa `Input::post('name', 'string')` — que já sanitiza a entrada
3. Verificar que as Views de listagem de usuários usam `e($user['name'])` para escape de saída
4. Auditar outros Models para o mesmo padrão (Customer, Product, Order)

**Validação:**
- Criar usuário com nome "O'Brien & Co." → verificar que o banco armazena exatamente "O'Brien & Co."
- Verificar que a listagem de usuários exibe corretamente sem XSS
- Verificar que nomes existentes corrompidos são corrigidos (se houver)

---

### 2.8 Fortalecer Política de Senhas

**Origem:** SEV-07 (CVSS 4.3)

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Apenas 6 caracteres mínimos, sem exigência de complexidade |
| **Arquivo** | `app/controllers/UserController.php` (L81), `app/utils/Validator.php` |
| **Solução** | Mínimo 8 caracteres, exigir pelo menos 1 maiúscula e 1 número |

**Etapas de implementação:**

1. No `Validator.php`, adicionar método `passwordStrength()`:
   - Mínimo 8 caracteres
   - Pelo menos 1 letra maiúscula
   - Pelo menos 1 número
   - Mensagem clara em PT-BR
2. No `UserController::store()` e `UserController::update()` (onde altera senha):
   - Substituir `minLength('password', $password, 6)` pelo novo `passwordStrength()`
3. Atualizar view de criação/edição de usuários com indicador visual de força da senha (JS)
4. Atualizar view de perfil (`profile`) para mesma regra na troca de senha
5. NÃO invalidar senhas existentes — aplicar regra apenas em novas senhas e trocas

**Validação:**
- Tentar senha "abc" → erro de tamanho
- Tentar senha "abcdefgh" → erro de complexidade  
- Tentar senha "Abcdef1!" → sucesso
- Verificar que login com senha antiga ainda funciona

---

## 3. Fase 2 — Arquitetura e Fundações

> **Objetivo:** Resolver problemas estruturais que geram bugs, desperdício de recursos e dificultam a manutenção.  
> **Pré-requisito:** Fase 1 completa  
> **Complexidade:** Média a Alta  
> **Risco se não feito:** ⚠️ Instabilidade sob carga, dificuldade de manutenção, bugs difíceis de rastrear

### 3.1 Implementar Singleton/Connection Pool para Database

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Cada `new Database()` cria uma nova conexão PDO. Requests complexos (pipeline detail) criam 10+ conexões |
| **Impacto** | Desperdício de recursos do MySQL, possível `Too many connections` sob carga |
| **Arquivos** | `app/config/database.php`, todos os controllers |
| **Solução** | Implementar Singleton com cache por tenant (cada tenant tem sua própria conexão, mas reutilizada) |

**Etapas de implementação:**

1. Refatorar `Database` para singleton com cache por DSN:
   - Propriedade estática `$instances = []` indexada pelo DSN do tenant
   - Método `getInstance(?string $tenantDb = null): PDO`
   - Manter `getConnection()` como wrapper para compatibilidade
2. Atualizar controllers gradualmente (sem urgência de mudar todos de uma vez)
3. Implementar `Database::resetInstance()` para uso em testes

**Validação:**
- Log: contar quantas conexões PDO são criadas por request antes vs depois
- Teste de carga: verificar que não há `Too many connections`
- Testes unitários: verificar que `getInstance()` retorna a mesma instância

---

### 3.2 Mover Lógica SQL do Header para Service

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | `header.php` executa 70+ linhas de queries SQL (pedidos atrasados, permissões, contadores) — viola MVC |
| **Impacto** | Toda página fica lenta; acoplamento da view com o banco; impossível testar |
| **Arquivos** | `app/views/layout/header.php` (L102-170) |
| **Solução** | Criar `HeaderDataService` e injetar dados via variáveis antes do include do header |

**Etapas de implementação:**

1. Criar `app/services/HeaderDataService.php`:
   - `getDelayedOrdersCount(): int`
   - `getDelayedProductsList(): array`
   - `getUserMenuPermissions(int $userId, int $groupId): array`
   - `getAlertBadges(): array`
2. No `index.php` (ou em um middleware `LayoutMiddleware`):
   - Instanciar `HeaderDataService` uma vez
   - Definir `$headerData = $service->getAllHeaderData()`
   - Passar via variável global ou armazenar em `$_SESSION['_header_cache']`
3. Refatorar `header.php` para apenas ler de `$headerData` — zero SQL na view
4. Implementar cache de 2-5 minutos para `getDelayedOrdersCount()` (via sessão)

**Validação:**
- `header.php` não deve conter nenhuma keyword SQL (`SELECT`, `FROM`, `JOIN`, `query(`, `prepare(`)
- Tempo de carregamento de qualquer página deve diminuir
- Badges de alerta continuam funcionando corretamente

---

### 3.3 Mover ALTER TABLEs do PHP para Migrations

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | `Stock.php` executa `ALTER TABLE` diretamente no PHP (L793-804) |
| **Risco** | Alteração de schema em runtime; pode falhar em produção; impossível de rastrear |
| **Arquivo** | `app/models/Stock.php` |
| **Solução** | Mover para arquivo SQL na pasta `/sql` e remover do PHP |

**Etapas de implementação:**

1. Identificar todos os `ALTER TABLE` e `CREATE TABLE IF NOT EXISTS` dentro de Models
2. Para cada um, criar arquivo SQL equivalente na pasta `/sql`
3. Remover o código PHP que executa DDL
4. Se o Model verificava a existência da coluna antes de usar, manter a verificação mas sem o `ALTER`

**Validação:**
- `grep -r "ALTER TABLE" app/models/` → zero resultados
- `grep -r "CREATE TABLE" app/models/` → zero resultados (exceto em comentários)
- Funcionalidades de estoque continuam funcionando

---

### 3.4 Implementar Sistema de Migrations Automatizado

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Migrations são manuais — nenhum controle de quais foram aplicadas |
| **Solução** | Tabela `applied_migrations` + script CLI `migrate.php` |

**Etapas de implementação:**

1. Criar tabela `applied_migrations`:
   ```
   id INT AUTO_INCREMENT PK
   filename VARCHAR(255) NOT NULL UNIQUE
   applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
   checksum VARCHAR(64) — hash MD5 do arquivo para detectar alterações
   ```
2. Criar `scripts/migrate.php`:
   - Lista arquivos em `/sql` ordenados por nome (prefixo YYYYMMDD garante ordem)
   - Compara com `applied_migrations`
   - Executa pendentes em transação
   - Registra na tabela após sucesso
   - Flag `--dry-run` para listar sem executar
   - Flag `--status` para mostrar quais foram aplicadas
3. Documentar workflow no README e `docs/DEPLOY.md`

**Validação:**
- Executar `php scripts/migrate.php --status` → lista todas as migrations e status
- Executar `php scripts/migrate.php --dry-run` → mostra pendentes sem aplicar
- Re-executar → "Nenhuma migration pendente"

---

### 3.5 Padronizar Tratamento de Erros

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Controllers não têm try-catch consistente; erros podem vazar para o usuário |
| **Solução** | Criar middleware de erro + páginas de erro padronizadas |

**Etapas de implementação:**

1. Criar `app/views/errors/404.php` — página 404 com design do sistema
2. Criar `app/views/errors/500.php` — página de erro genérica
3. Criar `app/views/errors/403.php` — página de acesso negado
4. Refatorar `set_exception_handler` no `index.php`:
   - Em modo desenvolvimento (`getenv('APP_ENV') === 'development'`): mostrar stack trace
   - Em modo produção: mostrar página genérica + logar erro
5. Garantir que erros de PDO nunca mostram detalhes ao usuário
6. Padronizar o formato de log: `[YYYY-MM-DD HH:mm:ss][LEVEL] Mensagem {contexto JSON}`

**Validação:**
- Forçar um erro 500 em produção → deve exibir página genérica sem detalhes técnicos
- Forçar um erro 500 em desenvolvimento → deve exibir stack trace útil
- Verificar que todos os erros são logados em `storage/logs/`

---

### 3.6 Refatorar Controllers Monolíticos

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Controllers excessivamente grandes dificultam manutenção e testes |
| **Alvos** | `CustomerController` (2398 linhas), `ProductController` (1194 linhas), `PipelineController` (948+ linhas) |
| **Solução** | Extrair lógica de negócio para Services e lógica de importação/exportação para services dedicados |

**Etapas de implementação (para cada controller):**

1. **CustomerController** (2398 → alvo <500 linhas):
   - Extrair para `app/services/CustomerImportService.php` — toda lógica de importação Excel/CSV
   - Extrair para `app/services/CustomerExportService.php` — toda lógica de exportação
   - Extrair para `app/services/CustomerSearchService.php` — lógica de busca avançada
   - Controller fica apenas com: receber request → chamar service → retornar view

2. **ProductController** (1194 → alvo <400 linhas):
   - Extrair para `app/services/ProductImportService.php`
   - Extrair para `app/services/ProductGradeService.php`
   - Manter no controller apenas a orquestração

3. **PipelineController** (948+ → alvo <400 linhas):
   - Extrair para `app/services/PipelineService.php` — lógica de movimentação e regras de etapas
   - Extrair para `app/services/PipelineAlertService.php` — lógica de alertas e atrasos

**Regra geral:** O controller não deve ter mais de 20 linhas por método. Se tiver, a lógica deve ir para um Service.

**Validação:**
- Todas as funcionalidades existentes continuam funcionando
- Nenhum controller > 500 linhas
- Cada Service é testável isoladamente (sem dependência de `$_POST`, `$_GET`, `$_SESSION`)

---

## 4. Fase 3 — Qualidade e Confiabilidade

> **Objetivo:** Elevar a cobertura de testes de ~8% para >40%. Implementar análise estática. Garantir que mudanças futuras não introduzam regressões.  
> **Pré-requisito:** Fase 2 completa (Services extraídos são testáveis)  
> **Complexidade:** Alta (esforço contínuo)  
> **Risco se não feito:** ⚠️ Regressões silenciosas, bugs em produção, medo de refatorar

### 3.1 Estrutura de Testes — Organização

**Estrutura alvo:**
```
tests/
├── bootstrap.php          (configuração do ambiente de teste)
├── TestCase.php            (classe base com helpers)
├── Unit/
│   ├── Utils/
│   │   ├── ValidatorTest.php
│   │   ├── SanitizerTest.php
│   │   ├── InputTest.php
│   │   └── EscapeTest.php
│   ├── Core/
│   │   ├── RouterTest.php
│   │   ├── SecurityTest.php
│   │   └── EventDispatcherTest.php
│   ├── Models/
│   │   ├── UserTest.php
│   │   ├── CustomerTest.php
│   │   ├── OrderTest.php
│   │   └── FinancialTest.php
│   ├── Middleware/
│   │   ├── CsrfMiddlewareTest.php
│   │   └── RateLimitMiddlewareTest.php
│   └── Services/
│       ├── HeaderDataServiceTest.php
│       ├── CustomerImportServiceTest.php
│       └── PipelineServiceTest.php
├── Integration/
│   └── Controllers/
│       ├── CustomerControllerTest.php
│       └── OrderControllerTest.php
├── Security/
│   ├── CsrfBypassTest.php
│   ├── XssInjectionTest.php
│   └── SessionFixationTest.php
└── Pages/
    └── (testes HTTP existentes)
```

### 3.2 Prioridade de Testes — Camada por Camada

**Onda 1 — Utils e Infraestrutura (maior ROI, menor esforço):**

| Componente | Testes | Justificativa |
|------------|--------|---------------|
| `Validator` | Todas as regras (required, minLength, email, custom, etc.) | Usado em 100% dos forms |
| `Sanitizer` | Todos os tipos (string, int, email, enum, date, etc.) | Fundação de segurança |
| `Input` | `::post()`, `::get()`, `::file()`, com e sem tipo | Wrapper universal |
| `Escape` | `e()`, `eAttr()`, `eJs()`, `eUrl()`, `eNum()` | Prevenção XSS |
| `EventDispatcher` | `listen()`, `dispatch()`, listener com erro, prioridade | Desacoplamento |
| `Router` | Resolução de rota, 404, rota pública, redirect | Core do sistema |

**Onda 2 — Middleware e Segurança:**

| Componente | Testes | Justificativa |
|------------|--------|---------------|
| `CsrfMiddleware` | Token válido, inválido, expirado, grace period, rota isenta | Proteção CSRF |
| `RateLimitMiddleware` | Dentro do limite, excedido, reset, por sessão vs DB | Proteção DDoS |
| `SecurityHeadersMiddleware` | Headers presentes na resposta | Novo middleware da Fase 1 |

**Onda 3 — Models e Services:**

| Componente | Testes | Justificativa |
|------------|--------|---------------|
| `User` | Login, permissões, CRUD, checkPermission, hashSenha | Autenticação |
| `Customer` | CRUD completo, busca, duplicatas | Módulo core |
| `Order` | CRUD, mudança de status, vínculo com pipeline | Módulo core |
| `Financial` | Cálculos, parcelas, summary | Impacto financeiro |
| `HeaderDataService` | Contagem de atrasados, permissões | Performance |

**Onda 4 — Integração:**

| Componente | Testes | Justificativa |
|------------|--------|---------------|
| Fluxo Login → Dashboard | Request HTTP, sessão, redirect | Fluxo crítico |
| Fluxo Pedido → Pipeline | Criar pedido, mover etapas | Fluxo core |
| Importação Excel | Upload, parsing, criação em lote | Feature complexa |

### 3.3 Implementar Análise Estática (PHPStan)

**Etapas:**

1. Instalar: `composer require --dev phpstan/phpstan`
2. Criar `phpstan.neon` com nível 5 e paths `app/`
3. Rodar e corrigir os erros mais críticos (tipos indefinidos, propriedades inexistentes)
4. Adicionar ao CI (quando implementado)
5. Meta: nível 5 sem erros → depois subir para nível 6

### 3.4 Implementar Code Style (PHP-CS-Fixer)

**Etapas:**

1. Instalar: `composer require --dev friendsofphp/php-cs-fixer`
2. Criar `.php-cs-fixer.php` com regras PSR-12
3. Rodar `fix --dry-run` para ver o impacto
4. Aplicar correções de estilo em um commit único
5. Adicionar ao CI como check

### 3.5 Configurar CI/CD (GitHub Actions)

**Pipeline mínimo:**

1. **Trigger:** Push e Pull Request para branches main/develop
2. **Jobs:**
   - **Lint:** PHP-CS-Fixer (--dry-run)
   - **Static Analysis:** PHPStan nível 5
   - **Unit Tests:** PHPUnit (apenas Unit/ e Security/)
   - **Notificação:** Status badge no README

**Evolução futura:**
- Adicionar job de deploy para staging
- Adicionar testes de integração com banco MySQL em container
- Adicionar `npm audit` para a API Node.js

---

## 5. Fase 4 — Performance e Otimização

> **Objetivo:** Reduzir tempo de carregamento, eliminar gargalos de banco e otimizar a experiência percebida pelo usuário.  
> **Pré-requisito:** Fase 1-2 completas (singleton do Database, HeaderDataService)  
> **Complexidade:** Média  
> **Risco se não feito:** ⚠️ Lentidão progressiva conforme dados crescem

### 4.1 Cache para Dados Frequentes

| Dados | Frequência de acesso | TTL sugerido | Estratégia |
|-------|---------------------|--------------|------------|
| Pedidos atrasados (badge) | Toda página | 2 min | Session cache |
| Company settings | Toda página | 10 min | Session cache |
| Permissões do usuário | Toda página | 5 min | Session cache |
| Categorias/Subcategorias | Toda listagem de produto | 10 min | Session cache |
| Menus e navegação | Toda página | 15 min | Session cache |

**Implementação:**

1. Criar `app/utils/SimpleCache.php`:
   - Método `remember(string $key, int $ttlSeconds, callable $loader): mixed`
   - Armazena em `$_SESSION['_cache'][$key]` com timestamp
   - Método `forget(string $key)` para invalidar
   - Método `flush()` para limpar todo o cache
2. Aplicar nos pontos mais críticos (header, menus, company settings)
3. Invalidar cache automaticamente em operações de escrita (ex: após criar pedido, limpar cache de contagem)

### 4.2 Otimizar Dropdowns com AJAX (Select2)

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | `OrderController::create()` carrega TODOS os produtos e clientes para popular selects |
| **Impacto** | Páginas lentas quando há milhares de produtos/clientes |
| **Solução** | Select2 com source AJAX + busca paginada server-side |

**Etapas:**

1. Criar endpoint AJAX para busca paginada:
   - `?page=customers&action=searchAjax&q=termo&page=1` → JSON com resultados paginados
   - `?page=products&action=searchAjax&q=termo&page=1` → JSON com resultados paginados
2. Nos Models, criar método `searchPaginated(string $query, int $page, int $perPage): array`
3. Substituir `<select>` estático por Select2 com AJAX source
4. Manter compatibilidade: se poucos registros (<100), pode manter estático

### 4.3 Criação de Índices de Banco de Dados

**Índices a criar (em arquivo SQL de migration):**

| Tabela | Índice | Tipo | Justificativa |
|--------|--------|------|---------------|
| `orders` | `(pipeline_stage, status)` | Composto | Query do pipeline/kanban |
| `orders` | `(customer_id)` | Simples | JOINs com customers |
| `orders` | `(created_at)` | Simples | Relatórios por período |
| `customers` | `(name)` | Simples | Busca por nome |
| `customers` | `(document)` | Simples | Busca por CPF/CNPJ |
| `customers` | `(status, deleted_at)` | Composto | Filtro de ativos |
| `login_attempts` | `(ip_address, email, attempted_at)` | Composto | Rate limiting |
| `order_installments` | `(status, paid_date)` | Composto | Relatório financeiro |
| `stock_items` | `(warehouse_id, product_id)` | Composto | Busca de estoque |
| `system_logs` | `(created_at)` | Simples | Consulta de logs |

**Regra:** Criar o arquivo SQL na pasta `/sql` com IF NOT EXISTS para idempotência.

### 4.4 Asset Versioning (Cache Busting)

**Etapas:**

1. Criar helper PHP `asset(string $path): string`:
   ```php
   function asset(string $path): string {
       $file = __DIR__ . '/../' . $path;
       $version = file_exists($file) ? filemtime($file) : time();
       return $path . '?v=' . $version;
   }
   ```
2. Substituir todas as referências estáticas:
   - De: `<link href="assets/css/style.css">`
   - Para: `<link href="<?= asset('assets/css/style.css') ?>">`
3. Aplicar em CSS, JS e imagens que mudam frequentemente

### 4.5 Paginação Server-Side Universal

**Verificar e implementar paginação em:**

| Listagem | Status atual | Ação |
|----------|-------------|------|
| Clientes | ✅ Paginado | — |
| Produtos | ⚠️ Verificar | Adicionar se ausente |
| Pedidos | ✅ Paginado | — |
| Estoque (movimentações) | ⚠️ Verificar | Adicionar se ausente |
| Logs do sistema | ⚠️ Verificar | Adicionar se ausente |
| Parcelas financeiras | ⚠️ Verificar | Adicionar se ausente |
| Comissões | ⚠️ Verificar | Adicionar se ausente |

**Padrão:** Toda listagem com mais de 50 registros deve ter paginação server-side com controle de `limit/offset`.

---

## 6. Fase 5 — UX Minimalista Avançada

> **Objetivo:** Transformar a interface em uma experiência limpa, intuitiva e profissional. Cada pixel tem propósito. Sem ruído visual. Ações fluidas e previsíveis.  
> **Pré-requisito:** Fases 1-4 completas  
> **Complexidade:** Média a Alta  
> **Filosofia:** "O melhor design é o que você não nota" — menos é mais, com acabamento impecável

### 5.1 Design System — Fundação Visual

> Um sistema de design garante consistência radical. Toda decisão visual é pré-definida e reutilizável.

**5.1.1 — Tipografia Unificada**

| Elemento | Fonte | Peso | Tamanho | Line-height |
|----------|-------|------|---------|-------------|
| H1 (título de página) | Inter | 600 | 24px | 1.3 |
| H2 (subtítulo de seção) | Inter | 600 | 18px | 1.3 |
| Body | Inter | 400 | 14px | 1.5 |
| Small (labels, captions) | Inter | 400 | 12px | 1.4 |
| Code (monospace) | JetBrains Mono | 400 | 13px | 1.5 |
| Buttons | Inter | 500 | 14px | 1 |

- Máximo 2 pesos de fonte por tela (400 regular + 600 semibold)
- Nunca usar `font-weight: bold` (700) — preferir 600 (semibold) para título
- Hierarquia por tamanho e peso, nunca por cor

**5.1.2 — Paleta de Cores (Minimalista)**

| Token | Light Mode | Dark Mode | Uso |
|-------|-----------|-----------|-----|
| `--bg-primary` | `#FFFFFF` | `#1A1A2E` | Fundo principal |
| `--bg-secondary` | `#F8F9FA` | `#16213E` | Fundo de cards, alternância de linhas |
| `--bg-tertiary` | `#F1F3F5` | `#0F3460` | Fundo de inputs, hover |
| `--text-primary` | `#212529` | `#E8E8E8` | Texto principal |
| `--text-secondary` | `#6C757D` | `#ADB5BD` | Texto auxiliar, labels |
| `--text-muted` | `#ADB5BD` | `#6C757D` | Placeholders, hints |
| `--accent` | `#0D6EFD` | `#4DABF7` | Links, botões primários, foco |
| `--accent-hover` | `#0B5ED7` | `#339AF0` | Hover de accent |
| `--success` | `#198754` | `#51CF66` | Confirmações, status ok |
| `--warning` | `#FFC107` | `#FFD43B` | Alertas, atenção |
| `--danger` | `#DC3545` | `#FF6B6B` | Erros, exclusão |
| `--border` | `#DEE2E6` | `#2C3E50` | Bordas, separadores |

- Regra: máximo 3 cores por tela (primário + accent + 1 semântica)
- Toda cor de status (success, warning, danger) é usada SOMENTE para status, nunca para decoração

**5.1.3 — Espaçamento (Scale 4px)**

| Token | Valor | Uso |
|-------|-------|-----|
| `--space-xs` | 4px | Gaps mínimos, entre ícone e texto |
| `--space-sm` | 8px | Padding interno de badges, gaps em groups |
| `--space-md` | 16px | Padding de cards, margem entre seções |
| `--space-lg` | 24px | Margem entre blocos |
| `--space-xl` | 32px | Margem de página, separação de seções |
| `--space-2xl` | 48px | Separação de grandes blocos |

- Regra: Todo espaçamento é múltiplo de 4px — sem valores arbitrários
- Consistência: o mesmo `padding` é usado em TODOS os cards do sistema

**5.1.4 — Bordas e Sombras**

| Token | Valor | Uso |
|-------|-------|-----|
| `--radius-sm` | 4px | Badges, tags |
| `--radius-md` | 8px | Cards, inputs, botões |
| `--radius-lg` | 12px | Modais, dropdowns |
| `--radius-full` | 9999px | Avatares, pills |
| `--shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Cards em repouso |
| `--shadow-md` | `0 4px 6px rgba(0,0,0,0.07)` | Cards em hover, dropdowns |
| `--shadow-lg` | `0 10px 15px rgba(0,0,0,0.1)` | Modais |

- Regra: sombra leve em repouso, mais pronunciada em hover/foco — SUTIL, nunca chamativa

**Implementação do Design System:**

1. Criar `assets/css/design-system.css`:
   - CSS Custom Properties (variáveis) para todas as cores, espaçamentos, tipografia
   - Classes utilitárias: `.text-sm`, `.space-md`, `.radius-md`
   - Suporte a `prefers-color-scheme: dark`
   - Toggle manual via `data-theme="dark"` no `<html>`
2. Criar `assets/css/components.css`:
   - Estilos padronizados para: `.akti-card`, `.akti-btn`, `.akti-input`, `.akti-table`, `.akti-badge`, `.akti-modal`
3. Importar no `header.php` ANTES de qualquer CSS customizado

---

### 5.2 Layout — Estrutura de Página

**5.2.1 — Sidebar Minimalista**

O sidebar deve ser:
- Largura fixa: 240px (expandido), 64px (colapsado — só ícones)
- Toggle suave com animação CSS (não JS)
- Ícones Font Awesome + texto — em colapsado, tooltip no hover mostra o texto
- Seções agrupadas visualmente por categoria (Operação, Financeiro, Admin)
- Indicador de página ativa: borda left accent + background tertiary
- Sem sub-menus profundos (máximo 1 nível)

**5.2.2 — Header Minimalista**

O header deve conter APENAS:
- Logo (pequeno, à esquerda)
- Breadcrumb (Home > Módulo > Ação)
- Busca rápida global (ícone expandível, `Ctrl+K`)
- Badge de notificações (sino com contador)
- Avatar + nome do usuário (dropdown com perfil/sair)

O que NÃO deve ter:
- Textos longos
- Múltiplas linhas de navegação
- Informações de status do sistema no header

**5.2.3 — Content Area**

- Padding consistente: `var(--space-xl)` em todos os lados
- Título da página: H1 com contador (ex: "Clientes (247)")
- Toolbar: busca + filtros + botão de ação primário — em uma única linha horizontal
- Conteúdo: cards ou tabelas — nunca misturados na mesma vista (toggle para mobile)
- Footer de conteúdo: paginação centralizada, sutil

---

### 5.3 Componentes UI — Padrões Minimalistas

**5.3.1 — Tabelas**

| Aspecto | Padrão |
|---------|--------|
| Cabeçalho | Background `--bg-secondary`, texto `--text-secondary`, uppercase, font-size 12px |
| Linhas | Hover com `--bg-tertiary`, transição 150ms |
| Alternância | Zebra striping com `--bg-secondary` a cada 2 linhas |
| Bordas | Apenas horizontal inferior (`border-bottom: 1px solid var(--border)`) |
| Ações | Dropdown "⋯" no final da linha (3 pontos), sem múltiplos botões visíveis |
| Seleção | Checkbox à esquerda, toolbar de ações em massa aparece no topo |
| Responsivo | Abaixo de 768px, cada linha vira um card |

**5.3.2 — Formulários**

| Aspecto | Padrão |
|---------|--------|
| Labels | Acima do campo, font-size 12px, `--text-secondary`, uppercase |
| Inputs | Borda `--border`, padding 10px 14px, radius `--radius-md` |
| Foco | Borda `--accent` + shadow `0 0 0 3px rgba(accent, 0.15)` |
| Erro | Borda `--danger` + mensagem abaixo em `--danger`, font-size 12px |
| Layout | 2 colunas em desktop (>768px), 1 coluna em mobile |
| Botões | Primário à direita, secundário (cancelar) à esquerda |
| Espaçamento | `var(--space-md)` entre campos |

**5.3.3 — Botões**

| Tipo | Estilo | Uso |
|------|--------|-----|
| Primário | Background `--accent`, texto branco, radius `--radius-md` | Ação principal (1 por tela) |
| Secundário | Background transparente, borda `--border`, texto `--text-primary` | Ações secundárias |
| Danger | Background `--danger`, texto branco | Exclusão (apenas em confirmação) |
| Ghost | Sem background, sem borda, texto `--accent` | Links disfarçados, ações terciárias |
| Icon | Quadrado 36x36, apenas ícone, tooltip | Ações inline (editar, copiar) |

- Regra: apenas 1 botão primário por tela/modal
- Todos com `cursor: pointer`, hover state e focus ring
- Mínimo 44x44px de área clicável (acessibilidade touch)

**5.3.4 — Cards**

| Aspecto | Padrão |
|---------|--------|
| Background | `--bg-primary` |
| Borda | `1px solid var(--border)` |
| Radius | `var(--radius-md)` |
| Shadow | `var(--shadow-sm)`, hover `var(--shadow-md)` |
| Padding | `var(--space-md)` |
| Header | Título bold + ação no canto superior direito |
| Máximo por linha | 4 em desktop, 2 em tablet, 1 em mobile |

**5.3.5 — Modais e Drawers**

| Aspecto | Padrão |
|---------|--------|
| Backdrop | `rgba(0,0,0,0.5)` com blur de 2px |
| Container | Centralizado, max-width 560px, radius `--radius-lg` |
| Header | Título + botão X (sem cor, ghost) |
| Footer | Botões alinhados à direita: [Cancelar] [Confirmar] |
| Animação | Fade in + slide up (200ms ease-out) |
| Drawer | Slide da direita, largura 480px, para formulários complexos |

**5.3.6 — Feedback e Estados**

| Estado | Indicador |
|--------|-----------|
| Loading | Skeleton screen (não spinner) |
| Vazio | Ilustração SVG sutil + texto + CTA |
| Sucesso | Toast no canto superior direito (3s, auto-dismiss, verde sutil) |
| Erro | Toast vermelho ou inline abaixo do campo |
| Confirmação destrutiva | Modal com input de confirmação ("digite EXCLUIR") |

---

### 5.4 Eliminar CSS Inline

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Views contêm blocos `<style>` extensos (ex: `customers/index.php` ~90 linhas) |
| **Solução** | Extrair para arquivos CSS por módulo |

**Etapas:**

1. Criar arquivos CSS por módulo:
   - `assets/css/modules/customers.css`
   - `assets/css/modules/products.css`
   - `assets/css/modules/pipeline.css`
   - `assets/css/modules/financial.css`
   - `assets/css/modules/orders.css`
2. Remover blocos `<style>` das views
3. Incluir CSS do módulo via `<link>` no head da view (ou via variável `$pageStyles`)
4. Consolidar estilos duplicados no `design-system.css`

---

### 5.5 Dark Mode

**Implementação:**

1. Toda a paleta de cores via CSS Custom Properties (já definida no Design System)
2. Toggle no header: ícone ☀️/🌙 que alterna `data-theme="dark"` no `<html>`
3. Salvar preferência:
   - No `localStorage` para persistência
   - Respeitar `prefers-color-scheme` como padrão
4. Transição suave: `transition: background-color 200ms, color 200ms` no `*`
5. Testar todos os módulos: tabelas, forms, modais, pipeline kanban, gráficos

---

### 5.6 Skeleton Loading

**Substituir todos os "Carregando..." por skeleton screens:**

1. Criar componente `assets/js/components/skeleton.js`:
   - `Skeleton.table(rows, cols)` — gera linhas cinzas pulsantes
   - `Skeleton.card(count)` — gera cards cinzas pulsantes
   - `Skeleton.form(fields)` — gera campos de formulário pulsantes
2. CSS de skeleton: `@keyframes pulse` com gradiente animado
3. Aplicar em:
   - Carregamento de tabelas (clientes, pedidos, produtos)
   - Dashboard widgets
   - Pipeline kanban (cards sendo carregados)

---

### 5.7 Breadcrumbs Contextuais

**Implementação:**

1. Criar `app/views/components/breadcrumb.php`
2. Definir breadcrumb por rota no `routes.php`:
   ```php
   'customers' => [
       'breadcrumb' => ['Início', 'Cadastros', 'Clientes'],
       ...
   ]
   ```
3. Renderizar no header de conteúdo, abaixo do header principal
4. O último item é texto (não link), os anteriores são links navegáveis
5. Em mobile: mostrar apenas "← Voltar" ao invés do breadcrumb completo

---

### 5.8 Keyboard Shortcuts

| Atalho | Ação | Contexto |
|--------|------|----------|
| `Ctrl+K` ou `/` | Focar busca rápida global | Qualquer página |
| `N` | Novo registro | Páginas de listagem (se não em input) |
| `Esc` | Fechar modal/drawer | Quando modal aberto |
| `Ctrl+S` | Salvar formulário | Páginas de edição/criação |
| `?` | Abrir ajuda de atalhos | Qualquer página |

**Implementação:**

1. Criar `assets/js/shortcuts.js`
2. Usar `document.addEventListener('keydown')` com detecção de foco (não disparar quando em input)
3. Criar modal de ajuda listando todos os atalhos (acessível via `?`)

---

### 5.9 Estados Vazios (Empty States)

**Cada listagem sem dados deve mostrar:**

1. Ilustração SVG minimalista (consistente com o visual do sistema)
2. Texto explicativo claro: "Nenhum cliente cadastrado ainda"
3. Call-to-action: botão primário "Adicionar primeiro cliente"
4. Tom positivo, nunca punitivo ("Comece cadastrando..." não "Não há dados!")

**Criar library de ilustrações SVG:**
- `assets/img/empty/no-customers.svg`
- `assets/img/empty/no-orders.svg`
- `assets/img/empty/no-products.svg`
- `assets/img/empty/no-results.svg` (para busca sem resultados)
- `assets/img/empty/no-notifications.svg`

---

### 5.10 Toasts e Feedback

**Substituir SweetAlert2 em confirmações simples por sistema de toasts:**

1. Criar `assets/js/components/toast.js`:
   - `Toast.success('Mensagem')` — verde, 3s, canto superior direito
   - `Toast.error('Mensagem')` — vermelho, 5s (mais tempo para ler)
   - `Toast.warning('Mensagem')` — amarelo, 4s
   - `Toast.info('Mensagem')` — azul, 3s
2. Stack de toasts (múltiplos ao mesmo tempo)
3. Animação: slide in da direita, fade out
4. Manter SweetAlert2 apenas para confirmações destrutivas (excluir, cancelar pedido)

---

## 7. Fase 6 — Expansão Inteligente

> **Objetivo:** Adicionar funcionalidades que elevam o valor do sistema para o usuário final, mantendo a filosofia minimalista.  
> **Pré-requisito:** Fases 1-5 completas  
> **Complexidade:** Alta  
> **Filosofia:** Cada novo módulo nasce já com design system, testes e documentação

### 6.1 Notificações em Tempo Real

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Alertas de pedidos atrasados dependem de queries no header (lento, não real-time) |
| **Solução** | WebSocket via API Node.js (Socket.io) ou Server-Sent Events (SSE) |

**Funcionalidades:**
- Sino no header com badge de contagem
- Dropdown com lista de notificações (lida/não lida)
- Notificações push (Web Push API) para navegador
- Tipos: pedido atrasado, pagamento recebido, estoque baixo, novo pedido

**Modelo de dados:**
```
notifications
  id INT PK
  tenant_id INT
  user_id INT (destinatário)
  type ENUM('order_delayed', 'payment_received', 'stock_low', 'new_order')
  title VARCHAR(255)
  message TEXT
  data JSON (metadata como order_id, amount)
  read_at DATETIME NULL
  created_at DATETIME
```

---

### 6.2 Dashboard Customizável

| Aspecto | Detalhe |
|---------|---------|
| **Problema** | Dashboard fixo, não adaptável ao perfil do usuário |
| **Solução** | Widgets drag-and-drop com persistência da configuração |

**Widgets disponíveis:**
- Faturamento do período (gráfico de barras)
- Pedidos por status (gráfico de donut)
- Pipeline — resumo de etapas (mini-kanban)
- Pedidos atrasados (lista)
- Últimas vendas (tabela compacta)
- Meta de produção (gauge/progress)
- Estoque crítico (lista com alerta)
- Financeiro — contas a receber/pagar (resumo)

**Implementação:**
1. Usar GridStack.js para layout drag-and-drop
2. Salvar configuração do usuário em `dashboard_widgets` (tabela)
3. Cada widget é um partial PHP carregado via AJAX
4. Preset de layouts por perfil: "Gerente", "Financeiro", "Produção"

---

### 6.3 Busca Global (Command Palette)

| Aspecto | Detalhe |
|---------|---------|
| **Inspiração** | VS Code (Ctrl+P), Spotlight (Mac), Linear (Ctrl+K) |
| **Ativação** | `Ctrl+K` ou clique no ícone de busca no header |

**Funcionalidades:**
- Buscar em: clientes, pedidos, produtos, páginas do sistema
- Resultados agrupados por tipo com ícone
- Navegação por teclado (↑↓ para selecionar, Enter para ir)
- Histórico de buscas recentes (localStorage)
- Ações rápidas: "Novo pedido", "Novo cliente" (prefixo `>`)

**Implementação:**
1. Criar `assets/js/components/command-palette.js`
2. Modal centrado, campo de busca com foco automático
3. Endpoint AJAX: `?page=api&action=globalSearch&q=termo`
4. Debounce de 200ms na busca
5. Cache dos resultados de "páginas do sistema" (estático)

---

### 6.4 Relatórios Avançados

**Relatórios essenciais a implementar:**

| Relatório | Dados | Visualização |
|-----------|-------|-------------|
| Faturamento por período | Soma de pedidos por mês/semana/dia | Gráfico de barras + tabela |
| Produtos mais vendidos | Ranking por quantidade e receita | Tabela + gráfico de barras horizontal |
| Tempo médio por etapa do pipeline | Média de dias em cada etapa | Gráfico de funil |
| Performance por responsável | Pedidos concluídos por operador | Tabela rankeada |
| Inadimplência | Parcelas vencidas, valor total | Tabela + indicador |
| Estoque mínimo | Produtos abaixo do estoque mínimo | Tabela com alerta |
| ABC de clientes | Classificação por faturamento | Curva ABC + tabela |

**Padrão UX para relatórios:**
- Filtros no topo: período, cliente, produto, status
- Visualização gráfica + tabela abaixo
- Exportação: PDF (TCPDF) e Excel (PhpSpreadsheet)
- Print-friendly: `@media print` otimizado

---

### 6.5 Documentação da API (Swagger/OpenAPI)

**Implementação:**

1. Instalar `swagger-jsdoc` e `swagger-ui-express` na API Node.js
2. Documentar todos os endpoints existentes com JSDoc:
   - Path, método HTTP, parâmetros, body, respostas
3. Acessível em `https://api.dominio/docs`
4. Documentar também os endpoints AJAX do PHP em `docs/API_PHP.md`

---

## 8. Fase 7 — Maturidade Operacional

> **Objetivo:** O sistema atinge nível 4 de maturidade (Quantificado). Processos automatizados, métricas monitoradas, deploy confiável.  
> **Pré-requisito:** Todas as fases anteriores  
> **Complexidade:** Média  
> **Filosofia:** Automação de tudo que pode ser automatizado

### 7.1 Docker para Ambiente de Desenvolvimento

**Criar `docker-compose.yml`:**
- PHP-FPM 8.1 + Nginx (ou Apache)
- MySQL 8.0
- Node.js 20 (para a API)
- Volumes para código-fonte
- .env para configuração
- Health checks

**Benefícios:**
- Setup de novo desenvolvedor em `docker-compose up` (1 comando)
- Ambiente idêntico entre devs
- Base para CI/CD

---

### 7.2 Monitoramento (APM e Error Tracking)

**Implementação:**
1. Integrar Sentry (free tier) para error tracking:
   - PHP SDK: `composer require sentry/sentry`
   - JS SDK: para erros de frontend
   - Node.js SDK: para erros da API
2. Criar `app/middleware/SentryMiddleware.php` para capturar exceções
3. Configurar alertas por email para erros críticos

---

### 7.3 Logging Estruturado (PSR-3)

**Implementação:**
1. Instalar Monolog: `composer require monolog/monolog`
2. Criar `app/core/Log.php` como wrapper:
   - Canais: `security`, `financial`, `general`, `api`
   - Formato JSON: `{"timestamp", "level", "message", "context", "tenant_id", "user_id"}`
   - Rotação diária automática (RotatingFileHandler)
3. Substituir todos os `error_log()` e `file_put_contents()` por `Log::channel('x')->error()`

---

### 7.4 Backup Automatizado

**Implementação:**
1. Criar `scripts/backup.sh`:
   - Dump de cada banco de tenant
   - Compressão com gzip
   - Rotação: manter últimos 7 dias (diário) + 4 semanas (semanal)
   - Upload para storage remoto (S3, Google Cloud Storage)
2. Configurar cron job: `0 2 * * * /path/to/backup.sh` (2h da manhã)
3. Criar endpoint de health check que verifica se o último backup foi feito

---

### 7.5 Documentação de Deploy

Criar `docs/DEPLOY.md` com:

1. **Requisitos de servidor:**
   - PHP >= 7.4 (recomendado 8.1) com extensões: pdo_mysql, mbstring, json, openssl, curl
   - MySQL >= 5.7 / MariaDB >= 10.3
   - Node.js >= 20 (para API)
   - Nginx ou Apache

2. **Variáveis de ambiente obrigatórias:**
   - Lista completa de todas as variáveis em `.env.example`

3. **Processo de deploy:**
   - `git pull`
   - `composer install --no-dev`
   - `php scripts/migrate.php`
   - `npm install --production` (api/)
   - Restart serviços

4. **Checklist pós-deploy:**
   - Verificar health check
   - Verificar logs de erro
   - Testar login

---

## 9. Dependências entre Fases

```
Fase 1 (Blindagem)
  │
  ├── Não depende de nada
  │
  ▼
Fase 2 (Arquitetura)
  │
  ├── Depende: Fase 1 (segurança resolvida)
  │
  ▼
Fase 3 (Qualidade)          Fase 4 (Performance)
  │                            │
  ├── Depende: Fase 2         ├── Depende: Fase 2
  │   (Services testáveis)    │   (Singleton DB, HeaderService)
  │                            │
  └────────┬───────────────────┘
           │
           ▼
       Fase 5 (UX)
           │
           ├── Depende: Fases 3-4
           │   (base estável e performática)
           │
           ▼
       Fase 6 (Expansão)
           │
           ├── Depende: Fase 5
           │   (design system definido)
           │
           ▼
       Fase 7 (Maturidade)
           │
           ├── Depende: Fase 6
           │   (sistema completo)
           │
           ▼
      Sistema Maduro (Nível 4)
```

**Paralelismo possível:**
- Fases 3 e 4 podem ser executadas em paralelo
- Dentro de cada fase, os items são independentes (exceto quando indicado)

---

## 10. Métricas de Sucesso por Fase

| Fase | Métrica | Valor Alvo | Como Medir |
|------|---------|------------|------------|
| **1** | Vulnerabilidades CRÍTICAS | 0 | Checklist SEV-01 a SEV-08 |
| **1** | Security Headers score | B+ ou A | securityheaders.com |
| **1** | Credenciais hardcoded | 0 | `grep -r` no codebase |
| **2** | Conexões PDO por request | 1 (singleton) | Log/debug |
| **2** | SQL direto em Views | 0 linhas | `grep -r "SELECT\|INSERT\|UPDATE" app/views/` |
| **2** | Controller > 500 linhas | 0 | `wc -l app/controllers/*.php` |
| **3** | Cobertura de testes (Unit) | >40% | PHPUnit --coverage |
| **3** | Erros PHPStan nível 5 | 0 | `vendor/bin/phpstan` |
| **3** | CI pipeline | Funcional | GitHub Actions green |
| **4** | Tempo médio de página | <2s | Browser DevTools / Lighthouse |
| **4** | Índices de banco | Todos os críticos criados | Verificar `SHOW INDEX` |
| **5** | CSS inline em views | 0 blocos `<style>` | `grep -r "<style>" app/views/` |
| **5** | Consistência de componentes | 100% usando design system | Code review |
| **5** | Lighthouse Performance | >80 | Lighthouse audit |
| **5** | Lighthouse Accessibility | >90 | Lighthouse audit |
| **6** | Novos módulos com testes | 100% | Coverage do novo código |
| **7** | Uptime monitorado | >99% | Health check endpoint |
| **7** | Backup automatizado | Diário | Verificar timestamps |

---

## 11. Princípios de UX Minimalista Aplicados

### 11.1 As 10 Regras de UX do Akti

1. **Uma ação primária por tela** — Nunca mais de 1 botão primário. O usuário deve saber instantaneamente o que fazer.

2. **Informação progressiva** — Mostrar o mínimo necessário na listagem. Detalhes acessíveis por clique/hover. Nunca sobrecarregar.

3. **Espaço em branco é design** — Não preencher espaço vazio. O espaço respira. Margens generosas, cards espaçados.

4. **Consistência radical** — Se um botão de salvar é azul em uma tela, é azul em TODAS. Sem exceções. Sem "criativos".

5. **Feedback imediato** — Toda ação tem resposta visual em <100ms. Loading state, hover state, focus state. O sistema nunca fica "mudo".

6. **Ações destrutivas são difíceis, ações construtivas são fáceis** — Criar: 1 clique. Excluir: confirmação + input de segurança. Essa assimetria é proposital.

7. **Mobile não é uma versão reduzida** — É uma versão repensada. Cards em vez de tabelas. Swipe em vez de botões. Bottom navigation em vez de sidebar.

8. **Acessibilidade é obrigatória** — Focus ring em todos os interativos. Contraste WCAG AA. Navegação 100% por teclado. Alt text em imagens.

9. **Animações servem para orientar** — Modais aparecem de onde foram clicados. Toasts surgem e saem suavemente. Nunca animar por animar. Sempre <300ms.

10. **O sistema se explica sozinho** — Labels claros, placeholders informativos, empty states com CTA. Se precisar de manual para usar, o design falhou.

### 11.2 Anti-Padrões a Evitar

| ❌ Evitar | ✅ Fazer |
|-----------|---------|
| Múltiplos botões coloridos por linha | Um dropdown "⋯" com opções |
| Tabelas com 15+ colunas | 5-7 colunas + "ver detalhes" |
| Modais dentro de modais | Drawer lateral ou página nova |
| Cores gritantes para status | Cores pastel com ícone semântico |
| Texto em CAPS LOCK | Semibold (600) com tamanho maior |
| Loading spinner por 5+ segundos | Skeleton screen + AJAX parcial |
| Alertas JavaScript (`alert()`) | Toasts estilizados |
| Redirecionamento sem feedback | Toast + redirecionamento |
| Formulário de 30+ campos | Multi-step wizard |
| Sidebar com 30+ itens | Agrupados por categoria, colapsáveis |

---

## 12. Gestão de Riscos

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| Regressão ao refatorar controllers | Alta | Alto | Escrever testes ANTES da refatoração |
| Quebra de layout ao migrar CSS | Média | Médio | Migrar módulo por módulo, testar em staging |
| Performance pior após cache | Baixa | Alto | Cache invalidation cuidadosa, monitorar |
| Resistência do usuário a nova UI | Média | Médio | Mudanças graduais, coleta de feedback |
| Conflitos de merge com trabalho em paralelo | Alta | Médio | Feature branches, PRs pequenos, CI |
| Migrations quebrando em produção | Média | Alto | Testar em staging idêntico, rollback plan |
| Dependência de novo dev que não segue padrões | Alta | Alto | CI com lint + PHPStan obrigatórios |

---

## 📋 Resumo Executivo

| Fase | Foco | Itens | Complexidade |
|------|------|-------|-------------|
| **1 — Blindagem** | Segurança e estabilidade | 8 | Baixa |
| **2 — Arquitetura** | Fundações e estrutura | 6 | Média-Alta |
| **3 — Qualidade** | Testes e CI/CD | 5 | Alta |
| **4 — Performance** | Velocidade e otimização | 5 | Média |
| **5 — UX Minimalista** | Interface e experiência | 10 | Média-Alta |
| **6 — Expansão** | Novos módulos e features | 5 | Alta |
| **7 — Maturidade** | DevOps e automação | 5 | Média |
| **TOTAL** | | **44 itens** | |

**Resultado esperado ao final do roadmap:**
- 🔒 0 vulnerabilidades críticas ou altas
- 🧪 >40% de cobertura de testes
- ⚡ <2s de tempo de carregamento
- 🎨 Interface minimalista e acessível
- 🏗️ Código limpo, testável e documentado
- 🚀 CI/CD funcional com deploy automatizado
- 📊 Monitoramento e backup automatizados
- **Nível de maturidade: 4 (Quantificado)**

---

**Documento gerado em:** 30/03/2026  
**Próxima revisão recomendada:** Ao final de cada fase

> ⚠️ Este roadmap não inclui nomes de arquivos SQL de migração nem datas fixas de entrega. A velocidade de execução depende da equipe e prioridades de negócio. Cada item deve ser avaliado individualmente antes da implementação.
