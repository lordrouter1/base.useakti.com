-- ============================================================
-- Migration: Import Batches + Mapping Profiles
-- Data: 2026-03-29
-- Descrição: Tabelas para rastreamento de lotes de importação,
--            perfis de mapeamento salvos e desfazer importação.
-- ============================================================

-- Tabela de lotes de importação (batch tracking + undo)
CREATE TABLE IF NOT EXISTS import_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL DEFAULT 'customers' COMMENT 'Tipo de entidade importada',
    file_name VARCHAR(255) DEFAULT NULL COMMENT 'Nome original do arquivo',
    total_rows INT NOT NULL DEFAULT 0 COMMENT 'Total de linhas no arquivo',
    imported_count INT NOT NULL DEFAULT 0,
    updated_count INT NOT NULL DEFAULT 0,
    skipped_count INT NOT NULL DEFAULT 0,
    error_count INT NOT NULL DEFAULT 0,
    warning_count INT NOT NULL DEFAULT 0,
    import_mode ENUM('create', 'update', 'create_or_update') NOT NULL DEFAULT 'create',
    mapping_json TEXT DEFAULT NULL COMMENT 'JSON do mapeamento usado',
    errors_json TEXT DEFAULT NULL COMMENT 'JSON dos erros encontrados',
    warnings_json TEXT DEFAULT NULL COMMENT 'JSON dos avisos encontrados',
    status ENUM('processing', 'completed', 'completed_with_errors', 'failed', 'undone') NOT NULL DEFAULT 'processing',
    progress INT NOT NULL DEFAULT 0 COMMENT 'Percentual concluído (0-100)',
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    undone_at DATETIME DEFAULT NULL,
    undone_by INT DEFAULT NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_entity (entity_type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de registros importados por batch (para undo)
CREATE TABLE IF NOT EXISTS import_batch_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    entity_id INT NOT NULL COMMENT 'ID do registro criado/atualizado',
    action ENUM('created', 'updated', 'skipped') NOT NULL DEFAULT 'created',
    original_data TEXT DEFAULT NULL COMMENT 'JSON dos dados originais (para undo de update)',
    line_number INT DEFAULT NULL COMMENT 'Linha no arquivo original',
    INDEX idx_batch (batch_id),
    INDEX idx_entity (entity_id),
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de perfis de mapeamento salvos
CREATE TABLE IF NOT EXISTS import_mapping_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL DEFAULT 'customers',
    name VARCHAR(100) NOT NULL COMMENT 'Nome do perfil',
    mapping_json TEXT NOT NULL COMMENT 'JSON do mapeamento de colunas',
    import_mode ENUM('create', 'update', 'create_or_update') NOT NULL DEFAULT 'create',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_entity (entity_type),
    UNIQUE KEY uk_tenant_name (tenant_id, entity_type, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna import_batch_id na tabela customers para rastreabilidade
ALTER TABLE customers ADD COLUMN IF NOT EXISTS import_batch_id INT DEFAULT NULL COMMENT 'ID do lote de importação';
ALTER TABLE customers ADD INDEX IF NOT EXISTS idx_import_batch (import_batch_id);
