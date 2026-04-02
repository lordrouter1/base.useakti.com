<?php
/**
 * Qualidade — Inspeção
 * FEAT-017
 * Variáveis: $inspections, $checklists, orderId via GET
 */
$orderId = (int) ($_GET['order_id'] ?? 0);
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-search me-2 text-primary"></i>Inspeção de Qualidade</h1>
            <?php if ($orderId): ?>
            <p class="text-muted mb-0">Pedido #<?= $orderId ?></p>
            <?php endif; ?>
        </div>
        <a href="?page=quality" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <!-- Nova Inspeção -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0">Registrar Inspeção</h6></div>
        <div class="card-body">
            <form method="post" action="?page=quality&action=storeInspection">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Checklist</label>
                        <select name="checklist_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($checklists ?? [] as $cl): ?>
                            <option value="<?= (int) $cl['id'] ?>"><?= e($cl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="passed">Aprovado</option>
                            <option value="failed">Reprovado</option>
                            <option value="pending">Pendente</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Observações</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Histórico -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><h6 class="mb-0">Histórico de Inspeções</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Checklist</th><th>Inspetor</th><th>Status</th><th>Observações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($inspections)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma inspeção registrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inspections as $insp): ?>
                        <tr>
                            <td style="font-size:.8rem;"><?= date('d/m/Y H:i', strtotime($insp['created_at'])) ?></td>
                            <td><?= e($insp['checklist_name'] ?? '-') ?></td>
                            <td><?= e($insp['inspector_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                $sb = ['passed' => 'bg-success', 'failed' => 'bg-danger', 'pending' => 'bg-warning text-dark'];
                                $sl = ['passed' => 'Aprovado', 'failed' => 'Reprovado', 'pending' => 'Pendente'];
                                $is = $insp['status'] ?? 'pending';
                                ?>
                                <span class="badge <?= $sb[$is] ?? 'bg-secondary' ?>"><?= $sl[$is] ?? $is ?></span>
                            </td>
                            <td style="font-size:.85rem;"><?= e($insp['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
