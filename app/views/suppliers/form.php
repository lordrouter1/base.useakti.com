<?php
/**
 * Fornecedores — Formulário (criar/editar)
 * FEAT-005
 * Variáveis: $supplier (null = novo)
 */
$isEdit = !empty($supplier);
$s = $supplier ?? [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-truck me-2 text-primary"></i>
                <?= $isEdit ? 'Editar Fornecedor' : 'Novo Fornecedor' ?>
            </h1>
        </div>
        <a href="?page=suppliers" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=suppliers&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Razão Social <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?= eAttr($s['company_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">CNPJ/CPF</label>
                        <input type="text" name="document" class="form-control" value="<?= eAttr($s['document'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($s['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inactive" <?= ($s['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">E-mail</label>
                        <input type="email" name="email" class="form-control" value="<?= eAttr($s['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Telefone</label>
                        <input type="text" name="phone" class="form-control" value="<?= eAttr($s['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Contato</label>
                        <input type="text" name="contact_name" class="form-control" value="<?= eAttr($s['contact_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Endereço</label>
                        <input type="text" name="address" class="form-control" value="<?= eAttr($s['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cidade</label>
                        <input type="text" name="city" class="form-control" value="<?= eAttr($s['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">UF</label>
                        <input type="text" name="state" class="form-control" maxlength="2" value="<?= eAttr($s['state'] ?? '') ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-bold">CEP</label>
                        <input type="text" name="zip_code" class="form-control" value="<?= eAttr($s['zip_code'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Observações</label>
                        <textarea name="notes" class="form-control" rows="3"><?= e($s['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="?page=suppliers" class="btn btn-outline-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
