<?php
/**
 * Equipamentos — Agendamentos de manutenção
 * Variáveis: $equipment, $schedules
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-calendar-check me-2 text-primary"></i>Agendamentos — <?= e($equipment['name']) ?></h1>
        </div>
        <a href="?page=equipment&action=edit&id=<?= (int) $equipment['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Novo Agendamento</strong></div>
        <div class="card-body">
            <form method="post" action="?page=equipment&action=storeSchedule">
                <?= csrf_field() ?>
                <input type="hidden" name="equipment_id" value="<?= (int) $equipment['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select name="maintenance_type" class="form-select">
                            <option value="preventive">Preventiva</option>
                            <option value="corrective">Corretiva</option>
                            <option value="predictive">Preditiva</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Frequência (dias)</label>
                        <input type="number" name="frequency_days" class="form-control" value="30" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Próxima data</label>
                        <input type="date" name="next_due_date" class="form-control" required>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Tipo</th><th>Descrição</th><th>Frequência</th><th>Próxima</th><th>Última</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum agendamento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><span class="badge bg-info"><?= e($s['maintenance_type']) ?></span></td>
                            <td><?= e($s['description']) ?></td>
                            <td><?= (int) $s['frequency_days'] ?> dias</td>
                            <td><?= e(date('d/m/Y', strtotime($s['next_due_date']))) ?></td>
                            <td><?= !empty($s['last_performed_at']) ? e(date('d/m/Y', strtotime($s['last_performed_at']))) : '-' ?></td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
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
