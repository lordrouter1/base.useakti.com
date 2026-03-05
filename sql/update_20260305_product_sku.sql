-- ═══════════════════════════════════════════════════════════════
-- Migration: Adicionar campo SKU na tabela de produtos
-- Data: 2026-03-05
-- ═══════════════════════════════════════════════════════════════

-- Adicionar coluna SKU ao produto (código único opcional)
ALTER TABLE products ADD COLUMN sku VARCHAR(100) DEFAULT NULL COMMENT 'SKU - Código único do produto' AFTER name;

-- Criar índice único para o SKU (permite NULLs)
ALTER TABLE products ADD UNIQUE INDEX idx_products_sku (sku);
