<?php
/**
 * View: Estoque de Insumos — Entrada
 * Variáveis: $warehouses
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-arrow-down me-2 text-success"></i>Entrada de Insumos</h1>
        <a href="?page=supply_stock" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="entryForm">
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
                        <input type="text" name="reason" class="form-control" placeholder="Ex: NF 12345, Compra fornecedor X...">
                    </div>
                </div>

                <!-- Itens dinâmicos -->
                <h6 class="fw-bold mb-3"><i class="fas fa-list me-1"></i>Itens</h6>
                <div id="itemsContainer">
                    <div class="entry-item row g-2 align-items-end mb-2" data-index="0">
                        <div class="col-md-3">
                            <label class="form-label small">Insumo <span class="text-danger">*</span></label>
                            <select name="items[0][supply_id]" class="form-select select2-supply" required></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" name="items[0][quantity]" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Preço Unitário</label>
                            <input type="number" name="items[0][unit_price]" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Lote</label>
                            <input type="text" name="items[0][batch_number]" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Validade</label>
                            <input type="date" name="items[0][expiry_date]" class="form-control">
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
                    <button type="submit" class="btn btn-success" id="btnSubmit">
                        <i class="fas fa-check me-1"></i>Confirmar Entrada
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
        });
    }

    // Inicializar primeiro item
    initSelect2('.select2-supply');

    // Adicionar item
    document.getElementById('btnAddItem').addEventListener('click', function() {
        itemIndex++;
        const html = `
        <div class="entry-item row g-2 align-items-end mb-2" data-index="${itemIndex}">
            <div class="col-md-3">
                <select name="items[${itemIndex}][supply_id]" class="form-select select2-supply-new" required></select>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0.01" required placeholder="Qtd">
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control" step="0.01" min="0" placeholder="Preço">
            </div>
            <div class="col-md-2">
                <input type="text" name="items[${itemIndex}][batch_number]" class="form-control" placeholder="Lote">
            </div>
            <div class="col-md-2">
                <input type="date" name="items[${itemIndex}][expiry_date]" class="form-control">
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
            btn.closest('.entry-item').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const items = document.querySelectorAll('.entry-item');
        items.forEach(item => {
            const btn = item.querySelector('.btn-remove-item');
            btn.disabled = items.length <= 1;
        });
    }

    // Submit
    document.getElementById('entryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';

        const formData = new FormData(this);

        fetch('?page=supply_stock&action=storeEntry', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 2000, showConfirmButton: false })
                    .then(() => window.location.href = '?page=supply_stock');
            } else {
                let errorMsg = data.message || 'Erro ao processar entrada.';
                if (data.errors && data.errors.length) {
                    errorMsg += '\n' + data.errors.join('\n');
                }
                Swal.fire({ icon: 'error', title: 'Erro', text: errorMsg });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão.' }))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Entrada';
        });
    });
});
</script>
