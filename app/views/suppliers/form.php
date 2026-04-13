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

    <?php if ($isEdit): ?>
    <!-- Insumos Fornecidos (read-only) -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="fas fa-cubes me-2"></i>Insumos Fornecidos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="supplierSuppliesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th>SKU Fornecedor</th>
                            <th class="text-end">Preço</th>
                            <th class="text-center">Preferencial</th>
                        </tr>
                    </thead>
                    <tbody id="supplierSuppliesBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="fas fa-spinner fa-spin me-1"></i>Carregando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    $(function() {
        const supplierId = <?= (int) $s['id'] ?>;
        $.getJSON('?page=supplies&action=getSuppliers&supplier_id=' + supplierId, function(resp) {
            const items = resp.items || resp.data || resp || [];
            const tbody = $('#supplierSuppliesBody');
            tbody.empty();

            if (!items.length) {
                tbody.html('<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-info-circle me-1"></i>Nenhum insumo vinculado a este fornecedor.</td></tr>');
                return;
            }

            items.forEach(function(item) {
                tbody.append(`
                    <tr>
                        <td><code>${item.supply_code || item.code || ''}</code></td>
                        <td>${item.supply_name || item.name || ''}</td>
                        <td>${item.supplier_sku || ''}</td>
                        <td class="text-end">R$ ${parseFloat(item.supplier_price || 0).toFixed(2).replace('.', ',')}</td>
                        <td class="text-center">${item.is_preferred == 1 ? '<i class="fas fa-star text-warning"></i>' : ''}</td>
                    </tr>
                `);
            });
        }).fail(function() {
            $('#supplierSuppliesBody').html('<tr><td colspan="5" class="text-center text-muted">Erro ao carregar insumos.</td></tr>');
        });
    });
    </script>
    <?php endif; ?>

</div>
                </div>
            </form>
        </div>
    </div>
</div>
