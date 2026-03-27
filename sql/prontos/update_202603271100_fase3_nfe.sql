-- ═══════════════════════════════════════════════════════════════
-- Migration: Fase 3 — NF-e Funcionalidades Parciais
-- Data: 2026-03-27
-- Descrição:
--   - Garantir is_active em nfe_credentials (multi-filial)
--   - Adicionar coluna vBCFCPUFDest em nfe_document_items (DIFAL)
--   - Adicionar campos de DIFAL nos totais de nfe_documents
--   - Índice auxiliar para batch_id + status na nfe_queue
-- ═══════════════════════════════════════════════════════════════

-- 1. Multi-filial: garantir que a credencial id=1 tenha is_active = 1
UPDATE `nfe_credentials` SET `is_active` = 1 WHERE `id` = 1 AND (`is_active` IS NULL OR `is_active` = 0);

-- 2. Campos DIFAL nos totais do documento (ICMSUFDest)
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `valor_fcp_uf_dest` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Total FCP UF Destino (DIFAL)' AFTER `valor_tributos_aprox`,
    ADD COLUMN IF NOT EXISTS `valor_icms_uf_dest` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Total ICMS UF Destino (DIFAL)' AFTER `valor_fcp_uf_dest`,
    ADD COLUMN IF NOT EXISTS `valor_icms_uf_remet` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Total ICMS UF Remetente (DIFAL)' AFTER `valor_icms_uf_dest`;

-- 3. Campos de DIFAL por item (nfe_document_items)
ALTER TABLE `nfe_document_items`
    ADD COLUMN IF NOT EXISTS `difal_vbc` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Base de cálculo DIFAL' AFTER `ipi_valor`,
    ADD COLUMN IF NOT EXISTS `difal_fcp` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor FCP UF Destino' AFTER `difal_vbc`,
    ADD COLUMN IF NOT EXISTS `difal_icms_dest` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor ICMS UF Destino' AFTER `difal_fcp`,
    ADD COLUMN IF NOT EXISTS `difal_icms_remet` DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor ICMS UF Remetente' AFTER `difal_icms_dest`;

-- 4. Índice composto batch_id + status para filtro eficiente na fila
ALTER TABLE `nfe_queue`
    ADD INDEX IF NOT EXISTS `idx_nfe_queue_batch_status` (`batch_id`, `status`);

-- 5. Campo fin_nfe no nfe_documents (finalidade: 1=Normal, 2=Complementar, 3=Ajuste, 4=Devolução)
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `fin_nfe` TINYINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Finalidade NF-e: 1=Normal, 2=Complementar, 3=Ajuste, 4=Devolução' AFTER `modelo`,
    ADD COLUMN IF NOT EXISTS `chave_ref` VARCHAR(44) DEFAULT NULL
        COMMENT 'Chave de acesso da NF-e referenciada (devolução/complementar)' AFTER `fin_nfe`;

-- Fim da migration Fase 3
