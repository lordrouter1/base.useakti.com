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
                            <select class="form-select customer-select" name="customer_id" required data-placeholder="Digite para buscar um cliente...">
                                <option value="">Selecione um cliente...</option>
                                <?php foreach($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>" <?= $order['customer_id'] == $customer['id'] ? 'selected' : '' ?>><?= e($customer['name']) ?><?= !empty($customer['document']) ? ' (' . e($customer['document']) . ')' : '' ?></option>
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
                <?php
                // Verifica se algum item tem desconto individual
                $hasItemDiscount = false;
                foreach ($orderItems as $chkItem) {
                    if ((float)($chkItem['discount'] ?? 0) > 0) { $hasItemDiscount = true; break; }
                }
                // Always show discount column on edit (editable)
                $showEditDiscount = true;
                ?>
                <div class="table-responsive mb-3">
                    <table class="table table-hover table-sm align-middle" id="editItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center" style="width:90px;">Qtd</th>
                                <th class="text-end" style="width:130px;">Preço Unit.</th>
                                <th class="text-end" style="width:130px;">Subtotal</th>
                                <th class="text-end" style="width:140px;">Desconto</th>
                                <th class="text-end" style="width:130px;">Líquido</th>
                                <th class="text-center" style="width:80px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $totalItems = 0; $totalDiscounts = 0; ?>
                            <?php foreach ($orderItems as $item): ?>
                            <?php 
                                $subtotal = $item['quantity'] * $item['unit_price']; 
                                $itemDiscount = (float)($item['discount'] ?? 0);
                                $netAmount = $subtotal - $itemDiscount;
                                $totalItems += $subtotal; 
                                $totalDiscounts += $itemDiscount;
                            ?>
                            <tr data-item-id="<?= $item['id'] ?>">
                                <td>
                                    <strong><?= e($item['product_name']) ?></strong>
                                    <?php if (!empty($item['combination_label'])): ?>
                                    <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= e($item['combination_label']) ?></small>
                                    <?php elseif (!empty($item['grade_description'])): ?>
                                    <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= e($item['grade_description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <input type="number" min="1" step="1"
                                           class="form-control form-control-sm text-center edit-item-qty-input py-0" 
                                           data-item-id="<?= $item['id'] ?>" data-unit-price="<?= $item['unit_price'] ?>"
                                           value="<?= $item['quantity'] ?>"
                                           style="width:70px; margin:0 auto; font-size:0.8rem;">
                                </td>
                                <td class="text-end">R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                <td class="text-end fw-bold edit-item-subtotal">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <div class="input-group input-group-sm" style="width:130px; margin-left:auto;">
                                        <span class="input-group-text py-0 px-1" style="font-size:0.7rem;">R$</span>
                                        <input type="number" step="0.01" min="0" max="<?= $subtotal ?>" 
                                               class="form-control form-control-sm text-end edit-item-discount-input py-0" 
                                               data-item-id="<?= $item['id'] ?>" data-subtotal="<?= $subtotal ?>"
                                               value="<?= $itemDiscount > 0 ? number_format($itemDiscount, 2, '.', '') : '' ?>"
                                               placeholder="0,00" style="font-size:0.8rem;">
                                    </div>
                                </td>
                                <td class="text-end fw-bold edit-item-net-amount <?= $itemDiscount > 0 ? 'text-success' : '' ?>">
                                    R$ <?= number_format($netAmount, 2, ',', '.') ?>
                                </td>
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
                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold fs-5" id="editTotalSubtotal">R$ <?= number_format($totalItems, 2, ',', '.') ?></td>
                                <td class="text-end fw-bold text-danger" id="editTotalDiscounts">
                                    <?= $totalDiscounts > 0 ? '- R$ ' . number_format($totalDiscounts, 2, ',', '.') : '' ?>
                                </td>
                                <td class="text-end fw-bold fs-5 text-success" id="editTotalNet">
                                    R$ <?= number_format($totalItems - $totalDiscounts, 2, ',', '.') ?>
                                </td>
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
                    <div class="card-header bg-primary  py-2">
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
                                            <?= e($prod['name']) ?> — R$ <?= number_format($displayPrice, 2, ',', '.') ?>
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

    // ═══ QUANTIDADE INLINE — Salvar via AJAX ═══
    (function() {
        let qtyTimers = {};
        const csrf = $('meta[name="csrf-token"]').attr('content') || '';

        document.querySelectorAll('.edit-item-qty-input').forEach(input => {
            input.addEventListener('input', function() {
                const itemId = this.dataset.itemId;
                const unitPrice = parseFloat(this.dataset.unitPrice) || 0;
                let qty = parseInt(this.value) || 1;
                if (qty < 1) { qty = 1; this.value = 1; }

                const newSubtotal = qty * unitPrice;
                const row = this.closest('tr');
                const subtotalCell = row ? row.querySelector('.edit-item-subtotal') : null;
                if (subtotalCell) {
                    subtotalCell.textContent = 'R$ ' + newSubtotal.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                }
                const discountInput = row ? row.querySelector('.edit-item-discount-input') : null;
                if (discountInput) {
                    discountInput.setAttribute('max', newSubtotal);
                    discountInput.dataset.subtotal = newSubtotal;
                    const disc = parseFloat(discountInput.value) || 0;
                    const netCell = row.querySelector('.edit-item-net-amount');
                    if (netCell) {
                        netCell.textContent = 'R$ ' + (newSubtotal - disc).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                }
                recalcEditTotals();

                clearTimeout(qtyTimers[itemId]);
                qtyTimers[itemId] = setTimeout(() => { saveEditQty(itemId, qty); }, 800);
            });
            input.addEventListener('blur', function() {
                const itemId = this.dataset.itemId;
                let qty = parseInt(this.value) || 1;
                clearTimeout(qtyTimers[itemId]);
                saveEditQty(itemId, qty);
            });
        });

        function saveEditQty(itemId, qty) {
            const fd = new FormData();
            fd.append('item_id', itemId);
            fd.append('quantity', qty);
            fd.append('csrf_token', csrf);
            fetch('?page=orders&action=updateItemQty', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const inp = document.querySelector('.edit-item-qty-input[data-item-id="'+itemId+'"]');
                        if (inp) { inp.classList.add('border-success'); setTimeout(()=>inp.classList.remove('border-success'),1500); }
                    }
                }).catch(e => console.error('Erro qty:', e));
        }
    })();

    // ═══ DESCONTO INLINE — Salvar via AJAX ═══
    (function() {
        let discTimers = {};
        const csrf = $('meta[name="csrf-token"]').attr('content') || '';

        document.querySelectorAll('.edit-item-discount-input').forEach(input => {
            input.addEventListener('input', function() {
                const itemId = this.dataset.itemId;
                const subtotal = parseFloat(this.dataset.subtotal) || 0;
                let discount = parseFloat(this.value) || 0;
                if (discount < 0) discount = 0;
                if (discount > subtotal) { discount = subtotal; this.value = discount.toFixed(2); }

                const netAmount = subtotal - discount;
                const row = this.closest('tr');
                const netCell = row ? row.querySelector('.edit-item-net-amount') : null;
                if (netCell) {
                    netCell.textContent = 'R$ ' + netAmount.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                    netCell.classList.toggle('text-success', discount > 0);
                }
                recalcEditTotals();

                clearTimeout(discTimers[itemId]);
                discTimers[itemId] = setTimeout(() => { saveEditDiscount(itemId, discount); }, 800);
            });
            input.addEventListener('blur', function() {
                const itemId = this.dataset.itemId;
                const discount = parseFloat(this.value) || 0;
                clearTimeout(discTimers[itemId]);
                saveEditDiscount(itemId, discount);
            });
        });

        function saveEditDiscount(itemId, discount) {
            const fd = new FormData();
            fd.append('item_id', itemId);
            fd.append('discount', discount);
            fd.append('csrf_token', csrf);
            fetch('?page=orders&action=updateItemDiscount', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const inp = document.querySelector('.edit-item-discount-input[data-item-id="'+itemId+'"]');
                        if (inp) { inp.classList.add('border-success'); setTimeout(()=>inp.classList.remove('border-success'),1500); }
                    }
                }).catch(e => console.error('Erro discount:', e));
        }
    })();

    function recalcEditTotals() {
        let totalSub = 0, totalDisc = 0;
        document.querySelectorAll('.edit-item-discount-input').forEach(inp => {
            totalSub += parseFloat(inp.dataset.subtotal) || 0;
            totalDisc += parseFloat(inp.value) || 0;
        });
        const subEl = document.getElementById('editTotalSubtotal');
        const discEl = document.getElementById('editTotalDiscounts');
        const netEl = document.getElementById('editTotalNet');
        if (subEl) subEl.textContent = 'R$ ' + totalSub.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
        if (discEl) discEl.textContent = totalDisc > 0 ? '- R$ ' + totalDisc.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}) : '';
        if (netEl) netEl.textContent = 'R$ ' + (totalSub - totalDisc).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    </script>

    <?php if (in_array($currentStage, ['venda', 'financeiro'])): ?>
    <!-- Nota de Pedido — Disponível nas etapas Venda e Financeiro -->
    <div class="row mt-4">
        <div class="col-md-8 mx-auto">
            <div class="card border-success border-opacity-50 shadow-sm">
                <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                    <h6 class="mb-0 text-white fw-bold">
                        <i class="fas fa-file-invoice me-2"></i>Nota de Pedido
                    </h6>
                    <span class="badge bg-white bg-opacity-25 text-white" style="font-size:0.7rem;">
                        <i class="fas fa-print me-1"></i>Documento para impressão
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Gere a Nota de Pedido com os dados do cliente, produtos, valores e informações de pagamento.
                            </p>
                            <p class="mb-0 small text-muted">
                                Ideal para entregar ao cliente como comprovante da compra.
                                <strong>O pedido será salvo antes de gerar a nota.</strong>
                            </p>
                        </div>
                        <button type="button" id="btnPrintOrderEdit" 
                                class="btn btn-success btn-sm ms-3 px-3 flex-shrink-0">
                            <i class="fas fa-print me-1"></i> Imprimir Nota de Pedido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('btnPrintOrderEdit').addEventListener('click', function() {
        // Salvar o formulário principal do pedido via POST, depois abrir a nota
        const form = document.querySelector('form[action*="action=update"]');
        if (form) {
            // Adicionar campo hidden para indicar que deve abrir a nota após salvar
            let hiddenInput = form.querySelector('input[name="print_order_after_save"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'print_order_after_save';
                form.appendChild(hiddenInput);
            }
            hiddenInput.value = '1';
            form.submit();
        }
    });
    </script>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Limpar parâmetros da URL
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('status');
        url.searchParams.delete('print_order');
        window.history.replaceState({}, '', url);
    }
    <?php if(!empty($_GET['print_order'])): ?>
    Swal.fire({ icon: 'success', title: 'Salvo!', text: 'Abrindo a Nota de Pedido...', timer: 1500, showConfirmButton: false });
    window.open('?page=orders&action=printOrder&id=<?= $order['id'] ?>', '_blank');
    <?php else: ?>
    Swal.fire({ icon: 'success', title: 'Salvo!', text: 'Pedido atualizado com sucesso.', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
});
</script>
<?php endif; ?>