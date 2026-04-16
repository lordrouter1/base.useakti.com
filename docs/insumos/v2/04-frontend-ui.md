# 04 — Especificação Frontend / UI

## 1. Telas Existentes — Modificações

### 1.1 Formulário de Insumo (`supplies/form.php`)

**Alterações na aba "Dados Básicos":**

```
┌─────────────────────────────────────────────────────────────────┐
│  Cadastro de Insumo                                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─── Dados Básicos ───────────────────────────────────────┐   │
│  │  Código: [INS-0042]    Nome: [____________]             │   │
│  │  Categoria: [▼ Select2]  [+ Nova Categoria]             │   │
│  │  Unidade: [▼ kg/m/L/un...]                              │   │
│  │  Descrição: [_________________________________]         │   │
│  │                                                         │   │
│  │  ┌─── Fracionamento (v2) ────────────────────────┐     │   │
│  │  │  [✓] Permite Fracionamento                     │     │   │
│  │  │  Precisão Decimal: [▼ 2 | 3 | 4* | 5 | 6]    │     │   │
│  │  │  ℹ️ Se desativado, consumo será arredondado    │     │   │
│  │  │  para cima (CEIL) em cálculos de produção.     │     │   │
│  │  └────────────────────────────────────────────────┘     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  [Dados Básicos] [Custos] [Fiscal] [Fornecedores] [Substitutos]│
│                                      ↑ v1 tab      ↑ v2 tab    │
└─────────────────────────────────────────────────────────────────┘
```

**Nova aba: "Substitutos" (v2)**

```
┌─── Substitutos de Emergência ──────────────────────────────────┐
│                                                                 │
│  ┌──────┬──────────────┬──────────┬──────────┬────────┬──────┐ │
│  │ Prio │ Insumo       │ Conversão│ Estoque  │ Status │ Ação │ │
│  ├──────┼──────────────┼──────────┼──────────┼────────┼──────┤ │
│  │  1   │ Tinta B (L)  │ 1.20x    │ 45.5 L   │ ● Ativo│ ✏️🗑│ │
│  │  2   │ Tinta C (L)  │ 1.05x    │ 12.0 L   │ ● Ativo│ ✏️🗑│ │
│  └──────┴──────────────┴──────────┴──────────┴────────┴──────┘ │
│                                                                 │
│  [+ Adicionar Substituto]                                       │
│                                                                 │
│  ℹ️ Em caso de ruptura do insumo principal, o sistema sugere    │
│  automaticamente o substituto mais prioritário com estoque.     │
└─────────────────────────────────────────────────────────────────┘
```

**Modal: Adicionar Substituto (SweetAlert2)**

```
┌─── Adicionar Substituto ──────────────────────┐
│                                                 │
│  Insumo Substituto: [▼ Select2 — busca AJAX]  │
│                                                 │
│  Taxa de Conversão:                            │
│  1 [un] do principal = [1.00] [un] substituto  │
│  ℹ️ Ex: 1 L de Tinta A = 1.2 L de Tinta B    │
│                                                 │
│  Prioridade: [▼ 1 (mais alta) | 2 | 3 | ...]  │
│                                                 │
│  Observações: [_____________________________]  │
│                                                 │
│           [Cancelar]  [Salvar]                  │
└─────────────────────────────────────────────────┘
```

### 1.2 Formulário de Produto — Aba BOM (`products/form.php`)

**Nova aba "Insumos (BOM)" no edit de produto:**

```
┌─── Insumos (BOM) — Composição do Produto ──────────────────────┐
│                                                                  │
│  Variação: [▼ Produto Pai (todos) | Tamanho P | Tamanho M | ...]│
│                                                                  │
│  ┌──────┬──────────────┬──────┬───────┬───────┬──────┬────────┐ │
│  │ #    │ Insumo       │ Qtd  │ Perda │ Custo │ Opc? │ Ação   │ │
│  ├──────┼──────────────┼──────┼───────┼───────┼──────┼────────┤ │
│  │ 1    │ Tecido Algod.│ 1.50m│ 10%   │R$15.00│      │ ✏️🗑   │ │
│  │ 2    │ Linha Costura│ 25.0m│ 5%    │R$ 2.10│      │ ✏️🗑   │ │
│  │ 3    │ Botão Metal  │ 6 un │ 0%    │R$ 4.80│      │ ✏️🗑   │ │
│  │ 4    │ Etiqueta     │ 1 un │ 0%    │R$ 0.50│  ✓   │ ✏️🗑   │ │
│  ├──────┴──────────────┴──────┴───────┴───────┴──────┴────────┤ │
│  │ CUSTO TOTAL INSUMOS (obrigatórios):          R$ 21.90      │ │
│  │ CUSTO COM OPCIONAIS:                         R$ 22.40      │ │
│  │ Preço de Venda: R$ 59.90 │ Margem: 63.4%                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  [+ Adicionar Insumo]                                            │
│                                                                  │
│  💡 Selecione uma variação para ver/editar composição específica.│
│  Insumos do "Produto Pai" são herdados por todas as variações.  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 2. Telas Novas

### 2.1 Dashboard de Previsão de Ruptura (`supply_stock/forecast.php`)

```
┌─────────────────────────────────────────────────────────────────┐
│  🔮 Previsão de Ruptura de Estoque                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─ KPIs ──────────────────────────────────────────────────┐   │
│  │ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────┐│   │
│  │ │   🔴 3     │ │   🟡 7     │ │   🟢 142   │ │ 📊 85% ││   │
│  │ │ Ruptura    │ │ Crítico    │ │ OK         │ │Cobertura││   │
│  │ │ Imediata   │ │ < 7 dias   │ │ Estoque ok │ │ Geral  ││   │
│  │ └────────────┘ └────────────┘ └────────────┘ └────────┘│   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Filtros: [▼ Status] [▼ Categoria] [▼ Depósito] [🔍 Buscar]   │
│                                                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Insumo          │Estoque│Comprometido│Disponível│Dias│ St │ │
│  ├─────────────────┼───────┼────────────┼──────────┼────┼────┤ │
│  │🔴 Tecido Algod. │ 15.5m │   42.0m    │  -26.5m  │ 0  │RUPT│ │
│  │🔴 Tinta Azul    │  3.2L │    8.5L    │   -5.3L  │ 0  │RUPT│ │
│  │🔴 Parafuso M6   │  120  │   350      │   -230   │ 0  │RUPT│ │
│  │🟡 Linha Cost.   │  80m  │   65.0m    │   15.0m  │ 3  │CRIT│ │
│  │🟡 Botão Metal   │  200  │   180      │    20    │ 5  │CRIT│ │
│  │🟢 Etiqueta      │ 1500  │   320      │  1180    │ 45 │ OK │ │
│  └─────────────────┴───────┴────────────┴──────────┴────┴────┘ │
│                                                                 │
│  Ao clicar na linha, abre detalhe:                             │
│  ┌─ Detalhe: Tecido Algodão ──────────────────────────────┐   │
│  │ Pedidos que demandam:                                    │   │
│  │  • Pedido #1234 — Camiseta P (×50) → 75.0m             │   │
│  │  • Pedido #1238 — Camiseta M (×30) → 52.5m             │   │
│  │                                                          │   │
│  │ Substitutos disponíveis:                                 │   │
│  │  1. Tecido Misto (×1.1) — 200m em estoque ✅           │   │
│  │                                                          │   │
│  │ Sugestão: Comprar 130m do fornecedor principal           │   │
│  │ (Lead time: 5 dias úteis)                               │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Dashboard de Eficiência (`supply_dashboard/efficiency.php`)

```
┌─────────────────────────────────────────────────────────────────┐
│  📊 Eficiência de Insumos — Previsto vs Real                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Período: [▼ Últimos 30 dias] Produto: [▼ Todos] [Filtrar]    │
│                                                                 │
│  ┌─ KPIs ──────────────────────────────────────────────────┐   │
│  │ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────┐│   │
│  │ │ 📦 342     │ │ 📈 +2.3%   │ │ 💰 R$1.2k  │ │ 🏆 94% ││   │
│  │ │ Ordens     │ │ Variação   │ │ Perda em $  │ │Eficiên.││   │
│  │ │ no período │ │ Média      │ │ Estimada    │ │ Global ││   │
│  │ └────────────┘ └────────────┘ └────────────┘ └────────┘│   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ Gráfico Chart.js ─────────────────────────────────────┐   │
│  │                                                         │   │
│  │  Previsto ████████████████████████████ 100%             │   │
│  │  Real     ██████████████████████████████ 102.3%         │   │
│  │                                                         │   │
│  │  (Gráfico de barras agrupadas por semana/mês)           │   │
│  │  Eixo X: período | Eixo Y: quantidade total consumida   │   │
│  │  Série 1: Previsto (azul) | Série 2: Real (verde/verm.) │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ Top 10 Maiores Desperdícios ──────────────────────────┐   │
│  │ Insumo          │Previsto│  Real  │Variação│Custo Perda│   │
│  ├─────────────────┼────────┼────────┼────────┼───────────│   │
│  │ Tecido Algodão  │ 150.0m │ 162.5m │ +8.3%  │ R$ 125.00│   │
│  │ Tinta Vermelha  │  25.0L │  27.2L │ +8.8%  │ R$  44.00│   │
│  │ Verniz Brilho   │  10.0L │  10.5L │ +5.0%  │ R$  15.00│   │
│  └─────────────────┴────────┴────────┴────────┴───────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2.3 Alertas de Custo (`supplies/cost_alerts.php`)

```
┌─────────────────────────────────────────────────────────────────┐
│  💲 Alertas de Custo — Impacto na Margem                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Filtros: [▼ Status: Pendente*] [▼ Período] [Filtrar]          │
│                                                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Produto       │Insumo Alterado│Margem Ant│Nova Margem│Ação │ │
│  ├───────────────┼───────────────┼──────────┼───────────┼─────┤ │
│  │ Camiseta P    │Tecido (+15%)  │  35.2%   │  28.1% ⚠️│ ▶   │ │
│  │ Camiseta M    │Tecido (+15%)  │  33.8%   │  26.4% ⚠️│ ▶   │ │
│  │ Caneca Person.│Tinta (+22%)   │  42.0%   │  12.3% 🔴│ ▶   │ │
│  └───────────────┴───────────────┴──────────┴───────────┴─────┘ │
│                                                                 │
│  Ao clicar ▶ abre modal SweetAlert2:                           │
│  ┌─ Impacto de Custo: Caneca Personalizada ──────────────┐     │
│  │                                                        │     │
│  │  Insumo: Tinta Sublimação                             │     │
│  │  Custo anterior: R$ 12.50/L → Novo: R$ 15.25/L       │     │
│  │                                                        │     │
│  │  Custo Produção: R$ 18.40 → R$ 21.15                  │     │
│  │  Preço Venda Atual: R$ 24.00                          │     │
│  │  Margem: 42.0% → 12.3% (mín. config: 15%)            │     │
│  │                                                        │     │
│  │  💡 Preço sugerido para margem 15%: R$ 24.88          │     │
│  │                                                        │     │
│  │  [Dispensar] [Reconhecer] [Aplicar Preço Sugerido]     │     │
│  └────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────┘
```

### 2.4 Apontamento de Consumo Real (`supply_dashboard/report.php`)

```
┌─────────────────────────────────────────────────────────────────┐
│  📋 Apontamento de Consumo — Ordem #1234                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Produto: Camiseta Algodão | Variação: Tamanho P | Qtd: 50     │
│                                                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Insumo          │ Previsto │ Real (editar) │ Variação      │ │
│  ├─────────────────┼──────────┼───────────────┼───────────────┤ │
│  │ Tecido Algodão  │  82.50m  │ [  85.00m  ]  │ +2.50 (+3.0%)│ │
│  │ Linha Costura   │ 1375.0m  │ [ 1400.0m  ]  │ +25.0 (+1.8%)│ │
│  │ Botão Metal     │  300 un  │ [  298 un  ]  │  -2   (-0.7%)│ │
│  │ Etiqueta        │   50 un  │ [   50 un  ]  │   0   ( 0.0%)│ │
│  └─────────────────┴──────────┴───────────────┴───────────────┘ │
│                                                                 │
│  Observações: [___________________________________________]     │
│                                                                 │
│  [Cancelar]  [Salvar Apontamento]                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Componentes Reutilizáveis

### 3.1 Card de Alerta de Estoque (existente — melhorar)

```html
<!-- Adicionar indicador de ruptura -->
<div class="card border-danger">
    <div class="card-body">
        <span class="badge bg-danger">RUPTURA</span>
        <h6>Tecido Algodão</h6>
        <small>Estoque: 15.5m | Comprometido: 42.0m</small>
        <div class="progress mt-2">
            <div class="progress-bar bg-danger" style="width: 270%"></div>
        </div>
        <small class="text-danger">Faltam 26.5m para pedidos em aberto</small>
    </div>
</div>
```

### 3.2 Badge de Status de Forecast

| Status | Cor | Ícone | Regra |
|--------|-----|-------|-------|
| `ok` | `bg-success` | 🟢 | Estoque > comprometido + 30% buffer |
| `warning` | `bg-warning` | 🟡 | Estoque cobre, mas < 7 dias de consumo |
| `critical` | `bg-danger` | 🔴 | Estoque cobre < 3 dias |
| `ruptured` | `bg-dark` | ⚫ | Estoque < comprometido |

### 3.3 Toast Notifications (SweetAlert2)

```javascript
// Entrada processada
Swal.fire({ toast: true, position: 'top-end', icon: 'success',
    title: 'Entrada registrada com sucesso', timer: 3000, showConfirmButton: false });

// Alerta de ruptura
Swal.fire({ toast: true, position: 'top-end', icon: 'warning',
    title: '3 insumos com ruptura iminente!',
    text: 'Verifique o dashboard de previsão.', timer: 5000 });

// Confirmação de baixa com substituto
Swal.fire({ icon: 'warning',
    title: 'Estoque insuficiente de Tecido Algodão',
    html: 'Substituto disponível:<br><b>Tecido Misto</b> (×1.1) — 200m<br>Deseja usar o substituto?',
    showCancelButton: true, confirmButtonText: 'Sim, usar substituto',
    cancelButtonText: 'Cancelar produção' });
```

---

## 4. Gráficos (Chart.js)

### 4.1 Dashboard de Eficiência — Barras Agrupadas

```javascript
// Tipo: Bar chart agrupado
// Datasets: [Previsto, Real]
// Labels: Semanas ou Meses
// Cores: Previsto=#3498db, Real= (verde se ≤105%, vermelho se >105%)
{
    type: 'bar',
    data: {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
        datasets: [
            { label: 'Previsto', backgroundColor: '#3498db' },
            { label: 'Real', backgroundColor: dynamicColor }
        ]
    }
}
```

### 4.2 Histórico de CMP — Linha

```javascript
// Tipo: Line chart
// Eixo X: Data | Eixo Y: Preço
// Exibe evolução do CMP ao longo do tempo
{
    type: 'line',
    data: {
        datasets: [
            { label: 'CMP', borderColor: '#2ecc71', tension: 0.3 },
            { label: 'Último Preço Compra', borderColor: '#e74c3c', borderDash: [5,5] }
        ]
    }
}
```

---

## 5. Responsividade

Todas as telas devem seguir o grid Bootstrap 5:

| Breakpoint | Layout |
|-----------|--------|
| `xs` (< 576px) | Cards empilhados, tabela com scroll horizontal |
| `sm` (≥ 576px) | KPIs em 2 colunas |
| `md` (≥ 768px) | KPIs em 4 colunas, tabelas visíveis |
| `lg` (≥ 992px) | Layout completo com sidebar |
| `xl` (≥ 1200px) | Gráficos lado a lado |

---

## 6. Mapa de Views

| Arquivo | Tipo | Status |
|---------|------|--------|
| `supplies/form.php` | Modificar | Adicionar tabs Substitutos + campo Fracionamento |
| `supplies/cost_alerts.php` | Novo | Listagem de alertas de custo |
| `supplies/_substitutes_tab.php` | Novo (partial) | Tab substitutos no form |
| `supplies/_bom_tab.php` | Novo (partial) | Tab BOM (usado em product form) |
| `supply_stock/forecast.php` | Novo | Dashboard de previsão de ruptura |
| `supply_stock/_forecast_detail.php` | Novo (partial) | Detalhe de ruptura (modal) |
| `supply_dashboard/efficiency.php` | Novo | Dashboard eficiência |
| `supply_dashboard/report.php` | Novo | Apontamento de consumo real |

---

*Anterior: [03 — Arquitetura Backend](03-arquitetura-backend.md) | Próximo: [05 — Regras de Negócio](05-regras-negocio.md)*
