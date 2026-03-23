-- ============================================================
-- MÓDULO: COMISSÕES
-- Criado em: 23/03/2026
-- Descrição: Tabelas para o motor de comissões (Rule Engine)
--   - formas_comissao: cadastro de modelos de comissão
--   - grupo_formas_comissao: regras de comissão por grupo
--   - usuario_forma_comissao: regras de comissão por usuário
--   - comissao_produto: regras específicas por produto
--   - comissao_faixas: faixas/escala progressiva
--   - comissoes_registradas: log de comissões calculadas
--   - comissao_config: parâmetros gerais do módulo
-- ============================================================

-- ─────────────────────────────────────────────────────
-- Formas de Comissão (modelos genéricos de comissão)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS formas_comissao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL COMMENT 'Nome da forma de comissão',
    descricao TEXT NULL COMMENT 'Descrição detalhada',
    tipo_calculo ENUM('percentual','valor_fixo','faixa') NOT NULL DEFAULT 'percentual'
        COMMENT 'Tipo de cálculo: percentual, valor fixo ou faixa progressiva',
    base_calculo ENUM('valor_venda','margem_lucro','valor_produto') NOT NULL DEFAULT 'valor_venda'
        COMMENT 'Base sobre a qual o cálculo é aplicado',
    valor DECIMAL(10,4) NOT NULL DEFAULT 0
        COMMENT 'Valor percentual ou fixo (ignorado se tipo=faixa)',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cadastro de modelos/formas de comissão';

-- ─────────────────────────────────────────────────────
-- Regra de comissão por Grupo de Usuários
-- (Prioridade 2 no Rule Engine)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grupo_formas_comissao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT 'FK → user_groups.id',
    forma_comissao_id INT NOT NULL COMMENT 'FK → formas_comissao.id',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (forma_comissao_id) REFERENCES formas_comissao(id) ON DELETE CASCADE,
    UNIQUE KEY uq_grupo_forma (group_id, forma_comissao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Vinculação de forma de comissão a grupo de usuários';

-- ─────────────────────────────────────────────────────
-- Regra de comissão por Usuário
-- (Prioridade 1 no Rule Engine — mais específica)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuario_forma_comissao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'FK → users.id',
    forma_comissao_id INT NOT NULL COMMENT 'FK → formas_comissao.id',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (forma_comissao_id) REFERENCES formas_comissao(id) ON DELETE CASCADE,
    UNIQUE KEY uq_usuario_forma (user_id, forma_comissao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Vinculação de forma de comissão a usuário específico';

-- ─────────────────────────────────────────────────────
-- Regras de comissão por Produto/Categoria
-- (Override opcional no cálculo)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comissao_produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NULL COMMENT 'FK → products.id (NULL = regra por categoria)',
    category_id INT NULL COMMENT 'FK → categories.id (NULL = regra por produto)',
    tipo_calculo ENUM('percentual','valor_fixo') NOT NULL DEFAULT 'percentual',
    valor DECIMAL(10,4) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Regras de comissão específicas por produto ou categoria';

-- ─────────────────────────────────────────────────────
-- Faixas / Escala Progressiva de Comissão
-- (Usado quando tipo_calculo = 'faixa')
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comissao_faixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forma_comissao_id INT NOT NULL COMMENT 'FK → formas_comissao.id',
    faixa_min DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Valor ou percentual mínimo da faixa',
    faixa_max DECIMAL(10,2) NULL COMMENT 'Valor ou percentual máximo (NULL = sem limite)',
    percentual DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT 'Percentual de comissão nesta faixa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (forma_comissao_id) REFERENCES formas_comissao(id) ON DELETE CASCADE,
    INDEX idx_forma_faixa (forma_comissao_id, faixa_min)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Faixas de escala progressiva de comissão';

-- ─────────────────────────────────────────────────────
-- Comissões Registradas (Log detalhado de cada cálculo)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comissoes_registradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL COMMENT 'FK → orders.id (venda)',
    user_id INT NOT NULL COMMENT 'FK → users.id (vendedor/comissionado)',
    forma_comissao_id INT NULL COMMENT 'FK → formas_comissao.id (regra utilizada)',
    origem_regra ENUM('usuario','grupo','produto','padrao') NOT NULL DEFAULT 'padrao'
        COMMENT 'De onde veio a regra aplicada',
    tipo_calculo VARCHAR(30) NOT NULL COMMENT 'Tipo de cálculo usado',
    base_calculo VARCHAR(30) NOT NULL COMMENT 'Base de cálculo usada',
    valor_base DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor base para o cálculo',
    valor_comissao DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor final da comissão',
    percentual_aplicado DECIMAL(10,4) NULL COMMENT 'Percentual efetivamente aplicado',
    status ENUM('calculada','aprovada','paga','cancelada') NOT NULL DEFAULT 'calculada',
    observacao TEXT NULL,
    approved_by INT NULL COMMENT 'Usuário que aprovou',
    approved_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (forma_comissao_id) REFERENCES formas_comissao(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_order (order_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log detalhado de comissões calculadas';

-- ─────────────────────────────────────────────────────
-- Configurações gerais do módulo de comissão
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comissao_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NULL,
    descricao VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Parâmetros gerais do módulo de comissão';

-- Inserir configurações padrão
INSERT INTO comissao_config (config_key, config_value, descricao) VALUES
('comissao_padrao_percentual', '5.00', 'Percentual padrão de comissão quando não há regra específica'),
('base_calculo_padrao', 'valor_venda', 'Base de cálculo padrão: valor_venda, margem_lucro, valor_produto'),
('aprovacao_automatica', '0', 'Se 1, comissões são aprovadas automaticamente ao calcular'),
('permite_comissao_cancelado', '0', 'Se 1, permite calcular comissão em pedidos cancelados'),
('pipeline_stage_comissao', 'concluido', 'Etapa do pipeline em que a comissão é calculada automaticamente')
ON DUPLICATE KEY UPDATE config_key = config_key;
