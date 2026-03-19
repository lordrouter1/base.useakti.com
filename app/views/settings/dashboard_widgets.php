<?php
/**
 * Settings Tab: Dashboard Widgets
 * Permite ao admin configurar quais widgets cada grupo de usuários vê no dashboard.
 *
 * Variáveis do escopo pai (SettingsController):
 *   $dashGroups          — array de grupos
 *   $dashSelectedGroupId — grupo selecionado (ou null)
 *   $dashGroupConfig     — config do grupo selecionado (ou null)
 *   $dashAvailableWidgets — lista de todos os widgets
 *   $dashHasCustomConfig  — bool, se o grupo tem personalização
 */
$groups   = $dashGroups ?? [];
$selGroup = $dashSelectedGroupId ?? null;
$config   = $dashGroupConfig ?? [];
$widgets  = $dashAvailableWidgets ?? \Akti\Models\DashboardWidget::getAvailableWidgets();
$hasCustom = $dashHasCustomConfig ?? false;

// Montar mapa da config atual (widget_key => is_visible)
$configMap = [];
$configOrder = [];
foreach ($config as $idx => $row) {
    $configMap[$row['widget_key']] = (int)$row['is_visible'];
    $configOrder[] = $row['widget_key'];
}

// Se não tem config, usar ordem padrão com tudo visível
if (empty($configOrder)) {
    $configOrder = array_keys($widgets);
    foreach ($configOrder as $k) {
        $configMap[$k] = 1;
    }
}

// Garantir que widgets novos que não estão na config apareçam no final
foreach ($widgets as $k => $w) {
    if (!in_array($k, $configOrder)) {
        $configOrder[] = $k;
        $configMap[$k] = 1;
    }
}
?>

<!-- jQuery UI para sortable (drag-and-drop) -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-lg me-3"></i>
                <div>
                    <strong>Dashboard por Grupo</strong>
                    <div class="small">Configure quais widgets cada grupo de usuários vê no dashboard. Arraste para reordenar. 
                    Se um grupo não tiver configuração personalizada, todos os widgets serão exibidos na ordem padrão.</div>
                </div>
            </div>
        </div>

        <!-- Seletor de grupo -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted mb-1">
                            <i class="fas fa-users me-1"></i>Grupo de Usuários
                        </label>
                        <select class="form-select" id="dashGroupSelector" onchange="location.href='?page=settings&tab=dashboard&group_id='+this.value">
                            <option value="">— Selecione um grupo —</option>
                            <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $selGroup == $g['id'] ? 'selected' : '' ?>>
                                <?= e($g['name']) ?>
                                <?php if ($selGroup == $g['id'] && $hasCustom): ?>
                                    ★ Personalizado
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <?php if ($selGroup): ?>
                            <?php if ($hasCustom): ?>
                                <span class="badge bg-success me-2"><i class="fas fa-check me-1"></i>Personalizado</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="resetGroupConfig(<?= $selGroup ?>)">
                                    <i class="fas fa-undo me-1"></i>Restaurar Padrão
                                </button>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-globe me-1"></i>Usando padrão global</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selGroup): ?>
        <!-- Lista de widgets (drag-and-drop) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-th-list me-2"></i>Widgets do Dashboard</h6>
                <div>
                    <button class="btn btn-sm btn-outline-success me-1" onclick="toggleAll(true)" title="Ativar todos">
                        <i class="fas fa-eye me-1"></i>Todos
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)" title="Desativar todos">
                        <i class="fas fa-eye-slash me-1"></i>Nenhum
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="widgetSortable">
                    <?php foreach ($configOrder as $wKey):
                        if (!isset($widgets[$wKey])) continue;
                        $w = $widgets[$wKey];
                        $isVisible = $configMap[$wKey] ?? 1;
                    ?>
                    <li class="list-group-item widget-item d-flex align-items-center gap-3 py-3 px-3" data-key="<?= $wKey ?>">
                        <!-- Drag handle -->
                        <div class="drag-handle text-muted" style="cursor:grab;font-size:1.1rem;" title="Arraste para reordenar">
                            <i class="fas fa-grip-vertical"></i>
                        </div>

                        <!-- Ícone -->
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:40px;height:40px;background:rgba(52,152,219,0.1);">
                            <i class="<?= $w['icon'] ?> text-primary"></i>
                        </div>

                        <!-- Info -->
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?= e($w['label']) ?></div>
                            <div class="text-muted" style="font-size:0.72rem;"><?= e($w['description']) ?></div>
                        </div>

                        <!-- Toggle visibilidade -->
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input widget-toggle" type="checkbox" role="switch"
                                   data-key="<?= $wKey ?>" <?= $isVisible ? 'checked' : '' ?>
                                   id="toggle-<?= $wKey ?>" style="width:2.5em;height:1.3em;cursor:pointer;">
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-footer bg-white text-end p-3">
                <button class="btn btn-primary fw-bold px-4" id="btnSaveWidgets" onclick="saveWidgets()">
                    <i class="fas fa-save me-2"></i>Salvar Configuração
                </button>
            </div>
        </div>

        <style>
        .widget-item {
            transition: background-color 0.15s ease, box-shadow 0.15s ease;
        }
        .widget-item:hover {
            background-color: #f8f9fa;
        }
        .widget-item.ui-sortable-helper {
            background: #fff;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12);
            border-radius: 8px;
            z-index: 10;
        }
        .widget-item.ui-sortable-placeholder {
            visibility: visible !important;
            background: #eef6ff;
            border: 2px dashed #3498db;
            border-radius: 8px;
            height: 60px;
        }
        .drag-handle:active {
            cursor: grabbing;
        }
        </style>

        <script>
        $(function(){
            // Inicializar sortable com jQuery UI
            $('#widgetSortable').sortable({
                handle: '.drag-handle',
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                axis: 'y',
                opacity: 0.85,
                update: function() {
                    // Visual feedback que houve mudança
                    $('#btnSaveWidgets').removeClass('btn-primary').addClass('btn-warning').html('<i class="fas fa-exclamation-triangle me-2"></i>Salvar Alterações');
                }
            });

            // Toggle visual feedback
            $('.widget-toggle').on('change', function(){
                $('#btnSaveWidgets').removeClass('btn-primary').addClass('btn-warning').html('<i class="fas fa-exclamation-triangle me-2"></i>Salvar Alterações');
            });
        });

        function toggleAll(state) {
            $('.widget-toggle').prop('checked', state).trigger('change');
        }

        function saveWidgets() {
            var btn = document.getElementById('btnSaveWidgets');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';

            var widgets = [];
            $('#widgetSortable .widget-item').each(function(){
                var key = $(this).data('key');
                var visible = $(this).find('.widget-toggle').is(':checked') ? 1 : 0;
                widgets.push({ widget_key: key, is_visible: visible });
            });

            $.ajax({
                url: '?page=settings&action=saveDashboardWidgets',
                method: 'POST',
                data: {
                    group_id: <?= (int)$selGroup ?>,
                    widgets: JSON.stringify(widgets),
                    csrf_token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Salvo!',
                            text: 'A configuração do dashboard foi atualizada.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function(){
                            location.reload();
                        });
                    } else {
                        Swal.fire('Erro', resp.message || 'Erro ao salvar.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Configuração';
                    }
                },
                error: function() {
                    Swal.fire('Erro', 'Erro de comunicação com o servidor.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Configuração';
                }
            });
        }

        function resetGroupConfig(groupId) {
            Swal.fire({
                title: 'Restaurar Padrão?',
                text: 'Isso removerá a personalização deste grupo. Todos os widgets serão exibidos na ordem padrão.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-undo me-1"></i> Restaurar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: '?page=settings&action=resetDashboardWidgets',
                    method: 'POST',
                    data: {
                        group_id: groupId,
                        csrf_token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Restaurado!',
                                text: 'O grupo voltou à configuração padrão.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function(){ location.reload(); });
                        } else {
                            Swal.fire('Erro', resp.message || 'Erro ao restaurar.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Erro', 'Erro de comunicação.', 'error');
                    }
                });
            });
        }
        </script>

        <?php else: ?>
        <!-- Nenhum grupo selecionado -->
        <div class="text-center text-muted py-5">
            <i class="fas fa-mouse-pointer d-block mb-3" style="font-size:2.5rem;opacity:0.3;"></i>
            <h5>Selecione um grupo</h5>
            <p class="small">Escolha um grupo de usuários para configurar os widgets do dashboard.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
