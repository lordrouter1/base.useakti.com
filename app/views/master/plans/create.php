<?php
/**
 * View: Plans - Criar
 */
$pageTitle = 'Novo Plano';
$pageSubtitle = 'Cadastre um novo plano de assinatura';
$topbarActions = '<a href="?page=master_plans" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form action="?page=master_plans&action=store" method="POST" class="form-card">
                    <?= csrf_field() ?>
            <!-- Informações do Plano -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i> Informações do Plano
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nome do Plano <span class="text-danger">*</span></label>
                        <input type="text" name="plan_name" class="form-control" placeholder="Ex: Professional" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preço Mensal (R$) <span class="text-danger">*</span></label>
                        <input type="text" name="price" class="form-control" placeholder="0,00" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Descrição do plano..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Limites -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-sliders-h"></i> Limites do Plano
                    <small class="text-muted fw-normal ms-2" style="font-size:12px;">Deixe em branco para ilimitado</small>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-users me-1 text-akti"></i> Máx. Usuários</label>
                        <input type="number" name="max_users" class="form-control" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-boxes-stacked me-1 text-akti"></i> Máx. Produtos</label>
                        <input type="number" name="max_products" class="form-control" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-warehouse me-1 text-akti"></i> Máx. Armazéns</label>
                        <input type="number" name="max_warehouses" class="form-control" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-tags me-1 text-akti"></i> Máx. Tab. Preço</label>
                        <input type="number" name="max_price_tables" class="form-control" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-industry me-1 text-akti"></i> Máx. Setores</label>
                        <input type="number" name="max_sectors" class="form-control" min="0" placeholder="Ilimitado">
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="form-section mb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                        <label class="form-check-label fw-semibold" for="isActive">Plano Ativo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?page=master_plans" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-save me-2"></i>Salvar Plano
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
