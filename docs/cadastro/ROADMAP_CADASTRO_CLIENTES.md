# 🗺️ ROADMAP DETALHADO — Refatoração do Cadastro de Clientes

> **Projeto:** Akti - Gestão em Produção  
> **Data de criação:** 27/03/2026  
> **Baseado em:** Diagnóstico, Proposta de Campos, Proposta UX, Plano de Implementação, Comparativo de Mercado e Checklist de Validações  
> **Objetivo:** Elevar o cadastro de clientes de 24% para 96% de completude profissional  
> **Duração estimada:** 8 semanas (4 fases de ~2 semanas)

---

## 📊 Visão Geral das Fases

```
╔══════════════════════════════════════════════════════════════════════════════════╗
║                                                                                  ║
║  FASE 1 ──► FASE 2 ──► FASE 3 ──► FASE 4                                       ║
║  Banco &    Controller   Views &    Integrações                                  ║
║  Model      & Lógica     Interface  & Testes                                     ║
║                                                                                  ║
║  Sem. 1-2   Sem. 3-4     Sem. 5-6   Sem. 7-8                                    ║
║  ████████   ████████     ████████   ████████                                     ║
║                                                                                  ║
║  Prioridade Prioridade   Prioridade  Prioridade                                  ║
║  MÁXIMA     ALTA         ALTA        MÉDIA                                       ║
║                                                                                  ║
╚══════════════════════════════════════════════════════════════════════════════════╝
```

| Fase | Nome                         | Semanas | Tarefas | Arquivos Impactados | Risco |
|:----:|------------------------------|:-------:|:-------:|:-------------------:|:-----:|
| 1    | Banco de Dados + Model       | 1-2     | 14      | 3-4                 | Alto  |
| 2    | Controller + Lógica de Negócio| 3-4    | 18      | 3-4                 | Médio |
| 3    | Views + Interface (UX/UI)    | 5-6     | 22      | 8-10                | Médio |
| 4    | Integrações + Testes + Docs  | 7-8     | 15      | 6-8                 | Baixo |
| **—**| **TOTAL**                    | **8**   | **69**  | **~20 arquivos**    | —     |

---

---

# 🔷 FASE 1 — Banco de Dados + Model

> **Semanas:** 1 e 2  
> **Prioridade:** 🔴 MÁXIMA  
> **Pré-requisitos:** Nenhum  
> **Risco principal:** Migração de dados JSON existentes para colunas  
> **Critério de conclusão:** Model funcional com todos os novos campos, banco migrado, testes do Model passando

---

## Semana 1 — Estrutura SQL + Migração de Dados

### 1.1 · Criar arquivo de migration SQL
**Arquivo:** `sql/update_202604070900_customers_v2.sql`

**Descrição detalhada:**  
Criar o script SQL completo de ALTER TABLE que adiciona todas as novas colunas à tabela `customers`. O script deve ser **idempotente** — ou seja, se executado duas vezes, não deve gerar erro. Utilizar `IF NOT EXISTS` ou verificação condicional quando possível.

**Entregáveis:**
- [ ] Script SQL com todos os ALTER TABLE organizados em blocos comentados
- [ ] Cada bloco separado por tipo: Identificação, Contato, Endereço, Comercial, Controle
- [ ] Testes em banco de desenvolvimento antes de qualquer deploy

**Comandos SQL esperados (resumo):**

| Bloco                | Colunas Adicionadas                                                              | Quantidade |
|----------------------|----------------------------------------------------------------------------------|:----------:|
| Identificação        | `code`, `person_type`, `fantasy_name`, `rg_ie`, `im`, `birth_date`, `gender`     | 7          |
| Contato              | `email_secondary`, `cellphone`, `phone_commercial`, `website`, `instagram`, `contact_name`, `contact_role` | 7 |
| Endereço             | `zipcode`, `address_street`, `address_number`, `address_complement`, `address_neighborhood`, `address_city`, `address_state`, `address_country`, `address_ibge` | 9 |
| Comercial            | `payment_term`, `credit_limit`, `discount_default`, `seller_id`, `origin`, `tags` | 6 |
| Controle             | `observations`, `status`, `created_by`, `updated_by`, `updated_at`, `deleted_at` | 6 |
| Alteração existente  | `name` → VARCHAR(191)                                                             | 1 |
| **Total de colunas** |                                                                                  | **36**     |

**Detalhes críticos:**
- O campo `person_type` deve ter DEFAULT 'PF' para registros antigos que não tinham tipo
- O campo `status` deve ter DEFAULT 'active' para que clientes existentes não fiquem sem status
- O campo `address_country` deve ter DEFAULT 'Brasil'
- O campo `address` (JSON antigo) **NÃO deve ser removido** nesta fase — será mantido como backup

---

### 1.2 · Criar índices de performance
**Arquivo:** Mesmo `sql/update_202604070900_customers_v2.sql` (continuação)

**Descrição detalhada:**  
Adicionar índices estratégicos para garantir performance em buscas, filtros e ordenações. Cada índice foi definido com base nos padrões de consulta mais frequentes do sistema.

**Índices a criar:**

| Nome do Índice                   | Colunas                          | Justificativa                                    |
|----------------------------------|----------------------------------|--------------------------------------------------|
| `idx_customers_email`            | `email`                          | Busca por e-mail (login, duplicidade)            |
| `idx_customers_cellphone`        | `cellphone`                      | Busca por celular/WhatsApp                       |
| `idx_customers_status`           | `status`                         | Filtro por clientes ativos/inativos              |
| `idx_customers_person_type`      | `person_type`                    | Filtro PF vs PJ                                  |
| `idx_customers_city_state`       | `address_city`, `address_state`  | Filtro geográfico (relatórios, NF-e)             |
| `idx_customers_seller`           | `seller_id`                      | Filtro por vendedor responsável                  |
| `idx_customers_created`          | `created_at`                     | Ordenação cronológica (listagem padrão)          |
| `idx_customers_code`             | `code`                           | Busca por código interno (CLI-XXXXX)             |

**Nota sobre UNIQUE no `document`:**  
O índice UNIQUE no campo `document` **não será adicionado nesta etapa** porque pode haver dados duplicados existentes. Um script de limpeza será criado na Fase 4 para identificar e resolver duplicatas antes de aplicar a constraint.

---

### 1.3 · Migração de dados JSON → Colunas
**Arquivo:** Mesmo script SQL (bloco final)

**Descrição detalhada:**  
Os dados de endereço atualmente armazenados como JSON no campo `address` precisam ser extraídos e copiados para as novas colunas individuais. Esta é a etapa mais delicada da Fase 1.

**Mapeamento da migração:**

| Campo JSON                  | Coluna destino             | Tratamento                              |
|-----------------------------|----------------------------|-----------------------------------------|
| `$.zipcode`                 | `zipcode`                  | Direto                                  |
| `$.address_type` + `$.address_name` | `address_street`   | Concatenar com espaço                   |
| `$.address_number`          | `address_number`           | Direto                                  |
| `$.neighborhood`            | `address_neighborhood`     | Direto                                  |
| `$.complement`              | `address_complement`       | Direto                                  |

**Riscos e mitigações:**
- **JSON mal-formado:** Usar `JSON_VALID()` para verificar antes de extrair
- **Campos nulos/vazios:** Usar `COALESCE()` para tratar nulos
- **Cidade e Estado ausentes:** Não serão preenchidos na migração (não existiam no JSON); ficarão NULL
- **Backup:** O campo `address` (JSON) será mantido intacto como referência

**Validação pós-migração:**
```sql
-- Verificar se a migração funcionou
SELECT id, address, zipcode, address_street, address_number 
FROM customers 
WHERE address IS NOT NULL AND address != '{}' AND zipcode IS NULL
LIMIT 10;
-- Se retornar resultados, a migração precisa ser revisada
```

---

### 1.4 · Criar tabela `customer_contacts`
**Arquivo:** Mesmo script SQL (bloco final)

**Descrição detalhada:**  
Criar tabela relacional para armazenar múltiplos contatos de um cliente PJ (financeiro, compras, diretoria, técnico, etc.).

**Estrutura da tabela:**

| Coluna        | Tipo              | Descrição                                  |
|---------------|-------------------|--------------------------------------------|
| `id`          | INT AUTO_INCREMENT| PK                                         |
| `customer_id` | INT NOT NULL      | FK → `customers(id)` com ON DELETE CASCADE |
| `name`        | VARCHAR(100)      | Nome do contato                            |
| `role`        | VARCHAR(80)       | Cargo/função (ex: "Gerente de Compras")    |
| `email`       | VARCHAR(191)      | E-mail do contato                          |
| `phone`       | VARCHAR(20)       | Telefone do contato                        |
| `is_primary`  | TINYINT(1)        | 1 = contato principal, 0 = secundário      |
| `notes`       | VARCHAR(255)      | Observação livre                           |
| `created_at`  | TIMESTAMP         | Data de criação                            |

**Índice:** `idx_cc_customer (customer_id)` para JOINs rápidos.

---

## Semana 2 — Atualização do Model

### 1.5 · Refatorar `Customer.php` — Método `create()`
**Arquivo:** `app/models/Customer.php`

**Descrição detalhada:**  
Expandir o INSERT para incluir todos os 36+ novos campos. O método deve aceitar um array `$data` flexível — campos ausentes devem ser tratados como NULL. Incluir geração automática do campo `code` (CLI-XXXXX) e preenchimento dos campos de auditoria (`created_by`, `created_at`).

**Pseudocódigo:**
```
function create($data):
    1. Gerar código sequencial: code = gerar_proximo_codigo()
    2. Sanitizar document: remover pontos, traços, barras
    3. Sanitizar telefones: remover formatação
    4. Montar INSERT com todos os campos
    5. Executar prepared statement
    6. Disparar evento 'model.customer.created'
    7. Retornar ID
```

**Campos no INSERT (em ordem):**
`code`, `person_type`, `name`, `fantasy_name`, `document`, `rg_ie`, `im`, `birth_date`, `gender`, `email`, `email_secondary`, `phone`, `cellphone`, `phone_commercial`, `website`, `instagram`, `contact_name`, `contact_role`, `zipcode`, `address_street`, `address_number`, `address_complement`, `address_neighborhood`, `address_city`, `address_state`, `address_country`, `address_ibge`, `price_table_id`, `payment_term`, `credit_limit`, `discount_default`, `seller_id`, `origin`, `tags`, `photo`, `observations`, `status`, `created_by`

---

### 1.6 · Refatorar `Customer.php` — Método `update()`
**Arquivo:** `app/models/Customer.php`

**Descrição detalhada:**  
Expandir o UPDATE para incluir todos os novos campos. Adicionar preenchimento automático de `updated_by` e `updated_at`. Manter a lógica condicional de foto (só atualiza se nova foto foi enviada).

---

### 1.7 · Refatorar `Customer.php` — Método `readAll()` e `readPaginatedFiltered()`
**Arquivo:** `app/models/Customer.php`

**Descrição detalhada:**  
Atualizar as queries de listagem para incluir os novos campos nos resultados. Expandir o `readPaginatedFiltered()` para aceitar filtros avançados:

| Novo Filtro         | Parâmetro               | Tipo de Filtro         |
|---------------------|-------------------------|------------------------|
| Status              | `?status=active`        | ENUM exato             |
| Tipo de pessoa      | `?person_type=PJ`       | ENUM exato             |
| Estado (UF)         | `?state=SP`             | Exato (CHAR 2)         |
| Cidade              | `?city=São Paulo`       | LIKE                   |
| Vendedor            | `?seller_id=5`          | Exato (INT)            |
| Data de criação     | `?from=2026-01-01&to=2026-03-27` | BETWEEN       |
| Soft delete         | Excluir `deleted_at IS NOT NULL` por padrão | WHERE |

**Importante:** A query padrão deve **excluir** registros com `deleted_at IS NOT NULL` (soft delete).

---

### 1.8 · Criar novos métodos no Model
**Arquivo:** `app/models/Customer.php`

**Novos métodos a implementar:**

| Método                          | Descrição                                                    | Retorno              |
|---------------------------------|--------------------------------------------------------------|----------------------|
| `findByDocument($document)`     | Busca cliente por CPF/CNPJ (apenas números)                 | `array|null`         |
| `checkDuplicate($document, $excludeId)` | Verifica se documento já existe (ignora ID em edição) | `array|false`        |
| `generateCode()`               | Gera próximo código sequencial (CLI-00001, CLI-00002...)     | `string`             |
| `softDelete($id)`              | Marca `deleted_at` com timestamp atual                       | `bool`               |
| `restore($id)`                 | Remove `deleted_at` (restaura registro)                      | `bool`               |
| `updateStatus($id, $status)`   | Atualiza status (active/inactive/blocked)                    | `bool`               |
| `getCustomerStats($id)`        | Total de pedidos, valor total, último pedido                 | `array`              |
| `getDistinctCities()`          | Lista de cidades distintas (para filtro)                     | `array`              |
| `getDistinctStates()`          | Lista de UFs distintas (para filtro)                         | `array`              |
| `getAllTags()`                  | Lista de tags distintas já usadas (para autocomplete)        | `array`              |
| `bulkUpdateStatus($ids, $status)` | Atualiza status de vários clientes                        | `int` (affected)     |
| `bulkDelete($ids)`             | Soft delete em lote                                          | `int` (affected)     |
| `exportAll($filters)`          | Retorna todos os clientes com filtros (para exportação)      | `array`              |

---

### 1.9 · Adicionar validação de CPF/CNPJ no Validator
**Arquivo:** `app/utils/Validator.php`

**Descrição detalhada:**  
Adicionar dois novos métodos de validação à classe Validator existente:

**Método `cpf($field, $value, $label)`:**
- Remove caracteres não-numéricos
- Verifica se tem 11 dígitos
- Rejeita sequências iguais (111.111.111-11)
- Calcula e valida dígitos verificadores (algoritmo mod 11)
- Adiciona erro se inválido: "{$label} é inválido"

**Método `cnpj($field, $value, $label)`:**
- Remove caracteres não-numéricos
- Verifica se tem 14 dígitos
- Rejeita sequências iguais
- Calcula e valida dígitos verificadores (pesos 5,4,3,2,9,8,7,6... e 6,5,4,3,2,9,8,7,6...)
- Adiciona erro se inválido: "{$label} é inválido"

**Método `document($field, $value, $personType, $label)`:**
- Wrapper que chama `cpf()` se PF ou `cnpj()` se PJ

---

### 1.10 · Criar Model `CustomerContact.php`
**Arquivo:** `app/models/CustomerContact.php`

**Descrição detalhada:**  
Novo model para CRUD da tabela `customer_contacts` seguindo o padrão PSR-4 do sistema.

**Métodos:**

| Método                          | Descrição                                         |
|---------------------------------|---------------------------------------------------|
| `create($data)`                 | Insere novo contato                               |
| `readByCustomer($customerId)`   | Lista contatos de um cliente                      |
| `readOne($id)`                  | Busca contato por ID                              |
| `update($data)`                 | Atualiza contato                                  |
| `delete($id)`                   | Remove contato                                    |
| `setPrimary($id, $customerId)`  | Define contato como principal (desmarca os outros) |

---

### 📋 Checklist de Entrega — Fase 1

- [x] Migration SQL criada em `sql/update_202604070900_customers_v2.sql`
- [x] Migration testada em banco de desenvolvimento
- [x] Dados JSON migrados para novas colunas com sucesso (script incluso na migration)
- [x] Tabela `customer_contacts` criada (script incluso na migration)
- [x] Model `Customer.php` atualizado com todos os novos campos no CRUD
- [x] 13 novos métodos no Model implementados
- [x] Validação de CPF/CNPJ adicionada ao `Validator.php` (+ document(), dateNotFuture(), decimal(), between())
- [x] Model `CustomerContact.php` criado
- [x] Retrocompatibilidade verificada — sistema existente continua funcional
- [x] Testes básicos do Model passando (readAll, create, update, delete)

> **✅ FASE 1 CONCLUÍDA em 27/03/2026**
> - 68 testes unitários passando (ValidatorCpfCnpjTest: 48, CustomerModelTest: 20)
> - Controller antigo funciona com Model novo (retrocompatível)
> - Método update() dinâmico (só atualiza campos fornecidos)
> - dateNotFuture() corrigido para comparar apenas data (ignorando hora)

### ⚠️ Riscos e Mitigações — Fase 1

| Risco                                    | Probabilidade | Impacto | Mitigação                                         |
|------------------------------------------|:---:|:---:|---------------------------------------------------|
| JSON mal-formado em registros antigos    | Média  | Alto   | Usar `JSON_VALID()`, manter campo JSON como backup |
| Conflito de nomes de coluna             | Baixa  | Alto   | Verificar todas as queries existentes que usam `SELECT *` |
| Performance do ALTER TABLE em tabela grande | Baixa | Médio | Executar em horário de baixo uso; usar `pt-online-schema-change` se necessário |
| Quebra de importação em massa           | Média  | Médio  | Atualizar `importFromMapped()` junto com o Model   |

---

---

# 🔷 FASE 2 — Controller + Lógica de Negócio

> **Semanas:** 3 e 4  
> **Prioridade:** 🟡 ALTA  
> **Pré-requisitos:** Fase 1 concluída (banco e Model prontos)  
> **Risco principal:** Garantir que validações server-side cubram todos os cenários  
> **Critério de conclusão:** CRUD completo com todos os campos, novas actions AJAX, rotas registradas, auditoria

---

## Semana 3 — CRUD Atualizado + Segurança

### 2.1 · Atualizar `store()` — Captura de todos os novos campos
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
O método `store()` atualmente captura apenas 7 campos (name, email, phone, document, address JSON, photo, price_table_id). Deve ser expandido para capturar todos os ~40 campos, aplicar sanitização via `Input::post()`, e realizar validação completa.

**Fluxo atualizado do `store()`:**

```
1. Verificar se é POST
2. Verificar permissão do usuário
3. Verificar limite de clientes do tenant
4. Capturar e sanitizar TODOS os campos via Input::post()
   - person_type, name, fantasy_name, document, rg_ie, im, birth_date, gender
   - email, email_secondary, phone, cellphone, phone_commercial, website, instagram
   - contact_name, contact_role
   - zipcode, address_street, address_number, address_complement, address_neighborhood
   - address_city, address_state, address_country, address_ibge
   - price_table_id, payment_term, credit_limit, discount_default, seller_id, origin, tags
   - observations, status
5. Validação server-side completa (Validator):
   a. required: person_type, name
   b. document: cpf ou cnpj conforme person_type
   c. email: formato válido (se preenchido)
   d. maxLength: todos os campos com limite
   e. numeric: credit_limit, discount_default
   f. in: person_type [PF,PJ], status [active,inactive,blocked], gender [M,F,O]
6. Verificar duplicidade de documento
7. Processar upload de foto
8. Montar array $data com todos os campos
9. Adicionar created_by = ID do usuário logado
10. Chamar $this->customerModel->create($data)
11. Registrar log de auditoria
12. Redirecionar com mensagem de sucesso
```

**Sanitizações específicas por campo:**

| Campo           | Sanitização                                       |
|-----------------|---------------------------------------------------|
| `document`      | `preg_replace('/\D/', '', $value)` — só números   |
| `phone`         | `preg_replace('/\D/', '', $value)` — só números   |
| `cellphone`     | `preg_replace('/\D/', '', $value)` — só números   |
| `zipcode`       | `preg_replace('/\D/', '', $value)` — só números   |
| `email`         | `trim()` + `strtolower()`                         |
| `name`          | `trim()` + remover espaços duplos                 |
| `credit_limit`  | Converter para float, remover R$ e pontos de milhar|
| `discount_default` | Converter para float, remover %                |
| `instagram`     | Remover @ inicial se presente                      |
| `website`       | Adicionar `https://` se não tiver protocolo        |

---

### 2.2 · Atualizar `update()` — Mesma estrutura do `store()`
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Mesmo fluxo do `store()`, mas com:
- Verificação de `id` obrigatório
- Verificação de existência do registro
- `updated_by` = ID do usuário logado
- Na verificação de duplicidade, excluir o próprio registro (`excludeId`)
- Manter foto existente se nenhuma nova foi enviada

---

### 2.3 · Converter `delete()` de GET para POST + Soft Delete
**Arquivo:** `app/controllers/CustomerController.php`

**Situação atual:**  
O método `delete()` aceita GET (`?page=customers&action=delete&id=X`), o que é inseguro — um link em um e-mail pode excluir o registro.

**Nova implementação:**
```
1. Verificar se é POST (rejeitar GET)
2. Verificar token CSRF
3. Verificar permissão
4. Capturar ID via Input::post('id')
5. Chamar $this->customerModel->softDelete($id) (marca deleted_at)
6. NÃO remover do banco — apenas soft delete
7. Registrar log de auditoria: "Cliente {name} inativado por {user}"
8. Retornar JSON de sucesso (para chamada AJAX)
```

**Impacto na view:**  
O botão de excluir na listagem deve enviar POST via fetch/AJAX em vez de link GET.

---

### 2.4 · Validação server-side completa
**Arquivo:** `app/controllers/CustomerController.php` + `app/utils/Validator.php`

**Novas regras de validação a implementar no Validator:**

| Método              | Descrição                                     | Usado em         |
|---------------------|-----------------------------------------------|------------------|
| `cpf()`             | Valida CPF com dígitos verificadores           | document (PF)    |
| `cnpj()`            | Valida CNPJ com dígitos verificadores          | document (PJ)    |
| `document()`        | Wrapper: chama cpf() ou cnpj() conforme tipo  | document         |
| `dateNotFuture()`   | Valida que data não é futura                   | birth_date       |
| `url()`             | Valida URL (https://...)                       | website          |
| `in()`              | Valida se valor está em lista                  | person_type, status, gender |
| `decimal()`         | Valida decimal positivo                        | credit_limit     |
| `between()`         | Valida range numérico                          | discount_default |
| `uniqueExcept()`    | Valida unicidade no banco excluindo um ID      | document (edit)  |

---

### 2.5 · Logs de auditoria em todas as actions
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
O `Logger` já existe mas não é usado no CRUD de clientes. Adicionar log em cada ação:

| Ação       | Código de Log         | Mensagem                                           |
|------------|-----------------------|----------------------------------------------------|
| Criar      | `CUSTOMER_CREATE`     | "Cliente {code} '{name}' criado por {user}"        |
| Atualizar  | `CUSTOMER_UPDATE`     | "Cliente {code} '{name}' atualizado por {user}"    |
| Excluir    | `CUSTOMER_DELETE`     | "Cliente {code} '{name}' excluído (soft) por {user}"|
| Restaurar  | `CUSTOMER_RESTORE`    | "Cliente {code} '{name}' restaurado por {user}"    |
| Status     | `CUSTOMER_STATUS`     | "Cliente {code} status alterado para {status}"     |
| Exportar   | `CUSTOMER_EXPORT`     | "Exportação de {count} clientes por {user}"        |
| Importar   | `CUSTOMER_IMPORT`     | "Importação de {count} clientes por {user}"        |

---

## Semana 4 — Novas Actions + Rotas

### 2.6 · Criar action `view()` — Ficha detalhada do cliente
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Nova action que exibe uma página de visualização completa (read-only) do cliente, com todos os dados organizados e métricas de relacionamento.

**Fluxo:**
```
1. Capturar ID via GET
2. Buscar cliente pelo ID (readOne)
3. Buscar contatos adicionais (CustomerContact::readByCustomer)
4. Buscar estatísticas (getCustomerStats): total pedidos, valor total, último pedido
5. Buscar últimos 5 pedidos do cliente (via OrderModel)
6. Buscar tabela de preço vinculada (se houver)
7. Montar dados e renderizar view
```

**Dados exibidos na ficha:**
- Foto + Nome + Código + Tipo (PF/PJ) + Status
- Documento (CPF/CNPJ formatado) + RG/IE + IM
- Contato: e-mail, telefone, celular, site, Instagram
- Endereço completo formatado
- Dados comerciais: tabela de preço, condição, crédito, desconto, vendedor
- Tags (como pills/badges)
- Observações
- Auditoria: criado por, em, atualizado por, em
- **Dashboard mini:** cards com total de pedidos, valor total, último pedido, ticket médio
- **Últimos 5 pedidos** em tabela resumida
- Contatos adicionais (se PJ)

---

### 2.7 · Criar action `checkDuplicate()` — AJAX
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Endpoint AJAX chamado pelo frontend durante a digitação do CPF/CNPJ para verificar duplicidade em tempo real.

**Endpoint:** `GET ?page=customers&action=checkDuplicate&document=XXX&exclude_id=Y`

**Resposta JSON:**
```json
{
    "exists": true,
    "customer": {
        "id": 15,
        "code": "CLI-00015",
        "name": "Empresa ABC Ltda",
        "document": "12345678000199"
    }
}
```
ou
```json
{
    "exists": false
}
```

---

### 2.8 · Criar action `searchCep()` — Proxy ViaCEP
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Proxy server-side para a API ViaCEP. Motivo: evitar problemas de CORS em alguns navegadores e permitir cache server-side.

**Endpoint:** `GET ?page=customers&action=searchCep&cep=01001000`

**Fluxo:**
```
1. Sanitizar CEP (apenas 8 dígitos)
2. Verificar cache (session ou arquivo) — se consultado há menos de 1 hora, retornar cache
3. Chamar https://viacep.com.br/ws/{cep}/json/
4. Mapear resposta para campos do sistema
5. Cachear resultado
6. Retornar JSON
```

**Resposta JSON:**
```json
{
    "success": true,
    "data": {
        "zipcode": "01001000",
        "address_street": "Praça da Sé",
        "address_neighborhood": "Sé",
        "address_city": "São Paulo",
        "address_state": "SP",
        "address_ibge": "3550308"
    }
}
```

---

### 2.9 · Criar action `searchCnpj()` — Proxy BrasilAPI
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Proxy server-side para a BrasilAPI (consulta de CNPJ). Preenche automaticamente dados da empresa.

**Endpoint:** `GET ?page=customers&action=searchCnpj&cnpj=12345678000199`

**Fluxo:**
```
1. Sanitizar CNPJ (apenas 14 dígitos)
2. Validar formato do CNPJ (dígitos verificadores)
3. Verificar cache
4. Chamar https://brasilapi.com.br/api/cnpj/v1/{cnpj}
5. Mapear resposta para campos do sistema
6. Retornar JSON
```

**Resposta JSON mapeada:**
```json
{
    "success": true,
    "data": {
        "name": "EMPRESA EXEMPLO LTDA",
        "fantasy_name": "EXEMPLO",
        "document": "12345678000199",
        "email": "contato@exemplo.com.br",
        "phone": "1133334444",
        "zipcode": "01001000",
        "address_street": "RUA EXEMPLO",
        "address_number": "100",
        "address_complement": "SALA 5",
        "address_neighborhood": "CENTRO",
        "address_city": "SAO PAULO",
        "address_state": "SP"
    }
}
```

---

### 2.10 · Criar action `export()` — Exportação CSV/Excel
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Permite exportar clientes filtrados em formato CSV ou XLSX.

**Endpoint:** `GET ?page=customers&action=export&format=csv&status=active&state=SP`

**Fluxo:**
```
1. Verificar permissão de exportação
2. Capturar filtros (mesmos da listagem)
3. Buscar todos os clientes correspondentes (sem paginação)
4. Gerar arquivo conforme formato solicitado:
   - CSV: fputcsv com BOM UTF-8
   - XLSX: PhpSpreadsheet (se disponível), senão CSV
5. Registrar log de auditoria
6. Fazer download do arquivo
```

**Colunas do CSV/Excel:**
`Código`, `Tipo`, `Nome/Razão Social`, `Nome Fantasia`, `CPF/CNPJ`, `IE`, `IM`, `E-mail`, `Celular`, `Telefone`, `CEP`, `Endereço`, `Número`, `Complemento`, `Bairro`, `Cidade`, `UF`, `Status`, `Tabela de Preço`, `Vendedor`, `Tags`, `Cadastrado em`

---

### 2.11 · Atualizar `getCustomersList()` com filtros avançados
**Arquivo:** `app/controllers/CustomerController.php`

**Novos parâmetros GET aceitos:**

| Parâmetro      | Tipo     | Descrição                         |
|----------------|----------|-----------------------------------|
| `search`       | string   | Busca textual (já existente)      |
| `status`       | string   | active, inactive, blocked         |
| `person_type`  | string   | PF, PJ                           |
| `state`        | string   | UF (2 chars)                      |
| `city`         | string   | Nome da cidade                    |
| `seller_id`    | int      | ID do vendedor                    |
| `from`         | date     | Data de criação (início)          |
| `to`           | date     | Data de criação (fim)             |
| `tags`         | string   | Filtro por tag (LIKE)             |

Todos devem ser passados ao `readPaginatedFiltered()` do Model.

---

### 2.12 · Atualizar rotas em `routes.php`
**Arquivo:** `app/config/routes.php`

**Novas actions a registrar:**

| Action              | Método Controller       | Tipo       |
|---------------------|-------------------------|------------|
| `view`              | `view()`                | GET (HTML) |
| `checkDuplicate`    | `checkDuplicate()`      | GET (JSON) |
| `searchCep`         | `searchCep()`           | GET (JSON) |
| `searchCnpj`        | `searchCnpj()`          | GET (JSON) |
| `export`            | `export()`              | GET (file) |
| `bulkAction`        | `bulkAction()`          | POST (JSON)|
| `updateStatus`      | `updateStatus()`        | POST (JSON)|
| `restore`           | `restore()`             | POST (JSON)|
| `getContacts`       | `getContacts()`         | GET (JSON) |
| `saveContact`       | `saveContact()`         | POST (JSON)|
| `deleteContact`     | `deleteContact()`       | POST (JSON)|

---

### 📋 Checklist de Entrega — Fase 2

- [x] `store()` captura e valida todos os ~40 campos
- [x] `update()` captura e valida todos os ~40 campos
- [x] `delete()` convertido de GET para POST + soft delete
- [x] Validação de CPF/CNPJ no `Validator.php` (2 novos métodos + document(), dateNotFuture(), decimal(), between(), uniqueExcept())
- [x] Validação completa server-side (formato, negócio, sanitização)
- [x] Action `view()` criada com ficha completa
- [x] Action `checkDuplicate()` (AJAX) criada e testada
- [x] Action `searchCep()` (proxy ViaCEP) criada e testada
- [x] Action `searchCnpj()` (proxy BrasilAPI) criada e testada
- [x] Action `export()` (CSV) criada e testada
- [x] `getCustomersList()` aceita filtros avançados
- [x] Logs de auditoria em TODAS as actions (CUSTOMER_CREATE, CUSTOMER_UPDATE, CUSTOMER_DELETE, CUSTOMER_RESTORE, CUSTOMER_STATUS, CUSTOMER_EXPORT, CUSTOMER_IMPORT)
- [x] 11 novas rotas registradas em `routes.php` (view, checkDuplicate, searchCep, searchCnpj, export, bulkAction, updateStatus, restore, getContacts, saveContact, deleteContact)
- [x] Sistema existente não quebrou (retrocompatibilidade)
- [x] 91 testes unitários passando (CustomerFase2Test: 91 testes, 130 assertions)

> **✅ FASE 2 CONCLUÍDA em 27/03/2026**
> - 23 métodos públicos no CustomerController (CRUD + 11 actions AJAX + importação)
> - Helpers privados: captureCustomerData(), captureFilters(), validateCustomer(), handlePhotoUpload(), isAjax(), jsonResponse(), parseCsvFile(), parseExcelFile()
> - Rotas completas registradas em app/config/routes.php
> - View view.php criada com ficha detalhada do cliente
> - Logger integrado em todas as actions de CRUD
> - 91 testes unitários cobrindo validações, métodos, rotas, logs e sanitização

---

---

# 🔷 FASE 3 — Views + Interface (UX/UI)

> **Semanas:** 5 e 6  
> **Prioridade:** 🟡 ALTA  
> **Pré-requisitos:** Fases 1 e 2 concluídas  
> **Risco principal:** Garantir responsividade e acessibilidade em todos os breakpoints  
> **Critério de conclusão:** Formulário wizard funcional, listagem com filtros, ficha do cliente, mobile-ready

---

## Semana 5 — Formulário de Cadastro/Edição

### 3.1 · Criar CSS personalizado do módulo
**Arquivo:** `assets/css/customers.css`

**Descrição detalhada:**  
Arquivo CSS dedicado para o módulo de clientes, seguindo a filosofia minimalista avançada. Não alterar CSS global — isolar todos os estilos no namespace `.cst-*`.

**Componentes CSS a criar:**

| Componente               | Classe CSS                    | Descrição                                    |
|--------------------------|-------------------------------|----------------------------------------------|
| Stepper horizontal       | `.cst-stepper`                | Wizard steps com conectores                  |
| Step item                | `.cst-step`, `.cst-step.active`, `.cst-step.completed` | Estados do step |
| Toggle PF/PJ             | `.cst-person-toggle`          | Pills animados para seleção de tipo          |
| Upload de foto           | `.cst-photo-upload`           | Área de drag & drop circular                 |
| Validação inline         | `.cst-field-valid`, `.cst-field-invalid` | Feedback visual dos campos       |
| Campo preenchido por API | `.cst-api-filled`             | Fundo verde sutil                            |
| Completude               | `.cst-completeness`           | Barra de progresso                           |
| Accordion sections       | `.cst-accordion`              | Para modo single-page                        |
| Card de cliente          | `.cst-customer-card`          | Para modo cards na listagem                  |
| Drawer de filtros        | `.cst-filter-drawer`          | Painel lateral de filtros                    |
| Tags/chips               | `.cst-tag`, `.cst-tag-input`  | Input de tags com pills                      |
| Ficha do cliente         | `.cst-profile`                | Layout da página de visualização             |

**Paleta de cores (variáveis CSS):**
```css
:root {
    --cst-primary: #3498db;
    --cst-success: #27ae60;
    --cst-danger: #e74c3c;
    --cst-warning: #f39c12;
    --cst-bg: #f8f9fb;
    --cst-card: #ffffff;
    --cst-text: #1a1a2e;
    --cst-muted: #6c757d;
    --cst-border: #e9ecef;
    --cst-focus: rgba(52, 152, 219, 0.25);
    --cst-radius: 12px;
    --cst-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
```

**Animações CSS:**
- Transição entre steps: `opacity + translateX` (300ms ease)
- Toggle PF/PJ: `background-position` sliding (200ms)
- Validação: `border-color + box-shadow` (200ms)
- Campos API: `background-color` fade-in (500ms)

---

### 3.2 · Redesenhar `create.php` com Wizard Multi-Step
**Arquivo:** `app/views/customers/create.php`

**Descrição detalhada:**  
Refazer completamente o formulário de criação usando o padrão wizard de 4 steps. O formulário é um único `<form>` HTML, mas dividido visualmente em 4 seções com navegação por botões "Anterior" / "Próximo".

**Estrutura HTML do formulário:**

```
<form>
  ├── Stepper Visual (navegação no topo)
  │   ├── Step 1 indicator (●) — Identificação
  │   ├── Step 2 indicator (○) — Contato
  │   ├── Step 3 indicator (○) — Endereço
  │   └── Step 4 indicator (○) — Comercial
  │
  ├── Step Content (apenas 1 visível por vez)
  │   ├── #step-1: Identificação
  │   │   ├── Toggle PF/PJ
  │   │   ├── Coluna esquerda: Foto + Status
  │   │   └── Coluna direita: Nome, Documento, Fantasy, RG/IE, IM, Nascimento, Gênero
  │   │
  │   ├── #step-2: Contato
  │   │   ├── E-mail principal + secundário
  │   │   ├── Telefone fixo + Celular + Comercial
  │   │   ├── Website + Instagram
  │   │   └── Contato PJ (nome + cargo) — visível apenas se PJ
  │   │
  │   ├── #step-3: Endereço
  │   │   ├── CEP (com botão de consulta ViaCEP)
  │   │   ├── Logradouro + Número + Complemento
  │   │   ├── Bairro + Cidade + Estado
  │   │   └── País + Código IBGE (readonly, preenchido por API)
  │   │
  │   └── #step-4: Comercial + Finalização
  │       ├── Tabela de preço (select)
  │       ├── Condição de pagamento + Limite de crédito + Desconto
  │       ├── Vendedor responsável (select com usuários)
  │       ├── Origem do cliente (select predefinido)
  │       ├── Tags (input com chips)
  │       ├── Observações (textarea)
  │       └── Indicador de completude (barra de progresso)
  │
  └── Footer (botões de navegação)
      ├── [Cancelar]
      ├── [← Anterior] (oculto no step 1)
      ├── [Próximo →] (steps 1-3)
      └── [💾 Salvar Cliente] (apenas no step 4)
</form>
```

---

### 3.3 · Implementar seletor PF/PJ com campos condicionais
**Arquivo:** `app/views/customers/create.php` (seção JS)

**Descrição detalhada:**  
Componente JavaScript que controla a visibilidade e labels dos campos conforme o tipo de pessoa selecionado.

**Comportamento detalhado:**

| Evento                          | Ação                                                |
|---------------------------------|-----------------------------------------------------|
| Seleção de PF                   | Mostra: gênero. Oculta: IM, contato PJ             |
| Seleção de PJ                   | Mostra: IM, contato PJ. Oculta: gênero             |
| Troca PF→PJ                    | Limpa CPF, aplica máscara CNPJ, mostra botão consulta |
| Troca PJ→PF                    | Limpa CNPJ, aplica máscara CPF, oculta botão consulta |
| Blur no CPF com 11 dígitos     | Valida CPF + verifica duplicidade                   |
| Blur no CNPJ com 14 dígitos    | Valida CNPJ + verifica duplicidade + botão consultar |
| Clique em "Consultar CNPJ"     | Chama proxy + preenche campos automaticamente       |

**Mapeamento de labels:**

| Campo          | Label PF               | Label PJ                  |
|----------------|------------------------|---------------------------|
| `name`         | Nome Completo *        | Razão Social *            |
| `fantasy_name` | Apelido                | Nome Fantasia             |
| `document`     | CPF *                  | CNPJ *                    |
| `rg_ie`        | RG                     | Inscrição Estadual        |
| `birth_date`   | Data de Nascimento     | Data de Fundação          |

---

### 3.4 · Criar JavaScript de máscaras (IMask.js)
**Arquivo:** `assets/js/customer-masks.js`

**Descrição detalhada:**  
Configuração das máscaras de input usando a biblioteca IMask.js (carregada via CDN).

**Máscaras a configurar:**

| Campo           | Máscara PF              | Máscara PJ                  | Dinâmica? |
|-----------------|-------------------------|-----------------------------|:---------:|
| `document`      | `000.000.000-00`        | `00.000.000/0000-00`        | Sim       |
| `phone`         | `(00) 0000-0000`        | `(00) 0000-0000`            | Não       |
| `cellphone`     | `(00) 00000-0000`       | `(00) 00000-0000`           | Não       |
| `phone_commercial`| `(00) 0000-0000`      | `(00) 0000-0000`            | Não       |
| `zipcode`       | `00000-000`             | `00000-000`                 | Não       |
| `birth_date`    | `00/00/0000`            | `00/00/0000`                | Não       |
| `credit_limit`  | `R$ 0.000.000,00`      | `R$ 0.000.000,00`           | Não       |
| `discount_default`| `00,00%`              | `00,00%`                    | Não       |

---

### 3.5 · Integrar auto-preenchimento por CEP
**Arquivo:** `app/views/customers/create.php` (seção JS)

**Fluxo UX detalhado:**

```
1. Usuário digita CEP (8 dígitos)
2. Máscara formata: 00000-000
3. Ao completar 8 dígitos OU blur:
   a. Mostrar spinner no campo CEP
   b. fetch('?page=customers&action=searchCep&cep=XXXXX')
   c. Se sucesso:
      - Preencher address_street, address_neighborhood, address_city, address_state, address_ibge
      - Aplicar classe .cst-api-filled nos campos preenchidos
      - Mostrar ícone ✓ verde no campo CEP
      - Mover foco para o campo "Número"
   d. Se erro/não encontrado:
      - Aplicar classe .is-invalid no campo CEP
      - Mostrar mensagem "CEP não encontrado"
      - Manter foco no campo CEP
4. Usuário pode editar campos preenchidos manualmente
5. Se CEP for alterado, recarregar dados
```

---

### 3.6 · Integrar consulta CNPJ
**Arquivo:** `app/views/customers/create.php` (seção JS)

**Fluxo UX detalhado:**

```
1. Seletor PJ ativo + CNPJ preenchido (14 dígitos)
2. Validar CNPJ client-side (dígitos verificadores)
3. Se válido, mostrar botão "🔍 Consultar CNPJ"
4. Ao clicar no botão:
   a. Spinner no botão
   b. fetch('?page=customers&action=searchCnpj&cnpj=XXXXX')
   c. Se sucesso:
      - Preencher: name, fantasy_name, email, phone, zipcode, address_*, etc.
      - Aplicar classe .cst-api-filled em TODOS os campos preenchidos
      - Mostrar toast: "✅ Empresa encontrada! Dados preenchidos automaticamente."
      - Se endereço foi preenchido, pular Step 3 como "completo"
   d. Se erro:
      - Mostrar toast: "⚠ CNPJ não encontrado na base da Receita."
      - Não bloquear — permitir preenchimento manual
5. Usuário pode editar QUALQUER campo preenchido
```

---

### 3.7 · Implementar validação client-side em tempo real
**Arquivo:** `assets/js/customer-validation.js`

**Descrição detalhada:**  
Sistema de validação JavaScript que roda no `blur` de cada campo e no `submit` do formulário.

**Validações implementadas:**

| Campo          | Evento    | Validação                         | Feedback Visual              |
|----------------|-----------|-----------------------------------|------------------------------|
| `person_type`  | change    | Selecionado                       | Toggle visual                |
| `name`         | blur      | Min 3 chars, max 191              | Verde/vermelho + mensagem    |
| `document`     | blur      | CPF/CNPJ válido (dígitos)         | Verde/vermelho + msg         |
| `email`        | blur      | Regex de e-mail                   | Verde/vermelho + msg         |
| `cellphone`    | blur      | 11 dígitos                        | Verde/vermelho               |
| `zipcode`      | blur      | 8 dígitos + consulta ViaCEP       | Verde/vermelho + auto-fill   |
| `credit_limit` | blur      | Decimal ≥ 0                       | Verde/vermelho               |
| `discount`     | blur      | 0-100                             | Verde/vermelho               |
| `website`      | blur      | URL válida                        | Verde/vermelho               |

**Validação do Step (antes de avançar):**
- Step 1: `person_type` selecionado + `name` preenchido + `document` válido → liberar "Próximo"
- Steps 2, 3, 4: Sempre liberados (campos opcionais)
- Step 4 (submit): Revalidar TUDO antes de enviar

---

## Semana 6 — Listagem, Ficha e Responsividade

### 3.8 · Redesenhar `edit.php`
**Arquivo:** `app/views/customers/edit.php`

**Descrição detalhada:**  
Mesmo layout wizard do `create.php`, mas com campos pré-preenchidos. Adicionar no topo um banner com dados resumidos do cliente (foto + nome + código + status) e link para a ficha.

---

### 3.9 · Criar `view.php` — Ficha do cliente
**Arquivo:** `app/views/customers/view.php`

**Descrição detalhada:**  
Página de visualização completa (read-only) do cliente. Layout de perfil/ficha com design minimalista.

**Layout da ficha:**

```
┌────────────────────────────────────────────────────────────┐
│  HEADER: ← Voltar    [✏ Editar]  [⋮ Mais]                │
├────────────────────────────────────────────────────────────┤
│  HERO: Avatar | Nome | Tipo | Status | Código | Documento │
├──────────────┬──────────────┬──────────────┬──────────────┤
│  📦 Pedidos  │  💰 Total    │  📅 Último   │  🎫 Ticket  │
│  12          │  R$ 15.420   │  20/03/2026  │  R$ 1.285   │
├──────────────┴──────────────┴──────────────┴──────────────┤
│  TAB: Dados | Contato | Endereço | Comercial | Histórico  │
├────────────────────────────────────────────────────────────┤
│  CONTEÚDO DA TAB ATIVA                                    │
│  (exibe os dados conforme a tab selecionada)              │
├────────────────────────────────────────────────────────────┤
│  OBSERVAÇÕES                                              │
│  (textarea readonly com texto)                            │
├────────────────────────────────────────────────────────────┤
│  AUDITORIA: Criado por X em DD/MM | Atualizado por Y     │
└────────────────────────────────────────────────────────────┘
```

---

### 3.10 · Adicionar filtros avançados na listagem (Drawer)
**Arquivo:** `app/views/customers/index.php`

**Descrição detalhada:**  
Adicionar um painel lateral (off-canvas Bootstrap 5) com filtros avançados. Aberto por botão "🔍 Filtros" ao lado da busca.

**Filtros no drawer:**

| Filtro            | Tipo de Campo        | Opções                              |
|-------------------|----------------------|-------------------------------------|
| Status            | Checkboxes           | ☑ Ativo ☑ Inativo ☐ Bloqueado     |
| Tipo de Pessoa    | Checkboxes           | ☑ PF ☑ PJ                         |
| Estado (UF)       | Select               | Lista de UFs existentes (dinâmica)  |
| Cidade            | Select com busca     | Lista de cidades (dinâmica)         |
| Vendedor          | Select               | Lista de vendedores (dinâmica)      |
| Tags              | Input com autocomplete| Tags existentes                    |
| Período           | 2 date inputs        | De — Até                           |

**Comportamento:**
- Ao aplicar filtros, a listagem AJAX recarrega automaticamente
- Badges de filtros ativos aparecem acima da tabela
- Cada badge tem botão ✕ para remover o filtro individual
- "Limpar Filtros" remove todos

---

### 3.11 · Adicionar toggle Tabela/Cards na listagem
**Arquivo:** `app/views/customers/index.php`

**Descrição detalhada:**  
Dois botões de visualização (≡ Lista | ⊞ Cards) que alternam entre a tabela atual e uma grade de cards.

**Layout do card:**
```
┌──────────────────────────┐
│  [Avatar]                │
│  Maria Silva             │
│  PF · ● Ativo            │
│  📍 São Paulo / SP       │
│  ─────────────────────── │
│  ✉ maria@email.com       │
│  📱 (11) 99999-0000      │
│  ─────────────────────── │
│  [👁 Ver]  [✏ Editar]    │
└──────────────────────────┘
```

**Responsividade dos cards:**
- Desktop: 3 cards por linha (`col-lg-4`)
- Tablet: 2 cards por linha (`col-md-6`)
- Mobile: 1 card por linha (`col-12`)

---

### 3.12 · Adicionar ações em lote
**Arquivo:** `app/views/customers/index.php`

**Descrição detalhada:**  
Checkboxes na tabela para selecionar múltiplos clientes + toolbar de ações.

**Componentes:**
1. Checkbox no header da tabela (selecionar todos)
2. Checkbox em cada linha
3. Toolbar flutuante aparece quando ≥1 selecionado:
   ```
   ☑ 3 selecionados   [📤 Exportar]  [🔄 Status]  [🗑 Excluir]
   ```

**Ações em lote disponíveis:**

| Ação               | Endpoint                                    | Confirmação |
|---------------------|---------------------------------------------|:-----------:|
| Exportar selecionados | `POST ?page=customers&action=export`       | Não         |
| Alterar status       | `POST ?page=customers&action=bulkAction`    | Sim (Swal)  |
| Excluir (soft)       | `POST ?page=customers&action=bulkAction`    | Sim (Swal)  |

---

### 3.13 · Adicionar coluna de status e novas colunas na tabela
**Arquivo:** `app/views/customers/index.php`

**Nova estrutura da tabela:**

| Coluna     | Descrição                          | Largura |
|------------|------------------------------------|---------|
| ☐          | Checkbox para seleção em lote      | 40px    |
| Nome       | Avatar + Nome + Código             | auto    |
| Tipo       | Badge PF ou PJ                     | 60px    |
| Documento  | CPF/CNPJ formatado                 | 150px   |
| Cidade/UF  | Cidade — UF                        | 150px   |
| Status     | Badge colorido (Ativo/Inativo/Bloq)| 90px    |
| Ações      | Ver | Editar | Excluir             | 120px   |

---

### 3.14 · Responsividade e testes mobile
**Arquivo:** `assets/css/customers.css` + todas as views

**Breakpoints a testar:**

| Breakpoint | Dispositivo       | Adaptações                                       |
|------------|-------------------|--------------------------------------------------|
| ≥1200px    | Desktop grande    | Layout completo, 2 colunas no form               |
| ≥992px     | Desktop/laptop    | Layout completo, sidebar visível                 |
| ≥768px     | Tablet            | Wizard em coluna única, sidebar horizontal       |
| <768px     | Mobile            | Tudo empilhado, stepper compacto, cards em 1 col |
| <576px     | Mobile pequeno    | Botões full-width, fontes menores                |

---

### 📋 Checklist de Entrega — Fase 3

- [x] `assets/css/customers.css` criado com todos os componentes
- [x] `create.php` redesenhado com wizard 4 steps
- [x] Toggle PF/PJ funcional com campos condicionais
- [x] Máscaras em todos os campos (IMask.js carregado via CDN)
- [x] Auto-preenchimento por CEP (ViaCEP) integrado
- [x] Consulta CNPJ (BrasilAPI) integrada
- [x] Validação client-side em tempo real (todos os campos)
- [x] `edit.php` redesenhado com mesmo layout
- [x] `view.php` (ficha do cliente) criada
- [x] Drawer de filtros avançados na listagem
- [x] Toggle tabela/cards na listagem
- [x] Ações em lote (checkbox + toolbar)
- [x] Novas colunas na tabela (tipo, cidade, status)
- [x] Responsivo em todos os breakpoints testados
- [x] Acessibilidade: labels semânticos, navegação por teclado

> **✅ FASE 3 CONCLUÍDA em 28/03/2026**
> - 8 arquivos entregues: customers.css, customer-masks.js, customer-validation.js, customer-wizard.js, create.php, edit.php, index.php, view.php
> - Wizard multi-step (4 steps) com stepper visual, navegação por botões e validação por step
> - Toggle PF/PJ com troca dinâmica de labels, campos condicionais e máscaras
> - IMask.js integrado via CDN: CPF/CNPJ dinâmico, telefones, CEP, data, moeda e percentual
> - Auto-preenchimento por CEP (ViaCEP) e consulta CNPJ (BrasilAPI) com feedback visual (.cst-api-filled)
> - Validação client-side em tempo real (blur + submit) para todos os campos obrigatórios e opcionais
> - Ficha do cliente (view.php) com hero, KPIs, tabs de dados, histórico de pedidos e auditoria
> - Listagem SPA com drawer de filtros avançados (off-canvas Bootstrap 5), toggle tabela/cards e ações em lote
> - Responsivo em 5 breakpoints (≥1200px, ≥992px, ≥768px, <768px, <576px)
> - Acessibilidade: labels semânticos, aria-labels, navegação por teclado, roles ARIA
> - 125 testes unitários da Fase 3 passando (CustomerFase3Test: 125 testes, 293 assertions)
> - Suíte completa do módulo Customer: 238 testes, 476 assertions — todos OK

---

---

# 🔷 FASE 4 — Integrações, Testes e Documentação

> **Semanas:** 7 e 8  
> **Prioridade:** 🟢 MÉDIA  
> **Pré-requisitos:** Fases 1, 2 e 3 concluídas  
> **Risco principal:** Baixo — refinamentos e polimento  
> **Critério de conclusão:** Sistema completo, testado, documentado e pronto para produção

---

## Semana 7 — Funcionalidades Avançadas

### 4.1 · Duplicidade em tempo real (on blur)
**Arquivo:** `assets/js/customer-validation.js`

**Descrição detalhada:**  
Ao sair do campo de documento (CPF/CNPJ), após validação local, fazer chamada AJAX para `checkDuplicate()`.

**Cenários de exibição:**

| Cenário            | Feedback visual                                          |
|--------------------|----------------------------------------------------------|
| Documento novo     | ✓ verde + "Documento disponível"                        |
| Documento duplicado| ⚠ amarelo + "Já existe: {nome} ({código})" + link ver   |
| CNPJ em modo edição| Ignorar o próprio registro na busca                     |
| Erro de rede       | Silencioso — não bloquear o formulário                   |

---

### 4.2 · Campo de Tags com autocomplete e chips
**Arquivo:** `assets/js/customer-tags.js` + views

**Descrição detalhada:**  
Input personalizado que exibe tags como pills/chips clicáveis.

**Comportamento:**
```
1. Ao focar no input, carregar tags existentes via AJAX (Model::getAllTags())
2. Ao digitar, filtrar sugestões (dropdown abaixo do input)
3. Ao pressionar Enter ou clicar numa sugestão:
   - Adicionar pill colorido ao lado do input
   - Pill tem botão ✕ para remover
4. Tags são armazenadas como string separada por vírgula no hidden input
5. Valor final: "VIP,Atacado,Indústria"
```

---

### 4.3 · Indicador de completude do cadastro
**Arquivo:** `assets/js/customer-completeness.js`

**Descrição detalhada:**  
Barra de progresso no formulário que mostra % de preenchimento.

**Pesos dos campos:**

| Grupo            | Peso | Campos contados                                     |
|------------------|:----:|-----------------------------------------------------|
| Identificação    | 30%  | person_type, name, document (obrigatórios) + fantasy, rg_ie |
| Contato          | 25%  | email OU cellphone (ao menos 1) + extras            |
| Endereço         | 25%  | zipcode + city + state (mínimo) + extras            |
| Comercial        | 20%  | Qualquer campo preenchido conta                     |

**Exibição:**
- Barra progressiva colorida (vermelho → amarelo → verde)
- Texto: "Completude: 75%"
- Checklist dos grupos: ✅ Identificação ✅ Contato ❌ Endereço ✅ Comercial

---

### 4.4 · Auto-save em localStorage
**Arquivo:** `assets/js/customer-autosave.js`

**Descrição detalhada:**  
Salvar rascunho do formulário automaticamente para evitar perda de dados.

**Fluxo:**
```
1. A cada 30 segundos, coletar todos os valores do form
2. Salvar em localStorage com key: "akti_customer_draft_{action}_{id}"
3. Ao carregar o formulário:
   a. Verificar se existe draft
   b. Se sim, mostrar toast: "📝 Rascunho encontrado. Deseja restaurar?"
   c. Se restaurar, preencher campos
   d. Se ignorar, limpar draft
4. Ao submit com sucesso, limpar draft
5. Ao clicar "Cancelar", perguntar se quer limpar draft
```

---

### 4.5 · Atalhos de teclado
**Arquivo:** `assets/js/customer-shortcuts.js`

**Atalhos:**

| Atalho       | Ação                                    | Contexto        |
|-------------|------------------------------------------|-----------------|
| `Ctrl+S`    | Salvar formulário (submit)               | Create/Edit     |
| `Ctrl+→`   | Próximo step                             | Wizard          |
| `Ctrl+←`   | Step anterior                            | Wizard          |
| `Esc`       | Fechar modal / Voltar à listagem         | Qualquer        |
| `Ctrl+N`    | Novo cliente (ir para create)            | Listagem        |
| `Ctrl+E`    | Exportar clientes                        | Listagem        |
| `/`         | Focar na busca                           | Listagem        |

---

### 4.6 · Histórico de pedidos na ficha do cliente
**Arquivo:** `app/views/customers/view.php` (tab Histórico)

**Descrição detalhada:**  
Na ficha do cliente, mostrar os últimos pedidos em tabela com paginação AJAX.

**Colunas da tabela de histórico:**

| Coluna     | Descrição                              |
|------------|----------------------------------------|
| # Pedido   | Número do pedido (link para detail)    |
| Data       | Data de criação do pedido              |
| Valor      | Valor total formatado                  |
| Status     | Badge colorido (Pipeline stage)        |
| Ações      | Ver detalhes                           |

---

### 4.7 · Atualizar importação em massa com novos campos
**Arquivo:** `app/controllers/CustomerController.php`

**Descrição detalhada:**  
Atualizar o array `$importFields`, o `parseImportFile()`, o `importCustomersMapped()` e o template CSV para incluir todos os novos campos.

**Novos campos na importação:**

| Campo sistema        | Label na importação        | Auto-mapeamento (nomes aceitos)          |
|----------------------|----------------------------|-----------------------------------------|
| `person_type`        | Tipo Pessoa                | tipo, tipo_pessoa, type                  |
| `fantasy_name`       | Nome Fantasia              | fantasia, nome_fantasia, fantasy         |
| `rg_ie`              | RG / Inscrição Estadual    | rg, ie, inscricao_estadual               |
| `im`                 | Inscrição Municipal        | im, inscricao_municipal                  |
| `cellphone`          | Celular / WhatsApp         | celular, whatsapp, mobile                |
| `address_city`       | Cidade                     | cidade, city, municipio                  |
| `address_state`      | Estado (UF)                | estado, uf, state                        |
| `birth_date`         | Data Nascimento/Fundação   | nascimento, fundacao, birth              |
| `observations`       | Observações                | obs, observacao, observacoes, notes      |
| `origin`             | Origem                     | origem, canal, origin                    |
| `tags`               | Tags                       | tags, etiquetas, classificacao           |

---

## Semana 8 — Testes + Documentação + Deploy

### 4.8 · Testes unitários — Validação de CPF/CNPJ
**Arquivo:** `tests/Unit/ValidatorCpfCnpjTest.php`

**Casos de teste:**

| Teste                                    | Input                  | Esperado |
|------------------------------------------|------------------------|----------|
| CPF válido                               | `529.982.247-25`       | true     |
| CPF inválido (dígitos errados)           | `123.456.789-00`       | false    |
| CPF com todos os dígitos iguais          | `111.111.111-11`       | false    |
| CPF com menos de 11 dígitos              | `123.456`              | false    |
| CNPJ válido                              | `11.222.333/0001-81`   | true     |
| CNPJ inválido (dígitos errados)          | `12.345.678/0001-99`   | false    |
| CNPJ com todos os dígitos iguais         | `11.111.111/1111-11`   | false    |
| Documento com formatação (limpa antes)    | `529.982.247-25`       | true     |
| Documento vazio                          | `""`                   | false    |

---

### 4.9 · Testes de integração — CRUD completo
**Arquivo:** `tests/Unit/CustomerCrudTest.php`

**Cenários de teste:**

| Teste                                           | Verificação                                    |
|------------------------------------------------|------------------------------------------------|
| Criar cliente PF com dados mínimos             | ID retornado, `code` gerado                    |
| Criar cliente PJ com dados completos           | Todos os campos persistidos                    |
| Verificar duplicidade de documento              | `checkDuplicate()` retorna existente           |
| Atualizar cliente sem alterar foto              | Foto original mantida                          |
| Soft delete                                     | `deleted_at` preenchido, não aparece em listagem |
| Restaurar cliente                               | `deleted_at` = NULL, aparece na listagem       |
| Filtro por status                               | Retorna apenas clientes com status filtrado    |
| Filtro por UF                                   | Retorna apenas clientes da UF                  |
| Paginação com filtros                           | Total e dados corretos                         |
| Geração de código sequencial                    | Formato CLI-XXXXX, incrementa                  |
| Busca por documento (findByDocument)            | Retorna cliente correto                        |
| Importação com novos campos                     | Todos os campos mapeados persistidos           |

---

### 4.10 · Script de limpeza de duplicatas
**Arquivo:** `scripts/fix_customer_duplicates.php`

**Descrição detalhada:**  
Script que identifica clientes com documento duplicado e propõe ações:
1. Listar todos os documentos duplicados
2. Para cada duplicata, identificar qual registro manter (mais recente ou com mais pedidos)
3. Mesclar dados dos registros duplicados
4. Marcar os redundantes como `deleted_at`
5. Gerar relatório de ações tomadas

**Após limpeza:** Aplicar UNIQUE INDEX no campo `document`
```sql
ALTER TABLE customers ADD UNIQUE INDEX idx_customers_document (document);
```

---

### 4.11 · Documentação final
**Arquivo:** `docs/cadastro/07_DOCUMENTACAO_FINAL.md`

**Conteúdo:**
- Descrição de todos os campos do cadastro
- Fluxo completo do wizard (com screenshots)
- APIs utilizadas (ViaCEP, BrasilAPI)
- Validações aplicadas (client + server)
- Permissões necessárias
- FAQ para suporte

---

### 📋 Checklist de Entrega — Fase 4

- [ ] Duplicidade em tempo real funcionando
- [ ] Campo de tags com autocomplete e chips
- [ ] Indicador de completude no formulário
- [ ] Auto-save em localStorage
- [ ] Atalhos de teclado configurados
- [ ] Histórico de pedidos na ficha do cliente
- [ ] Importação atualizada com novos campos
- [ ] Template CSV atualizado
- [ ] Testes unitários de CPF/CNPJ passando
- [ ] Testes de integração do CRUD passando
- [ ] Script de limpeza de duplicatas criado e testado
- [ ] UNIQUE INDEX aplicado em `document` (após limpeza)
- [ ] Documentação final escrita
- [ ] Revisão de segurança (CSRF, XSS, SQL Injection)
- [ ] Teste completo em ambiente de staging
- [ ] Aprovação para deploy em produção

---

---

# 📈 Métricas de Sucesso

Após implementação completa, medir:

| Métrica                              | Antes       | Meta          |
|--------------------------------------|:-----------:|:-------------:|
| Score de completude (benchmark)      | 24%         | ≥ 95%         |
| Campos disponíveis                   | 9           | ≥ 40          |
| Tempo médio de cadastro PJ           | ~1 min      | ~3-4 min      |
| Dados preenchidos por auto-fill      | 0%          | ≥ 60%         |
| Duplicatas no banco                  | Desconhecido| 0             |
| Erros de validação no submit         | Pós-submit  | Em tempo real |
| Clientes com endereço completo       | ~30%        | ≥ 80%         |
| Clientes com dados fiscais (IE/IM)   | 0%          | ≥ 50%         |

---

# 📅 Calendário de Marcos (Milestones)

| Marco                              | Data Estimada   | Fase | Entregável                          |
|-------------------------------------|:---------------:|:----:|-------------------------------------|
| 🏁 Kickoff                         | 07/04/2026      | —    | Aprovação do roadmap                |
| 🗄️ Banco de dados migrado          | 11/04/2026      | 1    | Migration SQL executada em dev      |
| 🧩 Model completo                  | 18/04/2026      | 1    | Customer.php + CustomerContact.php  |
| ⚙️ Controller completo             | 02/05/2026      | 2    | CRUD + 11 novas actions             |
| 🖥️ Interface wizard pronta         | 09/05/2026      | 3    | create.php + edit.php com wizard    |
| 📋 Listagem com filtros            | 16/05/2026      | 3    | index.php com drawer + cards + lote |
| 👤 Ficha do cliente                | 16/05/2026      | 3    | view.php completa                   |
| 🧪 Testes passando                 | 23/05/2026      | 4    | PHPUnit + testes manuais            |
| 📖 Documentação final              | 30/05/2026      | 4    | Docs completa                       |
| 🚀 Deploy em produção              | 02/06/2026      | —    | Release completa                    |

---

> **Documento gerado a partir dos arquivos de avaliação em `docs/cadastro/`.**  
> **Referências:** `01_DIAGNOSTICO_ATUAL.md`, `02_PROPOSTA_CAMPOS.md`, `03_PROPOSTA_UX.md`, `04_PLANO_IMPLEMENTACAO.md`, `05_COMPARATIVO_MERCADO.md`, `06_CHECKLIST_VALIDACOES.md`
