# Banco de Dados e Migrações

---

## Sumário
- [Regra Crítica](#regra-crítica)
- [Multi-Tenant](#multi-tenant)
- [Padrões de Tabelas](#padrões-de-tabelas)
- [Limites por Cliente](#limites-por-cliente)
- [Provisionamento de Cliente](#provisionamento-de-novo-cliente)

---

## Regra Crítica
> Toda alteração que envolva o banco de dados **deve obrigatoriamente usar a skill `sql-migration`** (`.github/skills/sql-migration/SKILL.md`) para gerar o arquivo SQL de atualização na pasta `/sql`.
> Padrão: `update_YYYYMMDDhhmm_<N>_descricao.sql` — com data/hora atual e sequencial auto-detectado.

---

## Multi-Tenant
- Resolvido pelo banco master `akti_master` e tabela `tenant_clients`.
- Cada cliente tem banco próprio: `akti_<empresa>`.
- Sessão e login respeitam o tenant do subdomínio.

---

## Padrões de Tabelas
- Nomes em snake_case e plural (ex: `users`, `products`).
- Chaves primárias: `id` (AUTO_INCREMENT).

---

## Limites por Cliente
- Limites: usuários, produtos, armazéns, tabelas de preço, setores.
- Valores NULL ou <= 0: sem limite.
- Validação no backend antes de criar/importar.
- Quando atingido, botão de criação desabilitado e alerta visual.

---

## Provisionamento de Novo Cliente
1. Criar banco: `CREATE DATABASE akti_<cliente> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Criar usuário MySQL com acesso apenas ao banco do cliente.
3. Rodar o schema completo (`sql/database.sql`) no banco do cliente.
4. Inserir registro em `akti_master.tenant_clients` com os dados do banco e limites.
5. Configurar DNS do subdomínio (propagação pode levar até 24h).

---

## Exemplos de Atualizações
- **Adicionar nova coluna:** `ALTER TABLE products ADD COLUMN new_column VARCHAR(255) DEFAULT NULL;`
- **Criar nova tabela:** `CREATE TABLE logs (id INT AUTO_INCREMENT PRIMARY KEY, message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);`
- **Atualizar dados existentes:** `UPDATE users SET is_active = 1 WHERE last_login > '2023-01-01';`

---

## Padrão de Nomenclatura de Migrations

> **Usar a skill `sql-migration`** para gerar migrations automaticamente com naming correto.

- **Formato obrigatório:** `update_YYYYMMDDhhmm_<N>_descricao.sql`
  - `YYYYMMDDhhmm` = data e hora **do momento da criação** (nunca copiar de outro arquivo)
  - `<N>` = sequencial auto-detectado a partir de `/sql` e `/sql/prontos`
  - `descricao` = snake_case, sem acentos
- **Exemplos:** `update_202604011430_0_criar_tabela_fornecedores.sql`, `update_202604011445_1_adicionar_coluna_status.sql`
- **Nunca** usar prefixos como `migration_`, `alter_`, `fix_`. Sempre `update_`.
- **Cabeçalho obrigatório** no arquivo SQL:
  ```sql
  -- Migration: Descrição clara da alteração
  -- Criado em: DD/MM/YYYY HH:MM
  -- Sequencial: <N>
  ```
- Sempre incluir `SET FOREIGN_KEY_CHECKS = 0;` no início e `SET FOREIGN_KEY_CHECKS = 1;` ao final quando houver tabelas com FK.
- Usar `IF NOT EXISTS` / `IF EXISTS` sempre que possível para tornar a migration idempotente.
- Usar `ADD COLUMN IF NOT EXISTS` e `DROP TABLE IF EXISTS` para evitar erros em execuções repetidas.

---

## Boas Práticas
- Usar a skill `sql-migration` para criar arquivos com naming padronizado (`update_YYYYMMDDhhmm_<N>_descricao.sql`).
- Incluir sempre um `README.md` na pasta `/sql` explicando como aplicar as atualizações.
- Testar as atualizações em um ambiente de staging antes de aplicar em produção.
- Manter backup completo do banco de dados antes de qualquer atualização.

---

## Aplicando Atualizações
Para aplicar uma atualização:
1. Fazer o upload do arquivo SQL para o servidor, na pasta `/sql`.
2. Conectar ao banco de dados via linha de comando ou ferramenta de administração (ex: phpMyAdmin).
3. Executar o comando: `SOURCE /caminho/para/o/arquivo/update_YYYYMMDDhhmm_N_descricao.sql;`
4. Verificar se a atualização foi aplicada corretamente (conferir novas tabelas/colunas, testar funcionalidades relacionadas).

---

## Registro de Atualizações

| Data | Arquivo | Descrição |
|------|---------|-----------|
| 02/03/2026 | `update_20260302_tenant_limits.sql` | Adição de colunas de limite no banco master |
| 03/03/2026 | `update_20260303_walkthrough.sql` | Tabela `user_walkthrough` para tour guiado de primeiro acesso |
| 04/03/2026 | `update_20260304_financial_module.sql` | Módulo financeiro: tabelas `order_installments`, `financial_transactions`, colunas NF-e |
| 09/03/2026 | `update_20260309_ip_blacklist.sql` | Sistema de blacklist automática por flood 404 (tabelas `ip_404_hits`, `ip_blacklist`, índices, usuário MySQL `akti_guard`) |