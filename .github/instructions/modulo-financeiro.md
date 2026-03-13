# Módulo Financeiro

---

## Sumário
- [Visão Geral](#visão-geral)
- [Recursos Financeiros](#recursos-financeiros)
- [Fluxo de Pagamento](#fluxo-de-pagamento)
- [Tipos de Transação](#tipos-de-transação)
- [Categorias de Transação](#categorias-de-transação)
- [Arquivos do Módulo](#arquivos-do-módulo)

---

## Visão Geral
O módulo financeiro controla pagamentos, parcelas, estornos, importação de extratos e integração com pedidos.

---

## Recursos Financeiros
- Controle de parcelas e pagamentos.
- Entradas e saídas de caixa.
- Estornos.
- Importação de extratos bancários (OFX).

---

## Fluxo de Pagamento
- Pagamentos vinculados ao pedido.
- Parcelas pagas bloqueiam alterações no pedido.
- Estornos liberam alterações.

---

## Tipos de Transação
O campo `type` da tabela `financial_transactions` aceita três valores:

| Tipo | Descrição | Contabiliza no saldo? | Badge na listagem |
|------|-----------|----------------------|-------------------|
| `entrada` | Dinheiro que entra no caixa | ✅ Sim (soma em Entradas) | 🟢 Verde + seta ↓ |
| `saida` | Dinheiro que sai do caixa | ✅ Sim (soma em Saídas) | 🔴 Vermelho + seta ↑ |
| `registro` | Lançamento informativo (estornos, importações OFX sem contabilizar) | ❌ Não contabiliza | ⚫ Cinza + risco (—) |

---

## Categorias de Transação
**Entradas:**
- `pagamento_pedido` — Pagamento de Pedido
- `servico_avulso` — Serviço Avulso
- `outra_entrada` — Outra Entrada

**Saídas:**
- `material` — Compra de Material
- `salario` — Salários
- `aluguel` — Aluguel
- `energia` — Energia/Água
- `internet` — Internet/Telefone
- `manutencao` — Manutenção

---

## Arquivos do Módulo
- `app/models/Financial.php`
- `app/controllers/FinancialController.php`
- `app/views/financial/`
- `sql/update_20260306_financial_module.sql`

---
