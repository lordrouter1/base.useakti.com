-- Migration: Correcao de indices que falharam nas migrations anteriores
-- Criado em: 04/04/2026 15:33
-- Sequencial: 3
--
-- Contexto: As migrations update_202603301000_performance_indexes e
-- update_202604020843_1_performance_indexes_adicionais falharam porque
-- usavam DELIMITER (comando do cliente MySQL, incompativel com PDO).
-- Esta migration recria apenas os indices que NAO existem no banco.
--
-- Usa CREATE INDEX IF NOT EXISTS (MariaDB 10.1+) para idempotencia
-- e compatibilidade com PDO.

-- ════════════════════════════════════════════════════════════
-- Indices da migration performance_indexes que faltam
-- ════════════════════════════════════════════════════════════

-- customers: filtro de ativos com soft delete
CREATE INDEX IF NOT EXISTS `idx_customers_status_deleted` ON `customers` (`status`, `deleted_at`);

-- order_installments: relatorio financeiro (status + data pagamento)
CREATE INDEX IF NOT EXISTS `idx_installments_status_paid` ON `order_installments` (`status`, `paid_date`);

-- products: busca por nome (Select2 e listagem)
CREATE INDEX IF NOT EXISTS `idx_products_name` ON `products` (`name`);

-- ════════════════════════════════════════════════════════════
-- Indices da migration performance_indexes_adicionais que faltam
-- ════════════════════════════════════════════════════════════

-- order_installments: parcelas vencendo/atrasadas (due_date primeiro)
CREATE INDEX IF NOT EXISTS `idx_installments_due_status` ON `order_installments` (`due_date`, `status`);

-- orders: filtro por status na listagem
CREATE INDEX IF NOT EXISTS `idx_orders_status` ON `orders` (`status`);
