# Auditoria PSR-11 — Akti v1

> **Data da Auditoria:** 04/04/2026
> **Escopo:** Avaliação da conformidade PSR-11 (Container Interface) em todo o sistema
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## Resumo Executivo

O sistema Akti **não implementa PSR-11** (`Psr\Container\ContainerInterface`). O que existe é um **DI Container leve** (ARQ-012) implementado no `Router::createController()` via PHP Reflection, que injeta apenas `PDO` nos controllers. A grande maioria dos controllers (86%) ainda usa `new Database()` manualmente.

### Métricas Gerais

| Métrica | Valor |
|---------|-------|
| Controllers analisados | 42 |
| Services analisados | 79 |
| Models analisados | 59 |
| Total de arquivos analisados | 180 |
| `new Database()` em controllers | 38 (90%) |
| Controllers com injection PDO | 1 (2%) |
| Dependências circulares | 0 |
| PSR-11 compliance | **0%** |

### Notas por Aspecto

| Aspecto | Nota | Status |
|---------|------|--------|
| PSR-11 Interface | 0/10 | ❌ Inexistente |
| Injeção de Dependência | 3/10 | ⚠️ Parcial (só PDO via Router) |
| Service Container | 2/10 | ⚠️ Embrionário |
| Services (design) | 9/10 | ✅ Excelente |
| Models (design) | 8/10 | ✅ Bom |
| Testabilidade | 2/10 | ❌ Crítico |
| Acoplamento | 2/10 | ❌ Alto |

---

## Índice de Documentos

| # | Arquivo | Conteúdo |
|---|---------|----------|
| 00 | [README.md](README.md) | Este documento — resumo executivo e índice |
| 01 | [01_ESTADO_ATUAL.md](01_ESTADO_ATUAL.md) | Diagnóstico completo do estado atual da DI |
| 02 | [02_INVENTARIO_CONTROLLERS.md](02_INVENTARIO_CONTROLLERS.md) | Inventário categorizado de todos os 42 controllers |
| 03 | [03_INVENTARIO_SERVICES_MODELS.md](03_INVENTARIO_SERVICES_MODELS.md) | Inventário de 79 services e 59 models |
| 04 | [04_ANALISE_CONFORMIDADE_PSR11.md](04_ANALISE_CONFORMIDADE_PSR11.md) | Análise item a item de conformidade com a PSR-11 |
| 05 | [05_ROADMAP_IMPLEMENTACAO.md](05_ROADMAP_IMPLEMENTACAO.md) | Plano de implementação PSR-11 em 4 fases |

---

## Sprint Imediato — Top 5 Ações

| # | Ação | Severidade | Impacto |
|---|------|------------|---------|
| 1 | Instalar `psr/container` via Composer | ALTO | Habilita tipagem PSR-11 |
| 2 | Criar `app/core/Container.php` com `ContainerInterface` | CRÍTICO | Centralizador de dependências |
| 3 | Registrar PDO, Models e Services no Container | CRÍTICO | Elimina `new Database()` manual |
| 4 | Modificar `Router::createController()` para auto-wiring completo | ALTO | Injeta todas as deps, não só PDO |
| 5 | Converter BaseController para aceitar PDO injetado | ALTO | Template para todos os controllers |

---

## Como Usar Esta Documentação

| Persona | Leitura recomendada |
|---------|-------------------|
| **Desenvolvedor** | 01 (entender estado) → 05 (roadmap de implementação) |
| **Tech Lead** | README (métricas) → 04 (conformidade) → 05 (roadmap) |
| **QA** | 02 + 03 (inventários) para validar cobertura de testes |
