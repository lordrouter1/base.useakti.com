# Checklist de Auditoria — Banco de Dados e Models

## Configuração PDO
- [ ] `ERRMODE_EXCEPTION` definido
- [ ] `EMULATE_PREPARES = false`
- [ ] `DEFAULT_FETCH_MODE = FETCH_ASSOC`
- [ ] Charset UTF-8 (`charset=utf8mb4`)
- [ ] Singleton ou pool por tenant

## Inventário de Models
- [ ] Listar todos os models com seus métodos
- [ ] Cada model tem: create, readAll, readOne, update, delete
- [ ] Retornos consistentes (array, bool, int)
- [ ] Todos usam prepared statements

## Prepared Statements
- [ ] Nenhum `query()` com variável interpolada
- [ ] `bindParam()` ou array em `execute()`
- [ ] LIKE com `%` tratado corretamente: `bindValue('%' . $term . '%')`
- [ ] IN clause não usa interpolação (usar `str_repeat('?,')`)

## Paginação
- [ ] LIMIT e OFFSET como parâmetros inteiros
- [ ] Contagem total separada para paginação
- [ ] Sem `LIMIT $_GET['limit']` direto

## Transações
- [ ] Operações compostas dentro de `beginTransaction()`
- [ ] `commit()` no sucesso
- [ ] `rollback()` no catch
- [ ] Sem transações aninhadas (ou tratadas com savepoints)

## Multi-Tenant
- [ ] `tenant_id` em todas as tabelas de dados
- [ ] WHERE `tenant_id = ?` em todas as queries
- [ ] Sem queries que acessem dados de outros tenants
- [ ] Tabelas compartilhadas vs isoladas documentadas

## Migrations
- [ ] Pasta `sql/` com naming convention: `update_YYYYMMDDhhmm_descricao.sql`
- [ ] Migrations idempotentes (IF NOT EXISTS, IF EXISTS)
- [ ] Sem dados sensíveis em migrations
- [ ] Migrations versionadas no git
- [ ] Pasta `sql/prontos/` para migrations já aplicadas

## Schemas
- [ ] Tabelas com PRIMARY KEY
- [ ] Foreign keys definidas
- [ ] Índices em colunas de busca frequente
- [ ] Tipos de dados apropriados (VARCHAR lengths, DECIMAL para dinheiro)
- [ ] Colunas `created_at`, `updated_at` em tabelas relevantes
- [ ] Soft delete (`deleted_at`) onde necessário
