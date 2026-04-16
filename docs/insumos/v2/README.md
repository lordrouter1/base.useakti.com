# Módulo de Insumos v2 — Engenharia de Produto e Gestão Inteligente

> **Expansão do módulo de insumos** para controle de estoque fracionado, Ratio de produção, previsão de ruptura e integração avançada com variações de produto.

---

## Índice de Documentação

| # | Documento | Descrição |
|---|-----------|-----------|
| 01 | [Visão Geral e Objetivos](01-visao-geral.md) | Escopo, objetivos, o que muda em relação ao v1 |
| 02 | [Modelo de Dados](02-modelo-dados.md) | Novas tabelas, alterações em tabelas existentes, ERD |
| 03 | [Arquitetura Backend](03-arquitetura-backend.md) | Services, Models, Controllers — novos e modificados |
| 04 | [Especificação Frontend/UI](04-frontend-ui.md) | Telas, componentes, interações, mockups textuais |
| 05 | [Regras de Negócio](05-regras-negocio.md) | Lógica de fracionamento, FEFO, CMP, ratio, ruptura |
| 06 | [Roadmap de Implementação](06-roadmap.md) | Fases detalhadas com dependências e critérios de aceite |

---

## Contexto

O módulo de Insumos **v1** implementou:
- ✅ CRUD de insumos com categorias
- ✅ Vinculação N:N com fornecedores (fator de conversão)
- ✅ Controle de estoque por depósito (dashboard, entradas, saídas, transferências, ajustes)
- ✅ Rastreabilidade por lote e validade (estrutura FEFO)
- ✅ Histórico de preços e CMP
- ✅ BOM (Bill of Materials) — composição de produto
- ✅ Análise "Onde é Usado"
- ✅ Rotas, menus e permissões configurados

### Status Atual da Implementação v1

| Componente | Completude | Observação |
|------------|-----------|------------|
| Schema (7 tabelas) | 100% | Todas criadas e aplicadas |
| Supply Model | ~60% | CRUD + categorias. Faltam: supplier linking methods, BOM methods, price history |
| SupplyStock Model | ~40% | Dashboard + items. Faltam: movimentos, FEFO, CMP |
| SupplyController | ~80% | CRUD completo. Faltam: ações de fornecedor, BOM AJAX |
| SupplyStockController | ~50% | Views existem, lógica parcial |
| SupplyStockMovementService | 0% | Não criado — crítico para v2 |
| Views | ~70% | Estrutura base pronta, refinamentos necessários |

---

## O que o v2 adiciona

1. **Fracionamento Inteligente** — Flag `permite_fracionamento` com arredondamento CEIL
2. **Ratio de Produção** — Consumo calculado por lote de produção
3. **Previsão de Ruptura (Forecast)** — Cruzamento pedidos × estoque
4. **Fator de Perda (Waste Factor)** — % de perda na composição do produto
5. **Substitutos de Emergência** — Insumos alternativos com prioridade
6. **Custo Médio Automático** — Recálculo CMP ao receber nota + impacto no preço de venda
7. **Dashboard de Eficiência** — Previsto vs. Real por ordem de produção
8. **Suporte a Variações** — BOM por variação de produto (id_variacao nullable)
9. **Completar implementação v1** — Finalizar services, models e controllers pendentes

---

## Stack Técnica

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.1+ (PSR-4, MVC) |
| Services | `InsumoService`, `ProducaoService`, `SupplyStockMovementService` |
| Banco | MySQL/MariaDB (InnoDB, utf8mb4) |
| Frontend | Bootstrap 5, jQuery, Chart.js, SweetAlert2, Select2 |
| Testes | PHPUnit |

---

## Pré-requisitos

- Módulo de Insumos v1 com schema aplicado
- Módulo de Fornecedores (`suppliers`) operacional
- Módulo de Produtos (`products`) com suporte a variações
- Módulo de Pipeline/Pedidos para integração de consumo

---

*Criado em: 2026-04-16*
*Versão: 2.0*
