<?php
$agendaMonth = $agendaMonth ?? (int)date('m');
$agendaYear = $agendaYear ?? (int)date('Y');
$scheduledContacts = $scheduledContacts ?? [];

// Agrupar contatos por dia
$contactsByDay = [];
foreach ($scheduledContacts as $contact) {
    $day = (int)date('d', strtotime($contact['scheduled_date']));
    $contactsByDay[$day][] = $contact;
}

$monthNames = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$dayNames = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
$dayNamesShort = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $agendaMonth, $agendaYear);
$firstDayOfWeek = (int)date('w', mktime(0,0,0,$agendaMonth,1,$agendaYear));
$today = date('Y-m-d');

$prevMonth = $agendaMonth - 1; $prevYear = $agendaYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $agendaMonth + 1; $nextYear = $agendaYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$prioColors = ['urgente'=>'danger','alta'=>'warning','normal'=>'primary','baixa'=>'secondary'];
$prioIcons  = ['urgente'=>'🔴','alta'=>'🟡','normal'=>'🔵','baixa'=>'🟢'];
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Agenda de Contatos</h1>
        <div class="btn-toolbar gap-2">
            <a href="?page=orders&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Novo Pedido
            </a>
            <a href="?page=pipeline" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-stream me-1"></i>Pipeline
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Calendário Grande -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <a href="?page=agenda&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chevron-left me-1"></i>Anterior
                    </a>
                    <h4 class="mb-0 fw-bold text-primary"><?= $monthNames[$agendaMonth] ?> <?= $agendaYear ?></h4>
                    <a href="?page=agenda&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline-secondary btn-sm">
                        Próximo<i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <?php foreach ($dayNamesShort as $dn): ?>
                                    <th class="text-center py-2 small"><?= $dn ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $dayCounter = 1;
                            $totalWeeks = ceil(($daysInMonth + $firstDayOfWeek) / 7);
                            for ($week = 0; $week < $totalWeeks; $week++):
                            ?>
                            <tr>
                                <?php for ($dow = 0; $dow < 7; $dow++): 
                                    $cellIndex = $week * 7 + $dow;
                                    if ($cellIndex < $firstDayOfWeek || $dayCounter > $daysInMonth):
                                ?>
                                    <td class="bg-light" style="height:110px;"></td>
                                <?php else:
                                    $currentDate = sprintf('%04d-%02d-%02d', $agendaYear, $agendaMonth, $dayCounter);
                                    $isToday = ($currentDate === $today);
                                    $hasContacts = isset($contactsByDay[$dayCounter]);
                                    $dayContacts = $hasContacts ? $contactsByDay[$dayCounter] : [];
                                ?>
                                    <td class="<?= $isToday ? 'bg-primary ' : '' ?>" 
                                        style="height:110px; vertical-align:top; padding:6px; width:14.28%;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small <?= $isToday ? 'text-primary' : 'text-muted' ?>"><?= $dayCounter ?></span>
                                            <?php if ($hasContacts): ?>
                                                <div class="d-flex gap-1">
                                                    <a href="?page=orders&action=report&date=<?= $currentDate ?>" target="_blank" 
                                                       class="btn btn-outline-secondary px-1 py-0" style="font-size:0.6rem;" title="Imprimir relatório">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <span class="badge rounded-pill" style="background:#9b59b6;font-size:0.65rem;"><?= count($dayContacts) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($hasContacts): ?>
                                            <div style="max-height:70px; overflow-y:auto;">
                                                <?php foreach (array_slice($dayContacts, 0, 3) as $c): 
                                                    $pc = $prioColors[$c['priority']] ?? 'primary';
                                                ?>
                                                    <div class="small mb-1 p-1 rounded bg-white border-start border-2 border-<?= $pc ?>" style="font-size:0.68rem; line-height:1.2;">
                                                        <strong><?= e(mb_substr($c['customer_name'], 0, 15)) ?></strong>
                                                        <?php if(!empty($c['customer_phone'])): ?>
                                                            <br><i class="fas fa-phone" style="font-size:0.55rem;"></i> <?= e($c['customer_phone']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($dayContacts) > 3): ?>
                                                    <div class="text-center small text-muted" style="font-size:0.6rem;">+<?= count($dayContacts) - 3 ?> mais</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php 
                                    $dayCounter++;
                                    endif;
                                endfor; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Painel Lateral -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <!-- Imprimir Relatório -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-print me-2 text-primary"></i>Imprimir Relatório</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Selecione uma data para gerar um relatório imprimível dos contatos agendados.</p>
                    <form class="d-flex gap-2" target="_blank">
                        <input type="hidden" name="page" value="orders">
                        <input type="hidden" name="action" value="report">
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        <button type="submit" class="btn btn-sm btn-primary flex-shrink-0">
                            <i class="fas fa-print me-1"></i>Gerar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Todos os contatos do mês -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-primary"></i>Contatos do Mês</h6>
                    <span class="badge bg-primary rounded-pill"><?= count($scheduledContacts) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($scheduledContacts)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                            <span class="small">Nenhum contato agendado para este mês.</span>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" style="max-height:500px; overflow-y:auto;">
                            <?php foreach ($scheduledContacts as $c): 
                                $pc = $prioColors[$c['priority']] ?? 'primary';
                                $pi = $prioIcons[$c['priority']] ?? '🔵';
                                $isPast = $c['scheduled_date'] < $today;
                            ?>
                            <a href="?page=pipeline&action=detail&id=<?= $c['id'] ?>" 
                               class="list-group-item list-group-item-action <?= $isPast ? 'bg-light' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <strong class="small">#<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                                            <span class="badge bg-<?= $pc ?> rounded-pill" style="font-size:0.6rem;"><?= $pi ?> <?= ucfirst($c['priority']) ?></span>
                                            <?php if ($isPast): ?>
                                                <span class="badge bg-danger rounded-pill" style="font-size:0.6rem;">Atrasado</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small fw-bold"><?= e($c['customer_name']) ?></div>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($c['scheduled_date'])) ?>
                                            <?php if (!empty($c['customer_phone'])): ?>
                                                &middot; <i class="fas fa-phone me-1"></i><?= e($c['customer_phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($c['notes'])): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-sticky-note me-1"></i><?= e(mb_substr($c['notes'], 0, 60)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small ms-2 mt-2"></i>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
