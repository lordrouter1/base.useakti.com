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

## Estrutura de Pastas
O projeto segue a seguinte organização de diretórios:

```
/sistemaTiago
|-- /app
|   |-- /config       # Arquivos de configuração (Banco de dados, Globais)
|   |-- /controllers  # Controladores da aplicação (Lógica de negócio)
|   |-- /models       # Modelos de interação com o banco de dados
|   |-- /views        # Arquivos de visualização (HTML/PHP misto)
|       |-- /layout   # Cabeçalho, Rodapé, Menu lateral
|-- /assets
|   |-- /css          # Estilos customizados
|   |-- /js           # Scripts customizados
|   |-- /img          # Imagens do sistema
|   |-- /uploads      # Uploads por tenant: uploads/{db_name}/{modulo}/
|-- /docs             # Documentação técnica e arquivos de configuração
|-- /sql              # Scripts SQL para criação e migração do banco
|-- index.php         # Ponto de entrada da aplicação (Router básico)
```

## Padrões de Código (Guidelines)

### PHP & MVC
- **Models:** Devem conter apenas lógica de acesso a dados e regras de negócio puras. Devem extender uma classe `Database` base.
- **Controllers:** Devem receber as requisições, instanciar models e retornar views. Evitar HTML dentro de controllers.
- **Views:** Devem conter HTML e o mínimo de PHP possível (apenas para exibição de dados: `<?= $variavel ?>`).

### Frontend
- Utilizar classes do **Bootstrap 5** para layout e responsividade.
- Arquivos CSS e JS customizados devem ficar separados em `assets/`.
- **jQuery** deve ser utilizado para manipulação de DOM e requisições AJAX.

### Banco de Dados
- Tabelas devem usar nomes no singular ou plural (definir padrão: sugerido **snake_case** e plural, ex: `users`, `products`, `orders`).
- Chaves primárias devem ser `id` (AUTO_INCREMENT).

### Banco de Dados Multi-Tenant (Obrigatório)
- O sistema deve operar em arquitetura **multi-tenant por subdomínio**.
- Existe um banco **master** (`akti_master`) responsável por mapear cada cliente para seu banco dedicado.
- Cada cliente deve possuir banco próprio com prefixo `akti_` (ex.: `akti_cliente1`, `akti_cliente2`).
- A tabela de referência de tenants no master é `tenant_clients`.
- O login e toda a sessão devem respeitar o tenant resolvido pelo subdomínio atual.

#### Regras de Resolução de Tenant
1. A aplicação lê o `HTTP_HOST` e extrai o subdomínio.
2. O subdomínio é consultado em `akti_master.tenant_clients`.
3. Se o cliente estiver ativo, a conexão usa `db_host`, `db_port`, `db_name`, `db_user`, `db_password`, `db_charset` desse tenant.
4. Se o subdomínio for inválido/inativo, o login deve ser bloqueado.
5. Se houver troca de subdomínio com sessão ativa, a sessão deve ser encerrada por segurança.

#### Estrutura Esperada no Banco Master (`tenant_clients`)
- Identificação: `id`, `client_name`, `subdomain`, `is_active`.
- Conexão: `db_host`, `db_port`, `db_name`, `db_user`, `db_password`, `db_charset`.
- Limites do cliente: `max_users`, `max_products`, `max_warehouses`, `max_price_tables`, `max_sectors`.
- Auditoria: `created_at`, `updated_at`.

#### Regras de Limites por Cliente
- `max_users`: quantidade máxima de usuários cadastrados por tenant.
- `max_products`: quantidade máxima de produtos cadastrados por tenant.
- `max_warehouses`: quantidade máxima de armazéns/locais de estoque por tenant.
- `max_price_tables`: quantidade máxima de tabelas de preço por tenant.
- `max_sectors`: quantidade máxima de setores de produção por tenant.
- Valores `NULL` ou `<= 0` devem ser tratados como **sem limite**.
- As validações devem ocorrer no backend antes de criar o recurso, incluindo importação em lote.
- Quando o limite é atingido, o botão de criação deve ser **desabilitado** na view e um **alerta visual** (alert Bootstrap + SweetAlert2) deve informar que o limite do plano foi atingido.
- A mensagem de limite deve orientar o usuário a entrar em contato com o suporte para upgrade do plano.


## Fluxo de Desenvolvimento
Ao realizar modificações:
1. Verifique se a alteração requer mudança no banco de dados (atualizar `/sql`).
2. Mantenha a separação MVC.
3. Garanta que o layout seja responsivo.

## Como Adicionar Novas Páginas (Workflow)
Para adicionar uma nova funcionalidade completa (ex: "Fornecedores"), siga esta ordem rigorosa:

1. **Banco de Dados:** Crie a tabela necessária no banco (e salve o script em `/sql`).
2. **Model:** Crie o arquivo (ex: `app/models/Supplier.php`).
   - Deve estender nenhuma classe (recebe `$db` no construtor).
   - Deve conter métodos CRUD: `create()`, `readAll()`, `readOne()`, `update()`, `delete()`.
3. **Controller:** Crie o controller (ex: `app/controllers/SupplierController.php`).
   - Deve ter métodos públicos mapeados para ações: `index()` (listagem), `create()` (exibir form), `store()` (processar form).
   - Deve fazer a checagem de permissão no início de cada método.
4. **View:** Crie a pasta e arquivos (ex: `app/views/suppliers/index.php`).
   - Use `header.php` e `footer.php` para manter o layout.
5. **Rotas (Router):** Edite o arquivo `index.php` na raiz.
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

## Módulo: Linha de Produção (Pipeline)

### Conceito
O Pipeline controla o fluxo completo de cada pedido/ordem de produção, desde o primeiro contato com o cliente até a conclusão financeira. Cada pedido passa pelas seguintes etapas:

1. **Contato** (📞) — Primeiro contato com cliente, entendimento da necessidade
2. **Orçamento** (📄) — Elaboração e envio do orçamento ao cliente
3. **Venda** (🤝) — Orçamento aprovado, venda confirmada
4. **Produção** (🏭) — Pedido em execução na linha de produção do cliente
5. **Preparação** (📦) — Acabamento, corte, empacotamento
6. **Envio/Entrega** (🚚) — Pronto para envio ou entrega ao cliente
7. **Financeiro** (💰) — Cobrança, conferência de pagamento
8. **Concluído** (✅) — Pedido finalizado com sucesso

### Tabelas no Banco de Dados
- `orders` — Colunas adicionadas: `pipeline_stage`, `pipeline_entered_at`, `deadline`, `priority`, `notes`, `assigned_to`, `payment_status`, `payment_method`, `discount`, `shipping_type`, `shipping_address`, `tracking_code`
- `pipeline_history` — Histórico de movimentação (de qual etapa para qual, por quem, quando)
- `pipeline_stage_goals` — Metas configuráveis de tempo máximo (em horas) por etapa

### Regras de Negócio
- Ao criar um pedido, ele entra automaticamente na etapa "Contato"
- Mover entre etapas registra no histórico com timestamp e usuário
- Pedidos que ultrapassam a meta de horas de uma etapa são marcados como **atrasados**
- Alertas visuais aparecem no Kanban e no Dashboard quando há atrasos
- Cada pedido pode ter prioridade (baixa, normal, alta, urgente), responsável, prazo e notas internas
- Dados de financeiro (pagamento) e envio (endereço, rastreio) são gerenciados pelo detalhe do pipeline

### Arquivos do Módulo
- `sql/pipeline.sql` — Script de migração do banco
- `app/models/Pipeline.php` — Model com métodos de consulta e movimentação
- `app/controllers/PipelineController.php` — Controller com actions do pipeline
- `app/views/pipeline/index.php` — Kanban Board visual
- `app/views/pipeline/detail.php` — Detalhe completo do pedido
- `app/views/pipeline/settings.php` — Configuração de metas por etapa

## Bibliotecas e Frameworks Frontend
- **Bootstrap 5** — Layout e componentes UI
- **jQuery 3.7** — Manipulação DOM e AJAX
- **Font Awesome 6** — Ícones
- **SweetAlert2** — Alertas e confirmações visuais (substituir `confirm()` e `alert()`)
- **jQuery Mask** — Máscaras de input (CPF, telefone, CEP)

### Padrão para Feedback Visual (SweetAlert2)
- Após ações de CRUD, redirecionar com `?status=success` na URL.
- Na view de listagem, verificar `$_GET['status']` e disparar `Swal.fire()`.
- Para exclusões, usar `Swal.fire()` com confirmação antes de prosseguir.
- Nunca usar `confirm()` ou `alert()` nativo do JavaScript.

### Menu Superior (header.php)
- O **nome do usuário** sempre redireciona para o **Perfil** (`?page=profile`).
- O ícone de **engrenagem (⚙️)** redireciona para a **Gestão de Usuários** (`?page=users`) e **só aparece para admin**.
- O botão de **Sair** faz logout (`?page=login&action=logout`).
- O menu é fixo no topo (`sticky-top`) e não muda de tamanho ao selecionar itens.

### Padrão de Formulários (Create/Edit)
- Os formulários de **criação** e **edição** de cada módulo devem ser **visualmente idênticos**.
- Ambos devem usar a mesma estrutura de fieldsets, mesmos campos, mesmos labels e mesmo layout.
- A diferença é que no edit os campos vêm pré-preenchidos com `value="<?= $model['campo'] ?>"` e o form action aponta para `action=update` em vez de `action=store`.
- Formulários de edição incluem um `<input type="hidden" name="id">` com o ID do registro.

## Regras de Visibilidade dos Cards no Detalhe do Pipeline (`detail.php`)

### Princípio Geral
Cada card/seção no detalhe do pipeline só deve ser exibido nas etapas em que é relevante. Isso reduz poluição visual e evita ações acidentais fora do contexto correto.

### Card de Controle de Produção (Ordem de Produção)
- **Visível apenas na etapa:** `producao` (Produção 🏭).
- **Não aparece em:** `preparacao`, `envio`, `financeiro`, `concluido`, nem nas etapas anteriores (`contato`, `orcamento`, `venda`).
- Quando visível, os campos são **somente leitura** (readonly), pois os dados de produção são preenchidos na etapa de produção e apenas consultados depois.
- A impressão da ordem de produção (`print_production_order.php`) também só é acessível na etapa de produção.

### Card de Produtos / Orçamento
- **Visível nas etapas:** `contato`, `orcamento`, `venda`, `preparacao`.
- **Não aparece em:** `producao`, `envio`, `financeiro`, `concluido`.
- Justificativa: nas etapas de produção em diante, os produtos já foram definidos e não devem ser alterados. Na etapa de envio, o foco é na logística/entrega.

### Card de Envio / Entrega (Shipping)
- **Visível nas etapas:** `envio` (e potencialmente `preparacao` para pré-preenchimento).
- **Comportamento dinâmico por Modalidade de Envio:**
  - O select "Modalidade de Envio" (`shipping_type`) controla dinamicamente (via JS) quais seções são exibidas:
    - **Retirada na Loja** (`retirada`): Oculta o card de endereço e o botão de impressão. Mostra apenas mensagem de retirada.
    - **Entrega Própria** (`entrega`): Exibe card de endereço em destaque + botão "Imprimir Guia de Endereçamento".
    - **Correios / Transportadora** (`correios`): Exibe card de endereço em destaque + botão "Imprimir Guia de Endereçamento" + campo de rastreio.
  - Ao trocar a modalidade, as seções atualizam instantaneamente sem recarregar a página.
- **Estrutura obrigatória do card:**
  1. **Endereço de entrega** em destaque visual (card com borda colorida, ícone de mapa, texto grande e legível). Visível apenas para `entrega` e `correios`.
  2. Botão "Usar endereço do cliente" que copia automaticamente o endereço cadastrado do cliente para o campo de envio.
  3. **Tipo de envio** (Correios, Motoboy, Retirada, etc.) em campo separado e visível.
  4. **Código de rastreamento** com campo dedicado.
  5. **Área de integração futura** com APIs de transportadoras (placeholder visual para Correios, Jadlog, etc.), preparada para receber dados de frete, rastreamento automático e status de entrega.
  6. **Botão "Imprimir Guia de Endereçamento"** — abre uma nova janela com etiqueta formatada (tamanho A5 landscape) contendo: remetente (dados da empresa), destinatário (nome, telefone, endereço completo), modalidade de envio, código de rastreio e data. O layout é otimizado para ser recortado e colado na embalagem.
- O card deve usar `fieldset` com `legend` estilizado, e o endereço deve ser o elemento mais proeminente da seção.
- O badge no `legend` do fieldset e a cor da borda atualizam dinamicamente conforme a modalidade selecionada.

### Card Financeiro (Pagamento, Parcelamento, Boleto, NF-e)
- **Visível nas etapas:** `venda`, `financeiro`, `concluido`.
- **Na etapa `financeiro`:** o card é o foco principal. O card de Produtos/Orçamento é **ocultado** para evitar poluição visual, e no lugar é exibido um resumo compacto dos produtos dentro do card financeiro.
- **Funcionalidades do card financeiro:**
  1. **Valor Total** (somente leitura, vindo do pedido).
  2. **Status de Pagamento** (`pendente`, `parcial`, `pago`).
  3. **Forma de Pagamento** (dinheiro, pix, cartão crédito/débito, boleto, transferência).
  4. **Parcelamento** — aparece para `cartao_credito` e `boleto`:
     - Número de parcelas (2x a 12x).
     - Entrada / sinal (`down_payment`) — campo numérico, 0 se não houver.
     - Valor por parcela (calculado automaticamente: `(total - desconto - entrada) / nParcelas`).
  5. **Tabela de boletos** — aparece apenas para forma de pagamento `boleto`:
     - Cada parcela tem data de vencimento editável, valor e status.
     - Botão **"Imprimir Boletos"** — abre nova janela com layout A4 formatado para impressão.
  6. **Links de Pagamento** — placeholder para integração futura com PagSeguro, Mercado Pago, PIX dinâmico, Stripe.
  7. **Fiscal / Nota Fiscal** — seção para NF-e:
     - Campos: número, série, status (`emitida`, `enviada`, `cancelada`), chave de acesso (44 dígitos), observações.
     - Botão **"Emitir NF"** (placeholder para integração futura com NFe.io, Bling, Tiny ERP, eNotas).
- **Campos no banco de dados:** `payment_status`, `payment_method`, `installments`, `installment_value`, `down_payment`, `discount`, `nf_number`, `nf_series`, `nf_status`, `nf_access_key`, `nf_notes`.
- **Migração SQL:** `sql/financial_upgrade.sql`.
- Quando o card financeiro está **oculto** (etapas que não o exibem), os valores são preservados via `<input type="hidden">` para não serem perdidos ao salvar o formulário.

### Regra Geral de Extensão
- Ao adicionar novos cards ou seções ao detalhe do pipeline, sempre definir explicitamente em quais etapas (`pipeline_stage`) o card será visível, usando condições PHP no `detail.php`.
- Documentar a visibilidade nesta seção do `PROJECT_RULES.md`.

## Módulo: Grades / Variações de Produtos

### Conceito
O sistema de grades permite que cada produto tenha múltiplas dimensões de variação (ex: Tamanho, Cor, Material). Cada grade possui valores configuráveis, e as combinações são geradas automaticamente como produto cartesiano de todas as grades ativas.

**Exemplo:**
- Produto "Camiseta" com 2 grades:
  - Grade "Tamanho" → Valores: P, M, G, GG
  - Grade "Cor" → Valores: Branca, Preta, Azul
- Combinações geradas: P/Branca, P/Preta, P/Azul, M/Branca, M/Preta, M/Azul, G/Branca, G/Preta, G/Azul, GG/Branca, GG/Preta, GG/Azul (12 combinações)

### Tabelas no Banco de Dados
- `product_grade_types` — Tipos de grade reutilizáveis (Tamanho, Cor, Material, etc.)
- `product_grades` — Grades vinculadas a um produto específico
- `product_grade_values` — Valores de cada grade (ex: P, M, G para "Tamanho")
- `product_grade_combinations` — Combinações geradas (com SKU, preço e estoque por combinação)
- `order_items` — Colunas `grade_combination_id` e `grade_description` para vincular a combinação escolhida

### Regras de Negócio
- Um produto pode ter 0 ou mais grades (grades são opcionais)
- Cada grade é baseada em um tipo de grade (`product_grade_types`) que é reutilizável entre produtos
- Novos tipos de grade podem ser criados via AJAX no formulário do produto
- Ao salvar um produto com grades, as combinações são geradas automaticamente (produto cartesiano)
- Cada combinação pode ter preço específico (`price_override`) ou usar o preço padrão do produto
- Cada combinação pode ter estoque próprio e SKU
- Ao selecionar um produto no pedido, se ele tiver grades, o usuário deve escolher a combinação desejada
- A descrição da combinação é salva no item do pedido (`grade_description`) para preservar histórico

### Arquivos do Módulo
- `sql/product_grades.sql` — Script de migração (para bancos existentes)
- `sql/database.sql` — Tabelas incluídas no script principal
- `app/models/ProductGrade.php` — Model com métodos CRUD para grades, valores e combinações
- `app/views/products/_grades_partial.php` — Partial view reutilizada em create.php e edit.php
- `app/controllers/ProductController.php` — Actions de grade: `createGradeType`, `getGradeTypes`, `generateCombinations`

### Actions AJAX (`?page=products`)
- `createGradeType` — POST: cria novo tipo de grade
- `getGradeTypes` — GET: lista todos os tipos de grade
- `generateCombinations` — POST: gera preview de combinações baseado nos dados enviados

## Módulo: Grades de Categorias e Subcategorias (Herança)

### Conceito
Categorias e subcategorias podem ter grades padrão definidas. Ao criar ou editar um produto, se a subcategoria selecionada possui grades, estas são oferecidas para importação automática. Caso a subcategoria não tenha grades, o sistema verifica a categoria. A subcategoria sempre tem prioridade sobre a categoria.

### Tabelas no Banco de Dados
- `category_grades` — Grades vinculadas a uma categoria (mesma estrutura de product_grades)
- `category_grade_values` — Valores de cada grade de categoria
- `category_grade_combinations` — Combinações de grades de categoria (com controle de ativação)
- `subcategory_grades` — Grades vinculadas a uma subcategoria
- `subcategory_grade_values` — Valores de cada grade de subcategoria
- `subcategory_grade_combinations` — Combinações de grades de subcategoria (com controle de ativação)

### Regras de Negócio — Herança
1. Ao selecionar uma **subcategoria** no formulário de produto:
   - Se a subcategoria tem grades → oferece importação das grades da subcategoria
   - Se a subcategoria NÃO tem grades → verifica a categoria
2. Ao selecionar uma **categoria** sem subcategoria:
   - Se a categoria tem grades → oferece importação das grades da categoria
3. O botão "Importar Grades" aparece automaticamente quando grades herdáveis são detectadas
4. O usuário pode optar por importar ou configurar manualmente
5. Após importação, o produto é dono das suas grades (pode editar independentemente)

### Regras de Negócio — Inativação de Combinações
- Combinações podem ser **ativadas/inativadas** em 3 níveis:
  - **Categoria:** define quais combinações padrão são válidas
  - **Subcategoria:** pode refinar quais combinações são válidas (sobrepõe categoria)
  - **Produto:** controle final de quais combinações estão disponíveis para venda
- Combinações inativas em qualquer nível superior são informadas durante a herança
- Toggle visual (switch) em cada combinação permite ativar/desativar
- Combinações inativas aparecem riscadas e em vermelho na interface
- No pedido, apenas combinações ativas são listadas para seleção

### Arquivos do Módulo
- `sql/category_grades.sql` — Script de migração para bancos existentes
- `sql/database.sql` — Tabelas incluídas no script principal (seção grades de categorias)
- `app/models/CategoryGrade.php` — Model com CRUD para grades de categorias/subcategorias + lógica de herança
- `app/views/categories/_grades_partial.php` — Partial view reutilizada nos forms de categoria e subcategoria
- `app/controllers/CategoryController.php` — Actions de grade: store/update incluem grades, AJAX para herança

### Actions AJAX (`?page=categories`)
- `getInheritedGrades` — GET: retorna grades herdáveis (subcategory_id e/ou category_id)
- `toggleCategoryCombination` — POST: ativa/desativa combinação de categoria
- `toggleSubcategoryCombination` — POST: ativa/desativa combinação de subcategoria

## Kanban Pipeline — Regras de Responsividade

### Problema Resolvido
As colunas do kanban podiam desaparecer em telas menores, fazendo com que o usuário não visse pedidos em certas etapas.

### Solução Implementada
- **Desktop (≥992px):** Todas as colunas compartilham o espaço igualmente (`flex: 1 1 0`), sem scroll horizontal.
- **Tablet (577px–991px):** Colunas com largura fixa de 220px + scroll horizontal + minimap de navegação.
- **Mobile (≤576px):** Colunas com largura fixa de 240px + scroll horizontal + minimap de navegação.
- **Minimap:** Barra com ícones das etapas abaixo do kanban. Ao clicar, rola suavemente até a coluna correspondente.
- **Botões de navegação:** Setas laterais aparecem quando há scroll disponível.

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

## Implantação (Deployment) — Nginx no VPS useakti.com

### Requisitos do Servidor
- Sistema operacional: Ubuntu 20.04 LTS ou superior (ou Debian equivalente)
- Nginx instalado (`apt install nginx`)
- PHP-FPM instalado (`apt install php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml`)
- MySQL/MariaDB (`apt install mariadb-server`)
- Certbot/Let's Encrypt para certificado SSL wildcard

### Estrutura de Arquivos no VPS
```
/var/www/useakti.com/public/   ← raiz da aplicação (DOCUMENT_ROOT)
```

### Configuração Nginx
O arquivo de configuração de referência está em `docs/nginx.conf`.

Passos para ativar:
```bash
cp docs/nginx.conf /etc/nginx/sites-available/useakti.com
ln -s /etc/nginx/sites-available/useakti.com /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### Variáveis de Ambiente
Configure no bloco `location ~ \.php$` do Nginx (via `fastcgi_param`) ou em um arquivo `.env` lido pela aplicação:

| Variável | Descrição |
|---|---|
| `AKTI_BASE_DOMAIN` | `useakti.com` |
| `AKTI_DB_HOST` | Host do banco padrão (fallback) |
| `AKTI_DB_NAME` | Banco padrão (fallback) |
| `AKTI_DB_USER` | Usuário do banco padrão |
| `AKTI_DB_PASS` | Senha do banco padrão |
| `AKTI_MASTER_DB_HOST` | Host do banco master |
| `AKTI_MASTER_DB_NAME` | Nome do banco master (`akti_master`) |
| `AKTI_MASTER_DB_USER` | Usuário do banco master |
| `AKTI_MASTER_DB_PASS` | Senha do banco master |

### DNS
- Configure um registro `A` para `useakti.com` apontando para o IP do VPS.
- Configure um registro `A` wildcard `*.useakti.com` apontando para o mesmo IP.
- Isso garante que `cliente1.useakti.com`, `cliente2.useakti.com`, etc. cheguem ao mesmo servidor e sejam roteados pelo `TenantManager`.

### Provisionamento de Novo Cliente
1. Criar banco: `CREATE DATABASE akti_<cliente> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Criar usuário MySQL com acesso apenas ao banco do cliente.
3. Rodar o schema completo (`sql/database.sql`) no banco do cliente.
4. Inserir registro em `akti_master.tenant_clients` com os dados do banco e limites.
5. Configurar DNS do subdomínio (propagação pode levar até 24h).

## Módulo: Dados Fiscais (NF-e)

### Conceito
O sistema armazena dados fiscais em dois níveis:
1. **Produto** — Classificação fiscal individual (NCM, CFOP, CSTs, alíquotas, etc.)
2. **Empresa** — Dados fiscais da empresa emitente (CNPJ, IE, CRT, certificado digital, configurações NF-e, etc.)

Esses dados são necessários para a futura emissão de Nota Fiscal Eletrônica (NF-e/NFC-e) e são armazenados de forma preparatória para integração com serviços como NFe.io, Bling, Tiny ERP ou eNotas.

### Campos Fiscais do Produto
Os seguintes campos foram adicionados à tabela `products`:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `fiscal_ncm` | VARCHAR(10) | NCM — Nomenclatura Comum do Mercosul (8 dígitos) |
| `fiscal_cest` | VARCHAR(10) | CEST — Código Especificador da Substituição Tributária (7 dígitos) |
| `fiscal_cfop` | VARCHAR(10) | CFOP — Código Fiscal de Operações e Prestações |
| `fiscal_cst_icms` | VARCHAR(5) | CST ICMS — Código de Situação Tributária do ICMS (Regime Normal) |
| `fiscal_csosn` | VARCHAR(5) | CSOSN — Código de Situação da Operação no Simples Nacional |
| `fiscal_cst_pis` | VARCHAR(5) | CST PIS |
| `fiscal_cst_cofins` | VARCHAR(5) | CST COFINS |
| `fiscal_cst_ipi` | VARCHAR(5) | CST IPI |
| `fiscal_origem` | VARCHAR(2) | Origem da mercadoria (0=Nacional, 1=Estrangeira, etc.) |
| `fiscal_unidade` | VARCHAR(10) | Unidade fiscal (UN, KG, MT, M2, etc.) |
| `fiscal_ean` | VARCHAR(14) | Código EAN/GTIN (código de barras) |
| `fiscal_aliq_icms` | DECIMAL(5,2) | Alíquota ICMS (%) |
| `fiscal_aliq_ipi` | DECIMAL(5,2) | Alíquota IPI (%) |
| `fiscal_aliq_pis` | DECIMAL(5,4) | Alíquota PIS (%) |
| `fiscal_aliq_cofins` | DECIMAL(5,4) | Alíquota COFINS (%) |
| `fiscal_beneficio` | VARCHAR(20) | Código de benefício fiscal (cBenef) |
| `fiscal_info_adicional` | TEXT | Informações adicionais do produto na NF-e |

### Dados Fiscais da Empresa (company_settings)
Armazenados como pares chave-valor na tabela `company_settings` com prefixo `fiscal_`:

- **Identificação:** `fiscal_razao_social`, `fiscal_nome_fantasia`, `fiscal_cnpj`, `fiscal_ie`, `fiscal_im`, `fiscal_cnae`, `fiscal_crt`
- **Endereço Fiscal:** `fiscal_endereco_logradouro`, `fiscal_endereco_numero`, `fiscal_endereco_complemento`, `fiscal_endereco_bairro`, `fiscal_endereco_cidade`, `fiscal_endereco_uf`, `fiscal_endereco_cep`, `fiscal_endereco_cod_municipio`, `fiscal_endereco_cod_pais`, `fiscal_endereco_pais`, `fiscal_endereco_fone`
- **Certificado Digital:** `fiscal_certificado_tipo`, `fiscal_certificado_senha`, `fiscal_certificado_validade`
- **Config. NF-e:** `fiscal_ambiente`, `fiscal_serie_nfe`, `fiscal_proximo_numero_nfe`, `fiscal_modelo_nfe`, `fiscal_tipo_emissao`, `fiscal_finalidade`
- **Alíquotas Padrão:** `fiscal_aliq_icms_padrao`, `fiscal_aliq_pis_padrao`, `fiscal_aliq_cofins_padrao`, `fiscal_aliq_iss_padrao`
- **Outros:** `fiscal_nat_operacao`, `fiscal_info_complementar`

### Regras de Negócio
- Os campos fiscais do produto são **opcionais** — o fieldset é colapsável (expandir/recolher)
- Se o produto não tiver alíquotas próprias, o sistema deverá usar as alíquotas padrão da empresa
- O campo `fiscal_crt` (Regime Tributário) determina se usa CST ICMS (Regime Normal) ou CSOSN (Simples Nacional)
- O ambiente NF-e deve ser `2 — Homologação` por padrão para evitar emissão acidental em produção
- O número da NF-e (`fiscal_proximo_numero_nfe`) é incrementado automaticamente após cada emissão
- O certificado digital tem controle visual de validade com alertas de expiração (30 dias)

### Arquivos do Módulo
- `sql/fiscal_data.sql` — Script de migração (colunas no produto + settings da empresa)
- `app/models/Product.php` — `$fiscalFields` estático com lista de campos fiscais, create/update dinâmicos
- `app/views/products/_fiscal_partial.php` — Partial view fiscal reutilizada em create.php e edit.php
- `app/views/settings/index.php` — Aba "Fiscal / NF-e" nas configurações do sistema
- `app/controllers/SettingsController.php` — Action `saveFiscalSettings` para salvar dados fiscais da empresa
- `app/controllers/ProductController.php` — Coleta campos fiscais via `Product::$fiscalFields` no store/update

### Aba de Configurações: Fiscal / NF-e
- Acessível em `?page=settings&tab=fiscal`
- Cor temática: `#8e44ad` (roxo)
- Fieldsets: Identificação Fiscal, Endereço Fiscal, Certificado Digital, Configurações NF-e, Alíquotas Padrão, Informações Complementares

## Atualização do Banco de Dados
Toda alteração que envolva o banco de dados (criação, modificação ou remoção de tabelas, colunas, índices, constraints, dados de configuração etc.) deve obrigatoriamente gerar um arquivo SQL de atualização (ex: `update_YYYYMMDD.sql`) na pasta `/sql`. Esse arquivo deve conter apenas os comandos necessários para atualizar o banco de produção, garantindo que o deploy seja feito apenas subindo e executando os arquivos SQL de atualização.

### Exemplos de Atualizações
- **Adicionar nova coluna:** `ALTER TABLE products ADD COLUMN new_column VARCHAR(255) DEFAULT NULL;`
- **Criar nova tabela:** `CREATE TABLE logs (id INT AUTO_INCREMENT PRIMARY KEY, message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);`
- **Atualizar dados existentes:** `UPDATE users SET is_active = 1 WHERE last_login > '2023-01-01';`

### Padrão de Nomenclatura de Migrations
- **Formato obrigatório:** `update_YYYYMMDD_descricao_curta.sql`
- **Exemplos:** `update_20260304_financial_module.sql`, `update_20260302_tenant_limits.sql`
- **Nunca** usar prefixos como `migration_`, `alter_`, `fix_`. Sempre `update_`.
- **Cabeçalho obrigatório** no arquivo SQL:
  ```sql
  -- ============================================================================
  -- UPDATE: update_YYYYMMDD_descricao.sql
  -- Descrição: Descrição clara da alteração
  -- Data: YYYY-MM-DD
  -- Autor: Nome ou Sistema Akti
  -- ============================================================================
  ```
- Sempre incluir `SET FOREIGN_KEY_CHECKS = 0;` no início e `SET FOREIGN_KEY_CHECKS = 1;` ao final quando houver tabelas com FK.
- Usar `IF NOT EXISTS` / `IF EXISTS` sempre que possível para tornar a migration idempotente.
- Usar `ADD COLUMN IF NOT EXISTS` e `DROP TABLE IF EXISTS` para evitar erros em execuções repetidas.

### Boas Práticas
- Nomear arquivos de atualização com data e descrição resumida da mudança (ex: `update_20231010_add_column_new_feature.sql`).
- Incluir sempre um `README.md` na pasta `/sql` explicando como aplicar as atualizações.
- Testar as atualizações em um ambiente de staging antes de aplicar em produção.
- Manter backup completo do banco de dados antes de qualquer atualização.

### Aplicando Atualizações
Para aplicar uma atualização:
1. Fazer o upload do arquivo SQL para o servidor, na pasta `/sql`.
2. Conectar ao banco de dados via linha de comando ou ferramenta de administração (ex: phpMyAdmin).
3. Executar o comando: `SOURCE /caminho/para/o/arquivo/update_YYYYMMDD.sql;`
4. Verificar se a atualização foi aplicada corretamente (conferir novas tabelas/colunas, testar funcionalidades relacionadas).

### Registro de Atualizações

| Data | Arquivo | Descrição |
|------|---------|-----------|
| 02/03/2026 | `update_20260302_tenant_limits.sql` | Adição de colunas de limite no banco master |
| 03/03/2026 | `update_20260303_walkthrough.sql` | Tabela `user_walkthrough` para tour guiado de primeiro acesso |
| 04/03/2026 | `update_20260304_financial_module.sql` | Módulo financeiro: tabelas `order_installments`, `financial_transactions`, colunas NF-e |
| 09/03/2026 | `update_20260309_ip_blacklist.sql` | Sistema de blacklist automática por flood 404 (tabelas `ip_404_hits`, `ip_blacklist`, índices, usuário MySQL `akti_guard`) |

## Módulo: Financeiro (Pagamentos, Parcelas e Caixa)

### Conceito
O módulo financeiro controla o ciclo de pagamento dos pedidos e o livro-caixa da empresa. A **geração de parcelas** acontece no pipeline (detalhe do pedido), e o módulo financeiro se concentra em:
- **Dashboard** com indicadores financeiros (receita, recebido, a receber, atrasados)
- **Confirmação de pagamentos** — fluxo simples onde o operador registra e confirma recebimentos
- **Entradas e saídas** — registro manual de transações financeiras diversas (despesas fixas, compras, etc.)
- **Importação OFX** — importação de extratos bancários no formato OFX com opção de contabilizar ou apenas registrar
- **Estornos e registros** — transações informativas que não contabilizam no saldo do caixa

### Princípio: Simplicidade
- As parcelas já vêm definidas pelo pipeline (card financeiro no `detail.php`)
- No módulo financeiro, o operador **apenas confirma** os pagamentos
- Nunca gerar parcelas a partir do módulo financeiro — isso é responsabilidade do pipeline
- O fluxo deve ser: ver lista → clicar em "Parcelas" → registrar pagamento → confirmar
- Todas as ações usam **SweetAlert2** para feedback e confirmação visual

### Fluxo de Pagamento
1. **Pipeline (`detail.php`):** O operador define forma de pagamento, parcelamento e entrada. As parcelas são geradas automaticamente.
2. **Financeiro > Pagamentos (`payments.php`):** Lista todos os pedidos com seus status de pagamento. O operador clica em "Parcelas" para ver detalhes.
3. **Financeiro > Parcelas (`installments.php`):** Mostra todas as parcelas do pedido. O operador pode:
   - **Registrar pagamento** (abre modal com data, valor e método)
   - **Confirmar** pagamento já registrado
   - **Estornar** um pagamento (reverte para pendente e registra estorno no caixa)
4. O `payment_status` do pedido (`orders.payment_status`) é atualizado automaticamente conforme as parcelas são pagas/confirmadas.

### Tipos de Transação
O campo `type` da tabela `financial_transactions` aceita três valores:

| Tipo | Descrição | Contabiliza no saldo? | Badge na listagem |
|------|-----------|----------------------|-------------------|
| `entrada` | Dinheiro que entra no caixa | ✅ Sim (soma em Entradas) | 🟢 Verde + seta ↓ |
| `saida` | Dinheiro que sai do caixa | ✅ Sim (soma em Saídas) | 🔴 Vermelho + seta ↑ |
| `registro` | Lançamento informativo (estornos, importações OFX sem contabilizar) | ❌ Não contabiliza | ⚫ Cinza + risco (—) |

### Categorias de Transação

#### Categorias manuais (disponíveis no formulário)
**Entradas:**
- `pagamento_pedido` — Pagamento de Pedido
- `servico_avulso` — Serviço Avulso
- `outra_entrada` — Outra Entrada **(default para novas entradas)**

**Saídas:**
- `material` — Compra de Material
- `salario` — Salários
- `aluguel` — Aluguel
- `energia` — Energia/Água
- `internet` — Internet/Telefone
- `manutencao` — Manutenção
- `imposto` — Impostos/Taxas
- `outra_saida` — Outra Saída **(default para novas saídas)**

#### Categorias internas (usadas apenas pelo sistema)
- `estorno_pagamento` — Estorno de Pagamento (gerada ao estornar uma parcela)
- `registro_ofx` — Registro OFX (gerada na importação OFX em modo "registro")

> ⚠️ **"Estorno de Pagamento" NÃO pode ser selecionada manualmente** pelo usuário. Ela é gerada exclusivamente pelo sistema ao estornar uma parcela no módulo de pagamentos.

### Regras de Estornos
- Ao estornar uma parcela, o sistema cria uma transação com `type = 'registro'` e `category = 'estorno_pagamento'`
- Estornos **nunca contabilizam** nos totais de entradas ou saídas
- Na listagem, estornos aparecem com **badge cinza**, ícone de **risco (—)** e valor em **texto cinza**
- A transação original de entrada associada à parcela é removida da tabela

### Importação OFX
O sistema permite importar extratos bancários no formato OFX (Open Financial Exchange):

1. Na tela de **Entradas e Saídas**, clique em **"Importar OFX"**
2. Selecione o arquivo `.ofx` ou `.ofc` exportado do banco
3. Escolha o **modo de importação**:
   - **Registro** (default): importa apenas como referência — badge cinza, não contabiliza no caixa
   - **Contabilizar**: créditos entram como `entrada` e débitos como `saída`, contabilizando normalmente
4. O parser suporta o formato SGML utilizado pela maioria dos bancos brasileiros
5. Transações com valor zero ou negativo são ignoradas automaticamente

### Tabelas no Banco de Dados
- `order_installments` — Parcelas individuais de cada pedido (geradas pelo pipeline)
- `financial_transactions` — Log de transações financeiras (entradas/saídas manuais + automáticas)
- `orders` — Colunas adicionadas: `down_payment`, `nf_number`, `nf_series`, `nf_status`, `nf_access_key`, `nf_notes`

### Regras de Negócio
- Parcela com `is_confirmed = 0` e `status = 'pago'` está aguardando confirmação manual
- Parcela com `is_confirmed = 1` e `status = 'pago'` está totalmente confirmada
- O `payment_status` do pedido é calculado automaticamente: `pendente` (nenhuma paga), `parcial` (algumas pagas), `pago` (todas confirmadas)
- Parcelas vencidas (`due_date < hoje`) com status `pendente` são automaticamente marcadas como `atrasado`
- Ao registrar pagamento, uma transação financeira (`type = 'entrada'`, `category = 'pagamento_pedido'`) é criada automaticamente
- Ao estornar, a parcela volta para `pendente`, os dados de pagamento são limpos, a entrada original é removida e um registro de estorno é criado (`type = 'registro'`, `category = 'estorno_pagamento'`)
- Transações com `type = 'registro'` ou `category IN ('estorno_pagamento', 'registro_ofx')` são **excluídas** dos cálculos de totais (entradas, saídas, saldo) tanto no dashboard quanto na listagem
- Ao lançar manualmente, a categoria default é `outra_entrada` para entradas e `outra_saida` para saídas

### Padrão Visual (UI)
- **Cards de resumo:** Seguem o mesmo padrão do Dashboard — `card border-0 shadow-sm border-start border-4` com ícone circular
- **Tabelas:** `table-responsive bg-white rounded shadow-sm` com `table-hover align-middle`
- **Badges de status:** cores padronizadas (warning=pendente, success=pago, danger=atrasado, secondary=cancelado)
- **Badge de estorno/registro:** `badge bg-secondary` com ícone `fa-minus` (risco —), texto cinza, linha com fundo `table-light`
- **Modais:** Bootstrap 5 modals com header colorido (`bg-success bg-opacity-10`) e footer sem borda
- **SweetAlert2:** Obrigatório para todas as confirmações e feedbacks. Nunca usar `confirm()` ou `alert()` nativo
- **Filtros:** Linha de selects compactos (`form-select-sm`) com botão Filtrar e botão limpar (X)
- **Dropdown de tipo (filtro):** Inclui "Registros" além de "Entradas" e "Saídas"

### Arquivos do Módulo
- `sql/update_20260306_financial_module.sql` — Migration (tabelas + colunas + índices + conversão de estornos)
- `app/models/Financial.php` — Model com métodos de consulta, geração de parcelas, confirmação, estorno, importação OFX, parsing OFX
- `app/controllers/FinancialController.php` — Controller com actions: index, payments, installments, payInstallment, confirmPayment, cancelInstallment, transactions, addTransaction, deleteTransaction, importOfx, getSummaryJson, getInstallmentsJson
- `app/views/financial/index.php` — Dashboard financeiro (cards + gráfico + alertas)
- `app/views/financial/payments.php` — Lista de pedidos com status de pagamento
- `app/views/financial/installments.php` — Parcelas de um pedido (confirmação simples)
- `app/views/financial/transactions.php` — Entradas, saídas e registros (inclui modal de importação OFX)
- `app/config/menu.php` — Grupo "Fiscal" com links para dashboard, pagamentos e transações

### Actions do Módulo (`?page=financial`)
| Action | Método | Descrição |
|--------|--------|-----------|
| `index` (default) | GET | Dashboard financeiro |
| `payments` | GET | Lista de pedidos com pagamento |
| `installments` | GET | Parcelas de um pedido (`&order_id=X`) |
| `payInstallment` | POST | Registra pagamento de uma parcela |
| `confirmPayment` | POST | Confirma pagamento manualmente |
| `cancelInstallment` | POST | Estorna pagamento de uma parcela |
| `transactions` | GET | Lista de entradas, saídas e registros |
| `addTransaction` | POST | Registra nova transação manual |
| `deleteTransaction` | POST | Exclui transação manual |
| `importOfx` | POST (AJAX) | Importa arquivo OFX (registro ou contabilizado) |
| `getSummaryJson` | GET | API JSON com resumo (para widgets) |
| `getInstallmentsJson` | GET | API JSON com parcelas de um pedido |

## Módulo: Segurança — IpGuard (Blacklist Automática por Flood 404)

### Conceito
O sistema detecta automaticamente ataques de varredura (scanners, bots, brute-force de paths) com base na quantidade de requisições 404 por IP dentro de uma janela de tempo. IPs que excedem o threshold são automaticamente adicionados à blacklist.

A proteção opera em **duas camadas**:
1. **PHP (index.php):** No handler `default` do switch de roteamento, o `IpGuard` registra hits 404 e bloqueia IPs que ultrapassem o limite. Se o IP já estiver na blacklist, retorna 403 imediato sem renderizar a view.
2. **Nginx/OpenResty (Lua):** Antes de processar qualquer request, o script Lua consulta a blacklist no banco `akti_master` e retorna 403 para IPs bloqueados. Usa cache em `lua_shared_dict` para minimizar queries.

### Tabelas no Banco de Dados (`akti_master`)
- `ip_404_hits` — Registro de cada hit 404 por IP (path, user-agent, timestamp)
- `ip_blacklist` — IPs bloqueados (com razão, duração, expiração e flag ativo/inativo)

> ⚠️ Estas tabelas ficam no banco **master** (`akti_master`), não nos bancos de tenant.

### Parâmetros de Configuração (constantes em `IpGuard.php`)

| Constante | Valor padrão | Descrição |
|-----------|-------------|-----------|
| `THRESHOLD` | 30 | Número máximo de 404s na janela de tempo |
| `WINDOW_SECONDS` | 60 | Janela de tempo em segundos |
| `BLOCK_HOURS` | 24 | Duração do bloqueio em horas (0 = permanente) |
| `MAX_PATH_LENGTH` | 2048 | Tamanho máximo do path armazenado |
| `MAX_UA_LENGTH` | 512 | Tamanho máximo do user-agent armazenado |

### Regras de Negócio
- O IP é obtido respeitando headers de proxy reverso (CF-Connecting-IP, X-Forwarded-For, X-Real-IP)
- Path e User-Agent são sanitizados e truncados antes do armazenamento
- A verificação é **fail-open**: se o banco master estiver indisponível, o sistema NÃO bloqueia (para não impedir acesso legítimo)
- IPs `127.0.0.1` e `::1` são whitelisted no script Lua (nunca bloqueados pela camada Nginx)
- Bloqueios expirados (`expires_at < NOW()`) são ignorados automaticamente nas consultas
- O método `blacklistIp()` usa UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) para atualizar IPs já presentes
- Registros antigos de `ip_404_hits` podem ser purgados via `IpGuard::purgeOldHits()` (recomendado via cron diário)

### Usuário MySQL para Lua (somente leitura)
O script Lua utiliza um usuário MySQL dedicado com acesso somente leitura à tabela `ip_blacklist`:
```sql
CREATE USER IF NOT EXISTS 'akti_guard'@'127.0.0.1' IDENTIFIED BY 'GuardR3ad0nly!@2026';
GRANT SELECT ON akti_master.ip_blacklist TO 'akti_guard'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### Integração com Nginx/OpenResty

#### Requisitos
- **OpenResty** (Nginx compilado com `ngx_http_lua_module`)
- **lua-resty-mysql** (incluído no OpenResty)
- Diretiva `lua_shared_dict ip_blacklist_cache 10m;` no bloco `http {}` do `nginx.conf`

#### Ativação
1. Copiar `nginx/ip_guard.lua` para `/etc/nginx/conf.d/ip_guard.lua` no servidor
2. Adicionar no bloco `server` do site:
   ```nginx
   access_by_lua_file /etc/nginx/conf.d/ip_guard.lua;
   ```
3. Adicionar no bloco `http {}` do `nginx.conf`:
   ```nginx
   lua_shared_dict ip_blacklist_cache 10m;
   ```
4. Testar e recarregar: `nginx -t && systemctl reload nginx`

#### Variáveis de Ambiente do Lua
| Variável | Default | Descrição |
|----------|---------|-----------|
| `AKTI_GUARD_DB_HOST` | `127.0.0.1` | Host do banco master |
| `AKTI_GUARD_DB_PORT` | `3306` | Porta do MySQL |
| `AKTI_GUARD_DB_NAME` | `akti_master` | Nome do banco master |
| `AKTI_GUARD_DB_USER` | `akti_guard` | Usuário MySQL (somente leitura) |
| `AKTI_GUARD_DB_PASSWORD` | — | Senha do usuário akti_guard |

### Arquivos do Módulo
- `sql/update_20260309_ip_blacklist.sql` — Migration: tabelas, índices e SQL para usuário akti_guard
- `app/models/IpGuard.php` — Model PHP: `register404Hit()`, `blacklistIp()`, `isBlacklisted()`, `purgeOldHits()`
- `nginx/ip_guard.lua` — Script Lua para OpenResty: consulta blacklist e bloqueia IPs na camada Nginx
- `nginx/sis-useakti` — Configuração Nginx atualizada com `access_by_lua_file`
- `index.php` — Integração no handler `default` (404) do switch de roteamento
