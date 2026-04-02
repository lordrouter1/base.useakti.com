# Estudo de Melhorias — Ferramentas do Akti

> **Data:** 2026-04-02  
> **Escopo:** Melhorias nos módulos de Relatórios Customizáveis, Automação de Workflows e Menu de Ferramentas  
> **Arquivos impactados:** 11 (3 controllers, 3 models/services, 4 views, 1 config)

---

## Índice

1. [Menu: Adicionar Site Builder ao Ferramentas](#1-menu-adicionar-site-builder-ao-ferramentas)
2. [Relatórios: Dicionário de Colunas](#2-relatórios-dicionário-de-colunas)
3. [Workflows: Campos por Evento (Select2)](#3-workflows-campos-por-evento-select2)
4. [Workflows: Tabela Drag & Drop com Prioridade](#4-workflows-tabela-drag--drop-com-prioridade)
5. [Workflows: Inputs de Ações Específicos + Tags](#5-workflows-inputs-de-ações-específicos--tags)
6. [Resumo de Alterações por Arquivo](#6-resumo-de-alterações-por-arquivo)
7. [Diagrama de Fluxo](#7-diagrama-de-fluxo)


---

## 1. Menu: Adicionar Site Builder ao Ferramentas

### Problema
O Site Builder (`?page=site_builder`) existe como rota mas **não aparece no submenu** do grupo "Ferramentas" em `app/config/menu.php`.

### Solução
Adicionar `site_builder` como filho do grupo `ferramentas`.

### Alteração

**Arquivo:** `app/config/menu.php`

```php
// ─── Grupo: Ferramentas ───
'ferramentas' => [
    'label'    => 'Ferramentas',
    'icon'     => 'fas fa-toolbox',
    'menu'     => true,
    'children' => [
+       'site_builder' => [
+           'label'      => 'Site Builder',
+           'icon'       => 'fas fa-palette',
+           'menu'       => true,
+           'permission' => true,
+       ],
        'custom_reports' => [ ... ],
        'workflows' => [ ... ],
        // ...
    ],
],
```

### Impacto
- Nenhum efeito colateral — o Site Builder já tem controller e rota registrada.
- Permissão `permission => true` garante controle por grupo de usuário.

---

## 2. Relatórios: Dicionário de Colunas

### Problema Atual
Na criação e exibição de relatórios customizados, os nomes das colunas são exibidos **crus** (ex: `customer_name`, `total_amount`, `created_at`). Isso:
- Expõe a estrutura interna do banco para o usuário
- Torna a interface confusa para operadores não-técnicos
- Não fornece contexto sobre o que cada coluna representa

### Solução: Dicionário Centralizado

Criar um **dicionário de colunas** no `ReportTemplate` model que mapeia cada coluna para:
- `label`: Nome amigável para exibição
- `description`: Texto explicativo exibido via tooltip/popover (ícone `ℹ️` ao lado)

### Alterações

#### 2.1 — Model `ReportTemplate.php` → Novo método `getColumnDictionary()`

```php
public function getAvailableEntities(): array
{
    return [
        'orders' => [
            'label' => 'Pedidos',
            'columns' => [
                'id' => [
                    'label' => 'Código',
                    'description' => 'Identificador único do pedido no sistema.',
                ],
                'customer_name' => [
                    'label' => 'Cliente',
                    'description' => 'Nome do cliente associado ao pedido.',
                ],
                'total' => [
                    'label' => 'Valor Total',
                    'description' => 'Soma de todos os itens do pedido (R$).',
                ],
                'status' => [
                    'label' => 'Status',
                    'description' => 'Situação atual: pendente, em produção, concluído ou cancelado.',
                ],
                'created_at' => [
                    'label' => 'Data de Criação',
                    'description' => 'Data e hora em que o pedido foi registrado.',
                ],
                'delivery_date' => [
                    'label' => 'Previsão de Entrega',
                    'description' => 'Data prevista para entrega ao cliente.',
                ],
            ],
        ],
        'customers' => [
            'label' => 'Clientes',
            'columns' => [
                'id'         => ['label' => 'Código', 'description' => 'Identificador único do cliente.'],
                'name'       => ['label' => 'Nome', 'description' => 'Nome completo ou razão social.'],
                'email'      => ['label' => 'E-mail', 'description' => 'E-mail principal de contato.'],
                'phone'      => ['label' => 'Telefone', 'description' => 'Telefone principal com DDD.'],
                'document'   => ['label' => 'CPF/CNPJ', 'description' => 'Documento fiscal do cliente.'],
                'city'       => ['label' => 'Cidade', 'description' => 'Cidade do endereço principal.'],
                'state'      => ['label' => 'Estado', 'description' => 'UF do endereço (sigla com 2 letras).'],
                'created_at' => ['label' => 'Cadastro', 'description' => 'Data de cadastro no sistema.'],
            ],
        ],
        'products' => [
            'label' => 'Produtos',
            'columns' => [
                'id'            => ['label' => 'Código', 'description' => 'Identificador único do produto.'],
                'name'          => ['label' => 'Nome', 'description' => 'Nome comercial do produto.'],
                'sku'           => ['label' => 'SKU', 'description' => 'Código de referência interno (Stock Keeping Unit).'],
                'price'         => ['label' => 'Preço', 'description' => 'Preço unitário de venda (R$).'],
                'category_name' => ['label' => 'Categoria', 'description' => 'Categoria principal do produto.'],
                'stock_qty'     => ['label' => 'Estoque', 'description' => 'Quantidade em estoque atual.'],
                'created_at'    => ['label' => 'Cadastro', 'description' => 'Data de cadastro do produto.'],
            ],
        ],
        'financial' => [
            'label' => 'Financeiro',
            'columns' => [
                'id'          => ['label' => 'Código', 'description' => 'Identificador da transação.'],
                'type'        => ['label' => 'Tipo', 'description' => 'Receita (income) ou Despesa (expense).'],
                'amount'      => ['label' => 'Valor', 'description' => 'Valor da transação (R$).'],
                'category'    => ['label' => 'Categoria', 'description' => 'Categoria contábil (ex: vendas, material).'],
                'description' => ['label' => 'Descrição', 'description' => 'Texto descritivo da transação.'],
                'date'        => ['label' => 'Data', 'description' => 'Data de competência da transação.'],
                'status'      => ['label' => 'Status', 'description' => 'Estado: pendente, pago, cancelado.'],
            ],
        ],
        'suppliers' => [
            'label' => 'Fornecedores',
            'columns' => [
                'id'           => ['label' => 'Código', 'description' => 'Identificador do fornecedor.'],
                'company_name' => ['label' => 'Razão Social', 'description' => 'Nome empresarial do fornecedor.'],
                'document'     => ['label' => 'CNPJ', 'description' => 'CNPJ do fornecedor.'],
                'email'        => ['label' => 'E-mail', 'description' => 'E-mail de contato comercial.'],
                'phone'        => ['label' => 'Telefone', 'description' => 'Telefone de contato.'],
                'city'         => ['label' => 'Cidade', 'description' => 'Cidade do fornecedor.'],
                'status'       => ['label' => 'Situação', 'description' => 'Ativo ou inativo.'],
            ],
        ],
        'quotes' => [
            'label' => 'Orçamentos',
            'columns' => [
                'id'            => ['label' => 'Código', 'description' => 'Identificador do orçamento.'],
                'customer_name' => ['label' => 'Cliente', 'description' => 'Nome do cliente solicitante.'],
                'total'         => ['label' => 'Valor Total', 'description' => 'Valor total do orçamento (R$).'],
                'status'        => ['label' => 'Status', 'description' => 'Situação: rascunho, enviado, aprovado, recusado, convertido.'],
                'valid_until'   => ['label' => 'Validade', 'description' => 'Data limite para aprovação do orçamento.'],
                'created_at'    => ['label' => 'Criação', 'description' => 'Data de criação do orçamento.'],
            ],
        ],
    ];
}
```

**Mudança de Breaking:** O formato de `columns` muda de `['id', 'name', ...]` (array indexado) para `['id' => ['label' => '...', 'description' => '...'], ...]` (array associativo).

Todos os consumidores precisam ser adaptados:
- `getAvailableEntities()` → retorna dicionários
- `executeReport()` → usa `array_keys($columns)` para obter chaves
- Views `form.php` e `results.php` → usam label/description

#### 2.2 — View `custom_reports/form.php` → Colunas com labels traduzidas

Substituir o checkbox que mostra `col` raw pelo label amigável + ícone info:

```javascript
function loadColumns(entity) {
    columnsList.innerHTML = '';
    if (!entity || !entities[entity]) return;
    const cols = entities[entity].columns || {};
    
    Object.entries(cols).forEach(([key, meta]) => {
        const checked = selectedCols.includes(key) ? 'checked' : '';
        const label = meta.label || key;
        const desc = meta.description || '';
        
        columnsList.innerHTML += `
            <div class="col-md-3">
                <div class="form-check d-flex align-items-center">
                    <input class="form-check-input colCheck" type="checkbox" value="${key}" ${checked}>
                    <label class="form-check-label ms-1">${label}</label>
                    ${desc ? `<i class="fas fa-info-circle text-muted ms-1" 
                        style="cursor:help;font-size:.75rem" 
                        data-bs-toggle="tooltip" 
                        title="${desc}"></i>` : ''}
                </div>
            </div>`;
    });
    
    // Inicializar tooltips do Bootstrap
    const tooltips = columnsList.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
}
```

#### 2.3 — View `custom_reports/results.php` → Cabeçalhos traduzidos com descrição

```php
<?php
// Resolver dicionário da entidade
$entityInfo = (new \Akti\Models\ReportTemplate($db ?? (new \Database())->getConnection()))
    ->getAvailableEntities()[$template['entity']] ?? [];
$columnDict = $entityInfo['columns'] ?? [];
?>
<thead class="table-light">
    <tr>
        <?php foreach ($cols as $col): ?>
        <th>
            <?= e($columnDict[$col]['label'] ?? $col) ?>
            <?php if (!empty($columnDict[$col]['description'])): ?>
            <i class="fas fa-info-circle text-muted ms-1" 
               style="cursor:help;font-size:.75rem" 
               data-bs-toggle="tooltip" 
               title="<?= eAttr($columnDict[$col]['description']) ?>"></i>
            <?php endif; ?>
        </th>
        <?php endforeach; ?>
    </tr>
</thead>
```

**O nome técnico da coluna (`customer_name`) nunca aparece para o usuário** — apenas `label` ("Cliente").

#### 2.4 — Model `ReportTemplate.php` → `executeReport()` adaptação

```php
// Linha que gera safeColumns deve usar array_keys() ao invés de intersect direto:
$entityCols = $this->getAvailableEntities()[$entity]['columns'] ?? [];
$allowedColumns = array_keys($entityCols);
$safeColumns = array_intersect($columns, $allowedColumns);
```

#### 2.5 — Export CSV deve usar labels

No JS de export, criar header com labels ao invés de nomes técnicos. O controller pode passar o dict via variável PHP para JS ou usar `data-label` nos `<th>`.

### Impacto
- **UX:** Usuário nunca vê nome de coluna do banco — tudo é label amigável
- **Segurança:** Oculta a estrutura interna do schema
- **Breaking change:** Formato de `getAvailableEntities()` muda — todas as views dependentes precisam adaptação

---

## 3. Workflows: Campos por Evento (Select2)

### Problema Atual
No formulário de workflow (`form.php`), ao criar uma condição, o campo é um **input de texto livre**:
```html
<input class="form-control" placeholder="Campo" value="${c.field||''}">
```
O usuário precisa **adivinhar** o nome do campo (ex: `total_amount`, `customer_id`). Isso causa:
- Erros de digitação que fazem a regra nunca disparar
- Confusão sobre quais campos estão disponíveis por evento
- Nenhum feedback sobre campos válidos

### Solução: Mapeamento de Campos por Evento + Select2

Criar um **registro de campos disponíveis por evento** no controller/model, e na view usar **Select2** para que o usuário selecione o campo correto.

### Alterações

#### 3.1 — Controller `WorkflowController.php` → Novo método `getEventFields()`

```php
private function getEventFields(): array
{
    return [
        'model.order.created' => [
            'id'             => ['label' => 'ID do Pedido', 'type' => 'int'],
            'customer_id'    => ['label' => 'ID do Cliente', 'type' => 'int'],
            'total_amount'   => ['label' => 'Valor Total (R$)', 'type' => 'decimal'],
            'pipeline_stage' => ['label' => 'Etapa do Pipeline', 'type' => 'string'],
        ],
        'model.order.updated' => [
            'id'           => ['label' => 'ID do Pedido', 'type' => 'int'],
            'customer_id'  => ['label' => 'ID do Cliente', 'type' => 'int'],
            'total_amount' => ['label' => 'Valor Total (R$)', 'type' => 'decimal'],
            'status'       => ['label' => 'Status', 'type' => 'string'],
        ],
        'model.order.stage_changed' => [
            'id'         => ['label' => 'ID do Pedido', 'type' => 'int'],
            'from_stage' => ['label' => 'Etapa Anterior', 'type' => 'string'],
            'to_stage'   => ['label' => 'Nova Etapa', 'type' => 'string'],
            'user_id'    => ['label' => 'Usuário que Moveu', 'type' => 'int'],
        ],
        'model.customer.created' => [
            'id'    => ['label' => 'ID do Cliente', 'type' => 'int'],
            'name'  => ['label' => 'Nome', 'type' => 'string'],
            'email' => ['label' => 'E-mail', 'type' => 'string'],
            'code'  => ['label' => 'Código', 'type' => 'string'],
        ],
        'model.customer.updated' => [
            'id'    => ['label' => 'ID do Cliente', 'type' => 'int'],
            'name'  => ['label' => 'Nome', 'type' => 'string'],
            'email' => ['label' => 'E-mail', 'type' => 'string'],
        ],
        'model.installment.paid' => [
            'installment_id' => ['label' => 'ID da Parcela', 'type' => 'int'],
            'order_id'       => ['label' => 'ID do Pedido', 'type' => 'int'],
            'paid_amount'    => ['label' => 'Valor Pago (R$)', 'type' => 'decimal'],
            'auto_confirmed' => ['label' => 'Confirmação Automática', 'type' => 'bool'],
            'user_id'        => ['label' => 'Usuário', 'type' => 'int'],
        ],
        'model.installment.overdue' => [
            'installment_id' => ['label' => 'ID da Parcela', 'type' => 'int'],
            'order_id'       => ['label' => 'ID do Pedido', 'type' => 'int'],
            'due_date'       => ['label' => 'Data de Vencimento', 'type' => 'date'],
            'amount'         => ['label' => 'Valor (R$)', 'type' => 'decimal'],
        ],
        'model.supplier.created' => [
            'id'   => ['label' => 'ID do Fornecedor', 'type' => 'int'],
            'name' => ['label' => 'Nome', 'type' => 'string'],
        ],
        'model.quote.created' => [
            'id' => ['label' => 'ID do Orçamento', 'type' => 'int'],
        ],
        'model.quote.approved' => [
            'id' => ['label' => 'ID do Orçamento', 'type' => 'int'],
        ],
        'model.nfe_document.authorized' => [
            'id'       => ['label' => 'ID do Documento', 'type' => 'int'],
            'order_id' => ['label' => 'ID do Pedido', 'type' => 'int'],
            'numero'   => ['label' => 'Número da NF-e', 'type' => 'string'],
        ],
        'auth.login.failed' => [
            'ip'       => ['label' => 'IP do Acesso', 'type' => 'string'],
            'username' => ['label' => 'Usuário Tentado', 'type' => 'string'],
        ],
    ];
}
```

Este mapeamento é derivado **diretamente** dos payloads do `EventDispatcher::dispatch()` nos models (Order.php, Customer.php, Pipeline.php, Installment.php, Supplier.php, Quote.php, NfeDocument.php).

#### 3.2 — Controller → Passar `eventFields` para a view

```php
public function create()
{
    $rule = null;
    $availableEvents = $this->getAvailableEvents();
    $eventFields = $this->getEventFields();  // ← novo
    
    require 'app/views/layout/header.php';
    require 'app/views/workflows/form.php';
    require 'app/views/layout/footer.php';
}

public function edit()
{
    // ... (mesmo padrão)
    $eventFields = $this->getEventFields();  // ← novo
    // ...
}
```

#### 3.3 — View `workflows/form.php` → Select2 para campo de condição

```javascript
const eventFields = <?= json_encode($eventFields ?? []) ?>;

// Ao mudar o evento, atualizar info disponível
document.querySelector('[name="event"]').addEventListener('change', function() {
    updateFieldInfo(this.value);
});

function updateFieldInfo(event) {
    const fields = eventFields[event] || {};
    // Mostrar alerta informativo com os campos disponíveis
    const infoBox = document.getElementById('eventFieldsInfo');
    if (Object.keys(fields).length === 0) {
        infoBox.innerHTML = '<div class="alert alert-warning py-2"><i class="fas fa-info-circle me-1"></i>Este evento não possui campos mapeados para condições.</div>';
    } else {
        let badges = Object.entries(fields).map(([key, meta]) =>
            `<span class="badge bg-light text-dark border me-1 mb-1" title="${meta.label}">
                <code>${key}</code> <small class="text-muted">(${meta.type})</small>
            </span>`
        ).join('');
        infoBox.innerHTML = `<div class="alert alert-info py-2">
            <i class="fas fa-lightbulb me-1"></i><strong>Campos disponíveis:</strong><br>${badges}
        </div>`;
    }
    
    // Atualizar todos os selects de condição com os novos campos
    refreshConditionSelects(fields);
}

function renderConditions() {
    condList.innerHTML = '';
    const currentEvent = document.querySelector('[name="event"]').value;
    const fields = eventFields[currentEvent] || {};
    const fieldOptions = Object.entries(fields).map(([key, meta]) =>
        `<option value="${key}">${meta.label}</option>`
    ).join('');
    
    conditions.forEach((c, i) => {
        condList.innerHTML += `<div class="row g-2 mb-2 align-items-center">
            <div class="col-md-3">
                <select class="form-select form-select-sm condFieldSelect" 
                        data-idx="${i}" style="width:100%">
                    <option value="">Selecione o campo...</option>
                    ${fieldOptions}
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" 
                        onchange="window.__conds[${i}].operator=this.value">
                    <option value="==" ${c.operator=='=='?'selected':''}>Igual</option>
                    <option value="!=" ${c.operator=='!='?'selected':''}>Diferente</option>
                    <option value=">"  ${c.operator=='>'?'selected':''}>Maior</option>
                    <option value="<"  ${c.operator=='<'?'selected':''}>Menor</option>
                    <option value="contains" ${c.operator=='contains'?'selected':''}>Contém</option>
                </select>
            </div>
            <div class="col-md-3">
                <input class="form-control form-control-sm" placeholder="Valor" 
                       value="${c.value||''}" 
                       onchange="window.__conds[${i}].value=this.value">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="window.__conds.splice(${i},1);renderConditions()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    });
    
    window.__conds = conditions;
    
    // Inicializar Select2 nos selects de campo + setar valor atual
    document.querySelectorAll('.condFieldSelect').forEach(sel => {
        const idx = parseInt(sel.dataset.idx);
        sel.value = conditions[idx]?.field || '';
        $(sel).select2({
            placeholder: 'Selecione o campo...',
            allowClear: true,
            width: '100%'
        }).on('change', function() {
            window.__conds[idx].field = this.value;
        });
    });
}
```

**Novo bloco HTML** entre o select de evento e as condições:
```html
<div id="eventFieldsInfo" class="mt-3"></div>
```

### Impacto
- O usuário **nunca precisa digitar** o nome do campo — seleciona de uma lista curada
- Select2 permite busca por texto (ex: digitar "valor" encontra "Valor Total (R$)")
- A box informativa mostra todos os campos disponíveis ao selecionar o evento
- Campos que não existem no payload não podem ser selecionados (elimina configs quebradas)

---

## 4. Workflows: Tabela Drag & Drop com Prioridade

### Problema Atual
A prioridade é definida por um **campo numérico manual** no formulário. Na listagem, a coluna de prioridade mostra o número mas o usuário **não consegue reorde ordenar visualmente**. Isso é contraintuitivo — o usuário espera arrastar para definir prioridade.

### Solução: SortableJS na tabela de listagem

Usar **SortableJS** (CDN, ~8KB gzip) para tornar as linhas da tabela arrastáveis. Ao soltar, enviar AJAX com a nova ordem. A prioridade passa a ser calculada pela posição na lista.

### Alterações

#### 4.1 — View `workflows/index.php` → Adicionar Drag & Drop

```html
<!-- CDN SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<table class="table table-hover mb-0" id="rulesTable">
    <thead class="table-light">
        <tr>
+           <th style="width:30px"><i class="fas fa-grip-vertical text-muted"></i></th>
            <th>Nome</th>
            <th>Evento</th>
-           <th>Prioridade</th>
+           <th style="width:50px">#</th>
            <th>Execuções</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
        </tr>
    </thead>
    <tbody id="rulesBody">
        <?php foreach ($rules as $idx => $r): ?>
        <tr data-id="<?= (int) $r['id'] ?>">
+           <td class="drag-handle" style="cursor:grab">
+               <i class="fas fa-grip-vertical text-muted"></i>
+           </td>
            <td>
                <div class="fw-bold"><?= e($r['name']) ?></div>
                ...
            </td>
            <td><code><?= e($r['event']) ?></code></td>
+           <td><span class="badge bg-light text-dark priority-badge"><?= $idx + 1 ?></span></td>
            ...
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

```javascript
// Inicializar Sortable na tbody
const sortable = Sortable.create(document.getElementById('rulesBody'), {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'table-active',
    onEnd: function(evt) {
        // Atualizar badges de posição
        document.querySelectorAll('.priority-badge').forEach((badge, i) => {
            badge.textContent = i + 1;
        });
        
        // Enviar nova ordem via AJAX
        const order = [];
        document.querySelectorAll('#rulesBody tr[data-id]').forEach((row, i) => {
            order.push({ id: parseInt(row.dataset.id), priority: i });
        });
        
        fetch('?page=workflows&action=reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ order: order })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                AktiToast.success('Prioridade atualizada');
            }
        });
    }
});
```

#### 4.2 — Controller `WorkflowController.php` → Nova action `reorder()`

```php
public function reorder()
{
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    foreach ($order as $item) {
        $id = (int) ($item['id'] ?? 0);
        $priority = (int) ($item['priority'] ?? 0);
        if ($id > 0) {
            $this->model->updatePriority($id, $priority);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
```

#### 4.3 — Model `WorkflowRule.php` → Novo método `updatePriority()`

```php
public function updatePriority(int $id, int $priority): bool
{
    $stmt = $this->conn->prepare(
        "UPDATE workflow_rules SET priority = :priority WHERE id = :id"
    );
    return $stmt->execute([':priority' => $priority, ':id' => $id]);
}
```

#### 4.4 — Rota `routes.php` → Adicionar action `reorder`

```php
'workflows' => [
    // ...
    'actions' => [
        // ... existentes ...
        'reorder' => 'reorder',  // ← novo
    ],
],
```

#### 4.5 — Remover campo de prioridade numérico do formulário

No `form.php`, o campo de prioridade (`<input type="number" name="priority">`) pode ser **removido ou tornando hidden** (valor padrão = 0, a posição real é definida pelo drag). 

### Impacto
- **UX muito superior** — arrastar para reordenar é intuitivo
- A regra no topo da tabela tem prioridade máxima (priority = 0)
- O campo numérico sai do formulário — a prioridade é definida exclusivamente pela posição
- Persistência via AJAX — sem reload da página

---

## 5. Workflows: Inputs de Ações Específicos + Tags

### Problema Atual
Todas as ações têm os mesmos inputs genéricos:
```html
<select> Tipo: Notificar / E-mail / Log / Atualizar campo </select>
<input placeholder="Mensagem/Destino">
```
Isso é insuficiente porque:
- **E-mail** precisa de: destinatário, assunto, corpo (com suporte a tags)
- **Notificar** precisa de: título, mensagem, usuário destino
- **Atualizar campo** precisa de: tabela, coluna, novo valor
- **Log** precisa apenas de: mensagem
- O usuário não tem como inserir **tags dinâmicas** (ex: `{{customer_name}}`, `{{total_amount}}`)

### Solução: Inputs condicionais por tipo + Seletor de Tags

Ao selecionar o tipo de ação, exibir campos específicos. Oferecer botões de tags que inserem placeholders `{{campo}}` no texto.

### Alterações

#### 5.1 — View `workflows/form.php` → `renderActions()` com campos dinâmicos

```javascript
function renderActions() {
    actList.innerHTML = '';
    const currentEvent = document.querySelector('[name="event"]').value;
    const fields = eventFields[currentEvent] || {};
    
    // Gerar botões de tags disponíveis
    const tagButtons = Object.entries(fields).map(([key, meta]) =>
        `<button type="button" class="btn btn-sm btn-outline-secondary tag-btn me-1 mb-1" 
                 data-tag="{{${key}}}" title="${meta.label}">
            {{${key}}}
        </button>`
    ).join('');
    
    actions.forEach((a, i) => {
        const type = a.type || 'notify';
        
        let specificFields = '';
        
        switch (type) {
            case 'email':
                specificFields = `
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Destinatário</label>
                        <input class="form-control form-control-sm" placeholder="email@exemplo.com ou {{email}}" 
                               value="${a.to || ''}" id="actTo_${i}"
                               onchange="window.__acts[${i}].to=this.value">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Assunto</label>
                        <input class="form-control form-control-sm" placeholder="Assunto do e-mail"
                               value="${a.subject || ''}" id="actSubject_${i}"
                               onchange="window.__acts[${i}].subject=this.value">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-muted">Corpo do E-mail</label>
                        <textarea class="form-control form-control-sm" rows="3" 
                                  placeholder="Use tags como {{customer_name}} para personalizar" id="actBody_${i}"
                                  onchange="window.__acts[${i}].body=this.value">${a.body || ''}</textarea>
                        <div class="mt-1"><small class="text-muted">Tags disponíveis:</small> <span class="tag-target" data-target="actBody_${i}">${tagButtons}</span></div>
                    </div>`;
                break;
                
            case 'notify':
                specificFields = `
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Usuário (ID ou {{user_id}})</label>
                        <input class="form-control form-control-sm" placeholder="ID do usuário"
                               value="${a.user_id || ''}" 
                               onchange="window.__acts[${i}].user_id=this.value">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Título</label>
                        <input class="form-control form-control-sm" placeholder="Título da notificação"
                               value="${a.title || ''}" id="actTitle_${i}"
                               onchange="window.__acts[${i}].title=this.value">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-muted">Mensagem</label>
                        <textarea class="form-control form-control-sm" rows="2"
                                  placeholder="Mensagem da notificação" id="actMsg_${i}"
                                  onchange="window.__acts[${i}].message=this.value">${a.message || ''}</textarea>
                        <div class="mt-1"><small class="text-muted">Tags disponíveis:</small> <span class="tag-target" data-target="actMsg_${i}">${tagButtons}</span></div>
                    </div>`;
                break;
                
            case 'update_field':
                specificFields = `
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tabela</label>
                        <select class="form-select form-select-sm" onchange="window.__acts[${i}].table=this.value">
                            <option value="">Selecione...</option>
                            <option value="orders" ${a.table==='orders'?'selected':''}>Pedidos</option>
                            <option value="customers" ${a.table==='customers'?'selected':''}>Clientes</option>
                            <option value="products" ${a.table==='products'?'selected':''}>Produtos</option>
                            <option value="suppliers" ${a.table==='suppliers'?'selected':''}>Fornecedores</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Coluna</label>
                        <input class="form-control form-control-sm" placeholder="nome_da_coluna"
                               value="${a.column || ''}" 
                               onchange="window.__acts[${i}].column=this.value">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Novo Valor</label>
                        <input class="form-control form-control-sm" placeholder="Valor ou {{tag}}"
                               value="${a.value || ''}" 
                               onchange="window.__acts[${i}].value=this.value">
                    </div>`;
                break;
                
            case 'log':
                specificFields = `
                    <div class="col-12">
                        <label class="form-label small text-muted">Mensagem do Log</label>
                        <textarea class="form-control form-control-sm" rows="2"
                                  placeholder="Mensagem de log" id="actLog_${i}"
                                  onchange="window.__acts[${i}].message=this.value">${a.message || ''}</textarea>
                        <div class="mt-1"><small class="text-muted">Tags disponíveis:</small> <span class="tag-target" data-target="actLog_${i}">${tagButtons}</span></div>
                    </div>`;
                break;
        }
        
        actList.innerHTML += `
            <div class="card mb-2 border">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Tipo de Ação</label>
                            <select class="form-select form-select-sm actionTypeSelect" data-idx="${i}"
                                    onchange="window.__acts[${i}].type=this.value; renderActions();">
                                <option value="notify" ${type==='notify'?'selected':''}>🔔 Notificar</option>
                                <option value="email" ${type==='email'?'selected':''}>📧 E-mail</option>
                                <option value="log" ${type==='log'?'selected':''}>📋 Log</option>
                                <option value="update_field" ${type==='update_field'?'selected':''}>✏️ Atualizar Campo</option>
                            </select>
                        </div>
                        ${specificFields}
                        <div class="col-auto ms-auto">
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="window.__acts.splice(${i},1);renderActions()">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
    });
    
    window.__acts = actions;
    
    // Listener para botões de tag — inserir no campo alvo
    document.querySelectorAll('.tag-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tag = this.dataset.tag;
            const targetId = this.closest('.tag-target')?.dataset.target;
            if (targetId) {
                const input = document.getElementById(targetId);
                if (input) {
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const text = input.value;
                    input.value = text.substring(0, start) + tag + text.substring(end);
                    input.focus();
                    input.setSelectionRange(start + tag.length, start + tag.length);
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
    });
}
```

### Impacto
- Cada tipo de ação tem seus **campos específicos** (e-mail: to/subject/body; notify: user_id/title/message; etc.)
- Botões de **tags clicáveis** (`{{customer_name}}`, `{{total_amount}}`) que inserem no cursor do campo ativo
- Tags são derivadas do evento selecionado — mudar o evento atualiza as tags disponíveis
- Visual mais organizado com cards por ação

### Integração com o WorkflowEngine
O `WorkflowEngine::interpolate()` já suporta a substituição de `{{campo}}` por valores do payload. A estrutura de dados das ações muda de:
```json
// Antes (genérico)
{"type": "email", "message": "texto"}

// Depois (específico)
{"type": "email", "to": "{{email}}", "subject": "Pedido {{id}} criado", "body": "Olá {{name}}, seu pedido..."}
```
O engine já lê `$action['to']`, `$action['subject']`, `$action['body']` no método `actionEmail()`, então **não precisa** de alteração no backend.

---

## 6. Resumo de Alterações por Arquivo

| Arquivo | Mudança | Complexidade |
|---|---|---|
| `app/config/menu.php` | Adicionar `site_builder` ao grupo Ferramentas | Baixa |
| `app/models/ReportTemplate.php` | Dicionário de colunas com label/description; adaptar `executeReport()` | Média |
| `app/views/custom_reports/form.php` | Colunas com labels + tooltip info; Select2-like checkboxes | Média |
| `app/views/custom_reports/results.php` | Cabeçalhos traduzidos + ícone info; CSV export com labels | Média |
| `app/controllers/WorkflowController.php` | `getEventFields()`, `reorder()`, passar `$eventFields` | Média |
| `app/models/WorkflowRule.php` | `updatePriority()` | Baixa |
| `app/views/workflows/index.php` | SortableJS drag & drop, handle column, AJAX reorder | Média |
| `app/views/workflows/form.php` | Select2 para campos, inputs específicos por tipo, tags | Alta |
| `app/config/routes.php` | Adicionar action `reorder` em workflows | Baixa |

**Total: 9 arquivos, ~4 novas funções/métodos, ~200 linhas de JS refactored**

---

## 7. Diagrama de Fluxo

### Workflow — Nova Regra (fluxo melhorado)

```
Usuário → [Nova Regra]
            │
            ├─ 1. Preenche nome, seleciona Evento Gatilho (select)
            │      └─→ Sistema exibe box: "Campos disponíveis: id, customer_name, total_amount..."
            │
            ├─ 2. Adiciona Condição
            │      ├─ Campo: [Select2 com campos do evento] (ex: "Valor Total (R$)")
            │      ├─ Operador: [Igual / Diferente / Maior / Menor / Contém]
            │      └─ Valor: [input texto/número]
            │
            ├─ 3. Adiciona Ação (tipo específico)
            │      ├─ E-mail → [Para] [Assunto] [Corpo] + botões {{tag}}
            │      ├─ Notificar → [Usuário ID] [Título] [Mensagem] + botões {{tag}}
            │      ├─ Atualizar Campo → [Tabela] [Coluna] [Valor]
            │      └─ Log → [Mensagem] + botões {{tag}}
            │
            └─ 4. Salva → redireciona para listagem
                          └─ Drag & drop para definir prioridade

```

### Relatório — Novo Relatório (fluxo melhorado)

```
Usuário → [Novo Relatório]
            │
            ├─ 1. Preenche nome, seleciona Entidade Base (select)
            │
            ├─ 2. Seleciona colunas (checkboxes com LABELS amigáveis)
            │      └─ Cada checkbox: [✓] Nome do Cliente ℹ️
            │                          └─ Tooltip: "Nome do cliente associado ao pedido"
            │
            ├─ 3. Salva e Executa
            │
            └─ 4. Visualiza resultados
                   ├─ Cabeçalhos: "Cliente" (não "customer_name") + ℹ️ tooltip
                   └─ CSV export com labels nos headers
```

---

## Dependências Externas

| Biblioteca | Uso | CDN |
|---|---|---|
| SortableJS | Drag & drop na tabela de workflows | `cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js` |
| Select2 | Já carregado no sistema (footer.php) | ✅ Disponível |
| Bootstrap Tooltips | Info icons nos relatórios | ✅ Disponível (Bootstrap 5) |

Nenhuma nova dependência necessária — SortableJS é a única adição (~8KB gzipped, MIT license).
