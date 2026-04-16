<?php
/**
 * View: Suporte - Formulário de novo ticket
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="fas fa-plus-circle me-2 text-primary"></i>Novo Ticket de Suporte</h4>
        <p class="text-muted mb-0">Envie sua solicitação para a equipe Akti</p>
    </div>
    <a href="?page=suporte" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= e($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card border-0" style="border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="card-body p-4">
        <form action="?page=suporte&action=store" method="POST" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="subject" class="form-label">Assunto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="subject" name="subject" required
                           placeholder="Descreva brevemente o problema..." maxlength="255"
                           value="<?= e($_POST['subject'] ?? '') ?>">
                    <div class="invalid-feedback">Informe o assunto.</div>
                </div>

                <div class="col-md-4">
                    <label for="priority" class="form-label">Prioridade</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="low">Baixa</option>
                        <option value="medium" selected>Média</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label for="category" class="form-label">Categoria</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Selecione (opcional)</option>
                        <option value="bug">Bug / Erro</option>
                        <option value="duvida">Dúvida</option>
                        <option value="melhoria">Sugestão de Melhoria</option>
                        <option value="financeiro">Financeiro / Cobrança</option>
                        <option value="integracao">Integração</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="6" required
                              placeholder="Descreva o problema em detalhes. Inclua passos para reproduzir, se aplicável..."><?= e($_POST['description'] ?? '') ?></textarea>
                    <div class="invalid-feedback">Informe a descrição.</div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="?page=suporte" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
