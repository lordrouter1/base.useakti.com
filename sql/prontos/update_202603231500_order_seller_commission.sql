-- ============================================================
-- MÓDULO: COMISSÕES — Vendedor/Comissionado no Pedido
-- Criado em: 23/03/2026 15:00
-- Descrição: Adiciona campo seller_id na tabela orders para
--   vincular um vendedor/comissionado ao pedido. Este campo é
--   utilizado para o cálculo automático de comissão quando o
--   pedido é concluído e o pagamento é confirmado.
-- ============================================================

-- Adicionar coluna seller_id na tabela orders
ALTER TABLE orders
    ADD COLUMN seller_id INT NULL COMMENT 'FK → users.id (vendedor/comissionado vinculado ao pedido)'
    AFTER assigned_to;

-- Adicionar índice para consultas de comissão por vendedor
ALTER TABLE orders
    ADD INDEX idx_seller (seller_id);

-- Adicionar FK (opcional, com ON DELETE SET NULL para não impedir exclusão de usuário)
ALTER TABLE orders
    ADD CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL;
