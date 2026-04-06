-- Migration: Adicionar coluna role na tabela admin_users para niveis de permissao
-- Criado em: 06/04/2026 14:22
-- Sequencial: 1

-- Adicionar coluna role para controle de permissões (superadmin, operator, viewer)
-- Usar procedure para compatibilidade com MySQL 5.7

SET @dbname = DATABASE();

-- Verificar se a coluna role já existe antes de adicionar
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'role') = 0,
    "ALTER TABLE `admin_users` ADD COLUMN `role` ENUM('superadmin','operator','viewer') NOT NULL DEFAULT 'superadmin' AFTER `is_active`",
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
