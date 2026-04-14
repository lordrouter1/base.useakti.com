<?php /** @var array $history */ /** @var bool $isConfigured */ ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-robot me-2"></i>Assistente IA</span>
                    <div>
                        <button class="btn btn-outline-danger btn-sm" id="btnClearHistory" title="Limpar histórico">
                            <i class="fas fa-trash me-1"></i>Limpar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!$isConfigured): ?>
                    <div class="alert alert-warning m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Assistente IA não configurado. Adicione a chave API em <strong>Configurações &gt; IA</strong>
                        (config_key: <code>ai_api_key</code>, <code>ai_api_url</code>, <code>ai_model</code> na tabela settings).
                    </div>
                    <?php endif; ?>

                    <!-- Chat messages area -->
                    <div id="chatMessages" class="p-3" style="height: 500px; overflow-y: auto;">
                        <?php if (empty($history)): ?>
                        <div class="text-center text-muted py-5" id="emptyState">
                            <i class="fas fa-robot fa-3x mb-3 opacity-25"></i>
                            <p>Olá! Sou o assistente virtual do Akti.<br>Como posso ajudar?</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($history as $msg): ?>
                            <div class="d-flex mb-3 <?= $msg['role'] === 'user' ? 'justify-content-end' : 'justify-content-start' ?>">
                                <div class="px-3 py-2 rounded-3 <?= $msg['role'] === 'user' ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width:75%">
                                    <div class="small fw-bold mb-1"><?= $msg['role'] === 'user' ? 'Você' : 'Assistente' ?></div>
                                    <div><?= e($msg['content']) ?></div>
                                    <div class="text-end mt-1 small opacity-50"><?= e($msg['created_at'] ?? '') ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Input area -->
                    <div class="border-top p-3">
                        <form id="chatForm" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="text" id="chatInput" class="form-control" placeholder="Digite sua pergunta..."
                                   autocomplete="off" <?= !$isConfigured ? 'disabled' : '' ?>>
                            <button type="submit" class="btn btn-primary" <?= !$isConfigured ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');

    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;

    function appendMessage(role, content) {
        const empty = document.getElementById('emptyState');
        if (empty) empty.remove();

        const align = role === 'user' ? 'justify-content-end' : 'justify-content-start';
        const bg = role === 'user' ? 'bg-primary text-white' : 'bg-light';
        const label = role === 'user' ? 'Você' : 'Assistente';
        const safe = DOMPurify.sanitize(content);

        const div = document.createElement('div');
        div.className = 'd-flex mb-3 ' + align;
        div.innerHTML = '<div class="px-3 py-2 rounded-3 ' + bg + '" style="max-width:75%">'
            + '<div class="small fw-bold mb-1">' + label + '</div>'
            + '<div>' + safe + '</div>'
            + '</div>';
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;

        appendMessage('user', msg);
        chatInput.value = '';
        chatInput.disabled = true;

        // Loading indicator
        const loading = document.createElement('div');
        loading.className = 'd-flex mb-3 justify-content-start';
        loading.id = 'aiLoading';
        loading.innerHTML = '<div class="px-3 py-2 rounded-3 bg-light"><i class="fas fa-spinner fa-spin me-2"></i>Pensando...</div>';
        chatMessages.appendChild(loading);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        $.ajax({
            url: '?page=ai_assistant&action=send',
            method: 'POST',
            data: { message: msg },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dataType: 'json',
            success: function(res) {
                const ld = document.getElementById('aiLoading');
                if (ld) ld.remove();
                chatInput.disabled = false;
                chatInput.focus();
                if (res.success) {
                    appendMessage('assistant', res.message);
                } else {
                    appendMessage('assistant', '⚠️ ' + (res.message || 'Erro ao processar.'));
                }
            },
            error: function() {
                const ld = document.getElementById('aiLoading');
                if (ld) ld.remove();
                chatInput.disabled = false;
                appendMessage('assistant', '⚠️ Erro de conexão com o servidor.');
            }
        });
    });

    document.getElementById('btnClearHistory').addEventListener('click', function() {
        Swal.fire({
            title: 'Limpar histórico?',
            text: 'Todo o histórico de conversa será removido.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Limpar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '?page=ai_assistant&action=clearHistory',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    dataType: 'json',
                    success: function() {
                        chatMessages.innerHTML = '<div class="text-center text-muted py-5" id="emptyState">'
                            + '<i class="fas fa-robot fa-3x mb-3 opacity-25"></i>'
                            + '<p>Olá! Sou o assistente virtual do Akti.<br>Como posso ajudar?</p></div>';
                        AktiToast.success('Histórico limpo.');
                    }
                });
            }
        });
    });
});
</script>
