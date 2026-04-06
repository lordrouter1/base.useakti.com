<?php
/**
 * View: Admin Users — Criar
 */
$pageTitle = 'Novo Administrador';
$pageSubtitle = 'Cadastrar novo usuário do painel Master';
$topbarActions = '<a href="?page=master_admins" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-user-plus" style="color: var(--akti-primary);"></i>
                <strong>Dados do Administrador</strong>
            </div>
            <div class="card-body">
                <form action="?page=master_admins&action=store" method="POST">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="name">
                                <i class="fas fa-user me-1 text-muted"></i>Nome *
                            </label>
                            <input type="text" name="name" id="name" class="form-control" required 
                                   placeholder="Nome completo" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email">
                                <i class="fas fa-envelope me-1 text-muted"></i>E-mail *
                            </label>
                            <input type="email" name="email" id="email" class="form-control" required 
                                   placeholder="admin@akti.com" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="password">
                                <i class="fas fa-lock me-1 text-muted"></i>Senha *
                            </label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8"
                                   placeholder="Mínimo 8 caracteres" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="role">
                                <i class="fas fa-shield-halved me-1 text-muted"></i>Papel *
                            </label>
                            <select name="role" id="role" class="form-select" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                                <option value="operator" selected>Operador</option>
                                <option value="superadmin">Super Admin</option>
                                <option value="viewer">Visualizador</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" id="isActive" class="form-check-input" checked>
                            <label class="form-check-label fw-semibold" for="isActive">Ativo</label>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="?page=master_admins" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-akti">
                            <i class="fas fa-save me-1"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
