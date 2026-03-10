-- ============================================================
-- BANCO DE DADOS COMPLETO — sistema_grafica
-- Atualizado em: 18/02/2026
-- 
-- Inclui todas as tabelas necessárias para o sistema funcionar.
-- Seguro para executar em banco novo (usa IF NOT EXISTS).
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_grafica;
USE sistema_grafica;

-- ─────────────────────────────────────────────────────
-- MÓDULO: USUÁRIOS E PERMISSÕES
-- ─────────────────────────────────────────────────────

-- Grupos de Usuários (deve ser criada ANTES de users)
CREATE TABLE IF NOT EXISTS user_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuários (Administradores/Funcionários)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'funcionario') DEFAULT 'funcionario',
    group_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissões dos Grupos (pages que cada grupo pode acessar)
CREATE TABLE IF NOT EXISTS group_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    page_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: CONFIGURAÇÕES DA EMPRESA
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: TABELAS DE PREÇO
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS price_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir tabela de preço padrão
INSERT IGNORE INTO price_tables (id, name, description, is_default) 
VALUES (1, 'Tabela Padrão', 'Tabela de preços padrão do sistema', 1);

-- ─────────────────────────────────────────────────────
-- MÓDULO: CLIENTES
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    document VARCHAR(20),
    address TEXT,
    photo VARCHAR(255),
    price_table_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (price_table_id) REFERENCES price_tables(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PRODUTOS
-- ─────────────────────────────────────────────────────

-- Categorias
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subcategorias
CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Produtos/Serviços
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(100) DEFAULT NULL COMMENT 'Código SKU do produto',
    description TEXT,
    category_id INT NULL,
    subcategory_id INT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    use_stock_control TINYINT(1) DEFAULT 0 COMMENT 'Se o produto usa controle de estoque',
    -- Campos Fiscais (NF-e)
    fiscal_ncm VARCHAR(10) DEFAULT NULL COMMENT 'NCM - Nomenclatura Comum do Mercosul',
    fiscal_cest VARCHAR(10) DEFAULT NULL COMMENT 'CEST - Código Especificador da Substituição Tributária',
    fiscal_cfop VARCHAR(10) DEFAULT NULL COMMENT 'CFOP - Código Fiscal de Operações e Prestações',
    fiscal_cst_icms VARCHAR(5) DEFAULT NULL COMMENT 'CST ICMS',
    fiscal_csosn VARCHAR(5) DEFAULT NULL COMMENT 'CSOSN - Simples Nacional',
    fiscal_cst_pis VARCHAR(5) DEFAULT NULL COMMENT 'CST PIS',
    fiscal_cst_cofins VARCHAR(5) DEFAULT NULL COMMENT 'CST COFINS',
    fiscal_cst_ipi VARCHAR(5) DEFAULT NULL COMMENT 'CST IPI',
    fiscal_origem VARCHAR(2) DEFAULT '0' COMMENT 'Origem da mercadoria',
    fiscal_unidade VARCHAR(10) DEFAULT 'UN' COMMENT 'Unidade de medida fiscal',
    fiscal_ean VARCHAR(14) DEFAULT NULL COMMENT 'Código EAN/GTIN',
    fiscal_aliq_icms DECIMAL(5,2) DEFAULT NULL COMMENT 'Alíquota ICMS (%)',
    fiscal_aliq_ipi DECIMAL(5,2) DEFAULT NULL COMMENT 'Alíquota IPI (%)',
    fiscal_aliq_pis DECIMAL(5,4) DEFAULT NULL COMMENT 'Alíquota PIS (%)',
    fiscal_aliq_cofins DECIMAL(5,4) DEFAULT NULL COMMENT 'Alíquota COFINS (%)',
    fiscal_beneficio VARCHAR(20) DEFAULT NULL COMMENT 'Código de benefício fiscal',
    fiscal_info_adicional TEXT DEFAULT NULL COMMENT 'Info adicional do produto na NF-e',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Imagens do Produto (Galeria)
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: GRADES / VARIAÇÕES DE PRODUTOS
-- ─────────────────────────────────────────────────────

-- Tipos de Grade (templates reutilizáveis: Tamanho, Cor, Material, etc.)
CREATE TABLE IF NOT EXISTS product_grade_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-th',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir tipos de grade comuns
INSERT IGNORE INTO product_grade_types (name, description, icon) VALUES
('Tamanho', 'Variações de tamanho do produto (P, M, G, GG, etc.)', 'fas fa-ruler-combined'),
('Cor', 'Variações de cor do produto', 'fas fa-palette'),
('Material', 'Tipo de material ou papel utilizado', 'fas fa-layer-group'),
('Acabamento', 'Tipo de acabamento (laminação, verniz, etc.)', 'fas fa-magic'),
('Gramatura', 'Gramatura do papel (90g, 150g, 300g, etc.)', 'fas fa-weight-hanging'),
('Formato', 'Formato ou dimensão do produto', 'fas fa-expand-arrows-alt'),
('Quantidade', 'Faixas de quantidade (100un, 500un, 1000un)', 'fas fa-boxes');

-- Grades vinculadas a um produto (um produto pode ter múltiplas grades)
CREATE TABLE IF NOT EXISTS product_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    grade_type_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_type_id) REFERENCES product_grade_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_grade (product_id, grade_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valores de cada grade (ex: grade "Tamanho" → valores "P", "M", "G", "GG")
CREATE TABLE IF NOT EXISTS product_grade_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_grade_id INT NOT NULL,
    value VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_grade_id) REFERENCES product_grades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Combinações de grades (produto cartesiano dos valores de todas as grades)
CREATE TABLE IF NOT EXISTS product_grade_combinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    combination_key VARCHAR(255) NOT NULL COMMENT 'Chave serializada ex: "2:5|3:8"',
    combination_label VARCHAR(500) DEFAULT NULL COMMENT 'Label legível ex: "M / Branca"',
    sku VARCHAR(100) DEFAULT NULL,
    price_override DECIMAL(10,2) DEFAULT NULL COMMENT 'Preço específico (NULL = usa preço do produto)',
    stock_quantity INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_combination (product_id, combination_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Itens das Tabelas de Preço (preço customizado por tabela/produto)
CREATE TABLE IF NOT EXISTS price_table_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price_table_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (price_table_id) REFERENCES price_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_table_product (price_table_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PEDIDOS
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    price_table_id INT DEFAULT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('orcamento', 'pendente', 'aprovado', 'em_producao', 'concluido', 'cancelado') DEFAULT 'orcamento',
    pipeline_stage ENUM('contato','orcamento','venda','producao','preparacao','envio','financeiro','concluido','cancelado') DEFAULT 'contato',
    pipeline_entered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deadline DATE NULL,
    priority ENUM('baixa','normal','alta','urgente') DEFAULT 'normal',
    internal_notes TEXT DEFAULT NULL,
    quote_notes TEXT DEFAULT NULL,
    scheduled_date DATE DEFAULT NULL,
    assigned_to INT NULL,
    payment_status ENUM('pendente','parcial','pago') DEFAULT 'pendente',
    payment_method VARCHAR(50) NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    shipping_type ENUM('retirada','entrega','correios') DEFAULT 'retirada',
    shipping_address TEXT NULL,
    tracking_code VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Itens do Pedido
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    grade_combination_id INT DEFAULT NULL,
    grade_description VARCHAR(500) DEFAULT NULL COMMENT 'Texto legível da combinação escolhida',
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Desconto aplicado ao item',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custos Extras dos Pedidos
CREATE TABLE IF NOT EXISTS order_extra_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: PIPELINE DE PRODUÇÃO
-- ─────────────────────────────────────────────────────

-- Histórico de movimentação do pipeline
CREATE TABLE IF NOT EXISTS pipeline_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    from_stage VARCHAR(30) NULL,
    to_stage VARCHAR(30) NOT NULL,
    changed_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Metas por etapa do pipeline (tempo máximo em horas)
CREATE TABLE IF NOT EXISTS pipeline_stage_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage VARCHAR(30) NOT NULL UNIQUE,
    stage_label VARCHAR(50) NOT NULL,
    max_hours INT NOT NULL DEFAULT 24,
    stage_order INT NOT NULL DEFAULT 0,
    color VARCHAR(20) DEFAULT '#3498db',
    icon VARCHAR(50) DEFAULT 'fas fa-circle',
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Metas padrão do pipeline
INSERT IGNORE INTO pipeline_stage_goals (stage, stage_label, max_hours, stage_order, color, icon) VALUES
('contato',    'Contato',       24,  1, '#9b59b6', 'fas fa-phone'),
('orcamento',  'Orçamento',     48,  2, '#3498db', 'fas fa-file-invoice-dollar'),
('venda',      'Venda',         24,  3, '#2ecc71', 'fas fa-handshake'),
('producao',   'Produção',      72,  4, '#e67e22', 'fas fa-industry'),
('preparacao', 'Preparação',    24,  5, '#1abc9c', 'fas fa-boxes-packing'),
('envio',      'Envio/Entrega', 48,  6, '#e74c3c', 'fas fa-truck'),
('financeiro', 'Financeiro',    48,  7, '#f39c12', 'fas fa-coins'),
('concluido',  'Concluído',      0,  8, '#27ae60', 'fas fa-check-double');

-- ─────────────────────────────────────────────────────
-- MÓDULO: SETORES DE PRODUÇÃO
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS production_sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-cogs',
    color VARCHAR(20) DEFAULT '#6c757d',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setores vinculados a produtos (quais setores o produto passa)
CREATE TABLE IF NOT EXISTS product_sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sector_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES production_sectors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_sector (product_id, sector_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setores vinculados a categorias (setores padrão da categoria)
CREATE TABLE IF NOT EXISTS category_sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    sector_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES production_sectors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cat_sector (category_id, sector_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setores vinculados a subcategorias (setores padrão da subcategoria)
CREATE TABLE IF NOT EXISTS subcategory_sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_id INT NOT NULL,
    sector_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES production_sectors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sub_sector (subcategory_id, sector_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: GRADES DE CATEGORIAS E SUBCATEGORIAS
-- ─────────────────────────────────────────────────────

-- Grades vinculadas a uma categoria (grades padrão que produtos herdam)
CREATE TABLE IF NOT EXISTS category_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    grade_type_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_type_id) REFERENCES product_grade_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_grade (category_id, grade_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valores de cada grade de categoria
CREATE TABLE IF NOT EXISTS category_grade_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_grade_id INT NOT NULL,
    value VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_grade_id) REFERENCES category_grades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Combinações de grades de categoria (com controle de inativação)
CREATE TABLE IF NOT EXISTS category_grade_combinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    combination_key VARCHAR(255) NOT NULL,
    combination_label VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_combination (category_id, combination_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grades vinculadas a uma subcategoria (grades padrão com prioridade sobre categoria)
CREATE TABLE IF NOT EXISTS subcategory_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_id INT NOT NULL,
    grade_type_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_type_id) REFERENCES product_grade_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subcategory_grade (subcategory_id, grade_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valores de cada grade de subcategoria
CREATE TABLE IF NOT EXISTS subcategory_grade_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_grade_id INT NOT NULL,
    value VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_grade_id) REFERENCES subcategory_grades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Combinações de grades de subcategoria (com controle de inativação)
CREATE TABLE IF NOT EXISTS subcategory_grade_combinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_id INT NOT NULL,
    combination_key VARCHAR(255) NOT NULL,
    combination_label VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subcategory_combination (subcategory_id, combination_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: LOGS DO SISTEMA
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MIGRAÇÕES / ALTER TABLE — Para bancos já existentes
-- ─────────────────────────────────────────────────────

-- Adiciona coluna sku na tabela products (caso não exista)
SET @dbname = DATABASE();
SET @tablename = 'products';
SET @columnname = 'sku';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    'ALTER TABLE products ADD COLUMN sku VARCHAR(100) DEFAULT NULL COMMENT \'Código SKU do produto\' AFTER name'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adiciona coluna use_stock_control na tabela products (caso não exista)
SET @columnname = 'use_stock_control';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    'ALTER TABLE products ADD COLUMN use_stock_control TINYINT(1) DEFAULT 0 COMMENT \'Se o produto usa controle de estoque\' AFTER stock_quantity'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ─────────────────────────────────────────────────────
-- MÓDULO: FINANCEIRO
-- ─────────────────────────────────────────────────────

-- Parcelas de pagamento dos pedidos
CREATE TABLE IF NOT EXISTS order_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    installment_number INT NOT NULL DEFAULT 1,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_date DATE NOT NULL,
    status ENUM('pendente','pago','atrasado','cancelado') DEFAULT 'pendente',
    paid_date DATE DEFAULT NULL,
    paid_amount DECIMAL(12,2) DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    is_confirmed TINYINT(1) DEFAULT 0,
    confirmed_by INT DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transações financeiras (entradas, saídas e registros)
CREATE TABLE IF NOT EXISTS financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL DEFAULT 'entrada' COMMENT 'entrada, saida ou registro',
    category VARCHAR(50) NOT NULL DEFAULT 'outra_entrada',
    description VARCHAR(500) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    transaction_date DATE NOT NULL,
    reference_type VARCHAR(50) DEFAULT NULL COMMENT 'manual, installment, ofx',
    reference_id INT DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    is_confirmed TINYINT(1) DEFAULT 1,
    confirmed_by INT DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    user_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- MÓDULO: SEGURANÇA — PROTEÇÃO CONTRA FORÇA BRUTA
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(191) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_la_ip_email_date (ip_address, email, attempted_at),
    INDEX idx_la_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
