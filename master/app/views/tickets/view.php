<?php
/**
 * View: Tickets - Detalhe + Chat
 */
$pageTitle = 'Ticket #' . $ticket['id'];
$pageSubtitle = htmlspecialchars($ticket['tenant_name'] ?? '') . ' — ' . htmlspecialchars($ticket['subject'] ?? $ticket['title'] ?? 'Sem assunto');
$topbarActions = '<a href="?page=tickets" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

$statusLabels = [
    'open' => ['label' => 'Aberto', 'color' => 'primary', 'icon' => 'fas fa-envelope-open'],
    'in_progress' => ['label' => 'Em Andamento', 'color' => 'warning', 'icon' => 'fas fa-spinner'],
    'resolved' => ['label' => 'Resolvido', 'color' => 'success', 'icon' => 'fas fa-check-circle'],
    'closed' => ['label' => 'Fechado', 'color' => 'secondary', 'icon' => 'fas fa-times-circle'],
];
$priorityLabels = [
    'urgent' => ['label' => 'Urgente', 'color' => 'danger'],
    'high' => ['label' => 'Alta', 'color' => 'warning'],
    'medium' => ['label' => 'Média', 'color' => 'info'],
    'low' => ['label' => 'Baixa', 'color' => 'success'],
];

$st = $statusLabels[$ticket['status'] ?? ''] ?? ['label' => $ticket['status'] ?? 'N/A', 'color' => 'secondary', 'icon' => 'fas fa-circle'];
$pr = $priorityLabels[$ticket['priority'] ?? 'medium'] ?? ['label' => 'Média', 'color' => 'info'];

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    // Confirm status change
    $('#statusForm').on('submit', function(e) {
        const newStatus = $('#newStatus').val();
        const labels = {open:'Aberto', in_progress:'Em Andamento', resolved:'Resolvido', closed:'Fechado'};
        e.preventDefault();
        Swal.fire({
            title: 'Alterar status?',
            text: 'Alterar para: ' + (labels[newStatus] || newStatus),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, alterar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
    });

    // Scroll to bottom of messages
    const msgContainer = document.getElementById('messagesContainer');
    if (msgContainer) {
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }
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
                        <td class="text-muted" style="width:100px;">ID</td>
                        <td class="fw-bold">#<?= $ticket['id'] ?></td>
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
                        <td class="text-muted">Criado por</td>
                        <td><?= htmlspecialchars($ticket['user_name'] ?? 'Sistema') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">E-mail</td>
                        <td><small><?= htmlspecialchars($ticket['user_email'] ?? '') ?></small></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Prioridade</td>
                        <td><span class="badge bg-<?= $pr['color'] ?>"><?= $pr['label'] ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><span class="badge bg-<?= $st['color'] ?>"><i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?></span></td>
                    </tr>
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
                    <?php if (!empty($ticket['source'])): ?>
                    <tr>
                        <td class="text-muted">Fonte</td>
                        <td><small><?= htmlspecialchars($ticket['source']) ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($ticket['category'])): ?>
                    <tr>
                        <td class="text-muted">Categoria</td>
                        <td><small><?= htmlspecialchars($ticket['category']) ?></small></td>
                    </tr>
                    <?php endif; ?>
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
                    <input type="hidden" name="tenant_client_id" value="<?= $ticket['tenant_client_id'] ?>">
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

        <!-- Master Reply Log -->
        <?php if (!empty($masterReplies)): ?>
        <div class="card border-0 mt-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-history text-akti me-2"></i>Log de Ações</h6>
            </div>
            <div class="card-body p-2" style="max-height:300px; overflow-y:auto;">
                <?php foreach ($masterReplies as $reply): ?>
                <div class="border-bottom py-2 px-2">
                    <div class="d-flex justify-content-between">
                        <small class="fw-semibold"><?= htmlspecialchars($reply['admin_name'] ?? 'Admin') ?></small>
                        <small class="text-muted"><?= date('d/m H:i', strtotime($reply['created_at'])) ?></small>
                    </div>
                    <small class="text-muted">
                        <?php if ($reply['action_type'] === 'status_change'): ?>
                            <i class="fas fa-exchange-alt me-1"></i><?= htmlspecialchars($reply['message']) ?>
                        <?php else: ?>
                            <i class="fas fa-reply me-1"></i><?= htmlspecialchars(mb_substr($reply['message'], 0, 100)) ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Messages + Reply -->
    <div class="col-lg-8">
        <!-- Ticket Description -->
        <?php if (!empty($ticket['description'] ?? $ticket['message'] ?? '')): ?>
        <div class="card border-0 mb-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-align-left text-akti me-2"></i>Descrição</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['description'] ?? $ticket['message'] ?? '')) ?></p>
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
                    <?php 
                        $isSupport = $msg['user_id'] === null || str_starts_with($msg['message'] ?? '', '[Suporte Akti');
                    ?>
                    <div class="d-flex mb-3 <?= $isSupport ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="p-3 rounded-3" style="max-width:80%; <?= $isSupport 
                            ? 'background:linear-gradient(135deg, #667eea, #764ba2); color:#fff;' 
                            : 'background:#f1f3f5; color:#333;' ?>">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-bold <?= $isSupport ? 'text-white-50' : 'text-muted' ?>">
                                    <?= htmlspecialchars($msg['user_name'] ?? ($isSupport ? 'Suporte Akti' : 'Usuário')) ?>
                                </small>
                                <small class="<?= $isSupport ? 'text-white-50' : 'text-muted' ?> ms-3">
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
                    <input type="hidden" name="tenant_client_id" value="<?= $ticket['tenant_client_id'] ?>">
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="4" 
                                  placeholder="Digite sua resposta..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Resposta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
