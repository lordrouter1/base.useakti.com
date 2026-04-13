<?php
/**
 * View: Estoque de Insumos — Saída (FEFO)
 * Variáveis: $warehouses
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-arrow-up me-2 text-danger"></i>Saída de Insumos</h1>
        <a href="?page=supply_stock" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="alert alert-info small mb-3">
        <i class="fas fa-info-circle me-1"></i>A saída segue a regra <strong>FEFO</strong> (First Expired, First Out) automaticamente — lotes com validade mais próxima serão consumidos primeiro.
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="exitForm">
                <?= csrf_field() ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Armazém <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouseId" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= eAttr($wh['id']) ?>"><?= e($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Motivo / Referência</label>
                        <input type="text" name="reason" class="form-control" placeholder="Ex: Ordem de produção #123, Consumo interno...">
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="fas fa-list me-1"></i>Itens</h6>
                <div id="itemsContainer">
                    <div class="exit-item row g-2 align-items-end mb-2" data-index="0">
                        <div class="col-md-4">
                            <label class="form-label small">Insumo <span class="text-danger">*</span></label>
                            <select name="items[0][supply_id]" class="form-select select2-supply" required></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" name="items[0][quantity]" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Estoque disponível</label>
                            <input type="text" class="form-control stock-info" readonly disabled placeholder="Selecione o insumo">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Lotes</label>
                            <button type="button" class="btn btn-outline-info btn-sm w-100 btn-view-batches" disabled>
                                <i class="fas fa-layer-group me-1"></i>Ver Lotes
                            </button>
                        </div>
                        <div class="col-md-1 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="Remover" disabled>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" id="btnAddItem" class="btn btn-outline-primary btn-sm mt-2 mb-4">
                    <i class="fas fa-plus me-1"></i>Adicionar Item
                </button>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-danger" id="btnSubmit">
                        <i class="fas fa-check me-1"></i>Confirmar Saída
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let itemIndex = 0;

    function initSelect2(el) {
        $(el).select2({
            ajax: {
                url: '?page=supply_stock&action=searchSupplies',
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data.results || [] })
            },
            placeholder: 'Buscar insumo...',
            minimumInputLength: 1,
            allowClear: true,
            width: '100%'
        }).on('select2:select', function(e) {
            loadStockInfo($(this).closest('.exit-item'), e.params.data.id);
        });
    }

    function loadStockInfo(row, supplyId) {
        const whId = document.getElementById('warehouseId').value;
        if (!whId) {
            row.find('.stock-info').val('Selecione armazém');
            return;
        }

        fetch(`?page=supply_stock&action=getStockInfo&supply_id=${supplyId}&warehouse_id=${whId}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const unit = data.supply?.unit_measure || '';
                row.find('.stock-info').val(`${parseFloat(data.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})} ${unit}`);
                row.find('.btn-view-batches').prop('disabled', false).data('supplyId', supplyId);
            }
        });
    }

    initSelect2('.select2-supply');

    // Adicionar item
    document.getElementById('btnAddItem').addEventListener('click', function() {
        itemIndex++;
        const html = `
        <div class="exit-item row g-2 align-items-end mb-2" data-index="${itemIndex}">
            <div class="col-md-4">
                <select name="items[${itemIndex}][supply_id]" class="form-select select2-supply-new" required></select>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0.01" required placeholder="Qtd">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control stock-info" readonly disabled placeholder="Selecione o insumo">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-info btn-sm w-100 btn-view-batches" disabled>
                    <i class="fas fa-layer-group me-1"></i>Ver Lotes
                </button>
            </div>
            <div class="col-md-1 text-center">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="Remover">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
        document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
        initSelect2(document.querySelector(`[data-index="${itemIndex}"] .select2-supply-new`));
        updateRemoveButtons();
    });

    // Remover item
    document.getElementById('itemsContainer').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-item');
        if (btn && !btn.disabled) {
            btn.closest('.exit-item').remove();
            updateRemoveButtons();
        }
    });

    // Ver lotes
    $(document).on('click', '.btn-view-batches', function() {
        const supplyId = $(this).data('supplyId');
        const whId = document.getElementById('warehouseId').value;
        if (!supplyId || !whId) return;

        fetch(`?page=supply_stock&action=getBatches&supply_id=${supplyId}&warehouse_id=${whId}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.batches || !data.batches.length) {
                Swal.fire({ icon: 'info', title: 'Lotes', text: 'Nenhum lote com estoque neste armazém.' });
                return;
            }
            let html = '<table class="table table-sm table-bordered"><thead><tr><th>Lote</th><th>Validade</th><th class="text-end">Qtd</th></tr></thead><tbody>';
            data.batches.forEach(b => {
                const exp = b.expiry_date ? new Date(b.expiry_date).toLocaleDateString('pt-BR') : '—';
                html += `<tr><td>${b.batch_number || '(sem lote)'}</td><td>${exp}</td><td class="text-end">${parseFloat(b.quantity).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td></tr>`;
            });
            html += '</tbody></table>';
            Swal.fire({ title: 'Lotes Disponíveis (FEFO)', html: html, width: 500, showConfirmButton: true, confirmButtonText: 'Fechar' });
        });
    });

    function updateRemoveButtons() {
        const items = document.querySelectorAll('.exit-item');
        items.forEach(item => {
            const btn = item.querySelector('.btn-remove-item');
            btn.disabled = items.length <= 1;
        });
    }

    // Submit
    document.getElementById('exitForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';

        fetch('?page=supply_stock&action=storeExit', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 2000, showConfirmButton: false })
                    .then(() => window.location.href = '?page=supply_stock');
            } else {
                let msg = data.message || 'Erro ao processar saída.';
                if (data.errors?.length) msg += '\n' + data.errors.join('\n');
                Swal.fire({ icon: 'error', title: 'Erro', text: msg });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão.' }))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Saída';
        });
    });
});
</script>
