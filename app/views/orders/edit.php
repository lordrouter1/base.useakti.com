<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-edit me-2"></i>Editar Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></h1>
        <div class="d-flex gap-2">
            <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="btn btn-outline-info btn-sm"><i class="fas fa-stream me-1"></i> Ver no Pipeline</a>
            <a href="?page=orders" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        </div>
    </div>

    <?php
        // Info do pipeline para mostrar badge
        $pipelineStageMap = [
            'contato'    => ['label' => 'Contato',       'color' => '#9b59b6', 'icon' => 'fas fa-phone'],
            'orcamento'  => ['label' => 'Orçamento',     'color' => '#3498db', 'icon' => 'fas fa-file-invoice-dollar'],
            'venda'      => ['label' => 'Venda',         'color' => '#2ecc71', 'icon' => 'fas fa-handshake'],
            'producao'   => ['label' => 'Produção',      'color' => '#e67e22', 'icon' => 'fas fa-industry'],
            'preparacao' => ['label' => 'Preparação',    'color' => '#1abc9c', 'icon' => 'fas fa-boxes-packing'],
            'envio'      => ['label' => 'Envio/Entrega', 'color' => '#e74c3c', 'icon' => 'fas fa-truck'],
            'financeiro' => ['label' => 'Financeiro',    'color' => '#f39c12', 'icon' => 'fas fa-coins'],
            'concluido'  => ['label' => 'Concluído',     'color' => '#27ae60', 'icon' => 'fas fa-check-double'],
        ];
        $currentStage = $order['pipeline_stage'] ?? 'contato';
        $stageData = $pipelineStageMap[$currentStage] ?? ['label' => 'Contato', 'color' => '#999', 'icon' => 'fas fa-circle'];
    ?>

    <!-- Indicador da etapa atual no pipeline -->
    <div class="alert alert-light border shadow-sm mb-4 d-flex align-items-center justify-content-between">
        <div>
            <i class="fas fa-stream me-2 text-primary"></i>
            <strong>Etapa atual no pipeline:</strong>
            <span class="badge ms-2 px-3 py-2" style="background:<?= $stageData['color'] ?>;font-size:0.85rem;">
                <i class="<?= $stageData['icon'] ?> me-1"></i><?= $stageData['label'] ?>
            </span>
        </div>
        <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-eye me-1"></i> Gerenciar no Pipeline
        </a>
    </div>
    
    <form method="POST" action="?page=orders&action=update">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $order['id'] ?>">
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold"><i class="fas fa-user-tag me-2"></i>Dados do Pedido</legend>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Cliente</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Selecione um cliente...</option>
                                <?php foreach($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>" <?= $order['customer_id'] == $customer['id'] ? 'selected' : '' ?>><?= $customer['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="orcamento" <?= $order['status'] == 'orcamento' ? 'selected' : '' ?>>Orçamento</option>
                                <option value="Pendente" <?= $order['status'] == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                <option value="aprovado" <?= $order['status'] == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                <option value="em_producao" <?= $order['status'] == 'em_producao' ? 'selected' : '' ?>>Em Produção</option>
                                <option value="concluido" <?= $order['status'] == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                <option value="cancelado" <?= $order['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Prioridade</label>
                            <select class="form-select" name="priority">
                                <option value="baixa" <?= ($order['priority'] ?? '') == 'baixa' ? 'selected' : '' ?>>🟢 Baixa</option>
                                <option value="normal" <?= ($order['priority'] ?? 'normal') == 'normal' ? 'selected' : '' ?>>🔵 Normal</option>
                                <option value="alta" <?= ($order['priority'] ?? '') == 'alta' ? 'selected' : '' ?>>🟡 Alta</option>
                                <option value="urgente" <?= ($order['priority'] ?? '') == 'urgente' ? 'selected' : '' ?>>🔴 Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Valor Total (R$)</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control" name="total_amount" required value="<?= $order['total_amount'] ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Prazo de Entrega</label>
                            <input type="date" class="form-control" name="deadline" value="<?= $order['deadline'] ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Data de Criação</label>
                            <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>" disabled>
                        </div>
                    </div>
                </fieldset>

                <div class="text-end">
                    <a href="?page=orders" class="btn btn-secondary px-4 me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                </div>
            </div>
        </div>
    </form>

    <?php
    // Mostrar seção de produtos quando o pedido está na etapa de orçamento ou posterior (exceto contato)
    $showProducts = ($currentStage !== 'contato');
    ?>

    <?php if ($showProducts): ?>
    <div class="row mt-4">
        <div class="col-md-8 mx-auto">
            <!-- Itens do Orçamento -->
            <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Produtos do Orçamento
                    <a href="?page=orders&action=printQuote&id=<?= $order['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success ms-3">
                        <i class="fas fa-print me-1"></i> Imprimir Orçamento
                    </a>
                </legend>

                <!-- Tabela de Itens Existentes -->
                <?php if (!empty($orderItems)): ?>
                <div class="table-responsive mb-3">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center" style="width:100px;">Qtd</th>
                                <th class="text-end" style="width:130px;">Preço Unit.</th>
                                <th class="text-end" style="width:130px;">Subtotal</th>
                                <th class="text-center" style="width:80px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $totalItems = 0; ?>
                            <?php foreach ($orderItems as $item): ?>
                            <?php $subtotal = $item['quantity'] * $item['unit_price']; $totalItems += $subtotal; ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    <?php if (!empty($item['combination_label'])): ?>
                                    <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($item['combination_label']) ?></small>
                                    <?php elseif (!empty($item['grade_description'])): ?>
                                    <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($item['grade_description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end">R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                <td class="text-end fw-bold">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                <td class="text-center">
                                    <a href="?page=orders&action=deleteItem&item_id=<?= $item['id'] ?>&order_id=<?= $order['id'] ?>&redirect=orders" 
                                       class="btn btn-sm btn-outline-danger btn-delete-item" title="Remover item">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold fs-5">R$ <?= number_format($totalItems, 2, ',', '.') ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhum produto adicionado ao orçamento ainda.
                </div>
                <?php endif; ?>

                <!-- Formulário Adicionar Item -->
                <div class="card border-primary border-opacity-25">
                    <div class="card-header bg-primary bg-opacity-10 py-2">
                        <h6 class="mb-0 text-primary"><i class="fas fa-plus-circle me-2"></i>Adicionar Produto</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST" action="?page=orders&action=addItem" id="formAddItemEdit">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="redirect" value="orders">
                            <input type="hidden" name="combination_id" id="combinationIdEdit" value="">
                            <input type="hidden" name="grade_description" id="gradeDescriptionEdit" value="">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">Produto</label>
                                    <select class="form-select form-select-sm" name="product_id" id="productSelectEdit" required>
                                        <option value="">Selecione um produto...</option>
                                        <?php foreach ($products as $prod): 
                                            $displayPrice = isset($customerPrices[$prod['id']]) ? $customerPrices[$prod['id']] : $prod['price'];
                                        ?>
                                        <option value="<?= $prod['id'] ?>" data-price="<?= $displayPrice ?>" data-original-price="<?= $prod['price'] ?>"
                                                data-has-combos="<?= !empty($productCombinations[$prod['id']]) ? '1' : '0' ?>">
                                            <?= htmlspecialchars($prod['name']) ?> — R$ <?= number_format($displayPrice, 2, ',', '.') ?>
                                            <?php if (isset($customerPrices[$prod['id']]) && $customerPrices[$prod['id']] != $prod['price']): ?>
                                            (base: R$ <?= number_format($prod['price'], 2, ',', '.') ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <!-- Seletor de variação (aparece dinamicamente) -->
                                    <div id="variationWrapEdit" class="mt-1" style="display:none;">
                                        <select class="form-select form-select-sm" id="variationSelectEdit">
                                            <option value="">Selecione a variação...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Quantidade</label>
                                    <input type="number" min="1" class="form-control form-control-sm" name="quantity" id="qtyInputEdit" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Preço Unitário</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" name="unit_price" id="priceInputEdit" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-plus me-1"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>

    <script>
    // Product combinations data from server
    const productCombosEdit = <?= json_encode($productCombinations ?? []) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        // Auto-preencher preço ao selecionar produto
        const productSelect = document.getElementById('productSelectEdit');
        const priceInput = document.getElementById('priceInputEdit');
        const variationWrap = document.getElementById('variationWrapEdit');
        const variationSelect = document.getElementById('variationSelectEdit');
        const combinationIdInput = document.getElementById('combinationIdEdit');
        const gradeDescInput = document.getElementById('gradeDescriptionEdit');

        if (productSelect && priceInput) {
            productSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.dataset.price) {
                    priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
                }
                // Show/hide variation selector
                const pid = this.value;
                combinationIdInput.value = '';
                gradeDescInput.value = '';
                if (pid && productCombosEdit[pid] && productCombosEdit[pid].length > 0) {
                    variationWrap.style.display = '';
                    variationSelect.innerHTML = '<option value="">Selecione a variação...</option>';
                    productCombosEdit[pid].forEach(c => {
                        const lbl = c.combination_label + (c.price_override ? ' — R$ ' + parseFloat(c.price_override).toFixed(2).replace('.', ',') : '');
                        variationSelect.innerHTML += `<option value="${c.id}" data-price="${c.price_override || ''}" data-label="${c.combination_label}">${lbl}</option>`;
                    });
                } else {
                    variationWrap.style.display = 'none';
                    variationSelect.innerHTML = '';
                }
            });

            if (variationSelect) {
                variationSelect.addEventListener('change', function() {
                    const opt = this.options[this.selectedIndex];
                    combinationIdInput.value = this.value;
                    gradeDescInput.value = opt ? (opt.dataset.label || '') : '';
                    // Override price if combination has specific price
                    if (opt && opt.dataset.price && opt.dataset.price !== '') {
                        priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
                    }
                });
            }
        }
        // Confirmar remoção de item
        document.querySelectorAll('.btn-delete-item').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.href;
                Swal.fire({
                    title: 'Remover item?',
                    text: 'O item será removido do orçamento.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-trash me-1"></i> Remover',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#e74c3c'
                }).then(r => { if (r.isConfirmed) window.location.href = href; });
            });
        });
    });
    </script>
    <?php endif; ?>
</div>
