# Auditoria de Frontend — Akti Master v1

> **Data da Auditoria:** 06/04/2026  
> **Escopo:** UI/UX, CSS, JavaScript, responsividade

---

## 1. Resumo Executivo

| Aspecto | Nota | Status |
|---------|------|--------|
| Design System | 8/10 | ✅ |
| Responsividade | 7/10 | ⚠️ |
| JavaScript/AJAX | 7/10 | ⚠️ |
| Acessibilidade | 4/10 | ❌ |
| UX/Fluxos | 7/10 | ⚠️ |
| Dark Mode | 0/10 | ❌ |

---

## 2. Design System ✅

**Arquivo:** `master/assets/css/style.css` (866 linhas)

O Master possui um design system próprio com variáveis CSS:
```css
:root {
    --primary: #1b3d6e;
    --secondary: #4a90d9;
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
    --dark: #1a1a2e;
    --light: #f8f9fa;
}
```

**Pontos positivos:**
- ✅ Paleta consistente (azul escuro profissional)
- ✅ Sidebar + topbar pattern bem implementado
- ✅ Cards, tabelas, formulários estilizados
- ✅ Scrollbar customizado
- ✅ Loading spinner para operações async
- ✅ Empty states para listas vazias
- ✅ Plan cards com destaque "popular"

---

## 3. Stack Frontend

| Tecnologia | Versão | Carregamento |
|-----------|--------|-------------|
| Bootstrap | 5.3.3 | CDN (jsdelivr) |
| jQuery | 3.7.1 | CDN (jsdelivr) |
| Font Awesome | 6.5.1 | CDN (cdnflare) |
| SweetAlert2 | v11 | CDN (jsdelivr) |

**FE-001:** Dependência total de CDNs externos. Se um CDN cair, o painel fica inutilizável.
**Recomendação:** Para um painel administrativo crítico, considerar assets locais como fallback.

---

## 4. JavaScript

**Arquivo:** `master/assets/js/app.js` (16 linhas)

O JS principal é mínimo (sidebar toggle + tooltips). A maioria do JS está inline nas views.

### ⚠️ Problemas:

**FE-002:** JS inline extenso nas views:
- `git/index.php` — ~300 linhas de JS inline
- `backup/index.php` — ~100 linhas de JS inline
- `clients/index.php` — ~150 linhas de JS inline
- `migrations/index.php` — ~150 linhas de JS inline

**Recomendação:** Extrair para arquivos JS por módulo (`assets/js/git.js`, `assets/js/backup.js`, etc.)

**FE-003:** AJAX sem CSRF token:
```javascript
// Padrão atual (inseguro):
$.ajax({ url: '?page=git&action=fetch', method: 'POST', data: { repo: repoName } });

// Deveria ser:
$.ajax({
    url: '?page=git&action=fetch',
    method: 'POST',
    data: { repo: repoName, csrf_token: csrfToken },
    headers: { 'X-CSRF-TOKEN': csrfToken }
});
```

---

## 5. Responsividade ⚠️

**Status:**
- ✅ Sidebar responsiva (colapsa em mobile)
- ✅ Bootstrap grid usado nas views
- ✅ Breakpoints definidos (576px, 768px, 992px, 1200px)
- ⚠️ Tabelas grandes não usam `table-responsive` consistentemente
- ⚠️ Views de migrations e git podem quebrar em telas pequenas

---

## 6. SweetAlert2 ✅

Uso consistente do SweetAlert2:
- ✅ Confirmações de exclusão com dupla verificação (nome + senha)
- ✅ Toast para feedback de operações
- ✅ Loading indicator durante operações async
- ✅ Flash messages (success/error) via sessão no footer

**Padrão implementado:**
```javascript
Swal.fire({
    title: 'Confirmar Exclusão',
    html: `<p>Digite o nome do banco <strong>${dbName}</strong> para confirmar:</p>
           <input type="text" id="confirmDbName" class="swal2-input">
           <input type="password" id="adminPassword" class="swal2-input" placeholder="Sua senha">`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    confirmButtonText: 'Excluir',
    preConfirm: () => { /* validação */ }
});
```

---

## 7. Dark Mode ❌

**Status:** Não implementado. O sistema principal Akti possui dark mode com `[data-theme="dark"]`. O Master não tem suporte algum.

**Recomendação:** Implementar usando variáveis CSS já definidas:
```css
[data-theme="dark"] {
    --primary: #2d5aa0;
    --dark: #0d1117;
    --light: #161b22;
    --body-bg: #0d1117;
    --text-color: #c9d1d9;
}
```

---

## 8. Acessibilidade ❌

**Problemas:**
- Sem ARIA labels nos botões de ação
- Sem `role` nos elementos interativos
- Sidebar não possui landmarks `<nav aria-label="...">`
- Formulários sem `aria-describedby` para dicas
- Contraste pode ser insuficiente em alguns badges

---

## 9. Layout e Componentes

### Header (`layout/header.php`) ✅
- HTML5 com meta viewport
- Sidebar com ícones Font Awesome
- Menu ativo destacado via `$page === 'nome'`
- Topbar com página atual e logout

### Footer (`layout/footer.php`) ✅
- Scripts no final do body
- Flash messages via SweetAlert2
- Suporte a scripts específicos por página via variable injection

### Dashboard (`dashboard/index.php`) ✅
- Cards de estatísticas
- Ações rápidas
- Atividade recente

---

## 10. Oportunidades de Melhoria de UX

**FE-004:** Falta indicador de "ambiente" (dev/staging/prod) no header. Importante para evitar ações acidentais em produção.

**FE-005:** Falta breadcrumbs para navegação em sub-páginas.

**FE-006:** Falta confirmação visual de sucesso após operações git (pull, fetch) — mostra apenas resultado JSON bruto.

**FE-007:** A view de migrations poderia ter um diff visual (antes/depois) para SQL que será executado.
