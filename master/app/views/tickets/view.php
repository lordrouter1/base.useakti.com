<?php
/**
 * View: Ticket de Suporte - Detalhe + Chat (Master)
 */
$pageTitle = htmlspecialchars($ticket['ticket_number'] ?? 'Ticket #' . $ticket['id']);
$pageSubtitle = htmlspecialchars($ticket['tenant_name'] ?? '') . ' — ' . htmlspecialchars($ticket['subject'] ?? '');
$topbarActions = '<a href="?page=tickets" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

$statusLabels = [
    'open'             => ['label' => 'Aberto', 'color' => 'primary', 'icon' => 'fas fa-envelope-open'],
    'in_progress'      => ['label' => 'Em Andamento', 'color' => 'warning', 'icon' => 'fas fa-spinner'],
    'waiting_customer' => ['label' => 'Aguardando Cliente', 'color' => 'info', 'icon' => 'fas fa-clock'],
    'resolved'         => ['label' => 'Resolvido', 'color' => 'success', 'icon' => 'fas fa-check-circle'],
    'closed'           => ['label' => 'Fechado', 'color' => 'secondary', 'icon' => 'fas fa-times-circle'],
];
$priorityLabels = [
    'urgent' => ['label' => 'Urgente', 'color' => 'danger'],
    'high'   => ['label' => 'Alta', 'color' => 'warning'],
    'medium' => ['label' => 'Média', 'color' => 'info'],
    'low'    => ['label' => 'Baixa', 'color' => 'success'],
];

$st = $statusLabels[$ticket['status'] ?? ''] ?? ['label' => $ticket['status'] ?? 'N/A', 'color' => 'secondary', 'icon' => 'fas fa-circle'];
$pr = $priorityLabels[$ticket['priority'] ?? 'medium'] ?? ['label' => 'Média', 'color' => 'info'];

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    $('#statusForm').on('submit', function(e) {
        const newStatus = $('#newStatus').val();
        const labels = {open:'Aberto', in_progress:'Em Andamento', waiting_customer:'Aguardando Cliente', resolved:'Resolvido', closed:'Fechado'};
        e.preventDefault();
        Swal.fire({
            title: 'Alterar status?',
            text: 'Alterar para: ' + (labels[newStatus] || newStatus),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, alterar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) { e.target.submit(); }
        });
    });

    const msgContainer = document.getElementById('messagesContainer');
    if (msgContainer) { msgContainer.scrollTop = msgContainer.scrollHeight; }
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

<div class="row g-4">
    <!-- Ticket Info -->
    <div class="col-lg-4">
        <div class="card border-0" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-info-circle text-akti me-2"></i>Informações</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width:110px;">Número</td>
                        <td class="fw-bold"><?= htmlspecialchars($ticket['ticket_number'] ?? '#' . $ticket['id']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tenant</td>
                        <td>
                            <a href="?page=clients&action=edit&id=<?= $ticket['tenant_client_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($ticket['tenant_name'] ?? '') ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Aberto por</td>
                        <td><?= htmlspecialchars($ticket['created_by_name'] ?? 'Sistema') ?></td>
                    </tr>
                    <?php if (!empty($ticket['created_by_email'])): ?>
                    <tr>
                        <td class="text-muted">E-mail</td>
                        <td><small><?= htmlspecialchars($ticket['created_by_email']) ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Prioridade</td>
                        <td><span class="badge bg-<?= $pr['color'] ?>"><?= $pr['label'] ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><span class="badge bg-<?= $st['color'] ?>"><i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?></span></td>
                    </tr>
                    <?php if (!empty($ticket['category'])): ?>
                    <tr>
                        <td class="text-muted">Categoria</td>
                        <td><small><?= htmlspecialchars($ticket['category']) ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Criado em</td>
                        <td><small><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></small></td>
                    </tr>
                    <?php if (!empty($ticket['updated_at'])): ?>
                    <tr>
                        <td class="text-muted">Atualizado</td>
                        <td><small><?= date('d/m/Y H:i', strtotime($ticket['updated_at'])) ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Responsável</td>
                        <td><small><?= htmlspecialchars($ticket['assigned_admin_name'] ?? 'Não atribuído') ?></small></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Change Status -->
        <div class="card border-0 mt-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-exchange-alt text-akti me-2"></i>Alterar Status</h6>
            </div>
            <div class="card-body">
                <form id="statusForm" action="?page=tickets&action=changeStatus" method="POST">
                    <?= master_csrf_field() ?>
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    <div class="mb-2">
                        <select name="new_status" id="newStatus" class="form-select form-select-sm">
                            <?php foreach ($statusLabels as $key => $sl): ?>
                                <option value="<?= $key ?>" <?= ($ticket['status'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $sl['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-save me-1"></i>Salvar Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Assign Admin -->
        <div class="card border-0 mt-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-user-shield text-akti me-2"></i>Atribuir Responsável</h6>
            </div>
            <div class="card-body">
                <form action="?page=tickets&action=assign" method="POST">
                    <?= master_csrf_field() ?>
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    <div class="mb-2">
                        <select name="assigned_admin_id" class="form-select form-select-sm">
                            <option value="">Nenhum</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= $admin['id'] ?>" <?= ($ticket['assigned_admin_id'] ?? '') == $admin['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($admin['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-user-check me-1"></i>Atribuir
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Messages + Reply -->
    <div class="col-lg-8">
        <!-- Ticket Description -->
        <?php if (!empty($ticket['description'])): ?>
        <div class="card border-0 mb-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-align-left text-akti me-2"></i>Descrição</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Messages Timeline -->
        <div class="card border-0 mb-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-comments text-akti me-2"></i>Mensagens (<?= count($messages) ?>)</h6>
            </div>
            <div class="card-body p-3" id="messagesContainer" style="max-height:500px; overflow-y:auto;">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash text-muted" style="font-size:2rem;"></i>
                        <p class="text-muted mt-2">Nenhuma mensagem ainda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <?php $isAdmin = ($msg['sender_type'] ?? '') === 'admin'; ?>
                    <div class="d-flex mb-3 <?= $isAdmin ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="p-3 rounded-3" style="max-width:80%; <?= $isAdmin
                            ? 'background:linear-gradient(135deg, #667eea, #764ba2); color:#fff;'
                            : 'background:#f1f3f5; color:#333;' ?>">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-bold <?= $isAdmin ? 'text-white-50' : 'text-muted' ?>">
                                    <?= htmlspecialchars($msg['sender_name'] ?? 'Usuário') ?>
                                    <?php if ($isAdmin): ?><span class="badge bg-light text-dark ms-1" style="font-size:9px;">Admin</span><?php endif; ?>
                                    <?php if (!empty($msg['is_internal_note'])): ?><span class="badge bg-warning text-dark ms-1" style="font-size:9px;">Nota Interna</span><?php endif; ?>
                                </small>
                                <small class="<?= $isAdmin ? 'text-white-50' : 'text-muted' ?> ms-3">
                                    <?= date('d/m H:i', strtotime($msg['created_at'])) ?>
                                </small>
                            </div>
                            <div style="font-size:14px;">
                                <?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Form -->
        <div class="card border-0" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-reply text-akti me-2"></i>Responder</h6>
            </div>
            <div class="card-body">
                <form action="?page=tickets&action=reply" method="POST">
                    <?= master_csrf_field() ?>
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="4"
                                  placeholder="Digite sua resposta..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_internal_note" value="1" id="internalNote">
                            <label class="form-check-label small text-muted" for="internalNote">
                                <i class="fas fa-lock me-1"></i>Nota interna (não visível para o tenant)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-paper-plane me-2"></i>Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
