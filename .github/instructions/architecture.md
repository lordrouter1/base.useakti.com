# Arquitetura Geral

- O sistema utiliza PHP na versão 7.4 ou superior e banco MySQL/MariaDB.
- A arquitetura seguida é MVC (Model-View-Controller) com Autoload PSR-4.
- As responsabilidades estão divididas:
  - Models: comunicação com o banco e lógicas críticas, sem mexer com requisições HTTP (usa Input).
  - Controllers: lidam com a requisição, validam (via Validator), chamam o model e processam ou redirecionam.
  - Views: apenas exibição. Todo o dado exibido deve passar por helpers de escape (e(), eAttr(), etc.).
- Multi-Tenant: o sistema é baseado em subdomínios vinculando ao banco (master para routing). Nunca mesclar uploads de clientes.

## Estrutura de Pastas
O projeto segue a seguinte organização de diretórios:

```
/sistemaTiago
|-- /app
|   |-- /bootstrap    # Autoloader PSR-4 (autoload.php)
|   |-- /config       # Arquivos de configuração (Banco de dados, Globais)
|   |-- /controllers  # Controladores da aplicação (Lógica de negócio)
|   |-- /core         # Classes centrais do sistema (Security, etc.)
|   |-- /middleware    # Middleware (CsrfMiddleware, etc.)
|   |-- /models       # Modelos de interação com o banco de dados
|   |-- /utils        # Funções utilitárias (form_helper.php, etc.)
|   |-- /views        # Arquivos de visualização (HTML/PHP misto)
|       |-- /errors   # Páginas de erro personalizadas (403.php, etc.)
|       |-- /layout   # Cabeçalho, Rodapé, Menu lateral
|-- /assets
|   |-- /css          # Estilos customizados
|   |-- /js           # Scripts customizados
|   |-- /img          # Imagens do sistema
|   |-- /uploads      # Uploads por tenant: uploads/{db_name}/{modulo}/
|-- /docs             # Documentação técnica e arquivos de configuração
|-- /sql              # Scripts SQL para criação e migração do banco
|-- /storage
|   |-- /logs         # Logs de segurança e sistema (security.log)
|-- index.php         # Ponto de entrada da aplicação (Router básico)
```

## Autoload PSR-4 (Namespaces)

### Conceito
O projeto utiliza **autoload PSR-4** para carregar classes automaticamente baseado no namespace, eliminando a necessidade de `require_once` / `include` manuais para models e controllers. O autoloader é registrado em `app/bootstrap/autoload.php` usando `spl_autoload_register()` e é compatível com **PHP 7.4+**.

### Arquivo de Autoload
- **Localização:** `app/bootstrap/autoload.php`
- **Carregado por:** `index.php` (primeira linha executável)
- **Constante definida:** `AKTI_BASE_PATH` — caminho absoluto da raiz do projeto (com `/` final)

### Namespace Base
Todas as classes do projeto utilizam o namespace raiz `Akti\`:

```php
namespace Akti\Models;     // para models
namespace Akti\Controllers; // para controllers
```

### Mapeamento de Namespaces → Diretórios

| Namespace | Diretório |
|-----------|-----------|
| `Akti\Controllers\` | `app/controllers/` |
| `Akti\Models\` | `app/models/` |
| `Akti\Config\` | `app/config/` |
| `Akti\Services\` | `app/services/` |
| `Akti\Core\` | `app/core/` |
| `Akti\Middleware\` | `app/middleware/` |
| `Akti\Repositories\` | `app/repositories/` |
| `Akti\Utils\` | `app/utils/` |
| `Akti\Security\` | `app/security/` |

### Classes Globais (Sem Namespace)
As seguintes classes de infraestrutura permanecem **sem namespace** e são carregadas diretamente pelo autoloader via `require_once`:

| Classe | Arquivo | Motivo |
|--------|---------|--------|
| `Database` | `app/config/database.php` | Classe de conexão usada por todos os models/controllers |
| `TenantManager` | `app/config/tenant.php` | Resolução de tenant por subdomínio (precisa rodar antes de tudo) |
| `SessionGuard` | `app/config/session.php` | Configuração de sessão segura + cookie (deve rodar antes de `session_start`) |

Estas classes são carregadas automaticamente pelo `autoload.php` na ordem correta: `session.php` → `tenant.php` → `database.php`.

### Como Usar nos Controllers
```php
<?php
namespace Akti\Controllers;

use Akti\Models\Product;
use Akti\Models\Category;

class ProductController {
    public function index() {
        $db = (new \Database())->getConnection();
        $product = new Product($db);
        // ...
    }
}
```

### Como Usar nas Views
Views **não possuem namespace** (são incluídas via `require`). Para referenciar classes com namespace, use o **FQCN** (Fully Qualified Class Name):

```php
<?php
// Correto — FQCN com contrabarra inicial
$stages = \Akti\Models\Pipeline::$stages;
$addr = \Akti\Models\CompanySettings::formatCustomerAddress($json);

// Errado — NÃO funciona sem namespace na view
$stages = Pipeline::$stages; // Fatal error: Class not found
```

### Como Adicionar Novas Classes

1. **Model:** Crie o arquivo em `app/models/NomeDoModel.php`:
   ```php
   <?php
   namespace Akti\Models;

   class NomeDoModel {
       // ...
   }
   ```

2. **Controller:** Crie o arquivo em `app/controllers/NomeController.php`:
   ```php
   <?php
   namespace Akti\Controllers;

   use Akti\Models\NomeDoModel;

   class NomeController {
       // ...
   }
   ```

3. **Outras classes** (Services, Utils, etc.): Crie o arquivo no diretório correspondente ao namespace mapeado (ex: `app/services/NomeService.php` com `namespace Akti\Services;`).

4. **Nenhum `require_once` é necessário** — o autoloader encontra e carrega a classe automaticamente pelo namespace.

### Regras Obrigatórias
- **NUNCA** adicionar `require_once` para models ou controllers em arquivos PHP. O autoloader cuida disso.
- **SEMPRE** declarar `namespace Akti\Models;` ou `namespace Akti\Controllers;` na primeira linha (após `<?php`) de todo model ou controller novo.
- **SEMPRE** usar `use Akti\Models\NomeClasse;` no topo do controller para importar models.
- **Nas views**, sempre usar FQCN com `\Akti\Models\` para referenciar classes com namespace.
- **Classes globais** (`Database`, `TenantManager`, `SessionGuard`) devem ser referenciadas com `\` prefixo em código com namespace (ex: `new \Database()`).
- O autoloader lança `RuntimeException` se a classe pertence a um namespace mapeado mas o arquivo não existe — isso ajuda a detectar erros de digitação rapidamente.

### Estrutura do index.php
O `index.php` (ponto de entrada) segue esta ordem:

```php
<?php
// 1. Carregar autoloader (session.php, tenant.php, database.php incluídos automaticamente)
require_once __DIR__ . '/app/bootstrap/autoload.php';

// 2. Importar classes usadas no roteamento
use Akti\Controllers\ProductController;
use Akti\Models\User;
// ... demais imports

// 3. session_start() e lógica de roteamento
session_start();
// ... switch/case de rotas
```

## Padrões de Código (Guidelines)

### PHP & MVC
- **Models:** Devem conter apenas lógica de acesso a dados e regras de negócio puras. Recebem `$db` (PDO connection) no construtor.
- **Controllers:** Devem receber as requisições, instanciar models e retornar views. Evitar HTML dentro de controllers.
- **Views:** Devem conter HTML e o mínimo de PHP possível (apenas para exibição de dados: `<?= $variavel ?>`).

### Frontend
- Utilizar classes do **Bootstrap 5** para layout e responsividade.
- Arquivos CSS e JS customizados devem ficar separados em `assets/`.
- **jQuery** deve ser utilizado para manipulação de DOM e requisições AJAX.