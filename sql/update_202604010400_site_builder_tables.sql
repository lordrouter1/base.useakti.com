-- =================================================================
-- Migration: Site Builder — Tabelas do módulo de construção de loja
-- Data: 2026-04-01
-- Descrição: Cria tabelas para páginas, seções, componentes e
--            configurações de tema do site builder.
-- =================================================================

-- ── Páginas da loja ──
CREATE TABLE IF NOT EXISTS sb_pages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT NOT NULL,
    title           VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL,
    type            ENUM('home','product','collection','cart','contact','custom') DEFAULT 'custom',
    meta_title      VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    sort_order      INT DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_slug (tenant_id, slug),
    INDEX idx_tenant_active (tenant_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seções de cada página ──
CREATE TABLE IF NOT EXISTS sb_sections (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT NOT NULL,
    page_id         INT NOT NULL,
    type            VARCHAR(100) NOT NULL COMMENT 'Tipo da seção (hero-banner, featured-products, etc.)',
    settings        JSON DEFAULT NULL COMMENT 'Configurações da seção (título, colunas, cores, etc.)',
    sort_order      INT DEFAULT 0,
    is_visible      TINYINT(1) DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_page_order (page_id, sort_order),
    CONSTRAINT fk_sb_section_page FOREIGN KEY (page_id) REFERENCES sb_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Componentes dentro de seções ──
CREATE TABLE IF NOT EXISTS sb_components (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT NOT NULL,
    section_id      INT NOT NULL,
    type            VARCHAR(100) NOT NULL COMMENT 'Tipo do componente (text, image, button, product-grid, etc.)',
    content         JSON DEFAULT NULL COMMENT 'Conteúdo e configurações do componente',
    grid_col        INT DEFAULT 12 COMMENT 'Largura no grid (1-12, padrão full-width)',
    grid_row        INT DEFAULT 0 COMMENT 'Posição na linha do grid',
    sort_order      INT DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section_order (section_id, sort_order),
    CONSTRAINT fk_sb_component_section FOREIGN KEY (section_id) REFERENCES sb_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Configurações globais do tema ──
CREATE TABLE IF NOT EXISTS sb_theme_settings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT NOT NULL,
    setting_key     VARCHAR(100) NOT NULL,
    setting_value   TEXT DEFAULT NULL,
    setting_group   VARCHAR(50) DEFAULT 'general' COMMENT 'Grupo: general, header, footer, colors, fonts, etc.',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_key (tenant_id, setting_key),
    INDEX idx_tenant_group (tenant_id, setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
