<?php include __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-clipboard-check me-2"></i><?= e($pageTitle) ?></h1>
    </div>

    <!-- Filtro -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="supply_dashboard">
                <input type="hidden" name="action" value="report">
                <div class="col-md-4">
                    <label class="form-label form-label-sm mb-0">Produto</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= eAttr($p['id']) ?>" <?= (isset($_GET['product_id']) && $_GET['product_id'] == $p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de apontamentos pendentes -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-2"></i>Consumos Pendentes de Apontamento
            <span class="badge bg-warning ms-2"><?= count($pending) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Insumo</th>
                            <th>Código</th>
                            <th>Unid.</th>
                            <th class="text-end">Qtd Prevista</th>
                            <th class="text-end">Qtd Real</th>
                            <th>Obs</th>
                            <th class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Nenhum apontamento pendente.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending as $row): ?>
                                <tr data-log-id="<?= eAttr($row['id']) ?>">
                                    <td><?= e($row['product_name']) ?></td>
                                    <td><?= e($row['supply_name']) ?></td>
                                    <td><code><?= e($row['supply_code']) ?></code></td>
                                    <td><?= e($row['unit_measure']) ?></td>
                                    <td class="text-end"><?= number_format($row['planned_quantity'], 4, ',', '.') ?></td>
                                    <td class="text-end">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control form-control-sm text-end input-actual"
                                               value="<?= eAttr($row['planned_quantity']) ?>"
                                               style="width:120px; display:inline-block;">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm input-notes"
                                               placeholder="Observação" style="width:150px;">
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-success btn-sm btn-save-report" title="Salvar">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '<?= csrf_token() ?>';

    $('.btn-save-report').on('click', function() {
        const $tr = $(this).closest('tr');
        const logId = $tr.data('log-id');
        const actual = parseFloat($tr.find('.input-actual').val());
        const notes = $tr.find('.input-notes').val();
        const $btn = $(this);

        if (isNaN(actual) || actual < 0) {
            Swal.fire('Erro', 'Informe uma quantidade válida.', 'error');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '?page=supply_dashboard&action=saveReport',
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data: { log_id: logId, actual_quantity: actual, notes: notes },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $tr.fadeOut(300, function() { $(this).remove(); });
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    Toast.fire({ icon: 'success', title: 'Apontamento salvo! Variação: ' + res.variance_percent + '%' });
                } else {
                    Swal.fire('Erro', res.message || 'Erro ao salvar.', 'error');
                    $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                }
            },
            error: function() {
                Swal.fire('Erro', 'Falha na comunicação.', 'error');
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
