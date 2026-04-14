<?php
/**
 * Workflows — Listagem de regras
 * FEAT-010 (melhorado: drag & drop prioridade)
 * Variáveis: $rules
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-cogs me-2 text-primary"></i>Automação de Workflows</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Regras automáticas que executam ações com base em eventos do sistema. Arraste para reordenar a prioridade.</p>
        </div>
        <a href="?page=workflows&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Regra</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="rulesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:30px"></th>
                            <th>Nome</th>
                            <th>Evento</th>
                            <th style="width:50px">#</th>
                            <th>Execuções</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="rulesBody">
                    <?php if (empty($rules)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma regra cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rules as $idx => $r): ?>
                        <tr data-id="<?= (int) $r['id'] ?>">
                            <td class="drag-handle" style="cursor:grab"><i class="fas fa-grip-vertical text-muted"></i></td>
                            <td>
                                <div class="fw-bold"><?= e($r['name']) ?></div>
                                <?php if (!empty($r['description'])): ?>
                                <small class="text-muted"><?= e(mb_substr($r['description'], 0, 80)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code style="font-size:.8rem;"><?= e($r['event']) ?></code></td>
                            <td><span class="badge bg-light text-dark priority-badge"><?= $idx + 1 ?></span></td>
                            <td><?= (int) ($r['trigger_count'] ?? 0) ?></td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggleRule" type="checkbox" data-id="<?= (int) $r['id'] ?>" <?= $r['is_active'] ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="?page=workflows&action=edit&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-sm btn-outline-info btnLogs" data-id="<?= (int) $r['id'] ?>" title="Logs"><i class="fas fa-history"></i></button>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $r['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ─── Drag & Drop ───
    const rulesBody = document.getElementById('rulesBody');
    if (rulesBody && rulesBody.querySelectorAll('tr[data-id]').length > 0) {
        Sortable.create(rulesBody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'table-active',
            onEnd: function() {
                document.querySelectorAll('.priority-badge').forEach((badge, i) => { badge.textContent = i + 1; });
                const order = [];
                rulesBody.querySelectorAll('tr[data-id]').forEach((row, i) => {
                    order.push({ id: parseInt(row.dataset.id), priority: i });
                });
                fetch('?page=workflows&action=reorder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ order: order })
                }).then(r => r.json()).then(data => {
                    if (data.success && typeof AktiToast !== 'undefined') AktiToast.success('Prioridade atualizada');
                });
            }
        });
    }

    // ─── Toggle ───
    document.querySelectorAll('.toggleRule').forEach(sw => {
        sw.addEventListener('change', function() {
            fetch('?page=workflows&action=toggle&id=' + this.dataset.id, {headers: {'X-CSRF-TOKEN': csrfToken}});
        });
    });

    // ─── Delete ───
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Excluir regra?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não'
            }).then(r => { if (r.isConfirmed) window.location.href = '?page=workflows&action=delete&id=' + id; });
        });
    });

    // ─── Logs ───
    document.querySelectorAll('.btnLogs').forEach(btn => {
        btn.addEventListener('click', function() {
            fetch('?page=workflows&action=logs&rule_id=' + this.dataset.id)
                .then(r => r.json())
                .then(data => {
                    let logs = data.data || [];
                    let html = '<div class="text-start" style="max-height:400px;overflow:auto;">';
                    if (logs.length === 0) {
                        html += '<p class="text-muted">Nenhum log registrado.</p>';
                    } else {
                        html += '<table class="table table-sm"><thead><tr><th>Data</th><th>Status</th><th>Evento</th></tr></thead><tbody>';
                        logs.forEach(l => {
                            html += '<tr><td>' + l.created_at + '</td><td><span class="badge bg-' + (l.status === 'success' ? 'success' : 'danger') + '">' + l.status + '</span></td><td>' + l.event + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    html += '</div>';
                    Swal.fire({title: 'Logs de Execução', html: html, width: 600, showCloseButton: true, showConfirmButton: false});
                });
        });
    });
});
</script>
