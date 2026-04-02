<?php
/**
 * Anexos — Listagem
 * FEAT-003
 * Variáveis: $attachments, $pagination
 */
$entityType = $_GET['entity_type'] ?? '';
$entityId   = $_GET['entity_id'] ?? '';
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-paperclip me-2 text-primary"></i>Anexos</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gestão de documentos e arquivos anexos.</p>
        </div>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i>Enviar Arquivo
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Arquivo</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Entidade</th>
                            <th>Enviado por</th>
                            <th>Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($attachments)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum anexo encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($attachments as $a): ?>
                        <tr>
                            <td><i class="fas fa-file me-1 text-muted"></i><?= e($a['original_name'] ?? $a['filename']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= e($a['mime_type'] ?? '-') ?></span></td>
                            <td><?= number_format(($a['size'] ?? 0) / 1024, 1) ?> KB</td>
                            <td><?= e(($a['entity_type'] ?? '-') . ' #' . ($a['entity_id'] ?? '')) ?></td>
                            <td><?= e($a['uploader_name'] ?? '-') ?></td>
                            <td style="font-size:.8rem;"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="?page=attachments&action=download&id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-primary" title="Download"><i class="fas fa-download"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDeleteFile" data-id="<?= (int) $a['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="?page=attachments&action=upload" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Enviar Arquivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Arquivo <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required>
                        <small class="text-muted">Máx. 10 MB</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">Entidade</label>
                            <select name="entity_type" class="form-select form-select-sm">
                                <option value="">Nenhuma</option>
                                <option value="order" <?= $entityType === 'order' ? 'selected' : '' ?>>Pedido</option>
                                <option value="customer" <?= $entityType === 'customer' ? 'selected' : '' ?>>Cliente</option>
                                <option value="product" <?= $entityType === 'product' ? 'selected' : '' ?>>Produto</option>
                                <option value="supplier" <?= $entityType === 'supplier' ? 'selected' : '' ?>>Fornecedor</option>
                                <option value="quote" <?= $entityType === 'quote' ? 'selected' : '' ?>>Orçamento</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">ID da Entidade</label>
                            <input type="number" name="entity_id" class="form-control form-control-sm" value="<?= eAttr($entityId) ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDeleteFile').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Excluir anexo?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não'
            }).then(r => { if (r.isConfirmed) window.location.href = '?page=attachments&action=delete&id=' + id; });
        });
    });
});
</script>
