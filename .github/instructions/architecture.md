# Arquitetura Geral

---

## Sumário
- [Visão Geral](#visão-geral)
- [Estrutura de Pastas](#estrutura-de-pastas)
- [Autoload PSR-4](#autoload-psr-4-namespaces)
- [Responsabilidades MVC](#responsabilidades-mvc)
- [Multi-Tenant](#multi-tenant)

---

## Visão Geral
O sistema utiliza PHP 7.4+ e MySQL/MariaDB, arquitetura MVC, com autoload PSR-4.

As responsabilidades estão divididas:
- **Models:** comunicação com o banco e lógicas críticas, sem mexer com requisições HTTP.
- **Controllers:** lidam com a requisição, validam, chamam o model e processam/redirecionam.
- **Views:** apenas exibição, todo dado exibido deve passar por helpers de escape.

---

## Estrutura de Pastas
```
/app
  /bootstrap    # Autoloader PSR-4
  /config       # Configurações
  /controllers  # Lógica de negócio
  /core         # Classes centrais
  /middleware   # Middleware
  /models       # Modelos de dados
  /utils        # Funções utilitárias
  /views        # Visualização
    /errors     # Páginas de erro
    /layout     # Cabeçalho, Rodapé, Menu
/assets
  /css          # Estilos
  /js           # Scripts
  /img          # Imagens
  /uploads      # Uploads por tenant
/docs           # Documentação
/sql            # Scripts SQL
/storage
  /logs         # Logs
index.php       # Router
```

---

## Autoload PSR-4 (Namespaces)
- Classes usam namespace raiz `Akti\`.
- Models: `Akti\Models`, Controllers: `Akti\Controllers`, etc.
- Não usar `require_once` manual.
- Classes globais (Database, TenantManager, SessionGuard) sem namespace, referenciadas com `\`.

---

## Responsabilidades MVC
- Models: acesso a dados e regras de negócio.
- Controllers: processam requisições, instanciam models, retornam views.
- Views: exibição, mínimo de PHP, sempre usar helpers de escape.

---

## Multi-Tenant
- Sistema baseado em subdomínios vinculando ao banco master.
- Cada cliente tem ambiente isolado.
- Nunca mesclar uploads de clientes.

---