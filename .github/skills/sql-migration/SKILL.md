---
name: sql-migration
description: "Cria arquivos SQL de migração padronizados. Use when: criar migration, criar SQL, alterar banco, nova tabela, nova coluna, alterar tabela, migration SQL, update SQL, arquivo SQL, database change. Naming: update_YYYYMMDDhhmm_<N>_descricao.sql com sequencial auto-detectado."
argument-hint: "Descrição da migração (ex: adicionar_coluna_status_pedidos)"
---

# SQL Migration — Criação Padronizada de Arquivos de Migração

Skill para criar arquivos SQL de atualização do banco de dados seguindo o padrão de naming e sequenciamento do projeto Akti.

## Quando Usar

- Qualquer alteração de banco de dados: CREATE TABLE, ALTER TABLE, CREATE INDEX, INSERT de dados de configuração, DROP, etc.
- Quando o agente detectar que uma mudança requer alteração de schema ou dados no banco
- Solicitações explícitas: "criar migration", "criar SQL", "alterar tabela"

## Padrão de Nomenclatura

```
update_YYYYMMDDhhmm_<N>_descricao.sql
```

| Parte | Descrição | Exemplo |
|-------|-----------|---------|
| `update_` | Prefixo fixo obrigatório | `update_` |
| `YYYYMMDDhhmm` | Data e hora **do momento da criação** (sempre atual, nunca sugerida) | `202604011430` |
| `<N>` | Sequencial numérico auto-detectado (ver regra abaixo) | `4` |
| `descricao` | Snake_case descritivo, sem acentos | `adicionar_coluna_status` |

### Regra do Sequencial `<N>`

O sequencial é um número inteiro que garante ordenação entre migrações. Para determiná-lo:

1. **Listar todos os arquivos** nas pastas `/sql/` e `/sql/prontos/`
2. **Extrair o `<N>`** de cada arquivo que siga o padrão `update_*_<N>_*.sql`
3. **Para arquivos sem `<N>`** (legado, ex: `update_202603301000_descricao.sql` sem sequencial), **ignorá-los** no cálculo — não atribuir número a eles retroativamente
4. **O novo `<N>`** = maior `<N>` encontrado + 1
5. **Se nenhum arquivo** tiver sequencial `<N>`, iniciar em `0`

### Regra da Data/Hora

- **SEMPRE** usar a data e hora **do momento de criação** do arquivo
- **NUNCA** usar data/hora sugerida pelo usuário, copiada de outro arquivo, ou inventada
- Formato: `YYYYMMDDhhmm` (ano 4 dígitos, mês 2, dia 2, hora 2, minuto 2, sem separadores)
- Usar hora no fuso horário local do sistema

## Procedimento

### Passo 1 — Determinar o Sequencial

```
1. Listar arquivos em /sql/ e /sql/prontos/
2. Extrair <N> de nomes que sigam update_*_<N>_*.sql  
   (regex: update_\d{12}_(\d+)_.+\.sql)
3. Se encontrou algum N: próximo = max(N) + 1
4. Se não encontrou nenhum: próximo = 0
```

### Passo 2 — Montar o Nome do Arquivo

```
1. Obter data/hora atual (terminal: Get-Date -Format "yyyyMMddHHmm" ou date +%Y%m%d%H%M)
2. Montar: update_<datetime>_<N>_<descricao>.sql
3. Garantir que descricao está em snake_case, sem acentos, sem espaços
```

### Passo 3 — Gerar o Conteúdo SQL

O arquivo SQL deve seguir estas regras:

```sql
-- Migration: <descrição legível>
-- Criado em: DD/MM/YYYY HH:MM
-- Sequencial: <N>

-- Usar IF NOT EXISTS / IF EXISTS para idempotência quando possível
-- Usar transações quando múltiplas operações dependem entre si

-- Exemplo CREATE TABLE:
CREATE TABLE IF NOT EXISTS `nome_tabela` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    -- ... colunas ...
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exemplo ALTER TABLE:
-- Verificar existência antes de adicionar coluna (MySQL 8+):
ALTER TABLE `nome_tabela` ADD COLUMN IF NOT EXISTS `nova_coluna` VARCHAR(100) DEFAULT NULL;

-- Para MySQL 5.7 (sem IF NOT EXISTS em ALTER), usar procedure:
-- SET @sql = (SELECT IF(
--     (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
--      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nome_tabela' AND COLUMN_NAME = 'nova_coluna') = 0,
--     'ALTER TABLE `nome_tabela` ADD COLUMN `nova_coluna` VARCHAR(100) DEFAULT NULL',
--     'SELECT 1'
-- ));
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;
```

### Passo 4 — Salvar o Arquivo

- Destino: `/sql/<nome_completo>.sql`
- **NÃO** mover para `/sql/prontos/` — isso é feito manualmente após aplicar no DB de teste
- **NÃO** executar o SQL automaticamente

### Passo 5 — Confirmar ao Usuário

Reportar:
- Nome completo do arquivo criado
- Sequencial atribuído
- Resumo do que o SQL faz
- Lembrete: "Execute manualmente no banco de teste e mova para `/sql/prontos/` após validar"

## Regras e Restrições

- **Usar `utf8mb4_unicode_ci`** como collation padrão
- **Usar `InnoDB`** como engine
- **Incluir `created_at` e `updated_at`** em tabelas novas
- **Preferir idempotência** (IF NOT EXISTS, IF EXISTS)
- **Nunca incluir DROP TABLE** sem confirmação explícita do usuário
- **Nunca incluir DELETE/TRUNCATE de dados** sem confirmação explícita
- **Nunca gerar testes PHPUnit** que verifiquem existência de arquivos .sql
- **Apenas comandos necessários** para atualizar o banco — sem dados de teste/seed

## Fluxo de Vida dos Arquivos SQL

```
/sql/                    → Migrations pendentes (não aplicadas)
    ↓ (aplicar no DB de teste)
/sql/prontos/            → Aplicadas no teste, pendentes para produção
    ↓ (deploy em produção)
    Deletar ou arquivar  → Aplicadas em produção
```
