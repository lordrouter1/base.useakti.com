# Checklist de Auditoria — Segurança

## CSRF (Cross-Site Request Forgery)
- [ ] Token gerado com `random_bytes()` ou equivalente criptográfico
- [ ] Token com pelo menos 32 bytes (64 hex chars)
- [ ] Rotação periódica do token (ideal: 30min)
- [ ] Grace period para tokens expirados (evita false positives)
- [ ] Validação com `hash_equals()` (timing-safe)
- [ ] Middleware global antes do dispatch
- [ ] Meta tag no `<head>` para AJAX
- [ ] jQuery/fetch auto-inject do token em requests POST
- [ ] Rotas isentas documentadas e justificadas
- [ ] Testes automatizados para CSRF

## XSS (Cross-Site Scripting)
- [ ] Helper `e()` / `Escape::html()` disponível nas views
- [ ] Helper `eAttr()` para contexto de atributos HTML
- [ ] Helper `eJs()` / `json_encode()` para contexto JavaScript
- [ ] Buscar usos de `echo $var` sem escape em views (grep: `echo \$` sem `e(` ou `htmlspecialchars`)
- [ ] Buscar `innerHTML` sem DOMPurify em arquivos JS
- [ ] Buscar `addslashes()` em contexto JS (insuficiente)
- [ ] Buscar construção de HTML via concatenação no PHP
- [ ] Buscar `document.write()` ou `eval()` em JS
- [ ] Popover/tooltip content escapado corretamente

## SQL Injection
- [ ] Todas as queries usam prepared statements (`prepare()` + `execute()`)
- [ ] Buscar interpolação em queries: `"SELECT...{$var}"`, `"...WHERE id = $id"`
- [ ] Buscar `query()` direto sem bind
- [ ] LIKE com `%` parametrizado corretamente
- [ ] ORDER BY não aceita input direto do usuário (whitelist)

## Information Disclosure
- [ ] `$e->getMessage()` nunca exposto em JSON responses
- [ ] Stack traces logados internamente, nunca retornados ao cliente
- [ ] `display_errors = Off` em produção
- [ ] Sem credenciais hardcoded no código-fonte
- [ ] `.env` ou equivalente no `.gitignore`
- [ ] Sem comentários HTML com informações internas

## File Upload
- [ ] Validação de MIME type via `finfo(FILEINFO_MIME_TYPE)`
- [ ] Validação de extensão por whitelist (não blacklist)
- [ ] Magic bytes check para imagens
- [ ] Tamanho máximo definido
- [ ] Nome do arquivo sanitizado (sem path traversal)
- [ ] Diretório de upload fora do webroot ou com `.htaccess` deny
- [ ] Multi-tenant: upload isolado por tenant

## Session
- [ ] `httponly` flag ativada
- [ ] `samesite` flag (`Strict` ou `Lax`)
- [ ] `secure` flag em produção (HTTPS)
- [ ] `use_strict_mode` ativado
- [ ] Session ID regenerado após login
- [ ] Timeout de inatividade configurado

## HTTP Headers
- [ ] `Content-Security-Policy` (CSP) definido
- [ ] `X-Frame-Options: DENY` ou `SAMEORIGIN`
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `Strict-Transport-Security` (HSTS)
- [ ] `Referrer-Policy`
- [ ] `Permissions-Policy`

## Rate Limiting
- [ ] Login com rate limit (LoginAttempt ou similar)
- [ ] API com rate limit por IP/token
- [ ] Proteção contra flood 404

## Auth
- [ ] Senhas com bcrypt ou argon2
- [ ] `password_needs_rehash()` na verificação
- [ ] Política de senha mínima (comprimento, complexidade)
- [ ] `must_change_password` flag

## Open Redirect
- [ ] `header('Location: ...')` validado contra whitelist
- [ ] Sem `$_GET['redirect']` ou `$_POST['return_url']` direto no redirect

## API Security
- [ ] JWT com `HS256` ou `RS256`
- [ ] Token com expiração (`exp` claim)
- [ ] CORS configurado (origins específicos, não `*`)
- [ ] Webhook com validação de signature/origin
- [ ] API keys não expostas no frontend

## Dependencies
- [ ] `composer audit` sem vulnerabilidades conhecidas
- [ ] `npm audit` para API Node.js
- [ ] CDN resources com SRI (Subresource Integrity)
- [ ] Versões de PHP/Node.js suportadas
