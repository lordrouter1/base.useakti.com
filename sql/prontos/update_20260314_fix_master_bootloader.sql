-- ============================================================================
-- MIGRAÇÃO CORRETIVA (MASTER): Adicionar enabled_modules em tenant_clients
-- Data: 2026-03-14
-- Autor: Sistema Akti
-- ============================================================================
-- Descrição:
--   A migration update_20260310_module_bootloader_master nunca foi aplicada
--   no banco akti_master. Este script adiciona a coluna enabled_modules
--   na tabela tenant_clients de forma direta (sem PREPARE/EXECUTE).
--
-- IMPORTANTE: Executar APENAS no banco akti_master.
-- ============================================================================

-- Adicionar coluna enabled_modules (JSON) para controle de módulos por tenant
-- Se a coluna já existir, o ALTER falhará de forma segura (erro ignorável)
ALTER TABLE tenant_clients
    ADD COLUMN enabled_modules JSON NULL AFTER max_sectors;
