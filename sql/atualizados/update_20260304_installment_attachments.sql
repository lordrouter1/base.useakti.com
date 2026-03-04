-- ============================================================================
-- UPDATE: update_20260304_installment_attachments.sql
-- Descrição: Adiciona campo de anexo (comprovante) nas parcelas e remove
--            gateway_reference. Adiciona suporte a impressão de boleto.
-- Data: 2026-03-04
-- Autor: Sistema Akti
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Adicionar coluna de anexo (caminho do comprovante)
ALTER TABLE `order_installments`
    ADD COLUMN IF NOT EXISTS `attachment_path` VARCHAR(500) DEFAULT NULL COMMENT 'Caminho do comprovante anexado' AFTER `notes`;

-- 2. Remover coluna gateway_reference (não utilizada)
ALTER TABLE `order_installments`
    DROP COLUMN IF EXISTS `gateway_reference`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FIM DA MIGRATION
-- ============================================================================
