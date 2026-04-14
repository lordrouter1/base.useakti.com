<?php
/**
 * Transportadoras
 * Variáveis: $carriers
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-truck me-2 text-primary"></i>Transportadoras</h1></div>
        <a href="?page=shipments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Nova Transportadora</strong></div>
                <div class="card-body">
                    <form method="post" action="?page=shipments&action=saveCarrier">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" name="code" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL de Rastreio</label>
                            <input type="text" name="tracking_url" class="form-control" placeholder="https://rastreio.com/?code={tracking_code}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Nome</th><th>Código</th><th>Telefone</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (empty($carriers)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma transportadora.</td></tr>
                            <?php else: ?>
                                <?php foreach ($carriers as $c): ?>
                                <tr>
                                    <td><?= e($c['name']) ?></td>
                                    <td><?= e($c['code'] ?? '-') ?></td>
                                    <td><?= e($c['phone'] ?? '-') ?></td>
                                    <td><?= $c['is_active'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
