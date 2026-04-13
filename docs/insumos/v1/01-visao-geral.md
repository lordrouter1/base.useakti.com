# 01 — Visão Geral do Módulo de Insumos

## 1. Contexto

O sistema Akti já possui módulos maduros de **Produtos**, **Fornecedores**, **Estoque** e **Pedidos de Compra**. Porém, não há distinção entre "produto acabado" (o que é vendido ao cliente) e "insumo" (matéria-prima utilizada na fabricação/produção do produto).

Atualmente:
- Produtos de consumo interno são cadastrados como produtos normais
- Não existe vínculo formal entre insumos e produtos (BOM)
- Não há controle de consumo de materiais na produção
- Não existe cálculo de necessidade de material (MRP simplificado)

---

## 2. Objetivos

| # | Objetivo | Benefício |
|---|----------|-----------|
| 1 | Cadastro separado de insumos | Separar catálogo de venda do catálogo de produção |
| 2 | Tipos e unidades de medida | Controle preciso (kg, m, L, un, m², etc.) |
| 3 | Vínculo insumo ↔ fornecedor | Saber quem fornece cada material, preço, prazo |
| 4 | Estoque dedicado de insumos | Controlar entrada/saída/mínimo de matéria-prima |
| 5 | BOM (Bill of Materials) | Definir composição de cada produto |
| 6 | Cálculo de consumo | Saber quanto de cada insumo será consumido por pedido |
| 7 | Alertas de estoque baixo | Notificar quando material está abaixo do mínimo |

---

## 3. Escopo — v1

### Incluído nesta versão

- [x] CRUD de insumos (cadastro completo)
- [x] Categorias de insumos
- [x] Vinculação insumo ↔ fornecedor (N:N com metadados)
- [x] Estoque de insumos por armazém
- [x] Movimentações de estoque (entrada, saída, ajuste)
- [x] BOM: vinculação insumo ↔ produto com quantidade
- [x] Listagem com filtros e paginação
- [x] Relatório de estoque crítico

### Fora do escopo v1 (futuro)

- [ ] Cálculo automático de necessidade (MRP)
- [ ] Consumo automático ao mover pedido no pipeline
- [ ] Geração automática de pedido de compra por falta de insumo
- [ ] Custeio de produto baseado em insumos (CPV automático)
- [ ] Importação em massa de insumos (CSV/Excel)
- [ ] Lote e validade de insumos

---

## 4. Personas e Permissões

| Persona | Ações Permitidas |
|---------|-----------------|
| **Admin** | CRUD completo, vincular fornecedores, gerenciar BOM |
| **Compras** | Cadastrar insumos, vincular fornecedores, movimentar estoque |
| **Produção** | Visualizar insumos, consultar BOM, registrar consumo |
| **Estoquista** | Movimentar estoque de insumos, consultar níveis |
| **Visualização** | Apenas leitura (listagens e relatórios) |

---

## 5. Integrações com Módulos Existentes

```
┌─────────────┐     N:N      ┌──────────────┐
│  Fornecedor │◄────────────►│    Insumo     │
│ (suppliers) │              │  (supplies)   │
└─────────────┘              └──────┬────────┘
                                    │
                        ┌───────────┼───────────┐
                        │           │           │
                        ▼           ▼           ▼
                  ┌──────────┐ ┌─────────┐ ┌──────────────┐
                  │ Estoque  │ │   BOM   │ │   Compras    │
                  │ (stock)  │ │(product │ │ (purchase    │
                  │          │ │ supply) │ │  orders)     │
                  └──────────┘ └────┬────┘ └──────────────┘
                                    │
                                    ▼
                              ┌──────────┐
                              │ Produto  │
                              │(products)│
                              └──────────┘
```

---

## 6. Glossário

| Termo | Definição |
|-------|-----------|
| **Insumo** | Matéria-prima ou material de consumo utilizado na fabricação de produtos |
| **BOM** | Bill of Materials — lista de insumos necessários para fabricar um produto |
| **SKU Fornecedor** | Código de referência do insumo no catálogo do fornecedor |
| **Lead Time** | Prazo de entrega do fornecedor para determinado insumo |
| **Estoque Mínimo** | Quantidade mínima que dispara alerta de reposição |
| **Ponto de Pedido** | Quantidade em que se deve fazer novo pedido ao fornecedor |
| **Unidade de Medida** | Como o insumo é quantificado (kg, m, L, un, m², pç, etc.) |
| **Perda/Desperdício** | Percentual de perda esperado no uso do insumo (ex: 5% de aplique) |
