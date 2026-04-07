# Auditoria — Módulo Loja & Site Builder (v1)

> **Data:** 2026-04-07  
> **Versão:** v1.0  
> **Escopo:** Avaliação completa do módulo de Loja Online e Site Builder do sistema Akti

---

## Sumário

1. [Visão Geral](#1-visão-geral)
2. [Inventário de Arquivos](#2-inventário-de-arquivos)
3. [O Que Está Funcionando](#3-o-que-está-funcionando)
4. [O Que NÃO Está Implementado](#4-o-que-não-está-implementado)
5. [Problemas Bloqueantes](#5-problemas-bloqueantes)
6. [Análise: index.php para a Loja](#6-análise-indexpphp-para-a-loja)
7. [Plano de Implementação](#7-plano-de-implementação)
8. [Roadmap de Fases](#8-roadmap-de-fases)

---

## 1. Visão Geral

O módulo é composto por duas partes:

| Parte | Descrição | Status |
|-------|-----------|--------|
| **Site Builder (Admin)** | Editor visual drag-and-drop para construir páginas da loja | ~85% implementado |
| **Loja (Frontend)** | Vitrine pública acessível por clientes para navegação e compra | ~20% implementado (templates existem, mas não há runtime) |

### Arquitetura Atual

```
┌─────────────────────────────────────────────────────┐
│ ADMIN (ERP)  ─  ?page=site_builder                  │
│   ├── SiteBuilderController (16 actions)            │
│   ├── SiteBuilder Model (CRUD completo)             │
│   ├── Editor UI (split-view + preview iframe)       │
│   └── Preview renderer (PHP puro)                   │
├─────────────────────────────────────────────────────┤
│ LOJA (Frontend) ─ NÃO POSSUI ENTRY POINT            │
│   ├── Templates Twig (16 arquivos)  ← SEM ENGINE    │
│   ├── Assets (CSS, JS, SW)                          │
│   ├── Config (settings_schema.json)                 │
│   └── Sem Controller de frontend público            │
└─────────────────────────────────────────────────────┘
```

---

## 2. Inventário de Arquivos

### 2.1 Backend (PHP) — Admin/Editor

| Arquivo | Linhas | Status |
|---------|--------|--------|
| `app/controllers/SiteBuilderController.php` | ~635 | ✅ Completo |
| `app/models/SiteBuilder.php` | ~450 | ✅ Completo |
| `app/views/site_builder/index.php` | ~1250 | ✅ Completo |
| `app/views/site_builder/preview.php` | ~450 | ✅ Completo |

### 2.2 Frontend (Loja) — Templates Twig

| Diretório | Arquivos | Status |
|-----------|----------|--------|
| `loja/layouts/` | `base.html.twig` | ✅ Completo |
| `loja/templates/pages/` | 6 templates (home, product, collection, cart, contact, custom) | ✅ Escritos, sem runtime |
| `loja/templates/sections/` | 9 templates (hero-banner, featured-products, image-with-text, gallery, newsletter, testimonials, custom-html, header, footer) | ✅ Escritos, sem runtime |
| `loja/templates/snippets/` | 5 snippets (product-card, price, breadcrumb, pagination, social-icons) | ✅ Escritos, sem runtime |

### 2.3 Assets da Loja

| Arquivo | Linhas | Descrição |
|---------|--------|-----------|
| `loja/assets/css/theme.css` | ~60 | CSS base com variáveis, hover effects, responsivo |
| `loja/assets/js/theme.js` | ~40 | Smooth scroll, lazy loading (vanilla JS) |
| `loja/assets/images/placeholder.svg` | — | Placeholder para imagens |
| `loja/loja-sw.js` | ~60 | Service Worker (cache-first estáticos, network-first HTML) |

### 2.4 Configuração

| Arquivo | Descrição |
|---------|-----------|
| `loja/config/settings_schema.json` | Schema JSON com 14 configurações agrupadas (cores, tipografia, header, footer) |

### 2.5 Banco de Dados

| Tabela | Função | Migration |
|--------|--------|-----------|
| `sb_pages` | Páginas da loja (title, slug, type, meta SEO) | ❌ Não encontrada em `/sql/` |
| `sb_sections` | Seções dentro de páginas (type, settings JSON, sort_order) | ❌ Não encontrada |
| `sb_components` | Componentes dentro de seções (type, content JSON, grid) | ❌ Não encontrada |
| `sb_theme_settings` | Configurações de tema (key-value por tenant) | ❌ Não encontrada |

> **Nota:** O código do Model referencia estas 4 tabelas. As tabelas podem já existir no banco (criadas manualmente ou por migration anterior não versionada), mas **não existe arquivo SQL de migration em `/sql/` nem em `/sql/prontos/`** para estas tabelas.

### 2.6 Rotas e Menu

| Config | Status | Detalhes |
|--------|--------|----------|
| `app/config/routes.php` | ✅ Registrado | Rota `site_builder` com 16 actions mapeadas |
| `app/config/menu.php` | ✅ Registrado | Menu sob "Ferramentas" com permissão |

---

## 3. O Que Está Funcionando

### 3.1 Editor Visual (Admin) ✅

| Feature | Status | Notas |
|---------|--------|-------|
| Split-view (editor + preview) | ✅ | Layout flexbox responsivo |
| Gerenciamento de páginas (CRUD) | ✅ | Criar, editar, excluir, listar |
| Tipos de página | ✅ | home, about, products, services, contact, blog, custom |
| Gerenciamento de seções | ✅ | Adicionar, editar, excluir, reordenar |
| Drag-and-drop de seções | ✅ | Via SortableJS |
| 9 tipos de seção | ✅ | hero-banner, featured-products, image-with-text, gallery, newsletter, testimonials, custom-html, header, footer |
| 8 tipos de componente | ✅ | rich-text, image, button, spacer, divider, custom-html, product-grid, product-carousel |
| Configurações de tema | ✅ | Cores, tipografia, header, footer |
| Preview em iframe | ✅ | Renderiza com dados reais do banco + produtos |
| Preview responsivo | ✅ | Desktop, tablet, mobile (botões de viewport) |
| AJAX com CSRF | ✅ | Todos endpoints protegidos |
| Multi-tenant | ✅ | tenant_id em todas operações |
| Batch save com transação | ✅ | Seções salvas atomicamente |
| Validação de reordenação | ✅ | Verifica IDs pertencem à página |
| Sanitização HTML | ✅ | `SafeHtml::sanitizeFragment()` para custom-html |
| Validação de URLs | ✅ | Bloqueia javascript:, vbscript:, data: |

### 3.2 Model (Backend) ✅

| Operação | Método | Status |
|----------|--------|--------|
| Listar páginas | `getPages()` | ✅ |
| Criar página | `createPage()` | ✅ |
| Editar página | `updatePage()` | ✅ |
| Excluir página (cascade) | `deletePage()` | ✅ |
| Page completa (seções+componentes) | `getFullPage()` | ✅ |
| Listar seções | `getSections()` | ✅ |
| Criar seção | `createSection()` | ✅ |
| Batch save seções | `saveSectionsBatch()` | ✅ |
| Reordenar seções | `reorderSections()` | ✅ |
| CRUD componentes | `create/update/deleteComponent()` | ✅ |
| Salvar tema (upsert) | `saveThemeSettings()` | ✅ |
| Ler tema | `getThemeSettings()` | ✅ |

### 3.3 Templates Twig ✅ (escritos mas sem runtime)

- 16 templates completos e bem estruturados
- Usam filtros Twig corretos (`|default`, `|striptags`, `|number_format`, `|raw`)
- Layout base com Bootstrap 5 + Font Awesome 6
- Snippets reutilizáveis (product-card, price, breadcrumb, pagination)

### 3.4 Assets da Loja ✅

- CSS com variáveis CSS customizáveis via tema
- JS vanilla com lazy loading
- Service Worker funcional para PWA

---

## 4. O Que NÃO Está Implementado

### 4.1 Bloqueantes (impedem uso em produção)

| Item | Prioridade | Descrição |
|------|-----------|-----------|
| **Migration SQL** | 🔴 Crítico | Arquivo de migration para as 4 tabelas (`sb_pages`, `sb_sections`, `sb_components`, `sb_theme_settings`) não existe em `/sql/` |
| **Twig Engine** | 🔴 Crítico | `twig/twig` não está no `composer.json` — templates `.html.twig` não podem ser renderizados |
| **Frontend Entry Point** | 🔴 Crítico | Não existe `loja/index.php` nem controller público para servir a loja ao visitante |
| **Roteamento público** | 🔴 Crítico | Não há rota `/loja/{slug}` ou similar para URLs amigáveis da vitrine |

### 4.2 Funcionalidades Importantes Ausentes

| Item | Prioridade | Descrição |
|------|-----------|-----------|
| **Integração com produtos reais** | 🟠 Alto | Seção `featured-products` usa dados mock no preview. Falta query real por categoria/tag |
| **Upload de imagens** | 🟠 Alto | `image_picker` no schema de tema não renderiza input de arquivo. Logo e imagens de seções não podem ser enviadas |
| **Carrinho de compras** | 🟠 Alto | Template `cart.html.twig` existe mas não há backend (sessão, adicionar, remover, totais) |
| **Catálogo de produtos** | 🟠 Alto | Template `collection.html.twig` existe mas falta controller de listagem pública |
| **Página de produto** | 🟠 Alto | Template `product.html.twig` existe mas falta controller de detalhe público |
| **Formulário de contato** | 🟡 Médio | Template `contact.html.twig` existe mas falta backend para processar/enviar email |
| **Newsletter** | 🟡 Médio | Seção `newsletter` existe mas formulário não faz nada |
| **SEO/Meta tags** | 🟡 Médio | Campos `meta_title` e `meta_description` existem no banco mas não são injetados no head |
| **Busca de produtos** | 🟡 Médio | Não implementado |
| **Checkout/Pagamento** | 🟡 Médio | Não implementado |
| **Conta do cliente** | 🟡 Médio | Login/registro de clientes da loja não existe |

### 4.3 Funcionalidades Avançadas Ausentes

| Item | Prioridade | Descrição |
|------|-----------|-----------|
| Versionamento de página | 🔵 Baixo | Sem histórico de edição |
| Templates pré-prontos | 🔵 Baixo | Biblioteca de templates iniciais |
| Duplicar página | 🔵 Baixo | Funcionalidade de clone |
| Preview com URL pública | 🔵 Baixo | Compartilhar preview sem login |
| Publicação agendada | 🔵 Baixo | Ativar/desativar em data futura |
| Multi-idioma | 🔵 Baixo | Loja em vários idiomas |
| Analytics | 🔵 Baixo | Tracking de visitas/conversão |
| PWA offline page | 🔵 Baixo | Service Worker registra mas não tem offline fallback |
| A/B testing | 🔵 Baixo | Testes de variação de páginas |

---

## 5. Problemas Bloqueantes

### 5.1 Migration SQL Ausente

**Problema:** As 4 tabelas do site builder (`sb_pages`, `sb_sections`, `sb_components`, `sb_theme_settings`) não possuem arquivo de migration em `/sql/`. Podem existir no banco de testes, mas não há garantia de reprodutibilidade.

**Impacto:** Impossível fazer deploy em novo ambiente ou tenant.

**Solução:** Gerar migration usando skill `sql-migration` com as definições já presentes no código do Model.

### 5.2 Twig Engine Não Instalada

**Problema:** O diretório `loja/` contém 16 templates `.html.twig`, mas a dependência `twig/twig` não está em `composer.json` nem no `vendor/`.

**Impacto:** Templates Twig são inertes — não existe engine para renderizá-los.

**Solução — Duas opções:**

| Opção | Prós | Contras |
|-------|------|---------|
| **A. Instalar Twig** (`composer require twig/twig`) | Templates já escritos e bem feitos; engine madura; facilita manutenção | Nova dependência (~2MB); curva de aprendizado para devs PHP puro |
| **B. Converter para PHP puro** | Sem dependência extra; consistente com resto do sistema | Retrabalho de 16 templates; perde expressividade do Twig |

**Recomendação:** **Opção A** — instalar Twig. Os templates já estão prontos e são de alta qualidade. A engine é leve e amplamente usada.

### 5.3 Sem Controller/Roteamento Público

**Problema:** O preview funciona via iframe dentro do admin (`?page=site_builder&action=preview`), mas não existe rota pública para visitantes acessarem a loja.

**Impacto:** Impossível acessar a loja como cliente.

**Solução:** Criar `LojaController` + `loja/index.php` (ver seção 6).

---

## 6. Análise: index.php para a Loja

### 6.1 Por Que É Necessário

| Razão | Explicação |
|-------|------------|
| **Separação de domínio** | Permite apontar um domínio customizado (ex: `minhaloja.com.br`) para a pasta `loja/` |
| **Performance** | Entry point enxuto — não carrega menus, middleware de admin, verificação de login do ERP |
| **SEO** | URLs amigáveis (`/produtos`, `/contato`, `/sobre`) em vez de `?page=x&action=y` |
| **Escalabilidade** | Pode ser servido por CDN/proxy reverso independente do admin |
| **PWA** | Service Worker já existe — precisa de um HTML base para funcionar |

### 6.2 Arquitetura Proposta

```
┌──── Domínio Admin (admin.empresa.akti.com) ──────────┐
│   index.php → Application → Router → Controllers     │
│   Inclui: SiteBuilderController (editor)              │
└───────────────────────────────────────────────────────┘

┌──── Domínio Loja (minhaloja.com.br ou /loja) ────────┐
│   loja/index.php → LojaApplication (leve)             │
│   ├── Resolve tenant pelo domínio ou subdomínio       │
│   ├── Lê sb_pages/sb_sections/sb_theme_settings        │
│   ├── Renderiza com Twig                              │
│   └── URLs: /, /produtos, /produto/{slug}, /contato   │
└───────────────────────────────────────────────────────┘
```

### 6.3 Requisitos do `loja/index.php`

```
loja/
├── index.php              ← NOVO — Entry point da loja
├── .htaccess              ← NOVO — Rewrite rules para URLs amigáveis
├── app/
│   ├── LojaRouter.php     ← NOVO — Router simples para URLs da loja
│   └── LojaController.php ← NOVO — Controller de renderização de páginas
├── layouts/
├── templates/
├── config/
└── assets/
```

### 6.4 Fluxo do `loja/index.php`

```
1. Requisição: GET /produtos
2. loja/index.php:
   a. require '../app/bootstrap/autoload.php'  (reutiliza autoloader, tenant, DB)
   b. session_start()
   c. TenantManager::bootstrap() → resolve tenant pelo domínio
   d. Database::getInstance() → conexão PDO do tenant
   e. LojaRouter::resolve($_SERVER['REQUEST_URI'])
   f. LojaController::renderPage($slug, $db, $tenantId)
      - SiteBuilder::getFullPage() → dados do banco
      - SiteBuilder::getThemeSettings() → tema
      - Twig::render('pages/collection.html.twig', $data)
3. Resposta: HTML renderizado
```

### 6.5 .htaccess para URLs Amigáveis

```apache
RewriteEngine On
RewriteBase /loja/

# Arquivos estáticos passam direto
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Tudo o mais vai para index.php
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]
```

### 6.6 Cenários de Acesso

| Cenário | URL | Resolução de Tenant |
|---------|-----|---------------------|
| Subpasta do ERP | `empresa.akti.com/loja/` | Mesmo subdomínio → mesmo tenant |
| Subdomínio dedicado | `loja.empresa.akti.com` | Subdomínio → TenantManager |
| Domínio customizado | `www.minhaloja.com.br` | DNS apontado → tabela `tenant_domains` no master | 

**Nota sobre domínio customizado:** Para suportar domínio customizado no futuro, será necessário:
1. Tabela `tenant_domains` no `akti_master` mapeando domínio → tenant_id
2. `TenantManager` atualizado para consultar esta tabela como fallback
3. Configuração de virtual host ou proxy reverso (nginx/Apache) apontando para `loja/`

---

## 7. Plano de Implementação

### 7.1 Prioridade de Implementação

```
FASE 1 — Fundação (Bloqueantes)
├── 1.1 Gerar migration SQL para as 4 tabelas sb_*
├── 1.2 Instalar twig/twig via Composer
├── 1.3 Criar loja/index.php (entry point)
├── 1.4 Criar LojaRouter (roteamento URL amigável)
├── 1.5 Criar LojaController (renderização Twig)
└── 1.6 Criar loja/.htaccess (rewrite rules)

FASE 2 — Vitrine Básica
├── 2.1 Renderização de home page com seções dinâmicas
├── 2.2 Renderização de páginas customizadas por slug
├── 2.3 Catálogo de produtos (listagem com paginação)
├── 2.4 Página de produto individual
├── 2.5 Página de contato funcional (envio de email)
├── 2.6 Upload de imagens nas seções e tema
└── 2.7 Injeção de meta tags SEO no <head>

FASE 3 — E-commerce Básico
├── 3.1 Carrinho de compras (sessão)
├── 3.2 Checkout simples
├── 3.3 Integração com gateway de pagamento existente
├── 3.4 Confirmação de pedido
├── 3.5 Gestão de pedidos da loja no admin
└── 3.6 Notificação por email ao cliente e admin

FASE 4 — Funcionalidades Avançadas
├── 4.1 Conta do cliente (registro, login, pedidos)
├── 4.2 Busca de produtos
├── 4.3 Filtros (categoria, preço, etc.)
├── 4.4 Suporte a domínio customizado (tenant_domains)
├── 4.5 Newsletter funcional
├── 4.6 PWA offline page
└── 4.7 Analytics básico
```

### 7.2 Detalhamento — FASE 1

#### 1.1 Migration SQL

Gerar arquivo via skill `sql-migration` com:

```sql
-- sb_pages
CREATE TABLE IF NOT EXISTS sb_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    type ENUM('home','about','products','services','contact','blog','custom') DEFAULT 'custom',
    meta_title VARCHAR(200) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_slug (tenant_id, slug),
    KEY idx_tenant_active (tenant_id, is_active),
    FOREIGN KEY (tenant_id) REFERENCES akti_master.tenant_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sb_sections
CREATE TABLE IF NOT EXISTS sb_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    page_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    settings JSON DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_page_sort (page_id, sort_order),
    FOREIGN KEY (page_id) REFERENCES sb_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES akti_master.tenant_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sb_components
CREATE TABLE IF NOT EXISTS sb_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    section_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    content JSON DEFAULT NULL,
    grid_col INT DEFAULT 12,
    grid_row INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_section_sort (section_id, sort_order),
    FOREIGN KEY (section_id) REFERENCES sb_sections(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES akti_master.tenant_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sb_theme_settings
CREATE TABLE IF NOT EXISTS sb_theme_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_key (tenant_id, setting_key),
    KEY idx_tenant_group (tenant_id, setting_group),
    FOREIGN KEY (tenant_id) REFERENCES akti_master.tenant_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 1.2 Instalar Twig

```bash
composer require twig/twig
```

#### 1.3–1.6 Criar Entry Point da Loja

**Arquivos a criar:**

| Arquivo | Responsabilidade |
|---------|------------------|
| `loja/index.php` | Bootstrap → Tenant → Router → Render |
| `loja/.htaccess` | Rewrite URLs para index.php |
| `app/controllers/LojaController.php` | Renderiza páginas da loja via Twig |
| `app/services/TwigRenderer.php` | Wrapper do Twig com caminhos de template |

**Exemplo `loja/index.php`:**

```php
<?php
// Entry point da loja pública
require_once __DIR__ . '/../app/bootstrap/autoload.php';

use Akti\Config\Database;
use Akti\Config\TenantManager;
use Akti\Controllers\LojaController;
use Akti\Models\SiteBuilder;
use Akti\Models\Product;

session_start();

// Resolver tenant
TenantManager::bootstrap();
$db = Database::getInstance();

$tenantId = (int) ($_SESSION['tenant']['id'] ?? 0);
if ($tenantId <= 0) {
    http_response_code(404);
    echo 'Loja não encontrada';
    exit;
}

// Router simples baseado em URL
$route = trim($_GET['route'] ?? '', '/');
$segments = $route ? explode('/', $route) : [];

$siteBuilder = new SiteBuilder($db);
$controller = new LojaController($db, $siteBuilder, $tenantId);

// Mapear rotas
match (true) {
    $route === '' || $route === 'home'
        => $controller->home(),
    $route === 'produtos'
        => $controller->collection(),
    str_starts_with($route, 'produto/')
        => $controller->product($segments[1] ?? ''),
    $route === 'carrinho'
        => $controller->cart(),
    $route === 'contato'
        => $controller->contact(),
    default
        => $controller->page($route),
};
```

---

## 8. Roadmap de Fases

### Fase 1 — Fundação (Estimativa: sprint 1)

| # | Tarefa | Complexidade | Depende de |
|---|--------|-------------|------------|
| 1.1 | Gerar migration SQL | Baixa | — |
| 1.2 | `composer require twig/twig` | Baixa | — |
| 1.3 | Criar `loja/index.php` | Média | 1.2 |
| 1.4 | Criar `loja/.htaccess` | Baixa | 1.3 |
| 1.5 | Criar `LojaController.php` | Média | 1.2, 1.3 |
| 1.6 | Criar `TwigRenderer.php` (service) | Média | 1.2 |
| 1.7 | Testar renderização home page | Baixa | 1.1–1.6 |

### Fase 2 — Vitrine (Estimativa: sprint 2)

| # | Tarefa | Complexidade | Depende de |
|---|--------|-------------|------------|
| 2.1 | Home page com seções dinâmicas | Média | Fase 1 |
| 2.2 | Páginas customizadas por slug | Baixa | Fase 1 |
| 2.3 | Catálogo de produtos (paginação) | Média | Fase 1 |
| 2.4 | Página de produto individual | Média | Fase 1 |
| 2.5 | Contato com envio de email | Baixa | Fase 1 |
| 2.6 | Upload de imagens no site builder | Alta | Fase 1 |
| 2.7 | Meta tags SEO dinâmicas | Baixa | Fase 1 |

### Fase 3 — E-commerce (Estimativa: sprint 3-4)

| # | Tarefa | Complexidade | Depende de |
|---|--------|-------------|------------|
| 3.1 | Carrinho de compras (sessão) | Alta | Fase 2 |
| 3.2 | Checkout simples | Alta | 3.1 |
| 3.3 | Gateway de pagamento | Alta | 3.2 |
| 3.4 | Gestão de pedidos no admin | Alta | 3.3 |
| 3.5 | Notificações por email | Média | 3.4 |

### Fase 4 — Avançado (Estimativa: sprint 5+)

| # | Tarefa | Complexidade | Depende de |
|---|--------|-------------|------------|
| 4.1 | Conta do cliente (registro/login) | Alta | Fase 3 |
| 4.2 | Busca de produtos | Média | Fase 2 |
| 4.3 | Filtros (categoria, preço) | Média | 4.2 |
| 4.4 | Tabela `tenant_domains` + resolução | Alta | Fase 1 |
| 4.5 | Newsletter funcional | Média | Fase 2 |
| 4.6 | PWA offline page | Baixa | Fase 1 |
| 4.7 | Analytics | Média | Fase 2 |

---

## Anexo A — Mapeamento de Tipos de Seção

| Tipo | Template Twig | Preview PHP | Configurações (JSON) |
|------|---------------|-------------|---------------------|
| hero-banner | ✅ | ✅ | title, subtitle, button_text, button_url, bg_image, bg_color, overlay_opacity |
| featured-products | ✅ | ✅ | title, count (4-12), columns (2-4) |
| image-with-text | ✅ | ✅ | image, title, text, button_text, button_url, image_position (left/right) |
| gallery | ✅ | ✅ | title, images[], columns (2-4) |
| newsletter | ✅ | ✅ | title, description, button_text |
| testimonials | ✅ | ✅ | title, items[{quote, name, role, photo}] |
| custom-html | ✅ | ✅ | html (sanitizado) |
| header | ✅ | ✅ | via theme_settings (style, logo, position, colors, sticky) |
| footer | ✅ | ✅ | via theme_settings (style, columns, colors) |

## Anexo B — Mapeamento de Tipos de Componente

| Tipo | Editor | Preview | Configurações (JSON) |
|------|--------|---------|---------------------|
| rich-text | ✅ | ✅ | html |
| image | ✅ | ✅ | src, alt, link |
| button | ✅ | ✅ | text, url, style (primary/secondary/outline) |
| spacer | ✅ | ✅ | height (px) |
| divider | ✅ | ✅ | — |
| custom-html | ✅ | ✅ | html (sanitizado) |
| product-grid | ✅ | ✅ | count, columns |
| product-carousel | ✅ | ✅ | count |

## Anexo C — Schema de Tema Completo

| Grupo | Setting Key | Tipo | Default |
|-------|-------------|------|---------|
| Cores | primary_color | color | #3b82f6 |
| Cores | secondary_color | color | #64748b |
| Cores | accent_color | color | #f59e0b |
| Tipografia | body_font | font_picker | Inter |
| Tipografia | heading_font | font_picker | Inter |
| Cabeçalho | header_style | select | default |
| Cabeçalho | header_logo | image_picker | — |
| Cabeçalho | header_logo_position | select | left |
| Cabeçalho | header_bg_color | color | #ffffff |
| Cabeçalho | header_text_color | color | #333333 |
| Cabeçalho | header_sticky | checkbox | true |
| Rodapé | footer_style | select | default |
| Rodapé | footer_columns | select | 3 |
| Rodapé | footer_bg_color | color | #2c3e50 |
| Rodapé | footer_text_color | color | #ffffff |
