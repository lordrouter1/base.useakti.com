-- ═══════════════════════════════════════════════════════════════════════════════
-- MIGRATION: Cadastro Profissional de Clientes — Fase 1
-- Arquivo: sql/update_202604070900_customers_v2.sql
-- Data: 07/04/2026
-- Descrição: Expande a tabela customers com ~36 novos campos, cria índices
--            de performance, migra dados JSON→colunas e cria tabela customer_contacts.
-- Idempotente: SIM — pode ser executado múltiplas vezes sem erro.
-- Retrocompatibilidade: SIM — campo address (JSON) mantido como backup.
-- ═══════════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 1: IDENTIFICAÇÃO
-- ─────────────────────────────────────────────────────────────────────────────

-- 1.1 Ampliar name para 191 caracteres
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'name' AND CHARACTER_MAXIMUM_LENGTH < 191);
SET @sql = IF(@col_exists > 0, 'ALTER TABLE customers MODIFY COLUMN name VARCHAR(191) NOT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.2 code — Código interno sequencial (CLI-00001)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'code');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN code VARCHAR(20) NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.3 person_type — Tipo de pessoa (PF / PJ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'person_type');
SET @sql = IF(@col_exists = 0, "ALTER TABLE customers ADD COLUMN person_type ENUM('PF','PJ') NOT NULL DEFAULT 'PF' AFTER code", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.4 fantasy_name — Nome fantasia (PJ) ou apelido (PF)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'fantasy_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN fantasy_name VARCHAR(191) NULL AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.5 rg_ie — RG (PF) ou Inscrição Estadual (PJ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'rg_ie');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN rg_ie VARCHAR(30) NULL AFTER document', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.6 im — Inscrição Municipal (PJ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'im');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN im VARCHAR(30) NULL AFTER rg_ie', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.7 birth_date — Data de nascimento (PF) ou fundação (PJ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'birth_date');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN birth_date DATE NULL AFTER im', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.8 gender — Gênero (PF)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'gender');
SET @sql = IF(@col_exists = 0, "ALTER TABLE customers ADD COLUMN gender ENUM('M','F','O') NULL AFTER birth_date", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 2: CONTATO
-- ─────────────────────────────────────────────────────────────────────────────

-- 2.1 email_secondary — E-mail secundário
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'email_secondary');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN email_secondary VARCHAR(191) NULL AFTER email', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.2 cellphone — Celular / WhatsApp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'cellphone');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN cellphone VARCHAR(20) NULL AFTER phone', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.3 phone_commercial — Telefone comercial
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'phone_commercial');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN phone_commercial VARCHAR(20) NULL AFTER cellphone', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.4 website
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'website');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN website VARCHAR(255) NULL AFTER phone_commercial', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.5 instagram
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'instagram');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN instagram VARCHAR(100) NULL AFTER website', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.6 contact_name — Nome do contato principal (PJ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'contact_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN contact_name VARCHAR(100) NULL AFTER instagram', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.7 contact_role — Cargo do contato principal (PJ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'contact_role');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN contact_role VARCHAR(80) NULL AFTER contact_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 3: ENDEREÇO (colunas diretas — desnormalizadas)
-- O campo "address" (TEXT/JSON) antigo NÃO é removido, mantido como backup.
-- ─────────────────────────────────────────────────────────────────────────────

-- 3.1 zipcode — CEP
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'zipcode');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN zipcode VARCHAR(10) NULL AFTER address', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.2 address_street — Logradouro
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_street');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_street VARCHAR(200) NULL AFTER zipcode', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.3 address_number — Número
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_number');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_number VARCHAR(20) NULL AFTER address_street', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.4 address_complement — Complemento
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_complement');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_complement VARCHAR(100) NULL AFTER address_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.5 address_neighborhood — Bairro
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_neighborhood');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_neighborhood VARCHAR(100) NULL AFTER address_complement', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.6 address_city — Cidade
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_city');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_city VARCHAR(100) NULL AFTER address_neighborhood', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.7 address_state — UF
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_state');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_state CHAR(2) NULL AFTER address_city', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.8 address_country — País
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_country');
SET @sql = IF(@col_exists = 0, "ALTER TABLE customers ADD COLUMN address_country VARCHAR(50) NULL DEFAULT 'Brasil' AFTER address_state", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.9 address_ibge — Código IBGE do município
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address_ibge');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN address_ibge VARCHAR(10) NULL AFTER address_country', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 4: COMERCIAL
-- ─────────────────────────────────────────────────────────────────────────────

-- 4.1 payment_term — Condição de pagamento padrão
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'payment_term');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN payment_term VARCHAR(50) NULL AFTER price_table_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4.2 credit_limit — Limite de crédito
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'credit_limit');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN credit_limit DECIMAL(12,2) NULL AFTER payment_term', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4.3 discount_default — Desconto padrão (%)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'discount_default');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN discount_default DECIMAL(5,2) NULL AFTER credit_limit', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4.4 seller_id — Vendedor responsável (FK → users)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'seller_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN seller_id INT NULL AFTER discount_default', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4.5 origin — Origem do cliente
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'origin');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN origin VARCHAR(50) NULL AFTER seller_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4.6 tags — Tags/etiquetas
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'tags');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN tags VARCHAR(500) NULL AFTER origin', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 5: CONTROLE E AUDITORIA
-- ─────────────────────────────────────────────────────────────────────────────

-- 5.1 observations — Notas internas
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'observations');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN observations TEXT NULL AFTER photo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.2 status — Status do cliente
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'status');
SET @sql = IF(@col_exists = 0, "ALTER TABLE customers ADD COLUMN status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active' AFTER observations", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.3 created_by — Quem criou
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN created_by INT NULL AFTER status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.4 updated_by — Quem atualizou
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN updated_by INT NULL AFTER created_by', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.5 updated_at — Data da última atualização
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.6 deleted_at — Soft delete
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'deleted_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 6: ÍNDICES DE PERFORMANCE
-- Cada índice é criado somente se ainda não existir.
-- ─────────────────────────────────────────────────────────────────────────────

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_email');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_email (email)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_cellphone');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_cellphone (cellphone)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_status');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_status (status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_person_type');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_person_type (person_type)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_city_state');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_city_state (address_city, address_state)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_seller');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_seller (seller_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_created');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_created (created_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_code');
SET @sql = IF(@idx = 0, 'ALTER TABLE customers ADD INDEX idx_customers_code (code)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 7: MIGRAÇÃO DE DADOS — JSON (address) → Colunas
-- Extrai dados do campo address (TEXT/JSON) para as novas colunas individuais.
-- Só atualiza registros que possuem JSON válido e colunas ainda vazias.
-- O campo address original é MANTIDO intacto como backup.
-- ─────────────────────────────────────────────────────────────────────────────

UPDATE customers
SET
    zipcode            = COALESCE(zipcode, NULLIF(JSON_UNQUOTE(JSON_EXTRACT(address, '$.zipcode')), '')),
    address_street     = COALESCE(address_street, NULLIF(TRIM(CONCAT_WS(' ',
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(address, '$.address_type')), ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(address, '$.address_name')), '')
    )), '')),
    address_number     = COALESCE(address_number, NULLIF(JSON_UNQUOTE(JSON_EXTRACT(address, '$.address_number')), '')),
    address_neighborhood = COALESCE(address_neighborhood, NULLIF(JSON_UNQUOTE(JSON_EXTRACT(address, '$.neighborhood')), '')),
    address_complement = COALESCE(address_complement, NULLIF(JSON_UNQUOTE(JSON_EXTRACT(address, '$.complement')), ''))
WHERE
    address IS NOT NULL
    AND address != ''
    AND address != '{}'
    AND JSON_VALID(address);


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 8: GERAR CÓDIGO SEQUENCIAL PARA REGISTROS EXISTENTES SEM CÓDIGO
-- Formato: CLI-00001, CLI-00002, ...
-- ─────────────────────────────────────────────────────────────────────────────

SET @row_number = (SELECT COALESCE(
    MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)), 0)
    FROM customers WHERE code IS NOT NULL AND code LIKE 'CLI-%');

UPDATE customers
SET code = CONCAT('CLI-', LPAD((@row_number := @row_number + 1), 5, '0'))
WHERE code IS NULL OR code = ''
ORDER BY id ASC;


-- ─────────────────────────────────────────────────────────────────────────────
-- BLOCO 9: TABELA customer_contacts (Multi-contato)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customer_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(80) NULL,
    email VARCHAR(191) NULL,
    phone VARCHAR(20) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cc_customer (customer_id),
    CONSTRAINT fk_cc_customer FOREIGN KEY (customer_id)
        REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────────────────────
-- VALIDAÇÃO PÓS-MIGRAÇÃO (queries de verificação — executar manualmente)
-- ─────────────────────────────────────────────────────────────────────────────
-- SELECT id, address, zipcode, address_street, address_number
-- FROM customers
-- WHERE address IS NOT NULL AND address != '{}' AND JSON_VALID(address) AND zipcode IS NULL
-- LIMIT 10;
-- Se retornar resultados, a migração JSON precisa ser revisada.

-- SELECT COUNT(*) AS total, COUNT(code) AS com_codigo, COUNT(person_type) AS com_tipo
-- FROM customers;

-- ═══════════════════════════════════════════════════════════════════════════════
-- FIM DA MIGRATION — Fase 1 Completa
-- ═══════════════════════════════════════════════════════════════════════════════
