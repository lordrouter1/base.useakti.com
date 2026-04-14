<?php
/**
 * E-mail Marketing — Campanhas
 * FEAT-013
 * Variáveis: $campaigns, $pagination
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-envelope me-2 text-primary"></i>E-mail Marketing</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Campanhas de e-mail para clientes segmentados.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=email_marketing&action=templates" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-alt me-1"></i>Templates</a>
            <a href="?page=email_marketing&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Campanha</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Campanha</th>
                            <th>Assunto</th>
                            <th>Status</th>
                            <th>Enviados</th>
                            <th>Abertos</th>
                            <th>Clicados</th>
                            <th>Agendamento</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma campanha.</td></tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $c): ?>
                        <tr>
                            <td class="fw-bold"><?= e($c['name']) ?></td>
                            <td><?= e($c['subject'] ?? '-') ?></td>
                            <td>
                                <?php
                                $sBadges = ['draft' => 'bg-secondary', 'scheduled' => 'bg-info', 'sending' => 'bg-warning text-dark', 'sent' => 'bg-success', 'paused' => 'bg-dark'];
                                $sLabels = ['draft' => 'Rascunho', 'scheduled' => 'Agendada', 'sending' => 'Enviando', 'sent' => 'Enviada', 'paused' => 'Pausada'];
                                $cs = $c['status'] ?? 'draft';
                                ?>
                                <span class="badge <?= $sBadges[$cs] ?? 'bg-secondary' ?>"><?= $sLabels[$cs] ?? $cs ?></span>
                            </td>
                            <td><?= (int) ($c['total_sent'] ?? 0) ?></td>
                            <td><?= (int) ($c['total_opened'] ?? 0) ?></td>
                            <td><?= (int) ($c['total_clicked'] ?? 0) ?></td>
                            <td style="font-size:.8rem;"><?= !empty($c['scheduled_at']) ? date('d/m/Y H:i', strtotime($c['scheduled_at'])) : '-' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info btnPreview" data-id="<?= (int) $c['id'] ?>" title="Preview"><i class="fas fa-eye"></i></button>
                                <a href="?page=email_marketing&action=edit&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <?php if (in_array($cs, ['draft', 'scheduled'])): ?>
                                <button class="btn btn-sm btn-outline-success btnSend" data-id="<?= (int) $c['id'] ?>" title="Enviar"><i class="fas fa-paper-plane"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $c['id'] ?>"><i class="fas fa-trash"></i></button>
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

<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-1"></i>Preview da Campanha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width:100%;height:500px;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview
    document.querySelectorAll('.btnPreview').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const frame = document.getElementById('previewFrame');
            frame.src = '?page=email_marketing&action=previewCampaign&id=' + id;
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        });
    });

    // Delete
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({ title: 'Excluir campanha?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não' })
            .then(r => { if (r.isConfirmed) window.location.href = '?page=email_marketing&action=delete&id=' + id; });
        });
    });

    // Send campaign
    document.querySelectorAll('.btnSend').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Enviar campanha?',
                html: 'Os e-mails serão enviados para todos os destinatários.<br><strong>Esta ação não pode ser desfeita.</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: '<i class="fas fa-paper-plane me-1"></i>Enviar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('?page=email_marketing&action=sendCampaign&id=' + id)
                        .then(r => r.json())
                        .then(data => {
                            if (!data.success) throw new Error(data.error || 'Erro ao enviar.');
                            return data;
                        })
                        .catch(err => Swal.showValidationMessage(err.message));
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(result => {
                if (result.isConfirmed) {
                    const d = result.value;
                    Swal.fire({
                        title: 'Campanha enviada!',
                        html: `Total: ${d.total} | Enviados: ${d.sent}` + (d.failed > 0 ? ` | Falharam: ${d.failed}` : ''),
                        icon: 'success'
                    }).then(() => location.reload());
                }
            });
        });
    });
});
</script>
