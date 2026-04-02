<?php
/**
 * Qualidade — Não-conformidades
 * FEAT-017
 * Variáveis: $nonConformities
 */
$statusFilter = $_GET['status'] ?? '';
$severityFilter = $_GET['severity'] ?? '';
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Não-Conformidades</h1>
        </div>
        <a href="?page=quality" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="quality">
                <input type="hidden" name="action" value="nonConformities">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos os Status</option>
                        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Aberta</option>
                        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>Em Tratamento</option>
                        <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolvida</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="severity" class="form-select form-select-sm">
                        <option value="">Todas as Severidades</option>
                        <option value="low" <?= $severityFilter === 'low' ? 'selected' : '' ?>>Baixa</option>
                        <option value="medium" <?= $severityFilter === 'medium' ? 'selected' : '' ?>>Média</option>
                        <option value="high" <?= $severityFilter === 'high' ? 'selected' : '' ?>>Alta</option>
                        <option value="critical" <?= $severityFilter === 'critical' ? 'selected' : '' ?>>Crítica</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Severidade</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($nonConformities)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma NC encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($nonConformities as $nc): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($nc['title']) ?></div>
                                <small class="text-muted"><?= e(mb_substr($nc['description'] ?? '', 0, 80)) ?></small>
                            </td>
                            <td>
                                <?php
                                $sevBadge = ['low' => 'bg-info', 'medium' => 'bg-warning text-dark', 'high' => 'bg-danger', 'critical' => 'bg-dark'];
                                $sev = $nc['severity'] ?? 'medium';
                                ?>
                                <span class="badge <?= $sevBadge[$sev] ?? 'bg-secondary' ?>"><?= ucfirst($sev) ?></span>
                            </td>
                            <td>
                                <?php
                                $stBadge = ['open' => 'bg-danger', 'in_progress' => 'bg-warning text-dark', 'resolved' => 'bg-success'];
                                $stLabel = ['open' => 'Aberta', 'in_progress' => 'Em Tratamento', 'resolved' => 'Resolvida'];
                                $ncs = $nc['status'] ?? 'open';
                                ?>
                                <span class="badge <?= $stBadge[$ncs] ?? 'bg-secondary' ?>"><?= $stLabel[$ncs] ?? $ncs ?></span>
                            </td>
                            <td style="font-size:.8rem;"><?= date('d/m/Y', strtotime($nc['created_at'])) ?></td>
                            <td class="text-end">
                                <?php if ($ncs !== 'resolved'): ?>
                                <button class="btn btn-sm btn-outline-success btnResolve" data-id="<?= (int) $nc['id'] ?>" title="Resolver"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    document.querySelectorAll('.btnResolve').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Resolver NC', input: 'textarea', inputPlaceholder: 'Ação corretiva...',
                showCancelButton: true, confirmButtonText: 'Resolver', cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', id);
                    fd.append('corrective_action', result.value || '');
                    fetch('?page=quality&action=resolveNonConformity', {
                        method: 'POST', body: fd,
                        headers: {'X-CSRF-TOKEN': csrfToken}
                    }).then(() => location.reload());
                }
            });
        });
    });
});
</script>
