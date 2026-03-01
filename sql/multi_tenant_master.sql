-- Banco master para multi-tenant por subdomínio
CREATE DATABASE IF NOT EXISTS akti_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE akti_master;

CREATE TABLE IF NOT EXISTS tenant_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(150) NOT NULL,
    subdomain VARCHAR(80) NOT NULL UNIQUE,
    db_host VARCHAR(100) NOT NULL DEFAULT 'localhost',
    db_port INT NOT NULL DEFAULT 3306,
    db_name VARCHAR(100) NOT NULL UNIQUE,
    db_user VARCHAR(100) NOT NULL,
    db_password VARCHAR(255) NOT NULL,
    db_charset VARCHAR(20) NOT NULL DEFAULT 'utf8mb4',
    max_users INT NULL DEFAULT NULL,
    max_products INT NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_db_name_prefix CHECK (db_name LIKE 'akti\\_%')
);

-- Exemplo de cliente cadastrado
INSERT INTO tenant_clients (client_name, subdomain, db_name, db_user, db_password, max_users, max_products)
VALUES ('Cliente Demo', 'demo', 'akti_demo', 'akti_demo_user', 'trocar_senha_forte', 10, 500)
ON DUPLICATE KEY UPDATE
    client_name = VALUES(client_name),
    db_user = VALUES(db_user),
    db_password = VALUES(db_password);
