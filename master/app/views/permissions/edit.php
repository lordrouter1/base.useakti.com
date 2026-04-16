<?php
/**
 * View: Permissions - Editar permissões de um tenant
 */
$pageTitle = 'Permissões de Páginas';
$pageSubtitle = htmlspecialchars($client['client_name']) . ' (' . htmlspecialchars($client['subdomain']) . '.useakti.com)';
$topbarActions = '<a href="?page=clients&action=edit&id=' . $client['id'] . '" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar ao Cliente</a>';

$totalPages = count($controllablePages);
$selectedCount = count($currentPermissions);

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    const totalPages = document.querySelectorAll('.page-checkbox').length;

    // Toggle access mode
    $('input[name="access_mode"]').on('change', function() {
        if ($(this).val() === 'full') {
            $('#restrictedSection').slideUp(200);
        } else {
            $('#restrictedSection').slideDown(200);
        }
        updateCounter();
    });

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

    // Apply plan permissions via AJAX
    $('#applyPlanBtn').on('click', function() {
        const planId = $('#planSelect').val();
        if (!planId) {
            Swal.fire('Atenção', 'Selecione um plano primeiro.', 'warning');
            return;
        }
        Swal.fire({
            title: 'Aplicar permissões do plano?',
            text: 'As permissões atuais serão substituídas pelas do plano selecionado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, aplicar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#applyPlanForm').submit();
            }
        });
    });

    // Confirm save
    $('#permForm').on('submit', function(e) {
        const mode = $('input[name="access_mode"]:checked').val();
        if (mode === 'restricted') {
            const selected = $('.page-checkbox:checked').length;
            if (selected === 0) {
                e.preventDefault();
                Swal.fire('Atenção', 'Selecione pelo menos uma página para o modo restrito, ou use "Acesso Total".', 'warning');
                return;
            }
        }
    });

    function updateCounter() {
        const mode = $('input[name="access_mode"]:checked').val();
        if (mode === 'full') {
            $('#pageCounter').text(totalPages + '/' + totalPages + ' páginas (acesso total)');
        } else {
            const selected = $('.page-checkbox:checked').length;
            $('#pageCounter').text(selected + '/' + totalPages + ' páginas selecionadas');
        }
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
                        <h5 class="mb-1"><i class="fas fa-shield-halved text-akti me-2"></i>Controle de Acesso a Páginas</h5>
                        <p class="text-muted mb-0" style="font-size:14px;">
                            Configure quais páginas o tenant <strong><?= htmlspecialchars($client['client_name']) ?></strong> pode acessar.
                            <?php if ($client['plan_id']): ?>
                                <br><span class="badge bg-info"><i class="fas fa-layer-group me-1"></i>Plano: <?= htmlspecialchars($client['plan_name'] ?? 'N/A') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary fs-6" id="pageCounter"><?= $hasRestrictions ? "$selectedCount/$totalPages" : "$totalPages/$totalPages (acesso total)" ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apply from Plan -->
        <?php if (!empty($plans)): ?>
        <div class="card border-0 mb-4" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-copy text-akti me-2"></i>Aplicar Permissões de um Plano</h6>
                <form id="applyPlanForm" action="?page=permissions&action=applyPlan" method="POST">
                    <?= master_csrf_field() ?>
                    <input type="hidden" name="tenant_client_id" value="<?= $client['id'] ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <select name="plan_id" id="planSelect" class="form-select">
                                <option value="">Selecione um plano...</option>
                                <?php foreach ($plans as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['plan_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-primary w-100" id="applyPlanBtn">
                                <i class="fas fa-download me-2"></i>Aplicar do Plano
                            </button>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">Substitui as permissões atuais pelas definidas no plano selecionado.</small>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Permissions Form -->
        <form action="?page=permissions&action=update" method="POST" id="permForm" class="form-card">
            <?= master_csrf_field() ?>
            <input type="hidden" name="tenant_client_id" value="<?= $client['id'] ?>">

            <!-- Access Mode -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-key"></i> Modo de Acesso
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="access_mode" id="accessFull" value="full" <?= !$hasRestrictions ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="accessFull">
                                <i class="fas fa-unlock text-success me-1"></i> Acesso Total
                            </label>
                            <div class="form-text">O tenant tem acesso a todas as páginas.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="access_mode" id="accessRestricted" value="restricted" <?= $hasRestrictions ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="accessRestricted">
                                <i class="fas fa-lock text-warning me-1"></i> Acesso Restrito
                            </label>
                            <div class="form-text">Apenas as páginas selecionadas abaixo são permitidas.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Checkboxes (hidden when full access) -->
            <div id="restrictedSection" style="<?= $hasRestrictions ? '' : 'display:none;' ?>">

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
            </div>

            <!-- Actions -->
            <div class="form-section mb-0" style="position:sticky; bottom:0; z-index:10; background:white; margin:-32px; margin-top:0; padding:20px 32px; border-top:2px solid #e5e7eb; box-shadow:0 -4px 12px rgba(0,0,0,0.05); border-radius:0 0 12px 12px;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge bg-primary" id="pageCounterBottom"><?= $hasRestrictions ? "$selectedCount/$totalPages páginas" : "Acesso total" ?></span>
                    <div class="d-flex gap-2">
                        <a href="?page=clients&action=edit&id=<?= $client['id'] ?>" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-save me-2"></i>Salvar Permissões
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
