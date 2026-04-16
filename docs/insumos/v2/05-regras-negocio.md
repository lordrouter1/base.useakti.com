# 05 — Regras de Negócio

## 1. Fracionamento Inteligente

### 1.1 Definição

Cada insumo possui a flag `permite_fracionamento` que controla se o consumo pode ser em frações ou deve ser arredondado para cima (inteiro).

### 1.2 Lógica

```
SE permite_fracionamento = TRUE:
    consumo_efetivo = ROUND(consumo_calculado, decimal_precision)

SE permite_fracionamento = FALSE:
    consumo_efetivo = CEIL(consumo_calculado)
```

### 1.3 Exemplos

| Insumo | Fracionável | Consumo Unitário | Lote | Perda | Consumo Calculado | Consumo Efetivo |
|--------|-------------|-----------------|------|-------|-------------------|-----------------|
| Tinta (L) | Sim | 0.15 L | 100 | 10% | 16.5000 L | 16.5000 L |
| Parafuso (un) | Não | 4.5 un | 100 | 0% | 450.0000 | 450 un |
| Tecido (m) | Sim | 1.2 m | 50 | 8% | 64.8000 m | 64.8000 m |
| Botão (un) | Não | 0.33 un | 10 | 0% | 3.3000 | 4 un (CEIL) |
| Cola (mL) | Sim | 5.7 mL | 20 | 5% | 119.7000 mL | 119.7000 mL |
| Rebite (un) | Não | 2.7 un | 15 | 0% | 40.5000 | 41 un (CEIL) |

### 1.4 Implementação (Pseudocódigo)

```php
function calculateEffectiveQuantity(
    float $baseQtyPerUnit,
    int $lotSize,
    float $lossPercent,
    bool $allowsFractionation,
    int $precision = 4
): float {
    // 1. Consumo base total
    $total = $baseQtyPerUnit * $lotSize;

    // 2. Adicionar fator de perda
    $withLoss = $total * (1 + $lossPercent / 100);

    // 3. Aplicar fracionamento
    if ($allowsFractionation) {
        return round($withLoss, $precision);
    }

    return ceil($withLoss);
}
```

---

## 2. Ratio de Produção

### 2.1 Conceito

O **Ratio** é a relação de consumo de um insumo por unidade de produto. Ele vem do campo `quantity` da tabela `product_supplies` (BOM).

### 2.2 Cálculos

```
consumo_para_lote = ratio × tamanho_lote × (1 + perda% / 100)
```

**Com variação:**
```
insumos_totais = insumos_do_produto_pai + insumos_específicos_da_variação

SE a variação tem insumo X E o pai também tem insumo X:
    → Usa SOMENTE o da variação (override)

SE a variação NÃO tem insumo X mas o pai tem:
    → Herda do pai
```

### 2.3 Exemplo Completo

**Produto: Camiseta Algodão (Preço: R$ 59.90)**

BOM do Produto Pai:
| Insumo | Ratio | Perda | Fracionável |
|--------|-------|-------|-------------|
| Tecido Algodão | 1.50 m | 10% | Sim |
| Linha Costura | 25.0 m | 5% | Sim |
| Etiqueta | 1 un | 0% | Não |

BOM específico Variação "Tamanho G" (override do pai):
| Insumo | Ratio | Perda | Fracionável |
|--------|-------|-------|-------------|
| Tecido Algodão | 1.80 m | 10% | Sim |
| Botão Extra | 2 un | 0% | Não |

**Cálculo para Lote = 50 unidades (Tamanho G):**

| Insumo | Origem | Ratio | Lote | Perda | Cálculo | Efetivo |
|--------|--------|-------|------|-------|---------|---------|
| Tecido Algodão | Variação (override) | 1.80 | 50 | 10% | 1.80 × 50 × 1.10 = 99.00 | 99.0000 m |
| Linha Costura | Pai (herdado) | 25.0 | 50 | 5% | 25.0 × 50 × 1.05 = 1312.50 | 1312.5000 m |
| Etiqueta | Pai (herdado) | 1 | 50 | 0% | 1 × 50 × 1.00 = 50 | 50 un |
| Botão Extra | Variação (novo) | 2 | 50 | 0% | 2 × 50 × 1.00 = 100 | 100 un |

---

## 3. FEFO (First Expired, First Out)

### 3.1 Lógica de Seleção

Ao processar uma saída ou consumo de produção, o sistema seleciona lotes na seguinte ordem:

```sql
SELECT *
FROM supply_stock_items
WHERE supply_id = :supply_id
  AND warehouse_id = :warehouse_id
  AND quantity > 0
ORDER BY
    CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END,  -- Com validade primeiro
    expiry_date ASC,                                     -- Menor validade primeiro
    created_at ASC                                       -- Mais antigo primeiro (FIFO fallback)
```

### 3.2 Consumo Parcial de Lotes

Se um lote não cobre a quantidade total, o sistema consome parcialmente e avança para o próximo:

```
Necessário: 25.0 L de Tinta Azul

Lote A (vencimento 2026-05-01): 10.0 L → Consome tudo (resta 0)
Lote B (vencimento 2026-06-15): 30.0 L → Consome 15.0 L (resta 15.0)

Total consumido: 25.0 L ✓
Movimentos registrados: 2 (um para cada lote)
```

### 3.3 Estratégia Configurável

A tabela `supply_settings` permite trocar a estratégia por tenant:

| Estratégia | Ordenação |
|-----------|-----------|
| `fefo` | `expiry_date ASC, created_at ASC` (padrão) |
| `fifo` | `created_at ASC` |
| `manual` | Operador seleciona o lote manualmente |

---

## 4. CMP (Custo Médio Ponderado)

### 4.1 Fórmula

```
novo_CMP = (estoque_atual × CMP_atual + qtd_entrada × preço_entrada) / (estoque_atual + qtd_entrada)
```

### 4.2 Exemplo

```
Estado atual:
  Estoque: 100 un
  CMP: R$ 5.00

Entrada:
  Quantidade: 50 un
  Preço: R$ 6.50

Cálculo:
  novo_CMP = (100 × 5.00 + 50 × 6.50) / (100 + 50)
  novo_CMP = (500.00 + 325.00) / 150
  novo_CMP = 825.00 / 150
  novo_CMP = R$ 5.5000
```

### 4.3 Regras

1. CMP é recalculado **somente em entradas** (compras, devoluções de cliente, ajustes positivos)
2. Saídas **não** alteram o CMP — apenas reduzem a quantidade
3. Se `auto_recalculate_cmp = false` nas configurações, o CMP não é atualizado automaticamente
4. O CMP é registrado em `supply_price_history` com source = `cmp_calculado`
5. Precisão mínima: 4 casas decimais

### 4.4 Conversão de Fornecedor

Quando a entrada vem de um fornecedor com fator de conversão:

```
Fornecedor vende: 1 caixa = R$ 100.00
Fator de conversão: 1 caixa = 50 unidades

Quantidade comprada: 5 caixas
→ Quantidade em estoque: 5 × 50 = 250 un
→ Preço unitário: R$ 100.00 / 50 = R$ 2.00/un

CMP será calculado com: qtd = 250, preço = R$ 2.00
```

---

## 5. Previsão de Ruptura (Forecast)

### 5.1 Algoritmo

```
PARA cada insumo ativo:
    1. consultar estoque total (todos depósitos)
    2. consultar pedidos em aberto no pipeline
    3. PARA cada pedido:
        calcular BOM → somar consumo deste insumo
    4. comprometido = soma dos consumos
    5. disponível = estoque - comprometido
    6. média_consumo_diário = consumo dos últimos 30 dias / 30
    7. SE disponível ≤ 0:
        status = 'ruptured', dias = 0
    SENÃO SE média_consumo_diário > 0:
        dias_até_ruptura = disponível / média_consumo_diário
        SE dias ≤ 3: status = 'critical'
        SENÃO SE dias ≤ 7: status = 'warning'
        SENÃO: status = 'ok'
    SENÃO:
        status = 'ok', dias = NULL (sem consumo recente)

    SALVAR em supply_rupture_forecasts
```

### 5.2 Quando Recalcular

- Após cada **movimentação de estoque** (entrada, saída, ajuste)
- Após **criação/alteração de pedido** no pipeline
- Sob demanda pelo dashboard (botão "Recalcular")
- Periodicamente por cron (1x ao dia, madrugada)

### 5.3 Status e Prioridade

| Status | Cor | Condição | Ação Sugerida |
|--------|-----|----------|--------------|
| `ruptured` | Vermelho | `disponível ≤ 0` | Compra urgente ou usar substituto |
| `critical` | Laranja | `dias ≤ 3` | Iniciar processo de compra |
| `warning` | Amarelo | `dias ≤ 7` | Monitorar e planejar compra |
| `ok` | Verde | `dias > 7` | Sem ação necessária |

---

## 6. Substitutos de Emergência

### 6.1 Fluxo de Substituição

```
1. Consumo de produção solicitado para Insumo A (50 un)
2. Estoque de A = 30 un (insuficiente)
3. Sistema busca substitutos ativos por prioridade:
   - Substituto 1: Insumo B (conversão 1.2x, prio 1) → estoque 100 un
4. Cálculo: 50 un × 1.2 = 60 un de B necessários
5. Estoque de B = 100 → suficiente ✓
6. Sistema sugere ao operador:
   "Estoque insuficiente de Insumo A (faltam 20 un).
    Substituto disponível: Insumo B (×1.2) — 100 un em estoque.
    Consumir 30 un de A + 24 un de B?"
7. Se aceito:
   - Baixa 30 un de A (tudo disponível)
   - Baixa 24 un de B (20 faltantes × 1.2)
   - Registra dois movimentos de consumo
   - Log indica substituição parcial
```

### 6.2 Regras

1. Substitutos são ordenados por `priority ASC` (menor = mais prioritário)
2. O `conversion_rate` converte na direção: `qty_principal × rate = qty_substituto`
3. Substituição pode ser **parcial**: consumir o que tem do principal + completar com substituto
4. O operador **sempre** recebe confirmação antes da substituição (nunca automático)
5. A substituição é registrada no `production_consumption_log` com referência ao insumo original

---

## 7. Alertas de Custo e Margem

### 7.1 Fluxo

```
1. Entrada de insumo com preço diferente
2. SupplyStockMovementService recalcula CMP
3. SupplyCostService.checkMarginImpact() é chamado
4. Para cada produto que usa este insumo na BOM:
   a. Recalcular custo de produção
   b. Comparar com preço de venda atual
   c. Calcular nova margem
   d. SE nova_margem < min_margin_threshold (config do tenant):
      → Criar registro em supply_cost_alerts
      → Calcular preço sugerido: custo_producao / (1 - margem_minima/100)
```

### 7.2 Fórmula de Margem

```
margem% = ((preço_venda - custo_produção) / preço_venda) × 100

preço_sugerido = custo_produção / (1 - margem_mínima / 100)
```

**Exemplo:**
```
Custo produção antigo: R$ 18.40
Custo produção novo:   R$ 21.15
Preço venda:           R$ 24.00
Margem mínima config:  15%

Margem anterior: (24.00 - 18.40) / 24.00 × 100 = 23.3%
Margem nova:     (24.00 - 21.15) / 24.00 × 100 = 11.9% ⚠️ < 15%

Preço sugerido: 21.15 / (1 - 0.15) = R$ 24.88
```

### 7.3 Ações sobre o Alerta

| Ação | Efeito |
|------|--------|
| **Reconhecer** | Marca como visto, sem alterar preço |
| **Aplicar** | Atualiza o preço de venda do produto para o sugerido |
| **Dispensar** | Ignora o alerta (decisão consciente de manter margem reduzida) |

---

## 8. Dashboard de Eficiência

### 8.1 Métricas Calculadas

```
eficiência_global% = (total_previsto / total_real) × 100
variação_média% = AVG(variance_percent) de production_consumption_log
custo_perda = Σ(ABS(variance) × CMP_insumo) onde variance > 0
```

### 8.2 Dados de Agregação

| Dimensão | Query |
|----------|-------|
| Por período (semana/mês) | `GROUP BY YEARWEEK(created_at)` ou `YEAR(created_at), MONTH(created_at)` |
| Por insumo | `GROUP BY supply_id` |
| Por produto | `GROUP BY product_id` |
| Por ordem | `WHERE order_id = :id` |

### 8.3 Interpretação

| Variação | Interpretação | Ação |
|----------|--------------|------|
| 0% | Consumo perfeito | — |
| 1% a 5% | Dentro do esperado | Monitorar |
| 5% a 10% | Atenção — possível ineficiência | Investigar processo |
| > 10% | Crítico — desperdício significativo | Revisar BOM e/ou processo |
| Negativo | Economia (consumiu menos) | Validar se BOM está superdimensionada |

---

## 9. Validações de Entrada (por endpoint)

### 9.1 Cadastro de Insumo

| Campo | Validação |
|-------|-----------|
| `name` | Obrigatório, 2-200 chars |
| `code` | Obrigatório, único, formato `INS-XXXX` |
| `unit_measure` | Obrigatório, ENUM válido |
| `cost_price` | Numérico ≥ 0, DECIMAL(12,4) |
| `min_stock` | Numérico ≥ 0 |
| `waste_percent` | Numérico 0-100, DECIMAL(5,2) |
| `permite_fracionamento` | Boolean (0 ou 1) |
| `decimal_precision` | Inteiro 2-6 |

### 9.2 Vínculo BOM

| Campo | Validação |
|-------|-----------|
| `product_id` | Obrigatório, FK válida |
| `supply_id` | Obrigatório, FK válida |
| `variation_id` | Opcional, FK válida se preenchido |
| `quantity` | Obrigatório, > 0, DECIMAL(12,4) |
| `loss_percent` | Numérico 0-100 |
| Unicidade | `(product_id, variation_id, supply_id)` |

### 9.3 Substituto

| Campo | Validação |
|-------|-----------|
| `substitute_id` | Obrigatório, FK válida, ≠ supply_id |
| `conversion_rate` | Obrigatório, > 0, DECIMAL(12,6) |
| `priority` | Inteiro ≥ 1 |
| Unicidade | `(supply_id, substitute_id)` |

### 9.4 Movimentação de Estoque

| Campo | Validação |
|-------|-----------|
| `warehouse_id` | Obrigatório, FK válida |
| `supply_id` | Obrigatório, FK válida |
| `quantity` | Obrigatório, > 0 |
| `type` | ENUM: 'entrada', 'saida', 'ajuste', 'transferencia', 'consumo_producao' |
| `unit_price` | Obrigatório para entrada, ≥ 0 |
| `batch_number` | Opcional, max 50 chars |
| `expiry_date` | Opcional, formato DATE válido, ≥ hoje (para entradas) |
| Estoque | Para saída: verificar disponibilidade (ou config permite negativo) |

---

*Anterior: [04 — Frontend/UI](04-frontend-ui.md) | Próximo: [06 — Roadmap](06-roadmap.md)*
