## Módulo: Financeiro (Pagamentos, Parcelas e Caixa)

### Conceito
O módulo financeiro controla o ciclo de pagamento dos pedidos e o livro-caixa da empresa. A **geração de parcelas** acontece no pipeline (detalhe do pedido), e o módulo financeiro se concentra em:
- **Dashboard** com indicadores financeiros (receita, recebido, a receber, atrasados)
- **Confirmação de pagamentos** — fluxo simples onde o operador registra e confirma recebimentos
- **Entradas e saídas** — registro manual de transações financeiras diversas (despesas fixas, compras, etc.)
- **Importação OFX** — importação de extratos bancários no formato OFX com opção de contabilizar ou apenas registrar
- **Estornos e registros** — transações informativas que não contabilizam no saldo do caixa

### Princípio: Simplicidade
- As parcelas já vêm definidas pelo pipeline (card financeiro no `detail.php`)
- No módulo financeiro, o operador **apenas confirma** os pagamentos
- Nunca gerar parcelas a partir do módulo financeiro — isso é responsabilidade do pipeline
- O fluxo deve ser: ver lista → clicar em "Parcelas" → registrar pagamento → confirmar
- Todas as ações usam **SweetAlert2** para feedback e confirmação visual

### Fluxo de Pagamento
1. **Pipeline (`detail.php`):** O operador define forma de pagamento, parcelamento e entrada. As parcelas são geradas automaticamente.
2. **Financeiro > Pagamentos (`payments.php`):** Lista todos os pedidos com seus status de pagamento. O operador clica em "Parcelas" para ver detalhes.
3. **Financeiro > Parcelas (`installments.php`):** Mostra todas as parcelas do pedido. O operador pode:
   - **Registrar pagamento** (abre modal com data, valor e método)
   - **Confirmar** pagamento já registrado
   - **Estornar** um pagamento (reverte para pendente e registra estorno no caixa)
4. O `payment_status` do pedido (`orders.payment_status`) é atualizado automaticamente conforme as parcelas são pagas/confirmadas.

### Tipos de Transação
O campo `type` da tabela `financial_transactions` aceita três valores:

| Tipo | Descrição | Contabiliza no saldo? | Badge na listagem |
|------|-----------|----------------------|-------------------|
| `entrada` | Dinheiro que entra no caixa | ✅ Sim (soma em Entradas) | 🟢 Verde + seta ↓ |
| `saida` | Dinheiro que sai do caixa | ✅ Sim (soma em Saídas) | 🔴 Vermelho + seta ↑ |
| `registro` | Lançamento informativo (estornos, importações OFX sem contabilizar) | ❌ Não contabiliza | ⚫ Cinza + risco (—) |

### Categorias de Transação

#### Categorias manuais (disponíveis no formulário)
**Entradas:**
- `pagamento_pedido` — Pagamento de Pedido
- `servico_avulso` — Serviço Avulso
- `outra_entrada` — Outra Entrada **(default para novas entradas)**

**Saídas:**
- `material` — Compra de Material
- `salario` — Salários
- `aluguel` — Aluguel
- `energia` — Energia/Água
- `internet` — Internet/Telefone
- `manutencao` — Manutenção
- `imposto` — Impostos/Taxas
- `outra_saida` — Outra Saída **(default para novas saídas)**

#### Categorias internas (usadas apenas pelo sistema)
- `estorno_pagamento` — Estorno de Pagamento (gerada ao estornar uma parcela)
- `registro_ofx` — Registro OFX (gerada na importação OFX em modo "registro")

> ⚠️ **"Estorno de Pagamento" NÃO pode ser selecionada manualmente** pelo usuário. Ela é gerada exclusivamente pelo sistema ao estornar uma parcela no módulo de pagamentos.

### Regras de Estornos
- Ao estornar uma parcela, o sistema cria uma transação com `type = 'registro'` e `category = 'estorno_pagamento'`
- Estornos **nunca contabilizam** nos totais de entradas ou saídas
- Na listagem, estornos aparecem com **badge cinza**, ícone de **risco (—)** e valor em **texto cinza**
- A transação original de entrada associada à parcela é removida da tabela

### Importação OFX
O sistema permite importar extratos bancários no formato OFX (Open Financial Exchange):

1. Na tela de **Entradas e Saídas**, clique em **"Importar OFX"**
2. Selecione o arquivo `.ofx` ou `.ofc` exportado do banco
3. Escolha o **modo de importação**:
   - **Registro** (default): importa apenas como referência — badge cinza, não contabiliza no caixa
   - **Contabilizar**: créditos entram como `entrada` e débitos como `saida`, contabilizando normalmente
4. O parser suporta o formato SGML utilizado pela maioria dos bancos brasileiros
5. Transações com valor zero ou negativo são ignoradas automaticamente

### Tabelas no Banco de Dados
- `order_installments` — Parcelas individuais de cada pedido (geradas pelo pipeline)
- `financial_transactions` — Log de transações financeiras (entradas/saídas manuais + automáticas)
- `orders` — Colunas adicionadas: `down_payment`, `nf_number`, `nf_series`, `nf_status`, `nf_access_key`, `nf_notes`

### Regras de Negócio
- Parcela com `is_confirmed = 0` e `status = 'pago'` está aguardando confirmação manual
- Parcela com `is_confirmed = 1` e `status = 'pago'` está totalmente confirmada
- O `payment_status` do pedido é calculado automaticamente: `pendente` (nenhuma paga), `parcial` (algumas pagas), `pago` (todas confirmadas)
- Parcelas vencidas (`due_date < hoje`) com status `pendente` são automaticamente marcadas como `atrasado`
- Ao registrar pagamento, uma transação financeira (`type = 'entrada'`, `category = 'pagamento_pedido'`) é criada automaticamente
- Ao estornar, a parcela volta para `pendente`, os dados de pagamento são limpos, a entrada original é removida e um registro de estorno é criado (`type = 'registro'`, `category = 'estorno_pagamento'`)
- Transações com `type = 'registro'` ou `category IN ('estorno_pagamento', 'registro_ofx')` são **excluídas** dos cálculos de totais (entradas, saídas, saldo) tanto no dashboard quanto na listagem
- Ao lançar manualmente, a categoria default é `outra_entrada` para entradas e `outra_saida` para saídas

### Padrão Visual (UI)
- **Cards de resumo:** Seguem o mesmo padrão do Dashboard — `card border-0 shadow-sm border-start border-4` com ícone circular
- **Tabelas:** `table-responsive bg-white rounded shadow-sm` com `table-hover align-middle`
- **Badges de status:** cores padronizadas (warning=pendente, success=pago, danger=atrasado, secondary=cancelado)
- **Badge de estorno/registro:** `badge bg-secondary` com ícone `fa-minus` (risco —), texto cinza, linha com fundo `table-light`
- **Modais:** Bootstrap 5 modals com header colorido (`bg-success bg-opacity-10`) e footer sem borda
- **SweetAlert2:** Obrigatório para todas as confirmações e feedbacks. Nunca usar `confirm()` ou `alert()` nativo
- **Filtros:** Linha de selects compactos (`form-select-sm`) com botão Filtrar e botão limpar (X)
- **Dropdown de tipo (filtro):** Inclui "Registros" além de "Entradas" e "Saídas"

### Arquivos do Módulo
- `sql/update_20260306_financial_module.sql` — Migration (tabelas + colunas + índices + conversão de estornos)
- `app/models/Financial.php` — Model com métodos de consulta, geração de parcelas, confirmação, estorno, importação OFX, parsing OFX
- `app/controllers/FinancialController.php` — Controller com actions: index, payments, installments, payInstallment, confirmPayment, cancelInstallment, transactions, addTransaction, deleteTransaction, importOfx, getSummaryJson, getInstallmentsJson
- `app/views/financial/index.php` — Dashboard financeiro (cards + gráfico + alertas)
- `app/views/financial/payments.php` — Lista de pedidos com status de pagamento
- `app/views/financial/installments.php` — Parcelas de um pedido (confirmação simples)
- `app/views/financial/transactions.php` — Entradas, saídas e registros (inclui modal de importação OFX)
- `app/config/menu.php` — Grupo "Fiscal" com links para dashboard, pagamentos e transações

### Actions do Módulo (`?page=financial`)
| Action | Método | Descrição |
|--------|--------|-----------|
| `index` (default) | GET | Dashboard financeiro |
| `payments` | GET | Lista de pedidos com pagamento |
| `installments` | GET | Parcelas de um pedido (`&order_id=X`) |
| `payInstallment` | POST | Registra pagamento de uma parcela |
| `confirmPayment` | POST | Confirma pagamento manualmente |
| `cancelInstallment` | POST | Estorna pagamento de uma parcela |
| `transactions` | GET | Lista de entradas, saídas e registros |
| `addTransaction` | POST | Registra nova transação manual |
| `deleteTransaction` | POST | Exclui transação manual |
| `importOfx` | POST (AJAX) | Importa arquivo OFX (registro ou contabilizado) |
| `getSummaryJson` | GET | API JSON com resumo (para widgets) |
| `getInstallmentsJson` | GET | API JSON com parcelas de um pedido |