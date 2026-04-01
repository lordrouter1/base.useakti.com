# Auditoria de Testes e Qualidade de Código — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** PHPUnit test suites, PHPStan, Composer, scripts de diagnóstico, CI/CD, cobertura de código  
> **Referência:** PHPUnit 9.6, PHPStan, Composer Best Practices, GitHub Actions

---

## 1. Resumo Executivo

O sistema possui uma suíte de testes **robusta e multi-camada** com 200+ testes unitários, 60+ testes de rota/página, testes de segurança CSRF e testes de integração AJAX. A base de testes é sólida, especialmente em validação, sanitização, CSRF e rotas. As lacunas estão na ausência de CI/CD, linting frontend, cobertura de código medida e PHPStan em nível moderado.

| Aspecto | Nota | Observação |
|---|---|---|
| Testes Unitários | ⭐⭐⭐⭐ | 200+ testes, cobertura ampla de utils/core |
| Testes de Rotas | ⭐⭐⭐⭐⭐ | 60+ rotas testadas de ponta a ponta |
| Testes de Segurança | ⭐⭐⭐⭐ | CSRF profundo, cenários de ataque cobertos |
| Testes de Integração | ⭐⭐⭐ | AJAX financeiro coberto, demais módulos limitados |
| PHPStan | ⭐⭐⭐ | Level 3/9 — moderado |
| CI/CD | ⭐ | Inexistente |
| Linting Frontend | ⭐ | Inexistente |

---

## 2. Configuração PHPUnit

### 2.1 Test Suites (4)

| Suite | Diretório | Propósito |
|---|---|---|
| **Unit** | `tests/Unit/` | Testes isolados de componentes |
| **Integration** | `tests/Integration/` | Fluxos multi-componente |
| **Security** | `tests/Security/` | Testes de vulnerabilidade |
| **Pages** | `tests/Pages/` | Testes HTTP de rotas/páginas |

### 2.2 Bootstrap e TestCase

**`tests/bootstrap.php`** — Carrega Composer autoloader + PSR-4 + helpers

**`tests/TestCase.php`** — Classe base com:
- Cliente HTTP (cURL) com cookie jar para sessão
- Login automático (sessão única por suite)
- Detecção de erros PHP (Fatal, Parse, Uncaught, etc.)
- Helpers: `assertStatusOk()`, `assertNoPhpErrors()`, `assertValidHtml()`, `assertBodyContains()`, `assertNotLoginPage()`
- Re-autenticação automática em expiração de sessão

### 2.3 Padrões de Detecção de Erros

```php
$errorPatterns = [
    'Fatal error', 'Parse error', 'Uncaught Error',
    'Uncaught Exception', 'Stack trace:', 'xdebug-error',
    'Undefined variable', 'Undefined index', 'Call to undefined'
];
```

---

## 3. Testes Unitários (200+ testes)

### 3.1 Core (tests/Unit/Core/)

| Classe de Teste | Métodos | Cobertura |
|---|---|---|
| `EventDispatcherTest` | 9 | FIFO, isolamento, exceções, forget |
| `RouterTest` | 6+ | Carregamento, resolução page/action, defaults |
| `LogTest` | 6 | Instanciação, canais, níveis, métodos estáticos |
| `ModuleBootloaderTest` | 12+ | Feature flags, page mapping, módulos default |
| `SecurityTest` | 12 | CSRF token generation, validação, expiração, grace period, unicidade |

### 3.2 Models (tests/Unit/)

| Classe de Teste | Métodos | Cobertura |
|---|---|---|
| `CustomerModelTest` | 5+ | CRUD, generateCode, formato CLI-XXXXX, retrocompat |
| `CustomerFase2Test` | 20+ | Todas regras de validação |
| `CustomerFase3Test` | 10+ | Funcionalidades Fase 3 |
| `CustomerFase4Test` | 8+ | Funcionalidades Fase 4 |
| `NfeDocumentTest` | 5+ | Campos fiscais, credenciais, fila |
| `NfeFase3/4/5Test` | 15+ | Fases progressivas NF-e |
| `DashboardWidgetTest` | 5+ | Renderização de widgets |
| `NotificationTest` | 3+ | Entrega de notificações |
| `ValidatorCpfCnpjTest` | 30+ | CPF/CNPJ: válidos, inválidos, dígitos iguais, detecção |

### 3.3 Utils (tests/Unit/Utils/)

| Classe de Teste | Métodos | Cobertura |
|---|---|---|
| `ValidatorTest` | 30+ | required, minLength, maxLength, email, integer, numeric, min, max, between, inList, date, url, regex, cpf, cnpj, passwordStrength, dateNotFuture, decimal, document |
| `SanitizerTest` | 25+ | string, richText, int, float, bool, email, phone, document, cep, url, date, datetime, slug, intArray, stringArray, enum, filename, json |
| `EscapeTest` | 15+ | HTML (XSS), attribute, JavaScript (JSON), URL, CSS |
| `FormHelperTest` | 10+ | Geração de formulários HTML |
| `AssetHelperTest` | 5+ | Links/scripts de assets |
| `InputTest` | 8+ | Sanitização de GET/POST |
| `JwtHelperTest` | 5+ | Geração e validação JWT |
| `SimpleCacheTest` | 5+ | Operações de cache |
| `EnvLoaderTest` | 5+ | Carregamento de variáveis de ambiente |

### 3.4 Services (tests/Unit/Services/)

| Classe de Teste | Métodos | Cobertura |
|---|---|---|
| `TaxCalculatorTest` | 20+ | vProd, vDesc, ICMS (CST 00, 20, 40, 51, CSOSN 101, 102, 900), PIS, COFINS, IPI, DIFAL, vTotTrib, 3 CRT regimes |
| `PerformanceFase4Test` | 5+ | Benchmarks de performance |

### 3.5 Middleware (tests/Unit/Middleware/)

| Classe de Teste | Métodos | Cobertura |
|---|---|---|
| `CsrfMiddlewareTest` | 12+ | Rotas isentas, wildcard, token extraction, duplicação |
| `PortalAuthMiddlewareTest` | 5+ | Autenticação do portal |
| `RateLimitMiddlewareTest` | 5+ | Rate limiting |
| `SecurityHeadersMiddlewareTest` | 5+ | Headers de segurança |
| `SentryMiddlewareTest` | 3+ | Integração Sentry |

---

## 4. Testes de Integração

### 4.1 AJAX Financial (tests/Integration/Controllers/)

| Teste | Endpoint | Validação |
|---|---|---|
| `getDre()` | DRE JSON | Estrutura + keys: receivedValues |
| `getCashflow()` | Cashflow projeção | Meses + JSON válido |
| `recurringList()` | Transações recorrentes | JSON array |
| `exportDreCsv()` | Export DRE CSV | CSV válido |
| `exportCashflowCsv()` | Export Cashflow CSV | CSV válido |

### 4.2 Lacunas de Integração

| Módulo | Status | Recomendação |
|---|---|---|
| Produtos CRUD completo | ❌ | Testar create + edit + delete |
| Pedidos fluxo completo | ❌ | Testar create → pipeline → conclusão |
| NF-e emissão | ❌ | Mock SEFAZ + testar fluxo |
| Pagamentos gateway | ❌ | Mock gateway + testar webhook |
| Import/Export | ❌ | Testar upload → processamento |

---

## 5. Testes de Segurança

### 5.1 CSRF Protection (tests/Security/)

| Cenário | Método | Resultado Esperado |
|---|---|---|
| Token vazio | `rejeita_token_vazio()` | false |
| Token null | `rejeita_token_null()` | false |
| Token com espaços | `rejeita_token_com_espacos()` | false |
| Token truncado | `rejeita_token_truncado()` | false |
| Caractere modificado | `rejeita_token_com_caractere_modificado()` | false |
| Token aleatório | `rejeita_token_completamente_aleatorio()` | false |
| Token em maiúsculo | `rejeita_token_em_maiusculo()` | false |
| Token válido | `aceita_token_valido_exato()` | true |
| Token múltiplas vezes | `aceita_token_valido_multiplas_vezes()` | true |
| Grace period | `apos_rotacao_token_antigo_aceito_no_grace_period()` | true |

### 5.2 Lacunas de Segurança

| Categoria | Status | Recomendação |
|---|---|---|
| XSS Testing | ❌ | Testar payloads XSS em formulários |
| SQL Injection | ❌ | Testar payloads SQLi em busca/filtros |
| File Upload | ❌ | Testar MIME type bypass |
| Auth Bypass | ❌ | Testar acesso direto sem sessão |
| Rate Limit | ❌ | Testar burst de requisições |

---

## 6. Testes de Rotas/Páginas (60+ rotas)

### 6.1 Rotas Testadas por Módulo

| Módulo | Rotas | Testes |
|---|---|---|
| Login | 1 | Acesso público, redirecionamento |
| Dashboard/Home | 2 | Carregamento, widgets, menu |
| Produtos | 4 | Listagem, criação, categorias, setores |
| Pedidos | 4 | Listagem, criação, agenda, relatórios |
| Clientes | 2 | Listagem, criação |
| Pipeline | 3 | Kanban, settings, production board |
| Estoque | 4 | Listagem, armazéns, entrada, movimentações |
| Financeiro | 3 | Pagamentos, transações |
| Configurações | 4+ | Company, price tables, widgets, segurança |
| NF-e | 20+ | Listagem, filtros, dashboard, fila, recebidos, auditoria, webhooks, settings, DANFE |
| Usuários | 4 | Listagem, criação, grupos, login |

### 6.2 Padrão de Teste de Rota

```php
public function pagina_carrega_sem_erros(string $url, string $label): void
{
    $response = $this->httpGet($url);
    $this->assertStatusOk($response['status'], $label);
    $this->assertNoPhpErrors($response['body'], $label);
    $this->assertValidHtml($response['body'], $label);
    $this->assertNotLoginPage($response['body'], $label);
}
```

### 6.3 Data Provider

`routes_test.php` — Registry master com 50+ rotas parametrizadas para `@dataProvider`.

---

## 7. PHPStan (Análise Estática)

### 7.1 Configuração Atual

```yaml
level: 3        # Moderado (de 0 a 9)
paths: [app/]   # Apenas código de produção
excludePaths:
  - app/views/  # Views excluídas
  - vendor/
ignoreErrors:
  - '#Undefined variable: \$db#'
  - '#Undefined variable: \$conn#'
  - '#Function checkAdmin not found#'
  - '#Access to an undefined property#'
  - '#Call to an undefined method#'
```

### 7.2 Avaliação

| Aspecto | Status | Observação |
|---|---|---|
| Nível 3/9 | ⚠️ | Poderia subir para 5 (return types obrigatórios) |
| Views excluídas | ✅ | Correto — views têm padrões diferentes |
| Erros ignorados | ⚠️ | 5 padrões ignorados — mascarando problemas reais |
| reportUnmatchedIgnoredErrors | false | ⚠️ Erros corrigidos não são notificados |

### 7.3 Recomendação de Evolução

| Nível | O que adiciona | Impacto |
|---|---|---|
| 4 | Verificação de tipos em chamadas de método | Médio — encontra bugs de tipo |
| 5 | Return types obrigatórios | Alto — força documentação de retorno |
| 6 | Strict comparisons (=== vs ==) | Médio — previne coercion bugs |
| 7-9 | Union types, generics, strictness total | Alto — requer refatoração significativa |

---

## 8. Composer e Autoload

### 8.1 Dependencies de Produção

| Pacote | Versão | Propósito |
|---|---|---|
| `nfephp-org/sped-nfe` | ^5.0 | Biblioteca NF-e |
| `nfephp-org/sped-da` | ^4.0 | Geração DANFE |
| `phpoffice/phpspreadsheet` | ^5.5 | Export Excel/CSV |
| `tecnickcom/tcpdf` | ^6.11 | Geração PDF |

### 8.2 Dependencies de Desenvolvimento

| Pacote | Versão | Propósito |
|---|---|---|
| `phpunit/phpunit` | ^9.6 | Framework de testes |

### 8.3 Autoload PSR-4

9 namespaces mapeados sob `Akti\`:
- `Akti\Core\`, `Akti\Controllers\`, `Akti\Models\`, `Akti\Config\`, `Akti\Services\`, `Akti\Middleware\`, `Akti\Utils\`, `Akti\Security\`, `Akti\Gateways\`

Classmap adicional para configs legados: `database.php`, `tenant.php`, `session.php`

---

## 9. Scripts de Diagnóstico (33 arquivos)

### 9.1 Categorias

| Categoria | Qtd | Exemplos |
|---|---|---|
| Database checks | 5 | `check_columns.php`, `check_db_nfe.php`, `check_tenant_db.php` |
| Debugging | 4 | `debug_approval.php`, `debug_customer_orders.php` |
| Migrations | 4 | `migrate.php`, `executar_migration_fase3.php` |
| Fixes | 4 | `fix_customer_duplicates.php`, `fix_order_approval.php` |
| Diagnósticos | 2 | `diagnostico_completo.php`, `diagnostico_detalhe2.php` |
| Verificações | 3 | `verify_approval.php`, `check_pages.php` |
| Utilities | 5 | `gen_token.php`, `lint_dark_mode.php`, `count_lines.php` |
| Backup | 1 | `backup.sh` |
| Testing | 2 | `testar_idempotencia.php`, `test_report_methods.php` |

### 9.2 Observação

Scripts estão no `.gitignore` — não versionados. Bom para segurança, mas reduz reprodutibilidade em deploy.

---

## 10. CI/CD e Ferramentas de Qualidade

### 10.1 Status Atual

| Ferramenta | Status | Recomendação |
|---|---|---|
| GitHub Actions | ❌ Inexistente | Criar workflow para testes + PHPStan |
| ESLint | ❌ Inexistente | Configurar para JS frontend |
| Prettier | ❌ Inexistente | Configurar para formatação |
| .editorconfig | ❌ Inexistente | Configurar para consistência |
| Pre-commit hooks | ❌ Inexistente | Lint + testes antes de commit |
| Coverage reports | ❌ Inexistente | Gerar com --coverage-html |
| Badges | ❌ Inexistente | Build status, coverage % |

### 10.2 GitHub Actions Recomendado

```yaml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: test
          MYSQL_DATABASE: akti_test
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: pdo_mysql, mbstring, gd, zip
      - run: composer install --no-dev
      - run: vendor/bin/phpstan analyse
      - run: vendor/bin/phpunit --coverage-text
```

---

## 11. Métricas de Qualidade

| Métrica | Valor | Meta |
|---|---|---|
| Testes totais | 280+ | 500+ |
| Classes de teste | 30+ | 50+ |
| Cobertura de código | Não medida | 70%+ |
| PHPStan level | 3 | 5+ |
| CI/CD pipeline | 0 | 1 |
| Linting rules | 0 | ESLint + PHPStan |
| Tempo de execução | ~30s (estimado) | <60s |

---

## 12. Conclusões e Prioridades

### Forças
1. ✅ Suíte multi-camada (Unit/Integration/Security/Pages)
2. ✅ TestCase robusto com HTTP e detecção de erros
3. ✅ CSRF com 10 cenários de ataque testados
4. ✅ Validação/Sanitização com 70+ testes
5. ✅ Cálculo fiscal (TaxCalculator) com 20+ cenários
6. ✅ 60+ rotas testadas end-to-end

### Prioridades de Melhoria

| Prioridade | Item | Esforço | Impacto |
|---|---|---|---|
| 1 | Criar CI/CD pipeline (GitHub Actions) | Médio | Alto |
| 2 | Medir cobertura de código (--coverage) | Baixo | Alto |
| 3 | Subir PHPStan para Level 5 | Médio | Alto |
| 4 | Adicionar testes de integração para CRUD completo | Alto | Alto |
| 5 | Implementar ESLint para JavaScript | Baixo | Médio |
| 6 | Adicionar .editorconfig | Baixo | Baixo |
| 7 | Testes de segurança: XSS, SQLi, file upload | Alto | Alto |
| 8 | Pre-commit hooks (PHPStan + testes rápidos) | Baixo | Médio |
