# 01 — Visão Geral e Objetivos

## 1. Introdução

O **Módulo de Insumos v2** é uma evolução do módulo v1, transformando o Akti de um ERP simples para um sistema de **gestão de produção inteligente**. Enquanto o v1 estabeleceu a infraestrutura base (CRUD, fornecedores, estoque, BOM), o v2 adiciona inteligência operacional: previsão, eficiência, automação de custos e suporte completo a fracionamento.

---

## 2. Objetivos Estratégicos

### 2.1 Completar a Base (v1 Pendente)
Antes de implementar funcionalidades novas, é necessário **completar o que o v1 desenhou mas não implementou**:

- Criar `SupplyStockMovementService` — serviço transacional para movimentações
- Completar métodos de vinculação de fornecedores no `Supply` model
- Implementar CMP (Custo Médio Ponderado) real nas entradas
- Ativar lógica FEFO nas saídas
- Completar métodos BOM no model e controller
- Integrar aba "Insumos (BOM)" no formulário de produto

### 2.2 Fracionamento Inteligente
Permitir que cada insumo tenha a flag `permite_fracionamento`. Insumos não fracionáveis terão consumo arredondado para cima (CEIL) quando calculados por lote de produção.

**Exemplo:** Se um produto consome 2.3 parafusos e o lote é de 10 unidades:
- Com fracionamento: baixa 23.0 do estoque
- Sem fracionamento: baixa CEIL(23.0) = 23 (já inteiro neste caso)
- Caso consumo unitário = 0.7: lote 10 → 7.0 (com) vs 7.0 (sem, pois já inteiro)
- Caso consumo unitário = 0.33: lote 10 → 3.3 (com) vs 4 (sem, CEIL)

### 2.3 Ratio de Produção
Definir a proporção de consumo de insumos em relação ao lote de produção. O ratio permite calcular automaticamente a quantidade necessária de cada insumo para produzir N unidades de produto.

### 2.4 Previsão de Ruptura (Forecast)
Cruzar os pedidos em aberto (pipeline) com o estoque atual de insumos, gerando alertas de **ruptura iminente** mesmo que o estoque mínimo não tenha sido atingido.

### 2.5 Gestão de Lotes e Validade (FEFO/FIFO)
Ativar efetivamente a estratégia FEFO (First Expired, First Out) nas saídas de estoque, garantindo que lotes mais próximos do vencimento sejam consumidos primeiro.

### 2.6 Fator de Perda (Waste Factor)
Adicionar campo de **% de perda estimada** no vínculo insumo-produto, permitindo que o sistema desconte automaticamente mais insumo do que o consumo teórico.

### 2.7 Substitutos de Emergência
Permitir cadastro de **insumos substitutos** com prioridade. Se o insumo principal acabar, o sistema sugere o substituto automaticamente.

### 2.8 Custo Médio Automático + Reajuste de Preço
Sempre que entrar nota de compra com preço diferente, recalcular o CMP. Se a margem de lucro do produto final cair abaixo de um limite configurável, sugerir reajuste de preço de venda.

### 2.9 Dashboard de Eficiência
Gráfico comparativo **Previsto vs. Real** por ordem de produção, identificando desperdícios no chão de fábrica.

### 2.10 Suporte a Variações de Produto
A tabela `product_supplies` ganha suporte a `id_variacao` (nullable). Se preenchido, o consumo é específico da variação; se nulo, aplica-se ao produto pai.

---

## 3. Escopo v2

### 3.1 Incluído no Escopo

| Feature | Prioridade | Fase |
|---------|-----------|------|
| Completar SupplyStockMovementService | Crítica | F0 |
| Completar Supply model (fornecedores, BOM, preço) | Crítica | F0 |
| Flag permite_fracionamento + lógica CEIL | Alta | F1 |
| Suporte a variações no BOM (id_variacao) | Alta | F1 |
| Fator de perda por vínculo produto-insumo | Alta | F1 |
| FEFO ativo nas saídas | Alta | F2 |
| CMP automático na entrada | Alta | F2 |
| Substitutos de emergência | Média | F3 |
| Previsão de ruptura (Forecast) | Média | F3 |
| Reajuste automático de preço de venda | Média | F4 |
| Dashboard de eficiência (Previsto vs Real) | Média | F4 |
| Apontamento de consumo real na produção | Média | F4 |
| Testes PHPUnit por fase | Obrigatória | Cada Fase |

### 3.2 Fora do Escopo v2

| Feature | Justificativa |
|---------|--------------|
| Importação CSV/Excel de insumos | Será v3 |
| Geração automática de ordem de compra (PO) | Será v3 |
| Integração com NF-e de entrada (XML) | Será v3 |
| Overhead de produção (custos indiretos) | Será v3 |
| Multi-depósito com regras de prioridade | Será v3 |

---

## 4. Integrações

```
┌──────────────┐     ┌──────────────────┐     ┌────────────────┐
│  Fornecedores │────▶│  INSUMOS (v2)    │◀────│  Produtos      │
│  (suppliers)  │     │                  │     │  (products)    │
└──────────────┘     │  ┌────────────┐  │     │  + variações   │
                     │  │ Estoque    │  │     └────────────────┘
┌──────────────┐     │  │ + FEFO     │  │
│  Pipeline    │────▶│  │ + CMP      │  │     ┌────────────────┐
│  (pedidos)   │     │  └────────────┘  │     │  Financeiro    │
└──────────────┘     │                  │────▶│  (custo prod.) │
                     │  ┌────────────┐  │     └────────────────┘
┌──────────────┐     │  │ Forecast   │  │
│  Produção    │────▶│  │ + Ruptura  │  │     ┌────────────────┐
│  (ordens)    │     │  └────────────┘  │────▶│  Dashboard     │
└──────────────┘     └──────────────────┘     │  Eficiência    │
                                              └────────────────┘
```

---

## 5. Personas e Casos de Uso

| Persona | Casos de Uso Principais |
|---------|------------------------|
| **Gestor de Produção** | Consultar BOM, calcular insumos por lote, verificar forecast de ruptura, analisar eficiência |
| **Estoquista** | Registrar entradas/saídas, consultar FEFO, verificar lotes próximos ao vencimento |
| **Comprador** | Verificar reorder points, consultar substitutos, analisar histórico de preços |
| **Financeiro** | Consultar CMP, verificar impacto no custo de produto, avaliar margens |
| **Operador de Produção** | Apontar consumo real, consultar insumos necessários para ordem |

---

## 6. Critérios de Sucesso

1. **Funcional:** Todas as features do escopo implementadas e testadas
2. **Performance:** Cálculo de forecast para 1000+ pedidos em < 3s
3. **Precisão:** CMP com 4 casas decimais, fracionamento com arredondamento correto
4. **Cobertura de Testes:** ≥ 80% nos services e models novos
5. **Segurança:** Prepared statements, CSRF, escape XSS, validação de input
6. **UX:** Dashboard responsivo, alertas visuais claros, SweetAlert2 para confirmações

---

*Próximo: [02 — Modelo de Dados](02-modelo-dados.md)*
