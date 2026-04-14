/**
 * workflows-index.js — Automação de Workflows: listagem
 * Extraído de app/views/workflows/index.php (FE-003)
 */
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
                            const statusClass = l.status === 'success' ? 'success' : 'danger';
                            const escapedStatus = document.createElement('span');
                            escapedStatus.textContent = l.status;
                            const escapedEvent = document.createElement('span');
                            escapedEvent.textContent = l.event;
                            const escapedDate = document.createElement('span');
                            escapedDate.textContent = l.created_at;
                            html += '<tr><td>' + escapedDate.innerHTML + '</td><td><span class="badge bg-' + statusClass + '">' + escapedStatus.innerHTML + '</span></td><td>' + escapedEvent.innerHTML + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    html += '</div>';
                    Swal.fire({title: 'Logs de Execução', html: typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(html) : html, width: 600, showCloseButton: true, showConfirmButton: false});
                });
        });
    });
});
