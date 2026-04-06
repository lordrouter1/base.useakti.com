<?php
/**
 * View: Admin Users — Editar
 */
$pageTitle = 'Editar Administrador';
$pageSubtitle = htmlspecialchars($admin['name']);
$topbarActions = '<a href="?page=master_admins" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-user-edit" style="color: var(--akti-primary);"></i>
                <strong>Dados do Administrador</strong>
            </div>
            <div class="card-body">
                <form action="?page=master_admins&action=update" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="name">
                                <i class="fas fa-user me-1 text-muted"></i>Nome *
                            </label>
                            <input type="text" name="name" id="name" class="form-control" required 
                                   value="<?= htmlspecialchars($admin['name']) ?>"
                                   style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email">
                                <i class="fas fa-envelope me-1 text-muted"></i>E-mail *
                            </label>
                            <input type="email" name="email" id="email" class="form-control" required 
                                   value="<?= htmlspecialchars($admin['email']) ?>"
                                   style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="password">
                                <i class="fas fa-lock me-1 text-muted"></i>Nova Senha
                            </label>
                            <input type="password" name="password" id="password" class="form-control" minlength="8"
                                   placeholder="Deixe em branco para manter" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                            <small class="text-muted">Deixe em branco para manter a senha atual.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="role">
                                <i class="fas fa-shield-halved me-1 text-muted"></i>Papel *
                            </label>
                            <select name="role" id="role" class="form-select" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;"
                                    <?= $admin['id'] === ($_SESSION['master_admin_id'] ?? 0) ? 'disabled' : '' ?>>
                                <option value="superadmin" <?= ($admin['role'] ?? 'superadmin') === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="operator" <?= ($admin['role'] ?? '') === 'operator' ? 'selected' : '' ?>>Operador</option>
                                <option value="viewer" <?= ($admin['role'] ?? '') === 'viewer' ? 'selected' : '' ?>>Visualizador</option>
                            </select>
                            <?php if ($admin['id'] === ($_SESSION['master_admin_id'] ?? 0)): ?>
                                <input type="hidden" name="role" value="superadmin">
                                <small class="text-muted">Não é possível alterar seu próprio papel.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" id="isActive" class="form-check-input" 
                                   <?= $admin['is_active'] ? 'checked' : '' ?>
                                   <?= $admin['id'] === ($_SESSION['master_admin_id'] ?? 0) ? 'disabled' : '' ?>>
                            <label class="form-check-label fw-semibold" for="isActive">Ativo</label>
                            <?php if ($admin['id'] === ($_SESSION['master_admin_id'] ?? 0)): ?>
                                <input type="hidden" name="is_active" value="1">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Info adicional -->
                    <div class="mt-4 p-3 rounded-3" style="background:#f8f9fa; font-size:13px;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <span class="text-muted">Criado em:</span>
                                <strong><?= date('d/m/Y H:i', strtotime($admin['created_at'])) ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted">Último login:</span>
                                <strong><?= $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Nunca' ?></strong>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="?page=master_admins" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-akti">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
