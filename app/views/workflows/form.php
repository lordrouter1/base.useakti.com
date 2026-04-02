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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventFields = <?= json_encode($eventFields ?? []) ?>;
    let conditions = <?= json_encode($conditions) ?>;
    let actions = <?= json_encode($actions) ?>;
    const condList = document.getElementById('conditionsList');
    const actList = document.getElementById('actionsList');
    const eventSelect = document.getElementById('eventSelect');

    // ─── Event Field Info ───
    function updateFieldInfo(event) {
        const fields = eventFields[event] || {};
        const infoBox = document.getElementById('eventFieldsInfo');
        if (!event) { infoBox.innerHTML = ''; return; }
        if (Object.keys(fields).length === 0) {
            infoBox.innerHTML = '<div class="alert alert-warning py-2 mb-0"><i class="fas fa-info-circle me-1"></i>Este evento não possui campos mapeados.</div>';
        } else {
            let badges = Object.entries(fields).map(([key, meta]) =>
                `<span class="badge bg-light text-dark border me-1 mb-1"><code>${key}</code> <small class="text-muted">${meta.label}</small></span>`
            ).join('');
            infoBox.innerHTML = `<div class="alert alert-info py-2 mb-0"><i class="fas fa-lightbulb me-1"></i><strong>Campos disponíveis neste evento:</strong><br>${badges}</div>`;
        }
    }

    eventSelect.addEventListener('change', function() {
        updateFieldInfo(this.value);
        renderConditions();
        renderActions();
    });
    if (eventSelect.value) updateFieldInfo(eventSelect.value);

    // ─── Conditions ───
    function renderConditions() {
        condList.innerHTML = '';
        const currentEvent = eventSelect.value;
        const fields = eventFields[currentEvent] || {};
        const fieldOptions = Object.entries(fields).map(([key, meta]) =>
            `<option value="${key}">${meta.label} (${key})</option>`
        ).join('');

        conditions.forEach((c, i) => {
            condList.innerHTML += `<div class="row g-2 mb-2 align-items-center">
                <div class="col-md-3">
                    <select class="form-select form-select-sm condFieldSelect" data-idx="${i}" style="width:100%">
                        <option value="">Selecione o campo...</option>
                        ${fieldOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm condOpSelect" data-idx="${i}">
                        <option value="==" ${c.operator=='=='?'selected':''}>Igual</option>
                        <option value="!=" ${c.operator=='!='?'selected':''}>Diferente</option>
                        <option value=">" ${c.operator=='>'?'selected':''}>Maior</option>
                        <option value="<" ${c.operator=='<'?'selected':''}>Menor</option>
                        <option value="contains" ${c.operator=='contains'?'selected':''}>Contém</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input class="form-control form-control-sm condValueInput" data-idx="${i}" placeholder="Valor" value="${c.value||''}">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-danger btnRemoveCond" data-idx="${i}"><i class="fas fa-times"></i></button>
                </div>
            </div>`;
        });

        // Set values & init Select2
        document.querySelectorAll('.condFieldSelect').forEach(sel => {
            const idx = parseInt(sel.dataset.idx);
            sel.value = conditions[idx]?.field || '';
            $(sel).select2({ placeholder: 'Selecione o campo...', allowClear: true, width: '100%' })
                .on('change', function() { conditions[idx].field = this.value; });
        });
        document.querySelectorAll('.condOpSelect').forEach(sel => {
            const idx = parseInt(sel.dataset.idx);
            sel.addEventListener('change', function() { conditions[idx].operator = this.value; });
        });
        document.querySelectorAll('.condValueInput').forEach(inp => {
            const idx = parseInt(inp.dataset.idx);
            inp.addEventListener('input', function() { conditions[idx].value = this.value; });
        });
        document.querySelectorAll('.btnRemoveCond').forEach(btn => {
            btn.addEventListener('click', function() {
                conditions.splice(parseInt(this.dataset.idx), 1);
                renderConditions();
            });
        });
    }

    // ─── Actions (specific fields per type + tag buttons) ───
    function getTagButtons(targetId) {
        const currentEvent = eventSelect.value;
        const fields = eventFields[currentEvent] || {};
        return Object.entries(fields).map(([key, meta]) =>
            `<button type="button" class="btn btn-sm btn-outline-secondary tag-btn me-1 mb-1" data-tag="{{${key}}}" data-target="${targetId}" title="${meta.label}">{{${key}}}</button>`
        ).join('');
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function renderActions() {
        actList.innerHTML = '';
        actions.forEach((a, i) => {
            const type = a.type || 'notify';
            let specificFields = '';

            switch (type) {
                case 'email':
                    specificFields = `
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-0">Destinatário</label>
                            <input class="form-control form-control-sm actInput" data-idx="${i}" data-key="to" placeholder="email@exemplo.com ou {{email}}" value="${escHtml(a.to)}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-0">Assunto</label>
                            <input class="form-control form-control-sm actInput" data-idx="${i}" data-key="subject" placeholder="Assunto do e-mail" value="${escHtml(a.subject)}" id="actSubject_${i}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted mb-0">Corpo do E-mail</label>
                            <textarea class="form-control form-control-sm actInput" data-idx="${i}" data-key="body" rows="3" placeholder="Use tags como {{customer_name}} para personalizar" id="actBody_${i}">${escHtml(a.body)}</textarea>
                            <div class="mt-1"><small class="text-muted">Tags:</small> ${getTagButtons('actBody_' + i)}</div>
                        </div>`;
                    break;
                case 'notify':
                    specificFields = `
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Usuário (ID)</label>
                            <input class="form-control form-control-sm actInput" data-idx="${i}" data-key="user_id" placeholder="ID ou {{user_id}}" value="${escHtml(a.user_id)}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-0">Título</label>
                            <input class="form-control form-control-sm actInput" data-idx="${i}" data-key="title" placeholder="Título da notificação" value="${escHtml(a.title)}" id="actTitle_${i}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted mb-0">Mensagem</label>
                            <textarea class="form-control form-control-sm actInput" data-idx="${i}" data-key="message" rows="2" placeholder="Mensagem da notificação" id="actMsg_${i}">${escHtml(a.message)}</textarea>
                            <div class="mt-1"><small class="text-muted">Tags:</small> ${getTagButtons('actMsg_' + i)}</div>
                        </div>`;
                    break;
                case 'update_field':
                    specificFields = `
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Tabela</label>
                            <select class="form-select form-select-sm actInput" data-idx="${i}" data-key="table">
                                <option value="">Selecione...</option>
                                <option value="orders" ${a.table==='orders'?'selected':''}>Pedidos</option>
                                <option value="customers" ${a.table==='customers'?'selected':''}>Clientes</option>
                                <option value="products" ${a.table==='products'?'selected':''}>Produtos</option>
                                <option value="suppliers" ${a.table==='suppliers'?'selected':''}>Fornecedores</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Coluna</label>
                            <input class="form-control form-control-sm actInput" data-idx="${i}" data-key="column" placeholder="nome_da_coluna" value="${escHtml(a.column)}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Novo Valor</label>
                            <input class="form-control form-control-sm actInput" data-idx="${i}" data-key="value" placeholder="Valor ou {{tag}}" value="${escHtml(a.value)}">
                        </div>`;
                    break;
                case 'log':
                    specificFields = `
                        <div class="col-12">
                            <label class="form-label small text-muted mb-0">Mensagem do Log</label>
                            <textarea class="form-control form-control-sm actInput" data-idx="${i}" data-key="message" rows="2" placeholder="Mensagem de log" id="actLog_${i}">${escHtml(a.message)}</textarea>
                            <div class="mt-1"><small class="text-muted">Tags:</small> ${getTagButtons('actLog_' + i)}</div>
                        </div>`;
                    break;
            }

            actList.innerHTML += `
                <div class="card mb-2 border">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-start">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-0">Tipo de Ação</label>
                                <select class="form-select form-select-sm actionTypeSelect" data-idx="${i}">
                                    <option value="notify" ${type==='notify'?'selected':''}>🔔 Notificar</option>
                                    <option value="email" ${type==='email'?'selected':''}>📧 E-mail</option>
                                    <option value="log" ${type==='log'?'selected':''}>📋 Log</option>
                                    <option value="update_field" ${type==='update_field'?'selected':''}>✏️ Atualizar Campo</option>
                                </select>
                            </div>
                            ${specificFields}
                            <div class="col-auto ms-auto">
                                <button type="button" class="btn btn-sm btn-outline-danger btnRemoveAct" data-idx="${i}"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>`;
        });

        // Bind events after render
        document.querySelectorAll('.actionTypeSelect').forEach(sel => {
            sel.addEventListener('change', function() {
                const idx = parseInt(this.dataset.idx);
                actions[idx] = { type: this.value };
                renderActions();
            });
        });
        document.querySelectorAll('.actInput').forEach(inp => {
            const idx = parseInt(inp.dataset.idx);
            const key = inp.dataset.key;
            inp.addEventListener('input', function() { actions[idx][key] = this.value; });
            inp.addEventListener('change', function() { actions[idx][key] = this.value; });
        });
        document.querySelectorAll('.btnRemoveAct').forEach(btn => {
            btn.addEventListener('click', function() {
                actions.splice(parseInt(this.dataset.idx), 1);
                renderActions();
            });
        });

        // Tag buttons — insert at cursor
        document.querySelectorAll('.tag-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tag = this.dataset.tag;
                const target = document.getElementById(this.dataset.target);
                if (!target) return;
                const start = target.selectionStart;
                const end = target.selectionEnd;
                const text = target.value;
                target.value = text.substring(0, start) + tag + text.substring(end);
                target.focus();
                target.setSelectionRange(start + tag.length, start + tag.length);
                target.dispatchEvent(new Event('input'));
            });
        });
    }

    document.getElementById('addCondition').addEventListener('click', () => {
        conditions.push({field: '', operator: '==', value: ''});
        renderConditions();
    });
    document.getElementById('addAction').addEventListener('click', () => {
        actions.push({type: 'notify', title: '', message: ''});
        renderActions();
    });

    renderConditions();
    renderActions();

    document.getElementById('workflowForm').addEventListener('submit', function() {
        document.getElementById('conditionsInput').value = JSON.stringify(conditions);
        document.getElementById('actionsInput').value = JSON.stringify(actions);
    });
});
</script>
