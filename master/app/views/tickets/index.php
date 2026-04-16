<?php
/**
 * View: Tickets de Suporte - Listagem centralizada (Master)
 */
$pageTitle = 'Tickets de Suporte';
$pageSubtitle = 'Gerenciamento centralizado';

$statusLabels = [
    'open'             => ['label' => 'Aberto', 'color' => 'primary', 'icon' => 'fas fa-envelope-open'],
    'in_progress'      => ['label' => 'Em Andamento', 'color' => 'warning', 'icon' => 'fas fa-spinner'],
    'waiting_customer' => ['label' => 'Aguardando Cliente', 'color' => 'info', 'icon' => 'fas fa-clock'],
    'resolved'         => ['label' => 'Resolvido', 'color' => 'success', 'icon' => 'fas fa-check-circle'],
    'closed'           => ['label' => 'Fechado', 'color' => 'secondary', 'icon' => 'fas fa-times-circle'],
];
$priorityLabels = [
    'urgent' => ['label' => 'Urgente', 'color' => 'danger', 'icon' => '🔴'],
    'high'   => ['label' => 'Alta', 'color' => 'warning', 'icon' => '🟡'],
    'medium' => ['label' => 'Média', 'color' => 'info', 'icon' => '🔵'],
    'low'    => ['label' => 'Baixa', 'color' => 'success', 'icon' => '🟢'],
];

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

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= $stats['total'] ?></div>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= $stats['open'] ?></div>
                <small class="text-muted">Abertos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-warning"><?= $stats['in_progress'] ?></div>
                <small class="text-muted">Em Andamento</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= $stats['resolved'] ?></div>
                <small class="text-muted">Resolvidos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-secondary"><?= $stats['closed'] ?></div>
                <small class="text-muted">Fechados</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-danger"><?= $stats['urgent'] ?></div>
                <small class="text-muted">Urgentes</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 mb-4" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="tickets">
            <div class="col-md-3">
                <label class="form-label small mb-1">Tenant</label>
                <select name="tenant_id" class="form-select form-select-sm">
                    <option value="">Todos os tenants</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_GET['tenant_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['client_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($statusLabels as $key => $sl): ?>
                        <option value="<?= $key ?>" <?= ($_GET['status'] ?? '') === $key ? 'selected' : '' ?>><?= $sl['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Prioridade</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($priorityLabels as $key => $pl): ?>
                        <option value="<?= $key ?>" <?= ($_GET['priority'] ?? '') === $key ? 'selected' : '' ?>><?= $pl['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Buscar</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Assunto ou número..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-akti btn-sm w-100">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table -->
<div class="card border-0" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox text-muted" style="font-size:3rem;"></i>
                <p class="text-muted mt-3">Nenhum ticket de suporte encontrado.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:90px;">Número</th>
                        <th>Assunto</th>
                        <th>Tenant</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Aberto por</th>
                        <th>Responsável</th>
                        <th>Data</th>
                        <th style="width:80px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                    <?php
                        $st = $statusLabels[$t['status'] ?? ''] ?? ['label' => $t['status'] ?? 'N/A', 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                        $pr = $priorityLabels[$t['priority'] ?? 'medium'] ?? ['label' => 'Média', 'color' => 'info', 'icon' => '🔵'];
                    ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="?page=tickets&action=view&id=<?= $t['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($t['ticket_number'] ?? '') ?>
                            </a>
                        </td>
                        <td>
                            <a href="?page=tickets&action=view&id=<?= $t['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars(mb_substr($t['subject'] ?? '', 0, 60)) ?>
                            </a>
                            <?php if (!empty($t['category'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($t['category']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark" style="font-size:11px;">
                                <?= htmlspecialchars($t['tenant_name'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $pr['color'] ?>" style="font-size:11px;">
                                <?= $pr['icon'] ?> <?= $pr['label'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $st['color'] ?>" style="font-size:11px;">
                                <i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
                            </span>
                        </td>
                        <td>
                            <small><?= htmlspecialchars($t['created_by_name'] ?? '') ?></small>
                        </td>
                        <td>
                            <small><?= htmlspecialchars($t['assigned_admin_name'] ?? '—') ?></small>
                        </td>
                        <td>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small>
                        </td>
                        <td>
                            <a href="?page=tickets&action=view&id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
