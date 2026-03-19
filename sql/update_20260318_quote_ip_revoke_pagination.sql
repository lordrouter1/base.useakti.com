-- ============================================================================
-- Atualização: IP de confirmação + suporte a revogação de orçamento
-- Data: 2026-03-18
-- Descrição:
--   1. Adiciona coluna quote_confirmed_ip na tabela orders para guardar
--      o IP do dispositivo que confirmou o orçamento.
-- ============================================================================

-- 1. Adicionar coluna quote_confirmed_ip na tabela orders
ALTER TABLE orders
    ADD COLUMN quote_confirmed_ip VARCHAR(45) DEFAULT NULL
    COMMENT 'IP do dispositivo que confirmou o orçamento via catálogo'
    AFTER quote_confirmed_at;
