# Fluxo Multi-Cliente por Subdomínio

## Objetivo
Separar cada cliente em um banco próprio (`akti_*`) e usar o banco master `akti_master` para resolver o subdomínio e credenciais.

## Como o fluxo funciona
1. A aplicação lê o host da requisição (`HTTP_HOST`).
2. O `TenantManager` extrai o subdomínio.
3. O sistema consulta o `akti_master.tenant_clients` usando o subdomínio.
4. Se encontrar cliente ativo, conecta no banco específico do cliente (`db_name`, `db_user`, `db_password`).
5. Se não encontrar, o login é bloqueado e exibe erro de subdomínio não vinculado.

## Arquivos envolvidos
- `app/config/tenant.php`: resolução do tenant por subdomínio e sessão do tenant.
- `app/config/database.php`: conexão dinâmica conforme tenant resolvido.
- `app/controllers/UserController.php`: bloqueia login quando tenant está inválido.
- `app/views/auth/login.php`: mostra o cliente detectado e desativa login em tenant inválido.
- `sql/multi_tenant_master.sql`: criação do banco master e tabela de vínculo.

## Variáveis de ambiente suportadas
- Tenant padrão:
  - `AKTI_DB_HOST`, `AKTI_DB_PORT`, `AKTI_DB_NAME`, `AKTI_DB_USER`, `AKTI_DB_PASS`, `AKTI_DB_CHARSET`
- Banco master:
  - `AKTI_MASTER_DB_HOST`, `AKTI_MASTER_DB_PORT`, `AKTI_MASTER_DB_NAME`, `AKTI_MASTER_DB_USER`, `AKTI_MASTER_DB_PASS`, `AKTI_MASTER_DB_CHARSET`
- Domínio base:
  - `AKTI_BASE_DOMAIN` (ex.: `useakti.com`)

## Exemplo de mapeamento
- `cliente1.useakti.com` -> registro `subdomain=cliente1` -> banco `akti_cliente1`
- `cliente2.useakti.com` -> registro `subdomain=cliente2` -> banco `akti_cliente2`

## Recomendação de provisionamento de novo cliente
1. Criar banco `akti_<cliente>`.
2. Rodar o schema padrão da aplicação nesse banco.
3. Criar usuário MySQL com permissão apenas nesse banco.
4. Inserir vínculo na tabela `akti_master.tenant_clients`.
5. Configurar DNS do subdomínio para a aplicação.


## Limites por cliente (banco master)
- `max_users`: limite de usuários no tenant (NULL ou <=0 = ilimitado).
- `max_products`: limite de produtos no tenant (NULL ou <=0 = ilimitado).

### Regras aplicadas no sistema
1. Cadastro de usuário (`?page=users&action=store`) valida `max_users` antes de criar.
2. Cadastro manual de produto (`?page=products&action=store`) valida `max_products`.
3. Importação de produtos (`?page=products&action=importProducts`) interrompe quando o limite é atingido.
4. O login envia e valida `tenant_key` (subdomínio resolvido) para evitar troca de cliente durante autenticação.
5. Se a sessão estiver logada e o subdomínio mudar, o sistema encerra sessão automaticamente e redireciona para login.
