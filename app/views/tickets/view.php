<?php
/**
 * Tickets — Visualização e mensagens
 * Variáveis: $ticket, $messages
 */
$statusColors = ['open' => 'primary', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
$statusLabels = ['open' => 'Aberto', 'in_progress' => 'Em Andamento', 'resolved' => 'Resolvido', 'closed' => 'Fechado'];
$priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'dark'];
$priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'];
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-headset me-2 text-primary"></i><?= e($ticket['ticket_number']) ?> — <?= e($ticket['subject']) ?></h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">
                <span class="badge bg-<?= $priorityColors[$ticket['priority']] ?? 'secondary' ?>"><?= $priorityLabels[$ticket['priority']] ?? $ticket['priority'] ?></span>
                <span class="badge bg-<?= $statusColors[$ticket['status']] ?? 'secondary' ?>"><?= $statusLabels[$ticket['status']] ?? $ticket['status'] ?></span>
                &middot; Criado em <?= e(date('d/m/Y H:i', strtotime($ticket['created_at']))) ?>
            </p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=tickets" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Descrição -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Descrição</strong></div>
                <div class="card-body"><?= nl2br(e($ticket['description'] ?? 'Sem descrição.')) ?></div>
            </div>

            <!-- Mensagens -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Mensagens</strong> <span class="badge bg-secondary"><?= count($messages) ?></span></div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <p class="text-muted">Nenhuma mensagem ainda.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="border-start border-3 <?= $msg['is_internal'] ? 'border-warning' : 'border-primary' ?> ps-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= e($msg['user_name'] ?? 'Sistema') ?></strong>
                                <small class="text-muted"><?= e(date('d/m/Y H:i', strtotime($msg['created_at']))) ?></small>
                            </div>
                            <?php if ($msg['is_internal']): ?><span class="badge bg-warning text-dark mb-1">Nota interna</span><?php endif; ?>
                            <p class="mb-0"><?= nl2br(e($msg['message'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($ticket['status'] !== 'closed'): ?>
                    <hr>
                    <form method="post" action="?page=tickets&action=addMessage">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                        <textarea name="message" class="form-control mb-2" rows="3" placeholder="Escreva uma mensagem..." required></textarea>
                        <div class="d-flex justify-content-between">
                            <div class="form-check">
                                <input type="checkbox" name="is_internal" value="1" class="form-check-input" id="chkInternal">
                                <label class="form-check-label" for="chkInternal">Nota interna</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane me-1"></i>Enviar</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Alterar status -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Alterar Status</strong></div>
                <div class="card-body">
                    <form method="post" action="?page=tickets&action=updateStatus">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
                        <select name="status" class="form-select form-select-sm mb-2">
                            <?php foreach ($statusLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $ticket['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-sync me-1"></i>Atualizar</button>
                    </form>
                </div>
            </div>

            <!-- Detalhes -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Detalhes</strong></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Solicitante:</strong> <?= e($ticket['requester_name'] ?? '-') ?></p>
                    <p class="mb-1"><strong>Responsável:</strong> <?= e($ticket['assigned_name'] ?? 'Não atribuído') ?></p>
                    <p class="mb-1"><strong>Categoria:</strong> <?= e($ticket['category_name'] ?? '-') ?></p>
                    <p class="mb-1"><strong>SLA:</strong> <?= (int) ($ticket['sla_hours'] ?? 0) ?>h</p>
                    <?php if (!empty($ticket['first_response_at'])): ?>
                    <p class="mb-1"><strong>1ª Resposta:</strong> <?= e(date('d/m/Y H:i', strtotime($ticket['first_response_at']))) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($ticket['resolved_at'])): ?>
                    <p class="mb-1"><strong>Resolvido em:</strong> <?= e(date('d/m/Y H:i', strtotime($ticket['resolved_at']))) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
