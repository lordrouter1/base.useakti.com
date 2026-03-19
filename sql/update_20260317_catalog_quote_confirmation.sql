-- ============================================================================
-- Atualização: Confirmação de Orçamento via Catálogo
-- Data: 2026-03-17
-- Descrição: Adiciona campo require_confirmation na tabela catalog_links
--            e campo quote_confirmed_at na tabela orders para rastrear
--            quando o cliente confirmou o orçamento via link do catálogo.
-- ============================================================================

-- 1. Adicionar coluna require_confirmation na tabela catalog_links
ALTER TABLE catalog_links
    ADD COLUMN require_confirmation TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Se 1, o catálogo exibe orçamento para confirmação do cliente'
    AFTER show_prices;

-- 2. Adicionar coluna quote_confirmed_at na tabela orders
ALTER TABLE orders
    ADD COLUMN quote_confirmed_at DATETIME DEFAULT NULL
    COMMENT 'Data/hora em que o cliente confirmou o orçamento via catálogo'
    AFTER tracking_code;
