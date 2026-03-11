<?php
// Preparar dados da agenda
$agendaMonth = $agendaMonth ?? (int)date('m');
$agendaYear = $agendaYear ?? (int)date('Y');
$scheduledContacts = $scheduledContacts ?? [];

// Agrupar contatos por dia para a agenda
$contactsByDay = [];
foreach ($scheduledContacts as $contact) {
    $day = (int)date('d', strtotime($contact['scheduled_date']));
    $contactsByDay[$day][] = $contact;
}

$monthNames = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $agendaMonth, $agendaYear);
$firstDayOfWeek = (int)date('w', mktime(0,0,0,$agendaMonth,1,$agendaYear));
$today = date('Y-m-d');

// Navegação meses
$prevMonth = $agendaMonth - 1;
$prevYear = $agendaYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $agendaMonth + 1;
$nextYear = $agendaYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- ============================================ -->
        <!-- COLUNA ESQUERDA: Formulário de Novo Pedido   -->
        <!-- ============================================ -->
        <div class="col-lg-7">
            <h2 class="mb-4 text-primary"><i class="fas fa-cart-plus me-2"></i>Novo Pedido</h2>
            
            <form id="orderForm" method="post" action="?page=orders&action=store">
                <?= csrf_field() ?>
                
                <!-- Escolha da Etapa Inicial -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                        <i class="fas fa-flag me-2"></i>Tipo do Pedido
                    </legend>
                    <p class="text-muted small mb-3">Escolha como o pedido será iniciado no sistema:</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="initial_stage" id="stage_contato" value="contato" checked autocomplete="off">
                            <label class="btn btn-outline-purple w-100 p-3 text-start" for="stage_contato" style="border-color:#9b59b6;">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:#9b59b6;color:#fff;">
                                        <i class="fas fa-phone fa-lg"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block">Contato</strong>
                                        <small class="text-muted">Cliente entrou em contato, ainda sem orçamento. Permite agendar data.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="initial_stage" id="stage_orcamento" value="orcamento" autocomplete="off">
                            <label class="btn btn-outline-primary w-100 p-3 text-start" for="stage_orcamento">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:#3498db;color:#fff;">
                                        <i class="fas fa-file-invoice-dollar fa-lg"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block">Orçamento</strong>
                                        <small class="text-muted">Cliente já pediu orçamento. Adicione produtos e valores.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </fieldset>

                <!-- Seleção de Cliente -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                        <i class="fas fa-user-tag me-2"></i>Dados do Cliente
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="customer_id" class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">Selecione um cliente...</option>
                                <?php if(isset($customers)): ?>
                                    <?php foreach($customers as $customer): ?>
                                        <option value="<?= $customer['id'] ?>"><?= $customer['name'] ?> (<?= $customer['document'] ?? 'N/A' ?>)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text"><a href="?page=customers&action=create" target="_blank"><i class="fas fa-plus-circle"></i> Cadastrar novo cliente</a></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Prioridade</label>
                            <select class="form-select" name="priority">
                                <option value="baixa">🟢 Baixa</option>
                                <option value="normal" selected>🔵 Normal</option>
                                <option value="alta">🟡 Alta</option>
                                <option value="urgente">🔴 Urgente</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- ========================================= -->
                <!-- SEÇÃO: CONTATO (agendamento + observações) -->
                <!-- ========================================= -->
                <div id="sectionContato">
                    <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                        <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color:#9b59b6;">
                            <i class="fas fa-phone me-2"></i>Dados do Contato
                        </legend>
                        
                        <div class="alert alert-light border mb-3" style="border-left:4px solid #9b59b6 !important;">
                            <i class="fas fa-info-circle me-2" style="color:#9b59b6;"></i>
                            O pedido será criado como <strong>Contato</strong>. Produtos e valores poderão ser adicionados quando o pedido avançar para <strong>Orçamento</strong>.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1 text-primary"></i>Agendamento
                                </label>
                                <input type="date" class="form-control" name="scheduled_date" id="scheduled_date" 
                                       min="<?= date('Y-m-d') ?>">
                                <div class="form-text">
                                    <i class="fas fa-clock me-1"></i>Se preenchido, o prazo de atraso só começará a contar a partir desta data.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-flag me-1 text-warning"></i>Prioridade do Contato
                                </label>
                                <div class="form-text mt-2 pt-1">
                                    Use a prioridade para destacar contatos urgentes na agenda.
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-sticky-note me-1 text-info"></i>Observações
                                </label>
                                <textarea class="form-control" name="notes" rows="4" 
                                          placeholder="Descreva o motivo do contato, interesse do cliente, informações relevantes..."></textarea>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <!-- ============================================ -->
                <!-- SEÇÃO: ORÇAMENTO (produtos + itens + valores) -->
                <!-- ============================================ -->
                <div id="sectionOrcamento" style="display:none;">
                    <!-- Info Pipeline -->
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        O pedido será criado diretamente na etapa <span class="badge bg-primary"><i class="fas fa-file-invoice-dollar me-1"></i>Orçamento</span>, 
                        pulando a etapa de contato.
                    </div>

                    <!-- Observações do Orçamento -->
                    <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                        <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                            <i class="fas fa-sticky-note me-2"></i>Observações
                        </legend>
                        <textarea class="form-control" name="notes_orcamento" rows="3" 
                                  placeholder="Observações sobre o orçamento (opcional)..."></textarea>
                    </fieldset>

                    <!-- Itens do Pedido -->
                    <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                        <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                            <i class="fas fa-list-alt me-2"></i>Itens do Pedido
                        </legend>
                        
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-hover" id="orderItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="35%">Produto</th>
                                        <th width="15%">Variação</th>
                                        <th width="10%">Qtd</th>
                                        <th width="15%">Preço Unit.</th>
                                        <th width="15%">Subtotal</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item-row">
                                        <td>
                                            <select class="form-select product-select" name="items[0][product_id]">
                                                <option value="">Escolha...</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select variation-select" name="items[0][combination_id]" style="display:none;">
                                                <option value="">Variação...</option>
                                            </select>
                                            <input type="hidden" class="grade-desc-input" name="items[0][grade_description]" value="">
                                            <span class="no-variation-text text-muted small">—</span>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control item-qty" name="items[0][quantity]" value="1" min="1">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control item-price" name="items[0][price]" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control item-subtotal" readonly value="0.00">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-info text-white" id="btnAddItem"><i class="fas fa-plus"></i> Adicionar Item</button>
                    </fieldset>
                </div>

                <!-- Rodapé com Total e Botões -->
                <div class="col-12 mt-4 text-end">
                    <div class="d-flex justify-content-end gap-2 align-items-center">
                        <div id="totalSection" style="display:none;">
                            <h4 class="mb-0 me-3">Total: <span class="text-primary fw-bold" id="grandTotalDisplay">R$ 0,00</span></h4>
                        </div>
                        <input type="hidden" name="total_amount" id="totalAmountInput" value="0">
                        <a href="?page=orders" class="btn btn-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-success px-4 btn-lg"><i class="fas fa-check-circle me-2"></i>Finalizar Pedido</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- COLUNA DIREITA: Agenda de Contatos           -->
        <!-- ============================================ -->
        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Agenda de Contatos</h5>
                    <a href="?page=orders&action=agenda" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-expand-alt me-1"></i>Expandir
                    </a>
                </div>
                <div class="card-body p-3">
                    <!-- Navegação do Mês -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="?page=orders&action=create&agenda_month=<?= $prevMonth ?>&agenda_year=<?= $prevYear ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <h6 class="mb-0 fw-bold"><?= $monthNames[$agendaMonth] ?> <?= $agendaYear ?></h6>
                        <a href="?page=orders&action=create&agenda_month=<?= $nextMonth ?>&agenda_year=<?= $nextYear ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <!-- Calendário -->
                    <table class="table table-bordered table-sm text-center mb-3 agenda-calendar">
                        <thead class="table-light">
                            <tr>
                                <?php foreach ($dayNames as $dn): ?>
                                    <th class="small py-2" style="font-size:0.75rem;"><?= $dn ?></th>
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
                                    <td class="bg-light" style="height:55px;"></td>
                                <?php else: 
                                    $currentDate = sprintf('%04d-%02d-%02d', $agendaYear, $agendaMonth, $dayCounter);
                                    $isToday = ($currentDate === $today);
                                    $hasContacts = isset($contactsByDay[$dayCounter]);
                                    $contactCount = $hasContacts ? count($contactsByDay[$dayCounter]) : 0;
                                ?>
                                    <td class="position-relative <?= $isToday ? 'bg-primary bg-opacity-10 border-primary' : '' ?>" 
                                        style="height:55px; cursor:<?= $hasContacts ? 'pointer' : 'default' ?>; vertical-align:top; padding:4px;"
                                        <?php if($hasContacts): ?>
                                        data-bs-toggle="popover" 
                                        data-bs-trigger="click"
                                        data-bs-html="true"
                                        data-bs-title="Contatos - <?= str_pad($dayCounter, 2, '0', STR_PAD_LEFT) ?>/<?= str_pad($agendaMonth, 2, '0', STR_PAD_LEFT) ?>"
                                        data-bs-content="<?php 
                                            $popoverContent = '';
                                            foreach($contactsByDay[$dayCounter] as $c) {
                                                $prioColors = ['urgente'=>'danger','alta'=>'warning','normal'=>'primary','baixa'=>'secondary'];
                                                $pColor = $prioColors[$c['priority']] ?? 'primary';
                                                $popoverContent .= '<div class=&quot;mb-2 pb-2 border-bottom&quot;>';
                                                $popoverContent .= '<strong>#' . str_pad($c['id'], 4, '0', STR_PAD_LEFT) . '</strong> ';
                                                $popoverContent .= '<span class=&quot;badge bg-' . $pColor . ' rounded-pill&quot; style=&quot;font-size:0.65rem&quot;>' . ucfirst($c['priority']) . '</span><br>';
                                                $popoverContent .= '<i class=&quot;fas fa-user me-1&quot;></i>' . e($c['customer_name']) . '<br>';
                                                if (!empty($c['customer_phone'])) {
                                                    $popoverContent .= '<i class=&quot;fas fa-phone me-1&quot;></i>' . e($c['customer_phone']) . '<br>';
                                                }
                                                if (!empty($c['notes'])) {
                                                    $popoverContent .= '<small class=&quot;text-muted&quot;>' . e(mb_substr($c['notes'], 0, 60)) . '...</small>';
                                                }
                                                $popoverContent .= '</div>';
                                            }
                                            $popoverContent .= '<a href=&quot;?page=orders&amp;action=report&amp;date=' . $currentDate . '&quot; target=&quot;_blank&quot; class=&quot;btn btn-sm btn-outline-primary w-100 mt-1&quot;><i class=&quot;fas fa-print me-1&quot;></i>Imprimir</a>';
                                            echo $popoverContent;
                                        ?>"
                                        <?php endif; ?>
                                    >
                                        <span class="small <?= $isToday ? 'fw-bold text-primary' : '' ?>"><?= $dayCounter ?></span>
                                        <?php if($hasContacts): ?>
                                            <div class="mt-1">
                                                <span class="badge rounded-pill bg-purple px-2" style="font-size:0.6rem;background:#9b59b6!important;">
                                                    <?= $contactCount ?> <i class="fas fa-phone" style="font-size:0.5rem;"></i>
                                                </span>
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

                    <!-- Legenda -->
                    <div class="d-flex gap-3 small text-muted mb-3">
                        <span><span class="badge rounded-pill" style="background:#9b59b6;font-size:0.6rem;">&nbsp;</span> Contatos agendados</span>
                        <span><span class="badge rounded-pill bg-primary" style="font-size:0.6rem;">&nbsp;</span> Hoje</span>
                    </div>

                    <!-- Relatório rápido -->
                    <div class="border-top pt-3">
                        <h6 class="fw-bold small text-muted mb-2"><i class="fas fa-print me-1"></i>Imprimir Relatório de Contatos</h6>
                        <form class="d-flex gap-2" target="_blank">
                            <input type="hidden" name="page" value="orders">
                            <input type="hidden" name="action" value="report">
                            <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary flex-shrink-0">
                                <i class="fas fa-print me-1"></i>Imprimir
                            </button>
                        </form>
                    </div>

                    <!-- Lista de próximos contatos -->
                    <?php 
                    $upcomingContacts = [];
                    foreach ($scheduledContacts as $c) {
                        if ($c['scheduled_date'] >= $today) {
                            $upcomingContacts[] = $c;
                        }
                    }
                    if (!empty($upcomingContacts)):
                    ?>
                    <div class="border-top pt-3 mt-3">
                        <h6 class="fw-bold small text-muted mb-2"><i class="fas fa-list me-1"></i>Próximos Contatos</h6>
                        <div style="max-height:250px; overflow-y:auto;">
                            <?php foreach (array_slice($upcomingContacts, 0, 10) as $uc): 
                                $prioColors = ['urgente'=>'danger','alta'=>'warning','normal'=>'primary','baixa'=>'secondary'];
                                $pColor = $prioColors[$uc['priority']] ?? 'primary';
                            ?>
                            <div class="d-flex align-items-start p-2 mb-2 rounded border-start border-3 bg-light" 
                                 style="border-color:<?= $uc['priority'] === 'urgente' ? '#e74c3c' : ($uc['priority'] === 'alta' ? '#f39c12' : '#9b59b6') ?> !important;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="small">#<?= str_pad($uc['id'], 4, '0', STR_PAD_LEFT) ?> - <?= e($uc['customer_name']) ?></strong>
                                        <span class="badge bg-<?= $pColor ?> rounded-pill" style="font-size:0.6rem;"><?= ucfirst($uc['priority']) ?></span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($uc['scheduled_date'])) ?>
                                        <?php if(!empty($uc['customer_phone'])): ?>
                                            &middot; <i class="fas fa-phone me-1"></i><?= e($uc['customer_phone']) ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php if (!empty($uc['notes'])): ?>
                                        <div class="small text-muted mt-1"><i class="fas fa-sticky-note me-1"></i><?= e(mb_substr($uc['notes'], 0, 80)) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-check:checked + .btn-outline-purple {
    background-color: #9b59b6;
    border-color: #9b59b6;
    color: #fff;
}
.btn-outline-purple:hover {
    background-color: rgba(155,89,182,0.1);
}
.btn-check:checked + .btn-outline-primary {
    background-color: #3498db;
    border-color: #3498db;
    color: #fff;
}
.agenda-calendar td {
    transition: background 0.2s;
}
.agenda-calendar td:hover {
    background-color: rgba(52,152,219,0.08) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const products = <?= json_encode($products ?? []) ?>;
    const productCombosCreate = <?= json_encode($productCombinations ?? []) ?>;
    const sectionContato = document.getElementById('sectionContato');
    const sectionOrcamento = document.getElementById('sectionOrcamento');
    const totalSection = document.getElementById('totalSection');
    const stageContato = document.getElementById('stage_contato');
    const stageOrcamento = document.getElementById('stage_orcamento');

    // ── Alternar entre Contato e Orçamento ──
    function toggleSections() {
        const isContato = stageContato.checked;
        sectionContato.style.display = isContato ? 'block' : 'none';
        sectionOrcamento.style.display = isContato ? 'none' : 'block';
        totalSection.style.display = isContato ? 'none' : 'flex';
        
        // Ajustar required nos campos
        document.querySelectorAll('#sectionOrcamento .product-select, #sectionOrcamento .item-qty, #sectionOrcamento .item-price').forEach(el => {
            el.required = !isContato;
        });
    }

    stageContato.addEventListener('change', toggleSections);
    stageOrcamento.addEventListener('change', toggleSections);
    toggleSections(); // Estado inicial

    // ── Produtos ──
    function populateProductSelect(selectElement) {
        products.forEach(p => {
            let option = document.createElement('option');
            option.value = p.id;
            option.text = p.name;
            option.dataset.price = p.price;
            option.dataset.hasCombos = (productCombosCreate[p.id] && productCombosCreate[p.id].length > 0) ? '1' : '0';
            selectElement.appendChild(option);
        });
    }

    function handleProductChange(row, selectElement) {
        const pid = selectElement.value;
        const variationSelect = row.querySelector('.variation-select');
        const gradeDescInput = row.querySelector('.grade-desc-input');
        const noVariation = row.querySelector('.no-variation-text');
        
        if (pid && productCombosCreate[pid] && productCombosCreate[pid].length > 0) {
            variationSelect.style.display = '';
            noVariation.style.display = 'none';
            variationSelect.innerHTML = '<option value="">Variação...</option>';
            productCombosCreate[pid].forEach(c => {
                const lbl = c.combination_label + (c.price_override ? ' — R$ ' + parseFloat(c.price_override).toFixed(2).replace('.', ',') : '');
                variationSelect.innerHTML += `<option value="${c.id}" data-price="${c.price_override || ''}" data-label="${c.combination_label}">${lbl}</option>`;
            });
            gradeDescInput.value = '';
        } else {
            variationSelect.style.display = 'none';
            noVariation.style.display = '';
            variationSelect.innerHTML = '';
            gradeDescInput.value = '';
        }
    }

    const firstSelect = document.querySelector('.product-select');
    if(firstSelect) populateProductSelect(firstSelect);

    // Adicionar novo item
    document.getElementById('btnAddItem').addEventListener('click', function() {
        const tbody = document.querySelector('#orderItemsTable tbody');
        const rowCount = tbody.rows.length;
        const newRow = tbody.rows[0].cloneNode(true);
        
        newRow.querySelectorAll('input').forEach(input => {
            if (input.classList.contains('item-qty')) {
                input.value = 1;
            } else if (input.classList.contains('grade-desc-input')) {
                input.value = '';
            } else if (!input.classList.contains('item-subtotal')) {
                input.value = '';
            }
            if(input.name) input.name = input.name.replace(/\[\d+\]/, `[${rowCount}]`);
        });
        
        newRow.querySelectorAll('select').forEach(sel => {
            sel.name = sel.name.replace(/\[\d+\]/, `[${rowCount}]`);
            sel.value = "";
        });

        // Reset variation column
        const varSel = newRow.querySelector('.variation-select');
        if (varSel) { varSel.style.display = 'none'; varSel.innerHTML = ''; }
        const noVar = newRow.querySelector('.no-variation-text');
        if (noVar) noVar.style.display = '';
        
        tbody.appendChild(newRow);
    });

    // Cálculo de subtotal e total
    document.querySelector('#orderItemsTable').addEventListener('change', function(e) {
        if(e.target.classList.contains('product-select')) {
            const price = e.target.options[e.target.selectedIndex].dataset.price;
            const row = e.target.closest('tr');
            row.querySelector('.item-price').value = price;
            calculateRow(row);
            handleProductChange(row, e.target);
        }
        if(e.target.classList.contains('variation-select')) {
            const opt = e.target.options[e.target.selectedIndex];
            const row = e.target.closest('tr');
            const gradeDesc = row.querySelector('.grade-desc-input');
            if (gradeDesc) gradeDesc.value = opt ? (opt.dataset.label || '') : '';
            // Override price if variation has specific price
            if (opt && opt.dataset.price && opt.dataset.price !== '') {
                row.querySelector('.item-price').value = parseFloat(opt.dataset.price).toFixed(2);
                calculateRow(row);
            }
        }
        if(e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
            calculateRow(e.target.closest('tr'));
        }
    });

    function calculateRow(row) {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.item-subtotal').value = subtotal.toFixed(2);
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-subtotal').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('grandTotalDisplay').innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
        document.getElementById('totalAmountInput').value = total.toFixed(2);
    }

    // Remover item
    document.querySelector('#orderItemsTable').addEventListener('click', function(e) {
        if(e.target.closest('.btn-remove-item')) {
            const tbody = document.querySelector('#orderItemsTable tbody');
            if(tbody.rows.length > 1) {
                e.target.closest('tr').remove();
                calculateTotal();
            } else {
                // Limpar a última linha em vez de impedir remoção
                const lastRow = tbody.rows[0];
                lastRow.querySelector('.product-select').value = '';
                lastRow.querySelector('.item-qty').value = 1;
                lastRow.querySelector('.item-price').value = '';
                lastRow.querySelector('.item-subtotal').value = '0.00';
                const varSel = lastRow.querySelector('.variation-select');
                if (varSel) { varSel.style.display = 'none'; varSel.innerHTML = ''; }
                const gradeDesc = lastRow.querySelector('.grade-desc-input');
                if (gradeDesc) gradeDesc.value = '';
                const noVar = lastRow.querySelector('.no-variation-text');
                if (noVar) noVar.style.display = '';
                calculateTotal();
            }
        }
    });

    // Ajustar o campo notes no submit - se orçamento, usa notes_orcamento
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (stageOrcamento.checked) {
            const notesOrc = document.querySelector('[name="notes_orcamento"]');
            if (notesOrc) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'notes';
                hidden.value = notesOrc.value;
                this.appendChild(hidden);
            }
        }
    });

    // ── Bootstrap Popovers para a agenda ──
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (el) {
        return new bootstrap.Popover(el, {
            container: 'body',
            placement: 'auto',
            sanitize: false
        });
    });

    // Fechar popovers ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-bs-toggle="popover"]') && !e.target.closest('.popover')) {
            popoverTriggerList.forEach(function(el) {
                var popover = bootstrap.Popover.getInstance(el);
                if (popover) popover.hide();
            });
        }
    });
});
</script>
