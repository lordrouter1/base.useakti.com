<div class="container py-4">
    <?php $refPage = $_GET['ref'] ?? 'settings'; ?>
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-tags me-2"></i>Tabela: <?= e($table['name']) ?>
            <?php if ($table['is_default']): ?>
                <span class="badge bg-success ms-2 fs-6"><i class="fas fa-star me-1"></i>Padrão</span>
            <?php endif; ?>
        </h1>
        <a href="?page=<?= $refPage === 'price_tables' ? 'price_tables' : 'settings&tab=prices' ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (!empty($table['description'])): ?>
    <p class="text-muted mb-4"><?= e($table['description']) ?></p>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Itens da tabela -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-list me-2"></i>Produtos e Preços</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($items)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Produto</th>
                                    <th class="text-end">Preço Original</th>
                                    <th class="text-end">Preço na Tabela</th>
                                    <th class="text-center">Diferença</th>
                                    <th class="text-center" style="width:80px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <?php
                                    $diff = $item['price'] - $item['product_default_price'];
                                    $diffPercent = $item['product_default_price'] > 0 
                                        ? round(($diff / $item['product_default_price']) * 100, 1) 
                                        : 0;
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= e($item['product_name']) ?></td>
                                    <td class="text-end text-muted">R$ <?= number_format($item['product_default_price'], 2, ',', '.') ?></td>
                                    <td class="text-end fw-bold text-primary">R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                    <td class="text-center">
                                        <?php if ($diff > 0): ?>
                                            <span class="badge bg-danger">+<?= $diffPercent ?>%</span>
                                        <?php elseif ($diff < 0): ?>
                                            <span class="badge bg-success"><?= $diffPercent ?>%</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="?page=<?= $refPage === 'price_tables' ? 'price_tables' : 'settings' ?>&action=deletePriceItem&item_id=<?= $item['id'] ?>&table_id=<?= $table['id'] ?>&ref=<?= $refPage ?>"
                                           class="btn btn-sm btn-outline-danger btn-delete-item" title="Remover">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-box-open d-block mb-2" style="font-size:2rem;"></i>
                        Nenhum produto adicionado a esta tabela ainda.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Adicionar produto -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary bg-opacity-10 py-2">
                    <h6 class="mb-0 text-primary"><i class="fas fa-plus-circle me-2"></i>Adicionar/Atualizar Produto</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="?page=<?= $refPage === 'price_tables' ? 'price_tables' : 'settings' ?>&action=savePriceItem">
                        <?= csrf_field() ?>
                        <input type="hidden" name="price_table_id" value="<?= $table['id'] ?>">
                        <input type="hidden" name="ref_page" value="<?= $refPage ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Produto</label>
                            <select class="form-select" name="product_id" id="productSelectPrice" required>
                                <option value="">Selecione um produto...</option>
                                <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>" data-price="<?= $prod['price'] ?>" 
                                    <?= isset($existingProducts[$prod['id']]) ? 'class="text-primary"' : '' ?>>
                                    <?= e($prod['name']) ?> — R$ <?= number_format($prod['price'], 2, ',', '.') ?>
                                    <?= isset($existingProducts[$prod['id']]) ? ' ✓' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Preço Original</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="originalPrice" disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Preço Nesta Tabela</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control" name="price" id="tablePrice" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Salvar Preço
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info rápida -->
            <div class="card border-0 shadow-sm">
                <div class="card-body small text-muted">
                    <p class="mb-1"><strong>Total de produtos na tabela:</strong> <?= count($items) ?></p>
                    <p class="mb-0">Produtos não incluídos na tabela usarão o preço padrão do cadastro.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status']) && in_array($_GET['status'], ['item_saved','item_deleted'])): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', timer: 1500, showConfirmButton: false });
    <?php endif; ?>

    // Auto-preencher preço original
    const sel = document.getElementById('productSelectPrice');
    const orig = document.getElementById('originalPrice');
    const tbl = document.getElementById('tablePrice');
    if (sel) {
        sel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.price) {
                orig.value = parseFloat(opt.dataset.price).toFixed(2);
                tbl.value = parseFloat(opt.dataset.price).toFixed(2);
            } else {
                orig.value = '';
                tbl.value = '';
            }
        });
    }

    // Confirmar exclusão
    document.querySelectorAll('.btn-delete-item').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Remover produto da tabela?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => { if (r.isConfirmed) window.location.href = href; });
        });
    });
});
</script>
