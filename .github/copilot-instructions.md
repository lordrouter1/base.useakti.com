# Akti - Gestão em Produção

> ⚠️ **REGRA CRÍTICA — Atualização do Banco de Dados**
> 
> **Toda alteração que envolva o banco de dados** (criação, modificação ou remoção de tabelas, colunas, índices, constraints, dados de configuração etc.) **deve obrigatoriamente gerar um arquivo SQL de atualização** (ex: `update_YYYYMMDD_descricao.sql`) na pasta `/sql`. Esse arquivo deve conter **apenas os comandos necessários** para atualizar o banco de produção, garantindo que o deploy seja feito apenas subindo e executando os arquivos SQL de atualização. **Nunca altere diretamente o banco de produção sem o arquivo de migração correspondente.**

## Nome do Sistema
**Akti - Gestão em Produção**

## Visão Geral
Este projeto é um sistema de gestão focado na linha de produção (ERP/CRM operacional), adaptável para diferentes segmentos industriais e tipos de produção (gráfica, confecção, alimentos, metalurgia, marcenaria, serviços sob demanda etc.).

## Tecnologias e Versões
- **Linguagem Backend:** PHP (Versão 7.4 ou 8.x)
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework CSS:** Bootstrap 5
- **Biblioteca JS:** jQuery (última versão estável)
- **Banco de Dados:** MySQL/MariaDB
- **Arquitetura:** MVC (Model-View-Controller)

## Fluxo de Desenvolvimento
Ao realizar modificações:
1. Verifique se a alteração requer mudança no banco de dados (atualizar `/sql`).
2. Mantenha a separação MVC.
3. Garanta que o layout seja responsivo.

## Como Adicionar Novas Páginas (Workflow)
Para adicionar uma nova funcionalidade completa (ex: "Fornecedores"), siga esta ordem rigorosa:

1. **Banco de Dados:** Crie a tabela necessária no banco (e salve o script em `/sql`).
2. **Model:** Crie o arquivo (ex: `app/models/Supplier.php`).
   - **Obrigatório:** declarar `namespace Akti\Models;` na primeira linha após `<?php`.
   - Recebe `$db` (PDO connection) no construtor.
   - Deve conter métodos CRUD: `create()`, `readAll()`, `readOne()`, `update()`, `delete()`.
   - **NÃO** adicionar `require_once` — o autoloader PSR-4 carrega automaticamente.
3. **Controller:** Crie o controller (ex: `app/controllers/SupplierController.php`).
   - **Obrigatório:** declarar `namespace Akti\Controllers;` na primeira linha após `<?php`.
   - **Obrigatório:** adicionar `use Akti\Models\Supplier;` para importar o model.
   - Deve ter métodos públicos mapeados para ações: `index()` (listagem), `create()` (exibir form), `store()` (processar form).
   - Deve fazer a checagem de permissão no início de cada método.
   - **NÃO** adicionar `require_once` — o autoloader PSR-4 carrega automaticamente.
4. **View:** Crie a pasta e arquivos (ex: `app/views/suppliers/index.php`).
   - Use `header.php` e `footer.php` para manter o layout.
   - Se precisar referenciar classes com namespace, usar FQCN: `\Akti\Models\NomeClasse::metodo()`.
5. **Rotas (Router):** Edite o arquivo `index.php` na raiz.
   - Adicione `use Akti\Controllers\SupplierController;` no bloco de imports do topo.
   - Adicione um novo `case 'nome_pagina':` no switch principal.
   - Instancie o controller e chame o método baseado na `action`.
6. **Permissões:**
   - Adicione a nova página ao array `$pages` no arquivo `app/views/users/groups.php` para que ela apareça na gestão de grupos.
   - Adicione o link no menu em `app/views/layout/header.php` (com verificação de permissão se necessário).

## Onde colocar cada código? (Responsabilidades MVC)

### 1. Models (`app/models/`)
**Responsabilidade:** Acesso a dados e Regras de Negócio.
- **Deve conter:** Queries SQL (`INSERT`, `SELECT`, etc), validação de dados antes de salvar (ex: checar duplicidade de email).
- **NÃO pode conter:** HTML, `echo`, `print`, acesso direto a `$_POST` ou `$_GET`.

### 2. Controllers (`app/controllers/`)
**Responsabilidade:** Recepcionista e Gerente.
- **Deve conter:** Captura de dados do formulário (`$_POST`), verificação de login (`checkAdmin`), instanciação de Models, decisão de qual View mostrar, Redirecionamentos (`header('Location: ...')`), mensagens de erro/sucesso.
- **NÃO pode conter:** Queries SQL diretas, HTML complexo.

### 3. Views (`app/views/`)
**Responsabilidade:** Interface com o Usuário.
- **Deve conter:** Estrutura HTML, formulários, loops (`foreach`) para exibir listas de dados vindas do controller.
- **NÃO pode conter:** Lógica de banco de dados, alterações de registro, lógica complexa de PHP. A View apenas **mostra** o que o Controller entregou.

## Rotas do Sistema (Router - index.php)
O roteamento é baseado nos parâmetros `page` e `action` via GET.

| Page       | Descrição                         | Requer Login | Permissão  |
|------------|-----------------------------------|--------------|------------|
| `home`     | Página inicial (landing)          | Sim          | Livre      |
| `login`    | Login/Logout                      | Não          | —          |
| `dashboard`| Painel de controle                | Sim          | Livre      |
| `profile`  | Perfil do usuário logado          | Sim          | Livre      |
| `customers`| CRUD de Clientes                  | Sim          | Por grupo  |
| `products` | CRUD de Produtos                  | Sim          | Por grupo  |
| `orders`   | CRUD de Pedidos                   | Sim          | Por grupo  |
| `pipeline` | Linha de Produção (Pipeline)      | Sim          | Livre      |
| `users`    | Gestão de Usuários/Grupos (Admin) | Sim          | Admin only |

### Padrão de Actions por módulo
- `index` → Listagem (padrão)
- `create` → Exibir formulário de criação
- `store` → Processar formulário de criação (POST)
- `edit` → Exibir formulário de edição
- `update` → Processar formulário de edição (POST)
- `delete` → Excluir registro

### Actions do Pipeline (`?page=pipeline`)
- `index` → Kanban Board (visão principal)
- `detail` → Detalhe completo do pedido no pipeline (GET `&id=X`)
- `move` → Mover pedido para outra etapa (GET `&id=X&stage=Y`)
- `updateDetails` → Atualizar dados extras do pedido (POST)
- `settings` → Configuração de metas de tempo por etapa
- `saveSettings` → Salvar configurações de metas (POST)
- `alerts` → JSON com pedidos atrasados (para notificações)

## Para mais detalhes procure pelas instruções complementares na pasta:
**`.github/instructions/`**

- `architecture.md`: Padrões e estrutura (PSR-4, Multi-Tenant).
- `security.md`: Sanitização, escape e proteção CSRF.
- `database.md`: Referência aos procedimentos com banco e migrations.
- `pipeline.md`: O conceito e fluxo do Pipeline principal em Kanban.
- `events.md`: Despachante de eventos da aplicação.
- `extras.md`: Frontend, componentes visuais e outros módulos.
- `modulo-grade_categoria_subcategoria.md`: contem definições sobre grades e heranças de categorias e subcategorias de produtos.
- `upload.md`: contem regras basicas para upload de arquivos seguindo o padrão do sistema para multitenant.
- `modulo-financeiro.md`: modulo responsavel pela confirmação de pagamentos e gestão financeira.
- `Bootloader.md`: responsavel pelo carregamento de modulos.
- `funcoes.md`: definições basicas da estrutura necessária para criação de novas funções ou alterações de funções existentes;
- `nodejs-api`: definições do funcionamento do servidor nodejs para a api.