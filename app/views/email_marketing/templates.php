<?php
/**
 * E-mail Marketing — Templates
 * FEAT-013
 * Variáveis: $templates
 */
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-alt me-2 text-primary"></i>Templates de E-mail</h1>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=email_marketing" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Campanhas</a>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTemplateModal"><i class="fas fa-plus me-1"></i>Novo Template</button>
        </div>
    </div>

    <div class="row g-3">
        <?php if (empty($templates)): ?>
        <div class="col-12"><div class="alert alert-info">Nenhum template cadastrado.</div></div>
        <?php else: ?>
            <?php foreach ($templates as $t): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6><?= e($t['name']) ?></h6>
                        <p class="text-muted small mb-1">Assunto: <?= e($t['subject'] ?? '-') ?></p>
                        <small class="text-muted">Criado em <?= date('d/m/Y', strtotime($t['created_at'])) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Template -->
<div class="modal fade" id="newTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="?page=email_marketing&action=storeTemplate">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Novo Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assunto</label>
                        <input type="text" name="subject" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conteúdo HTML</label>
                        <textarea name="body_html" class="form-control" rows="8"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Variáveis (JSON)</label>
                        <input type="text" name="variables" class="form-control" placeholder='["nome","email"]'>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
