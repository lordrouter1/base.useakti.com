<?php
/**
 * Portal do Cliente — Documentos
 *
 * Variáveis: $documents, $company
 */
?>

<div class="portal-page">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-file-alt me-2"></i>
            <?= __p('documents_title') ?>
        </h1>
    </div>

    <!-- ═══ Lista de Documentos ═══ -->
    <?php if (empty($documents)): ?>
        <div class="portal-empty-state">
            <i class="fas fa-file-alt"></i>
            <p><?= __p('documents_empty') ?></p>
        </div>
    <?php else: ?>
        <div class="portal-documents-list">
            <?php foreach ($documents as $doc): ?>
                <?php
                    $statusClass = 'secondary';
                    if (($doc['status'] ?? '') === 'autorizada') $statusClass = 'success';
                    elseif (($doc['status'] ?? '') === 'cancelada') $statusClass = 'danger';
                    elseif (($doc['status'] ?? '') === 'enviada') $statusClass = 'info';
                ?>
                <div class="portal-document-card">
                    <div class="portal-document-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="portal-document-info">
                        <div class="portal-document-title">
                            <?= __p('documents_nfe') ?> <?= e($doc['number'] ?? '—') ?>
                            <?php if (!empty($doc['series'])): ?>
                                <small class="text-muted">/ Série <?= e($doc['series']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="portal-document-meta">
                            <span>
                                <i class="fas fa-box me-1"></i>
                                <?= __p('order_detail_title', ['id' => (int) $doc['order_id']]) ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar me-1"></i>
                                <?= portal_date($doc['created_at']) ?>
                            </span>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= e(ucfirst($doc['status'] ?? 'Pendente')) ?>
                            </span>
                        </div>
                    </div>
                    <div class="portal-document-actions">
                        <?php if (!empty($doc['pdf_path'])): ?>
                            <a href="?page=portal&action=downloadDocument&id=<?= (int) $doc['id'] ?>&type=pdf"
                               class="btn btn-sm btn-primary" title="Download PDF">
                                <i class="fas fa-file-pdf"></i>
                                <span class="d-none d-sm-inline ms-1">PDF</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($doc['xml_path'])): ?>
                            <a href="?page=portal&action=downloadDocument&id=<?= (int) $doc['id'] ?>&type=xml"
                               class="btn btn-sm btn-outline-secondary" title="Download XML">
                                <i class="fas fa-file-code"></i>
                                <span class="d-none d-sm-inline ms-1">XML</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
