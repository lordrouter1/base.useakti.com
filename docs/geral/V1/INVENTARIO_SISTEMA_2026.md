# 📋 Inventário Completo do Sistema — Akti

**Data:** 30/03/2026  
**Referência:** Auditoria Geral 2026

---

## 1. Módulos do Sistema

| Módulo            | Controller(s)                   | Model(s)                        | Status     |
|-------------------|--------------------------------|----------------------------------|------------|
| Autenticação      | UserController                 | User, LoginAttempt               | ✅ Ativo    |
| Dashboard         | DashboardController            | DashboardWidget                  | ✅ Ativo    |
| Clientes          | CustomerController             | Customer, CustomerContact        | ✅ Ativo    |
| Produtos          | ProductController              | Product, ProductGrade, Category  | ✅ Ativo    |
| Categorias        | CategoryController             | Category, Subcategory, CategoryGrade | ✅ Ativo |
| Pedidos           | OrderController                | Order, OrderItemLog              | ✅ Ativo    |
| Pipeline          | PipelineController             | Pipeline, OrderPreparation       | ✅ Ativo    |
| Setores           | SectorController               | ProductionSector, PreparationStep | ✅ Ativo   |
| Estoque           | StockController                | Stock                            | ✅ Ativo    |
| Financeiro        | FinancialController            | Financial, Installment           | ✅ Ativo    |
| Parcelas          | InstallmentController          | Installment                      | ✅ Ativo    |
| Transações        | TransactionController          | Financial                        | ✅ Ativo    |
| Import Financeiro | FinancialImportController      | Financial                        | ✅ Ativo    |
| Comissões         | CommissionController           | Commission                       | ✅ Ativo    |
| Recorrentes       | RecurringTransactionController | RecurringTransaction             | ✅ Ativo    |
| NF-e              | NfeDocumentController          | NfeDocument, NfeCredential       | ✅ Ativo    |
| NF-e Credenciais  | NfeCredentialController        | NfeCredential                    | ✅ Ativo    |
| Gateways Pgto     | PaymentGatewayController       | PaymentGateway                   | ✅ Ativo    |
| Catálogo Público  | CatalogController              | CatalogLink                      | ✅ Ativo    |
| Portal Cliente    | PortalController               | PortalAccess, PortalMessage      | ✅ Ativo    |
| Portal Admin      | PortalAdminController          | PortalAccess                     | ✅ Ativo    |
| Relatórios        | ReportController               | ReportModel                      | ✅ Ativo    |
| Configurações     | SettingsController             | CompanySettings                  | ✅ Ativo    |
| Walkthrough       | WalkthroughController          | Walkthrough                      | ✅ Ativo    |
| Home (Landing)    | HomeController                 | —                                | ✅ Ativo    |
| API Gateway       | ApiController                  | —                                | ✅ Ativo    |

**Total: 25+ módulos**

---

## 2. Contagem de Arquivos

| Tipo                | Quantidade | Localização             |
|---------------------|-----------|-------------------------|
| Controllers PHP     | 28        | `app/controllers/`      |
| Models PHP          | 43        | `app/models/`           |
| Services PHP        | 28        | `app/services/`         |
| Middleware PHP      | 3         | `app/middleware/`        |
| Core PHP            | 5         | `app/core/`             |
| Utils PHP           | 8         | `app/utils/`            |
| Config PHP          | 5         | `app/config/`           |
| Views (diretórios)  | 23        | `app/views/`            |
| Testes PHPUnit      | 21        | `tests/`                |
| Scripts utilitários | 17        | `scripts/`              |
| SQL migrations      | 2+        | `sql/`                  |
| API Node.js files   | ~20       | `api/src/`              |

---

## 3. Dependências PHP (Composer)

| Pacote                         | Versão    | Finalidade                   |
|-------------------------------|-----------|------------------------------|
| `nfephp-org/sped-nfe`         | ^5.0      | Emissão NF-e (SEFAZ)        |
| `nfephp-org/sped-da`          | ^4.0      | DANFE (PDF da NF-e)         |
| `phpoffice/phpspreadsheet`    | ^5.5      | Import/Export Excel          |
| `tecnickcom/tcpdf`            | ^6.11     | Geração de PDFs              |

### Dev Dependencies
| Pacote           | Versão | Finalidade   |
|------------------|--------|-------------|
| `phpunit/phpunit`| ^9.6   | Testes      |

---

## 4. Dependências Node.js (API)

| Pacote              | Versão     | Finalidade              |
|---------------------|------------|------------------------|
| express             | ^4.21.2    | Framework web           |
| helmet              | ^8.0.0     | Security headers        |
| cors                | ^2.8.5     | Cross-Origin            |
| jsonwebtoken        | ^9.0.2     | Autenticação JWT        |
| mysql2              | ^3.12.0    | Driver MySQL            |
| sequelize           | ^6.37.5    | ORM                     |
| express-rate-limit  | ^7.5.0     | Rate limiting           |
| morgan              | ^1.10.0    | HTTP logging            |
| dotenv              | ^16.4.7    | Variáveis de ambiente   |

---

## 5. Endpoints/Rotas do Sistema

### Rotas Públicas (sem autenticação)
| Page      | Controller           | Descrição              |
|-----------|---------------------|------------------------|
| catalog   | CatalogController    | Catálogo público        |
| portal    | PortalController     | Portal do cliente       |
| login     | UserController       | Autenticação            |

### Rotas Autenticadas
| Page              | Controller                   | Actions Mapeadas |
|-------------------|------------------------------|-----------------|
| home              | HomeController               | 1               |
| dashboard         | DashboardController          | 5+              |
| customers         | CustomerController           | 15+             |
| products          | ProductController            | 20+             |
| categories        | CategoryController           | 10+             |
| orders            | OrderController              | 10+             |
| pipeline          | PipelineController           | 15+             |
| sectors           | SectorController             | 5+              |
| stock             | StockController              | 10+             |
| financial         | FinancialController          | 5+              |
| financial_payments| FinancialController          | 3               |
| installments      | InstallmentController        | 10+             |
| transactions      | TransactionController        | 10+             |
| financial_import  | FinancialImportController    | 5+              |
| commissions       | CommissionController         | 10+             |
| recurring         | RecurringTransactionController| 5+             |
| nfe               | NfeDocumentController        | 15+             |
| nfe_credentials   | NfeCredentialController      | 5+              |
| gateways          | PaymentGatewayController     | 5+              |
| reports           | ReportController             | 5+              |
| settings          | SettingsController           | 10+             |
| users             | UserController               | 10+             |
| profile           | UserController               | 3               |
| portal_admin      | PortalAdminController        | 10+             |
| walkthrough       | WalkthroughController        | 3               |

**Total estimado:** 200+ endpoints

---

## 6. Estrutura de Logging

| Log File                     | Conteúdo                              |
|------------------------------|---------------------------------------|
| `storage/logs/security.log`  | Falhas CSRF, tentativas suspeitas     |
| `storage/logs/events.log`    | Erros em listeners de eventos         |
| `storage/logs/financial.log` | Operações financeiras                 |
| `storage/logs/commission.log`| Cálculos de comissão                  |
| `storage/logs/gateways.log`  | Operações com gateways de pagamento   |
| `system_logs` (tabela DB)    | Auditoria geral (ações de usuários)   |
| `login_attempts` (tabela DB) | Tentativas de login (brute force)     |
| `ip_404_hits` (tabela DB)    | Hits 404 por IP (flood detection)     |
| `ip_blacklist` (tabela DB)   | IPs bloqueados                        |
| `rate_limit` (tabela DB)     | Rate limiting cross-session           |

---

## 7. Tecnologias e Versões

| Tecnologia     | Versão Requerida | Observação                    |
|----------------|-----------------|-------------------------------|
| PHP            | >=7.4           | Compatível com 8.x            |
| MySQL/MariaDB  | 5.7+ / 10.3+   | utf8mb4                        |
| Node.js        | >=20.0.0        | API microserviço               |
| Bootstrap      | 5.3.0           | CSS Framework                  |
| jQuery         | Última estável  | Via CDN                        |
| Font Awesome   | 6.4.0           | Ícones                         |
| SweetAlert2    | 11.x            | Modais e alerts                |
| Select2        | 4.1.0           | Dropdowns com busca            |

---

**Atualizado em:** 30/03/2026
