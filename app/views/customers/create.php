<div class="container py-4">
    <h2 class="mb-4 text-primary"><i class="fas fa-user-plus me-2"></i>Novo Cliente</h2>
    
    <form id="customerForm" method="post" action="?page=customers&action=store" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-md-3">
                <!-- Foto do Cliente -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm h-100">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold text-center"><i class="fas fa-camera"></i> Foto</legend>
                    <div class="text-center">
                        <div class="mb-3 position-relative d-inline-block">
                             <img id="preview-photo" src="assets/img/default-avatar.png" class="rounded-circle border border-3 border-light shadow-sm" style="width: 150px; height: 150px; object-fit: cover; background-color: #f8f9fa;">
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
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Nome do cliente ou empresa">
                        </div>
                        <div class="col-md-4">
                            <label for="document" class="form-label fw-bold">CPF / CNPJ</label>
                            <input type="text" class="form-control" id="document" name="document" placeholder="000.000.000-00">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="email@exemplo.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-bold">Telefone / WhatsApp</label>
                             <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- Endereço -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Endereço</legend>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="zipcode" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="zipcode" name="zipcode">
                        </div>
                        <div class="col-md-3">
                            <label for="address_type" class="form-label">Tipo Logradouro</label>
                            <select class="form-select" id="address_type" name="address_type">
                                <option value="">Selecione...</option>
                                <option value="Rua">Rua</option>
                                <option value="Avenida">Avenida</option>
                                <option value="Travessa">Travessa</option>
                                <option value="Praça">Praça</option>
                                <option value="Alameda">Alameda</option>
                                <option value="Rodovia">Rodovia</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="address_name" class="form-label">Nome do Logradouro</label>
                            <input type="text" class="form-control" id="address_name" name="address_name">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="address_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="address_number" name="address_number">
                        </div>
                        <div class="col-md-5">
                            <label for="neighborhood" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="neighborhood" name="neighborhood">
                        </div>
                        <div class="col-md-5">
                            <label for="complement" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complement" name="complement">
                        </div>
                    </div>
                </fieldset>
                <fieldset class="border rounded p-3 mb-2">
                    <legend class="float-none w-auto px-2 fs-6">Observações</legend>
                    <div class="mb-3">
                        <textarea class="form-control" id="observations" name="observations" rows="3" style="resize: none; height: 80px; background: #f8f9fa;"></textarea>
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
                                <option value="<?= $pt['id'] ?>"><?= htmlspecialchars($pt['name']) ?> <?= $pt['is_default'] ? '(Padrão)' : '' ?></option>
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
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Salvar Cliente</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
