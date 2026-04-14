<?php
/**
 * Workflows — Listagem de regras
 * FEAT-010 (melhorado: drag & drop prioridade)
 * Variáveis: $rules
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-cogs me-2 text-primary"></i>Automação de Workflows</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Regras automáticas que executam ações com base em eventos do sistema. Arraste para reordenar a prioridade.</p>
        </div>
        <a href="?page=workflows&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Regra</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="rulesTable">
                    <caption class="visually-hidden">Lista de regras de automação de workflows</caption>
                    <thead class="table-light">
                        <tr>
                            <th style="width:30px"></th>
                            <th>Nome</th>
                            <th>Evento</th>
                            <th style="width:50px">#</th>
                            <th>Execuções</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="rulesBody">
                    <?php if (empty($rules)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma regra cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rules as $idx => $r): ?>
                        <tr data-id="<?= (int) $r['id'] ?>">
                            <td class="drag-handle" style="cursor:grab"><i class="fas fa-grip-vertical text-muted"></i></td>
                            <td>
                                <div class="fw-bold"><?= e($r['name']) ?></div>
                                <?php if (!empty($r['description'])): ?>
                                <small class="text-muted"><?= e(mb_substr($r['description'], 0, 80)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code style="font-size:.8rem;"><?= e($r['event']) ?></code></td>
                            <td><span class="badge bg-body-secondary text-body priority-badge"><?= $idx + 1 ?></span></td>
                            <td><?= (int) ($r['trigger_count'] ?? 0) ?></td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggleRule" type="checkbox" data-id="<?= (int) $r['id'] ?>" <?= $r['is_active'] ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="?page=workflows&action=edit&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar" aria-label="Editar regra"><i class="fas fa-edit" aria-hidden="true"></i></a>
                                <button class="btn btn-sm btn-outline-info btnLogs" data-id="<?= (int) $r['id'] ?>" title="Logs" aria-label="Ver logs"><i class="fas fa-history" aria-hidden="true"></i></button>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $r['id'] ?>" title="Excluir" aria-label="Excluir regra"><i class="fas fa-trash" aria-hidden="true"></i></button>
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?= asset('assets/js/modules/workflows-index.js') ?>"></script>
