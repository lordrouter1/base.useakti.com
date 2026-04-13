<?php
/**
 * View: Estoque de Insumos — Transferência
 * Variáveis: $warehouses
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-exchange-alt me-2 text-info"></i>Transferência de Insumos</h1>
        <a href="?page=supply_stock" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="transferForm">
                <?= csrf_field() ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Armazém Origem <span class="text-danger">*</span></label>
                        <select name="origin_warehouse_id" id="originWarehouse" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= eAttr($wh['id']) ?>"><?= e($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Armazém Destino <span class="text-danger">*</span></label>
                        <select name="dest_warehouse_id" id="destWarehouse" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= eAttr($wh['id']) ?>"><?= e($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Motivo</label>
                        <input type="text" name="reason" class="form-control" placeholder="Motivo da transferência...">
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="fas fa-list me-1"></i>Itens</h6>
                <div id="itemsContainer">
                    <div class="transfer-item row g-2 align-items-end mb-2" data-index="0">
                        <div class="col-md-4">
                            <label class="form-label small">Insumo <span class="text-danger">*</span></label>
                            <select name="items[0][supply_id]" class="form-select select2-supply" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" name="items[0][quantity]" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Lote</label>
                            <input type="text" name="items[0][batch_number]" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Estoque Origem</label>
                            <input type="text" class="form-control stock-info" readonly disabled>
                        </div>
                        <div class="col-md-1 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" disabled><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>

                <button type="button" id="btnAddItem" class="btn btn-outline-primary btn-sm mt-2 mb-4">
                    <i class="fas fa-plus me-1"></i>Adicionar Item
                </button>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-info" id="btnSubmit">
                        <i class="fas fa-exchange-alt me-1"></i>Confirmar Transferência
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
            loadOriginStock($(this).closest('.transfer-item'), e.params.data.id);
        });
    }

    function loadOriginStock(row, supplyId) {
        const whId = document.getElementById('originWarehouse').value;
        if (!whId) { row.find('.stock-info').val('Selecione origem'); return; }
        fetch(`?page=supply_stock&action=getStockInfo&supply_id=${supplyId}&warehouse_id=${whId}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                row.find('.stock-info').val(`${parseFloat(data.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})} ${data.supply?.unit_measure || ''}`);
            }
        });
    }

    initSelect2('.select2-supply');

    document.getElementById('btnAddItem').addEventListener('click', function() {
        itemIndex++;
        const html = `
        <div class="transfer-item row g-2 align-items-end mb-2" data-index="${itemIndex}">
            <div class="col-md-4"><select name="items[${itemIndex}][supply_id]" class="form-select select2-supply-new" required></select></div>
            <div class="col-md-3"><input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0.01" required placeholder="Qtd"></div>
            <div class="col-md-2"><input type="text" name="items[${itemIndex}][batch_number]" class="form-control" placeholder="Lote"></div>
            <div class="col-md-2"><input type="text" class="form-control stock-info" readonly disabled></div>
            <div class="col-md-1 text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-item"><i class="fas fa-trash"></i></button></div>
        </div>`;
        document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
        initSelect2(document.querySelector(`[data-index="${itemIndex}"] .select2-supply-new`));
        updateRemoveButtons();
    });

    document.getElementById('itemsContainer').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-item');
        if (btn && !btn.disabled) { btn.closest('.transfer-item').remove(); updateRemoveButtons(); }
    });

    function updateRemoveButtons() {
        const items = document.querySelectorAll('.transfer-item');
        items.forEach(item => { item.querySelector('.btn-remove-item').disabled = items.length <= 1; });
    }

    document.getElementById('transferForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const origin = document.getElementById('originWarehouse').value;
        const dest = document.getElementById('destWarehouse').value;
        if (origin === dest) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Armazém de origem e destino devem ser diferentes.' });
            return;
        }
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';

        fetch('?page=supply_stock&action=storeTransfer', {
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
                let msg = data.message || 'Erro';
                if (data.errors?.length) msg += '\n' + data.errors.join('\n');
                Swal.fire({ icon: 'error', title: 'Erro', text: msg });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão.' }))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Confirmar Transferência'; });
    });
});
</script>
