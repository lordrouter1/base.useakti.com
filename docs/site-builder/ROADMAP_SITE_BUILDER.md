# 🏪 Akti Site Builder — Roadmap de Implementação

> **Criado em:** 01/04/2026  
> **Versão do sistema:** Akti - Gestão em Produção (Multi-Tenant)  
> **Inspiração:** Shopify Theme Architecture, Wix Editor, WordPress Customizer  
> **Motor de Templates:** Twig (PHP)

---

## 📋 Sumário

1. [Visão Geral](#1-visão-geral)
2. [Arquitetura do Sistema](#2-arquitetura-do-sistema)
3. [Estrutura da Pasta `/loja`](#3-estrutura-da-pasta-loja)
4. [Motor de Templates (Twig)](#4-motor-de-templates-twig)
5. [Banco de Dados](#5-banco-de-dados)
6. [Backend — Controller & Model](#6-backend--controller--model)
7. [Site Builder — Interface de Edição](#7-site-builder--interface-de-edição)
8. [Componentes Disponíveis](#8-componentes-disponíveis)
9. [Sistema de Temas](#9-sistema-de-temas)
10. [API de Preview em Tempo Real](#10-api-de-preview-em-tempo-real)
11. [Fases de Implementação](#11-fases-de-implementação)
12. [Referências](#12-referências)

---

## 1. Visão Geral

O **Akti Site Builder** é um módulo integrado ao sistema Akti que permite aos tenants
criarem e personalizarem a vitrine/loja online de seus produtos. Inspirado na
arquitetura de temas do Shopify, o sistema utiliza o motor de templates **Twig** para
renderizar as páginas da loja, enquanto oferece uma interface visual de edição
drag & drop no painel administrativo.

### Objetivos Principais

| Objetivo | Descrição |
|----------|-----------|
| **Personalização visual** | O usuário configura header, footer, páginas e seções sem precisar escrever código |
| **Drag & Drop** | Interface de grid com arrastar e soltar componentes (banners, produtos, texto, vídeo etc.) |
| **Preview em tempo real** | Painel dividido: editor à esquerda, preview ao vivo à direita via iframe |
| **Templates Twig** | Todos os templates da loja ficam em `/loja` usando Twig, separando lógica de apresentação |
| **Multi-Tenant** | Cada tenant tem suas próprias configurações de tema, páginas e componentes |
| **Responsivo** | A loja gerada é responsiva por padrão (Bootstrap 5 no tema base) |

---

## 2. Arquitetura do Sistema

```
┌────────────────────────────────────────────────────────────────┐
│                      PAINEL ADMIN (Akti)                       │
│  ┌──────────────────────┐   ┌───────────────────────────────┐  │
│  │   EDITOR (esquerda)  │   │     PREVIEW (direita)         │  │
│  │                      │   │                               │  │
│  │  ┌────────────────┐  │   │   ┌───────────────────────┐   │  │
│  │  │ Configurações  │  │   │   │   <iframe>            │   │  │
│  │  │  · Header      │  │   │   │                       │   │  │
│  │  │  · Footer      │  │   │   │   Renderização Twig   │   │  │
│  │  │  · Páginas     │  │   │   │   em tempo real       │   │  │
│  │  │  · Colunas     │  │   │   │                       │   │  │
│  │  └────────────────┘  │   │   └───────────────────────┘   │  │
│  │                      │   │                               │  │
│  │  ┌────────────────┐  │   │   Atualiza via POST AJAX     │  │
│  │  │ Grid D&D       │  │   │   ao mover/editar componente │  │
│  │  │  ┌──┐ ┌──┐     │  │   │                               │  │
│  │  │  │▦ │ │▦ │     │  │   └───────────────────────────────┘  │
│  │  │  └──┘ └──┘     │  │                                      │
│  │  │  ┌────────┐    │  │                                      │
│  │  │  │  ▦▦▦▦  │    │  │                                      │
│  │  │  └────────┘    │  │                                      │
│  │  └────────────────┘  │                                      │
│  └──────────────────────┘                                      │
└────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌────────────────────────────────────────────────────────────────┐
│                     PASTA /loja (Twig)                         │
│                                                                │
│  layouts/       → Layouts base (base.html.twig)                │
│  templates/                                                    │
│    pages/       → Templates de páginas (home, produto, etc.)   │
│    sections/    → Seções reutilizáveis (banner, grid, etc.)    │
│    snippets/    → Fragmentos (card de produto, breadcrumb)     │
│  assets/        → CSS, JS e imagens do tema                    │
│  config/        → Configuração padrão do tema (JSON)           │
└────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌────────────────────────────────────────────────────────────────┐
│                    BANCO DE DADOS                              │
│                                                                │
│  sb_pages          → Páginas criadas pelo tenant               │
│  sb_sections       → Seções de cada página (com posição)       │
│  sb_components     → Componentes dentro de cada seção          │
│  sb_theme_settings → Configurações globais do tema             │
└────────────────────────────────────────────────────────────────┘
```

### Fluxo de Requisição da Loja Pública

```
1. Requisição: GET /loja/home (ou subdomínio tenant)
2. Router identifica a rota de loja
3. SiteBuilderController carrega configurações do tenant (DB)
4. Twig renderiza: layout + seções + componentes + dados de produtos
5. HTML final é servido ao visitante
```

---

## 3. Estrutura da Pasta `/loja`

A pasta `/loja` na raiz do projeto contém todos os arquivos de template da loja.
Esta estrutura é inspirada na organização de temas do Shopify.

```
/loja
├── layouts/
│   └── base.html.twig          # Layout principal (HTML shell, <head>, scripts)
│
├── templates/
│   ├── pages/
│   │   ├── home.html.twig      # Template da página inicial
│   │   ├── product.html.twig   # Template de página de produto
│   │   ├── collection.html.twig # Template de listagem/coleção
│   │   ├── cart.html.twig      # Template do carrinho
│   │   ├── contact.html.twig   # Template de contato
│   │   └── custom.html.twig    # Template genérico para páginas customizadas
│   │
│   ├── sections/
│   │   ├── header.html.twig    # Seção de cabeçalho
│   │   ├── footer.html.twig    # Seção de rodapé
│   │   ├── hero-banner.html.twig   # Banner principal (hero)
│   │   ├── featured-products.html.twig  # Produtos em destaque
│   │   ├── image-with-text.html.twig    # Imagem + texto lado a lado
│   │   ├── newsletter.html.twig    # Formulário de newsletter
│   │   ├── testimonials.html.twig  # Depoimentos
│   │   ├── gallery.html.twig       # Galeria de imagens
│   │   └── custom-html.html.twig   # HTML customizado
│   │
│   └── snippets/
│       ├── product-card.html.twig  # Card de produto reutilizável
│       ├── pagination.html.twig    # Paginação
│       ├── breadcrumb.html.twig    # Breadcrumb
│       ├── social-icons.html.twig  # Ícones de redes sociais
│       └── price.html.twig         # Formatação de preço
│
├── assets/
│   ├── css/
│   │   └── theme.css               # Estilos do tema
│   ├── js/
│   │   └── theme.js                # Scripts do tema
│   └── images/
│       └── placeholder.svg          # Imagem placeholder
│
└── config/
    └── settings_schema.json         # Schema de configurações do tema
```

### Convenções de Nomenclatura

| Convenção | Exemplo | Descrição |
|-----------|---------|-----------|
| **Layouts** | `base.html.twig` | Sempre em `layouts/`, um por tema |
| **Pages** | `home.html.twig` | Um template por tipo de página |
| **Sections** | `hero-banner.html.twig` | Kebab-case, descritivo |
| **Snippets** | `product-card.html.twig` | Fragmentos incluídos via `{% include %}` |
| **Config** | `settings_schema.json` | JSON Schema para configurações |

---

## 4. Motor de Templates (Twig)

### Por que Twig?

| Vantagem | Descrição |
|----------|-----------|
| **Segurança** | Auto-escape de variáveis por padrão (previne XSS) |
| **Sandbox** | Modo sandbox permite restringir tags/filtros disponíveis |
| **Performance** | Compilação para PHP nativo com cache |
| **Herança** | Sistema robusto de herança de templates (`extends`, `block`) |
| **Sintaxe limpa** | Fácil para designers e não-programadores |
| **Comunidade** | Amplamente utilizado (Symfony, Drupal, etc.) |

### Instalação

```bash
composer require twig/twig ^3.0
```

### Exemplo de Uso

**Layout base** (`loja/layouts/base.html.twig`):
```twig
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}{{ shop.name }}{% endblock %}</title>
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    {% block head_extra %}{% endblock %}
</head>
<body>
    {% include 'sections/header.html.twig' %}

    <main>
        {% block content %}{% endblock %}
    </main>

    {% include 'sections/footer.html.twig' %}

    <script src="{{ asset('js/theme.js') }}"></script>
    {% block scripts %}{% endblock %}
</body>
</html>
```

**Página home** (`loja/templates/pages/home.html.twig`):
```twig
{% extends 'layouts/base.html.twig' %}

{% block title %}{{ shop.name }} — Página Inicial{% endblock %}

{% block content %}
    {% for section in page.sections %}
        {% include 'sections/' ~ section.type ~ '.html.twig' with {
            'settings': section.settings,
            'data': section.data
        } %}
    {% endfor %}
{% endblock %}
```

**Seção de produtos** (`loja/templates/sections/featured-products.html.twig`):
```twig
<section class="featured-products py-5">
    <div class="container">
        <h2 class="text-center mb-4">{{ settings.title|default('Produtos em Destaque') }}</h2>
        <div class="row row-cols-1 row-cols-md-{{ settings.columns|default(3) }} g-4">
            {% for product in data.products %}
                <div class="col">
                    {% include 'snippets/product-card.html.twig' with {'product': product} %}
                </div>
            {% endfor %}
        </div>
    </div>
</section>
```

### Variáveis Globais Disponíveis nos Templates

| Variável | Tipo | Descrição |
|----------|------|-----------|
| `shop` | object | Dados da loja (name, logo, description, etc.) |
| `page` | object | Página atual (title, sections, slug, etc.) |
| `theme` | object | Configurações do tema (cores, fontes, etc.) |
| `cart` | object | Carrinho atual do visitante |
| `menu` | array | Itens do menu de navegação |
| `request` | object | Dados da requisição atual |

---

## 5. Banco de Dados

### Tabelas Necessárias

#### `sb_pages` — Páginas da loja

```sql
CREATE TABLE sb_pages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT NOT NULL,
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL,
    type        ENUM('home','product','collection','cart','contact','custom') DEFAULT 'custom',
    meta_title  VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    sort_order  INT DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_slug (tenant_id, slug),
    INDEX idx_tenant_active (tenant_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `sb_sections` — Seções de cada página

```sql
CREATE TABLE sb_sections (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT NOT NULL,
    page_id     INT NOT NULL,
    type        VARCHAR(100) NOT NULL COMMENT 'Tipo da seção (hero-banner, featured-products, etc.)',
    settings    JSON DEFAULT NULL COMMENT 'Configurações da seção (título, colunas, cores, etc.)',
    sort_order  INT DEFAULT 0,
    is_visible  TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_page_order (page_id, sort_order),
    CONSTRAINT fk_section_page FOREIGN KEY (page_id) REFERENCES sb_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `sb_components` — Componentes dentro de seções

```sql
CREATE TABLE sb_components (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT NOT NULL,
    section_id  INT NOT NULL,
    type        VARCHAR(100) NOT NULL COMMENT 'Tipo do componente (text, image, button, product-grid, etc.)',
    content     JSON DEFAULT NULL COMMENT 'Conteúdo e configurações do componente',
    grid_col    INT DEFAULT 12 COMMENT 'Largura no grid (1-12, padrão full-width)',
    grid_row    INT DEFAULT 0 COMMENT 'Posição na linha do grid',
    sort_order  INT DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section_order (section_id, sort_order),
    CONSTRAINT fk_component_section FOREIGN KEY (section_id) REFERENCES sb_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `sb_theme_settings` — Configurações globais do tema

```sql
CREATE TABLE sb_theme_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general' COMMENT 'Grupo: general, header, footer, colors, fonts, etc.',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_key (tenant_id, setting_key),
    INDEX idx_tenant_group (tenant_id, setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Dados Iniciais (Seed)

```sql
-- Configurações padrão de tema para novo tenant
INSERT INTO sb_theme_settings (tenant_id, setting_key, setting_value, setting_group) VALUES
('{TENANT_ID}', 'header_style', 'default', 'header'),
('{TENANT_ID}', 'header_bg_color', '#ffffff', 'header'),
('{TENANT_ID}', 'header_text_color', '#333333', 'header'),
('{TENANT_ID}', 'header_logo_position', 'left', 'header'),
('{TENANT_ID}', 'header_sticky', '1', 'header'),
('{TENANT_ID}', 'footer_style', 'default', 'footer'),
('{TENANT_ID}', 'footer_bg_color', '#2c3e50', 'footer'),
('{TENANT_ID}', 'footer_text_color', '#ffffff', 'footer'),
('{TENANT_ID}', 'footer_columns', '3', 'footer'),
('{TENANT_ID}', 'primary_color', '#3b82f6', 'colors'),
('{TENANT_ID}', 'secondary_color', '#64748b', 'colors'),
('{TENANT_ID}', 'accent_color', '#f59e0b', 'colors'),
('{TENANT_ID}', 'body_font', 'Inter', 'fonts'),
('{TENANT_ID}', 'heading_font', 'Inter', 'fonts');
```

---

## 6. Backend — Controller & Model

### SiteBuilderController

**Arquivo:** `app/controllers/SiteBuilderController.php`

**Actions disponíveis:**

| Action | Método HTTP | Descrição |
|--------|-------------|-----------|
| `index` | GET | Dashboard do Site Builder (editor visual) |
| `pages` | GET | Listagem de páginas da loja |
| `createPage` | POST | Criar nova página |
| `updatePage` | POST | Atualizar página existente |
| `deletePage` | POST | Excluir página |
| `saveSections` | POST (AJAX) | Salvar layout de seções (ordem, configurações) |
| `addComponent` | POST (AJAX) | Adicionar componente a uma seção |
| `updateComponent` | POST (AJAX) | Atualizar conteúdo/posição de um componente |
| `removeComponent` | POST (AJAX) | Remover componente de uma seção |
| `saveThemeSettings` | POST (AJAX) | Salvar configurações globais do tema |
| `preview` | GET | Renderizar preview da loja (usado no iframe) |

### SiteBuilder Model

**Arquivo:** `app/models/SiteBuilder.php`

**Métodos do Model:**

| Método | Descrição |
|--------|-----------|
| `getPages($tenantId)` | Listar todas as páginas do tenant |
| `getPage($id, $tenantId)` | Obter uma página específica |
| `createPage($data)` | Criar nova página |
| `updatePage($id, $data)` | Atualizar página |
| `deletePage($id, $tenantId)` | Excluir página |
| `getSections($pageId)` | Listar seções de uma página (ordenadas) |
| `saveSection($data)` | Criar/atualizar seção |
| `deleteSection($id)` | Excluir seção |
| `getComponents($sectionId)` | Listar componentes de uma seção |
| `saveComponent($data)` | Criar/atualizar componente |
| `deleteComponent($id)` | Excluir componente |
| `getThemeSettings($tenantId)` | Obter todas configurações do tema |
| `saveThemeSetting($tenantId, $key, $value, $group)` | Salvar configuração |

---

## 7. Site Builder — Interface de Edição

### Layout da Interface

A interface do Site Builder segue o padrão split-view:

```
┌────────────────────────────────────────────────────────────────────┐
│ 🔧 Site Builder — Loja        [Páginas ▼]  [💾 Salvar] [👁 Ver]  │
├──────────────────────────┬─────────────────────────────────────────┤
│                          │                                         │
│   PAINEL DE EDIÇÃO       │         PREVIEW (iframe)               │
│   (col-md-4)             │         (col-md-8)                     │
│                          │                                         │
│   ┌──────────────────┐   │   ┌─────────────────────────────────┐  │
│   │ ⚙ Configurações  │   │   │                                 │  │
│   │                  │   │   │    Renderização em tempo real    │  │
│   │ Header:          │   │   │    da loja usando Twig           │  │
│   │  · Estilo  [▼]  │   │   │                                 │  │
│   │  · Logo    [◻]  │   │   │    ┌─────────────────────────┐  │  │
│   │  · Cor bg  [🎨] │   │   │    │  HEADER                 │  │  │
│   │                  │   │   │    ├─────────────────────────┤  │  │
│   │ Footer:          │   │   │    │                         │  │  │
│   │  · Colunas [▼]  │   │   │    │   CONTEÚDO DA PÁGINA    │  │  │
│   │  · Cor bg  [🎨] │   │   │    │                         │  │  │
│   │  · Links  [✏]  │   │   │    │  ┌──────┐ ┌──────┐     │  │  │
│   └──────────────────┘   │   │    │  │ Comp │ │ Comp │     │  │  │
│                          │   │    │  └──────┘ └──────┘     │  │  │
│   ┌──────────────────┐   │   │    │                         │  │  │
│   │ 📦 Componentes   │   │   │    ├─────────────────────────┤  │  │
│   │                  │   │   │    │  FOOTER                 │  │  │
│   │ [▦ Texto    ]    │   │   │    └─────────────────────────┘  │  │
│   │ [🖼 Imagem  ]    │   │   │                                 │  │
│   │ [🛍 Produtos]    │   │   └─────────────────────────────────┘  │
│   │ [📹 Vídeo   ]    │   │                                         │
│   │ [🔘 Botão   ]    │   │   Responsivo: [📱] [💻] [🖥]           │
│   │ [📊 Colunas ]    │   │                                         │
│   └──────────────────┘   │                                         │
│                          │                                         │
│   ┌──────────────────┐   │                                         │
│   │ 🗂 Seções da     │   │                                         │
│   │    Página        │   │                                         │
│   │                  │   │                                         │
│   │ ≡ Hero Banner    │   │                                         │
│   │ ≡ Produtos Dest. │   │                                         │
│   │ ≡ Texto + Imagem │   │                                         │
│   │ ≡ Newsletter     │   │                                         │
│   │                  │   │                                         │
│   │ [+ Adicionar]    │   │                                         │
│   └──────────────────┘   │                                         │
│                          │                                         │
├──────────────────────────┴─────────────────────────────────────────┤
│  Seção selecionada: Hero Banner                    [🗑 Excluir]   │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  Grid Drag & Drop (12 colunas Bootstrap)                    │  │
│  │  ┌─────────────────────────────┬────────────────────────┐   │  │
│  │  │ Componente A (col-8)        │ Componente B (col-4)   │   │  │
│  │  │ [Arrastar para reordenar]   │ [Arrastar]             │   │  │
│  │  └─────────────────────────────┴────────────────────────┘   │  │
│  │  ┌──────────────────────────────────────────────────────┐   │  │
│  │  │ Componente C (col-12)                                │   │  │
│  │  │ [Arrastar para reordenar]                            │   │  │
│  │  └──────────────────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────┘
```

### Funcionalidades do Editor

#### 7.1 Gerenciamento de Páginas

- **Criar/Editar/Excluir** páginas com título, slug, tipo e metadados SEO
- **Tipos de página:** Home, Produto, Coleção, Carrinho, Contato, Customizada
- **Ativar/Desativar** páginas
- **Reordenar** páginas (afeta o menu de navegação da loja)

#### 7.2 Configuração do Header

| Propriedade | Opções | Descrição |
|-------------|--------|-----------|
| **Estilo** | `default`, `centered`, `minimal`, `mega-menu` | Layout do cabeçalho |
| **Logo** | Upload de imagem | Logo da loja |
| **Posição do logo** | `left`, `center`, `right` | Alinhamento do logo |
| **Cor de fundo** | Color picker | Cor de fundo do header |
| **Cor do texto** | Color picker | Cor do texto/links |
| **Sticky** | Toggle | Fixa o header no topo ao rolar |
| **Menu items** | Editor de lista | Links do menu principal |

#### 7.3 Configuração do Footer

| Propriedade | Opções | Descrição |
|-------------|--------|-----------|
| **Estilo** | `default`, `minimal`, `expanded` | Layout do rodapé |
| **Colunas** | `1`, `2`, `3`, `4` | Quantidade de colunas |
| **Cor de fundo** | Color picker | Cor de fundo |
| **Cor do texto** | Color picker | Cor do texto |
| **Redes sociais** | Editor de links | URLs das redes sociais |
| **Texto de copyright** | Input text | Texto legal do rodapé |

#### 7.4 Grid Drag & Drop

O sistema de grid utiliza o sistema de 12 colunas do Bootstrap:

- **Arrastar componentes** do painel lateral para o grid
- **Redimensionar** colunas arrastando as bordas (col-1 a col-12)
- **Reordenar** componentes arrastando para cima/baixo
- **Editar** clicando no componente (abre painel de propriedades)
- **Excluir** componente com botão de lixeira
- **Duplicar** componente com botão de cópia

**Biblioteca sugerida:** [SortableJS](https://sortablejs.github.io/Sortable/) (já disponível no projeto como CDN)

#### 7.5 Preview em Tempo Real

O painel de preview (lado direito) é um **iframe** que carrega a rota `?page=site_builder&action=preview`:

1. Ao arrastar/soltar um componente → AJAX salva a posição → iframe recarrega
2. Ao editar propriedades → AJAX salva → iframe recarrega
3. **Botões de viewport:** Simula dispositivos (mobile 375px, tablet 768px, desktop 1200px)
4. **Debounce:** Atualizações de preview são debounced (300ms) para evitar excesso de requests

---

## 8. Componentes Disponíveis

### Fase 1 — Componentes Básicos

| Componente | Tipo | Propriedades |
|------------|------|-------------|
| **Texto Rico** | `rich-text` | Conteúdo HTML (editor WYSIWYG), alinhamento, cor |
| **Imagem** | `image` | URL, alt, largura, link, bordas |
| **Botão** | `button` | Texto, URL, estilo (primary/secondary/outline), tamanho |
| **Espaçador** | `spacer` | Altura em px |
| **Divisor** | `divider` | Estilo (sólido/pontilhado/tracejado), cor |
| **HTML Customizado** | `custom-html` | Código HTML livre (com sanitização) |

### Fase 2 — Componentes de Produto

| Componente | Tipo | Propriedades |
|------------|------|-------------|
| **Grid de Produtos** | `product-grid` | Categoria, quantidade, colunas (2/3/4), ordenação |
| **Produto em Destaque** | `featured-product` | ID do produto, layout (horizontal/vertical) |
| **Carrossel de Produtos** | `product-carousel` | Categoria, quantidade, autoplay, velocidade |

### Fase 3 — Componentes Avançados

| Componente | Tipo | Propriedades |
|------------|------|-------------|
| **Banner/Hero** | `hero-banner` | Imagem de fundo, título, subtítulo, CTA, overlay |
| **Slideshow** | `slideshow` | Slides (imagem + texto + link), autoplay, intervalo |
| **Vídeo** | `video` | URL (YouTube/Vimeo), autoplay, poster |
| **Mapa** | `map` | Endereço ou coordenadas, zoom, estilo |
| **Formulário de Contato** | `contact-form` | Campos, email destino, mensagem de sucesso |
| **Newsletter** | `newsletter` | Título, descrição, campo de email |
| **Depoimentos** | `testimonials` | Lista de depoimentos (nome, texto, foto) |
| **FAQ/Accordion** | `faq` | Lista de perguntas e respostas |
| **Galeria** | `gallery` | Imagens, layout (grid/masonry), lightbox |
| **Contagem Regressiva** | `countdown` | Data alvo, formato, texto complementar |
| **Ícones/Features** | `icon-features` | Lista de ícones + título + descrição, colunas |

### Schema de Componente (JSON)

Cada componente possui um schema que define suas propriedades editáveis:

```json
{
    "type": "product-grid",
    "name": "Grid de Produtos",
    "icon": "fas fa-th",
    "category": "products",
    "properties": {
        "title": {
            "type": "text",
            "label": "Título",
            "default": "Nossos Produtos"
        },
        "columns": {
            "type": "select",
            "label": "Colunas",
            "options": [2, 3, 4],
            "default": 3
        },
        "limit": {
            "type": "number",
            "label": "Quantidade",
            "min": 1,
            "max": 24,
            "default": 6
        },
        "category_id": {
            "type": "category-picker",
            "label": "Categoria",
            "default": null
        },
        "show_price": {
            "type": "toggle",
            "label": "Mostrar preço",
            "default": true
        }
    }
}
```

---

## 9. Sistema de Temas

### Estrutura de Tema

Cada tema é um conjunto de templates Twig + assets + configurações:

```
/loja
├── config/
│   └── settings_schema.json    # Define todas as opções editáveis do tema
```

### Settings Schema

O `settings_schema.json` define os campos de configuração exibidos no painel:

```json
[
    {
        "name": "Cores",
        "settings": [
            {
                "type": "color",
                "id": "primary_color",
                "label": "Cor primária",
                "default": "#3b82f6"
            },
            {
                "type": "color",
                "id": "secondary_color",
                "label": "Cor secundária",
                "default": "#64748b"
            }
        ]
    },
    {
        "name": "Tipografia",
        "settings": [
            {
                "type": "font_picker",
                "id": "body_font",
                "label": "Fonte do corpo",
                "default": "Inter"
            },
            {
                "type": "range",
                "id": "body_font_size",
                "label": "Tamanho da fonte",
                "min": 12,
                "max": 20,
                "step": 1,
                "default": 16,
                "unit": "px"
            }
        ]
    },
    {
        "name": "Cabeçalho",
        "settings": [
            {
                "type": "select",
                "id": "header_style",
                "label": "Estilo do cabeçalho",
                "options": [
                    {"value": "default", "label": "Padrão"},
                    {"value": "centered", "label": "Centralizado"},
                    {"value": "minimal", "label": "Minimalista"}
                ],
                "default": "default"
            },
            {
                "type": "image_picker",
                "id": "header_logo",
                "label": "Logo"
            }
        ]
    }
]
```

### Tipos de Campo Suportados

| Tipo | Widget | Descrição |
|------|--------|-----------|
| `text` | Input text | Texto simples |
| `textarea` | Textarea | Texto multilinha |
| `richtext` | Editor WYSIWYG | HTML rico |
| `number` | Input number | Número |
| `range` | Slider | Número com range visual |
| `select` | Dropdown | Seleção de opção |
| `checkbox` | Checkbox | Boolean |
| `color` | Color picker | Seletor de cor |
| `image_picker` | Upload/galeria | Seletor de imagem |
| `font_picker` | Dropdown de fontes | Google Fonts |
| `url` | Input URL | Link |
| `collection_picker` | Dropdown | Selecionar categoria/coleção de produtos |

---

## 10. API de Preview em Tempo Real

### Endpoints AJAX

Todos os endpoints AJAX do Site Builder seguem o padrão do sistema:

| Endpoint | Método | Corpo | Resposta |
|----------|--------|-------|----------|
| `?page=site_builder&action=saveSections` | POST | `{page_id, sections: [...]}` | `{success: true}` |
| `?page=site_builder&action=addComponent` | POST | `{section_id, type, content, grid_col}` | `{success: true, id: N}` |
| `?page=site_builder&action=updateComponent` | POST | `{id, content, grid_col, sort_order}` | `{success: true}` |
| `?page=site_builder&action=removeComponent` | POST | `{id}` | `{success: true}` |
| `?page=site_builder&action=saveThemeSettings` | POST | `{settings: {key: value, ...}}` | `{success: true}` |
| `?page=site_builder&action=preview` | GET | `?page_id=N` | HTML renderizado |

### Fluxo de Atualização do Preview

```
1. Usuário arrasta componente para o grid
2. JavaScript captura evento 'drop' do SortableJS
3. AJAX POST envia nova posição/dados para o backend
4. Backend salva no banco e retorna {success: true}
5. JavaScript atualiza o src do iframe de preview
6. Iframe recarrega com o template Twig atualizado
7. Usuário vê o resultado instantaneamente
```

### Comunicação Editor ↔ Preview

```javascript
// No editor (pai):
const previewFrame = document.getElementById('site-builder-preview');

function refreshPreview(pageId) {
    previewFrame.src = '?page=site_builder&action=preview&page_id=' + pageId
        + '&_t=' + Date.now(); // cache bust
}

// Debounce para não sobrecarregar
let previewTimeout;
function debouncedRefresh(pageId) {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(() => refreshPreview(pageId), 300);
}
```

---

## 11. Fases de Implementação

### 📊 Resumo das Fases

| Fase | Escopo | Prioridade | Estimativa |
|------|--------|------------|------------|
| **Fase 1** | Infraestrutura base | 🔴 Crítica | 2-3 sprints |
| **Fase 2** | Editor visual MVP | 🟠 Alta | 3-4 sprints |
| **Fase 3** | Componentes avançados | 🟡 Média | 2-3 sprints |
| **Fase 4** | Temas e marketplace | 🟢 Baixa | 3-4 sprints |
| **Fase 5** | Otimizações e extras | 🔵 Futura | Contínuo |

---

### Fase 1 — Infraestrutura Base 🔴

| # | Tarefa | Descrição | Dependência |
|---|--------|-----------|-------------|
| F1-01 | **Instalar Twig** | `composer require twig/twig ^3.0` e configurar loader | — |
| F1-02 | **Criar tabelas no banco** | Executar migrations: `sb_pages`, `sb_sections`, `sb_components`, `sb_theme_settings` | — |
| F1-03 | **Criar estrutura `/loja`** | Pastas e templates Twig base (layout, header, footer) | F1-01 |
| F1-04 | **Criar SiteBuilder Model** | CRUD de páginas, seções, componentes e configurações de tema | F1-02 |
| F1-05 | **Criar SiteBuilderController** | Actions básicas: index, pages, preview | F1-04 |
| F1-06 | **Registrar rotas e menu** | Adicionar em `routes.php` e `menu.php` | F1-05 |
| F1-07 | **Criar TwigService** | Serviço para inicializar Twig, registrar variáveis globais e renderizar | F1-01, F1-03 |
| F1-08 | **Renderização de preview** | Rota de preview que renderiza template Twig com dados do banco | F1-07, F1-04 |

### Fase 2 — Editor Visual MVP 🟠

| # | Tarefa | Descrição | Dependência |
|---|--------|-----------|-------------|
| F2-01 | **View do Site Builder** | Interface split-view (editor + preview iframe) | F1-06 |
| F2-02 | **Painel de configurações de tema** | Formulário para header, footer, cores, fontes | F1-04 |
| F2-03 | **CRUD de páginas na UI** | Modal para criar/editar/excluir páginas | F1-04 |
| F2-04 | **Listagem de seções** | Painel lateral listando seções da página com drag & drop para reordenar (SortableJS) | F2-01 |
| F2-05 | **Grid de componentes** | Grid drag & drop dentro de cada seção (sistema de 12 colunas) | F2-04 |
| F2-06 | **Componentes básicos** | Implementar: Texto, Imagem, Botão, Espaçador, Divisor, HTML Customizado | F2-05 |
| F2-07 | **Painel de propriedades** | Ao clicar em componente, abrir painel para editar propriedades | F2-06 |
| F2-08 | **Preview em tempo real** | Refresh do iframe ao salvar alterações (com debounce) | F1-08 |
| F2-09 | **Responsividade do preview** | Botões para simular viewport mobile/tablet/desktop | F2-08 |

### Fase 3 — Componentes de Produto 🟡

| # | Tarefa | Descrição | Dependência |
|---|--------|-----------|-------------|
| F3-01 | **Grid de Produtos** | Componente que puxa produtos do catálogo | F2-06 |
| F3-02 | **Produto em Destaque** | Componente para destacar um produto | F2-06 |
| F3-03 | **Carrossel de Produtos** | Slider de produtos (Swiper.js) | F2-06 |
| F3-04 | **Banner/Hero** | Componente de banner com imagem de fundo, CTA | F2-06 |
| F3-05 | **Slideshow** | Carrossel de banners | F2-06 |
| F3-06 | **Formulário de Contato** | Formulário com envio por email | F2-06 |
| F3-07 | **Newsletter** | Formulário de captura de email | F2-06 |
| F3-08 | **Depoimentos** | Lista de depoimentos com foto | F2-06 |
| F3-09 | **FAQ/Accordion** | Perguntas e respostas com accordion | F2-06 |

### Fase 4 — Temas e Marketplace 🟢

| # | Tarefa | Descrição | Dependência |
|---|--------|-----------|-------------|
| F4-01 | **Sistema de temas** | Permitir múltiplos temas em `/loja/themes/` | Fase 3 |
| F4-02 | **Importar/Exportar tema** | Exportar configurações como JSON + assets como ZIP | F4-01 |
| F4-03 | **Temas pré-construídos** | 3-5 temas prontos para diferentes segmentos | F4-01 |
| F4-04 | **Marketplace de temas** | Interface para selecionar e instalar temas | F4-03 |
| F4-05 | **Versionamento de tema** | Histórico de alterações com rollback | F4-01 |

### Fase 5 — Otimizações e Extras 🔵

| # | Tarefa | Descrição | Dependência |
|---|--------|-----------|-------------|
| F5-01 | **SEO avançado** | Meta tags, Open Graph, sitemap XML automático | Fase 2 |
| F5-02 | **PWA da loja** | Service worker, manifest.json para a loja do tenant | Fase 2 |
| F5-03 | **Analytics integrado** | Dashboard de visitas, cliques, conversões | Fase 3 |
| F5-04 | **A/B Testing** | Testes A/B de seções/componentes | Fase 3 |
| F5-05 | **CDN de assets** | Upload de imagens para CDN (S3/R2) | Fase 2 |
| F5-06 | **Cache de templates** | Cache Twig compilado + invalidação inteligente | Fase 2 |
| F5-07 | **Domínio customizado** | Suporte a domínio próprio para a loja do tenant | Fase 4 |
| F5-08 | **Multi-idioma** | i18n nos templates (Twig i18n extension) | Fase 4 |

---

## 12. Referências

### Arquitetura Inspirada em

| Plataforma | Referência |
|------------|------------|
| **Shopify** | [Theme Architecture](https://shopify.dev/docs/themes/architecture) — Estrutura de layouts, templates, sections, snippets |
| **Shopify** | [Online Store Editor](https://shopify.dev/docs/themes/tools/online-editor) — Editor visual com sections/blocks |
| **Wix** | [Editor X](https://www.wix.com/editorx) — Grid responsivo drag & drop |
| **WordPress** | [Gutenberg Editor](https://developer.wordpress.org/block-editor/) — Sistema de blocos |
| **Webflow** | [Designer](https://webflow.com/designer) — Editor visual com CSS controls |

### Bibliotecas Recomendadas

| Biblioteca | Uso | Licença |
|------------|-----|---------|
| [Twig 3.x](https://twig.symfony.com/) | Motor de templates | BSD-3 |
| [SortableJS](https://sortablejs.github.io/Sortable/) | Drag & Drop (já no projeto) | MIT |
| [GrapesJS](https://grapesjs.com/) | Editor visual alternativo (mais completo) | BSD-3 |
| [Spectrum](https://bgrins.github.io/spectrum/) | Color picker | MIT |
| [TinyMCE](https://www.tiny.cloud/) / [Quill](https://quilljs.com/) | Editor WYSIWYG | LGPL / BSD |
| [Swiper](https://swiperjs.com/) | Carrosséis/slideshows | MIT |

### Documentação Interna Relacionada

| Documento | Descrição |
|-----------|-----------|
| `docs/ROADMAP.md` | Roadmap geral do sistema Akti |
| `.github/instructions/architecture.md` | Padrões de arquitetura (PSR-4, Multi-Tenant) |
| `.github/instructions/upload.md` | Regras de upload de arquivos |
| `.github/instructions/Bootloader.md` | Carregamento de módulos |
| `app/config/routes.php` | Mapa de rotas do sistema |
| `app/config/menu.php` | Configuração do menu principal |
