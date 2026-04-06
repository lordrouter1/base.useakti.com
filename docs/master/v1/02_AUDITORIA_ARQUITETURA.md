# Auditoria de Arquitetura — Akti Master v1

> **Data da Auditoria:** 06/04/2026  
> **Escopo:** Arquitetura, padrões MVC, estrutura de código

---

## 1. Resumo Executivo

| Aspecto | Nota | Status |
|---------|------|--------|
| Separação MVC | 7/10 | ⚠️ |
| Autoloader/PSR-4 | 3/10 | ❌ |
| Roteamento | 6/10 | ⚠️ |
| Padrões de Projeto | 7/10 | ⚠️ |
| Organização de Código | 7/10 | ✅ |
| Reutilização com Sistema Principal | 3/10 | ❌ |

---

## 2. Separação MVC

### Controllers ✅
Os controllers seguem o padrão correto:
- Capturam input (`$_POST`, `$_GET`)
- Instanciam models
- Decidem qual view renderizar
- Fazem redirecionamentos
- Nenhum controller contém SQL direto

### Models ✅
Os models encapsulam as queries:
- CRUD completo com prepared statements (maioria)
- Lógica de negócio nos models
- Sem HTML ou echo nos models

### Views ⚠️
- Usam `htmlspecialchars()` para escape (parcial)
- Sem lógica SQL nas views
- **Problema:** Algumas views são muito grandes (ex: `git/index.php` com 752 linhas, `logs/index.php` com 575 linhas) — deveriam ser decompostas em partials

---

## 3. Autoloader — Ausência Total

**Problema ARCH-001:** O sistema Master usa `require_once` manual para cada arquivo:

```php
// master/index.php — 16 require_once consecutivos
require_once __DIR__ . '/app/models/AdminUser.php';
require_once __DIR__ . '/app/models/Plan.php';
// ... mais 14 includes
```

**Contraste:** O sistema principal Akti usa PSR-4 autoloader (`app/bootstrap/autoload.php`).

**Impacto:**
- Toda nova classe exige edição manual do `index.php`
- Sem namespaces, risco de colisão de nomes com o sistema principal
- Impossível usar composer autoload

**Correção sugerida:**
1. Adicionar namespaces (`namespace AktiMaster\Controllers;`, `AktiMaster\Models;`)
2. Criar autoloader PSR-4 ou reutilizar o do sistema principal
3. Configurar no `composer.json` raiz:
```json
{
    "autoload": {
        "psr-4": {
            "AktiMaster\\": "master/app/"
        }
    }
}
```

---

## 4. Roteamento

**Status:** Router baseado em `switch/case` monolítico no `index.php`.

```php
switch ($page) {
    case 'login': ...
    case 'dashboard': ...
    case 'plans': ... switch ($action) { ... }
    case 'clients': ... switch ($action) { ... }
    // ...
}
```

**Problemas ARCH-002:**
- Todo novo módulo exige edição do `index.php`
- Switch aninhado (page > action) dificulta manutenção
- Não há middleware pipeline
- Sem suporte a métodos HTTP (GET/POST no mesmo action)

**Correção sugerida:** Implementar router declarativo similar ao sistema principal:
```php
$routes = [
    'dashboard' => ['controller' => 'DashboardController', 'actions' => ['index']],
    'plans'     => ['controller' => 'PlanController', 'actions' => ['index','create','store','edit','update','delete']],
    // ...
];
```

---

## 5. Singleton Database

**Status:** ✅ Padrão Singleton implementado corretamente em `Database.php`:
- `getInstance()` para conexão master
- `connectTo()` estático para conexões a bancos específicos
- PDO com `ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false`

**Problema menor:** O construtor é `public` — deveria ser `private` para garantir o singleton:
```php
private function __construct(...) { }
```

---

## 6. Dependência entre Master e Sistema Principal

**Problema ARCH-003:** O Master é completamente independente do sistema principal, sem compartilhar:
- Autoloader
- Helpers (`e()`, `csrf_field()`, etc.)
- Middleware de segurança
- Padrões de sessão

**Impacto:** Duplicação de código e inconsistência de segurança. O sistema principal tem proteções (CSRF, rate limiting, XSS helpers) que o Master não usa.

**Recomendação:** Avaliar se faz sentido o Master usar o bootstrap do sistema principal para herdar proteções, ou ao menos importar helpers críticos.

---

## 7. Estrutura de Pastas

```
master/
├── index.php              ✅ Entry point
├── app/
│   ├── config/            ✅ Configurações separadas
│   │   ├── config.php     ⚠️ Credenciais hardcoded
│   │   └── database.php   ✅ Singleton PDO
│   ├── controllers/       ✅ 8 controllers organizados
│   ├── models/            ✅ 8 models organizados
│   └── views/             ✅ Organizados por módulo
│       └── layout/        ✅ header/footer separados
├── assets/
│   ├── css/style.css      ✅ Design system próprio
│   └── js/app.js          ✅ JS mínimo
├── docs/                  ✅ Documentação
└── logos/                 ✅ Assets visuais
```

A estrutura é limpa e bem organizada. Segue convenções MVC padrão.

---

## 8. Cross-Platform Support

**Destaque positivo:** Os models `GitVersion`, `Backup`, `NginxLog` e `TenantClient` possuem detecção automática Windows/Linux:
- Caminhos de binários (`mysqldump`, `git`, `mysql`)
- Comandos de shell
- Paths de diretórios

Isso permite desenvolvimento local em XAMPP e deploy em VPS Linux sem alteração de código.

---

## 9. Métricas de Complexidade

| Arquivo | Linhas | Complexidade | Observação |
|---------|--------|-------------|------------|
| GitVersion.php | ~700 | Alta | Muitos métodos, exec complexo |
| Migration.php | ~450 | Alta | SQL dinâmico, multi-DB |
| TenantClient.php | ~350 | Alta | Provisioning, exec |
| Backup.php | ~380 | Média | Cross-platform |
| git/index.php (view) | ~752 | Alta | Deveria ser decomposto |
| logs/index.php (view) | ~575 | Alta | Deveria ser decomposto |
| clients/index.php (view) | ~534 | Alta | Deveria ser decomposto |

**Recomendação:** Views acima de 300 linhas devem ser decompostas em partials para manutenibilidade.
