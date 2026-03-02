-- ============================================================
-- ATUALIZAÇÃO: Novos limites de tenant no banco master
-- Data: 02/03/2026
-- Descrição: Adiciona colunas max_warehouses, max_price_tables
--            e max_sectors na tabela tenant_clients do banco
--            master para controlar limites por plano do cliente.
-- ============================================================

-- ─── BANCO MASTER ───

USE akti_master;

-- Adicionar novas colunas de limite na tabela tenant_clients
ALTER TABLE tenant_clients
    ADD COLUMN max_warehouses INT NULL DEFAULT NULL COMMENT 'Limite de armazéns por tenant (NULL = sem limite)' AFTER max_products,
    ADD COLUMN max_price_tables INT NULL DEFAULT NULL COMMENT 'Limite de tabelas de preço por tenant (NULL = sem limite)' AFTER max_warehouses,
    ADD COLUMN max_sectors INT NULL DEFAULT NULL COMMENT 'Limite de setores de produção por tenant (NULL = sem limite)' AFTER max_price_tables;

-- Atualizar o registro do cliente teste com valores de exemplo
UPDATE tenant_clients 
SET max_warehouses = 3, 
    max_price_tables = 5, 
    max_sectors = 10
WHERE subdomain = 'teste';
