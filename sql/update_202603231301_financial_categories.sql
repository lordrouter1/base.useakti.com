-- ═══════════════════════════════════════════════════════════════
-- MIGRAÇÃO: Tabela de categorias financeiras dinâmicas
-- Data: 2026-03-23 13:01
-- Fase 2 do relatório de refatoração
-- Descrição:
--   Cria tabela financial_categories para permitir categorias dinâmicas
--   de entradas e saídas, substituindo o array estático no código PHP.
--   Categorias do sistema (is_system=1) não podem ser excluídas pelo usuário.
-- ═══════════════════════════════════════════════════════════════

SET @dbname = DATABASE();

-- Criar tabela apenas se não existir
CREATE TABLE IF NOT EXISTS financial_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL COMMENT 'Identificador único (usado internamente)',
    name VARCHAR(100) NOT NULL COMMENT 'Nome exibido ao usuário',
    type ENUM('entrada','saida','ambos') NOT NULL DEFAULT 'ambos' COMMENT 'Tipo da categoria',
    icon VARCHAR(50) DEFAULT NULL COMMENT 'Classe do ícone FontAwesome',
    color VARCHAR(7) DEFAULT NULL COMMENT 'Cor hex (ex: #28a745)',
    is_system TINYINT(1) DEFAULT 0 COMMENT 'Categorias do sistema não podem ser excluídas',
    sort_order INT DEFAULT 0 COMMENT 'Ordem de exibição',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Se a categoria está ativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_financial_cat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorias dinâmicas de transações financeiras';

-- ─────────────────────────────────────────────────────
-- Seed: categorias padrão do sistema (is_system = 1)
-- INSERT IGNORE evita erro se os slugs já existirem
-- ─────────────────────────────────────────────────────

INSERT IGNORE INTO financial_categories (slug, name, type, icon, color, is_system, sort_order) VALUES
('pagamento_pedido',   'Pagamento de Pedido',  'entrada', 'fas fa-file-invoice-dollar', '#28a745', 1, 1),
('servico_avulso',     'Serviço Avulso',        'entrada', 'fas fa-concierge-bell',      '#17a2b8', 1, 2),
('outra_entrada',      'Outra Entrada',         'entrada', 'fas fa-plus-circle',          '#6f42c1', 1, 99),
('material',           'Compra de Material',    'saida',   'fas fa-boxes',                '#dc3545', 1, 1),
('salario',            'Salários',              'saida',   'fas fa-users',                '#fd7e14', 1, 2),
('aluguel',            'Aluguel',               'saida',   'fas fa-building',             '#6c757d', 1, 3),
('energia',            'Energia/Água',          'saida',   'fas fa-bolt',                 '#ffc107', 1, 4),
('internet',           'Internet/Telefone',     'saida',   'fas fa-wifi',                 '#20c997', 1, 5),
('manutencao',         'Manutenção',            'saida',   'fas fa-tools',                '#795548', 1, 6),
('imposto',            'Impostos/Taxas',        'saida',   'fas fa-landmark',             '#e91e63', 1, 7),
('outra_saida',        'Outra Saída',           'saida',   'fas fa-minus-circle',         '#6c757d', 1, 99),
('estorno_pagamento',  'Estorno de Pagamento',  'ambos',   'fas fa-undo-alt',             '#ff5722', 1, 100),
('registro_ofx',       'Registro OFX',          'ambos',   'fas fa-file-import',          '#607d8b', 1, 101);
