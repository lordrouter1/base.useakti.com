# Checklist de Auditoria — Arquitetura e Padrões

## Separação MVC
- [ ] Controllers: sem queries SQL diretas, sem `echo`/HTML
- [ ] Models: sem `echo`, `print`, `$_POST`, `$_GET`, HTML
- [ ] Views: sem queries SQL, sem `INSERT`/`UPDATE`/`DELETE`
- [ ] Services: lógica de negócio complexa extraída dos controllers

## PSR-4 Autoloading
- [ ] Todos os controllers: `namespace Akti\Controllers;`
- [ ] Todos os models: `namespace Akti\Models;`
- [ ] Todos os services: `namespace Akti\Services;`
- [ ] Sem `require_once` manual (usa autoloader)
- [ ] `composer.json` com mapeamento PSR-4 correto
- [ ] Nomes de classe coincidem com nomes de arquivo

## Roteamento
- [ ] Mapa de rotas declarativo em `app/config/routes.php`
- [ ] Actions padronizadas: index, create, store, edit, update, delete
- [ ] Checagem de permissão no início de cada action
- [ ] Sem rotas "órfãs" (definidas mas sem controller/método)

## Eventos
- [ ] EventDispatcher registrado no bootstrap
- [ ] Listeners em `app/bootstrap/events.php`
- [ ] Eventos disparados para operações importantes (create, update, delete)
- [ ] Listeners não bloqueiam (sem operações pesadas síncronas)

## Multi-Tenancy
- [ ] TenantManager identifica tenant por subdomain
- [ ] Database switching por tenant
- [ ] Todas as queries filtram por `tenant_id`
- [ ] Uploads isolados por tenant
- [ ] Sessions isoladas por tenant
- [ ] Sem vazamento de dados entre tenants

## Consistência de Controllers
- [ ] Todos os controllers seguem mesmo padrão de construtor (`$db`)
- [ ] Verificação de login no início de cada método
- [ ] Verificação de permissão do grupo
- [ ] Retornos JSON para AJAX, redirect para forms
- [ ] Try/catch com log de erros

## Consistência de Models
- [ ] Todos os models recebem `$db` no construtor
- [ ] Métodos CRUD: `create()`, `readAll()`, `readOne()`, `update()`, `delete()`
- [ ] Retornos consistentes (array ou false)
- [ ] `tenant_id` em todas as queries

## Design Patterns
- [ ] Strategy: Payment Gateways
- [ ] Observer: EventDispatcher
- [ ] Singleton: Database connection
- [ ] Factory: (se aplicável)
- [ ] Repository: Models como data access layer

## Métricas
- [ ] Controllers: contar LOC, métodos públicos
- [ ] Models: contar LOC, métodos
- [ ] Services: contar LOC
- [ ] Arquivos >500 linhas: listar
- [ ] Métodos >100 linhas: listar
- [ ] Complexidade ciclomática alta: identificar

## Middleware Pipeline
- [ ] Ordem de execução documentada
- [ ] CSRF antes do dispatch
- [ ] Security headers em todas as responses
- [ ] Rate limiting configurado
- [ ] Middlewares específicos por rota (se aplicável)
