# Fluxo modular de plugins com controle central pela `akti_master`

## Objetivo
Definir um fluxo modular para que a base `akti_master` consiga **ativar, desativar e versionar módulos** (ex.: boletos, financeiro, NFe, entre outros), com governança central e execução segura nas bases operacionais.

---

## Princípio da arquitetura

- A `akti_master` funciona como **control plane** (fonte de verdade de módulos e regras).
- Cada base operacional (tenant/cliente) funciona como **runtime plane** (onde o módulo roda).
- Toda mudança de estado de módulo nasce na `akti_master` e é propagada para as bases autorizadas.

---

## Módulos padrão sugeridos

- `boletos`
- `financeiro`
- `nfe`
- `fiscal`
- `estoque_avancado`
- `crm`
- `integracao_bancaria`

> Observação: esses módulos seguem o mesmo contrato técnico e podem ser ativados por cliente, plano ou ambiente.

---

## Fluxo de lifecycle modular (controlado pela `akti_master`)

1. **Cadastro do módulo na `akti_master`**
   - Define metadados (`nome`, `slug`, `versão`, `provedor`, `tipo`).
   - Define dependências (ex.: `nfe` depende de `fiscal` e `financeiro`).

2. **Política de elegibilidade**
   - Define onde o módulo pode ser usado:
     - por plano (`basic`, `pro`, `enterprise`),
     - por tenant específico,
     - por feature flag.

3. **Publicação da versão**
   - Módulo é publicado como `released` na `akti_master`.
   - Pode ter rollout por lotes (5%, 20%, 100% dos tenants elegíveis).

4. **Ativação por tenant/base**
   - Admin marca `ativar` na `akti_master`.
   - Sistema cria uma tarefa de provisionamento para a base destino:
     - aplica migrations do módulo,
     - registra rotas e listeners,
     - executa health-check.
   - Se sucesso, estado fica `active` para aquele tenant.

5. **Desativação por tenant/base**
   - Admin marca `desativar` na `akti_master`.
   - Runtime interrompe hooks, jobs e menus do módulo.
   - Dados podem ser preservados (`soft disable`) para reativação futura.

6. **Suspensão automática**
   - Em falhas graves (erro recorrente, timeout, violação de política), módulo vira `suspended` no tenant.
   - A `akti_master` recebe evento de incidente e permite ações (retry, rollback, desativação global).

7. **Atualização e rollback**
   - Nova versão é aplicada por tenant, com janela de manutenção opcional.
   - Se health-check falhar, rollback automático para última versão estável.

---

## Estados por módulo (por tenant)

`pending -> provisioning -> active -> disabled -> suspended -> upgrading -> rollback -> removed`

- `pending`: aguardando execução na base destino.
- `provisioning`: aplicando scripts e configurações do módulo.
- `disabled`: desligado manualmente, mantendo dados.
- `suspended`: bloqueado por erro/política.

---

## Modelo mínimo de dados (na `akti_master`)

## 1) `modules_catalog`
Catálogo global de módulos.

Campos sugeridos:
- `id`
- `slug` (único)
- `name`
- `current_version`
- `status` (`draft`, `released`, `deprecated`)
- `requires_modules` (json)
- `created_at`, `updated_at`

## 2) `tenant_modules`
Estado de cada módulo em cada tenant/base.

Campos sugeridos:
- `id`
- `tenant_id`
- `module_slug`
- `target_version`
- `installed_version`
- `state`
- `last_error`
- `updated_by`
- `updated_at`

## 3) `module_audit_log`
Rastreabilidade completa de mudanças.

Campos sugeridos:
- `id`
- `tenant_id`
- `module_slug`
- `action` (`activate`, `disable`, `upgrade`, `rollback`, `suspend`)
- `payload` (json)
- `actor`
- `created_at`

---

## Contrato de módulo (`module.json`)

```json
{
  "name": "Módulo NFe",
  "slug": "nfe",
  "version": "2.1.0",
  "main": "bootstrap.php",
  "install": "install.php",
  "uninstall": "uninstall.php",
  "dependencies": ["fiscal", "financeiro"],
  "permissions": [
    "fiscal.read",
    "fiscal.write",
    "invoice.issue"
  ],
  "compatibility": {
    "core": ">=3.0 <4.0"
  },
  "healthcheck": {
    "type": "http",
    "endpoint": "/health/module/nfe"
  }
}
```

---

## Exemplo prático de ativação (NFe)

1. Na `akti_master`, tenant `acme` recebe `nfe = activate`.
2. Orquestrador valida dependências (`fiscal` e `financeiro`).
3. Runtime da base `acme` aplica `install.php` + migrations.
4. Registra rotas/eventos de emissão fiscal.
5. Executa `/health/module/nfe`.
6. Se OK: `state = active`; se falhar: `state = rollback` + log de erro.

---

## Regras de governança recomendadas

- Ativação/desativação somente por perfis com permissão de plataforma.
- Toda mudança obrigatoriamente auditada (`module_audit_log`).
- Rollout progressivo para módulos sensíveis (financeiro, NFe, boletos).
- Limites de execução por módulo (timeout/memória/retentativas).
- Alertas proativos por módulo e por tenant.

---

## MVP sugerido (implementável rápido)

1. Criar tabelas: `modules_catalog`, `tenant_modules`, `module_audit_log`.
2. Criar tela na `akti_master` para toggle por tenant (`ativar/desativar`).
3. Criar worker de provisionamento com estados (`pending`, `provisioning`, `active`, `failed`).
4. Implementar health-check e rollback básico.
5. Liberar inicialmente 3 módulos: `boletos`, `financeiro`, `nfe`.

---

## Resultado esperado

Com esse fluxo, a `akti_master` passa a controlar centralmente o ciclo de vida modular, permitindo habilitar/desabilitar funcionalidades por tenant de forma segura, auditável e com baixo risco operacional.
