# 05 — Auditoria de Views e Assets

> **Data:** 06/04/2026
> **Escopo:** 16 views em `app/views/master/`, 2 assets em `assets/`

---

## Resumo

| Categoria | Total | OK | Com Problema |
|-----------|-------|-----|-------------|
| Layout (header/footer) | 2 | 2 | 0 |
| Views de conteúdo | 13 | 12 | 1 (escape menor) |
| View não utilizada | 1 | — | — |
| Assets (CSS/JS) | 2 | 2 | 0 |

---

## 1. Layout Header (`app/views/master/layout/header.php`)

### Verificações

| Item | Status | Detalhe |
|------|--------|---------|
| URLs com prefixo `master_*` | ✅ | `?page=master_dashboard`, `?page=master_plans`, etc. |
| Sessão `$_SESSION['user_name']` | ✅ | Correto (não usa `admin_name` legado) |
| CSS path | ✅ | `assets/css/master.css` |
| Logo path | ✅ | `assets/logos/akti-logo-dark-nBg.svg` |
| XSS em nome admin | ✅ | `htmlspecialchars($adminName)` |
| `$currentPage` | ✅ | `str_replace('master_', '', $_GET['page'])` — resolve comparações |
| Logout | ✅ | `?page=login&action=logout` (usa sistema unificado) |

### Problemas menores
- 🔵 `$pageSubtitle` sem escape: `<?= $pageSubtitle ?>` — deveria ser `<?= e($pageSubtitle ?? '') ?>`
- 🔵 `$topbarActions` sem escape — aceitável pois é HTML gerado pelo controller

---

## 2. Layout Footer (`app/views/master/layout/footer.php`)

### Verificações

| Item | Status | Detalhe |
|------|--------|---------|
| CSRF global para AJAX | ✅ | `$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } })` |
| Token via `Security::getCsrfToken()` | ✅ | `\Akti\Core\Security::getCsrfToken()` |
| JS path | ✅ | `assets/js/master.js` |
| Flash messages | ✅ | `json_encode()` em SweetAlert2 (seguro) |
| Cleanup sessão | ✅ | `unset($_SESSION['success'])`, `unset($_SESSION['error'])` |

### Problemas
- Nenhum

---

## 3. Views de Conteúdo

### Verificações Globais (aplicadas a todas)

| Verificação | Resultado |
|-------------|-----------|
| `require_once` para header/footer | ✅ Removidos (renderMaster faz o include) |
| URLs com prefixo `master_*` | ✅ Todas atualizadas |
| `csrf_field()` em forms POST | ✅ 9 formulários, todos com csrf_field() |
| `htmlspecialchars()` / `e()` para dados | ✅ Consistente em dados dinâmicos |

### Por View

| View | Forms | CSRF | Escape | URLs | Status |
|------|-------|------|--------|------|--------|
| `dashboard/index.php` | 0 | N/A | ✅ `htmlspecialchars()` | ✅ | ✅ OK |
| `plans/index.php` | 0 | N/A | ✅ | ✅ | ✅ OK |
| `plans/create.php` | 1 POST | ✅ | ✅ | ✅ | ✅ OK |
| `plans/edit.php` | 1 POST | ✅ | ✅ | ✅ | ✅ OK |
| `clients/index.php` | 1 POST (delete modal) | ✅ | ✅ | ✅ | ✅ OK |
| `clients/create.php` | 1 POST | ✅ | ✅ | ✅ | ✅ OK |
| `clients/edit.php` | 2 POST | ✅ | ✅ | ✅ | ✅ OK |
| `migrations/index.php` | 1 POST | ✅ | ✅ | ✅ | ✅ OK |
| `migrations/results.php` | 0 | N/A | ✅ | ✅ | ✅ OK |
| `migrations/users.php` | 1 POST | ✅ | ✅ | ✅ | ✅ OK |
| `git/index.php` | 0 (AJAX only) | Via `$.ajaxSetup` | ✅ | ✅ | ✅ OK |
| `backup/index.php` | 0 (AJAX only) | Via `$.ajaxSetup` | ✅ | ✅ | ✅ OK |
| `logs/index.php` | 0 (GET params) | N/A | ⚠️ ver nota | ✅ | ⚠️ |

### Nota sobre `logs/index.php`
- 🔵 O conteúdo dos logs é exibido em `<pre>` — se o log contiver HTML/JS, deve ser escapado. O `NginxLog::readTail()` retorna array de linhas, que devem ser concatenadas com `htmlspecialchars()` antes de exibir

---

## 4. View Não Utilizada

`app/views/master/auth/login.php` — Foi copiada do master original mas **não é usada**. O login unificado usa `app/views/login.php` do sistema principal. Esta view pode ser removida.

---

## 5. Assets

### `assets/css/master.css`
- ✅ Copiado de `master/assets/css/style.css`
- ✅ Contém ~1000+ linhas de estilos do painel master
- ✅ Inclui sidebar, cards, stats, tabelas, responsividade
- ✅ Referenciado corretamente no `header.php`

### `assets/js/master.js`
- ✅ Copiado de `master/assets/js/app.js`
- ✅ ~28 linhas (sidebar toggle + tooltips Bootstrap)
- ✅ Referenciado corretamente no `footer.php`
- 🔵 Minimalista — funcionalidade JavaScript principal está inline nas views (padrão do master original)

---

## Conclusão

A migração de views foi **bem executada**. As substituições automatizadas de URLs, sessão e paths funcionaram corretamente. A adição de `csrf_field()` e `$.ajaxSetup` para CSRF cobre tanto forms quanto AJAX. O único ponto de atenção é o escape de conteúdo de logs na view `logs/index.php`.
