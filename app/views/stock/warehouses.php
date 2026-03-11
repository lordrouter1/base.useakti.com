<?php
/**
 * Estoque — Gerenciar Armazéns
 * Variáveis: $warehouses
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-building me-2"></i>Armazéns / Locais de Estoque</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=stock" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Estoque</a>
        <?php if (!empty($limitReached)): ?>
            <button type="button" class="btn btn-sm btn-primary disabled" disabled title="Limite do plano atingido">
                <i class="fas fa-plus me-1"></i> Novo Armazém
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="openNewWarehouse()">
                <i class="fas fa-plus me-1"></i> Novo Armazém
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($limitReached)): ?>
<div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
    <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
    <div>
        <strong>Limite do plano atingido!</strong> Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> armazéns permitidos.
        <span class="text-muted">Para cadastrar mais armazéns, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Lista de Armazéns ══════ -->
<div class="row g-3">
    <?php foreach ($warehouses as $wh): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm warehouse-card <?= $wh['is_active'] ? '' : 'opacity-50' ?>">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0">
                    <i class="fas fa-warehouse me-2 text-primary"></i><?= e($wh['name']) ?>
                    <?php if (!empty($wh['is_default'])): ?>
                        <span class="badge bg-success ms-1"><i class="fas fa-star me-1"></i>Padrão</span>
                    <?php endif; ?>
                    <?php if (!$wh['is_active']): ?>
                        <span class="badge bg-secondary ms-1">Inativo</span>
                    <?php endif; ?>
                </h5>
                <div class="btn-group btn-group-sm">
                    <?php if (empty($wh['is_default']) && $wh['is_active']): ?>
                    <button type="button" class="btn btn-outline-success btn-set-default-wh"
                            data-id="<?= $wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>"
                            title="Definir como padrão">
                        <i class="fas fa-star"></i>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-primary btn-edit-wh"
                            data-id="<?= $wh['id'] ?>"
                            data-name="<?= eAttr($wh['name']) ?>"
                            data-address="<?= eAttr($wh['address'] ?? '') ?>"
                            data-city="<?= eAttr($wh['city'] ?? '') ?>"
                            data-state="<?= eAttr($wh['state'] ?? '') ?>"
                            data-zip="<?= eAttr($wh['zip_code'] ?? '') ?>"
                            data-phone="<?= eAttr($wh['phone'] ?? '') ?>"
                            data-notes="<?= eAttr($wh['notes'] ?? '') ?>"
                            data-active="<?= $wh['is_active'] ?>"
                            data-default="<?= $wh['is_default'] ?? 0 ?>"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($wh['total_items'] == 0): ?>
                    <button type="button" class="btn btn-outline-danger btn-delete-wh"
                            data-id="<?= $wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body py-3">
                <?php if ($wh['address']): ?>
                <p class="mb-1 small"><i class="fas fa-map-marker-alt text-danger me-2 opacity-50"></i><?= e($wh['address']) ?></p>
                <?php endif; ?>
                <?php if ($wh['city'] || $wh['state']): ?>
                <p class="mb-1 small"><i class="fas fa-city text-muted me-2 opacity-50"></i><?= e(trim($wh['city'] . ' - ' . $wh['state'], ' - ')) ?></p>
                <?php endif; ?>
                <?php if ($wh['zip_code']): ?>
                <p class="mb-1 small"><i class="fas fa-envelope text-muted me-2 opacity-50"></i>CEP: <?= e($wh['zip_code']) ?></p>
                <?php endif; ?>
                <?php if ($wh['phone']): ?>
                <p class="mb-1 small"><i class="fas fa-phone text-muted me-2 opacity-50"></i><?= e($wh['phone']) ?></p>
                <?php endif; ?>
                <?php if ($wh['notes']): ?>
                <p class="mb-0 small text-muted fst-italic mt-2"><?= e($wh['notes']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between small text-muted">
                <span><i class="fas fa-box me-1"></i><?= $wh['total_items'] ?> itens</span>
                <span><i class="fas fa-cubes me-1"></i><?= number_format($wh['total_quantity'], 0) ?> unidades</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($warehouses)): ?>
    <div class="col-12 text-center py-5 text-muted">
        <i class="fas fa-building fa-3x mb-3 d-block text-secondary"></i>
        Nenhum armazém cadastrado ainda.
    </div>
    <?php endif; ?>
</div>

<!-- ══════ Modal: Criar / Editar Armazém ══════ -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="warehouseForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary py-2">
                    <h5 class="modal-title text-white" id="whModalTitle"><i class="fas fa-warehouse me-2"></i>Novo Armazém</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="wh_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Armazém <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="wh_name" required placeholder="Ex: Estoque Principal, Depósito 2...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Endereço</label>
                        <input type="text" class="form-control" name="address" id="wh_address" placeholder="Rua, número, complemento">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Cidade</label>
                            <input type="text" class="form-control" name="city" id="wh_city">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">UF</label>
                            <input type="text" class="form-control" name="state" id="wh_state" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">CEP</label>
                            <input type="text" class="form-control" name="zip_code" id="wh_zip" placeholder="00000-000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Telefone</label>
                        <input type="text" class="form-control" name="phone" id="wh_phone" placeholder="(11) 99999-0000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observações</label>
                        <textarea class="form-control" name="notes" id="wh_notes" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-2" id="wh_active_wrap" style="display:none;">
                        <input type="checkbox" class="form-check-input" name="is_active" id="wh_active" checked>
                        <label class="form-check-label" for="wh_active">Armazém ativo</label>
                    </div>
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" name="is_default" id="wh_default">
                        <label class="form-check-label" for="wh_default">
                            <i class="fas fa-star text-warning me-1"></i>Armazém padrão
                            <small class="text-muted d-block">O armazém padrão será usado automaticamente nas movimentações de estoque pelo pipeline.</small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewWarehouse() {
    document.getElementById('whModalTitle').innerHTML = '<i class="fas fa-warehouse me-2"></i>Novo Armazém';
    document.getElementById('warehouseForm').action = '?page=stock&action=storeWarehouse';
    document.getElementById('wh_id').value = '';
    document.getElementById('wh_name').value = '';
    document.getElementById('wh_address').value = '';
    document.getElementById('wh_city').value = '';
    document.getElementById('wh_state').value = '';
    document.getElementById('wh_zip').value = '';
    document.getElementById('wh_phone').value = '';
    document.getElementById('wh_notes').value = '';
    document.getElementById('wh_active_wrap').style.display = 'none';
    document.getElementById('wh_default').checked = false;
}

document.addEventListener('DOMContentLoaded', function() {
    // Status messages
    <?php if (isset($_GET['status'])): ?>
    const urlClean = new URL(window.location);
    urlClean.searchParams.delete('status');
    urlClean.searchParams.delete('error');
    window.history.replaceState({}, '', urlClean);
    <?php if ($_GET['status'] == 'created'): ?>
    Swal.fire({ icon:'success', title:'Armazém criado!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'updated'): ?>
    Swal.fire({ icon:'success', title:'Armazém atualizado!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'deleted'): ?>
    Swal.fire({ icon:'success', title:'Armazém removido!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'limit_warehouses'): ?>
    Swal.fire({ icon:'warning', title:'Limite atingido!', text:'Você atingiu o limite de armazéns do seu plano. Entre em contato com o suporte para fazer um upgrade.', confirmButtonColor:'#3498db' });
    <?php endif; ?>
    <?php endif; ?>

    // Edit warehouse
    document.querySelectorAll('.btn-edit-wh').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('whModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Armazém';
            document.getElementById('warehouseForm').action = '?page=stock&action=updateWarehouse';
            document.getElementById('wh_id').value = this.dataset.id;
            document.getElementById('wh_name').value = this.dataset.name;
            document.getElementById('wh_address').value = this.dataset.address;
            document.getElementById('wh_city').value = this.dataset.city;
            document.getElementById('wh_state').value = this.dataset.state;
            document.getElementById('wh_zip').value = this.dataset.zip;
            document.getElementById('wh_phone').value = this.dataset.phone;
            document.getElementById('wh_notes').value = this.dataset.notes;
            document.getElementById('wh_active').checked = this.dataset.active == '1';
            document.getElementById('wh_active_wrap').style.display = 'block';
            document.getElementById('wh_default').checked = this.dataset.default == '1';
            new bootstrap.Modal(document.getElementById('warehouseModal')).show();
        });
    });

    // Delete warehouse
    document.querySelectorAll('.btn-delete-wh').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            Swal.fire({
                title: 'Excluir armazém?',
                html: `Deseja remover <strong>${name}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `?page=stock&action=deleteWarehouse&id=${id}`;
                }
            });
        });
    });

    // Set as default warehouse
    document.querySelectorAll('.btn-set-default-wh').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            Swal.fire({
                title: 'Definir como padrão?',
                html: `Deseja definir <strong>${name}</strong> como o armazém padrão?<br><small class="text-muted">O estoque será movimentado automaticamente por este armazém no pipeline.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                confirmButtonText: '<i class="fas fa-star me-1"></i>Definir Padrão',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', id);
                    fetch('?page=stock&action=setDefault', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({ icon:'success', title:'Armazém padrão definido!', timer:1500, showConfirmButton:false })
                                    .then(() => location.reload());
                            }
                        });
                }
            });
        });
    });
});
</script>
