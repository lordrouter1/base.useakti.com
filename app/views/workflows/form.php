<?php
/**
 * Workflows — Formulário de regra
 * FEAT-010
 * Variáveis: $rule (null = nova), $availableEvents
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
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($r['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Evento Gatilho <span class="text-danger">*</span></label>
                        <select name="event" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($availableEvents ?? [] as $key => $label): ?>
                            <option value="<?= eAttr($key) ?>" <?= ($r['event'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Prioridade</label>
                        <input type="number" name="priority" class="form-control" min="0" value="<?= (int) ($r['priority'] ?? 0) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($r['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <input type="hidden" name="conditions" id="conditionsInput" value="<?= eAttr(json_encode($conditions)) ?>">
                <input type="hidden" name="actions" id="actionsInput" value="<?= eAttr(json_encode($actions)) ?>">

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let conditions = <?= json_encode($conditions) ?>;
    let actions = <?= json_encode($actions) ?>;
    const condList = document.getElementById('conditionsList');
    const actList = document.getElementById('actionsList');

    function renderConditions() {
        condList.innerHTML = '';
        conditions.forEach((c, i) => {
            condList.innerHTML += `<div class="row g-2 mb-2 align-items-center">
                <div class="col-md-3"><input class="form-control form-control-sm" placeholder="Campo" value="${c.field||''}" onchange="window.__conds[${i}].field=this.value"></div>
                <div class="col-md-2"><select class="form-select form-select-sm" onchange="window.__conds[${i}].operator=this.value">
                    <option value="==" ${c.operator=='==' ?'selected':''}>Igual</option>
                    <option value="!=" ${c.operator=='!=' ?'selected':''}>Diferente</option>
                    <option value=">" ${c.operator=='>' ?'selected':''}>Maior</option>
                    <option value="<" ${c.operator=='<' ?'selected':''}>Menor</option>
                    <option value="contains" ${c.operator=='contains' ?'selected':''}>Contém</option>
                </select></div>
                <div class="col-md-3"><input class="form-control form-control-sm" placeholder="Valor" value="${c.value||''}" onchange="window.__conds[${i}].value=this.value"></div>
                <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger" onclick="window.__conds.splice(${i},1);renderConditions()"><i class="fas fa-times"></i></button></div>
            </div>`;
        });
        window.__conds = conditions;
    }

    function renderActions() {
        actList.innerHTML = '';
        actions.forEach((a, i) => {
            actList.innerHTML += `<div class="row g-2 mb-2 align-items-center">
                <div class="col-md-3"><select class="form-select form-select-sm" onchange="window.__acts[${i}].type=this.value">
                    <option value="notify" ${a.type=='notify'?'selected':''}>Notificar</option>
                    <option value="email" ${a.type=='email'?'selected':''}>E-mail</option>
                    <option value="log" ${a.type=='log'?'selected':''}>Log</option>
                    <option value="update_field" ${a.type=='update_field'?'selected':''}>Atualizar campo</option>
                </select></div>
                <div class="col-md-5"><input class="form-control form-control-sm" placeholder="Mensagem/Destino" value="${a.message||a.to||''}" onchange="window.__acts[${i}].message=this.value"></div>
                <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger" onclick="window.__acts.splice(${i},1);renderActions()"><i class="fas fa-times"></i></button></div>
            </div>`;
        });
        window.__acts = actions;
    }

    document.getElementById('addCondition').addEventListener('click', () => { conditions.push({field:'',operator:'==',value:''}); renderConditions(); });
    document.getElementById('addAction').addEventListener('click', () => { actions.push({type:'notify',message:''}); renderActions(); });

    renderConditions();
    renderActions();

    document.getElementById('workflowForm').addEventListener('submit', function() {
        document.getElementById('conditionsInput').value = JSON.stringify(window.__conds || []);
        document.getElementById('actionsInput').value = JSON.stringify(window.__acts || []);
    });
});
</script>
