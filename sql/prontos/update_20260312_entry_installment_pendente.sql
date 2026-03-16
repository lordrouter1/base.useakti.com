-- ============================================================================
-- UPDATE: update_20260312_entry_installment_pendente.sql
-- Descrição: Altera parcelas de entrada (installment_number=0) de 'pago' para
--            'pendente', para que a existência de entrada não bloqueie alterações
--            na forma de pagamento. A entrada agora segue o mesmo fluxo de
--            confirmação das demais parcelas.
-- Data: 2026-03-12
-- Autor: Sistema Akti
-- ============================================================================

-- Reverter parcelas de entrada que foram auto-marcadas como pagas na geração
-- Apenas afeta parcelas que NÃO foram confirmadas manualmente por um operador
-- no módulo financeiro (confirmed_by IS NULL indica geração automática)
UPDATE order_installments
SET status = 'pendente',
    paid_date = NULL,
    paid_amount = NULL,
    is_confirmed = 0,
    confirmed_by = NULL,
    confirmed_at = NULL,
    updated_at = NOW()
WHERE installment_number = 0
  AND status = 'pago'
  AND confirmed_by IS NULL;
