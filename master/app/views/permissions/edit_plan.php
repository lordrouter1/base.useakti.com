<?php
/**
 * View: Permissions - Editar permissões padrão de um plano
 */
$pageTitle = 'Permissões do Plano';
$pageSubtitle = htmlspecialchars($plan['plan_name']);
$topbarActions = '<a href="?page=plans&action=edit&id=' . $plan['id'] . '" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar ao Plano</a>';

$totalPages = count($controllablePages);
$selectedCount = count($currentPermissions);

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    const totalPages = document.querySelectorAll('.page-checkbox').length;

    // Select all per group
    $('.select-all-group').on('change', function() {
        const group = $(this).data('group');
        const checked = this.checked;
        $(`.page-checkbox[data-group="${group}"]`).prop('checked', checked);
        updateCounter();
    });

    // Individual checkbox → update group "select all"
    $('.page-checkbox').on('change', function() {
        const group = $(this).data('group');
        const total = $(`.page-checkbox[data-group="${group}"]`).length;
        const selected = $(`.page-checkbox[data-group="${group}"]:checked`).length;
        $(`.select-all-group[data-group="${group}"]`).prop('checked', total === selected);
        updateCounter();
    });

    // Select all pages
    $('#selectAllPages').on('change', function() {
        const checked = this.checked;
        $('.page-checkbox').prop('checked', checked);
        $('.select-all-group').prop('checked', checked);
        updateCounter();
    });

    // Confirm save with sync
    $('#planPermForm').on('submit', function(e) {
        const syncChecked = $('#syncTenants').is(':checked');
        const selected = $('.page-checkbox:checked').length;
        
        if (syncChecked && selected > 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Sincronizar com tenants?',
                html: 'Isso aplicará as <strong>' + selected + ' permissões</strong> selecionadas a <strong>todos os tenants</strong> que usam este plano.<br><br>As permissões individuais serão substituídas.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, sincronizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit();
                }
            });
        }
    });

    function updateCounter() {
        const selected = document.querySelectorAll('.page-checkbox:checked').length;
        document.getElementById('pageCounter').textContent = selected + '/' + totalPages + ' páginas';
    }

    // Initialize group checkboxes state
    document.querySelectorAll('.select-all-group').forEach(function(el) {
        const group = el.dataset.group;
        const total = document.querySelectorAll(`.page-checkbox[data-group="${group}"]`).length;
        const selected = document.querySelectorAll(`.page-checkbox[data-group="${group}"]:checked`).length;
        el.checked = total === selected && total > 0;
    });

    updateCounter();
});
</script>
SCRIPTS;

require_once __DIR__ . '/../layout/header.php';
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-10">

        <!-- Info Card -->
        <div class="card border-0 mb-4" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-layer-group text-akti me-2"></i>Permissões Padrão do Plano</h5>
                        <p class="text-muted mb-0" style="font-size:14px;">
                            Defina quais páginas os tenants do plano <strong><?= htmlspecialchars($plan['plan_name']) ?></strong> podem acessar por padrão.
                            <br><small>Estas permissões servem como template e podem ser aplicadas individualmente a tenants.</small>
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary fs-6" id="pageCounter"><?= "$selectedCount/$totalPages páginas" ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Form -->
        <form action="?page=permissions&action=updatePlan" method="POST" id="planPermForm" class="form-card">
            <?= master_csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">

            <!-- Select All -->
            <div class="form-section pb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllPages">
                    <label class="form-check-label fw-bold" for="selectAllPages">
                        Marcar / Desmarcar Todas
                    </label>
                </div>
            </div>

            <?php foreach ($pageGroups as $groupName => $groupPages): ?>
            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-section-title mb-0">
                        <i class="fas fa-folder"></i> <?= htmlspecialchars($groupName) ?>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input select-all-group" type="checkbox" 
                               data-group="<?= htmlspecialchars($groupName) ?>"
                               id="groupAll_<?= md5($groupName) ?>">
                        <label class="form-check-label small" for="groupAll_<?= md5($groupName) ?>">Marcar todos</label>
                    </div>
                </div>
                <div class="row g-2">
                    <?php foreach ($groupPages as $pageKey): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="form-check">
                            <input class="form-check-input page-checkbox" type="checkbox" 
                                   name="pages[]" value="<?= htmlspecialchars($pageKey) ?>"
                                   data-group="<?= htmlspecialchars($groupName) ?>"
                                   id="page_<?= $pageKey ?>"
                                   <?= in_array($pageKey, $currentPermissions) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="page_<?= $pageKey ?>">
                                <?= htmlspecialchars($pageLabels[$pageKey] ?? $pageKey) ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Sync option -->
            <div class="form-section">
                <div class="alert alert-info border-0" style="border-radius:10px; background: #e8f4fd;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="syncTenants" name="sync_tenants">
                        <label class="form-check-label" for="syncTenants">
                            <strong>Sincronizar permissões com tenants vinculados</strong>
                            <br><small class="text-muted">Ao marcar, todos os tenants ativos que usam este plano terão suas permissões de página substituídas por estas.</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-section mb-0" style="position:sticky; bottom:0; z-index:10; background:white; margin:-32px; margin-top:0; padding:20px 32px; border-top:2px solid #e5e7eb; box-shadow:0 -4px 12px rgba(0,0,0,0.05); border-radius:0 0 12px 12px;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge bg-primary"><?= $selectedCount ?>/<?= $totalPages ?> páginas</span>
                    <div class="d-flex gap-2">
                        <a href="?page=plans&action=edit&id=<?= $plan['id'] ?>" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-save me-2"></i>Salvar Permissões do Plano
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
