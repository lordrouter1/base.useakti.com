# 🚀 Plano de Implementação — Cadastro de Clientes Profissional

> **Data:** 27/03/2026  
> **Baseado em:** Diagnóstico, Proposta de Campos e Proposta de UX  
> **Estimativa total:** 4 fases (Sprint de 2 semanas cada)

---

## Fase 1 — Estrutura do Banco + Model (Prioridade Máxima)

### Objetivo
Expandir a tabela `customers` com todos os novos campos sem quebrar o sistema atual.

### Tarefas

| #  | Tarefa                                                    | Arquivo(s)                          | Esforço |
|----|-----------------------------------------------------------|-------------------------------------|---------|
| 1.1| Criar migration SQL com ALTER TABLE                       | `sql/update_YYYYMMDD_customers_v2.sql` | Médio   |
| 1.2| Adicionar novos campos (person_type, fantasy_name, etc.) | Migration SQL                        | Médio   |
| 1.3| Criar índices e constraints                               | Migration SQL                        | Baixo   |
| 1.4| Criar tabela `customer_contacts` (opcional)               | Migration SQL                        | Baixo   |
| 1.5| Atualizar Model `Customer.php` com novos campos          | `app/models/Customer.php`            | Alto    |
| 1.6| Adicionar métodos: `checkDuplicate()`, `findByDocument()` | `app/models/Customer.php`            | Médio   |
| 1.7| Adicionar validação de CPF/CNPJ no Validator              | `app/utils/Validator.php`            | Médio   |
| 1.8| Garantir retrocompatibilidade (endereço JSON → colunas)   | Migration SQL + Model                | Alto    |

### Migration SQL (Estrutura Prevista)

```sql
-- Arquivo: sql/update_YYYYMMDDHHMM_customers_v2.sql

-- 1. Novos campos de identificação
ALTER TABLE customers
    ADD COLUMN code VARCHAR(20) DEFAULT NULL AFTER id,
    ADD COLUMN person_type ENUM('PF','PJ') NOT NULL DEFAULT 'PF' AFTER code,
    ADD COLUMN fantasy_name VARCHAR(191) DEFAULT NULL AFTER name,
    ADD COLUMN rg_ie VARCHAR(30) DEFAULT NULL AFTER document,
    ADD COLUMN im VARCHAR(30) DEFAULT NULL AFTER rg_ie,
    ADD COLUMN birth_date DATE DEFAULT NULL AFTER im,
    ADD COLUMN gender ENUM('M','F','O') DEFAULT NULL AFTER birth_date;

-- 2. Novos campos de contato
ALTER TABLE customers
    ADD COLUMN email_secondary VARCHAR(191) DEFAULT NULL AFTER email,
    ADD COLUMN cellphone VARCHAR(20) DEFAULT NULL AFTER phone,
    ADD COLUMN phone_commercial VARCHAR(20) DEFAULT NULL AFTER cellphone,
    ADD COLUMN website VARCHAR(255) DEFAULT NULL AFTER phone_commercial,
    ADD COLUMN instagram VARCHAR(100) DEFAULT NULL AFTER website,
    ADD COLUMN contact_name VARCHAR(100) DEFAULT NULL AFTER instagram,
    ADD COLUMN contact_role VARCHAR(80) DEFAULT NULL AFTER contact_name;

-- 3. Novos campos de endereço (desnormalizados)
ALTER TABLE customers
    ADD COLUMN zipcode VARCHAR(10) DEFAULT NULL AFTER address,
    ADD COLUMN address_street VARCHAR(200) DEFAULT NULL AFTER zipcode,
    ADD COLUMN address_number VARCHAR(20) DEFAULT NULL AFTER address_street,
    ADD COLUMN address_complement VARCHAR(100) DEFAULT NULL AFTER address_number,
    ADD COLUMN address_neighborhood VARCHAR(100) DEFAULT NULL AFTER address_complement,
    ADD COLUMN address_city VARCHAR(100) DEFAULT NULL AFTER address_neighborhood,
    ADD COLUMN address_state CHAR(2) DEFAULT NULL AFTER address_city,
    ADD COLUMN address_country VARCHAR(50) DEFAULT 'Brasil' AFTER address_state,
    ADD COLUMN address_ibge VARCHAR(10) DEFAULT NULL AFTER address_country;

-- 4. Novos campos comerciais
ALTER TABLE customers
    ADD COLUMN payment_term VARCHAR(50) DEFAULT NULL AFTER price_table_id,
    ADD COLUMN credit_limit DECIMAL(12,2) DEFAULT NULL AFTER payment_term,
    ADD COLUMN discount_default DECIMAL(5,2) DEFAULT NULL AFTER credit_limit,
    ADD COLUMN seller_id INT DEFAULT NULL AFTER discount_default,
    ADD COLUMN origin VARCHAR(50) DEFAULT NULL AFTER seller_id,
    ADD COLUMN tags VARCHAR(500) DEFAULT NULL AFTER origin;

-- 5. Novos campos de controle
ALTER TABLE customers
    ADD COLUMN observations TEXT DEFAULT NULL AFTER photo,
    ADD COLUMN status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active' AFTER observations,
    ADD COLUMN created_by INT DEFAULT NULL AFTER status,
    ADD COLUMN updated_by INT DEFAULT NULL AFTER created_by,
    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

-- 6. Alterar coluna name para 191 chars
ALTER TABLE customers MODIFY COLUMN name VARCHAR(191) NOT NULL;

-- 7. Índices
ALTER TABLE customers
    ADD INDEX idx_customers_email (email),
    ADD INDEX idx_customers_cellphone (cellphone),
    ADD INDEX idx_customers_status (status),
    ADD INDEX idx_customers_person_type (person_type),
    ADD INDEX idx_customers_city_state (address_city, address_state),
    ADD INDEX idx_customers_seller (seller_id),
    ADD INDEX idx_customers_created (created_at),
    ADD INDEX idx_customers_code (code);

-- Nota: UNIQUE em document é opcional aqui, pois pode haver dados antigos duplicados.
-- Recomenda-se limpar duplicatas antes e depois adicionar:
-- ALTER TABLE customers ADD UNIQUE INDEX idx_customers_document (document);

-- 8. Migrar dados do campo JSON `address` para as novas colunas
UPDATE customers 
SET 
    zipcode = JSON_UNQUOTE(JSON_EXTRACT(address, '$.zipcode')),
    address_street = CONCAT(
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(address, '$.address_type')), ''),
        ' ',
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(address, '$.address_name')), '')
    ),
    address_number = JSON_UNQUOTE(JSON_EXTRACT(address, '$.address_number')),
    address_neighborhood = JSON_UNQUOTE(JSON_EXTRACT(address, '$.neighborhood')),
    address_complement = JSON_UNQUOTE(JSON_EXTRACT(address, '$.complement'))
WHERE address IS NOT NULL AND address != '' AND address != '{}' AND address != 'null';

-- 9. Tabela de contatos adicionais (PJ)
CREATE TABLE IF NOT EXISTS customer_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(80) DEFAULT NULL,
    email VARCHAR(191) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_cc_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Riscos da Fase 1
- **Migração de dados JSON → colunas:** Dados antigos podem ter JSON mal-formado
- **Retrocompatibilidade:** O Model deve continuar lendo o campo `address` (JSON) até que a migração esteja completa
- **Mitigação:** Manter campo `address` (JSON) como backup; novos cadastros usam apenas as colunas

---

## Fase 2 — Controller + Validações (Prioridade Alta)

### Tarefas

| #  | Tarefa                                                    | Arquivo(s)                               | Esforço |
|----|-----------------------------------------------------------|------------------------------------------|---------|
| 2.1| Atualizar `store()` com todos os novos campos             | `app/controllers/CustomerController.php`  | Alto    |
| 2.2| Atualizar `update()` com todos os novos campos            | `app/controllers/CustomerController.php`  | Alto    |
| 2.3| Adicionar validação server-side completa                  | `CustomerController.php` + `Validator.php`| Alto    |
| 2.4| Criar action `view()` para ficha do cliente               | `CustomerController.php`                  | Médio   |
| 2.5| Criar action `checkDuplicate()` (AJAX)                    | `CustomerController.php`                  | Baixo   |
| 2.6| Criar action `searchCep()` (proxy ViaCEP)                | `CustomerController.php`                  | Baixo   |
| 2.7| Criar action `searchCnpj()` (proxy BrasilAPI)            | `CustomerController.php`                  | Médio   |
| 2.8| Criar action `export()` (CSV/Excel)                      | `CustomerController.php`                  | Médio   |
| 2.9| Atualizar `getCustomersList()` com filtros avançados      | `CustomerController.php`                  | Médio   |
| 2.10| Adicionar logs de auditoria em todas as actions          | `CustomerController.php`                  | Baixo   |
| 2.11| Mudar `delete()` de GET para POST + soft delete          | `CustomerController.php`                  | Médio   |
| 2.12| Atualizar rotas em `routes.php`                          | `app/config/routes.php`                   | Baixo   |

---

## Fase 3 — Views / Interface (Prioridade Alta)

### Tarefas

| #  | Tarefa                                                    | Arquivo(s)                               | Esforço |
|----|-----------------------------------------------------------|------------------------------------------|---------|
| 3.1| Redesenhar `create.php` com wizard multi-step             | `app/views/customers/create.php`          | Alto    |
| 3.2| Implementar seletor PF/PJ com campos condicionais        | `create.php` + JS                         | Alto    |
| 3.3| Adicionar máscaras de input (IMask.js)                    | `create.php` + `assets/js/`              | Médio   |
| 3.4| Integrar auto-preenchimento por CEP (ViaCEP)             | JS no form                                | Médio   |
| 3.5| Integrar consulta de CNPJ (BrasilAPI)                    | JS no form                                | Médio   |
| 3.6| Adicionar validação client-side em tempo real             | JS no form                                | Alto    |
| 3.7| Implementar indicador de completude                       | JS no form                                | Baixo   |
| 3.8| Implementar auto-save em localStorage                     | JS no form                                | Médio   |
| 3.9| Redesenhar `edit.php` com o mesmo layout                  | `app/views/customers/edit.php`            | Alto    |
| 3.10| Criar `view.php` — ficha do cliente (read-only)          | `app/views/customers/view.php`            | Alto    |
| 3.11| Adicionar filtros avançados na listagem (drawer)         | `app/views/customers/index.php`           | Alto    |
| 3.12| Adicionar toggle tabela/cards na listagem                | `index.php` + JS                          | Médio   |
| 3.13| Adicionar ações em lote (checkbox + toolbar)             | `index.php` + JS                          | Alto    |
| 3.14| Adicionar exportação (botão com dropdown)                | `index.php` + JS                          | Médio   |
| 3.15| Criar CSS personalizado para o formulário                | `assets/css/customers.css`                | Médio   |

---

## Fase 4 — Integrações e Refinamentos (Prioridade Média)

### Tarefas

| #  | Tarefa                                                    | Arquivo(s)                               | Esforço |
|----|-----------------------------------------------------------|------------------------------------------|---------|
| 4.1| Verificação de duplicidade em tempo real (AJAX)           | JS + Controller                           | Médio   |
| 4.2| Campo de tags com autocomplete e chips                    | JS + Model                                | Médio   |
| 4.3| Histórico de pedidos na ficha do cliente                  | `view.php` + OrderModel                   | Médio   |
| 4.4| Dashboard de métricas por cliente (total gasto, etc.)     | `view.php` + queries                      | Alto    |
| 4.5| Atualizar importação em massa com novos campos            | `CustomerController.php`                  | Médio   |
| 4.6| Atualizar template CSV de importação                      | `CustomerController.php`                  | Baixo   |
| 4.7| Testes unitários para validação de CPF/CNPJ              | `tests/Unit/`                             | Médio   |
| 4.8| Testes de integração para CRUD completo                   | `tests/Unit/`                             | Alto    |
| 4.9| Documentação do módulo atualizado                         | `docs/`                                   | Baixo   |

---

## Cronograma Sugerido

```
Semana 1-2  │  FASE 1: Banco + Model
             │  ├── Migration SQL
             │  ├── Atualizar Model
             │  └── Validações de dados
             │
Semana 3-4  │  FASE 2: Controller
             │  ├── CRUD atualizado
             │  ├── Novas actions (view, export, AJAX)
             │  └── Rotas e permissões
             │
Semana 5-6  │  FASE 3: Views / Interface
             │  ├── Wizard multi-step
             │  ├── PF/PJ dinâmico
             │  ├── Máscaras + ViaCEP + CNPJ
             │  └── Listagem com filtros avançados
             │
Semana 7-8  │  FASE 4: Integrações + Testes
             │  ├── Duplicidade real-time
             │  ├── Tags, histórico, métricas
             │  └── Testes + documentação
```

---

## Checklist de Entrega por Fase

### Fase 1 ✅
- [ ] Migration SQL criada e testada em dev
- [ ] Dados antigos (JSON) migrados para novas colunas
- [ ] Model atualizado com todos os campos
- [ ] Validação de CPF/CNPJ implementada
- [ ] Testes básicos do Model passando

### Fase 2 ✅
- [ ] Controller atualizado (store, update, edit)
- [ ] Novas actions criadas (view, export, checkDuplicate, searchCep, searchCnpj)
- [ ] Rotas registradas
- [ ] Soft delete implementado
- [ ] Logs de auditoria em todas as actions

### Fase 3 ✅
- [ ] Formulário wizard funcionando (4 steps)
- [ ] PF/PJ com campos condicionais
- [ ] Máscaras em todos os campos
- [ ] ViaCEP integrado
- [ ] Consulta CNPJ integrada
- [ ] Validação client-side em tempo real
- [ ] Listagem com filtros avançados
- [ ] Ficha do cliente (view.php)
- [ ] Mobile responsivo

### Fase 4 ✅
- [ ] Duplicidade em tempo real
- [ ] Tags com autocomplete
- [ ] Exportação CSV/Excel
- [ ] Ações em lote
- [ ] Testes unitários e integração
- [ ] Documentação completa

---

## Dependências Técnicas

| Dependência        | Uso                        | CDN / Instalação                      |
|--------------------|----------------------------|---------------------------------------|
| IMask.js           | Máscaras de input          | CDN: `unpkg.com/imask`                |
| ViaCEP API         | Auto-preenchimento CEP     | `viacep.com.br/ws/{cep}/json/`        |
| BrasilAPI          | Consulta CNPJ              | `brasilapi.com.br/api/cnpj/v1/{cnpj}` |
| SweetAlert2        | Alertas (já instalado)     | Já disponível                          |
| Select2            | Autocomplete (já instalado)| Já disponível                          |

---

> **Nota:** Todas as alterações de banco seguem a regra de gerar arquivo SQL em `/sql/` conforme documentação do projeto.
