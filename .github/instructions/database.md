# Banco de Dados e Migrações

> ⚠️ REGRA CRÍTICA — Atualização do Banco de Dados
>
> Toda alteração que envolva o banco de dados deve obrigatoriamente gerar um arquivo SQL de atualização (ex: `update_YYYYMMDD_descricao.sql`) na pasta `/sql`.

- Multi-tenant é resolvido pelo `tenant_clients` banco `akti_master`. Cada tenant tem um banco próprio como `akti_empresaX`.
- Ao inserir FKs, desative por conveniência e religue ao terminar `SET FOREIGN_KEY_CHECKS=0`.
- Chaves primárias usam AUTO_INCREMENT com ID referenciado.
- Suba updates como indepotentes, se possível `IF NOT EXISTS` ou updates manuais sem erros com múltiplas execuções.

### Banco de Dados
- Tabelas devem usar nomes no singular ou plural (definir padrão: sugerido **snake_case** e plural, ex: `users`, `products`, `orders`).
- Chaves primárias devem ser `id` (AUTO_INCREMENT).

### Banco de Dados Multi-Tenant (Obrigatório)
- O sistema deve operar em arquitetura **multi-tenant por subdomínio**.
- Existe um banco **master** (`akti_master`) responsável por mapear cada cliente para seu banco dedicado.
- Cada cliente deve possuir banco próprio com prefixo `akti_` (ex.: `akti_cliente1`, `akti_cliente2`).
- A tabela de referência de tenants no master é `tenant_clients`.
- O login e toda a sessão devem respeitar o tenant resolvido pelo subdomínio atual.

#### Regras de Resolução de Tenant
1. A aplicação lê o `HTTP_HOST` e extrai o subdomínio.
2. O subdomínio é consultado em `akti_master.tenant_clients`.
3. Se o cliente estiver ativo, a conexão usa `db_host`, `db_port`, `db_name`, `db_user`, `db_password`, `db_charset` desse tenant.
4. Se o subdomínio for inválido/inativo, o login deve ser bloqueado.
5. Se houver troca de subdomínio com sessão ativa, a sessão deve ser encerrada por segurança.

#### Estrutura Esperada no Banco Master (`tenant_clients`)
- Identificação: `id`, `client_name`, `subdomain`, `is_active`.
- Conexão: `db_host`, `db_port`, `db_name`, `db_user`, `db_password`, `db_charset`.
- Limites do cliente: `max_users`, `max_products`, `max_warehouses`, `max_price_tables`, `max_sectors`.
- Auditoria: `created_at`, `updated_at`.

#### Regras de Limites por Cliente
- `max_users`: quantidade máxima de usuários cadastrados por tenant.
- `max_products`: quantidade máxima de produtos cadastrados por tenant.
- `max_warehouses`: quantidade máxima de armazéns/locais de estoque por tenant.
- `max_price_tables`: quantidade máxima de tabelas de preço por tenant.
- `max_sectors`: quantidade máxima de setores de produção por tenant.
- Valores `NULL` ou `<= 0` devem ser tratados como **sem limite**.
- As validações devem ocorrer no backend antes de criar o recurso, incluindo importação em lote.
- Quando o limite é atingido, o botão de criação deve ser **desabilitado** na view e um **alerta visual** (alert Bootstrap + SweetAlert2) deve informar que o limite do plano foi atingido.
- A mensagem de limite deve orientar o usuário a entrar em contato com o suporte para upgrade do plano.

### Provisionamento de Novo Cliente
1. Criar banco: `CREATE DATABASE akti_<cliente> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Criar usuário MySQL com acesso apenas ao banco do cliente.
3. Rodar o schema completo (`sql/database.sql`) no banco do cliente.
4. Inserir registro em `akti_master.tenant_clients` com os dados do banco e limites.
5. Configurar DNS do subdomínio (propagação pode levar até 24h).

### Exemplos de Atualizações
- **Adicionar nova coluna:** `ALTER TABLE products ADD COLUMN new_column VARCHAR(255) DEFAULT NULL;`
- **Criar nova tabela:** `CREATE TABLE logs (id INT AUTO_INCREMENT PRIMARY KEY, message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);`
- **Atualizar dados existentes:** `UPDATE users SET is_active = 1 WHERE last_login > '2023-01-01';`

### Padrão de Nomenclatura de Migrations
- **Formato obrigatório:** `update_YYYYMMDD_descricao_curta.sql`
- **Exemplos:** `update_20260304_financial_module.sql`, `update_20260302_tenant_limits.sql`
- **Nunca** usar prefixos como `migration_`, `alter_`, `fix_`. Sempre `update_`.
- **Cabeçalho obrigatório** no arquivo SQL:
  ```sql
  -- ============================================================================
  -- UPDATE: update_YYYYMMDD_descricao.sql
  -- Descrição: Descrição clara da alteração
  -- Data: YYYY-MM-DD
  -- Autor: Nome ou Sistema Akti
  -- ============================================================================
  ```
- Sempre incluir `SET FOREIGN_KEY_CHECKS = 0;` no início e `SET FOREIGN_KEY_CHECKS = 1;` ao final quando houver tabelas com FK.
- Usar `IF NOT EXISTS` / `IF EXISTS` sempre que possível para tornar a migration idempotente.
- Usar `ADD COLUMN IF NOT EXISTS` e `DROP TABLE IF EXISTS` para evitar erros em execuções repetidas.

### Boas Práticas
- Nomear arquivos de atualização com data e descrição resumida da mudança (ex: `update_20231010_add_column_new_feature.sql`).
- Incluir sempre um `README.md` na pasta `/sql` explicando como aplicar as atualizações.
- Testar as atualizações em um ambiente de staging antes de aplicar em produção.
- Manter backup completo do banco de dados antes de qualquer atualização.

### Aplicando Atualizações
Para aplicar uma atualização:
1. Fazer o upload do arquivo SQL para o servidor, na pasta `/sql`.
2. Conectar ao banco de dados via linha de comando ou ferramenta de administração (ex: phpMyAdmin).
3. Executar o comando: `SOURCE /caminho/para/o/arquivo/update_YYYYMMDD.sql;`
4. Verificar se a atualização foi aplicada corretamente (conferir novas tabelas/colunas, testar funcionalidades relacionadas).

### Registro de Atualizações

| Data | Arquivo | Descrição |
|------|---------|-----------|
| 02/03/2026 | `update_20260302_tenant_limits.sql` | Adição de colunas de limite no banco master |
| 03/03/2026 | `update_20260303_walkthrough.sql` | Tabela `user_walkthrough` para tour guiado de primeiro acesso |
| 04/03/2026 | `update_20260304_financial_module.sql` | Módulo financeiro: tabelas `order_installments`, `financial_transactions`, colunas NF-e |
| 09/03/2026 | `update_20260309_ip_blacklist.sql` | Sistema de blacklist automática por flood 404 (tabelas `ip_404_hits`, `ip_blacklist`, índices, usuário MySQL `akti_guard`) |