# 🛡️ Relatório de Segurança Detalhado — Akti

**Data:** 30/03/2026  
**Classificação:** Interno — Confidencial  
**Referência:** Auditoria Geral 2026

---

## Vulnerabilidades Encontradas

### SEV-01 — Credenciais Hardcoded no Código-Fonte
| Campo          | Valor |
|----------------|-------|
| **Severidade** | 🔴 CRÍTICA |
| **CVSS**       | 9.1 |
| **Arquivo(s)** | `app/config/tenant.php` (L72, L85), `app/models/IpGuard.php` (L57) |
| **Tipo**       | CWE-798: Use of Hard-coded Credentials |

**Descrição:**  
As credenciais de banco de dados (`kP9!vR2@mX6#zL5$`) estão hardcoded como fallback em `getDefaultTenantConfig()` e `getMasterConfig()`. Se o repositório for exposto (GitHub leak, acesso não autorizado ao servidor), um atacante obtém acesso direto ao banco de dados.

**Correção:**
```php
// ANTES (vulnerável):
'password' => getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$',

// DEPOIS (seguro):
'password' => getenv('AKTI_DB_PASS') ?: (function() {
    throw new \RuntimeException('AKTI_DB_PASS não configurada. Defina no .env');
})(),
```

---

### SEV-02 — Exposição de Erro de Conexão ao Banco
| Campo          | Valor |
|----------------|-------|
| **Severidade** | 🔴 ALTA |
| **CVSS**       | 7.5 |
| **Arquivo**    | `app/config/database.php` (L41) |
| **Tipo**       | CWE-209: Information Exposure Through an Error Message |

**Descrição:**  
O método `getConnection()` faz `echo 'Erro na conexão: ' . $exception->getMessage()` que pode expor hostname, porta, nome do banco e detalhes internos do MySQL.

**Correção:**
```php
// ANTES:
catch(PDOException $exception) {
    echo 'Erro na conexão: ' . $exception->getMessage();
}

// DEPOIS:
catch(PDOException $exception) {
    error_log('[Database] Connection failed: ' . $exception->getMessage());
    throw new \RuntimeException('Falha na conexão com o banco de dados.');
}
```

---

### SEV-03 — Session Fixation (Ausência de Regeneração de ID)
| Campo          | Valor |
|----------------|-------|
| **Severidade** | 🔴 ALTA |
| **CVSS**       | 7.1 |
| **Arquivo**    | `app/controllers/UserController.php` → `app/models/User.php` (método login) |
| **Tipo**       | CWE-384: Session Fixation |

**Descrição:**  
Após autenticação bem-sucedida, o session ID não é regenerado. Um atacante que conheça o session ID antes do login pode sequestrar a sessão após o login.

**Correção:**  
No `UserController::login()`, após `$user->login()` retornar true:
```php
session_regenerate_id(true);
```

---

### SEV-04 — Ausência de Security Headers HTTP
| Campo          | Valor |
|----------------|-------|
| **Severidade** | ⚠️ MÉDIA |
| **CVSS**       | 5.3 |
| **Arquivo**    | `index.php` (ausência) |
| **Tipo**       | CWE-693: Protection Mechanism Failure |

**Headers ausentes:**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security` (em HTTPS)

---

### SEV-05 — Arquivos de Backup Acessíveis
| Campo          | Valor |
|----------------|-------|
| **Severidade** | ⚠️ MÉDIA |
| **CVSS**       | 5.0 |
| **Arquivo**    | `app/controllers/FinancialController.php.bak`, `.php.new` |
| **Tipo**       | CWE-538: File and Directory Information Exposure |

**Descrição:**  
Arquivos `.bak` e `.new` podem ser acessíveis via web e conter código com vulnerabilidades ou informações sensíveis.

---

### SEV-06 — Scripts de Debug Acessíveis
| Campo          | Valor |
|----------------|-------|
| **Severidade** | ⚠️ MÉDIA |
| **CVSS**       | 6.5 |
| **Diretório**  | `scripts/` (17 arquivos PHP de diagnóstico) |
| **Tipo**       | CWE-489: Active Debug Code |

**Descrição:**  
O diretório `scripts/` contém ferramentas como `diagnostico_completo.php`, `debug_approval.php`, `fix_customer_duplicates.php` que, se acessíveis via web, podem expor ou alterar dados.

**Correção:**
1. adicionar ao .gitignore para não enviar arquivos para produção

---

### SEV-07 — Política de Senha Fraca
| Campo          | Valor |
|----------------|-------|
| **Severidade** | ⚠️ MÉDIA |
| **CVSS**       | 4.3 |
| **Arquivo**    | `app/controllers/UserController.php` (L81) |
| **Tipo**       | CWE-521: Weak Password Requirements |

**Descrição:**  
Apenas 6 caracteres mínimos, sem exigência de complexidade.

**Correção:**
```php
$v->minLength('password', $password, 8, 'Senha')
  ->custom('password', function($val) {
      return preg_match('/[A-Z]/', $val) && preg_match('/[0-9]/', $val);
  }, 'Senha deve conter pelo menos 1 letra maiúscula e 1 número');
```

---

### SEV-08 — Sanitização Dupla no Model (Potencial Corrupção)
| Campo          | Valor |
|----------------|-------|
| **Severidade** | ⚠️ BAIXA |
| **CVSS**       | 3.1 |
| **Arquivo**    | `app/models/User.php` (L121-122) |
| **Tipo**       | CWE-116: Improper Encoding or Escaping of Output |

**Descrição:**  
`htmlspecialchars(strip_tags())` é aplicado no Model ao salvar dados. Isso significa que se alguém salvar "O'Brien", será armazenado como "O&amp;#039;Brien" no banco. A sanitização deveria acontecer apenas na SAÍDA (views), não na entrada do banco.

---

## Checklist de Segurança

| # | Controle de Segurança                       | Status |
|---|---------------------------------------------|--------|
| 1 | HTTPS obrigatório                           | ⚠️ Parcial (condicional) |
| 2 | CSRF protection                             | ✅ Implementado |
| 3 | XSS prevention (escape de saída)            | ⚠️ Parcial |
| 4 | SQL Injection prevention                    | ✅ Prepared statements |
| 5 | Session security (httponly, samesite)        | ✅ Implementado |
| 6 | Session fixation prevention                 | ❌ Ausente |
| 7 | Brute force protection                      | ✅ LoginAttempt |
| 8 | Rate limiting                               | ✅ Implementado |
| 9 | Password hashing (bcrypt)                   | ✅ Implementado |
| 10| Security headers                            | ❌ Ausente |
| 11| File upload validation                      | ⚠️ Parcial |
| 12| Error handling (sem exposição)              | ❌ Database.php expõe |
| 13| Logging de segurança                        | ✅ Implementado |
| 14| Credenciais em variáveis de ambiente        | ❌ Hardcoded fallback |
| 15| Princípio do menor privilégio               | ⚠️ Admin tem acesso total |
| 16| Audit trail                                 | ✅ system_logs |
| 17| IP blacklisting                             | ✅ IpGuard |
| 18| Multi-tenant isolation                      | ✅ Por banco de dados |
| 19| Content Security Policy                     | ❌ Ausente |
| 20| Dependency vulnerability scanning           | ❌ Ausente |

**Score:** 12/20 controles implementados = **60%**  
**Alvo profissional:** 18/20 = **90%**

---

**Recomendação:** Corrigir as vulnerabilidades CRÍTICAS (SEV-01 a SEV-03) imediatamente antes do próximo deploy em produção.
