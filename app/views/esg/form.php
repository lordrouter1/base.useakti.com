<?php
/**
 * ESG — Formulário de Métrica com registros e metas
 * Variáveis: $metric (null para novo), $records (se edição), $targets (se edição)
 */
$isEdit = !empty($metric);
$catLabels = ['environmental' => 'Ambiental', 'social' => 'Social', 'governance' => 'Governança'];
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-leaf me-2 text-success"></i><?= $isEdit ? 'Editar Métrica ESG' : 'Nova Métrica ESG' ?></h1></div>
        <a href="?page=esg" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=esg&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $metric['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($metric['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Categoria</label>
                        <select name="category" class="form-select">
                            <?php foreach ($catLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($metric['category'] ?? 'environmental') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unidade</label>
                        <input type="text" name="unit" class="form-control" value="<?= eAttr($metric['unit'] ?? '') ?>" placeholder="kWh, kg, m³">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="chkActive" <?= ($metric['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActive">Ativa</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($metric['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit): ?>
    <!-- Adicionar registro -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><strong>Novo Registro</strong></div>
        <div class="card-body">
            <form method="post" action="?page=esg&action=addRecord">
                <?= csrf_field() ?>
                <input type="hidden" name="metric_id" value="<?= (int) $metric['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="recorded_at" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor (<?= e($metric['unit'] ?? '') ?>)</label>
                        <input type="number" name="value" class="form-control" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Observações</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Adicionar Registro</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Registros existentes -->
    <?php if (!empty($records)): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><strong>Registros</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Valor</th><th>Observações</th><th>Registrado por</th></tr></thead>
                    <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($r['recorded_at']))) ?></td>
                            <td><strong><?= e(number_format((float)$r['value'], 2, ',', '.')) ?></strong> <?= e($metric['unit'] ?? '') ?></td>
                            <td><?= e($r['notes'] ?? '-') ?></td>
                            <td><?= e($r['recorded_by_name'] ?? '#' . ($r['recorded_by'] ?? '?')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meta -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><strong>Definir Meta</strong></div>
        <div class="card-body">
            <form method="post" action="?page=esg&action=setTarget">
                <?= csrf_field() ?>
                <input type="hidden" name="metric_id" value="<?= (int) $metric['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Ano</label>
                        <input type="number" name="year" class="form-control" value="<?= date('Y') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor Meta (<?= e($metric['unit'] ?? '') ?>)</label>
                        <input type="number" name="target_value" class="form-control" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-bullseye me-1"></i>Definir Meta</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
