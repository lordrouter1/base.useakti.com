-- =====================================================
-- UPDATE: Bootloader de módulos por tenant (MASTER)
-- Data: 2026-03-10
-- Escopo: banco akti_master
-- =====================================================
-- Objetivo:
--   Adicionar coluna enabled_modules na tabela tenant_clients
--   para permitir ativação/desativação de módulos por tenant.
--
-- Formato esperado (JSON):
--   {
--     "financial": true,
--     "boleto": true,
--     "fiscal": true,
--     "nfe": true
--   }
--
-- Observação:
--   NULL mantém fallback do sistema (módulos padrão habilitados).
-- =====================================================

SET @dbname = DATABASE();
SET @table_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'tenant_clients'
);

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'tenant_clients'
      AND COLUMN_NAME = 'enabled_modules'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Tabela tenant_clients não encontrada neste banco. Execute no akti_master."',
    IF(
        @column_exists = 0,
        'ALTER TABLE tenant_clients ADD COLUMN enabled_modules JSON NULL AFTER max_sectors',
        'SELECT 1'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
