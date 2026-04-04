# 04 — Análise de Conformidade PSR-11 — Akti

> **Data da Auditoria:** 04/04/2026
> **Referência:** [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)
> **Conformidade Geral:** **0%** (nenhum requisito implementado)

---

## 1. O que é PSR-11?

A PSR-11 define uma **interface mínima** para containers de injeção de dependência em PHP. Ela especifica:

```php
namespace Psr\Container;

interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundExceptionInterface  No entry was found for this identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function get(string $id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;
}
```

Mais duas exceções:

```php
interface ContainerExceptionInterface extends \Throwable {}
interface NotFoundExceptionInterface extends ContainerExceptionInterface {}
```

---

## 2. Checklist de Conformidade PSR-11

### 2.1 Requisitos Obrigatórios (MUST)

| # | Requisito PSR-11 | Status | Evidência |
|---|------------------|--------|-----------|
| R1 | O pacote `psr/container` deve estar instalado | ❌ Ausente | `composer.json` não lista `psr/container` |
| R2 | Deve existir uma classe que implemente `ContainerInterface` | ❌ Ausente | Nenhuma classe implementa a interface |
| R3 | `get($id)` deve retornar a entrada pelo identificador | ❌ Ausente | Nenhum método `get()` de container existe |
| R4 | `get($id)` deve lançar `NotFoundExceptionInterface` se ID não existir | ❌ Ausente | Nenhuma exceção PSR-11 definida |
| R5 | `has($id)` deve retornar `true` se o container pode resolver o ID | ❌ Ausente | Nenhum método `has()` existe |
| R6 | `has($id)` deve retornar `false` se o container não pode resolver | ❌ Ausente | — |
| R7 | Se `has($id)` retorna `true`, `get($id)` NÃO deve lançar `NotFoundExceptionInterface` | ❌ N/A | — |
| R8 | `get($id)` PODE lançar `ContainerExceptionInterface` em erros gerais | ❌ N/A | — |

**Resultado: 0/8 requisitos atendidos**

### 2.2 Recomendações (SHOULD)

| # | Recomendação PSR-11 | Status | Notas |
|---|---------------------|--------|-------|
| S1 | Identificadores SHOULD ser strings (normalmente FQCNs) | ❌ N/A | Sem container |
| S2 | Duas chamadas a `get()` com o mesmo ID SHOULD retornar o mesmo valor | ❌ N/A | Sem container |
| S3 | Entradas SHOULD ser lazy-loaded quando possível | ❌ N/A | Sem container |

### 2.3 Opcional (MAY)

| # | Opcional PSR-11 | Status | Notas |
|---|-----------------|--------|-------|
| O1 | Container MAY suportar delegação (delegate lookup) | ❌ N/A | Sem container |
| O2 | Container MAY implementar métodos além de `get()`/`has()` | ❌ N/A | Sem container |

---

## 3. Análise do "Container Leve" Existente vs. PSR-11

O `Router::createController()` faz algo semelhante a um container, mas **não é PSR-11 compliant**:

| Capacidade | PSR-11 Exige | Router Implementa | Gap |
|------------|-------------|-------------------|-----|
| Interface tipada | `ContainerInterface` | Nenhuma | ❌ Falta interface |
| Método `get($id)` | Sim | Não existe | ❌ Usa método privado |
| Método `has($id)` | Sim | Não existe | ❌ |
| Resolução por FQCN | Recomendado | Sim (ReflectionClass) | ✅ |
| Resolução recursiva | Implícito | Não (só PDO) | ❌ Resolve 1 tipo |
| Singleton support | Recomendado (SHOULD same value) | Via Database::getInstance() | ⚠️ Parcial |
| Lazy loading | MAY | Não | ❌ Instancia eager |
| Exceções tipadas | `NotFoundExceptionInterface` | Nenhuma (fallback silencioso) | ❌ |
| Registro de bindings | Necessário | Nenhum | ❌ Hardcoded PDO |
| Escopo public/acessível | Interface pública | Método `private` | ❌ Inacessível |

---

## 4. Gap Analysis — O que Falta para PSR-11

### 4.1 Infraestrutura Ausente

| # | Item | Prioridade | Esforço |
|---|------|------------|---------|
| GAP-01 | Instalar `psr/container` via Composer | CRÍTICO | 1 min |
| GAP-02 | Criar classe `Container` que implemente `ContainerInterface` | CRÍTICO | 2-4h |
| GAP-03 | Criar `ContainerException` (implements `ContainerExceptionInterface`) | ALTO | 15 min |
| GAP-04 | Criar `NotFoundException` (implements `NotFoundExceptionInterface`) | ALTO | 15 min |
| GAP-05 | Implementar `get(string $id): mixed` | CRÍTICO | 1-2h |
| GAP-06 | Implementar `has(string $id): bool` | CRÍTICO | 30 min |
| GAP-07 | Implementar registro de bindings (service map) | ALTO | 2-3h |
| GAP-08 | Implementar auto-wiring via Reflection (recursivo) | ALTO | 3-4h |
| GAP-09 | Implementar suporte a singleton/shared services | MÉDIO | 1h |
| GAP-10 | Implementar factory callbacks | MÉDIO | 1h |
| GAP-11 | Integrar Container no Application bootstrap | ALTO | 2h |
| GAP-12 | Modificar Router para usar Container em vez de Reflection próprio | ALTO | 1-2h |

### 4.2 Refatoração de Código Consumer

| # | Item | Prioridade | Esforço | Escopo |
|---|------|------------|---------|--------|
| GAP-13 | Adicionar `PDO` type-hint em 42 models | BAIXO | 1-2h | 42 arquivos |
| GAP-14 | Refatorar BaseController para aceitar PDO injetado | ALTO | 30 min | 1 arquivo |
| GAP-15 | Refatorar 35 controllers Cat.B para PDO injection | MÉDIO | 4-8h | 35 arquivos |
| GAP-16 | Refatorar 2 controllers Cat.C para padrão consistente | BAIXO | 30 min | 2 arquivos |
| GAP-17 | Registrar Models no container | MÉDIO | 1-2h | 1 arquivo de config |
| GAP-18 | Registrar Services no container | MÉDIO | 1-2h | 1 arquivo de config |

---

## 5. Comparação: Estado Atual vs. PSR-11 Ideal

```
╔══════════════════════════════════════════════════════════════════╗
║                    ESTADO ATUAL                                  ║
╠══════════════════════════════════════════════════════════════════╣
║                                                                  ║
║  index.php ─► Application ─► Router::dispatch()                  ║
║                                  │                               ║
║                    createController() ◄── Reflection (PDO only)  ║
║                          │                                       ║
║                     Controller.__construct()                     ║
║                          │                                       ║
║                   new Database() ◄── ACOPLAMENTO FORTE           ║
║                   new Model($db) ◄── WIRING MANUAL               ║
║                   new Service($db, $model, ...) ◄── BOILERPLATE  ║
║                                                                  ║
╚══════════════════════════════════════════════════════════════════╝

                          ▼ ▼ ▼

╔══════════════════════════════════════════════════════════════════╗
║                    IDEAL PSR-11                                  ║
╠══════════════════════════════════════════════════════════════════╣
║                                                                  ║
║  index.php ─► Container::build()                                 ║
║                  │ PDO → Database::getInstance()                 ║
║                  │ Models → auto-wire (PDO injection)            ║
║                  │ Services → auto-wire (PDO + Models)           ║
║                  │                                               ║
║              Application($container)                             ║
║                  ─► Router($container)                           ║
║                       ─► $container->get(Controller::class)      ║
║                            │ Auto-resolve: PDO → Models →        ║
║                            │ Services → Controller               ║
║                            │                                     ║
║                       ─► $controller->$action()                  ║
║                                                                  ║
║  Controller::__construct(PDO $db, Model $m, Service $s)          ║
║  ◄── INJETADO PELO CONTAINER · ZERO BOILERPLATE                 ║
║                                                                  ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## 6. Riscos da Não-Implementação

| Risco | Severidade | Impacto |
|-------|-----------|---------|
| **Testabilidade nula** para controllers | CRÍTICO | Impossível mockar PDO em 41/42 controllers |
| **Boilerplate crescente** | ALTO | Cada novo controller repete 10-25 linhas de wiring |
| **Acoplamento forte** | ALTO | Mudança em Database impacta 38 controllers |
| **Violação SRP** | MÉDIO | Controllers sabem como construir suas dependências |
| **Não-conformidade** com padrões PHP-FIG | BAIXO | Sem interop com libs que esperam PSR-11 |
| **Debugging complexo** | MÉDIO | Cada controller pode ter ordem diferente de init |
| **Memory leak potencial** | BAIXO | `new Database()` em cada controller (mitigado pelo singleton) |

---

## 7. Pontos Fortes do Estado Atual

| Aspecto | Avaliação |
|---------|-----------|
| Services 100% com constructor injection | 🟢 Prontos para container |
| Models 100% aceitam PDO | 🟢 Prontos para container |
| Sem dependências circulares | 🟢 Grafo limpo |
| Database singleton funcional | 🟢 Base para binding no container |
| Reflection no Router já existe | 🟢 Pode ser expandida |
| HealthController como template | 🟢 Padrão provado |
