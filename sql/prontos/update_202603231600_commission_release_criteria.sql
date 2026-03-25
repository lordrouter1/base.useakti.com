-- ============================================================
-- MÓDULO: COMISSÕES — Critério de Liberação e Status Aguardando
-- Criado em: 23/03/2026 16:00
-- Descrição: 
--   1. Adiciona config 'criterio_liberacao_comissao' para definir
--      quando a comissão é liberada (sem_confirmacao, primeira_parcela,
--      pagamento_total).
--   2. Altera ENUM de status em comissoes_registradas para incluir
--      'aguardando_pagamento' (entre aprovada e paga).
-- ============================================================

-- ─────────────────────────────────────────────────────
-- Nova configuração: critério de liberação da comissão
-- Valores possíveis:
--   'sem_confirmacao'  → Liberação imediata (sem checar pagamento)
--   'primeira_parcela' → Liberada ao pagar a primeira parcela
--   'pagamento_total'  → Liberada somente com pagamento total
-- ─────────────────────────────────────────────────────
INSERT INTO comissao_config (config_key, config_value, descricao) VALUES
('criterio_liberacao_comissao', 'pagamento_total', 'Critério de liberação: sem_confirmacao, primeira_parcela, pagamento_total')
ON DUPLICATE KEY UPDATE config_key = config_key;

-- ─────────────────────────────────────────────────────
-- Alterar ENUM de status para incluir 'aguardando_pagamento'
-- Fluxo: calculada → aprovada → aguardando_pagamento → paga
-- ─────────────────────────────────────────────────────
ALTER TABLE comissoes_registradas
    MODIFY COLUMN status ENUM('calculada','aprovada','aguardando_pagamento','paga','cancelada')
    NOT NULL DEFAULT 'calculada';
