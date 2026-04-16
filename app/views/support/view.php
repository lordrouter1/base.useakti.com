<?php
/**
 * View: Suporte - Detalhe do ticket + Chat
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

$st = $statusLabels[$ticket['status'] ?? ''] ?? ['label' => 'N/A', 'color' => 'secondary', 'icon' => 'fas fa-circle'];
$pr = $priorityLabels[$ticket['priority'] ?? 'medium'] ?? ['label' => 'Média', 'color' => 'info'];
$isClosed = in_array($ticket['status'] ?? '', ['resolved', 'closed'], true);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">
            <i class="fas fa-life-ring me-2 text-primary"></i>
            <?= e($ticket['ticket_number'] ?? '') ?> — <?= e($ticket['subject'] ?? '') ?>
        </h4>
        <p class="text-muted mb-0">
            <span class="badge bg-<?= $st['color'] ?>"><i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?></span>
            <span class="badge bg-<?= $pr['color'] ?> ms-1"><?= $pr['label'] ?></span>
            <?php if (!empty($ticket['category'])): ?>
                <span class="badge bg-light text-dark ms-1"><?= e($ticket['category']) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <a href="?page=suporte" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Voltar
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

<div class="row g-4">
    <!-- Info -->
    <div class="col-lg-4">
        <div class="card border-0" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Informações</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width:90px;">Número</td>
                        <td class="fw-bold"><?= e($ticket['ticket_number'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Prioridade</td>
                        <td><span class="badge bg-<?= $pr['color'] ?>"><?= $pr['label'] ?></span></td>
                    </tr>
                    <?php if (!empty($ticket['category'])): ?>
                    <tr>
                        <td class="text-muted">Categoria</td>
                        <td><?= e($ticket['category']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Criado</td>
                        <td><small><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></small></td>
                    </tr>
                    <?php if (!empty($ticket['updated_at'])): ?>
                    <tr>
                        <td class="text-muted">Atualizado</td>
                        <td><small><?= date('d/m/Y H:i', strtotime($ticket['updated_at'])) ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($ticket['resolved_at'])): ?>
                    <tr>
                        <td class="text-muted">Resolvido</td>
                        <td><small class="text-success"><?= date('d/m/Y H:i', strtotime($ticket['resolved_at'])) ?></small></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Messages + Reply -->
    <div class="col-lg-8">
        <!-- Description -->
        <?php if (!empty($ticket['description'])): ?>
        <div class="card border-0 mb-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-align-left text-primary me-2"></i>Descrição</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(e($ticket['description'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Messages -->
        <div class="card border-0 mb-3" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-comments text-primary me-2"></i>Mensagens (<?= count($messages) ?>)</h6>
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
                    <div class="d-flex mb-3 <?= $isAdmin ? 'justify-content-start' : 'justify-content-end' ?>">
                        <div class="p-3 rounded-3" style="max-width:80%; <?= $isAdmin
                            ? 'background:linear-gradient(135deg, #667eea, #764ba2); color:#fff;'
                            : 'background:#f1f3f5; color:#333;' ?>">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-bold <?= $isAdmin ? 'text-white-50' : 'text-muted' ?>">
                                    <?= e($msg['sender_name'] ?? 'Usuário') ?>
                                    <?php if ($isAdmin): ?><span class="badge bg-light text-dark ms-1" style="font-size:9px;">Suporte Akti</span><?php endif; ?>
                                </small>
                                <small class="<?= $isAdmin ? 'text-white-50' : 'text-muted' ?> ms-3">
                                    <?= date('d/m H:i', strtotime($msg['created_at'])) ?>
                                </small>
                            </div>
                            <div style="font-size:14px;">
                                <?= nl2br(e($msg['message'] ?? '')) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Form -->
        <?php if (!$isClosed): ?>
        <div class="card border-0" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-reply text-primary me-2"></i>Enviar Mensagem</h6>
            </div>
            <div class="card-body">
                <form action="?page=suporte&action=addMessage" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="4"
                                  placeholder="Digite sua mensagem..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-paper-plane me-2"></i>Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-secondary text-center">
            <i class="fas fa-lock me-2"></i>Este ticket está <?= $ticket['status'] === 'closed' ? 'fechado' : 'resolvido' ?> e não aceita novas mensagens.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    const msgContainer = document.getElementById('messagesContainer');
    if (msgContainer) { msgContainer.scrollTop = msgContainer.scrollHeight; }
});
</script>
