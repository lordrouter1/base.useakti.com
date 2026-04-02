<?php
/**
 * Agenda / Calendário — Listagem
 * FEAT-007
 * Variáveis: $upcomingEvents
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= addslashes($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-calendar-alt me-2 text-primary"></i>Agenda</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Calendário integrado de eventos, pedidos e vencimentos.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <button class="btn btn-sm btn-outline-info" id="btnSync"><i class="fas fa-sync me-1"></i>Sincronizar</button>
            <button class="btn btn-sm btn-primary" id="btnNewEvent"><i class="fas fa-plus me-1"></i>Novo Evento</button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Calendário principal -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="fullCalendar" style="min-height:600px;"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Próximos eventos -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Próximos Eventos</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="upcomingList">
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="list-group-item text-muted text-center py-3">Nenhum evento próximo.</div>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $ev): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted"><?= date('d/m H:i', strtotime($ev['start_date'])) ?></small>
                                    <div class="fw-bold" style="font-size:.85rem;"><?= e($ev['title']) ?></div>
                                    <?php if (!empty($ev['description'])): ?>
                                    <small class="text-muted"><?= e(mb_substr($ev['description'], 0, 60)) ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge" style="background-color:<?= eAttr($ev['color'] ?? '#0d6efd') ?>;font-size:.65rem;">
                                    <?= e($ev['type'] ?? 'custom') ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CDN -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const calendar = new FullCalendar.Calendar(document.getElementById('fullCalendar'), {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: function(info, successCallback, failureCallback) {
            fetch('?page=calendar&action=events&start=' + info.startStr + '&end=' + info.endStr)
                .then(r => r.json())
                .then(data => successCallback(data.data || []))
                .catch(failureCallback);
        },
        editable: true,
        selectable: true,
        dateClick: function(info) {
            Swal.fire({
                title: 'Novo Evento',
                html: '<input id="swalTitle" class="swal2-input" placeholder="Título">' +
                      '<textarea id="swalDesc" class="swal2-textarea" placeholder="Descrição"></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => ({
                    title: document.getElementById('swalTitle').value,
                    description: document.getElementById('swalDesc').value,
                    start_date: info.dateStr,
                    type: 'custom'
                })
            }).then(result => {
                if (result.isConfirmed && result.value.title) {
                    const fd = new FormData();
                    Object.keys(result.value).forEach(k => fd.append(k, result.value[k]));
                    fetch('?page=calendar&action=store', {
                        method: 'POST', body: fd,
                        headers: {'X-CSRF-TOKEN': csrfToken}
                    }).then(() => calendar.refetchEvents());
                }
            });
        },
        eventClick: function(info) {
            Swal.fire({
                title: info.event.title,
                text: info.event.extendedProps.description || '',
                icon: 'info',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'OK',
                denyButtonText: 'Excluir',
                cancelButtonText: 'Fechar'
            }).then(result => {
                if (result.isDenied) {
                    fetch('?page=calendar&action=delete&id=' + info.event.id, {
                        headers: {'X-CSRF-TOKEN': csrfToken}
                    }).then(() => calendar.refetchEvents());
                }
            });
        }
    });
    calendar.render();

    document.getElementById('btnSync')?.addEventListener('click', function() {
        fetch('?page=calendar&action=sync', {method: 'POST', headers: {'X-CSRF-TOKEN': csrfToken}})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    AktiToast.success('Agenda sincronizada!');
                    calendar.refetchEvents();
                }
            });
    });
});
</script>
