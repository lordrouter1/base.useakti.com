-- ============================================================================
-- Atualização: Dashboard Dinâmico com Widgets por Grupo de Usuários
-- Data: 2026-03-18
-- Descrição: Cria tabela dashboard_widgets para armazenar quais widgets
--            cada grupo de usuários pode ver no dashboard e em qual ordem.
--            Se um grupo não tiver configuração, o sistema usa um padrão global.
-- ============================================================================

-- 1. Criar tabela dashboard_widgets
CREATE TABLE IF NOT EXISTS dashboard_widgets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    group_id    INT NOT NULL COMMENT 'ID do grupo de usuários (FK user_groups.id)',
    widget_key  VARCHAR(100) NOT NULL COMMENT 'Chave única do widget (ex: cards_summary, pipeline, financeiro)',
    sort_order  INT NOT NULL DEFAULT 0 COMMENT 'Ordem de exibição no dashboard',
    is_visible  TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=visível, 0=oculto',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_group_widget (group_id, widget_key),
    INDEX idx_group_order (group_id, sort_order),
    
    CONSTRAINT fk_dw_group FOREIGN KEY (group_id) 
        REFERENCES user_groups(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuração de widgets do dashboard por grupo de usuários';
