## Bootloader de Módulos por Tenant (akti_master)

### Regra de arquitetura
- O sistema possui bootloader de módulos por tenant com base no campo `tenant_clients.enabled_modules` (JSON) no banco `akti_master`.
- A resolução é feita pelo `TenantManager` e armazenada em `$_SESSION['tenant']['enabled_modules']`.
- O fallback padrão é manter os módulos críticos habilitados quando não houver configuração explícita.

### Arquivos-chave
- `app/core/ModuleBootloader.php` — regras centrais de módulos (page map, tab map, fallback).
- `app/config/tenant.php` — leitura de `enabled_modules` no master e persistência na sessão.
- `index.php` — bloqueio de rota por módulo antes da checagem de permissão.
- `app/views/layout/header.php` — ocultação de itens de menu quando módulo estiver desativado.

### Mapeamento inicial de módulos
- `financial` → páginas `financial`, `financial_payments`, `financial_transactions`
- `boleto` → aba `settings&tab=boleto` + elementos de boleto no pipeline
- `fiscal` → aba `settings&tab=fiscal`
- `nfe` → seção de nota fiscal no detalhe do pipeline

### Exemplo de JSON em `tenant_clients.enabled_modules`
```json
{
  "financial": true,
  "boleto": true,
  "fiscal": true,
  "nfe": false
}
```

### Regras obrigatórias para novas features modulares
1. Toda nova área modular deve mapear rota/página no `ModuleBootloader`.
2. Toda aba modular em `settings` deve passar por `sanitizeSettingsTab()`.
3. Menus de módulos devem ser ocultados quando módulo estiver desativado.
4. Se houver mudança de banco para suportar módulo, criar SQL em `/sql/update_YYYYMMDD_*.sql`.

---