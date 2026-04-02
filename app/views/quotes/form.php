<?php
/**
 * Orçamentos — Formulário (criar/editar)
 * FEAT-006
 * Variáveis: $quote (null = novo), $customers, $items (edit mode)
 */
$isEdit = !empty($quote);
$q = $quote ?? [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-file-alt me-2 text-primary"></i>
                <?= $isEdit ? 'Editar Orçamento #' . (int) $q['id'] : 'Novo Orçamento' ?>
            </h1>
        </div>
        <a href="?page=quotes" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=quotes&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($customers ?? [] as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= ($q['customer_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Validade</label>
                        <input type="date" name="valid_until" class="form-control" value="<?= eAttr($q['valid_until'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Desconto (%)</label>
                        <input type="number" name="discount" class="form-control" step="0.01" min="0" max="100" value="<?= eAttr($q['discount'] ?? '0') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= ($q['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                            <option value="sent" <?= ($q['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Enviado</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Observações</label>
                        <textarea name="notes" class="form-control" rows="3"><?= e($q['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="?page=quotes" class="btn btn-outline-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit): ?>
    <!-- Itens do Orçamento -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Itens do Orçamento</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fas fa-plus me-1"></i>Adicionar Item</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Preço Unit.</th>
                            <th>Desconto</th>
                            <th>Total</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="quoteItems">
                    <?php foreach ($items ?? [] as $item): ?>
                        <tr>
                            <td><?= e($item['product_name'] ?? 'Produto #' . ($item['product_id'] ?? '')) ?></td>
                            <td><?= eNum($item['quantity'] ?? 0) ?></td>
                            <td>R$ <?= number_format((float) ($item['unit_price'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= number_format((float) ($item['discount'] ?? 0), 2, ',', '.') ?>%</td>
                            <td>R$ <?= number_format((float) ($item['total'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger btnRemoveItem" data-id="<?= (int) $item['id'] ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($isEdit): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    document.querySelectorAll('.btnRemoveItem').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.id;
            Swal.fire({
                title: 'Remover item?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não'
            }).then(r => {
                if (r.isConfirmed) {
                    fetch('?page=quotes&action=removeItem&item_id=' + itemId, {
                        headers: {'X-CSRF-TOKEN': csrfToken}
                    }).then(() => location.reload());
                }
            });
        });
    });
});
</script>
<?php endif; ?>
