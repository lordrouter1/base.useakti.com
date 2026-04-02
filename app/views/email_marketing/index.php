<?php
/**
 * E-mail Marketing — Campanhas
 * FEAT-013
 * Variáveis: $campaigns, $pagination
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= addslashes($_SESSION['flash_success']) ?>');});</script>
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
                            <td><?= (int) ($c['sent_count'] ?? 0) ?></td>
                            <td><?= (int) ($c['open_count'] ?? 0) ?></td>
                            <td><?= (int) ($c['click_count'] ?? 0) ?></td>
                            <td style="font-size:.8rem;"><?= !empty($c['scheduled_at']) ? date('d/m/Y H:i', strtotime($c['scheduled_at'])) : '-' ?></td>
                            <td class="text-end">
                                <a href="?page=email_marketing&action=edit&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({ title: 'Excluir campanha?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não' })
            .then(r => { if (r.isConfirmed) window.location.href = '?page=email_marketing&action=delete&id=' + id; });
        });
    });
});
</script>
