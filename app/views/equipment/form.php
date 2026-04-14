<?php
/**
 * Equipamentos — Formulário
 * Variáveis: $equipment (null para novo), $schedules (opcional)
 */
$isEdit = !empty($equipment);
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-tools me-2 text-primary"></i><?= $isEdit ? 'Editar Equipamento' : 'Novo Equipamento' ?></h1>
        </div>
        <a href="?page=equipment" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=equipment&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $equipment['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($equipment['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="code" class="form-control" value="<?= eAttr($equipment['code'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nº Série</label>
                        <input type="text" name="serial_number" class="form-control" value="<?= eAttr($equipment['serial_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fabricante</label>
                        <input type="text" name="manufacturer" class="form-control" value="<?= eAttr($equipment['manufacturer'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="model" class="form-control" value="<?= eAttr($equipment['model'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Localização</label>
                        <input type="text" name="location" class="form-control" value="<?= eAttr($equipment['location'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Compra</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= eAttr($equipment['purchase_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Garantia até</label>
                        <input type="date" name="warranty_until" class="form-control" value="<?= eAttr($equipment['warranty_until'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active' => 'Ativo', 'maintenance' => 'Em Manutenção', 'inactive' => 'Inativo', 'decommissioned' => 'Descomissionado'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($equipment['status'] ?? 'active') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="3"><?= e($equipment['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit && !empty($schedules)): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><strong>Manutenções Agendadas</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Tipo</th><th>Descrição</th><th>Frequência</th><th>Próxima</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><span class="badge bg-info"><?= e($s['maintenance_type']) ?></span></td>
                            <td><?= e($s['description']) ?></td>
                            <td><?= (int) $s['frequency_days'] ?> dias</td>
                            <td><?= e(date('d/m/Y', strtotime($s['next_due_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isEdit): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><strong>Registrar Manutenção</strong></div>
        <div class="card-body">
            <form method="post" action="?page=equipment&action=storeLog">
                <?= csrf_field() ?>
                <input type="hidden" name="equipment_id" value="<?= (int) $equipment['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Data</label>
                        <input type="datetime-local" name="performed_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Custo (R$)</label>
                        <input type="number" name="cost" class="form-control" step="0.01" value="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Próxima Manutenção</label>
                        <input type="date" name="next_due_date" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-wrench me-1"></i>Registrar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
