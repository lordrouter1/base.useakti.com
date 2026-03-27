# 📦 Proposta de Campos — Cadastro Profissional de Clientes

> **Data:** 27/03/2026  
> **Baseado em:** Diagnóstico `01_DIAGNOSTICO_ATUAL.md`  
> **Referências:** Sistemas profissionais (SAP, TOTVS, Bling, Tiny, Omie, Sankhya)

---

## 1. Nova Estrutura da Tabela `customers`

### 1.1 Campos de Identificação

| Coluna              | Tipo              | Nullable | Descrição                                                |
|---------------------|-------------------|----------|----------------------------------------------------------|
| `id`                | INT AUTO_INCREMENT| NOT NULL | PK                                                       |
| `code`              | VARCHAR(20)       | YES      | Código interno/sequencial do cliente (ex: CLI-00001)      |
| `person_type`       | ENUM('PF','PJ')   | NOT NULL | Tipo de pessoa: Física ou Jurídica                        |
| `name`              | VARCHAR(191)      | NOT NULL | Nome completo (PF) ou Razão Social (PJ)                  |
| `fantasy_name`      | VARCHAR(191)      | YES      | Nome fantasia (PJ) ou apelido (PF)                        |
| `document`          | VARCHAR(20)       | YES      | CPF (PF) ou CNPJ (PJ) — sem formatação, apenas números    |
| `rg_ie`             | VARCHAR(30)       | YES      | RG (PF) ou Inscrição Estadual (PJ)                        |
| `im`                | VARCHAR(30)       | YES      | Inscrição Municipal (PJ)                                   |
| `birth_date`        | DATE              | YES      | Data de nascimento (PF) ou Data de fundação (PJ)           |
| `gender`            | ENUM('M','F','O') | YES      | Gênero (PF): Masculino, Feminino, Outro                    |

### 1.2 Campos de Contato

| Coluna              | Tipo              | Nullable | Descrição                                                |
|---------------------|-------------------|----------|----------------------------------------------------------|
| `email`             | VARCHAR(191)      | YES      | E-mail principal                                          |
| `email_secondary`   | VARCHAR(191)      | YES      | E-mail secundário (financeiro, NF-e, etc.)                 |
| `phone`             | VARCHAR(20)       | YES      | Telefone fixo                                             |
| `cellphone`         | VARCHAR(20)       | YES      | Celular / WhatsApp                                        |
| `phone_commercial`  | VARCHAR(20)       | YES      | Telefone comercial                                        |
| `website`           | VARCHAR(255)      | YES      | Site / URL                                                |
| `instagram`         | VARCHAR(100)      | YES      | Instagram (rede social principal para vendas)              |
| `contact_name`      | VARCHAR(100)      | YES      | Nome do contato principal (PJ)                             |
| `contact_role`      | VARCHAR(80)       | YES      | Cargo/função do contato (PJ)                               |

### 1.3 Campos de Endereço (desnormalizados — colunas diretas)

| Coluna              | Tipo              | Nullable | Descrição                                                |
|---------------------|-------------------|----------|----------------------------------------------------------|
| `zipcode`           | VARCHAR(10)       | YES      | CEP                                                      |
| `address_street`    | VARCHAR(200)      | YES      | Logradouro (tipo + nome: "Rua das Flores")                |
| `address_number`    | VARCHAR(20)       | YES      | Número                                                   |
| `address_complement`| VARCHAR(100)      | YES      | Complemento (Sala, Bloco, Apto)                           |
| `address_neighborhood`| VARCHAR(100)    | YES      | Bairro                                                   |
| `address_city`      | VARCHAR(100)      | YES      | Cidade                                                   |
| `address_state`     | CHAR(2)           | YES      | UF (ex: SP, RJ, MG)                                      |
| `address_country`   | VARCHAR(50)       | YES      | País (default: Brasil)                                    |
| `address_ibge`      | VARCHAR(10)       | YES      | Código IBGE do município (para NF-e)                      |

### 1.4 Campos Comerciais

| Coluna              | Tipo              | Nullable | Descrição                                                |
|---------------------|-------------------|----------|----------------------------------------------------------|
| `price_table_id`    | INT               | YES      | FK para `price_tables`                                    |
| `payment_term`      | VARCHAR(50)       | YES      | Condição de pagamento padrão (ex: "30/60/90", "À vista")   |
| `credit_limit`      | DECIMAL(12,2)     | YES      | Limite de crédito do cliente                               |
| `discount_default`  | DECIMAL(5,2)      | YES      | Desconto padrão em %                                       |
| `seller_id`         | INT               | YES      | FK para `users` — vendedor responsável                     |
| `origin`            | VARCHAR(50)       | YES      | Origem do cliente (Google, Indicação, Feira, etc.)          |
| `tags`              | VARCHAR(500)      | YES      | Tags/etiquetas separadas por vírgula (VIP, Atacado, etc.)  |

### 1.5 Campos de Controle e Auditoria

| Coluna              | Tipo              | Nullable | Descrição                                                |
|---------------------|-------------------|----------|----------------------------------------------------------|
| `photo`             | VARCHAR(255)      | YES      | Caminho da foto/avatar do cliente                          |
| `observations`      | TEXT              | YES      | Observações gerais / notas internas                        |
| `status`            | ENUM('active','inactive','blocked') | NOT NULL DEFAULT 'active' | Status do cliente |
| `created_by`        | INT               | YES      | FK para `users` — quem criou                               |
| `updated_by`        | INT               | YES      | FK para `users` — quem atualizou por último                |
| `created_at`        | TIMESTAMP         | DEFAULT  | Data de criação                                            |
| `updated_at`        | TIMESTAMP         | YES      | Data da última atualização                                 |
| `deleted_at`        | TIMESTAMP         | YES      | Soft delete                                                |

### 1.6 Índices e Constraints

```sql
UNIQUE INDEX idx_customers_document (document) -- Evita cadastro duplicado
INDEX idx_customers_email (email)               -- Busca rápida por e-mail
INDEX idx_customers_phone (cellphone)           -- Busca rápida por celular
INDEX idx_customers_status (status)             -- Filtro por status
INDEX idx_customers_person_type (person_type)   -- Filtro por tipo
INDEX idx_customers_city_state (address_city, address_state) -- Filtro geográfico
INDEX idx_customers_seller (seller_id)          -- Filtro por vendedor
INDEX idx_customers_created (created_at)        -- Ordenação cronológica
```

---

## 2. Tabela Auxiliar: `customer_contacts` (Multi-contato)

Para clientes PJ que possuem múltiplos contatos (financeiro, compras, diretoria, etc.):

| Coluna          | Tipo              | Nullable | Descrição                          |
|-----------------|-------------------|----------|------------------------------------|
| `id`            | INT AUTO_INCREMENT| NOT NULL | PK                                 |
| `customer_id`   | INT               | NOT NULL | FK para `customers`                |
| `name`          | VARCHAR(100)      | NOT NULL | Nome do contato                    |
| `role`          | VARCHAR(80)       | YES      | Cargo/função                       |
| `email`         | VARCHAR(191)      | YES      | E-mail do contato                  |
| `phone`         | VARCHAR(20)       | YES      | Telefone do contato                |
| `is_primary`    | TINYINT(1)        | DEFAULT 0| Se é o contato principal           |
| `notes`         | VARCHAR(255)      | YES      | Observação sobre o contato         |
| `created_at`    | TIMESTAMP         | DEFAULT  | Data de criação                    |

---

## 3. Tabela Auxiliar: `customer_addresses` (Multi-endereço)

Para clientes com endereço de entrega diferente do endereço fiscal:

| Coluna          | Tipo              | Nullable | Descrição                          |
|-----------------|-------------------|----------|------------------------------------|
| `id`            | INT AUTO_INCREMENT| NOT NULL | PK                                 |
| `customer_id`   | INT               | NOT NULL | FK para `customers`                |
| `label`         | VARCHAR(50)       | YES      | Rótulo (Principal, Entrega, Cobrança)|
| `zipcode`       | VARCHAR(10)       | YES      | CEP                                |
| `street`        | VARCHAR(200)      | YES      | Logradouro                         |
| `number`        | VARCHAR(20)       | YES      | Número                             |
| `complement`    | VARCHAR(100)      | YES      | Complemento                        |
| `neighborhood`  | VARCHAR(100)      | YES      | Bairro                             |
| `city`          | VARCHAR(100)      | YES      | Cidade                             |
| `state`         | CHAR(2)           | YES      | UF                                 |
| `ibge_code`     | VARCHAR(10)       | YES      | Código IBGE                        |
| `is_default`    | TINYINT(1)        | DEFAULT 0| Endereço principal                 |
| `created_at`    | TIMESTAMP         | DEFAULT  | Data de criação                    |

> **Nota:** A tabela `customer_addresses` é **opcional na fase 1**. Na primeira versão, os campos de endereço ficam diretamente na tabela `customers`. Quando o sistema evoluir para suportar múltiplos endereços, essa tabela será criada.

---

## 4. Comparativo: Antes vs. Depois

| Aspecto                    | Antes (Atual)            | Depois (Proposto)                    |
|---------------------------|--------------------------|--------------------------------------|
| Campos totais             | 9                        | 40+                                  |
| Distinção PF/PJ           | ❌ Não                   | ✅ Sim (ENUM + lógica condicional)   |
| Endereço                  | JSON em 1 campo          | Colunas separadas + Cidade/Estado     |
| Contato                   | 1 tel + 1 email          | 3 tel + 2 email + redes sociais       |
| Dados fiscais             | Apenas CPF/CNPJ          | + IE, IM, código IBGE                 |
| Dados comerciais          | Apenas tabela de preço   | + crédito, prazo, vendedor, origem    |
| Auditoria                 | Apenas `created_at`      | + `updated_at`, `created_by`, `updated_by`, soft delete |
| Status                    | ❌ Ativo/Inativo         | ✅ active/inactive/blocked            |
| Observações               | ❌ Não salva             | ✅ Campo TEXT dedicado                |
| Índices                   | ❌ Nenhum                | ✅ 8 índices para performance         |
| Unicidade de documento    | ❌ Permite duplicados    | ✅ UNIQUE no document                 |

---

## 5. Mapa de Campos por Tipo de Pessoa

### Campos que mudam conforme `person_type`:

| Campo            | PF (Pessoa Física)        | PJ (Pessoa Jurídica)           |
|------------------|---------------------------|--------------------------------|
| `name`           | Nome completo             | Razão Social                   |
| `fantasy_name`   | Apelido (opcional)        | Nome Fantasia                  |
| `document`       | CPF (11 dígitos)          | CNPJ (14 dígitos)              |
| `rg_ie`          | RG                        | Inscrição Estadual             |
| `im`             | —                         | Inscrição Municipal            |
| `birth_date`     | Data de Nascimento        | Data de Fundação               |
| `gender`         | Gênero                    | — (oculto)                     |
| `contact_name`   | — (oculto)                | Nome do contato principal      |
| `contact_role`   | — (oculto)                | Cargo do contato               |

---

> **Próximo passo:** Veja o arquivo `03_PROPOSTA_UX.md` para a proposta de UX/UI do novo formulário.
