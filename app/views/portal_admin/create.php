<?php
/**
 * Admin do Portal — Criar Novo Acesso
 *
 * Variáveis: $customers, $error, $success
 */
?>

<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">

            <!-- ═══ Header ═══ -->
            <div class="d-flex align-items-center mb-4">
                <a href="?page=portal_admin" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-plus me-2 text-primary"></i>
                        Criar Acesso ao Portal
                    </h1>
                    <small class="text-muted">Crie um acesso para um cliente existente acessar o portal.</small>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- ═══ Formulário ═══ -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="?page=portal_admin&action=store">
                        <?= csrf_field() ?>

                        <!-- Cliente -->
                        <div class="mb-3">
                            <label for="customer_id" class="form-label fw-semibold">
                                <i class="fas fa-user me-1"></i> Cliente *
                            </label>
                            <?php if (empty($customers)): ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Todos os clientes já possuem acesso ao portal.
                                    <a href="?page=customers&action=create" class="alert-link">Cadastrar novo cliente</a>
                                </div>
                            <?php else: ?>
                                <select name="customer_id" id="customer_id" class="form-select" required>
                                    <option value="">Selecione um cliente...</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>"
                                                data-email="<?= eAttr($c['email'] ?? '') ?>">
                                            <?= e($c['name']) ?>
                                            <?php if (!empty($c['email'])): ?>
                                                — <?= e($c['email']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- E-mail do Portal -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                <i class="fas fa-envelope me-1"></i> E-mail de Acesso *
                            </label>
                            <input type="email" name="email" id="email" class="form-control"
                                   placeholder="email@exemplo.com" required>
                            <div class="form-text">
                                E-mail que será usado para login no portal. Pode ser diferente do e-mail cadastral.
                            </div>
                        </div>

                        <!-- Senha (opcional) -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                <i class="fas fa-key me-1"></i> Senha (opcional)
                            </label>
                            <input type="text" name="password" id="password" class="form-control"
                                   placeholder="Deixe em branco para acesso apenas por link mágico"
                                   autocomplete="new-password">
                            <div class="form-text">
                                Se não definir senha, o cliente poderá acessar apenas via link mágico.
                            </div>
                        </div>

                        <!-- Enviar Magic Link -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="send_magic_link" value="1"
                                       class="form-check-input" id="send_magic_link">
                                <label class="form-check-label" for="send_magic_link">
                                    Gerar link mágico de acesso após criar
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="?page=portal_admin" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary"
                                    <?= empty($customers) ? 'disabled' : '' ?>>
                                <i class="fas fa-check me-1"></i> Criar Acesso
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Auto-preencher e-mail ao selecionar cliente
document.getElementById('customer_id')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const email = selected?.dataset?.email || '';
    const emailField = document.getElementById('email');
    if (email && emailField && !emailField.value) {
        emailField.value = email;
    }
});
</script>
