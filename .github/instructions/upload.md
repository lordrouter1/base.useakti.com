# Módulo Upload

---

## Sumário
- [Visão Geral](#visão-geral)
- [Regras de Upload](#regras-de-upload)
- [Multi-Tenant](#multi-tenant)
- [Exibição de Arquivos](#exibição-de-arquivos-nas-views)

---

## Visão Geral
O módulo de upload permite envio de arquivos de forma segura, seguindo as regras de multi-tenant e controle de acesso.

---

## Regras de Upload
- Uploads são organizados por tenant: `assets/uploads/{db_name}/{modulo}/`.
- Apenas usuários autenticados podem enviar arquivos.
- Tipos permitidos: imagens, PDFs, planilhas.
- Limite de tamanho configurável.
- Sanitização de nomes de arquivos.
- Validação de extensão e MIME type.
- Nunca usar caminhos fixos, sempre usar `TenantManager::getTenantUploadBase()`.
- Diretório criado com `mkdir($dir, 0755, true)` se não existir.
- Caminho completo salvo no banco e usado pela view.
- Arquivos de diferentes tenants jamais devem se misturar.

---

## Multi-Tenant
- Cada tenant possui diretório próprio.
- Caminho base fornecido por `TenantManager::getTenantUploadBase()`.

---

## Exibição de Arquivos nas Views
- Views exibem arquivos usando o caminho armazenado no banco.
- Caminho já inclui o subdiretório do tenant.
- Arquivos antigos (sem prefixo) permanecem acessíveis.

---

## Uploads de Arquivos (Multi-Tenant)

### Regra de Pastas por Cliente
Todo arquivo enviado pelo usuário (imagens de produtos, fotos de clientes, logo da empresa, anexos de logs de itens) deve ser armazenado em um subdiretório exclusivo do tenant dentro de `assets/uploads/`.

O caminho base é fornecido pelo método estático `TenantManager::getTenantUploadBase()`, que retorna `assets/uploads/{db_name}/` onde `{db_name}` é o nome do banco do tenant (ex.: `akti_cliente1`).

**Estrutura de diretórios:**
```
assets/uploads/
  akti_cliente1/
    products/          # Imagens de produtos
    customers/         # Fotos de clientes
    item_logs/         # Anexos de logs de itens de pedido
                       #   └── {order_id}/{order_item_id}/
    company_logo_*.ext # Logo da empresa
  akti_cliente2/
    ...
```

### Regras Obrigatórias
- **Nunca** usar caminhos fixos como `assets/uploads/` diretamente. Sempre usar `TenantManager::getTenantUploadBase()`.
- O caminho completo (relativo à raiz do projeto) é salvo no banco de dados e usado diretamente pela view para exibir o arquivo.
- Ao criar novos módulos com upload, seguir o padrão: `TenantManager::getTenantUploadBase() . 'nome_do_modulo/'`.
- O diretório deve ser criado com `mkdir($dir, 0755, true)` se não existir.
- Arquivos de diferentes tenants jamais devem se misturar.

### Exibição de Arquivos nas Views
- As views exibem arquivos usando o caminho armazenado no banco de dados (`$product['image_path']`, `$customer['photo']`, `$settings['company_logo']`, `$log['file_path']`).
- Como o caminho armazenado já inclui o subdiretório do tenant, não é necessário nenhum ajuste adicional na view.
- Arquivos enviados antes da implementação do multi-tenant (sem prefixo do tenant no caminho) permanecem acessíveis nos caminhos antigos armazenados no banco.

---