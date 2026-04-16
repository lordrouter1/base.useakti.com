<?php
/**
 * View: Suporte - Listagem de tickets do tenant
 */

$statusLabels = [
    'open'             => ['label' => 'Aberto', 'color' => 'primary', 'icon' => 'fas fa-envelope-open'],
    'in_progress'      => ['label' => 'Em Andamento', 'color' => 'warning', 'icon' => 'fas fa-spinner'],
    'waiting_customer' => ['label' => 'Aguardando Resposta', 'color' => 'info', 'icon' => 'fas fa-clock'],
    'resolved'         => ['label' => 'Resolvido', 'color' => 'success', 'icon' => 'fas fa-check-circle'],
    'closed'           => ['label' => 'Fechado', 'color' => 'secondary', 'icon' => 'fas fa-times-circle'],
];
$priorityLabels = [
    'urgent' => ['label' => 'Urgente', 'color' => 'danger'],
    'high'   => ['label' => 'Alta', 'color' => 'warning'],
    'medium' => ['label' => 'Média', 'color' => 'info'],
    'low'    => ['label' => 'Baixa', 'color' => 'success'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="fas fa-life-ring me-2 text-primary"></i>Suporte</h4>
        <p class="text-muted mb-0">Seus tickets de suporte com a equipe Akti</p>
    </div>
    <a href="?page=suporte&action=create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Novo Ticket
    </a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= e($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= e($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= (int) ($stats['total'] ?? 0) ?></div>
                <small class="text-muted">Total de Tickets</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-warning"><?= (int) ($stats['active'] ?? 0) ?></div>
                <small class="text-muted">Em Aberto</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 h-100" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= (int) ($stats['resolved'] ?? 0) ?></div>
                <small class="text-muted">Resolvidos</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 mb-4" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="suporte">
            <div class="col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($statusLabels as $key => $sl): ?>
                        <option value="<?= $key ?>" <?= ($_GET['status'] ?? '') === $key ? 'selected' : '' ?>><?= $sl['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
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
                <p class="text-muted mt-3 mb-1">Nenhum ticket encontrado.</p>
                <a href="?page=suporte&action=create" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-plus me-1"></i>Criar primeiro ticket
                </a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Assunto</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th style="width:80px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                    <?php
                        $stl = $statusLabels[$t['status'] ?? ''] ?? ['label' => 'N/A', 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                        $prl = $priorityLabels[$t['priority'] ?? 'medium'] ?? ['label' => 'Média', 'color' => 'info'];
                    ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="?page=suporte&action=view&id=<?= $t['id'] ?>" class="text-decoration-none">
                                <?= e($t['ticket_number'] ?? '') ?>
                            </a>
                        </td>
                        <td>
                            <a href="?page=suporte&action=view&id=<?= $t['id'] ?>" class="text-decoration-none">
                                <?= e(mb_substr($t['subject'] ?? '', 0, 60)) ?>
                            </a>
                            <?php if (!empty($t['category'])): ?>
                                <br><small class="text-muted"><?= e($t['category']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $prl['color'] ?>" style="font-size:11px;"><?= $prl['label'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $stl['color'] ?>" style="font-size:11px;">
                                <i class="<?= $stl['icon'] ?> me-1"></i><?= $stl['label'] ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small>
                        </td>
                        <td>
                            <a href="?page=suporte&action=view&id=<?= $t['id'] ?>"
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
