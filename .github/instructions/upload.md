# Módulo de Gestão de Arquivos (FileManager)

---

## Sumário
- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [FileManager Service](#filemanager-service)
- [ThumbnailService](#thumbnailservice)
- [FileController](#filecontroller)
- [Helpers Globais](#helpers-globais)
- [Helpers JavaScript](#helpers-javascript)
- [Multi-Tenant](#multi-tenant)
- [Perfis de Módulo](#perfis-de-módulo)
- [Exibição de Arquivos nas Views](#exibição-de-arquivos-nas-views)
- [Regras Obrigatórias](#regras-obrigatórias)
- [Exceções](#exceções)

---

## Visão Geral

O sistema possui um módulo centralizado de gestão de arquivos (`Akti\Services\FileManager`) que gerencia **todos** os uploads, downloads, exclusões e geração de URLs para arquivos e imagens. Isso garante:

- Validação centralizada (MIME type via magic bytes, extensão, tamanho)
- Organização multi-tenant automática
- Geração de thumbnails sob demanda (`ThumbnailService`)
- Rastreamento de assets no banco (`file_assets`)
- Preparação para integração futura com storage externo (Cloudflare R2, S3)

---

## Arquitetura

```
FileManager (Service)         → Upload, delete, replace, URL generation
ThumbnailService (Service)    → Geração de thumbs via GD (sob demanda)
FileController (Controller)   → HTTP endpoints (serve, thumb, download, upload)
file_helper.php (Utils)       → Funções globais: file_url(), thumb_url()
footer.php JS helpers         → Funções JS: thumbUrl(), fileUrl()
```

---

## FileManager Service

**Classe:** `Akti\Services\FileManager` (`app/services/FileManager.php`)

### Métodos Principais

| Método | Descrição |
|--------|-----------|
| `upload($file, $module, $options)` | Upload único com validação |
| `uploadMultiple($files, $module, $options)` | Upload de múltiplos arquivos |
| `delete($path)` | Exclui arquivo e seus thumbnails |
| `replace($file, $module, $oldPath, $options)` | Substitui arquivo (delete + upload) |
| `getUrl($path, $size)` | Gera URL para arquivo com tamanho opcional |
| `thumbUrl($path, $w, $h)` | Gera URL para thumbnail |
| `download($path, $filename)` | Força download do arquivo |
| `serve($path)` | Serve arquivo inline com cache headers |

### Opções de Upload

```php
$fileManager->upload($file, 'products', [
    'subdirectory' => 'custom/path',     // Subdiretório dentro do tenant
    'prefix'       => 'prod_123',        // Prefixo do nome do arquivo
    'entityType'   => 'product',         // Tipo de entidade (para rastreamento)
    'entityId'     => 123,               // ID da entidade
    'track'        => true,              // Salvar na tabela file_assets
]);
```

### Retorno

```php
// Sucesso
['success' => true, 'path' => 'assets/uploads/akti_x/products/prod_123_abc.jpg', 'original_name' => 'foto.jpg', 'mime_type' => 'image/jpeg', 'size' => 12345]

// Erro
['success' => false, 'error' => 'Tipo de arquivo não permitido.']
```

### Size Presets

| Preset | Largura (px) |
|--------|-------------|
| `xs`   | 40          |
| `sm`   | 80          |
| `md`   | 150         |
| `lg`   | 300         |
| `xl`   | 600         |

---

## ThumbnailService

**Classe:** `Akti\Services\ThumbnailService` (`app/services/ThumbnailService.php`)

- Gera thumbnails sob demanda com cache em `_thumbs/` subdirectory
- Padrão de nome: `{dir}/_thumbs/{filename}_{w}x{h}.jpg`
- Requer extensão **GD** do PHP (gracefully degrada sem ela)
- Modos: `cover` (crop para preencher) e `contain` (proporcional)
- Preserva transparência para PNG/WebP
- SVGs são ignorados (não redimensionáveis via GD)

---

## FileController

**Classe:** `Akti\Controllers\FileController` (`app/controllers/FileController.php`)

**Rota:** `?page=files&action=<action>`

| Action | Descrição |
|--------|-----------|
| `serve` | Serve arquivo com cache 304/ETag |
| `thumb` | Gera thumbnail on-the-fly (`&path=...&w=150&h=150`) |
| `download` | Força download |
| `upload` | Upload via AJAX (requer CSRF) |

**Segurança:** Validação `isPathSafe()` previne path traversal, limita a `assets/uploads/` e `storage/`.

---

## Helpers Globais

**Arquivo:** `app/utils/file_helper.php` (carregado automaticamente pelo autoloader)

```php
// Gera URL de arquivo com tamanho opcional
file_url(?string $path, ?string $size = null): string

// Gera URL de thumbnail (retorna path original se GD indisponível ou SVG)
thumb_url(?string $path, int $width, ?int $height = null): string

// Verifica se é imagem pelo MIME
is_file_image(?string $path): bool

// URL com fallback para placeholder
file_url_or(?string $path, string $placeholder, ?string $size = null): string
```

### Uso em Views (PHP)

```php
<!-- Thumbnail de produto -->
<img src="<?= eAttr(thumb_url($product['image_path'], 80, 80)) ?>" alt="...">

<!-- Imagem em tamanho original -->
<img src="<?= eAttr(file_url($product['image_path'])) ?>" alt="...">

<!-- Com placeholder -->
<img src="<?= eAttr(file_url_or($customer['photo'], 'assets/img/avatar.png', 'md')) ?>" alt="...">
```

---

## Helpers JavaScript

**Definidos em:** `app/views/layout/footer.php`

```javascript
// Gera URL de thumbnail
thumbUrl(path, width, height)

// Gera URL de arquivo com preset de tamanho
fileUrl(path, size)  // size: 'xs', 'sm', 'md', 'lg', 'xl'
```

### Uso em Views (JavaScript / AJAX)

```javascript
// Em templates renderizados via JS
const imgHtml = `<img src="${thumbUrl(product.main_image_path, 40, 40)}" alt="...">`;

// Com preset
const imgSrc = fileUrl(product.image_path, 'md');
```

---

## Multi-Tenant

- FileManager usa `TenantManager::getTenantUploadBase()` internamente.
- Caminho base: `assets/uploads/{db_name}/`.
- Cada módulo cria subdiretórios automaticamente.
- Arquivos de diferentes tenants jamais se misturam.

**Estrutura de diretórios:**
```
assets/uploads/
  akti_cliente1/
    products/          # Imagens de produtos
    customers/         # Fotos de clientes
    item_logs/         # Anexos de logs de itens
    avatars/           # Avatars do portal
    logos/             # Logos da empresa
    comprovantes/      # Comprovantes de pagamento
    attachments/       # Anexos gerais
    nfe/               # Logo NFe
    _thumbs/           # Thumbnails gerados
  akti_cliente2/
    ...
```

---

## Perfis de Módulo

O FileManager possui perfis predefinidos por módulo (MODULE_PROFILES):

| Módulo | Extensões Permitidas | Tamanho Máximo | Subdiretório |
|--------|---------------------|----------------|--------------|
| `products` | jpg, jpeg, png, gif, webp | 5 MB | `products/` |
| `customers` | jpg, jpeg, png, gif, webp | 2 MB | `customers/` |
| `logos` | jpg, jpeg, png, gif, webp, svg | 2 MB | (raiz) |
| `avatars` | jpg, jpeg, png, gif, webp | 2 MB | `avatars/` |
| `comprovantes` | jpg, jpeg, png, gif, webp, pdf | 5 MB | `comprovantes/` |
| `attachments` | jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, zip, rar | 10 MB | `attachments/` |
| `item_logs` | jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx | 5 MB | `item_logs/` |
| `nfe` | jpg, jpeg, png, gif, webp | 2 MB | `nfe/` |

Para adicionar novo perfil, editar a constante `MODULE_PROFILES` em `FileManager.php`.

---

## Exibição de Arquivos nas Views

- **PHP (server-rendered):** usar `thumb_url()` para imagens, `file_url()` para links de download.
- **JavaScript (AJAX-rendered):** usar `thumbUrl()` e `fileUrl()` definidos no footer.
- Sempre escapar com `eAttr()` dentro de atributos HTML (`src`, `href`).
- Caminhos armazenados no banco já incluem o subdiretório do tenant.
- Arquivos de antes do módulo FileManager permanecem acessíveis nos caminhos antigos.

---

## Regras Obrigatórias

1. **Todo upload** deve passar por `FileManager::upload()` ou `FileManager::uploadMultiple()`.
2. **Toda exclusão de arquivo** deve usar `FileManager::delete()` (nunca `unlink()` direto).
3. **Toda substituição** deve usar `FileManager::replace()`.
4. **Imagens em views PHP** devem usar `thumb_url()` ou `file_url()` com `eAttr()`.
5. **Imagens em views JS** devem usar `thumbUrl()` ou `fileUrl()`.
6. **Nunca** usar `move_uploaded_file()` diretamente em controllers/models.
7. **Nunca** construir caminhos de upload manualmente — o FileManager resolve via TenantManager.
8. **Nunca** usar `$_FILES` diretamente sem passar pelo FileManager.

---

## Exceções

Os seguintes casos **não** passam pelo FileManager por motivos justificados:

| Caso | Motivo |
|------|--------|
| `ProductImportService` | Arquivo temporário em `sys_get_temp_dir()` para processamento CSV/Excel |
| `CustomerImportService` | Idem — arquivo temporário para processamento |
| `NfeCredentialController` | Certificado digital (.pfx/.p12) armazenado em `storage/certificates/` fora do webroot, com requisitos de segurança específicos |

---