<?php
/**
 * Qualidade — Formulário de checklist
 * FEAT-017
 * Variáveis: $checklist (null = novo), $items (edit mode)
 */
$isEdit = !empty($checklist);
$cl = $checklist ?? [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-clipboard-check me-2 text-primary"></i><?= $isEdit ? 'Editar Checklist' : 'Novo Checklist' ?></h1>
        </div>
        <a href="?page=quality" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=quality&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $cl['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($cl['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Etapa do Pipeline</label>
                        <input type="number" name="pipeline_stage_id" class="form-control" value="<?= (int) ($cl['pipeline_stage_id'] ?? 0) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" <?= ($cl['is_active'] ?? 1) ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= !($cl['is_active'] ?? 1) ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($cl['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="?page=quality" class="btn btn-outline-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit): ?>
    <!-- Itens do checklist -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-check-square me-2"></i>Itens de Verificação</h6>
            <button class="btn btn-sm btn-primary" id="btnAddItem"><i class="fas fa-plus me-1"></i>Adicionar Item</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Descrição</th><th>Obrigatório</th><th>Ordem</th><th class="text-end">Ações</th></tr>
                    </thead>
                    <tbody id="itemsList">
                    <?php foreach ($items ?? [] as $item): ?>
                    <tr>
                        <td><?= e($item['description']) ?></td>
                        <td><?= $item['required'] ? '<span class="badge bg-warning text-dark">Sim</span>' : '<span class="badge bg-light text-dark">Não</span>' ?></td>
                        <td><?= (int) $item['sort_order'] ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger btnRemoveItem" data-id="<?= (int) $item['id'] ?>"><i class="fas fa-times"></i></button>
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
    const checklistId = <?= (int) $cl['id'] ?>;

    document.getElementById('btnAddItem')?.addEventListener('click', function() {
        Swal.fire({
            title: 'Novo Item',
            html: '<input id="swalDesc" class="swal2-input" placeholder="Descrição">' +
                  '<div class="form-check mt-2 text-start ms-4"><input class="form-check-input" type="checkbox" id="swalReq" checked><label class="form-check-label" for="swalReq">Obrigatório</label></div>',
            showCancelButton: true, confirmButtonText: 'Adicionar', cancelButtonText: 'Cancelar',
            preConfirm: () => ({
                description: document.getElementById('swalDesc').value,
                required: document.getElementById('swalReq').checked ? 1 : 0
            })
        }).then(result => {
            if (result.isConfirmed && result.value.description) {
                const fd = new FormData();
                fd.append('checklist_id', checklistId);
                fd.append('description', result.value.description);
                fd.append('required', result.value.required);
                fetch('?page=quality&action=addItem', {
                    method: 'POST', body: fd,
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).then(() => location.reload());
            }
        });
    });

    document.querySelectorAll('.btnRemoveItem').forEach(btn => {
        btn.addEventListener('click', function() {
            fetch('?page=quality&action=removeItem&item_id=' + this.dataset.id, {
                headers: {'X-CSRF-TOKEN': csrfToken}
            }).then(() => location.reload());
        });
    });
});
</script>
<?php endif; ?>
