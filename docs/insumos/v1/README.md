# Módulo de Insumos — Documentação v1

> **Data:** 2026-04-13  
> **Status:** Planejamento  
> **Autor:** Copilot (estudo de viabilidade e arquitetura)

---

## Índice

| Documento | Descrição |
|-----------|-----------|
| [01-visao-geral.md](01-visao-geral.md) | Visão geral do módulo, objetivos e escopo |
| [02-modelo-dados.md](02-modelo-dados.md) | Tabelas, colunas, índices e relacionamentos (ERD) |
| [03-fluxo-cadastro-insumos.md](03-fluxo-cadastro-insumos.md) | Fluxo de cadastro de insumos (CRUD) |
| [04-fluxo-vinculo-fornecedor.md](04-fluxo-vinculo-fornecedor.md) | Vinculação de insumo a fornecedor |
| [05-fluxo-estoque-insumos.md](05-fluxo-estoque-insumos.md) | Estoque de insumos (entrada, saída, movimentações) |
| [06-fluxo-vinculo-produto.md](06-fluxo-vinculo-produto.md) | Vinculação de insumo a produto (BOM — Bill of Materials) |
| [07-arquitetura-tecnica.md](07-arquitetura-tecnica.md) | Implementação: Models, Controllers, Views, Rotas, Menu |
| [08-migrations.md](08-migrations.md) | SQL de migração necessário (prévia) |

---

## Resumo Executivo

O módulo de **Insumos** (matérias-primas / materiais de consumo) permite:

1. **Cadastrar insumos** com tipo, unidade de medida, custo e dados fiscais
2. **Vincular insumos a fornecedores** com preço negociado, SKU, prazo e **fator de conversão de UOM**
3. **Controlar estoque de insumos** em armazéns, com **lote, validade e FEFO** (First Expired, First Out)
4. **Histórico de preços e CMP** — rastrear variações de custo e calcular Custo Médio Ponderado
5. **Vincular insumos a produtos** (BOM) definindo quantidade necessária por unidade produzida
6. **Custeio automático** — recalcular custo do produto baseado no BOM quando o CMP muda
7. **Alertas de reposição (MRP)** — sugestões de compra quando estoque atinge o ponto de pedido
8. **Análise de impacto (Where Used)** — visualizar quais produtos são afetados por mudanças de preço

O módulo se integra nativamente com os módulos já existentes:
- **Fornecedores** (`suppliers`) — relação N:N via tabela pivot
- **Estoque** (`stock`) — reutiliza o sistema de armazéns e movimentações
- **Produtos** (`products`) — BOM (Bill of Materials) para cálculo de consumo
- **Pedidos de Compra** (`purchase_orders`) — compra de insumos
- **Pipeline de Produção** — consumo automático ao produzir
