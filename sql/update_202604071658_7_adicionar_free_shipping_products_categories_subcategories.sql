-- Migration: Adicionar coluna free_shipping em products, categories e subcategories
-- Sequência: 7
-- Data: 2025-06-04 (gerado automaticamente)
-- Descrição: Permite marcar frete grátis no produto, categoria ou subcategoria.
--            Quando marcado na categoria/subcategoria, todos os produtos herdam a flag na loja.

-- 1. Coluna free_shipping em products
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `free_shipping` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Produto com frete grátis (0=não, 1=sim)';

-- 2. Coluna free_shipping em categories
ALTER TABLE `categories`
    ADD COLUMN IF NOT EXISTS `free_shipping` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Frete grátis para todos os produtos desta categoria (0=não, 1=sim)';

-- 3. Coluna free_shipping em subcategories
ALTER TABLE `subcategories`
    ADD COLUMN IF NOT EXISTS `free_shipping` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Frete grátis para todos os produtos desta subcategoria (0=não, 1=sim)';
