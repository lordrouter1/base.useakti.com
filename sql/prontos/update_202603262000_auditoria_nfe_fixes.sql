-- ══════════════════════════════════════════════════════════════
-- Akti - Gestão em Produção
-- Migration: Correções de Auditoria NF-e v2
-- Data: 2026-03-26 20:00
-- Descrição:
--   A) Renomear colunas camelCase da tabela nfe_document_items para snake_case
--      (padronizar com o restante do sistema)
--   B) Adicionar coluna batch_id à tabela nfe_queue
--   C) Adicionar coluna vFrete como v_frete (renomear se existir)
-- ══════════════════════════════════════════════════════════════

-- ═══════════════════════════════════════════
-- A) RENOMEAR COLUNAS DE nfe_document_items
-- camelCase → snake_case
-- ═══════════════════════════════════════════

-- Verificar se a tabela existe antes de alterar
-- MySQL/MariaDB: CHANGE COLUMN faz rename + redefine tipo

-- nItem → n_item
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `nItem` `n_item` INT UNSIGNED NOT NULL COMMENT 'Número sequencial do item';

-- cProd → c_prod
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `cProd` `c_prod` VARCHAR(60) DEFAULT NULL COMMENT 'Código do produto';

-- xProd → x_prod
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `xProd` `x_prod` VARCHAR(255) DEFAULT NULL COMMENT 'Descrição do produto';

-- uCom → u_com
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `uCom` `u_com` VARCHAR(6) DEFAULT 'UN' COMMENT 'Unidade comercial';

-- qCom → q_com
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `qCom` `q_com` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Quantidade comercial';

-- vUnCom → v_un_com
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `vUnCom` `v_un_com` DECIMAL(21,10) NOT NULL DEFAULT 0.0000000000 COMMENT 'Valor unitário';

-- vProd → v_prod
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `vProd` `v_prod` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor total do produto';

-- vDesc → v_desc
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `vDesc` `v_desc` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor do desconto';

-- vFrete → v_frete
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `vFrete` `v_frete` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor do frete por item';

-- vTotTrib → v_tot_trib
ALTER TABLE `nfe_document_items`
    CHANGE COLUMN IF EXISTS `vTotTrib` `v_tot_trib` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor total de tributos aproximados';


-- ═══════════════════════════════════════════
-- B) ADICIONAR batch_id À TABELA nfe_queue
-- ═══════════════════════════════════════════

ALTER TABLE `nfe_queue`
    ADD COLUMN IF NOT EXISTS `batch_id` VARCHAR(50) DEFAULT NULL COMMENT 'ID de lote para emissão em lote' AFTER `modelo`;

-- Índice para filtrar por lote
ALTER TABLE `nfe_queue`
    ADD INDEX IF NOT EXISTS `idx_nfe_queue_batch` (`batch_id`);


-- ═══════════════════════════════════════════
-- C) FIM
-- ═══════════════════════════════════════════
