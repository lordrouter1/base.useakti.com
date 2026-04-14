<?php
/**
 * Filiais — Formulário
 * Variáveis: $branch (null para novo)
 */
$isEdit = !empty($branch);
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-building me-2 text-primary"></i><?= $isEdit ? 'Editar Filial' : 'Nova Filial' ?></h1></div>
        <a href="?page=branches" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=branches&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $branch['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($branch['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="code" class="form-control" value="<?= eAttr($branch['code'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="document" class="form-control" value="<?= eAttr($branch['document'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="phone" class="form-control" value="<?= eAttr($branch['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" value="<?= eAttr($branch['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">CEP</label>
                        <input type="text" name="zip_code" class="form-control" value="<?= eAttr($branch['zip_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="address" class="form-control" value="<?= eAttr($branch['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="city" class="form-control" value="<?= eAttr($branch['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">UF</label>
                        <input type="text" name="state" class="form-control" maxlength="2" value="<?= eAttr($branch['state'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_headquarters" value="1" class="form-check-input" id="chkHQ" <?= !empty($branch['is_headquarters']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkHQ">Matriz</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="chkActive" <?= ($branch['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActive">Ativa</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
