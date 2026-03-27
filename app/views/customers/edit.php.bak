<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-user-edit me-2"></i>Editar Cliente</h1>
        <a href="?page=customers" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>
    
    <form id="customerForm" method="post" action="?page=customers&action=update" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">
        <div class="row">
            <div class="col-md-3">
                <!-- Foto do Cliente -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm h-100">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold text-center"><i class="fas fa-camera"></i> Foto</legend>
                    <div class="text-center">
                        <div class="mb-3 position-relative d-inline-block">
                             <img id="preview-photo" src="<?= !empty($customer['photo']) ? eAttr($customer['photo']) : 'assets/img/default-avatar.png' ?>" class="rounded-circle border border-3 border-light shadow-sm" style="width: 150px; height: 150px; object-fit: cover; background-color: #f8f9fa;">
                             <label for="photo" class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 shadow-sm" style="cursor: pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-camera text-white"></i>
                             </label>
                             <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
                        </div>
                        <small class="text-muted d-block mt-2">Clique no ícone para alterar</small>
                    </div>
                </fieldset>
            </div>
            
            <div class="col-md-9">
                <!-- Informações Básicas -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold"><i class="fas fa-user me-2"></i>Dados Principais</legend>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-bold">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?= eAttr($customer['name']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="document" class="form-label fw-bold">CPF / CNPJ</label>
                            <input type="text" class="form-control" id="document" name="document" value="<?= eAttr($customer['document'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= eAttr($customer['email']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-bold">Telefone / WhatsApp</label>
                             <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= eAttr($customer['phone']) ?>">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- Endereço -->
                <?php $addr = $customer['address_data'] ?? []; ?>
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Endereço</legend>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="zipcode" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="zipcode" name="zipcode" value="<?= eAttr($addr['zipcode'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="address_type" class="form-label">Tipo Logradouro</label>
                            <select class="form-select" id="address_type" name="address_type">
                                <option value="">Selecione...</option>
                                <?php foreach(['Rua','Avenida','Travessa','Praça','Alameda','Rodovia','Outro'] as $t): ?>
                                    <option value="<?= eAttr($t) ?>" <?= ($addr['address_type'] ?? '') == $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="address_name" class="form-label">Nome do Logradouro</label>
                            <input type="text" class="form-control" id="address_name" name="address_name" value="<?= eAttr($addr['address_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="address_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="address_number" name="address_number" value="<?= eAttr($addr['address_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="neighborhood" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="neighborhood" name="neighborhood" value="<?= eAttr($addr['neighborhood'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="complement" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complement" name="complement" value="<?= eAttr($addr['complement'] ?? '') ?>">
                        </div>
                    </div>
                </fieldset>

                <!-- Tabela de Preço -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold"><i class="fas fa-tags me-2"></i>Tabela de Preço</legend>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="price_table_id" class="form-label fw-bold">Tabela de Preço do Cliente</label>
                            <select class="form-select" id="price_table_id" name="price_table_id">
                                <option value="">Usar tabela padrão</option>
                                <?php if (!empty($priceTables)): ?>
                                <?php foreach ($priceTables as $pt): ?>
                                <option value="<?= (int)$pt['id'] ?>" <?= ($customer['price_table_id'] ?? '') == $pt['id'] ? 'selected' : '' ?>><?= e($pt['name']) ?> <?= $pt['is_default'] ? '(Padrão)' : '' ?></option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Define qual tabela de preços será usada para este cliente nos orçamentos.</small>
                        </div>
                    </div>
                </fieldset>

                <div class="col-12 mt-4 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="?page=customers" class="btn btn-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const reader = new FileReader();
    reader.onload = function(event) {
        document.getElementById('preview-photo').src = event.target.result;
    };
    if(e.target.files[0]) reader.readAsDataURL(e.target.files[0]);
});
</script>
