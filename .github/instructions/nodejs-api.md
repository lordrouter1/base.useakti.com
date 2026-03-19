# Node.js API — Arquitetura Multi-Tenant

> Guia completo da API Node.js do Akti, com foco na arquitetura multi-tenant,
> padrões de código e instruções para adicionar novas funcionalidades.

---

## 1. Visão Geral

A API Node.js roda em paralelo ao backend PHP e expõe endpoints REST
para operações que se beneficiam de processamento assíncrono (buscas
em tempo real, integrações, etc.).

**Stack:**
- Runtime: Node.js 18+
- Framework: Express 4
- ORM: Sequelize 6
- Banco: MySQL/MariaDB (um banco por tenant)
- Autenticação: JWT (mesmo secret do PHP)

---

## 2. Estrutura de Pastas

```
api/
├── server.js                    # Entry point — inicia o servidor
├── .env                         # Variáveis de ambiente (não versionado)
├── .env.example                 # Template das variáveis
├── package.json
└── src/
    ├── app.js                   # Configuração do Express
    ├── config/
    │   ├── env.js               # Leitura e validação do .env
    │   ├── database.js          # TenantPool + Master DB
    │   ├── constants.js         # Constantes (HTTP status, paginação)
    │   └── cors.js              # Configuração CORS
    ├── middlewares/
    │   ├── authMiddleware.js    # Valida JWT → popula req.user
    │   ├── tenantMiddleware.js  # Resolve tenant → popula req.db, req.models
    │   ├── rateLimiter.js       # Rate limiting
    │   └── errorHandler.js      # Tratamento global de erros
    ├── models/
    │   ├── index.js             # Factory: getModels(sequelize) 
    │   ├── Product.js           # defineProduct(sequelize)
    │   └── ProductGrade...js    # defineProductGradeCombination(sequelize)
    ├── services/
    │   ├── BaseService.js       # CRUD genérico
    │   └── ProductService.js    # Lógica de negócio de produtos
    ├── controllers/
    │   ├── BaseController.js    # HTTP adapter genérico
    │   └── ProductController.js # Endpoints de produtos
    ├── routes/
    │   ├── index.js             # Router principal (auth + tenant)
    │   └── productRoutes.js     # Rotas de /products
    └── utils/
        └── helpers.js           # Utilitários (asyncHandler, toPositiveInt)
```

---

## 3. Arquitetura Multi-Tenant

### 3.1 Modelo de Isolamento

Cada tenant (empresa cliente) possui seu **próprio banco de dados MySQL**.
Um banco central chamado `akti_master` contém a tabela `tenant_clients`
com as credenciais de cada tenant:

```sql
-- akti_master.tenant_clients
+----+-------------+-----------+---------+-----------+---------+-------------+-----------+
| id | client_name | db_host   | db_port | db_name   | db_user | db_password | is_active |
+----+-------------+-----------+---------+-----------+---------+-------------+-----------+
|  1 | Empresa X   | 127.0.0.1 |    3306 | akti_empx | root    | ****        |         1 |
|  2 | Empresa Y   | 127.0.0.1 |    3306 | akti_empy | root    | ****        |         1 |
+----+-------------+-----------+---------+-----------+---------+-------------+-----------+
```

### 3.2 Fluxo de uma Requisição

```
Client → [Express] → authMiddleware → tenantMiddleware → Controller → Service → Model → DB
                          │                  │
                          ▼                  ▼
                     req.user          req.db (Sequelize)
                     (JWT decoded)     req.models { Product, ... }
                                       req.tenantDb (string)
```

1. **`authMiddleware`** — Valida o token JWT e popula `req.user`.
   O JWT deve conter o campo `tenant_db` (nome do banco do tenant).

2. **`tenantMiddleware`** — Lê `req.user.tenant_db`, consulta o banco
   master para obter credenciais, cria (ou reutiliza) um pool Sequelize
   e injeta:
   - `req.db` — Instância Sequelize conectada ao banco do tenant
   - `req.models` — Objeto com todos os models definidos
   - `req.tenantDb` — Nome do banco (string)

3. **Controller** — Obtém os models de `req.models` via `getService(req)`.

4. **Service** — Executa lógica de negócio usando os models recebidos.

### 3.3 TenantPool (database.js)

O `tenantPool` é o componente central que gerencia conexões:

```javascript
import { tenantPool, getMasterSequelize } from './config/database.js';

// Adquirir conexão de um tenant
const sequelize = await tenantPool.acquire('akti_teste');

// Fechar conexão de um tenant específico
await tenantPool.release('akti_teste');

// Fechar todas as conexões (shutdown)
await tenantPool.closeAll();

// Monitoramento
console.log(`Pools ativos: ${tenantPool.size}`);
```

**Funcionalidades:**
- Pool de conexões Sequelize por tenant (chave = db_name)
- Cache de credenciais do master (TTL: 5 min)
- Garbage collector automático (remove pools ociosos após 10 min)
- Graceful shutdown (fecha todos os pools no SIGINT/SIGTERM)

---

## 4. Como Adicionar um Novo Recurso (CRUD)

Siga esta ordem para adicionar, por exemplo, um CRUD de "Fornecedores":

### Passo 1: Model (`src/models/Supplier.js`)

```javascript
import { DataTypes } from 'sequelize';

export function defineSupplier(sequelize) {
  return sequelize.define('Supplier', {
    id: { type: DataTypes.INTEGER, primaryKey: true, autoIncrement: true },
    name: { type: DataTypes.STRING(191), allowNull: false },
    email: { type: DataTypes.STRING(191), allowNull: true },
    phone: { type: DataTypes.STRING(20), allowNull: true },
    created_at: { type: DataTypes.DATE, defaultValue: DataTypes.NOW },
  }, {
    tableName: 'suppliers',
    timestamps: false,
    underscored: true,
  });
}
```

### Passo 2: Registrar no Factory (`src/models/index.js`)

```javascript
import { defineSupplier } from './Supplier.js';

export function getModels(sequelize) {
  // ...existing models...
  const Supplier = defineSupplier(sequelize);

  // Adicionar ao objeto retornado
  const models = { Product, ProductGradeCombination, Supplier };
  // ...
}
```

### Passo 3: Service (`src/services/SupplierService.js`)

```javascript
import { BaseService } from './BaseService.js';

export class SupplierService extends BaseService {
  constructor(SupplierModel) {
    super(SupplierModel);
  }

  // Métodos customizados (opcional)
  async findByEmail(email) {
    return this.model.findOne({ where: { email } });
  }
}
```

### Passo 4: Controller (`src/controllers/SupplierController.js`)

```javascript
import { BaseController } from './BaseController.js';
import { SupplierService } from '../services/SupplierService.js';

export class SupplierController extends BaseController {
  getService(req) {
    return new SupplierService(req.models.Supplier);
  }
}
```

### Passo 5: Routes (`src/routes/supplierRoutes.js`)

```javascript
import { Router } from 'express';
import { SupplierController } from '../controllers/SupplierController.js';

const router = Router();
const ctrl = new SupplierController();

router.get('/', ctrl.index);
router.get('/:id', ctrl.show);
router.post('/', ctrl.store);
router.put('/:id', ctrl.update);
router.delete('/:id', ctrl.destroy);

export default router;
```

### Passo 6: Registrar no Router (`src/routes/index.js`)

```javascript
import supplierRoutes from './supplierRoutes.js';

// Dentro do bloco de rotas protegidas:
router.use('/suppliers', supplierRoutes);
```

### Passo 7: SQL de Atualização (`/sql/update_YYYYMMDD_suppliers.sql`)

```sql
CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NULL,
  phone VARCHAR(20) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> ⚠️ O SQL deve ser executado em **todos** os bancos de tenant.

---

## 5. Padrões Importantes

### 5.1 Nunca importe models como singletons

```javascript
// ❌ ERRADO — aponta para um banco fixo
import { Product } from '../models/index.js';

// ✅ CORRETO — usa os models do tenant da request
const { Product } = req.models;
```

### 5.2 Não filtre por tenant_id nas queries

Como cada tenant tem seu próprio banco, **não é necessário** (nem correto)
adicionar `WHERE tenant_id = ?` nas queries. O isolamento é por banco.

```javascript
// ❌ ERRADO
await Product.findAll({ where: { tenant_id: req.tenantId } });

// ✅ CORRETO
await Product.findAll();
```

### 5.3 Use getService(req) nos Controllers

Todo controller que estende `BaseController` deve implementar `getService(req)`:

```javascript
class MyController extends BaseController {
  getService(req) {
    return new MyService(req.models.MyModel);
  }
}
```

### 5.4 JWT deve conter tenant_db

O token JWT gerado pelo PHP deve incluir o campo `tenant_db` com o nome
do banco de dados do tenant:

```json
{
  "user_id": 1,
  "tenant_db": "akti_teste",
  "role": "admin",
  "iat": 1710000000,
  "exp": 1710086400
}
```

---

## 6. Variáveis de Ambiente

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `NODE_ENV` | Ambiente (development/production) | development |
| `PORT` | Porta do servidor | 3000 |
| `DB_MASTER_HOST` | Host do banco master | 127.0.0.1 |
| `DB_MASTER_PORT` | Porta do banco master | 3306 |
| `DB_MASTER_NAME` | Nome do banco master | akti_master |
| `DB_MASTER_USER` | Usuário do banco master | root |
| `DB_MASTER_PASS` | Senha do banco master | (vazio) |
| `JWT_SECRET` | Chave para validar JWTs | (obrigatório em prod) |
| `CORS_ORIGIN_PATTERN` | Padrão de domínio CORS | .useakti.com |
| `TENANT_POOL_IDLE_MS` | TTL de inatividade do pool (ms) | 600000 |

---

## 7. Comandos

```bash
# Instalar dependências
cd api && npm install

# Desenvolvimento (com hot-reload)
npm run dev

# Produção
npm start
```

---

## 8. Diagnóstico e Monitoramento

### Health Check
```
GET /health → { "status": "ok", "timestamp": "..." }
GET /api/status → { "status": "ok" }
```

### Logs do TenantPool
O pool emite logs no console:
```
[DB] Master connection (akti_master) OK.
[DB] Tenant pool created: akti_teste @ 127.0.0.1
[DB/GC] Evicted idle tenant pool: akti_teste
[DB] Closed: akti_teste
[DB] Closed: master
```

---

## 9. Shutdown Graceful

O `server.js` captura `SIGINT` e `SIGTERM` para fechar todos os pools
antes de encerrar o processo, evitando conexões órfãs no MySQL.
