# 🚀 Melhorias e Correções Recomendadas — Akti

**Data:** 30/03/2026  
**Referência:** Auditoria Geral 2026

---

## 1. Correções Imediatas (Quick Wins)

### 1.1 Database.php — Substituir `echo` por exceção
```
Arquivo: app/config/database.php
Linha: 41
Esforço: 5 min
```
- Substituir `echo 'Erro na conexão: '` por `throw new RuntimeException()`
- Isso já está no ROADMAP.md como A-05

### 1.2 Remover Arquivos de Backup
```
Arquivos:
  - app/controllers/FinancialController.php.bak
  - app/controllers/FinancialController.php.new
Esforço: 1 min
```

### 1.3 Adicionar Security Headers
```
Arquivo: index.php (após session_start())
Esforço: 10 min
```

### 1.4 Adicionar session_regenerate_id
```
Arquivo: app/controllers/UserController.php (no método que processa login)
Esforço: 5 min
```

### 1.5 Proteger Diretório scripts/
```
Arquivo: .gitignore
Conteúdo: script/
Esforço: 2 min
```

### 1.6 Remover Credenciais Hardcoded
```
Arquivos: app/config/tenant.php, app/models/IpGuard.php
Esforço: 15 min
```

---

## 2. Melhorias Arquiteturais

### 2.1 Implementar Singleton para Database
**Problema:** Múltiplas conexões PDO criadas por request.  
**Solução:**
```php
class Database {
    private static $instance = null;
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = (new self())->getConnection();
        }
        return self::$instance;
    }
}
```

### 2.2 Service Layer para Header.php
**Problema:** Header.php contém 70+ linhas de queries SQL.  
**Solução:** Criar `app/services/HeaderDataService.php`:
```php
class HeaderDataService {
    public function getDelayedOrders(): array { ... }
    public function getDelayedProducts(): array { ... }
    public function getUserPermissions(int $userId): array { ... }
}
```

### 2.3 Refatorar Controllers Grandes
**Alvo:** Controllers com mais de 500 linhas.

| Controller           | Linhas | Ação                              |
|----------------------|--------|-----------------------------------|
| CustomerController   | 2398   | Extrair ImportService, ExportService |
| ProductController    | 1194   | Extrair ImportService, GradeService  |
| PipelineController   | 948+   | Extrair SectorService               |
| OrderController      | 565    | OK (mas pode melhorar)              |
| UserController       | 516    | OK                                  |

### 2.5 Implementar Cache Layer
**Proposta:** Cache baseado em sessão para dados frequentes:
```php
class SimpleCache {
    public static function remember(string $key, int $ttl, callable $callback) {
        $cacheKey = '_cache_' . $key;
        $timeKey = '_cache_time_' . $key;
        
        if (isset($_SESSION[$cacheKey]) && (time() - ($_SESSION[$timeKey] ?? 0)) < $ttl) {
            return $_SESSION[$cacheKey];
        }
        
        $value = $callback();
        $_SESSION[$cacheKey] = $value;
        $_SESSION[$timeKey] = time();
        return $value;
    }
}
```

---

## 3. Melhorias de Testes

### 3.1 Prioridade de Cobertura

| Componente            | Testes Necessários                    | Prioridade |
|-----------------------|---------------------------------------|-----------|
| Validator             | Todas as regras de validação          | Alta      |
| Input/Sanitizer       | Todos os tipos de sanitização         | Alta      |
| CsrfMiddleware        | Validação, isenção, falha             | Alta      |
| Router                | Resolução de rotas, 404, public       | Alta      |
| User (auth)           | Login, permissões, checkPermission    | Alta      |
| Customer (CRUD)       | Create, read, update, delete, search  | Média     |
| Order (CRUD)          | Create, pipeline stages               | Média     |
| EventDispatcher       | Dispatch, listen, error handling      | Média     |
| ModuleBootloader      | canAccess, isEnabled                  | Média     |
| Financial (Services)  | Cálculos, relatórios                  | Média     |

### 3.2 Estrutura de Testes Sugerida
```
tests/
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
│   │   └── OrderTest.php
│   ├── Middleware/
│   │   ├── CsrfMiddlewareTest.php
│   │   └── RateLimitMiddlewareTest.php
│   └── Services/
│       ├── InstallmentServiceTest.php
│       └── CommissionServiceTest.php
├── Integration/
│   └── Controllers/
│       ├── CustomerControllerTest.php
│       └── OrderControllerTest.php
└── Pages/
    └── (testes HTTP existentes)
```

---

## 4. Melhorias de Performance

### 4.1 Queries do Header (Impacto em TODAS as páginas)
- Cachear resultado de pedidos atrasados por 2 minutos
- Ou usar AJAX para carregar o badge de notificações depois do carregamento da página

### 4.2 Select2 AJAX para Dropdowns Grandes
- `OrderController::create()` carrega TODOS produtos e clientes
- Substituir por Select2 com endpoint AJAX paginado

### 4.3 Índices de Banco de Dados
Sugerir criação de índices para queries frequentes:
```sql
-- Pipeline performance
CREATE INDEX idx_orders_pipeline ON orders(pipeline_stage, status);
CREATE INDEX idx_orders_customer ON orders(customer_id);

-- Customers search
CREATE INDEX idx_customers_name ON customers(name);
CREATE INDEX idx_customers_document ON customers(document);
CREATE INDEX idx_customers_status ON customers(status, deleted_at);

-- Login attempts
CREATE INDEX idx_login_attempts_ip_email ON login_attempts(ip_address, email, attempted_at);
```

### 4.4 Asset Versioning
```php
// Em vez de:
<link rel="stylesheet" href="assets/css/style.css">

// Usar:
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
```

---

## 5. Melhorias de DevOps

### 5.1 GitHub Actions CI
```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: vendor/bin/phpunit
```

### 5.2 PHPStan (Análise Estática)
```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse app/ --level 5
```

### 5.3 PHP-CS-Fixer (Code Style)
```bash
composer require --dev friendsofphp/php-cs-fixer
```

---

## 6. Melhorias de UX/Frontend

### 6.1 Extrair CSS Inline
Criar `assets/css/customers.css` com os ~90 linhas de CSS inline de `customers/index.php`.  
*(Observação: já existe referência a `customers.css` no topo da view, mas o CSS inline duplica/sobrescreve)*

### 6.2 Dark Mode
Adicionar suporte a `prefers-color-scheme: dark` para acessibilidade visual.

### 6.3 Skeleton Loading
Substituir "Carregando..." por skeleton screens para melhor UX percebida.

### 6.4 Keyboard Shortcuts
Implementar atalhos de teclado para ações comuns:
- `/` → Focar busca (já parcialmente implementado)
- `N` → Novo registro
- `Esc` → Fechar modal/drawer

---

## Resumo de Esforço

| Categoria                      | Itens | Esforço Total Estimado |
|-------------------------------|-------|----------------------|
| Correções Imediatas           | 6     | ~2 horas             |
| Melhorias Arquiteturais       | 5     | ~3-5 dias            |
| Melhorias de Testes           | 10+   | ~2-3 semanas         |
| Melhorias de Performance      | 4     | ~2-3 dias            |
| Melhorias de DevOps           | 3     | ~1-2 dias            |
| Melhorias de UX               | 4     | ~1 semana            |
| **Total**                     | **32+** | **~4-6 semanas**    |

---

**Prioridade de execução:** C1-C6 → A1-A7 → M1-M8 → B1-B8  
**Próxima revisão:** 30/06/2026
