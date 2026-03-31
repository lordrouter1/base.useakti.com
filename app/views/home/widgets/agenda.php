<?php
/**
 * Widget: Próximos Contatos (Agenda)
 * Variáveis esperadas: $proximosContatos
 */
?>
<div class="col-md-6" id="home-agenda">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-purple"><i class="fas fa-calendar-check me-2"></i>Próximos Contatos</h6>
            <a href="?page=agenda" class="btn btn-sm btn-outline-secondary">Ver Agenda</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($proximosContatos)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-calendar-check d-block mb-2" style="font-size:1.5rem;opacity:0.4;"></i>
                <small>Nenhum contato agendado</small>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($proximosContatos as $contato):
                    $isToday = (($contato['scheduled_date'] ?? '') == date('Y-m-d'));
                ?>
                <a href="?page=pipeline&action=detail&id=<?= $contato['id'] ?>" class="list-group-item list-group-item-action py-2 px-3 <?= $isToday ? 'list-group-item-warning' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold small">#<?= str_pad($contato['id'], 4, '0', STR_PAD_LEFT) ?></span>
                            <span class="ms-1 small"><?= e($contato['customer_name'] ?? 'Cliente') ?></span>
                        </div>
                        <?php if ($isToday): ?>
                        <span class="badge bg-warning text-dark" style="font-size:0.65rem;">HOJE</span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:0.7rem;"><?= date('d/m', strtotime($contato['scheduled_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
