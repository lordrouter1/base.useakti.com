-- ============================================================================
-- MIGRAÇÃO CORRETIVA: Correção de falhas pós-deploy de 14/03/2026
-- Data: 2026-03-14
-- Autor: Sistema Akti
-- ============================================================================
-- Descrição:
--   Corrige problemas causados por migrações que falharam parcialmente
--   devido a erro PDO "unbuffered queries" no executor de migrations.
--
--   Migrações afetadas:
--     1. update_20260310_item_discount_and_installments (PARTIAL 11/48)
--        → Colunas nf_number e nf_series ficaram com tamanho menor que o esperado
--     2. update_20260312_fix_nf_status_column (PARTIAL em ambas execuções)
--        → UPDATE para limpar strings vazias nunca executou
--
--   Correções adicionais:
--     3. Limpeza de strings vazias em todas as colunas nf_* da tabela orders
--     4. Remoção de registros duplicados de 'financial' em group_permissions
--
-- IMPORTANTE: Este script deve ser executado em TODOS os bancos de tenant
--   (akti_gsul, akti_teste, akti_init_base). NÃO usa PREPARE/EXECUTE para
--   evitar o mesmo problema de unbuffered queries.
-- ============================================================================

-- ─────────────────────────────────────────────────────
-- 1. Corrigir tamanho da coluna nf_number (VARCHAR(20) → VARCHAR(50))
--    A migration original definia VARCHAR(50) mas a coluna já existia
--    com VARCHAR(20) no schema base, então o IF NOT EXISTS não a alterou.
-- ─────────────────────────────────────────────────────
ALTER TABLE orders MODIFY COLUMN nf_number VARCHAR(50) DEFAULT NULL;

-- ─────────────────────────────────────────────────────
-- 2. Corrigir tamanho da coluna nf_series (VARCHAR(5) → VARCHAR(10))
--    Mesmo caso: já existia menor que o especificado na migration.
-- ─────────────────────────────────────────────────────
ALTER TABLE orders MODIFY COLUMN nf_series VARCHAR(10) DEFAULT NULL;

-- ─────────────────────────────────────────────────────
-- 3. Garantir que nf_status é VARCHAR(20) e não ENUM
--    (Refaz parte da fix_nf_status_column que falhou)
-- ─────────────────────────────────────────────────────
ALTER TABLE orders MODIFY COLUMN nf_status VARCHAR(20) DEFAULT NULL;

-- ─────────────────────────────────────────────────────
-- 4. Limpar strings vazias nas colunas nf_* da tabela orders
--    O UPDATE da migration fix_nf_status_column nunca executou.
--    Aproveitamos para limpar TODAS as colunas nf_* de uma vez.
-- ─────────────────────────────────────────────────────
UPDATE orders SET nf_status = NULL WHERE nf_status = '';
UPDATE orders SET nf_number = NULL WHERE nf_number = '';
UPDATE orders SET nf_series = NULL WHERE nf_series = '';
UPDATE orders SET nf_access_key = NULL WHERE nf_access_key = '';
UPDATE orders SET nf_notes = NULL WHERE nf_notes = '';

-- ─────────────────────────────────────────────────────
-- 5. Remover registros duplicados de 'financial' em group_permissions
--    O backup mostra IDs 12,13,14,15 todos com page_name='financial'
--    para o mesmo group_id=1. Devemos manter apenas 1 por grupo.
-- ─────────────────────────────────────────────────────
DELETE gp FROM group_permissions gp
INNER JOIN (
    SELECT MIN(id) AS keep_id, group_id, page_name
    FROM group_permissions
    WHERE page_name = 'financial'
    GROUP BY group_id, page_name
) keep ON gp.group_id = keep.group_id
    AND gp.page_name = 'financial'
    AND gp.id != keep.keep_id;
