<?php
/**
 * Portal do Cliente — Mensagens
 *
 * Variáveis: $messages, $orders, $orderId, $company
 */
$selectedOrderId = $orderId ?? 0;
?>

<div class="portal-page portal-page-messages">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-comments me-2"></i>
            <?= __p('messages_title') ?>
        </h1>
    </div>

    <!-- ═══ Filtrar por Pedido ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-body">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="page" value="portal">
                <input type="hidden" name="action" value="messages">
                <label class="form-label mb-0 text-nowrap"><?= __p('messages_filter_order') ?>:</label>
                <select name="order_id" class="form-select portal-input" onchange="this.form.submit()">
                    <option value=""><?= __p('all') ?></option>
                    <?php foreach ($orders as $ord): ?>
                        <option value="<?= (int) $ord['id'] ?>"
                            <?= (int) $ord['id'] === $selectedOrderId ? 'selected' : '' ?>>
                            #<?= (int) $ord['id'] ?> — <?= portal_money($ord['total_amount'] ?? 0) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if (!empty($_GET['sent'])): ?>
        <div class="alert alert-success alert-sm">
            <i class="fas fa-check-circle me-1"></i>
            <?= __p('messages_sent') ?>
        </div>
    <?php endif; ?>

    <!-- ═══ Lista de Mensagens (Chat) ═══ -->
    <div class="portal-chat-container" id="portalChatContainer">
        <?php if (empty($messages)): ?>
            <div class="portal-empty-state portal-empty-sm">
                <i class="fas fa-comments"></i>
                <p><?= __p('messages_empty') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php $isCustomer = ($msg['sender_type'] === 'customer'); ?>
                <div class="portal-chat-msg <?= $isCustomer ? 'portal-chat-msg-customer' : 'portal-chat-msg-admin' ?>">
                    <div class="portal-chat-bubble">
                        <?php if (!$isCustomer && !empty($msg['admin_name'])): ?>
                            <div class="portal-chat-sender">
                                <i class="fas fa-headset me-1"></i>
                                <?= e($msg['admin_name']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($msg['order_id'])): ?>
                            <div class="portal-chat-order-ref">
                                <i class="fas fa-box me-1"></i>
                                <?= __p('order_detail_title', ['id' => (int) $msg['order_id']]) ?>
                            </div>
                        <?php endif; ?>
                        <div class="portal-chat-text"><?= nl2br(e($msg['message'])) ?></div>
                        <?php if (!empty($msg['attachment_path'])): ?>
                            <div class="portal-chat-attachment">
                                <a href="<?= eAttr($msg['attachment_path']) ?>" target="_blank">
                                    <i class="fas fa-paperclip me-1"></i> <?= __p('messages_attachment') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="portal-chat-time">
                            <?= portal_datetime($msg['created_at']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══ Formulário de Envio ═══ -->
    <div class="portal-chat-input-bar">
        <form method="POST" action="?page=portal&action=sendMessage" class="d-flex gap-2 w-100"
              id="portalMessageForm">
            <?= csrf_field() ?>
            <?php if ($selectedOrderId > 0): ?>
                <input type="hidden" name="order_id" value="<?= $selectedOrderId ?>">
            <?php endif; ?>
            <input type="text" name="message" class="form-control portal-input"
                   placeholder="<?= __p('messages_placeholder') ?>"
                   required autocomplete="off" id="portalMessageInput">
            <button type="submit" class="btn btn-primary portal-chat-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
// Auto-scroll para o final do chat
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('portalChatContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }

    // AJAX submit para mensagens
    const form = document.getElementById('portalMessageForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('portalMessageInput');
            const message = input.value.trim();
            if (!message) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const orderId = form.querySelector('input[name="order_id"]')?.value || '';

            fetch('?page=portal&action=sendMessage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `message=${encodeURIComponent(message)}&order_id=${orderId}&csrf_token=${csrfToken}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Adicionar mensagem ao chat
                    const bubble = document.createElement('div');
                    bubble.className = 'portal-chat-msg portal-chat-msg-customer';
                    bubble.innerHTML = `<div class="portal-chat-bubble">
                        <div class="portal-chat-text">${message.replace(/</g, '&lt;').replace(/\n/g, '<br>')}</div>
                        <div class="portal-chat-time">Agora</div>
                    </div>`;
                    container.appendChild(bubble);
                    container.scrollTop = container.scrollHeight;
                    input.value = '';

                    // Remover empty state se existir
                    const empty = container.querySelector('.portal-empty-state');
                    if (empty) empty.remove();
                }
            })
            .catch(() => {
                // Fallback: submit normal
                form.submit();
            });
        });
    }
});
</script>
