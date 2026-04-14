<?php
/**
 * Workflows — Formulário de regra
 * FEAT-010 (melhorado: Select2 campos, inputs específicos, tags)
 * Variáveis: $rule (null = nova), $availableEvents, $eventFields
 */
$isEdit = !empty($rule);
$r = $rule ?? [];
$conditions = !empty($r['conditions']) ? (is_string($r['conditions']) ? json_decode($r['conditions'], true) : $r['conditions']) : [];
$actions = !empty($r['actions']) ? (is_string($r['actions']) ? json_decode($r['actions'], true) : $r['actions']) : [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-cogs me-2 text-primary"></i><?= $isEdit ? 'Editar Regra' : 'Nova Regra de Workflow' ?></h1>
        </div>
        <a href="?page=workflows" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=workflows&action=<?= $isEdit ? 'update' : 'store' ?>" id="workflowForm">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($r['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Evento Gatilho <span class="text-danger">*</span></label>
                        <select name="event" id="eventSelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($availableEvents ?? [] as $key => $label): ?>
                            <option value="<?= eAttr($key) ?>" <?= ($r['event'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($r['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <input type="hidden" name="priority" value="<?= (int) ($r['priority'] ?? 0) ?>">
                <input type="hidden" name="conditions" id="conditionsInput" value="<?= eAttr(json_encode($conditions)) ?>">
                <input type="hidden" name="actions" id="actionsInput" value="<?= eAttr(json_encode($actions)) ?>">

                <div id="eventFieldsInfo" class="mt-3"></div>

                <h6 class="mt-4 mb-2"><i class="fas fa-filter me-2"></i>Condições</h6>
                <div id="conditionsList" class="mb-3"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addCondition"><i class="fas fa-plus me-1"></i>Adicionar Condição</button>

                <h6 class="mt-3 mb-2"><i class="fas fa-bolt me-2"></i>Ações</h6>
                <div id="actionsList" class="mb-3"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addAction"><i class="fas fa-plus me-1"></i>Adicionar Ação</button>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="?page=workflows" class="btn btn-outline-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" id="page-config"><?= json_encode([
    'eventFields' => $eventFields ?? [],
    'conditions'  => $conditions,
    'actions'     => $actions,
]) ?></script>
<script src="<?= asset('assets/js/modules/workflows-form.js') ?>"></script>
