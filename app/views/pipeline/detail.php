<div class="container py-4">
    <?php
        $customerFormattedAddress = '';
        if (!empty($order['customer_address'])) {
            $customerFormattedAddress = \Akti\Models\CompanySettings::formatCustomerAddress($order['customer_address']);
        }
        $currentStage = $order['pipeline_stage'] ?? 'contato';
        $stageInfo = $stages[$currentStage] ?? ['label' => $currentStage, 'color' => '#999', 'icon' => 'fas fa-circle'];
        $hoursInStage = (int)$order['hours_in_stage'];
        $stageGoal = isset($goals[$currentStage]) ? (int)$goals[$currentStage]['max_hours'] : 24;
        $isDelayed = ($stageGoal > 0 && $hoursInStage > $stageGoal);
        $isReadOnly = in_array($currentStage, ['concluido', 'cancelado']);
        $canUseBoletoModule = \Akti\Core\ModuleBootloader::isModuleEnabled('boleto');
        $canUseFiscalModule = \Akti\Core\ModuleBootloader::isModuleEnabled('fiscal');
        $canUseNfeModule = \Akti\Core\ModuleBootloader::isModuleEnabled('nfe');

        // Etapas bloqueadas quando existem parcelas pagas (produção ou anteriores + cancelado)
        $stagesBlockedByPaid = ['contato', 'orcamento', 'venda', 'producao', 'cancelado'];
        $hasPaidInstallments = !empty($hasAnyPaidInstallment);
    ?>

    <!-- ═══ Detail Page Header — Modern SaaS style ═══ -->
    <div class="pipeline-page-header" style="padding-bottom:12px;margin-bottom:16px;">
        <div style="min-width:0;">
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="?page=pipeline" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--radius-sm);padding:4px 10px;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="mb-0" style="font-size: 1.35rem; font-weight:700; letter-spacing:-0.02em; color:var(--text-main);">
                    Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                </h2>
                <span class="badge py-2 px-3" style="background:<?= $stageInfo['color'] ?>;font-size:0.78rem;border-radius:20px;">
                    <i class="<?= $stageInfo['icon'] ?> me-1"></i> <?= $stageInfo['label'] ?>
                </span>
                <?php if($isDelayed): ?>
                <span class="badge bg-danger py-1 px-2" style="font-size:0.7rem;border-radius:20px;animation:pulse-danger 2s infinite;">
                    <i class="fas fa-exclamation-triangle me-1"></i> ATRASADO +<?= $hoursInStage - $stageGoal ?>h
                </span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3 mt-1" style="font-size: 0.72rem;">
                <span class="text-muted">
                    <i class="fas fa-calendar-alt me-1"></i>Criado em <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </span>
                <span class="<?= $isDelayed ? 'text-danger fw-bold' : 'text-muted' ?>">
                    <i class="fas fa-clock me-1"></i>
                    <?= ($hoursInStage >= 24) ? floor($hoursInStage/24).'d '.($hoursInStage%24).'h' : $hoursInStage.'h' ?>
                    na etapa
                </span>
                <?php if (!empty($order['customer_name'])): ?>
                <span class="text-muted">
                    <i class="fas fa-user me-1"></i><?= e($order['customer_name']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
            <?php if (!$isReadOnly): ?>
            <div class="btn-group btn-group-sm" role="group">
                <a href="?page=orders&action=edit&id=<?= $order['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i> Editar Pedido</a>
                <button type="submit" form="formPipelineDetail" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ Progress Bar do Pipeline — Modern Stepper ═══ -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--radius-lg);">
        <div class="card-body p-3" style="overflow-x:auto;scrollbar-width:thin;">
            <?php 
            $stageKeys = array_keys($stages);
            $currentIdx = array_search($currentStage, $stageKeys);
            $totalStages = count($stageKeys);
            $progressPct = $totalStages > 1 ? round(($currentIdx / ($totalStages - 1)) * 100) : 0;
            ?>
            <div class="pipeline-progress d-flex align-items-center justify-content-between position-relative" style="z-index:1;">
                <?php foreach ($stages as $sKey => $sInfo):
                    $sIdx = array_search($sKey, $stageKeys);
                    $isCompleted = $sIdx < $currentIdx;
                    $isCurrent = $sKey === $currentStage;
                    $isFuture = $sIdx > $currentIdx;
                    $stepClass = $isCompleted ? 'step-completed' : ($isCurrent ? 'step-current' : 'step-future');
                ?>
                <div class="pipeline-step <?= $stepClass ?> text-center flex-fill position-relative">
                    <div class="pipeline-step-icon mx-auto rounded-circle d-flex align-items-center justify-content-center"
                        style="width:44px; height:44px; font-size:0.85rem;
                        background: <?= $isCompleted ? $sInfo['color'] : ($isCurrent ? '#fff' : 'var(--bg-body)') ?>;
                        color: <?= $isCompleted ? '#fff' : ($isCurrent ? $sInfo['color'] : 'var(--secondary-color)') ?>;
                        border: <?= $isCurrent ? '3px solid ' . $sInfo['color'] : ($isCompleted ? 'none' : '2px solid var(--border-color)') ?>;
                        transition: all 0.3s ease;">
                        <i class="<?= $isCompleted ? 'fas fa-check' : $sInfo['icon'] ?>"></i>
                    </div>
                    <div class="pipeline-step-label <?= $isCurrent ? 'fw-bold' : ($isFuture ? 'text-muted' : '') ?>" style="font-size:0.68rem;color:<?= $isCompleted ? $sInfo['color'] : ($isCurrent ? $sInfo['color'] : '') ?>;margin-top:6px;">
                        <?= $sInfo['label'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ═══ Ações rápidas de movimentação — Redesigned ═══ -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--radius-lg);">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-center flex-wrap gap-2">
                
                <?php if (!$isReadOnly): ?>
                <!-- Botão retroceder -->
                <?php if ($currentIdx > 0): ?>
                <?php $prevStage = $stageKeys[$currentIdx - 1]; $prevBlocked = $hasPaidInstallments && in_array($prevStage, $stagesBlockedByPaid); ?>
                <a href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=<?= $prevStage ?>" 
                   class="btn btn-sm <?= $prevBlocked ? 'btn-outline-danger' : 'btn-outline-secondary' ?> btn-move-stage" 
                   style="border-radius:var(--radius-sm);font-size:0.78rem;"
                   data-dir="Retroceder" data-stage="<?= $stages[$prevStage]['label'] ?>"
                   data-target-stage="<?= $prevStage ?>"
                   data-order-id="<?= $order['id'] ?>"
                   <?php if ($prevBlocked): ?>title="Bloqueado — Existem parcelas pagas"<?php endif; ?>>
                    <?php if ($prevBlocked): ?><i class="fas fa-lock me-1"></i><?php else: ?><i class="fas fa-arrow-left me-1"></i><?php endif; ?>
                    <?= $stages[$prevStage]['label'] ?>
                </a>
                <?php endif; ?>
                <?php endif;?>

                <!-- Mover para qualquer etapa -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border-radius:var(--radius-sm);font-size:0.78rem;">
                        <i class="fas fa-random me-1"></i> Mover para...
                    </button>
                    <ul class="dropdown-menu shadow-lg" style="border-radius:var(--radius-md);border:1px solid var(--border-color);">
                        <?php foreach ($stages as $sKey => $sInfo): ?>
                        <?php if ($sKey !== $currentStage): ?>
                        <?php $isBlockedByPaid = $hasPaidInstallments && in_array($sKey, $stagesBlockedByPaid); ?>
                        <li>
                            <a class="dropdown-item btn-move-stage <?= $isBlockedByPaid ? 'text-danger' : '' ?>" 
                               href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=<?= $sKey ?>" 
                               data-dir="Mover" data-stage="<?= $sInfo['label'] ?>"
                               data-target-stage="<?= $sKey ?>"
                               data-order-id="<?= $order['id'] ?>"
                               style="font-size:0.82rem;padding:8px 16px;">
                                <?php if ($isBlockedByPaid): ?>
                                <i class="fas fa-lock me-2 text-danger"></i> <span class="text-decoration-line-through"><?= $sInfo['label'] ?></span>
                                <small class="text-danger ms-1" style="font-size:0.65rem;"><i class="fas fa-info-circle"></i> parcelas pagas</small>
                                <?php else: ?>
                                <i class="<?= $sInfo['icon'] ?> me-2" style="color:<?= $sInfo['color'] ?>;"></i> <?= $sInfo['label'] ?>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <?php if (!$isReadOnly): ?>
                <!-- Botão avançar (principal) -->
                <?php if ($currentIdx < $totalStages - 1): ?>
                <a href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=<?= $stageKeys[$currentIdx + 1] ?>" 
                   class="btn btn-sm btn-success btn-move-stage px-3" 
                   style="border-radius:var(--radius-sm);font-size:0.78rem;font-weight:600;"
                   data-dir="Avançar" data-stage="<?= $stages[$stageKeys[$currentIdx + 1]]['label'] ?>"
                   data-target-stage="<?= $stageKeys[$currentIdx + 1] ?>"
                   data-order-id="<?= $order['id'] ?>">
                    <?= $stages[$stageKeys[$currentIdx + 1]]['label'] ?> <i class="fas fa-arrow-right ms-1"></i>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <?php if ($isReadOnly): ?>
    <div class="alert <?= $currentStage === 'cancelado' ? 'alert-danger' : 'alert-success' ?> d-flex align-items-center mb-4 shadow-sm" role="alert" style="border-left: 4px solid <?= $currentStage === 'cancelado' ? '#dc3545' : '#198754' ?> !important; border-radius: var(--radius-md);">
        <i class="fas <?= $currentStage === 'cancelado' ? 'fa-ban' : 'fa-check-double' ?> me-3 fs-4"></i>
        <div>
            <strong>Pedido <?= $currentStage === 'cancelado' ? 'Cancelado' : 'Concluído' ?>.</strong>
            Todos os campos estão em modo de visualização. Use o botão "Mover para..." para reabrir o pedido, se necessário.
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($order['quote_confirmed_at'])): ?>
    <div class="alert alert-success border-success d-flex align-items-center mb-4 shadow-sm" role="alert" style="border-left: 5px solid #198754 !important;">
        <div class="me-3">
            <i class="fas fa-clipboard-check fs-3 text-success"></i>
        </div>
        <div>
            <strong class="fs-6"><i class="fas fa-check-circle me-1"></i> Orçamento Aprovado pelo Cliente!</strong>
            <div class="small mt-1 text-muted">
                O cliente confirmou o orçamento em <strong><?= date('d/m/Y \à\s H:i', strtotime($order['quote_confirmed_at'])) ?></strong> através do link do catálogo.
                <?php if (!empty($order['quote_confirmed_ip'])): ?>
                    <br><i class="fas fa-globe me-1"></i>IP do dispositivo: <code><?= e($order['quote_confirmed_ip']) ?></code>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Coluna Esquerda: Informações e Formulário -->
        <div class="col-lg-8">
            <form method="POST" action="?page=pipeline&action=updateDetails" id="formPipelineDetail">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">

                <!-- Dados do Cliente -->
                <fieldset class="p-4 mb-4" style="border: 2px solid #6c757d; border-radius: 8px;">
                    <legend class="float-none w-auto px-2 fs-5" style="color: #6c757d;"><i class="fas fa-user-tag me-2"></i>Cliente</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nome</label>
                            <input type="text" class="form-control" value="<?= $order['customer_name'] ?? '—' ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Telefone</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= $order['customer_phone'] ?? '—' ?>" disabled>
                                <?php if (!empty($order['customer_phone'])): ?>
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $order['customer_phone']) ?>" target="_blank" class="btn btn-success btn-sm" title="WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">CPF/CNPJ</label>
                            <input type="text" class="form-control" value="<?= $order['customer_document'] ?? '—' ?>" disabled>
                        </div>
                        <?php if (!empty($order['customer_email'])): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">E-mail</label>
                            <input type="text" class="form-control" value="<?= e($order['customer_email']) ?>" disabled>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($customerFormattedAddress)): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Endereço</label>
                            <input type="text" class="form-control" value="<?= e($customerFormattedAddress) ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <?php
                // Mostrar seção de produtos quando o pedido está na etapa de orçamento ou venda
                // Mas NÃO mostrar na etapa "producao" (onde exibimos o controle de setores)
                // Nem na etapa "preparacao" (onde exibimos o controle de preparo)
                // Nem na etapa "envio" (onde focamos no card de envio/entrega)
                // Nem na etapa "financeiro" (onde focamos no card financeiro completo)
                // Em modo read-only (concluido/cancelado), mostrar sempre
                $showProducts = $isReadOnly || !in_array($currentStage, ['contato', 'producao', 'preparacao', 'envio', 'financeiro']);
                ?>

                <?php if ($showProducts): ?>
                <!-- Produtos do Orçamento -->
                <fieldset class="p-4 mb-4" style="border: 2px solid #9b59b6; border-radius: 8px;">
                    <legend class="float-none w-auto px-2 fs-5" style="color: #9b59b6;">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Produtos do Orçamento
                        <?php if (!$isReadOnly): ?>
                        <a href="?page=orders&action=printQuote&id=<?= $order['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success ms-3">
                            <i class="fas fa-print me-1"></i> Imprimir Orçamento
                        </a>
                        <?php endif; ?>
                    </legend>

                    <!-- ═══ Link de Catálogo para o Cliente ═══ -->
                    <?php if ($currentStage === 'orcamento' && !$isReadOnly): ?>
                    <div class="card border-0 shadow-sm mb-3" id="catalogLinkSection">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #17a2b810 0%, #0dcaf015 100%);">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="mb-0" style="font-size:0.85rem; color:#17a2b8;"><i class="fas fa-share-alt me-2"></i>Catálogo do Cliente</h6>
                                <span class="badge" style="font-size:0.6rem; background:#17a2b820; color:#17a2b8;" id="catalogHeaderBadge">
                                    <i class="fas fa-magic me-1"></i>O cliente monta a lista!
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-3">

                            <!-- ══ Estado 1: Sem link ativo — CTA + formulário ══ -->
                            <div id="catalogLinkForm">
                                <!-- Opções + botão gerar -->
                                <div class="row g-2 align-items-end mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted mb-1">Confirmação do cliente?</label>
                                        <select class="form-select form-select-sm" id="catalogRequireConfirmation">
                                            <option value="0" selected>🚫 Não — apenas montar lista</option>
                                            <option value="1">✅ Sim — aprovar orçamento</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted mb-1">Validade</label>
                                        <select class="form-select form-select-sm" id="catalogExpires">
                                            <option value="">Sem expiração</option>
                                            <option value="1">1 dia</option>
                                            <option value="3">3 dias</option>
                                            <option value="7" selected>7 dias</option>
                                            <option value="15">15 dias</option>
                                            <option value="30">30 dias</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-sm w-100 fw-bold shadow-sm" id="btnGenerateCatalog" onclick="generateCatalogLink()" style="background:#17a2b8; color:#fff; border-radius: 8px;">
                                            <i class="fas fa-magic me-1"></i> Gerar Link
                                        </button>
                                    </div>
                                </div>

                                <!-- CTA visual -->
                                <div class="text-center py-3 mb-3" style="background: linear-gradient(135deg, #e0f7fa 0%, #e8f8f5 100%); border-radius: 10px; border: 2px dashed #17a2b840;">
                                    <i class="fas fa-share-alt d-block mb-2" style="font-size: 2.2rem; color: #17a2b8; opacity: 0.6;"></i>
                                    <p class="mb-1 small text-muted" style="font-size: 0.78rem;">
                                        Gere um link exclusivo para o Pedido <strong>#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></strong>.
                                    </p>
                                    <p class="mb-0 small text-muted" style="font-size: 0.68rem;">
                                        <i class="fas fa-info-circle me-1"></i>O cliente navega pelos produtos e monta a lista. Os itens aparecem aqui em tempo real.
                                    </p>
                                    <div id="catalogLinkActive" style="display:none;">
                                        <div class="input-group input-group-sm mt-2 px-3">
                                            <span class="input-group-text" style="background: #f8f9fa; border-color: #17a2b840;">
                                                <i class="fas fa-link" style="font-size:0.7rem; color:#17a2b8;"></i>
                                            </span>
                                            <input type="text" class="form-control" id="catalogLinkUrl" readonly onclick="this.select()" style="font-size:0.75rem; border-color: #17a2b840; color: #17a2b8; font-weight: 600;">
                                            <button class="btn btn-outline-success btn-sm" type="button" onclick="copyCatalogLink()" title="Copiar link" style="border-color: #17a2b840;">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <a id="catalogLinkOpen" href="#" target="_blank" class="btn btn-outline-primary btn-sm" title="Abrir catálogo" style="border-color: #17a2b840;">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <button class="btn btn-outline-info btn-sm" type="button" onclick="shareViaWhatsApp()" title="Enviar via WhatsApp" style="border-color: #17a2b840;">
                                                <i class="fab fa-whatsapp"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="deactivateCatalogLink()">
                                            <i class="fas fa-ban me-1"></i>
                                        </button>
                                        </div>
                                        <b><small class="mb-0" style="font-size: 0.7rem; color: #6c757d;" id="catalogLinkPriceInfo"></small></b> - <small class="text-muted" style="font-size:0.65rem;" id="catalogLinkMeta"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card-header border-bottom p-2 mb-3" style="background: linear-gradient(135deg,rgba(0, 204, 0, 0.06) 0%,rgba(0, 204, 0, 0.08) 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0" style="font-size:0.85rem; color:#00cc00;"><i class="fa-solid fa-table-list me-2"></i>Lista de Produtos</h6>
                        </div>
                    </div>

                    <?php if (!$isReadOnly): ?>
                    <!-- Seletor de Tabela de Preços -->
                    <div class="alert alert-light border mb-3 py-2">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-tags me-1"></i>Tabela de Preços</label>
                                <select class="form-select form-select-sm" name="price_table_id" id="priceTableSelect">
                                    <option value="">Padrão do cliente</option>
                                    <?php foreach ($priceTables as $pt): ?>
                                    <option value="<?= $pt['id'] ?>" <?= ($currentPriceTableId == $pt['id']) ? 'selected' : '' ?>>
                                        <?= e($pt['name']) ?> <?= $pt['is_default'] ? '(Padrão)' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block mt-md-4">
                                    <i class="fas fa-info-circle me-1"></i>Ao mudar a tabela, os preços dos produtos serão atualizados automaticamente.
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?> <!-- /!$isReadOnly price table -->

                    <!-- Tabela de Itens Existentes -->
                    <?php 
                    $showItemDiscount = in_array($currentStage, ['orcamento', 'venda', 'financeiro']) && !$isReadOnly;
                    $showEditQty = in_array($currentStage, ['orcamento', 'venda']) && !$isReadOnly;
                    ?>
                    <?php if (!empty($orderItems)): ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center" style="width:100px;">Qtd</th>
                                    <th class="text-end" style="width:130px;">Preço Unit.</th>
                                    <th class="text-end" style="width:130px;">Subtotal</th>
                                    <?php if ($showItemDiscount): ?>
                                    <th class="text-end" style="width:140px;">Desconto</th>
                                    <th class="text-end" style="width:130px;">Líquido</th>
                                    <?php endif; ?>
                                    <?php if (!$isReadOnly): ?>
                                    <th class="text-center" style="width:80px;">Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalItems = 0; $totalDiscounts = 0; ?>
                                <?php foreach ($orderItems as $item): ?>
                                <?php 
                                    $subtotal = $item['quantity'] * $item['unit_price']; 
                                    $itemDiscount = (float)($item['discount'] ?? 0);
                                    $netAmount = $subtotal - $itemDiscount;
                                    $totalItems += $subtotal; 
                                    $totalDiscounts += $itemDiscount;
                                ?>
                                <tr data-item-id="<?= $item['id'] ?>">
                                    <td>
                                        <strong><?= e($item['product_name']) ?></strong>
                                        <?php if (!empty($item['combination_label'])): ?>
                                        <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= e($item['combination_label']) ?></small>
                                        <?php elseif (!empty($item['grade_description'])): ?>
                                        <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= e($item['grade_description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($showEditQty): ?>
                                        <input type="number" min="1" step="1"
                                               class="form-control form-control-sm text-center item-qty-input py-0" 
                                               data-item-id="<?= $item['id'] ?>" data-unit-price="<?= $item['unit_price'] ?>"
                                               value="<?= $item['quantity'] ?>"
                                               style="width:70px; margin:0 auto; font-size:0.8rem;"
                                               <?= $hasPaidInstallments ? 'disabled title="Bloqueado — parcelas pagas"' : '' ?>>
                                        <?php else: ?>
                                        <?= $item['quantity'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                    <td class="text-end fw-bold">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                    <?php if ($showItemDiscount): ?>
                                    <td class="text-end">
                                        <div class="input-group input-group-sm" style="width:130px; margin-left:auto;">
                                            <span class="input-group-text py-0 px-1" style="font-size:0.7rem;">R$</span>
                                            <input type="number" step="0.01" min="0" max="<?= $subtotal ?>" 
                                                   class="form-control form-control-sm text-end item-discount-input py-0" 
                                                   data-item-id="<?= $item['id'] ?>" data-subtotal="<?= $subtotal ?>"
                                                   value="<?= $itemDiscount > 0 ? number_format($itemDiscount, 2, '.', '') : '' ?>"
                                                   placeholder="0,00" style="font-size:0.8rem;"
                                                   <?= $hasPaidInstallments ? 'disabled title="Bloqueado — parcelas pagas"' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold item-net-amount <?= $itemDiscount > 0 ? 'text-success' : '' ?>">
                                        R$ <?= number_format($netAmount, 2, ',', '.') ?>
                                    </td>
                                    <?php endif; ?>
                                    <?php if (!$isReadOnly): ?>
                                    <td class="text-center">
                                        <?php if ($hasPaidInstallments): ?>
                                        <span class="btn btn-sm btn-outline-secondary disabled" title="Bloqueado — parcelas pagas">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <?php else: ?>
                                        <a href="?page=orders&action=deleteItem&item_id=<?= $item['id'] ?>&order_id=<?= $order['id'] ?>&redirect=pipeline" 
                                           class="btn btn-sm btn-outline-danger btn-delete-item" title="Remover item">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end fw-bold">Subtotal Produtos:</td>
                                    <td class="text-end fw-bold fs-5">R$ <?= number_format($totalItems, 2, ',', '.') ?></td>
                                    <?php if ($showItemDiscount): ?>
                                    <td class="text-end fw-bold text-danger" id="totalItemDiscounts">
                                        <?= $totalDiscounts > 0 ? '- R$ ' . number_format($totalDiscounts, 2, ',', '.') : '' ?>
                                    </td>
                                    <td class="text-end fw-bold fs-5 text-success" id="totalNetAmount">
                                        R$ <?= number_format($totalItems - $totalDiscounts, 2, ',', '.') ?>
                                    </td>
                                    <?php endif; ?>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-3" style="border-radius: 8px;">
                        <i class="fas fa-info-circle me-2"></i>Nenhum produto adicionado ao orçamento ainda.
                    </div>
                    <?php endif; ?>

                    <?php if (!$isReadOnly): ?>
                    <?php if ($hasPaidInstallments): ?>
                    <!-- Alerta: Produtos bloqueados por parcelas pagas -->
                    <div class="alert alert-warning py-2 px-3 mb-0 small" id="productsLockedAlert">
                        <i class="fas fa-lock me-2"></i><strong>Produtos bloqueados:</strong> Existem parcelas já pagas. 
                        Para adicionar, remover ou alterar produtos, estorne os pagamentos primeiro no módulo 
                        <a href="?page=financial&action=payments" target="_blank"><strong>Financeiro</strong></a>.
                    </div>
                    <?php else: ?>
                    <!-- Formulário Adicionar Item -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #0d6efd10 0%, #3498db15 100%);">
                            <h6 class="mb-0" style="font-size:0.85rem; color:#0d6efd;"><i class="fas fa-plus-circle me-2"></i>Adicionar Produto</h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- O form real é colocado via JS para evitar nesting -->
                            <div class="row g-2 align-items-end" id="addItemRowPipeline">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">Produto</label>
                                    <select class="form-select form-select-sm product-select" id="pipProductSelect" data-placeholder="Digite para buscar um produto...">
                                        <option value="">Selecione um produto...</option>
                                    </select>
                                    <!-- Seletor de variação (aparece dinamicamente) -->
                                    <div id="variationWrapPipeline" class="mt-1" style="display:none;">
                                        <select class="form-select form-select-sm" id="pipVariationSelect">
                                            <option value="">Selecione a variação...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Quantidade</label>
                                    <input type="number" min="1" class="form-control form-control-sm" id="pipQtyInput" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Preço Unitário</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" id="pipPriceInput">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddItemPipeline">
                                        <i class="fas fa-plus me-1"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?> <!-- /hasPaidInstallments else -->
                    <?php endif; ?> <!-- /!$isReadOnly add item form -->

                    <!-- Custos Extras do Orçamento -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #f39c1210 0%, #e67e2215 100%);">
                            <h6 class="mb-0" style="font-size:0.85rem; color:#e67e22;"><i class="fas fa-receipt me-2"></i>Custos Extras</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if (!empty($extraCosts)): ?>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Descrição</th>
                                            <th class="text-end" style="width:130px;">Valor</th>
                                            <?php if (!$isReadOnly): ?>
                                            <th class="text-center" style="width:80px;">Ações</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $totalExtras = 0; ?>
                                        <?php foreach ($extraCosts as $ec): ?>
                                        <?php $totalExtras += $ec['amount']; ?>
                                        <tr>
                                            <td><?= e($ec['description']) ?></td>
                                            <td class="text-end fw-bold <?= $ec['amount'] < 0 ? 'text-danger' : '' ?>">
                                                <?= $ec['amount'] < 0 ? '- R$ ' . number_format(abs($ec['amount']), 2, ',', '.') : 'R$ ' . number_format($ec['amount'], 2, ',', '.') ?>
                                            </td>
                                            <?php if (!$isReadOnly): ?>
                                            <td class="text-center">
                                                <?php if ($hasPaidInstallments): ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled" title="Bloqueado — parcelas pagas">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                                <?php else: ?>
                                                <a href="?page=pipeline&action=deleteExtraCost&cost_id=<?= $ec['id'] ?>&order_id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger btn-delete-extra" title="Remover custo">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-warning">
                                            <td class="text-end fw-bold">Total Custos Extras:</td>
                                            <td class="text-end fw-bold <?= $totalExtras < 0 ? 'text-danger' : '' ?>">
                                                <?= $totalExtras < 0 ? '- R$ ' . number_format(abs($totalExtras), 2, ',', '.') : 'R$ ' . number_format($totalExtras, 2, ',', '.') ?>
                                            </td>
                                            <?php if (!$isReadOnly): ?><td></td><?php endif; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php endif; ?>
                            <?php if (!$isReadOnly): ?>
                            <?php if ($hasPaidInstallments): ?>
                            <div class="alert alert-warning py-2 px-3 mb-0 small">
                                <i class="fas fa-lock me-1"></i>Custos extras bloqueados enquanto houver parcelas pagas.
                            </div>
                            <?php else: ?>
                            <!-- Form para adicionar custo extra -->
                            <div class="row g-2 align-items-end" id="addExtraCostRow">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Descrição do custo</label>
                                    <input type="text" class="form-control form-control-sm" id="extraDescription" placeholder="Ex: Frete, Arte, Desconto especial...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Valor (R$)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" id="extraAmount" placeholder="Use negativo p/ desconto">
                                    </div>
                                    <div class="form-text small" style="font-size:0.7rem;"><i class="fas fa-info-circle me-1"></i>Valor negativo = desconto</div>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-warning btn-sm w-100" id="btnAddExtraCost">
                                        <i class="fas fa-plus me-1"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Observações do Orçamento (aparece no orçamento impresso) -->
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-muted"><i class="fas fa-file-alt me-1"></i>Observações do Orçamento <small class="text-success">(aparece no orçamento impresso)</small></label>
                        <textarea class="form-control" name="quote_notes" rows="3" placeholder="Notas visíveis ao cliente no orçamento impresso..." <?= $isReadOnly ? 'disabled' : '' ?>><?= $order['quote_notes'] ?? '' ?></textarea>
                    </div>
                </fieldset>
                <?php else: ?>
                <!-- Manter valores atuais nos campos ocultos quando a seção de produtos não aparece -->
                <input type="hidden" name="quote_notes" value="<?= e($order['quote_notes'] ?? '') ?>">
                <input type="hidden" name="price_table_id" value="<?= $order['price_table_id'] ?? '' ?>">
                <?php endif; ?>

                <!-- ═══ ORDEM DE PRODUÇÃO / PREPARAÇÃO — Card para impressão ═══ -->
                <?php if (in_array($currentStage, ['producao', 'preparacao'])): ?>
                <?php
                    $isPreparationStage = ($currentStage === 'preparacao');
                    $orderCardTitle     = $isPreparationStage ? 'Ordem de Preparação' : 'Ordem de Produção';
                    $orderCardIcon      = $isPreparationStage ? 'fa-box-open' : 'fa-clipboard-list';
                    $orderCardColor     = $isPreparationStage ? '#27ae60' : '#e67e22';
                    $orderCardBgStart   = $isPreparationStage ? '#27ae6010' : '#e67e2210';
                    $orderCardBgEnd     = $isPreparationStage ? '#2ecc7115' : '#f39c1215';
                    $orderCardAreaBgStart = $isPreparationStage ? '#e8f5e9' : '#fff3e0';
                    $orderCardAreaBgEnd   = $isPreparationStage ? '#f1f8f2' : '#fef9f0';
                    $orderCardDescription = $isPreparationStage
                        ? 'Imprima a ordem de preparação com os detalhes do pedido, produtos e checklist de preparo.'
                        : 'Imprima a ordem de produção com os detalhes do pedido, produtos e setores.';
                    $orderCardSubtext = $isPreparationStage
                        ? 'Documento interno para acompanhamento da preparação e embalagem do pedido.'
                        : 'Documento interno para acompanhamento da produção no chão de fábrica.';
                ?>
                <div class="card border-0 shadow-sm mb-4" id="productionOrderSection">
                    <div class="card-header py-2" style="background: linear-gradient(135deg, <?= $orderCardBgStart ?> 0%, <?= $orderCardBgEnd ?> 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" style="font-size:0.85rem; color:<?= $orderCardColor ?>;">
                                <i class="fas <?= $orderCardIcon ?> me-2"></i><?= $orderCardTitle ?>
                            </h6>
                            <span class="badge" style="font-size:0.6rem; background:<?= $orderCardColor ?>20; color:<?= $orderCardColor ?>;">
                                <i class="fas fa-print me-1"></i>Impressão
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="text-center py-3" style="background: linear-gradient(135deg, <?= $orderCardAreaBgStart ?> 0%, <?= $orderCardAreaBgEnd ?> 100%); border-radius: 10px; border: 2px dashed <?= $orderCardColor ?>40;">
                            <i class="fas <?= $orderCardIcon ?> d-block mb-2" style="font-size: 2.2rem; color: <?= $orderCardColor ?>; opacity: 0.6;"></i>
                            <p class="mb-1 small text-muted" style="font-size: 0.78rem;">
                                <?= $orderCardDescription ?>
                            </p>
                            <p class="mb-3 small text-muted" style="font-size: 0.68rem;">
                                <i class="fas fa-info-circle me-1"></i><?= $orderCardSubtext ?>
                            </p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="?page=pipeline&action=printProductionOrder&id=<?= $order['id'] ?>" 
                                   target="_blank" class="btn px-4 shadow-sm" style="background:<?= $orderCardColor ?>; color:#fff; font-size: 0.95rem; border-radius: 10px;">
                                    <i class="fas fa-print me-2"></i> Imprimir Ordem
                                </a>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted" style="font-size: 0.65rem;">
                                    <i class="fas fa-box me-1"></i><?= count($orderItems ?? []) ?> produto(s)
                                    &nbsp;·&nbsp; Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($currentStage === 'producao' || ($isReadOnly && !empty($orderProductionSectors))): ?>

                <?php if (!empty($orderProductionSectors)): ?>
                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- ═══ CONTROLE DE SETORES DE PRODUÇÃO (POR PRODUTO) ═══ -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <?php
                    // Agrupar setores por order_item_id
                    $itemSectors = [];
                    foreach ($orderProductionSectors as $sec) {
                        $iid = $sec['order_item_id'];
                        if (!isset($itemSectors[$iid])) {
                            $itemSectors[$iid] = [
                                'product_name' => $sec['product_name'],
                                'product_id'   => $sec['product_id'],
                                'quantity'      => $sec['quantity'],
                                'sectors'       => [],
                            ];
                        }
                        $itemSectors[$iid]['sectors'][] = $sec;
                    }

                    // Filtrar itens: mostrar apenas se o usuário tem permissão para pelo menos 1 setor do item
                    $visibleItems = [];
                    foreach ($itemSectors as $iid => $itemData) {
                        $hasPermission = false;
                        foreach ($itemData['sectors'] as $sec) {
                            if (empty($userAllowedSectorIds) || in_array((int)$sec['sector_id'], $userAllowedSectorIds)) {
                                $hasPermission = true;
                                break;
                            }
                        }
                        if ($hasPermission) {
                            $visibleItems[$iid] = $itemData;
                        }
                    }

                    // Calcular progresso geral
                    $totalSteps = 0;
                    $completedSteps = 0;
                    foreach ($visibleItems as $itemData) {
                        foreach ($itemData['sectors'] as $sec) {
                            $totalSteps++;
                            if ($sec['status'] === 'concluido') $completedSteps++;
                        }
                    }
                    $progressPct = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
                ?>
                <fieldset class="p-4 mb-4" style="border: 2px solid #27ae60; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5 text-success">
                        <i class="fas fa-industry me-2"></i>Controle de Produção
                        <span class="badge bg-success bg-opacity-75 ms-2" style="font-size:0.7rem;"><?= $completedSteps ?>/<?= $totalSteps ?> setores</span>
                    </legend>

                    <!-- Barra de Progresso Geral -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-bold">Progresso Geral da Produção</small>
                            <small class="fw-bold <?= $progressPct == 100 ? 'text-success' : 'text-primary' ?>"><?= $progressPct ?>%</small>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 5px;">
                            <div class="progress-bar <?= $progressPct == 100 ? 'bg-success' : 'bg-primary' ?> progress-bar-striped <?= ($progressPct > 0 && $progressPct < 100) ? 'progress-bar-animated' : '' ?>" 
                                 role="progressbar" style="width: <?= $progressPct ?>%;"></div>
                        </div>
                        <?php if ($progressPct == 100): ?>
                        <div class="alert alert-success py-1 px-3 mt-2 mb-0 small">
                            <i class="fas fa-check-double me-1"></i> Todos os produtos passaram por todos os setores! O pedido pode avançar.
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tabela de Produtos com Stepper de Setores -->
                    <?php foreach ($visibleItems as $itemId => $itemData): 
                        $sectors = $itemData['sectors'];
                        $totalItemSectors = count($sectors);
                        $itemCompleted = 0;
                        $currentSector = null;
                        $currentSectorIdx = -1;
                        foreach ($sectors as $idx => $sec) {
                            if ($sec['status'] === 'concluido') {
                                $itemCompleted++;
                            }
                        }
                        // O setor atual é o primeiro pendente
                        foreach ($sectors as $idx => $sec) {
                            if ($sec['status'] === 'pendente') {
                                $currentSector = $sec;
                                $currentSectorIdx = $idx;
                                break;
                            }
                        }
                        $allDone = ($itemCompleted === $totalItemSectors);
                        $itemPct = $totalItemSectors > 0 ? round(($itemCompleted / $totalItemSectors) * 100) : 0;

                        // Permissão do usuário para o setor atual
                        $canActOnCurrent = false;
                        if ($currentSector) {
                            $canActOnCurrent = empty($userAllowedSectorIds) || in_array((int)$currentSector['sector_id'], $userAllowedSectorIds);
                        }
                    ?>
                    <div class="card border-0 shadow-sm mb-3 production-item-card <?= $allDone ? 'border-success' : '' ?>" data-item-id="<?= $itemId ?>">
                        <div class="card-body p-3">
                            <!-- Cabeçalho do Produto -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <?php if ($allDone): ?>
                                        <span class="badge bg-success rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-75 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.7rem;">
                                            <?= $itemCompleted ?>/<?= $totalItemSectors ?>
                                        </span>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= e($itemData['product_name']) ?></h6>
                                        <small class="text-muted">Qtd: <?= $itemData['quantity'] ?></small>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($allDone): ?>
                                        <span class="badge bg-success px-3 py-1"><i class="fas fa-check-double me-1"></i>Concluído</span>
                                    <?php elseif ($currentSector): ?>
                                        <span class="badge py-1 px-2" style="background:<?= $currentSector['color'] ?>;">
                                            <i class="<?= $currentSector['icon'] ?> me-1"></i><?= e($currentSector['sector_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-50 px-2 py-1"><i class="fas fa-pause me-1"></i>Aguardando</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Stepper Visual dos Setores -->
                            <div class="production-stepper d-flex align-items-center position-relative py-2 px-1">
                                <?php foreach ($sectors as $idx => $sec): 
                                    $isDone = ($sec['status'] === 'concluido');
                                    $isActive = ($sec['status'] === 'pendente' && $currentSector && $sec['sector_id'] === $currentSector['sector_id']);
                                    $isPending = ($sec['status'] === 'pendente' && !$isActive);
                                    $isFirst = ($idx === 0);
                                    $isLast = ($idx === $totalItemSectors - 1);

                                    // Cor do step
                                    if ($isDone) {
                                        $stepBg = '#27ae60';
                                        $stepColor = '#fff';
                                        $stepBorder = '#27ae60';
                                    } elseif ($isActive) {
                                        $stepBg = '#fff';
                                        $stepColor = $sec['color'];
                                        $stepBorder = $sec['color'];
                                    } else {
                                        $stepBg = '#f0f0f0';
                                        $stepColor = '#bbb';
                                        $stepBorder = '#ddd';
                                    }

                                    $userCanSector = empty($userAllowedSectorIds) || in_array((int)$sec['sector_id'], $userAllowedSectorIds);
                                ?>
                                <?php if (!$isFirst): ?>
                                <!-- Linha conectora -->
                                <div class="flex-grow-1" style="height:3px;background:<?= $isDone ? '#27ae60' : '#e0e0e0' ?>;min-width:12px;"></div>
                                <?php endif; ?>
                                <!-- Step -->
                                <div class="production-step text-center position-relative flex-shrink-0" 
                                     data-bs-toggle="tooltip" data-bs-placement="top"
                                     title="<?= e($sec['sector_name']) ?><?= $isDone && !empty($sec['completed_at']) ? ' — Concluído em '.date('d/m H:i', strtotime($sec['completed_at'])) : '' ?>">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto
                                        <?= $isActive ? 'sector-pulse' : '' ?>"
                                        style="width:36px;height:36px;font-size:0.8rem;
                                        background:<?= $stepBg ?>;color:<?= $stepColor ?>;
                                        border:2px solid <?= $stepBorder ?>;
                                        transition: all 0.3s;">
                                        <?php if ($isDone): ?>
                                            <i class="fas fa-check"></i>
                                        <?php else: ?>
                                            <i class="<?= $sec['icon'] ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small mt-1 <?= $isActive ? 'fw-bold' : ($isPending ? 'text-muted' : '') ?>" 
                                         style="font-size:0.65rem;max-width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
                                         color:<?= $isDone ? '#27ae60' : ($isActive ? $sec['color'] : '#999') ?>;">
                                        <?= e($sec['sector_name']) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Botão de Ação -->
                            <?php if (!$isReadOnly && !$allDone && $currentSector && $canActOnCurrent): ?>
                            <div class="mt-2 d-flex justify-content-between">
                                <div>
                                    <?php
                                    // Botão de retroceder: se há setor anterior concluído
                                    $revertSector = null;
                                    if ($currentSectorIdx > 0) {
                                        $prevSec = $sectors[$currentSectorIdx - 1];
                                        if ($prevSec['status'] === 'concluido') {
                                            $canRevertPrev = empty($userAllowedSectorIds) || in_array((int)$prevSec['sector_id'], $userAllowedSectorIds);
                                            if ($canRevertPrev) $revertSector = $prevSec;
                                        }
                                    }
                                    ?>
                                    <?php if ($revertSector): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning btn-sector-action"
                                            data-order-id="<?= $order['id'] ?>"
                                            data-item-id="<?= $itemId ?>"
                                            data-sector-id="<?= $revertSector['sector_id'] ?>"
                                            data-action="revert"
                                            data-sector-name="<?= e($revertSector['sector_name']) ?>">
                                        <i class="fas fa-undo me-1"></i> Retroceder
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-success btn-sector-action"
                                            data-order-id="<?= $order['id'] ?>"
                                            data-item-id="<?= $itemId ?>"
                                            data-sector-id="<?= $currentSector['sector_id'] ?>"
                                            data-action="advance"
                                            data-sector-name="<?= e($currentSector['sector_name']) ?>">
                                        <i class="fas fa-check me-1"></i> Concluir <strong><?= e($currentSector['sector_name']) ?></strong>
                                        <?php 
                                        $nextIdx = $currentSectorIdx + 1;
                                        if ($nextIdx < $totalItemSectors):
                                        ?>
                                        <span class="ms-1 opacity-75">→ <?= e($sectors[$nextIdx]['sector_name']) ?></span>
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                            <?php elseif (!$isReadOnly && $allDone): ?>
                            <!-- Produto concluído: permitir retroceder o último setor -->
                            <?php
                            $lastSec = end($sectors);
                            $canRevertLast = empty($userAllowedSectorIds) || in_array((int)$lastSec['sector_id'], $userAllowedSectorIds);
                            ?>
                            <?php if ($canRevertLast): ?>
                            <div class="mt-2 d-flex justify-content-start">
                                <button type="button" class="btn btn-sm btn-outline-warning btn-sector-action"
                                        data-order-id="<?= $order['id'] ?>"
                                        data-item-id="<?= $itemId ?>"
                                        data-sector-id="<?= $lastSec['sector_id'] ?>"
                                        data-action="revert"
                                        data-sector-name="<?= e($lastSec['sector_name']) ?>">
                                    <i class="fas fa-undo me-1"></i> Retroceder <strong><?= e($lastSec['sector_name']) ?></strong>
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </fieldset>
                <?php else: ?>
                <!-- Sem setores configurados para os produtos deste pedido -->
                <fieldset class="p-4 mb-4" style="border: 2px solid #e67e22; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5 text-warning">
                        <i class="fas fa-industry me-2"></i>Setores de Produção
                    </legend>
                    <?php if (empty($orderItems)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Este pedido não possui produtos.</strong>
                        <br><small class="text-muted">Adicione produtos ao pedido na etapa de Orçamento para que os setores de produção sejam configurados automaticamente.</small>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Nenhum setor de produção configurado</strong> para os produtos deste pedido.
                        <br><small class="text-muted">Configure os setores nos cadastros de Produtos, Subcategorias ou Categorias para que o controle de produção funcione.</small>
                    </div>
                    <?php endif; ?>
                </fieldset>
                <?php endif; ?>
                <?php endif; ?>

                <?php
                // ═══════════════════════════════════════════════════════════════
                // ═══ CARD DE DEDUÇÕES DE ESTOQUE — Exibido em "preparacao" ═══
                // ═══════════════════════════════════════════════════════════════
                if ($currentStage === 'preparacao' && !empty($activeDeductions)):
                    $warehouseName = $activeDeductions[0]['warehouse_name'] ?? 'N/D';
                ?>
                <fieldset class="p-4 mb-4" style="border: 2px solid #e67e22; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5" style="color: #e67e22;">
                        <i class="fas fa-warehouse me-2"></i>Estoque Deduzido
                        <span class="badge bg-opacity-75 ms-2" style="font-size:0.7rem;background:#e67e22;">
                            <?= count($activeDeductions) ?> item(ns)
                        </span>
                    </legend>
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <small>Os itens abaixo foram deduzidos do armazém <strong><?= e($warehouseName) ?></strong> ao entrar em preparação. Se o pedido for retrocedido, o estoque será automaticamente devolvido.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th>Variação</th>
                                    <th class="text-center">Qtd Deduzida</th>
                                    <th>Armazém</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeDeductions as $ded): ?>
                                <tr>
                                    <td><?= e($ded['product_name']) ?></td>
                                    <td><?= $ded['combination_label'] ? e($ded['combination_label']) : '<span class="text-muted">—</span>' ?></td>
                                    <td class="text-center fw-bold text-danger"><?= number_format($ded['quantity'], 0, ',', '.') ?></td>
                                    <td><i class="fas fa-warehouse me-1 text-muted"></i><?= e($ded['warehouse_name']) ?></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($ded['deducted_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </fieldset>
                <?php endif; ?>

                <?php
                // ═══════════════════════════════════════════════════════════
                // ═══ CARD DE PREPARO — Exibido na etapa "preparacao" ═══
                // ═══════════════════════════════════════════════════════════
                $showPreparo = ($currentStage === 'preparacao' && !$isReadOnly);
                $showPreparoReadOnly = ($isReadOnly && ($order['pipeline_stage'] ?? '') === 'preparacao');
                if ($showPreparo || $showPreparoReadOnly):
                    // Preparar checklist de preparo — carregado dinamicamente do banco (via controller)
                    $preparoChecklist = $orderPreparationChecklist ?? [];
                    // $preparoItems já é definido pelo controller com as etapas ativas do banco

                    $checkedCount = 0;
                    foreach ($preparoItems as $key => $item) {
                        $checkVal = $preparoChecklist[$key] ?? null;
                        if ($checkVal) $checkedCount++;
                    }
                    $totalPrepItems = count($preparoItems);
                    $prepPct = $totalPrepItems > 0 ? round(($checkedCount / $totalPrepItems) * 100) : 0;
                    $allPrepDone = ($checkedCount === $totalPrepItems);
                ?>
                <fieldset class="p-4 mb-4" style="border: 2px solid #1abc9c; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5" style="color: #1abc9c;">
                        <i class="fas fa-boxes-packing me-2"></i>Preparo do Pedido
                        <span class="badge bg-opacity-75 ms-2" style="font-size:0.7rem;background:#1abc9c;"><?= $checkedCount ?>/<?= $totalPrepItems ?> etapas</span>
                    </legend>

                    <!-- Barra de progresso do preparo -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-bold">Progresso do Preparo</small>
                            <small class="fw-bold <?= $allPrepDone ? 'text-success' : '' ?>" style="color:<?= !$allPrepDone ? '#1abc9c' : '' ?>;"><?= $prepPct ?>%</small>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 5px;">
                            <div class="progress-bar <?= $allPrepDone ? 'bg-success' : '' ?> progress-bar-striped <?= (!$allPrepDone && $prepPct > 0) ? 'progress-bar-animated' : '' ?>" 
                                 role="progressbar" style="width:<?= $prepPct ?>%;background:<?= !$allPrepDone ? '#1abc9c' : '' ?>;"></div>
                        </div>
                    </div>

                    <!-- Lista de itens do pedido (resumo) -->
                    <?php if (!empty($orderItems)): ?>
                    <div class="alert alert-light border py-2 px-3 mb-3">
                        <small class="fw-bold text-muted"><i class="fas fa-boxes-stacked me-1"></i>Produtos do Pedido:</small>
                        <div class="mt-1">
                            <?php foreach ($orderItems as $oi): ?>
                            <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:0.75rem;">
                                <i class="fas fa-box me-1 text-muted"></i><?= e($oi['product_name']) ?> 
                                <strong class="ms-1">×<?= $oi['quantity'] ?></strong>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Checklist de preparo -->
                    <div class="row g-2">
                        <?php foreach ($preparoItems as $key => $pItem): 
                            $isChecked = !empty($preparoChecklist[$key]);
                            $checkedBy = $preparoChecklist[$key . '_by'] ?? null;
                            $checkedAt = $preparoChecklist[$key . '_at'] ?? null;
                        ?>
                        <div class="col-md-6">
                            <div class="card border <?= $isChecked ? 'border-success bg-success ' : 'border-light' ?> h-100 prep-check-card" 
                                 data-key="<?= $key ?>" style="cursor:<?= $showPreparo ? 'pointer' : 'default' ?>;transition:all 0.2s;">
                                <div class="card-body p-2 d-flex align-items-start gap-2">
                                    <div class="flex-shrink-0 mt-1">
                                        <?php if ($isChecked): ?>
                                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-success" 
                                                  style="width:28px;height:28px;">
                                                <i class="fas fa-check text-white" style="font-size:0.7rem;"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="d-flex align-items-center justify-content-center rounded-circle border border-2" 
                                                  style="width:28px;height:28px;border-color:#ccc !important;">
                                                <i class="<?= $pItem['icon'] ?> text-muted" style="font-size:0.7rem;"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small <?= $isChecked ? 'text-success' : '' ?>"><?= $pItem['label'] ?></div>
                                        <div class="text-muted" style="font-size:0.7rem;"><?= $pItem['desc'] ?></div>
                                        <?php if ($isChecked && $checkedBy): ?>
                                        <div class="text-muted mt-1" style="font-size:0.6rem;">
                                            <i class="fas fa-user me-1"></i><?= e($checkedBy) ?>
                                            <?php if ($checkedAt): ?>
                                                · <?= date('d/m H:i', strtotime($checkedAt)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($allPrepDone): ?>
                    <div class="alert alert-success py-2 px-3 mt-3 mb-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fas fa-check-double me-2"></i>
                                <strong>Preparo concluído!</strong> O pedido está pronto para avançar para Envio/Entrega.
                            </div>
                            <a href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=envio" 
                               class="btn btn-sm btn-success btn-move-stage" data-dir="Avançar" data-stage="Envio/Entrega"
                               data-target-stage="envio" data-order-id="<?= $order['id'] ?>">
                                <i class="fas fa-truck me-1"></i> Avançar para Envio
                            </a>
                        </div>
                    </div>
                    <?php elseif ($showPreparo): ?>
                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <small>Conclua todas as etapas do preparo antes de avançar o pedido. Clique em cada item para confirmar.</small>
                    </div>
                    <?php endif; ?>
                </fieldset>
                <?php endif; ?>

                <!-- Gerenciamento do Pedido -->
                <fieldset class="p-4 mb-4" style="border: 2px solid #3498db; border-radius: 8px;">
                    <legend class="float-none w-auto px-2 fs-5" style="color: #3498db;"><i class="fas fa-sliders-h me-2"></i>Gerenciamento</legend>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Prioridade</label>
                            <select class="form-select" name="priority" <?= $isReadOnly ? 'disabled' : '' ?>>
                                <option value="baixa" <?= ($order['priority'] ?? '') == 'baixa' ? 'selected' : '' ?>>🟢 Baixa</option>
                                <option value="normal" <?= ($order['priority'] ?? 'normal') == 'normal' ? 'selected' : '' ?>>🔵 Normal</option>
                                <option value="alta" <?= ($order['priority'] ?? '') == 'alta' ? 'selected' : '' ?>>🟡 Alta</option>
                                <option value="urgente" <?= ($order['priority'] ?? '') == 'urgente' ? 'selected' : '' ?>>🔴 Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Prazo (Deadline)</label>
                            <input type="date" class="form-control" name="deadline" value="<?= $order['deadline'] ?? '' ?>" <?= $isReadOnly ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Responsável</label>
                            <select class="form-select" name="assigned_to" <?= $isReadOnly ? 'disabled' : '' ?>>
                                <option value="">Sem responsável</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($order['assigned_to'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                    <?= $u['name'] ?> (<?= $u['role'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-lock me-1"></i>Observações Internas 
                                <small class="text-danger">(NÃO aparece no orçamento impresso)</small>
                            </label>
                            <textarea class="form-control" name="internal_notes" rows="3" placeholder="Notas internas sobre este pedido..." <?= $isReadOnly ? 'disabled' : '' ?>><?= $order['internal_notes'] ?? '' ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <?php
                // Campos de Envio/Entrega: só aparecem nas etapas de envio ou concluído (NÃO em preparação)
                // Em modo read-only (concluido/cancelado), mostrar sempre
                $showShipping = $isReadOnly || in_array($currentStage, ['envio', 'concluido']);
                // Campos Financeiro: só aparecem nas etapas venda, financeiro ou concluído
                $showFinancial = $isReadOnly || in_array($currentStage, ['venda', 'financeiro', 'concluido']);
                ?>

                <?php if ($showFinancial): ?>

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- ═══ NOTA DE PEDIDO — Impressão nas etapas venda/financeiro ═══ -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <?php if (in_array($currentStage, ['venda', 'financeiro'])): ?>
                <div class="card border-0 shadow-sm mb-4" id="notaPedidoSection">
                    <div class="card-header py-2" style="background: linear-gradient(135deg, #27ae6010 0%, #2ecc7115 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" style="font-size:0.85rem; color:#27ae60;">
                                <i class="fas fa-file-invoice me-2"></i>Nota de Pedido
                            </h6>
                            <span class="badge" style="font-size:0.6rem; background:#27ae6020; color:#27ae60;">
                                <i class="fas fa-print me-1"></i>Documento para impressão
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="text-center py-3" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-radius: 10px; border: 2px dashed #27ae6040;">
                            <i class="fas fa-file-invoice d-block mb-2" style="font-size: 2.2rem; color: #27ae60; opacity: 0.6;"></i>
                            <p class="mb-1 small text-muted" style="font-size: 0.78rem;">
                                Gere a Nota de Pedido com dados do cliente, produtos, valores e pagamento.
                            </p>
                            <p class="mb-3 small text-muted" style="font-size: 0.68rem;">
                                <i class="fas fa-info-circle me-1"></i>Ideal como comprovante ao cliente. <strong>Dados serão salvos automaticamente.</strong>
                            </p>
                            <button type="submit" name="print_order_after_save" value="1"
                                    class="btn px-4 shadow-sm" style="background:#27ae60; color:#fff; font-size: 0.95rem; border-radius: 10px;">
                                <i class="fas fa-print me-2"></i> Imprimir Nota de Pedido
                            </button>
                            <div class="mt-2">
                                <small class="text-muted" style="font-size: 0.65rem;">
                                    <i class="fas fa-coins me-1"></i>Total: <strong>R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></strong>
                                    &nbsp;·&nbsp; <?= count($orderItems ?? []) ?> produto(s)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- ═══ FINANCEIRO — Card completo (reformulado) ═══ -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <?php
                    $isFinanceiroStage = ($currentStage === 'financeiro');
                    $finBorderColor = $isFinanceiroStage ? '#f39c12' : '#dee2e6';

                    // Carregar gateways ativos para a seção de links de pagamento
                    $__gwModel = new \Akti\Models\PaymentGateway((new \Database())->getConnection());
                    $__activeGateways = $__gwModel->getActive();
                    $__hasActiveGateway = !empty($__activeGateways);

                    // Construir mapa de métodos suportados por gateway para uso no JS
                    $__gwMethodsMap = [];
                    foreach ($__activeGateways as $__gw) {
                        try {
                            $__gwInst = \Akti\Gateways\GatewayManager::make($__gw['gateway_slug']);
                            $__gwMethodsMap[$__gw['gateway_slug']] = $__gwInst->getSupportedMethods();
                        } catch (\Exception $e) {
                            $__gwMethodsMap[$__gw['gateway_slug']] = ['auto', 'pix', 'credit_card', 'boleto'];
                        }
                    }

                    // Link de pagamento salvo
                    $savedPaymentLink = $order['payment_link_url'] ?? '';
                    $savedPaymentGateway = $order['payment_link_gateway'] ?? '';
                    $savedPaymentMethod = $order['payment_link_method'] ?? '';
                    $savedPaymentLinkAt = $order['payment_link_created_at'] ?? '';
                ?>
                <fieldset class="p-4 mb-4" style="border: 2px solid <?= $finBorderColor ?>; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5" style="color: #f39c12;">
                        <i class="fas fa-coins me-2"></i>Financeiro
                        <?php if ($isFinanceiroStage): ?>
                        <span class="badge ms-2" style="font-size:0.7rem;background:#f39c12;color:#fff;">
                            <i class="fas fa-money-bill-wave me-1"></i>Etapa Atual
                        </span>
                        <?php endif; ?>
                    </legend>

                    <?php if ($isFinanceiroStage && !empty($orderItems)): ?>
                    <!-- ── Resumo dos produtos com desconto por item ── -->
                    <div class="alert alert-light border py-2 px-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="fw-bold text-muted"><i class="fas fa-boxes-stacked me-1"></i>Produtos do Pedido</small>
                            <span class="badge bg-secondary"><?= count($orderItems) ?> item(ns)</span>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-borderless mb-0" style="font-size:0.8rem;">
                                <thead>
                                    <tr class="text-muted" style="font-size:0.7rem;">
                                        <th>Produto</th>
                                        <th class="text-center">Qtd</th>
                                        <th class="text-end">Subtotal</th>
                                        <?php if (!$isReadOnly): ?>
                                        <th class="text-end" style="width:130px;">Desconto</th>
                                        <?php endif; ?>
                                        <th class="text-end">Líquido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $finTotalItems = 0; $finTotalDiscounts = 0; ?>
                                    <?php foreach ($orderItems as $oi): ?>
                                    <?php 
                                        $oiSub = $oi['quantity'] * $oi['unit_price'];
                                        $oiDisc = (float)($oi['discount'] ?? 0);
                                        $oiNet = $oiSub - $oiDisc;
                                        $finTotalItems += $oiSub;
                                        $finTotalDiscounts += $oiDisc;
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-box me-1 text-muted"></i><?= e($oi['product_name']) ?>
                                            <?php if (!empty($oi['combination_label'])): ?>
                                            <small class="text-info ms-1"><?= e($oi['combination_label']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $oi['quantity'] ?></td>
                                        <td class="text-end">R$ <?= number_format($oiSub, 2, ',', '.') ?></td>
                                        <?php if (!$isReadOnly): ?>
                                        <td class="text-end">
                                            <div class="input-group input-group-sm" style="width:120px; margin-left:auto;">
                                                <span class="input-group-text py-0 px-1" style="font-size:0.65rem;">R$</span>
                                                <input type="number" step="0.01" min="0" max="<?= $oiSub ?>" 
                                                       class="form-control form-control-sm text-end item-discount-input py-0" 
                                                       data-item-id="<?= $oi['id'] ?>" data-subtotal="<?= $oiSub ?>"
                                                       value="<?= $oiDisc > 0 ? number_format($oiDisc, 2, '.', '') : '' ?>"
                                                       placeholder="0,00" style="font-size:0.75rem;">
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                        <td class="text-end fw-bold item-net-amount <?= $oiDisc > 0 ? 'text-success' : '' ?>">
                                            R$ <?= number_format($oiNet, 2, ',', '.') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <?php if ($finTotalDiscounts > 0): ?>
                            <small class="text-danger"><i class="fas fa-tag me-1"></i>Descontos: - R$ <?= number_format($finTotalDiscounts, 2, ',', '.') ?></small>
                            <?php else: ?>
                            <small>&nbsp;</small>
                            <?php endif; ?>
                            <strong class="text-success fin-order-total" style="font-size:0.85rem;">
                                <i class="fas fa-coins me-1"></i>Total: R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <input type="hidden" name="discount" id="finDiscount" value="<?= $order['discount'] ?? 0 ?>">

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- ══ STEP 1 — Forma de Pagamento (sempre visível) ══ -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #f39c1215 0%, #e67e2210 100%);">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="mb-0" style="font-size:0.85rem; color:#e67e22;">
                                    <i class="fas fa-wallet me-2"></i>Forma de Pagamento
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold fs-5" style="color:#e67e22;" id="finTotalDisplay">
                                        R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <!-- Seletor visual de forma de pagamento -->
                            <div class="row g-2 mb-3" id="paymentMethodCards">
                                <?php 
                                $payMethods = [
                                    'dinheiro'        => ['label' => 'Dinheiro',     'icon' => 'fas fa-money-bill-wave', 'color' => '#27ae60', 'emoji' => '💵'],
                                    'pix'             => ['label' => 'PIX',          'icon' => 'fas fa-qrcode',          'color' => '#00b4a0', 'emoji' => '📱'],
                                    'cartao_credito'  => ['label' => 'Crédito',      'icon' => 'fas fa-credit-card',     'color' => '#3498db', 'emoji' => '💳'],
                                    'cartao_debito'   => ['label' => 'Débito',       'icon' => 'fas fa-credit-card',     'color' => '#8e44ad', 'emoji' => '💳'],
                                    'boleto'          => ['label' => 'Boleto',       'icon' => 'fas fa-barcode',         'color' => '#e67e22', 'emoji' => '📄'],
                                    'transferencia'   => ['label' => 'Transferência','icon' => 'fas fa-university',      'color' => '#2c3e50', 'emoji' => '🏦'],
                                    'gateway'         => ['label' => 'Gateway Online','icon' => 'fas fa-globe',          'color' => '#9b59b6', 'emoji' => '🌐'],
                                ];
                                $selectedMethod = $order['payment_method'] ?? '';
                                ?>
                                <?php foreach ($payMethods as $pmKey => $pm): ?>
                                <div class="col-6 col-sm-4 col-lg">
                                    <div class="card h-100 text-center payment-method-card <?= $selectedMethod === $pmKey ? 'border-2 shadow' : 'border' ?>" 
                                         role="button" data-method="<?= $pmKey ?>"
                                         style="cursor:<?= $isReadOnly ? 'default' : 'pointer' ?>; border-color:<?= $selectedMethod === $pmKey ? $pm['color'] : '#dee2e6' ?>; transition:all 0.2s; border-radius: 10px;">
                                        <div class="card-body p-2">
                                            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-1"
                                                 style="width:38px; height:38px; background:<?= $selectedMethod === $pmKey ? $pm['color'] : '#f8f9fa' ?>; color:<?= $selectedMethod === $pmKey ? '#fff' : $pm['color'] ?>; transition:all 0.2s;">
                                                <i class="<?= $pm['icon'] ?>" style="font-size:0.95rem;"></i>
                                            </div>
                                            <div class="small fw-bold" style="font-size:0.72rem; color:<?= $selectedMethod === $pmKey ? $pm['color'] : '#6c757d' ?>;">
                                                <?= $pm['label'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <select class="form-select d-none" name="payment_method" id="finPaymentMethod" <?= $isReadOnly ? 'disabled' : '' ?>>
                                <option value="">Selecione...</option>
                                <?php foreach ($payMethods as $pmKey => $pm): ?>
                                <option value="<?= $pmKey ?>" <?= $selectedMethod === $pmKey ? 'selected' : '' ?>><?= $pm['emoji'] ?> <?= $pm['label'] ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- ═══ Status + Entrada (aparece após selecionar forma de pagamento) ═══ -->
                            <div id="finPaymentDetails" style="<?= empty($selectedMethod) ? 'display:none;' : '' ?>">
                                <hr class="my-3" style="border-color:#f39c12; opacity:0.2;">

                                <!-- Aviso quando modo Gateway Online está selecionado -->
                                <div class="alert alert-info py-2 px-3 mb-3" id="finGatewayModeAlert" style="border-radius:8px; <?= ($selectedMethod !== 'gateway') ? 'display:none;' : '' ?>">
                                    <i class="fas fa-globe me-2 text-primary"></i>
                                    <strong>Pagamento via Gateway Online:</strong>
                                    <span class="small">O status e os valores serão controlados automaticamente pelo gateway de pagamento. Gere o link abaixo e envie ao cliente.</span>
                                </div>

                                <div class="row g-3" id="finManualPaymentFields" style="<?= ($selectedMethod === 'gateway') ? 'display:none;' : '' ?>">
                                    <!-- Status do Pagamento -->
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted"><i class="fas fa-flag me-1"></i>Status do Pagamento</label>
                                        <?php
                                        $payStatusInfo = [
                                            'pendente' => ['color' => '#f39c12', 'bg' => '#fff3cd', 'icon' => 'fas fa-clock',       'label' => 'Pendente'],
                                            'parcial'  => ['color' => '#3498db', 'bg' => '#cfe2ff', 'icon' => 'fas fa-adjust',      'label' => 'Parcial'],
                                            'pago'     => ['color' => '#198754', 'bg' => '#d1e7dd', 'icon' => 'fas fa-check-circle', 'label' => 'Pago'],
                                        ];
                                        $curStatus = $order['payment_status'] ?? 'pendente';
                                        $csInfo = $payStatusInfo[$curStatus] ?? $payStatusInfo['pendente'];
                                        ?>
                                        <select class="form-select" name="payment_status" id="finPaymentStatus" <?= $isReadOnly ? 'disabled' : '' ?>
                                                style="border-color:<?= $csInfo['color'] ?>; background-color:<?= $csInfo['bg'] ?>;">
                                            <option value="pendente" <?= $curStatus == 'pendente' ? 'selected' : '' ?>>⏳ Pendente</option>
                                            <option value="parcial" <?= $curStatus == 'parcial' ? 'selected' : '' ?>>💳 Parcial</option>
                                            <option value="pago" <?= $curStatus == 'pago' ? 'selected' : '' ?>>✅ Pago</option>
                                        </select>
                                    </div>

                                    <!-- Entrada (Sinal) -->
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted"><i class="fas fa-hand-holding-usd me-1"></i>Entrada / Sinal (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="down_payment" id="finDownPayment"
                                                   value="<?= $order['down_payment'] ?? '0' ?>" 
                                                   placeholder="0,00"
                                                   <?= $isReadOnly ? 'disabled' : '' ?>>
                                        </div>
                                        <small class="text-muted" style="font-size:0.63rem;">Gera uma parcela aberta no financeiro.</small>
                                    </div>

                                    <!-- Info restante -->
                                    <div class="col-md-4 d-flex align-items-center">
                                        <div class="alert alert-info py-2 px-3 mb-0 small w-100" id="downPaymentInfo" style="display:none; border-radius:8px;">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <span id="downPaymentInfoText"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- ══ STEP 2 — Parcelamento (condicional)         ══ -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <div class="card mb-3 border-0 shadow-sm" id="installmentRow" style="display:none;">
                        <div class="card-header py-2" style="background:linear-gradient(135deg, #f39c1210 0%, #e67e2208 100%);">
                            <h6 class="mb-0" style="font-size:0.85rem; color:#e67e22;" id="installmentCardTitle">
                                <i class="fas fa-calculator me-2"></i><span id="installmentCardTitleText">Parcelamento</span>
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Nº de Parcelas</label>
                                    <select class="form-select" name="installments" id="finInstallments" <?= $isReadOnly ? 'disabled' : '' ?>>
                                        <option value="">À vista</option>
                                        <?php for ($i = 2; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($order['installments'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?>x</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Valor por Parcela</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control fw-bold" id="finInstallmentValue" name="installment_value_display" disabled
                                               value="<?= ($order['installment_value'] ?? 0) > 0 ? number_format($order['installment_value'], 2, ',', '.') : '' ?>">
                                        <input type="hidden" name="installment_value" id="finInstallmentValueHidden" value="<?= $order['installment_value'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="alert alert-info py-1 px-3 mb-0 small w-100" id="installmentInfo" style="display:none; border-radius:8px;">
                                        <i class="fas fa-calculator me-1"></i>
                                        <span id="installmentInfoText"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabela de parcelas detalhada (para boleto) -->
                            <div id="boletoInstallmentTable" class="mt-3" style="display:none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 small fw-bold text-muted"><i class="fas fa-list-ol me-1"></i>Detalhamento das Parcelas</h6>
                                    <?php if ($canUseBoletoModule): ?>
                                    <button type="button" class="btn btn-sm btn-outline-dark" id="btnPrintBoletos" style="font-size:0.7rem;">
                                        <i class="fas fa-print me-1"></i> Imprimir Boletos
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:0.7rem;"
                                            onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('boleto') ?>">
                                        <i class="fas fa-print me-1"></i> Imprimir Boletos <i class="fas fa-lock ms-1" style="font-size:0.6rem;"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" id="boletoTableBody">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:50px;">#</th>
                                                <th>Vencimento</th>
                                                <th class="text-end">Valor (R$)</th>
                                                <th class="text-center" style="width:100px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <small class="text-muted" style="font-size:0.65rem;">
                                    <i class="fas fa-info-circle me-1"></i>Os vencimentos são gerados a cada 30 dias a partir de hoje. Edite as datas conforme necessário.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- ══ STEP 3 — Link de Pagamento (Gateway)        ══ -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <div class="card mb-3 border-0 shadow-sm" id="paymentLinksSection" style="<?= ($selectedMethod !== 'gateway') ? 'display:none;' : '' ?>">
                        <div class="card-header py-2" style="background:linear-gradient(135deg, #9b59b610 0%, #8e44ad08 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" style="font-size:0.85rem; color:#8e44ad;">
                                    <i class="fas fa-link me-2"></i>Link de Pagamento
                                </h6>
                                <?php if (!empty($savedPaymentLink)): ?>
                                <span class="badge" style="font-size:0.6rem; background:#8e44ad20; color:#8e44ad;">
                                    <i class="fas fa-check-circle me-1"></i>Link ativo
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <?php if (!empty($savedPaymentLink)): ?>
                            <!-- ── Link já gerado — exibir de forma compacta ── -->
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success text-success border border-success border-opacity-25" style="font-size:0.65rem;">
                                    <i class="fas fa-check-circle me-1"></i><?= e(ucfirst($savedPaymentGateway)) ?>
                                    <?php if ($savedPaymentMethod && $savedPaymentMethod !== 'auto'): ?>
                                     · <?= e(strtoupper(str_replace('_', ' ', $savedPaymentMethod))) ?>
                                    <?php endif; ?>
                                </span>
                                <?php if ($savedPaymentLinkAt): ?>
                                <small class="text-muted" style="font-size:0.6rem;">
                                    <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($savedPaymentLinkAt)) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control text-primary fw-bold" id="savedGwLinkUrl" 
                                       value="<?= e($savedPaymentLink) ?>" readonly onclick="this.select()" style="font-size:0.78rem;">
                                <button type="button" class="btn btn-outline-secondary" id="btnCopySavedGwLink" title="Copiar link">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <a href="<?= e($savedPaymentLink) ?>" target="_blank" class="btn btn-outline-primary" title="Abrir link">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <?php if (!empty($order['customer_phone'])): ?>
                                <button type="button" class="btn btn-outline-success" id="btnResendGwWhatsApp" title="Reenviar via WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="payment_link_url" value="<?= e($savedPaymentLink) ?>">
                            <input type="hidden" name="payment_link_gateway" value="<?= e($savedPaymentGateway) ?>">
                            <input type="hidden" name="payment_link_method" value="<?= e($savedPaymentMethod) ?>">
                            <?php endif; ?>

                            <?php if ($isFinanceiroStage && !$isReadOnly): ?>
                                <?php if ($__hasActiveGateway): ?>
                                <!-- ── Geração de link compacta ── -->
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <select class="form-select form-select-sm" id="gwSelectGateway" style="max-width:180px;font-size:0.75rem;">
                                        <?php foreach ($__activeGateways as $__gw): ?>
                                        <option value="<?= htmlspecialchars($__gw['gateway_slug']) ?>"
                                                <?= !empty($__gw['is_default']) ? 'selected' : '' ?>
                                                data-methods="<?= htmlspecialchars(json_encode($__gwMethodsMap[$__gw['gateway_slug']] ?? [])) ?>">
                                            <?= htmlspecialchars($__gw['display_name']) ?>
                                            <?= $__gw['environment'] === 'sandbox' ? ' ⚠' : '' ?>
                                            <?= !empty($__gw['is_default']) ? ' ★' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Método: auto por padrão (cliente escolhe no checkout), com opção de forçar -->
                                    <input type="hidden" id="gwSelectMethod" value="auto">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleMethodSelect"
                                            title="Escolher método específico" style="font-size:0.7rem;padding:0.2rem 0.5rem;">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                    <select class="form-select form-select-sm d-none" id="gwSelectMethodVisible" style="max-width:200px;font-size:0.75rem;">
                                        <option value="auto" selected>🔄 Cliente Escolhe</option>
                                        <option value="pix">📱 PIX</option>
                                        <option value="credit_card">💳 Cartão de Crédito</option>
                                        <option value="debit_card">💳 Cartão de Débito</option>
                                        <option value="boleto">📄 Boleto</option>
                                    </select>

                                    <button type="button" class="btn btn-sm btn-primary" id="btnGenerateGwLink" style="font-size:0.75rem;">
                                        <i class="fas fa-bolt me-1"></i> <?= !empty($savedPaymentLink) ? 'Novo Link' : 'Gerar Link' ?>
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-2">
                                    <i class="fas fa-plug text-muted d-block mb-2" style="font-size:1.2rem;opacity:0.6;"></i>
                                    <p class="small text-muted mb-1">Nenhum gateway de pagamento ativo.</p>
                                    <a href="?page=payment_gateways" class="btn btn-sm btn-outline-primary" style="font-size:0.72rem;">
                                        <i class="fas fa-cog me-1"></i> Configurar Gateways
                                    </a>
                                </div>
                                <?php endif; ?>
                            <?php elseif (empty($savedPaymentLink)): ?>
                            <div class="text-center py-2">
                                <small class="text-muted" style="font-size:0.68rem;">
                                    <i class="fas fa-info-circle me-1"></i>Geração de links disponível na etapa Financeiro.
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Toast container para notificações de link de pagamento (push canto direito) -->
                    <div id="gwPaymentToastContainer" style="position:fixed;bottom:20px;right:20px;z-index:9999;max-width:380px;width:100%;pointer-events:none;"></div>

                    <!-- ═══ CUPOM NÃO FISCAL — Impressão Térmica ═══ -->
                    <div class="card mt-3 border-0 shadow-sm" id="thermalReceiptSection">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #8e44ad10 0%, #9b59b615 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" style="font-size:0.85rem; color:#8e44ad;">
                                    <i class="fas fa-receipt me-2"></i>Cupom Não Fiscal
                                </h6>
                                <span class="badge" style="font-size:0.6rem; background:#8e44ad20; color:#8e44ad;">
                                    <i class="fas fa-print me-1"></i>Impressora Térmica
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="text-center py-3" style="background: linear-gradient(135deg, #f3e5f5 0%, #f8f0fc 100%); border-radius: 10px; border: 2px dashed #8e44ad40;">
                                <i class="fas fa-receipt d-block mb-2" style="font-size: 2.2rem; color: #8e44ad; opacity: 0.6;"></i>
                                <p class="mb-1 small text-muted" style="font-size: 0.78rem;">
                                    Gere um cupom formatado para impressora térmica (80mm/58mm) com dados do pedido.
                                </p>
                                <p class="mb-3 small text-muted" style="font-size: 0.68rem;">
                                    <i class="fas fa-info-circle me-1"></i>O cupom <strong>não tem valor fiscal</strong> — use para controle interno ou comprovante ao cliente.
                                </p>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="?page=pipeline&action=printThermalReceipt&id=<?= $order['id'] ?>" 
                                       target="_blank" class="btn px-4 shadow-sm" style="background:#8e44ad; color:#fff; font-size: 0.95rem; border-radius: 10px;">
                                        <i class="fas fa-receipt me-2"></i> Imprimir Cupom
                                    </a>
                                    <a href="?page=pipeline&action=printThermalReceipt&id=<?= $order['id'] ?>&auto_print=1" 
                                       target="_blank" class="btn btn-outline-secondary px-3 shadow-sm" style="font-size: 0.85rem; border-radius: 10px;" title="Abre e imprime automaticamente">
                                        <i class="fas fa-bolt me-1"></i> Rápida
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted" style="font-size: 0.65rem;">
                                        <i class="fas fa-coins me-1"></i>Total: <strong>R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></strong>
                                        &nbsp;·&nbsp; <?= count($orderItems ?? []) ?> produto(s)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($canUseNfeModule): ?>
                    <?php
                        // ═══ NF-e — Carregar dados da nota vinculada ao pedido ═══
                        $nfeDoc = null;
                        $nfeStatusLabel = '';
                        $nfeStatusColor = 'secondary';
                        $nfeStatusIcon = 'fas fa-circle';
                        $hasNfe = false;
                        try {
                            $nfeDocModel = new \Akti\Models\NfeDocument((new \Database())->getConnection());
                            $nfeDoc = $nfeDocModel->readByOrder($order['id']);
                            if ($nfeDoc) {
                                $hasNfe = true;
                                $_nfeStatusMap = [
                                    'rascunho'    => ['label' => 'Rascunho',    'color' => 'secondary', 'icon' => 'fas fa-pencil-alt'],
                                    'processando' => ['label' => 'Processando', 'color' => 'info',      'icon' => 'fas fa-spinner fa-spin'],
                                    'autorizada'  => ['label' => 'Autorizada',  'color' => 'success',   'icon' => 'fas fa-check-circle'],
                                    'rejeitada'   => ['label' => 'Rejeitada',   'color' => 'danger',    'icon' => 'fas fa-times-circle'],
                                    'cancelada'   => ['label' => 'Cancelada',   'color' => 'dark',      'icon' => 'fas fa-ban'],
                                    'denegada'    => ['label' => 'Denegada',     'color' => 'warning',   'icon' => 'fas fa-exclamation'],
                                    'corrigida'   => ['label' => 'Corrigida',    'color' => 'primary',   'icon' => 'fas fa-pen'],
                                ];
                                $_si = $_nfeStatusMap[$nfeDoc['status']] ?? ['label' => $nfeDoc['status'], 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                                $nfeStatusLabel = $_si['label'];
                                $nfeStatusColor = $_si['color'];
                                $nfeStatusIcon = $_si['icon'];
                            }
                        } catch (\Exception $e) { /* ignora se tabela não existe ainda */ }
                    ?>
                    <!-- ═══ FISCAL — Nota Fiscal Eletrônica (SEFAZ) ═══ -->
                    <div class="card mt-3 border-0 shadow-sm" id="fiscalSection">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #28a74510 0%, #27ae6015 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" style="font-size:0.85rem; color:#28a745;">
                                    <i class="fas fa-file-invoice me-2"></i>NF-e — Nota Fiscal Eletrônica
                                    <?php if ($hasNfe): ?>
                                    <span class="badge bg-<?= $nfeStatusColor ?> ms-2" style="font-size:0.6rem;">
                                        <i class="<?= $nfeStatusIcon ?> me-1"></i><?= $nfeStatusLabel ?>
                                    </span>
                                    <?php endif; ?>
                                </h6>
                                <div class="d-flex gap-1">
                                    <?php if ($hasNfe): ?>
                                    <a href="?page=nfe_documents&action=detail&id=<?= $nfeDoc['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;" title="Ver detalhe da NF-e">
                                        <i class="fas fa-eye me-1"></i> Detalhe
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!$isReadOnly): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="btnEmitirNF" style="font-size:0.7rem;" title="Registrar NF-e emitida por outro sistema">
                                        <i class="fas fa-edit me-1"></i> Manual
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">

                            <?php if (!$hasNfe && in_array($currentStage, ['venda', 'financeiro', 'concluido'])): ?>
                            <!-- ═══ CTA principal: Emitir NF-e (sem nota emitida) ═══ -->
                            <div class="text-center py-3 mb-3" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-radius: 10px; border: 2px dashed #27ae6040;">
                                <i class="fas fa-file-invoice-dollar d-block mb-2" style="font-size: 2.5rem; color: #27ae60; opacity: 0.7;"></i>
                                <p class="mb-2 fw-bold text-success" style="font-size: 0.95rem;">Nenhuma NF-e emitida para este pedido</p>
                                <p class="small text-muted mb-3" style="font-size: 0.78rem;">Emita a nota fiscal eletrônica diretamente pela SEFAZ com um clique.</p>
                                <button type="button" class="btn btn-success btn-lg px-5 shadow-sm" id="btnEmitirNfeSefaz" style="font-size: 1rem; border-radius: 10px;">
                                    <i class="fas fa-file-export me-2"></i> Emitir NF-e via SEFAZ
                                </button>
                                <div class="mt-2">
                                    <small class="text-muted" style="font-size: 0.68rem;">
                                        <i class="fas fa-shield-alt me-1"></i>Valor: <strong>R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></strong>
                                        &nbsp;·&nbsp; Cliente: <strong><?= e($order['customer_name'] ?? '—') ?></strong>
                                    </small>
                                </div>
                            </div>
                            <?php elseif ($hasNfe && $nfeDoc['status'] === 'rejeitada' && in_array($currentStage, ['venda', 'financeiro', 'concluido'])): ?>
                            <!-- ═══ CTA: Reemitir NF-e rejeitada ═══ -->
                            <div class="text-center py-3 mb-3" style="background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%); border-radius: 10px; border: 2px dashed #e67e22;">
                                <i class="fas fa-exclamation-triangle d-block mb-2" style="font-size: 2rem; color: #e67e22; opacity: 0.7;"></i>
                                <p class="mb-2 fw-bold text-warning" style="font-size: 0.9rem;">NF-e Rejeitada pela SEFAZ</p>
                                <?php if (!empty($nfeDoc['motivo_sefaz'])): ?>
                                <p class="small text-danger mb-2" style="font-size: 0.75rem;"><i class="fas fa-times-circle me-1"></i><?= e($nfeDoc['motivo_sefaz']) ?></p>
                                <?php endif; ?>
                                <button type="button" class="btn btn-warning btn-lg text-dark px-5 shadow-sm" id="btnEmitirNfeSefaz" style="font-size: 1rem; border-radius: 10px;">
                                    <i class="fas fa-redo me-2"></i> Reemitir NF-e
                                </button>
                            </div>
                            <?php endif; ?>

                            <?php if ($hasNfe): ?>
                            <!-- ═══ Dados da NF-e emitida ═══ -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-3">
                                    <span class="small text-muted d-block">Número</span>
                                    <span class="fw-bold"><?= e($nfeDoc['numero']) ?></span>
                                </div>
                                <div class="col-md-2">
                                    <span class="small text-muted d-block">Série</span>
                                    <span class="fw-bold"><?= e($nfeDoc['serie']) ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="small text-muted d-block">Protocolo</span>
                                    <span class="fw-bold"><?= e($nfeDoc['protocolo'] ?? '—') ?></span>
                                </div>
                                <div class="col-md-3">
                                    <span class="small text-muted d-block">Valor</span>
                                    <span class="fw-bold text-success">R$ <?= number_format($nfeDoc['valor_total'], 2, ',', '.') ?></span>
                                </div>
                                <?php if (!empty($nfeDoc['chave'])): ?>
                                <div class="col-12">
                                    <span class="small text-muted d-block">Chave de Acesso</span>
                                    <span class="fw-bold" style="font-size:0.72rem; word-break:break-all; font-family:monospace;"><?= e($nfeDoc['chave']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($nfeDoc['emitted_at']): ?>
                                <div class="col-md-6">
                                    <span class="small text-muted d-block">Data Emissão</span>
                                    <span class="fw-bold"><?= date('d/m/Y H:i', strtotime($nfeDoc['emitted_at'])) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($nfeDoc['status'] === 'rejeitada' && !empty($nfeDoc['motivo_sefaz'])): ?>
                                <div class="col-12">
                                    <div class="alert alert-danger py-1 px-2 mb-0 small">
                                        <i class="fas fa-times-circle me-1"></i>
                                        <strong>SEFAZ:</strong> <?= e($nfeDoc['motivo_sefaz']) ?>
                                        <?php if ($nfeDoc['status_sefaz']): ?> (cStat: <?= e($nfeDoc['status_sefaz']) ?>)<?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($nfeDoc['status'] === 'cancelada'): ?>
                                <div class="col-12">
                                    <div class="alert alert-dark py-1 px-2 mb-0 small">
                                        <i class="fas fa-ban me-1"></i>
                                        <strong>Cancelada:</strong> <?= e($nfeDoc['cancel_motivo'] ?? '') ?>
                                        <?php if ($nfeDoc['cancel_date']): ?> em <?= date('d/m/Y H:i', strtotime($nfeDoc['cancel_date'])) ?><?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ═══ Ações da NF-e emitida ═══ -->

                            <?php if ($nfeDoc['xml_autorizado'] && in_array($nfeDoc['status'], ['autorizada', 'corrigida'])): ?>
                            <!-- Botão principal: Imprimir DANFE (destaque) -->
                            <div class="text-center py-3 mb-2" style="background: linear-gradient(135deg, #fce4ec 0%, #ffebee 100%); border-radius: 10px; border: 2px dashed #dc354540;">
                                <i class="fas fa-file-pdf d-block mb-2" style="font-size: 2.2rem; color: #dc3545; opacity: 0.6;"></i>
                                <p class="mb-2 small text-muted" style="font-size: 0.78rem;">Imprima o DANFE (PDF) para acompanhar a nota fiscal.</p>
                                <a href="?page=nfe_documents&action=download&id=<?= $nfeDoc['id'] ?>&type=danfe" 
                                   target="_blank" class="btn px-4 shadow-sm" id="btnPrintDanfePipeline" style="background:#dc3545; color:#fff; font-size: 0.95rem; border-radius: 10px;">
                                    <i class="fas fa-print me-2"></i> Imprimir DANFE (PDF)
                                </a>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-1">
                                <?php if ($nfeDoc['xml_autorizado']): ?>
                                <a href="?page=nfe_documents&action=download&id=<?= $nfeDoc['id'] ?>&type=xml" 
                                   class="btn btn-sm btn-outline-secondary" style="font-size:0.7rem;">
                                    <i class="fas fa-file-code me-1"></i> Baixar XML
                                </a>
                                <a href="?page=nfe_documents&action=download&id=<?= $nfeDoc['id'] ?>&type=danfe" 
                                   target="_blank" class="btn btn-sm btn-outline-danger" style="font-size:0.7rem;">
                                    <i class="fas fa-file-pdf me-1"></i> Abrir DANFE
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($nfeDoc['xml_cancelamento'])): ?>
                                <a href="?page=nfe_documents&action=download&id=<?= $nfeDoc['id'] ?>&type=xml_cancel" 
                                   class="btn btn-sm btn-outline-dark" style="font-size:0.7rem;">
                                    <i class="fas fa-file-code me-1"></i> XML Cancelamento
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($nfeDoc['xml_correcao'])): ?>
                                <a href="?page=nfe_documents&action=download&id=<?= $nfeDoc['id'] ?>&type=xml_correcao" 
                                   class="btn btn-sm btn-outline-info" style="font-size:0.7rem;">
                                    <i class="fas fa-file-code me-1"></i> XML Correção
                                </a>
                                <?php endif; ?>
                                <a href="?page=nfe_documents&action=detail&id=<?= $nfeDoc['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;">
                                    <i class="fas fa-external-link-alt me-1"></i> Detalhe Completo
                                </a>
                            </div>

                            <?php if (in_array($nfeDoc['status'], ['autorizada', 'corrigida']) && !$isReadOnly): ?>
                            <hr class="my-2">
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-dark btn-cancel-nfe-pipeline" 
                                        data-id="<?= $nfeDoc['id'] ?>" data-numero="<?= e($nfeDoc['numero']) ?>"
                                        style="font-size:0.7rem;">
                                    <i class="fas fa-ban me-1"></i> Cancelar NF-e
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info btn-correcao-nfe-pipeline"
                                        data-id="<?= $nfeDoc['id'] ?>" data-numero="<?= e($nfeDoc['numero']) ?>"
                                        style="font-size:0.7rem;">
                                    <i class="fas fa-pen me-1"></i> Carta Correção
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-check-status-pipeline"
                                        data-id="<?= $nfeDoc['id'] ?>" style="font-size:0.7rem;">
                                    <i class="fas fa-sync me-1"></i> Consultar SEFAZ
                                </button>
                            </div>
                            <?php elseif (in_array($nfeDoc['status'], ['autorizada', 'corrigida']) && $isReadOnly): ?>
                            <hr class="my-2">
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-check-status-pipeline"
                                        data-id="<?= $nfeDoc['id'] ?>" style="font-size:0.7rem;">
                                    <i class="fas fa-sync me-1"></i> Consultar SEFAZ
                                </button>
                            </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <?php endif; ?>

                            <?php
                                // Determinar se os campos manuais têm dados preenchidos (auto-expandir se sim)
                                $_hasManualNfData = !empty($order['nf_number'] ?? '')
                                    || !empty($order['nf_series'] ?? '')
                                    || !empty($order['nf_status'] ?? '')
                                    || !empty($order['nf_access_key'] ?? '')
                                    || !empty($order['nf_notes'] ?? '');
                            ?>
                            <!-- ═══ Campos manuais (fallback / complemento) — ocultos por padrão ═══ -->
                            <div id="nfeManualFieldsWrapper" style="<?= $_hasManualNfData ? '' : 'display:none;' ?>">
                                <hr class="my-2">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="small fw-bold text-muted"><i class="fas fa-edit me-1"></i>Registro Manual de NF-e</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-auto" id="btnCloseManualNfe" title="Fechar campos manuais">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <p class="small text-muted mb-2" style="font-size:0.72rem;">
                                    Use estes campos apenas se a NF-e foi emitida por outro sistema externo.
                                    Para emitir via SEFAZ, use o botão <strong>"Emitir NF-e (SEFAZ)"</strong>.
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Nº da Nota Fiscal</label>
                                        <input type="text" class="form-control" name="nf_number" id="nfNumber"
                                               placeholder="Ex: 000123"
                                               value="<?= e($order['nf_number'] ?? '') ?>"
                                               <?= $isReadOnly ? 'disabled' : '' ?>>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Série</label>
                                        <input type="text" class="form-control" name="nf_series" id="nfSeries"
                                               placeholder="Ex: 1"
                                               value="<?= e($order['nf_series'] ?? '') ?>"
                                               <?= $isReadOnly ? 'disabled' : '' ?>>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Status NF</label>
                                        <select class="form-select" name="nf_status" id="nfStatus" <?= $isReadOnly ? 'disabled' : '' ?>>
                                            <option value="" <?= empty($order['nf_status'] ?? '') ? 'selected' : '' ?>>⬜ Não emitida</option>
                                            <option value="emitida" <?= ($order['nf_status'] ?? '') == 'emitida' ? 'selected' : '' ?>>📄 Emitida</option>
                                            <option value="enviada" <?= ($order['nf_status'] ?? '') == 'enviada' ? 'selected' : '' ?>>📨 Enviada ao cliente</option>
                                            <option value="cancelada" <?= ($order['nf_status'] ?? '') == 'cancelada' ? 'selected' : '' ?>>❌ Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Chave de Acesso (NFe)</label>
                                        <input type="text" class="form-control" name="nf_access_key" id="nfAccessKey"
                                               placeholder="44 dígitos da chave da NFe..."
                                               value="<?= e($order['nf_access_key'] ?? '') ?>"
                                               <?= $isReadOnly ? 'disabled' : '' ?>>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Observações Fiscais</label>
                                        <input type="text" class="form-control" name="nf_notes" id="nfNotes"
                                               placeholder="Observações sobre a nota fiscal..."
                                               value="<?= e($order['nf_notes'] ?? '') ?>"
                                               <?= $isReadOnly ? 'disabled' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!$canUseNfeModule): ?>
                    <!-- NF-e desabilitado — card informativo -->
                    <div class="card mt-3 border-0 shadow-sm opacity-75" role="button"
                         onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('nfe') ?>">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #dee2e610 0%, #adb5bd10 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-muted" style="font-size:0.85rem;">
                                    <i class="fas fa-file-invoice me-2"></i>Fiscal / Nota Fiscal
                                    <span class="badge bg-secondary ms-2" style="font-size:0.6rem;">
                                        <i class="fas fa-lock me-1"></i>Módulo Inativo
                                    </span>
                                </h6>
                                <i class="fas fa-lock text-muted" style="font-size:0.75rem;"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </fieldset>
                <?php else: ?>
                <!-- Manter valores atuais nos campos ocultos para não perder ao salvar -->
                <input type="hidden" name="discount" value="<?= $order['discount'] ?? 0 ?>">
                <input type="hidden" name="payment_status" value="<?= $order['payment_status'] ?? 'pendente' ?>">
                <input type="hidden" name="payment_method" value="<?= $order['payment_method'] ?? '' ?>">
                <input type="hidden" name="installments" value="<?= $order['installments'] ?? '' ?>">
                <input type="hidden" name="installment_value" value="<?= $order['installment_value'] ?? '' ?>">
                <input type="hidden" name="down_payment" value="<?= $order['down_payment'] ?? '0' ?>">
                <input type="hidden" name="nf_number" value="<?= e($order['nf_number'] ?? '') ?>">
                <input type="hidden" name="nf_series" value="<?= e($order['nf_series'] ?? '') ?>">
                <input type="hidden" name="nf_status" value="<?= $order['nf_status'] ?? '' ?>">
                <input type="hidden" name="nf_access_key" value="<?= e($order['nf_access_key'] ?? '') ?>">
                <input type="hidden" name="nf_notes" value="<?= e($order['nf_notes'] ?? '') ?>">
                <?php endif; ?>

                <?php if ($showShipping): ?>
                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- ═══ ENVIO / ENTREGA — Card principal na etapa "envio" ═══ -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <?php 
                    $isEnvioStage = ($currentStage === 'envio');
                    $shippingType = $order['shipping_type'] ?? 'retirada';
                    $shippingAddress = !empty($order['shipping_address']) ? $order['shipping_address'] : ($customerFormattedAddress ?? '');
                    $trackingCode = $order['tracking_code'] ?? '';
                    
                    $shippingTypeLabels = [
                        'retirada' => ['label' => 'Retirada na Loja', 'icon' => 'fas fa-store', 'color' => '#27ae60', 'emoji' => '🏪'],
                        'entrega'  => ['label' => 'Entrega Própria',  'icon' => 'fas fa-motorcycle', 'color' => '#e67e22', 'emoji' => '🏍️'],
                        'correios' => ['label' => 'Correios / Transportadora', 'icon' => 'fas fa-box', 'color' => '#3498db', 'emoji' => '📦'],
                    ];
                    $stInfo = $shippingTypeLabels[$shippingType] ?? $shippingTypeLabels['retirada'];
                ?>
                <fieldset class="p-4 mb-4" style="border: 2px solid <?= $stInfo['color'] ?>; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5" style="color: <?= $stInfo['color'] ?>;">
                        <i class="fas fa-truck me-2"></i>Envio / Entrega
                        <span class="badge ms-2" id="shippingBadgeLegend" style="font-size:0.7rem;background:<?= $stInfo['color'] ?>;color:#fff;">
                            <i class="<?= $stInfo['icon'] ?> me-1"></i><?= $stInfo['label'] ?>
                        </span>
                    </legend>

                    <?php if ($isEnvioStage && !empty($orderItems)): ?>
                    <!-- Resumo dos produtos do pedido (visível apenas na etapa envio) -->
                    <div class="alert alert-light border py-2 px-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="fw-bold text-muted"><i class="fas fa-boxes-stacked me-1"></i>Produtos do Pedido</small>
                            <span class="badge bg-secondary"><?= count($orderItems) ?> item(ns)</span>
                        </div>
                        <div class="mt-1">
                            <?php foreach ($orderItems as $oi): ?>
                            <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:0.75rem;">
                                <i class="fas fa-box me-1 text-muted"></i><?= e($oi['product_name']) ?>
                                <strong class="ms-1">×<?= $oi['quantity'] ?></strong>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 text-end">
                            <strong class="text-success" style="font-size:0.85rem;">
                                <i class="fas fa-coins me-1"></i>Total: R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tipo de Envio -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted"><i class="fas fa-shipping-fast me-1"></i>Modalidade de Envio</label>
                            <select class="form-select" name="shipping_type" id="shippingType" <?= $isReadOnly ? 'disabled' : '' ?>>
                                <option value="retirada" <?= $shippingType == 'retirada' ? 'selected' : '' ?>>🏪 Retirada na loja</option>
                                <option value="entrega" <?= $shippingType == 'entrega' ? 'selected' : '' ?>>🏍️ Entrega própria</option>
                                <option value="correios" <?= $shippingType == 'correios' ? 'selected' : '' ?>>📦 Correios / Transportadora</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted"><i class="fas fa-user me-1"></i>Destinatário</label>
                            <input type="text" class="form-control" value="<?= e($order['customer_name'] ?? '—') ?> — <?= $order['customer_phone'] ?? '' ?>" disabled>
                        </div>
                    </div>

                    <!-- Retirada na loja (visível apenas quando tipo = retirada) -->
                    <div class="card mb-3 border-0 shadow-sm" id="shippingRetiradaCard" style="<?= ($shippingType !== 'retirada') ? 'display:none;' : '' ?>">
                        <div class="card-body p-3 text-center" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-radius: 10px; border: 2px dashed #27ae6040;">
                            <i class="fas fa-store d-block mb-2" style="font-size:2.2rem; color:#27ae60; opacity:0.6;"></i>
                            <span class="text-muted fs-6">O cliente irá <strong>retirar na loja</strong>.</span>
                            <p class="text-muted small mt-1 mb-0">Nenhum endereço de entrega necessário.</p>
                        </div>
                    </div>

                    <!-- Endereço de Entrega (visível quando tipo = entrega ou correios) -->
                    <div class="card mb-3 border-0 shadow-sm" id="shippingAddressCard" style="<?= ($shippingType === 'retirada') ? 'display:none;' : '' ?>">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #e67e2210 0%, #f39c1208 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" style="font-size:0.85rem; color:#e67e22;">
                                    <i class="fas fa-map-marker-alt me-2"></i>Endereço de Entrega
                                </h6>
                                <div class="d-flex gap-1">
                                    <?php if (!empty($shippingAddress)): ?>
                                    <a href="https://www.google.com/maps/search/<?= urlencode($shippingAddress) ?>" target="_blank" 
                                       class="btn btn-sm btn-outline-primary" title="Ver no Google Maps" style="font-size:0.7rem;" id="btnVerMapa">
                                        <i class="fas fa-map me-1"></i> Ver no Mapa
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-dark" id="btnPrintLabel" title="Imprimir guia de endereçamento" style="font-size:0.7rem;">
                                        <i class="fas fa-print me-1"></i> Imprimir Guia
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <textarea class="form-control form-control-lg" name="shipping_address" id="shippingAddressTextarea" rows="2" 
                                      placeholder="Endereço completo de entrega..." 
                                      style="font-size:0.95rem;"
                                      <?= $isReadOnly ? 'disabled' : '' ?>><?= e($shippingAddress) ?></textarea>
                            <?php if (!empty($customerFormattedAddress) && !$isReadOnly): ?>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUseCustomerAddress">
                                    <i class="fas fa-user-tag me-1"></i> Usar endereço do cliente
                                </button>
                                <small class="text-muted ms-2"><?= e($customerFormattedAddress) ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Hidden field para manter o endereço quando retirada está selecionada -->
                    <input type="hidden" name="shipping_address_backup" id="shippingAddressBackup" value="<?= e($shippingAddress) ?>"

                    >

                    <!-- Rastreamento e Código -->
                    <div class="card mb-3 border-0 shadow-sm" id="trackingSection">
                        <div class="card-header py-2" style="background: linear-gradient(135deg, #3498db10 0%, #2980b908 100%);">
                            <h6 class="mb-0" style="font-size:0.85rem; color:#3498db;">
                                <i class="fas fa-barcode me-2"></i>Rastreamento
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Código de Rastreio</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="tracking_code" id="trackingCodeInput"
                                               placeholder="Ex: BR123456789XX" 
                                               value="<?= e($trackingCode) ?>" 
                                               <?= $isReadOnly ? 'disabled' : '' ?>>
                                        <?php if (!empty($trackingCode)): ?>
                                        <a href="https://www.linkcorreios.com.br/?id=<?= urlencode($trackingCode) ?>" target="_blank" 
                                           class="btn btn-outline-primary" title="Rastrear nos Correios">
                                            <i class="fas fa-search"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Status do Envio</label>
                                    <div class="d-flex align-items-center gap-2 py-2">
                                        <?php if (empty($trackingCode) && $shippingType !== 'retirada'): ?>
                                        <span class="badge bg-warning bg-opacity-75 px-3 py-2">
                                            <i class="fas fa-clock me-1"></i> Aguardando envio
                                        </span>
                                        <?php elseif ($shippingType === 'retirada'): ?>
                                        <span class="badge bg-success bg-opacity-75 px-3 py-2">
                                            <i class="fas fa-store me-1"></i> Aguardando retirada
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-info bg-opacity-75 px-3 py-2">
                                            <i class="fas fa-shipping-fast me-1"></i> Enviado
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($trackingCode)): ?>
                            <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">
                                <i class="fas fa-truck me-1"></i>
                                Código: <strong class="user-select-all"><?= e($trackingCode) ?></strong>
                                <?php if (!empty($order['customer_phone'])): ?>
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $order['customer_phone']) ?>?text=<?= urlencode('Olá! Seu pedido #' . str_pad($order['id'], 4, '0', STR_PAD_LEFT) . ' foi enviado. Código de rastreio: ' . $trackingCode) ?>" 
                                   target="_blank" class="btn btn-sm btn-success ms-2" style="font-size:0.7rem;">
                                    <i class="fab fa-whatsapp me-1"></i> Enviar rastreio via WhatsApp
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- API de Transportadoras (placeholder para integração futura) -->
                    <div class="card border-0 shadow-sm mb-0">
                        <div class="card-body p-3 text-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border: 2px dashed #adb5bd40;">
                            <i class="fas fa-plug text-muted d-block mb-2" style="font-size:1.5rem;opacity:0.4;"></i>
                            <p class="small text-muted mb-1 fw-bold">Integração com Transportadoras</p>
                            <p class="small text-muted mb-0" style="font-size:0.72rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                Em breve: integração com APIs de Correios, Jadlog, Melhor Envio e outras transportadoras
                                para calcular frete, gerar etiquetas e rastrear automaticamente.
                            </p>
                            <?php if ($isEnvioStage && !$isReadOnly): ?>
                            <div class="mt-2 d-flex justify-content-center gap-2">
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-box me-1"></i>Correios</span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-truck me-1"></i>Jadlog</span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-shipping-fast me-1"></i>Melhor Envio</span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-dolly me-1"></i>Loggi</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </fieldset>
                <?php else: ?>
                <!-- Manter valores atuais nos campos ocultos para não perder ao salvar -->
                <input type="hidden" name="shipping_type" value="<?= $order['shipping_type'] ?? 'retirada' ?>">
                <input type="hidden" name="shipping_address" value="<?= e($order['shipping_address'] ?? '') ?>">
                <input type="hidden" name="tracking_code" value="<?= $order['tracking_code'] ?? '' ?>">
                <?php endif; ?>

            </form>
        </div>

        <!-- Coluna Direita: Timeline / Histórico -->
        <div class="col-lg-4">

            <!-- ═══ Mini Manual Contextual ═══ -->
            <?php
            $miniManualContent = [];
            switch ($currentStage) {
                case 'contato':
                    $miniManualContent = [
                        'icon'  => 'fas fa-handshake',
                        'color' => '#3498db',
                        'title' => 'Etapa de Contato',
                        'tips'  => [
                            'Registre os dados do cliente e as primeiras informações do pedido.',
                            'Quando tiver os dados iniciais, avance para <strong>Orçamento</strong>.',
                            'Use o campo de <em>Observações Internas</em> para anotar detalhes da conversa.'
                        ]
                    ];
                    break;
                case 'orcamento':
                    $miniManualContent = [
                        'icon'  => 'fas fa-calculator',
                        'color' => '#9b59b6',
                        'title' => 'Etapa de Orçamento',
                        'tips'  => [
                            'Adicione os <strong>produtos</strong> e <strong>custos extras</strong> ao pedido.',
                            'Gere um <strong>link de catálogo</strong> para o cliente montar a lista.',
                            'Imprima o orçamento para enviar ao cliente e aguarde aprovação.',
                            'Após aprovação, avance para <strong>Venda</strong>.'
                        ]
                    ];
                    break;
                case 'venda':
                    $miniManualContent = [
                        'icon'  => 'fas fa-shopping-cart',
                        'color' => '#e67e22',
                        'title' => 'Etapa de Venda',
                        'tips'  => [
                            'Confira os produtos e valores do pedido.',
                            'Defina a <strong>forma de pagamento</strong> e as condições.',
                            'Imprima a <strong>Nota de Pedido</strong> para o cliente.',
                            'Avance para <strong>Produção</strong> quando tudo estiver confirmado.'
                        ]
                    ];
                    break;
                case 'producao':
                    $miniManualContent = [
                        'icon'  => 'fas fa-industry',
                        'color' => '#27ae60',
                        'title' => 'Etapa de Produção',
                        'tips'  => [
                            'Acompanhe os <strong>setores de produção</strong> de cada produto.',
                            'Clique em <strong>Concluir</strong> ao finalizar cada setor.',
                            'Quando todos os setores estiverem concluídos, avance para <strong>Preparação</strong>.',
                            'Use o <em>Registro</em> abaixo para anotar observações ou anexar fotos.'
                        ]
                    ];
                    break;
                case 'preparacao':
                    $miniManualContent = [
                        'icon'  => 'fas fa-boxes-packing',
                        'color' => '#1abc9c',
                        'title' => 'Etapa de Preparação',
                        'tips'  => [
                            'Complete o <strong>checklist de preparo</strong> clicando em cada item.',
                            'Verifique se todos os produtos estão prontos para envio.',
                            'Quando tudo estiver preparado, avance para <strong>Envio/Entrega</strong>.'
                        ]
                    ];
                    break;
                case 'envio':
                    $miniManualContent = [
                        'icon'  => 'fas fa-truck',
                        'color' => '#3498db',
                        'title' => 'Etapa de Envio/Entrega',
                        'tips'  => [
                            'Defina a <strong>modalidade de envio</strong> (retirada, entrega ou correios).',
                            'Preencha o endereço e o <strong>código de rastreamento</strong>.',
                            'Envie o código de rastreio ao cliente via <strong>WhatsApp</strong>.',
                            'Avance para <strong>Financeiro</strong> quando o pedido for entregue.'
                        ]
                    ];
                    break;
                case 'financeiro':
                    $miniManualContent = [
                        'icon'  => 'fas fa-coins',
                        'color' => '#f39c12',
                        'title' => 'Etapa Financeira',
                        'tips'  => [
                            'Confirme a <strong>forma de pagamento</strong> e configure o parcelamento.',
                            'Registre a <strong>entrada (sinal)</strong> se houver.',
                            'Emita a <strong>NF-e</strong> ou registre o cupom fiscal.',
                            'Quando o pagamento estiver confirmado, avance para <strong>Concluído</strong>.'
                        ]
                    ];
                    break;
                case 'concluido':
                    $miniManualContent = [
                        'icon'  => 'fas fa-check-double',
                        'color' => '#27ae60',
                        'title' => 'Pedido Concluído',
                        'tips'  => [
                            'Este pedido está <strong>finalizado</strong>. Todos os campos são apenas leitura.',
                            'Use o botão <strong>Mover para...</strong> para reabrir se necessário.',
                            'Os documentos fiscais e o histórico permanecem acessíveis.'
                        ]
                    ];
                    break;
                case 'cancelado':
                    $miniManualContent = [
                        'icon'  => 'fas fa-ban',
                        'color' => '#e74c3c',
                        'title' => 'Pedido Cancelado',
                        'tips'  => [
                            'Este pedido foi <strong>cancelado</strong>. Todos os campos são apenas leitura.',
                            'Use o botão <strong>Mover para...</strong> para reabrir se necessário.',
                            'Se houver NF-e emitida, cancele-a antes de reabrir.'
                        ]
                    ];
                    break;
            }
            ?>
            <?php if (!empty($miniManualContent)): ?>
            <div class="card border-0 shadow-sm mb-4" style="border-left: 3px solid <?= $miniManualContent['color'] ?> !important;">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold" style="color: <?= $miniManualContent['color'] ?>; font-size: 0.85rem;">
                            <i class="<?= $miniManualContent['icon'] ?> me-2"></i><?= $miniManualContent['title'] ?>
                        </h6>
                        <span class="badge" style="font-size:0.6rem; background: <?= $miniManualContent['color'] ?>20; color: <?= $miniManualContent['color'] ?>;">
                            <i class="fas fa-lightbulb me-1"></i>Dica
                        </span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0" style="font-size: 0.78rem;">
                        <?php foreach ($miniManualContent['tips'] as $idx => $tip): ?>
                        <li class="<?= $idx > 0 ? 'mt-2' : '' ?> d-flex align-items-start">
                            <i class="fas fa-chevron-right me-2 mt-1 flex-shrink-0" style="font-size:0.55rem; color: <?= $miniManualContent['color'] ?>; opacity:0.7;"></i>
                            <span class="text-muted"><?= $tip ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (!$isReadOnly): ?>
                    <hr class="my-2" style="opacity:0.15;">
                    <div class="d-flex align-items-center gap-2">
                        <a href="?page=walkthrough&action=manual" class="btn btn-sm btn-link text-muted p-0" style="font-size:0.7rem;" title="Manual do Sistema">
                            <i class="fas fa-book me-1"></i>Manual Completo
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Histórico de Movimentação do Pipeline -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2" style="background: linear-gradient(135deg, #3498db10 0%, #0d6efd15 100%);">
                    <h6 class="mb-0 fw-bold" style="font-size:0.85rem; color:#3498db;"><i class="fas fa-history me-2"></i>Histórico de Movimentação</h6>
                </div>
                <div class="card-body p-3" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($history)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-stream d-block mb-2" style="font-size:2rem;"></i>
                            Nenhuma movimentação registrada.
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php 
                                $historyFirst = !empty($history) ? $history[0] : null;
                            ?>
                            <?php foreach ($history as $h): ?>
                            <?php 
                                $toInfo = $stages[$h['to_stage']] ?? ['label' => $h['to_stage'], 'color' => '#999', 'icon' => 'fas fa-circle'];
                                $fromInfo = $stages[$h['from_stage'] ?? ''] ?? ['label' => '—', 'color' => '#ccc', 'icon' => ''];
                            ?>
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-icon me-3 flex-shrink-0">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width:32px;height:32px;background:<?= $toInfo['color'] ?>;color:#fff;font-size:0.75rem;">
                                        <i class="<?= $toInfo['icon'] ?>"></i>
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <div class="small fw-bold"><?= $toInfo['label'] ?></div>
                                    <div class="small text-muted">
                                        <?php if ($h['from_stage']): ?>
                                            De: <?= $fromInfo['label'] ?> → <?= $toInfo['label'] ?>
                                        <?php else: ?>
                                            Etapa inicial
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($h['notes'])): ?>
                                    <div class="small fst-italic mt-1">"<?= $h['notes'] ?>"</div>
                                    <?php endif; ?>
                                    <?php 
                                        // Exibir permanência na etapa (duration_seconds = tempo até a próxima movimentação)
                                        // Para a última movimentação (mais recente, primeira na lista DESC), mostra "em andamento"
                                        // Para etapas terminais (concluído/cancelado), não mostrar duração acumulando
                                        $isLastEntry = ($historyFirst !== null && $h['id'] === $historyFirst['id']);
                                        $isTerminalStage = in_array($h['to_stage'], ['concluido', 'cancelado']);
                                        $showDuration = isset($h['duration_seconds']) && (int)$h['duration_seconds'] > 0 
                                                         && !($isLastEntry && $isTerminalStage);
                                    ?>
                                    <?php if ($showDuration): 
                                        $dur = (int)$h['duration_seconds'];
                                        $durDays = floor($dur / 86400);
                                        $durHours = floor(($dur % 86400) / 3600);
                                        $durMins = floor(($dur % 3600) / 60);
                                        $durText = '';
                                        if ($durDays > 0) $durText .= $durDays . 'd ';
                                        if ($durHours > 0 || $durDays > 0) $durText .= $durHours . 'h ';
                                        $durText .= $durMins . 'min';
                                    ?>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-dark border" style="font-size:0.6rem;">
                                            <i class="fas fa-stopwatch me-1 text-warning"></i>Permanência: <?= trim($durText) ?>
                                            <?php if ($isLastEntry && !$isTerminalStage): ?>
                                            <span class="text-muted">(em andamento)</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="text-muted" style="font-size:0.65rem;">
                                        <i class="fas fa-user me-1"></i><?= $h['user_name'] ?? 'Sistema' ?>
                                        · <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ═══ Registro (Logs dos Produtos) ═══ -->
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2" style="background: linear-gradient(135deg, #27ae6010 0%, #2ecc7115 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold" style="font-size:0.85rem; color:#27ae60;"><i class="fas fa-clipboard-list me-2"></i>Registro</h6>
                        <?php if (!empty($orderItems) && !$isReadOnly): ?>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#collapseAddLog">
                            <i class="fas fa-plus me-1"></i> Novo
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($orderItems) && !$isReadOnly): ?>
                <div class="collapse" id="collapseAddLog">
                    <div class="p-3 border-bottom bg-light">
                        <form id="formAddItemLogDetail" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-muted mb-1">Produto</label>
                                <select class="form-select form-select-sm" name="order_item_id" id="detailLogItemSelect" required>
                                    <option value="">Selecione o produto...</option>
                                    <option value="all">📋 Todos os Produtos (Registro Geral)</option>
                                    <?php foreach ($orderItems as $oi): ?>
                                    <option value="<?= $oi['id'] ?>"><?= e($oi['product_name'] ?? 'Produto #'.$oi['product_id']) ?> (Qtd: <?= $oi['quantity'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <textarea class="form-control form-control-sm" name="message" rows="2" 
                                          placeholder="Observação, registro de erro, instrução..."></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="btn btn-sm btn-outline-secondary mb-0" for="detailLogFile">
                                        <i class="fas fa-paperclip me-1"></i> Anexar
                                    </label>
                                    <input type="file" class="d-none" id="detailLogFile" name="file" accept="image/*,.pdf">
                                    <small class="text-muted d-none" id="detailLogFileLabel"></small>
                                </div>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i> Adicionar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card-body p-3" style="max-height: 500px; overflow-y: auto; overflow-x: hidden;">
                    <?php if (empty($orderItemLogs)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clipboard d-block mb-2" style="font-size:2rem;opacity:0.4;"></i>
                            <p class="mb-0">Nenhum registro de produto.<br><small>Clique em "Novo" para adicionar.</small></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orderItemLogs as $log): 
                            $isImage = !empty($log['file_type']) && str_starts_with($log['file_type'], 'image/');
                            $isPdf = ($log['file_type'] ?? '') === 'application/pdf';
                        ?>
                        <div class="d-flex gap-2 mb-3 pb-3 border-bottom detail-log-entry" style="min-width:0;">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width:32px;height:32px;background:<?= $isImage ? '#e8f5e9' : ($isPdf ? '#fce4ec' : '#e3f2fd') ?>;">
                                    <?php if ($isImage): ?>
                                        <i class="fas fa-image text-success" style="font-size:0.8rem;"></i>
                                    <?php elseif ($isPdf): ?>
                                        <i class="fas fa-file-pdf text-danger" style="font-size:0.8rem;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-comment text-primary" style="font-size:0.8rem;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-1">
                                    <div style="min-width:0;">
                                        <span class="badge bg-success text-success border border-success border-opacity-25 me-1" style="font-size:0.6rem; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block; vertical-align:middle;">
                                            <i class="fas fa-box me-1"></i><?= e($log['product_name'] ?? 'Produto') ?>
                                        </span>
                                        <span class="small fw-bold"><?= e($log['user_name'] ?? 'Sistema') ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        <span class="text-muted" style="font-size:0.6rem;white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></span>
                                        <?php if (!$isReadOnly): ?>
                                        <button type="button" class="btn btn-sm p-0 text-danger btn-delete-detail-log" 
                                                data-log-id="<?= $log['id'] ?>" title="Excluir" style="font-size:0.65rem;line-height:1;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($log['message'])): ?>
                                <div class="small mt-1" style="white-space:pre-wrap;word-break:break-word;overflow-wrap:break-word;"><?= e($log['message']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($log['file_path'])): ?>
                                    <?php if ($isImage): ?>
                                    <div class="mt-2">
                                        <a href="<?= $log['file_path'] ?>" target="_blank">
                                            <img src="<?= $log['file_path'] ?>" class="rounded border" 
                                                 style="max-width:100%;max-height:150px;" alt="<?= e($log['file_name']) ?>">
                                        </a>
                                        <div class="small text-muted mt-1"><i class="fas fa-image me-1"></i><?= e($log['file_name']) ?></div>
                                    </div>
                                    <?php elseif ($isPdf): ?>
                                    <div class="mt-2">
                                        <a href="<?= $log['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-file-pdf me-1"></i><?= e($log['file_name']) ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ═══ Micro-interações e transições ═══ */
.card { transition: box-shadow 0.2s ease; }
.card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08) !important; }
fieldset { transition: border-color 0.3s ease; }
.btn { transition: all 0.2s ease; }
.badge { transition: all 0.2s ease; }
.prep-check-card { transition: all 0.2s ease; }
.prep-check-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.payment-method-card { transition: all 0.2s ease !important; }
.payment-method-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; }
.timeline-item { transition: background-color 0.2s ease; }
.timeline-item:hover { background-color: rgba(0,0,0,0.01); border-radius: 8px; }

/* ═══ Toast Push para Link de Pagamento ═══ */
@keyframes gwToastSlideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}
@keyframes gwToastTimer {
    from { width: 100%; }
    to   { width: 0%;   }
}
.gw-payment-toast {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.gw-payment-toast .input-group .form-control {
    font-size: 0.7rem !important;
}

/* ═══ Estilos Controle de Produção por Produto ═══ */
.production-item-card {
    transition: box-shadow 0.2s;
    border-left: 3px solid #e0e0e0 !important;
}
.production-item-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.08) !important;
}
.production-item-card.border-success {
    border-left: 3px solid #27ae60 !important;
}
.production-stepper {
    overflow-x: auto;
    scrollbar-width: thin;
}
.production-step {
    min-width: 50px;
}
@keyframes sectorPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4); }
    50% { box-shadow: 0 0 0 6px rgba(52, 152, 219, 0); }
}
.sector-pulse {
    animation: sectorPulse 2s ease-in-out infinite;
}
</style>

<script>
// ── CSRF token global para fetch POST (acessível por todas as funções, inclusive fora do DOMContentLoaded) ──
const __csrfMeta = document.querySelector('meta[name="csrf-token"]');
const __csrfToken = __csrfMeta ? __csrfMeta.getAttribute('content') : '';

document.addEventListener('DOMContentLoaded', function() {
    
    <?php if(isset($_GET['status'])): ?>
    // Limpar o parâmetro status da URL para não disparar novamente
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('status');
        url.searchParams.delete('print_order');
        window.history.replaceState({}, '', url);
    }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <?php if(!empty($_GET['print_order'])): ?>
    // Salvo com sucesso + imprimir nota de pedido: abrir em nova aba automaticamente
    Swal.fire({ icon: 'success', title: 'Salvo!', text: 'Abrindo a Nota de Pedido...', timer: 1500, showConfirmButton: false });
    window.open('?page=orders&action=printOrder&id=<?= $order['id'] ?>', '_blank');
    <?php else: ?>
    Swal.fire({ icon: 'success', title: 'Salvo!', text: 'Detalhes atualizados com sucesso.', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'item_added'): ?>
    Swal.fire({ icon: 'success', title: 'Produto adicionado!', timer: 1500, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'item_deleted'): ?>
    Swal.fire({ icon: 'success', title: 'Produto removido!', timer: 1500, showConfirmButton: false });
    <?php endif; ?>

    <?php if(!empty($_SESSION['error'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Ação bloqueada',
        html: <?= json_encode($_SESSION['error']) ?>,
        confirmButtonColor: '#e74c3c'
    });
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    // Botão "Nota de Pedido" no cabeçalho — salva o formulário antes de imprimir
    const btnHeaderPrintOrder = document.getElementById('btnHeaderPrintOrder');
    if (btnHeaderPrintOrder) {
        btnHeaderPrintOrder.addEventListener('click', function() {
            // Injetar campo hidden no formulário principal e submeter
            const form = document.querySelector('form[action*="updateDetails"]');
            if (form) {
                let hiddenInput = form.querySelector('input[name="print_order_after_save"]');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'print_order_after_save';
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = '1';
                form.submit();
            }
        });
    }

    // Confirmação ao mover etapa — com seleção de armazém quando transição pré-produção → produção+
    const currentStageKey = '<?= $currentStage ?>';
    const preProductionStages = ['contato', 'orcamento', 'venda'];
    const productionStages = ['producao', 'preparacao', 'envio', 'financeiro', 'concluido'];

    // ═══ Etapas bloqueadas quando existem parcelas pagas ═══
    const stagesBlockedByPaid = <?= json_encode($stagesBlockedByPaid) ?>;
    const hasPaidInstallments = <?= $hasPaidInstallments ? 'true' : 'false' ?>;

    function needsWarehouseForTransition(fromStage, toStage) {
        return preProductionStages.includes(fromStage) && productionStages.includes(toStage);
    }

    /**
     * Verifica se a movimentação está bloqueada por parcelas pagas
     */
    function isMoveBlockedByPaidInstallments(targetStage) {
        return hasPaidInstallments && stagesBlockedByPaid.includes(targetStage);
    }
    
    document.querySelectorAll('.btn-move-stage').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            const dir = this.dataset.dir;
            const stage = this.dataset.stage;
            const targetStage = this.dataset.targetStage || '';
            const orderId = this.dataset.orderId || '<?= $order['id'] ?>';

            // ═══ BLOQUEIO: Parcelas pagas impedem retrocesso/cancelamento ═══
            if (isMoveBlockedByPaidInstallments(targetStage)) {
                Swal.fire({
                    icon: 'error',
                    title: '<i class="fas fa-lock me-2"></i>Movimentação bloqueada',
                    html: '<p>Não é possível mover o pedido para <strong>' + stage + '</strong> porque existem parcelas já pagas.</p>'
                        + '<p class="small text-muted mt-2">Para retroceder o pedido ou cancelá-lo, estorne todos os pagamentos primeiro no módulo <strong>Financeiro</strong>.</p>',
                    confirmButtonText: '<i class="fas fa-external-link-alt me-1"></i> Ir para Financeiro',
                    showCancelButton: true,
                    cancelButtonText: 'Fechar',
                    confirmButtonColor: '#e74c3c'
                }).then(function(r) {
                    if (r.isConfirmed) {
                        window.open('?page=financial&action=payments', '_blank');
                    }
                });
                return;
            }
            
            // Se a transição precisa de seleção de armazém (pré-produção → produção+)
            if (needsWarehouseForTransition(currentStageKey, targetStage)) {
                // Buscar dados de estoque via AJAX
                Swal.fire({
                    title: `<i class="fas fa-warehouse me-2"></i>${dir} para ${stage}`,
                    html: '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2 d-block">Verificando estoque...</small></div>',
                    showConfirmButton: false,
                    showCancelButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        fetch(`?page=pipeline&action=checkOrderStock&order_id=${orderId}`)
                            .then(r => r.json())
                            .then(data => {
                                if (!data.success) {
                                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao verificar estoque.' });
                                    return;
                                }
                                
                                let warehouseOptions = '';
                                if (data.warehouses && data.warehouses.length > 0) {
                                    data.warehouses.forEach(w => {
                                        const isDefault = (w.id == data.default_warehouse_id);
                                        const selected = isDefault ? 'selected' : '';
                                        const badge = isDefault ? ' ★ Padrão' : '';
                                        warehouseOptions += `<option value="${w.id}" ${selected}>${w.name}${badge}</option>`;
                                    });
                                }
                                
                                let itemsHtml = '';
                                let hasStockItems = false;
                                if (data.items && data.items.length > 0) {
                                    data.items.forEach(item => {
                                        if (item.use_stock_control) {
                                            hasStockItems = true;
                                            const icon = item.sufficient 
                                                ? '<i class="fas fa-check-circle text-success"></i>' 
                                                : '<i class="fas fa-exclamation-triangle text-danger"></i>';
                                            const label = item.combination_label ? `${item.product_name} — ${item.combination_label}` : item.product_name;
                                            const stockClass = item.sufficient ? 'text-success' : 'text-danger fw-bold';
                                            itemsHtml += `<tr>
                                                <td class="small">${icon} ${label}</td>
                                                <td class="text-center small">${item.quantity}</td>
                                                <td class="text-center small ${stockClass}">${item.stock_available}</td>
                                            </tr>`;
                                        }
                                    });
                                }
                                
                                let html = '';
                                
                                if (warehouseOptions) {
                                    html += `<div class="mb-3 text-start">
                                        <label class="form-label small fw-bold"><i class="fas fa-warehouse me-1"></i>Armazém para dedução de estoque:</label>
                                        <select id="swalWarehouseSelect" class="form-select form-select-sm">${warehouseOptions}</select>
                                    </div>`;
                                }
                                
                                if (hasStockItems) {
                                    html += `<div class="text-start mb-2">
                                        <small class="fw-bold text-muted"><i class="fas fa-boxes me-1"></i>Itens com controle de estoque:</small>
                                    </div>
                                    <table class="table table-sm table-bordered mb-2" style="font-size:0.85rem;">
                                        <thead class="table-light"><tr><th>Produto</th><th class="text-center">Necessário</th><th class="text-center">Disponível</th></tr></thead>
                                        <tbody id="swalStockTableBody">${itemsHtml}</tbody>
                                    </table>`;
                                    
                                    if (data.all_from_stock) {
                                        html += `<div class="alert alert-success py-2 small mb-2">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <strong>Todos os itens possuem estoque suficiente!</strong> O estoque será deduzido automaticamente.
                                        </div>`;
                                    } else if (!data.all_from_stock) {
                                        html += `<div class="alert alert-warning py-2 small mb-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <small>Alguns itens não possuem estoque suficiente. A dedução será parcial (apenas itens com controle ativo e estoque).</small>
                                        </div>`;
                                    }
                                } else {
                                    html += `<div class="alert alert-light py-2 small mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <small>Nenhum item deste pedido possui controle de estoque ativo.</small>
                                    </div>`;
                                }
                                
                                if (!html) {
                                    html = `<p>${dir} para <strong>${stage}</strong>?</p>`;
                                }
                                
                                Swal.fire({
                                    title: `<i class="fas fa-warehouse me-2"></i>${dir} para ${stage}`,
                                    html: html,
                                    icon: hasStockItems ? undefined : 'question',
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#27ae60',
                                    width: (hasStockItems || warehouseOptions) ? '550px' : undefined,
                                    preConfirm: () => {
                                        const whSelect = document.getElementById('swalWarehouseSelect');
                                        return whSelect ? whSelect.value : null;
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        let url = new URL(href, window.location.origin);
                                        if (result.value) {
                                            url.searchParams.set('warehouse_id', result.value);
                                        }
                                        window.location.href = url.toString();
                                    }
                                });
                                
                                // Atualizar estoque ao mudar armazém
                                setTimeout(() => {
                                    const whSelect = document.getElementById('swalWarehouseSelect');
                                    if (whSelect) {
                                        whSelect.addEventListener('change', function() {
                                            const wid = this.value;
                                            fetch(`?page=pipeline&action=checkOrderStock&order_id=${orderId}&warehouse_id=${wid}`)
                                                .then(r => r.json())
                                                .then(d => {
                                                    if (d.success && d.items) {
                                                        const tbody = document.getElementById('swalStockTableBody');
                                                        if (tbody) {
                                                            let rows = '';
                                                            d.items.forEach(item => {
                                                                if (item.use_stock_control) {
                                                                    const icon = item.sufficient 
                                                                        ? '<i class="fas fa-check-circle text-success"></i>' 
                                                                        : '<i class="fas fa-exclamation-triangle text-danger"></i>';
                                                                    const label = item.combination_label ? `${item.product_name} — ${item.combination_label}` : item.product_name;
                                                                    const stockClass = item.sufficient ? 'text-success' : 'text-danger fw-bold';
                                                                    rows += `<tr>
                                                                        <td class="small">${icon} ${label}</td>
                                                                        <td class="text-center small">${item.quantity}</td>
                                                                        <td class="text-center small ${stockClass}">${item.stock_available}</td>
                                                                    </tr>`;
                                                                }
                                                            });
                                                            tbody.innerHTML = rows;
                                                        }
                                                    }
                                                });
                                        });
                                    }
                                }, 100);
                            })
                            .catch(err => {
                                Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível verificar o estoque.' });
                            });
                    }
                });
            } else if (productionStages.includes(currentStageKey) && preProductionStages.includes(targetStage)) {
                // Produção+ → Pré-produção: aviso que estoque será devolvido
                Swal.fire({
                    title: `<i class="fas fa-undo me-2 text-warning"></i>${dir} para ${stage}?`,
                    html: `<p>Ao retornar o pedido para <strong>${stage}</strong>, todos os produtos que foram deduzidos do estoque serão <strong>devolvidos automaticamente</strong> ao armazém.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar e Devolver Estoque',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#e67e22'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            } else {
                // Movimentação simples (sem lógica de estoque)
                Swal.fire({
                    title: dir + ' pedido?',
                    html: `${dir} para <strong>${stage}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#27ae60'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            }
        });
    });

    // Product combinations data — variável global usada como fallback pelo product-select2.js
    // (ex: preços customizados por tabela de preço do cliente)
    const productCombinations = <?= json_encode($productCombinations ?? []) ?>;

    // Auto-preencher preço ao selecionar produto (pipeline)
    const pipProductSelect = document.getElementById('pipProductSelect');
    const pipPriceInput = document.getElementById('pipPriceInput');
    const pipVariationWrap = document.getElementById('variationWrapPipeline');
    const pipVariationSelect = document.getElementById('pipVariationSelect');

    // Nota: A seleção de produto e preenchimento de preço/variações é feita pelo
    // product-select2.js via Select2 AJAX + API Node.js.
    // Mantemos apenas o handler de variação para override de preço.
    if (pipVariationSelect) {
        pipVariationSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.price && opt.dataset.price !== '') {
                pipPriceInput.value = parseFloat(opt.dataset.price).toFixed(2);
            }
        });
    }

    // Adicionar item via form dinâmico (evita nesting de forms)
    const btnAdd = document.getElementById('btnAddItemPipeline');
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            const productId = document.getElementById('pipProductSelect').value;
            const quantity = document.getElementById('pipQtyInput').value;
            const price = document.getElementById('pipPriceInput').value;

            if (!productId || !quantity || !price) {
                Swal.fire({ icon: 'warning', title: 'Preencha todos os campos', timer: 2000, showConfirmButton: false });
                return;
            }

            // Get variation data
            const varSelect = document.getElementById('pipVariationSelect');
            let combinationId = '';
            let gradeDescription = '';
            if (varSelect && varSelect.value) {
                combinationId = varSelect.value;
                const varOpt = varSelect.options[varSelect.selectedIndex];
                gradeDescription = varOpt ? (varOpt.dataset.label || '') : '';
            }

            // Criar form dinamicamente e submeter
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=orders&action=addItem';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="${__csrfToken}">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="${quantity}">
                <input type="hidden" name="unit_price" value="${price}">
                <input type="hidden" name="combination_id" value="${combinationId}">
                <input type="hidden" name="grade_description" value="${gradeDescription}">
                <input type="hidden" name="redirect" value="pipeline">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }

    // Confirmar remoção de item
    document.querySelectorAll('.btn-delete-item').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Remover item?',
                text: 'O item será removido do orçamento.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => { if (r.isConfirmed) window.location.href = href; });
        });
    });

    // Adicionar custo extra via form dinâmico
    const btnAddExtra = document.getElementById('btnAddExtraCost');
    if (btnAddExtra) {
        btnAddExtra.addEventListener('click', function() {
            const description = document.getElementById('extraDescription').value.trim();
            const amount = document.getElementById('extraAmount').value;

            if (!description || !amount || parseFloat(amount) === 0) {
                Swal.fire({ icon: 'warning', title: 'Preencha a descrição e o valor', text: 'O valor não pode ser zero.', timer: 2000, showConfirmButton: false });
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=pipeline&action=addExtraCost';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="${__csrfToken}">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="extra_description" value="${description}">
                <input type="hidden" name="extra_amount" value="${amount}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }

    // Confirmar remoção de custo extra
    document.querySelectorAll('.btn-delete-extra').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Remover custo extra?',
                text: 'Este custo será removido do orçamento.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => { if (r.isConfirmed) window.location.href = href; });
        });
    });

    // ═══ DESCONTO POR ITEM — Salvar via AJAX ao alterar ═══
    (function() {
        let discountTimers = {};
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfVal = csrfMeta ? csrfMeta.getAttribute('content') : '';

        document.querySelectorAll('.item-discount-input').forEach(input => {
            input.addEventListener('input', function() {
                const itemId = this.dataset.itemId;
                const subtotal = parseFloat(this.dataset.subtotal) || 0;
                let discount = parseFloat(this.value) || 0;
                if (discount < 0) discount = 0;
                if (discount > subtotal) { discount = subtotal; this.value = discount.toFixed(2); }

                const netAmount = subtotal - discount;
                const row = this.closest('tr');
                const netCell = row ? row.querySelector('.item-net-amount') : null;
                if (netCell) {
                    netCell.textContent = 'R$ ' + netAmount.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                    netCell.classList.toggle('text-success', discount > 0);
                }

                // Recalcular totais do footer
                recalcItemDiscountTotals();

                // Debounce: salvar após 800ms sem digitação
                clearTimeout(discountTimers[itemId]);
                discountTimers[itemId] = setTimeout(() => {
                    saveItemDiscount(itemId, discount);
                }, 800);
            });

            // Salvar ao sair do campo (blur)
            input.addEventListener('blur', function() {
                const itemId = this.dataset.itemId;
                const discount = parseFloat(this.value) || 0;
                clearTimeout(discountTimers[itemId]);
                saveItemDiscount(itemId, discount);
            });
        });

        function saveItemDiscount(itemId, discount) {
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('discount', discount);
            formData.append('csrf_token', csrfVal);

            fetch('?page=orders&action=updateItemDiscount', {
                method: 'POST',
                body: formData
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    // Exibir feedback sutil
                    const input = document.querySelector('.item-discount-input[data-item-id="' + itemId + '"]');
                    if (input) {
                        input.classList.add('border-success');
                        setTimeout(() => input.classList.remove('border-success'), 1500);
                    }
                    // Atualizar totalAmount no card financeiro e regenerar parcelas
                    if (data.new_total !== undefined && typeof window.aktiUpdateFinancialTotal === 'function') {
                        window.aktiUpdateFinancialTotal(data.new_total);
                    }
                }
            })
            .catch(err => {
                console.error('Erro ao salvar desconto:', err);
            });
        }

        function recalcItemDiscountTotals() {
            let totalDiscounts = 0;
            let totalSubtotals = 0;
            document.querySelectorAll('.item-discount-input').forEach(inp => {
                const discount = parseFloat(inp.value) || 0;
                const subtotal = parseFloat(inp.dataset.subtotal) || 0;
                totalDiscounts += discount;
                totalSubtotals += subtotal;
            });

            const totalDiscountsEl = document.getElementById('totalItemDiscounts');
            const totalNetEl = document.getElementById('totalNetAmount');
            if (totalDiscountsEl) {
                totalDiscountsEl.textContent = totalDiscounts > 0
                    ? '- R$ ' + totalDiscounts.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})
                    : '';
            }
            if (totalNetEl) {
                const net = totalSubtotals - totalDiscounts;
                totalNetEl.textContent = 'R$ ' + net.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
            }
        }
    })();

    // ═══ QUANTIDADE POR ITEM — Salvar via AJAX ao alterar ═══
    (function() {
        let qtyTimers = {};
        const csrfMeta2 = document.querySelector('meta[name="csrf-token"]');
        const csrfVal2 = csrfMeta2 ? csrfMeta2.getAttribute('content') : '';

        document.querySelectorAll('.item-qty-input').forEach(input => {
            input.addEventListener('input', function() {
                const itemId = this.dataset.itemId;
                const unitPrice = parseFloat(this.dataset.unitPrice) || 0;
                let qty = parseInt(this.value) || 1;
                if (qty < 1) { qty = 1; this.value = 1; }

                const newSubtotal = qty * unitPrice;
                const row = this.closest('tr');
                // Update subtotal cell (4th td)
                const subtotalCell = row ? row.querySelectorAll('td')[3] : null;
                if (subtotalCell) {
                    subtotalCell.textContent = 'R$ ' + newSubtotal.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                }
                // Update discount input max and recalculate net
                const discountInput = row ? row.querySelector('.item-discount-input') : null;
                if (discountInput) {
                    discountInput.setAttribute('max', newSubtotal);
                    discountInput.dataset.subtotal = newSubtotal;
                    const discount = parseFloat(discountInput.value) || 0;
                    const netCell = row.querySelector('.item-net-amount');
                    if (netCell) {
                        const net = newSubtotal - discount;
                        netCell.textContent = 'R$ ' + net.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                }
                recalcItemDiscountTotals();

                // Debounce: salvar após 800ms
                clearTimeout(qtyTimers[itemId]);
                qtyTimers[itemId] = setTimeout(() => {
                    saveItemQty(itemId, qty);
                }, 800);
            });

            input.addEventListener('blur', function() {
                const itemId = this.dataset.itemId;
                let qty = parseInt(this.value) || 1;
                if (qty < 1) { qty = 1; this.value = 1; }
                clearTimeout(qtyTimers[itemId]);
                saveItemQty(itemId, qty);
            });
        });

        function saveItemQty(itemId, qty) {
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('quantity', qty);
            formData.append('csrf_token', csrfVal2);

            fetch('?page=orders&action=updateItemQty', {
                method: 'POST',
                body: formData
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    const input = document.querySelector('.item-qty-input[data-item-id="' + itemId + '"]');
                    if (input) {
                        input.classList.add('border-success');
                        setTimeout(() => input.classList.remove('border-success'), 1500);
                    }
                    // Atualizar totalAmount no card financeiro e regenerar parcelas
                    if (data.new_total !== undefined && typeof window.aktiUpdateFinancialTotal === 'function') {
                        window.aktiUpdateFinancialTotal(data.new_total);
                    }
                }
            })
            .catch(err => {
                console.error('Erro ao salvar quantidade:', err);
            });
        }

        // Reuse the recalc function from discount block
        function recalcItemDiscountTotals() {
            let totalDiscounts = 0;
            let totalSubtotals = 0;
            document.querySelectorAll('.item-discount-input').forEach(inp => {
                const discount = parseFloat(inp.value) || 0;
                const subtotal = parseFloat(inp.dataset.subtotal) || 0;
                totalDiscounts += discount;
                totalSubtotals += subtotal;
            });
            // Also sum subtotals from qty inputs (for rows without discount input)
            document.querySelectorAll('.item-qty-input').forEach(inp => {
                const row = inp.closest('tr');
                const discountInput = row ? row.querySelector('.item-discount-input') : null;
                if (!discountInput) {
                    const unitPrice = parseFloat(inp.dataset.unitPrice) || 0;
                    const qty = parseInt(inp.value) || 1;
                    totalSubtotals += qty * unitPrice;
                }
            });
            const totalDiscountsEl = document.getElementById('totalItemDiscounts');
            const totalNetEl = document.getElementById('totalNetAmount');
            if (totalDiscountsEl) {
                totalDiscountsEl.textContent = totalDiscounts > 0
                    ? '- R$ ' + totalDiscounts.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})
                    : '';
            }
            if (totalNetEl) {
                const net = totalSubtotals - totalDiscounts;
                totalNetEl.textContent = 'R$ ' + net.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
            }
        }
    })();

    // ── Seletor de Tabela de Preços: atualizar preços ao mudar ──
    const priceTableSelect = document.getElementById('priceTableSelect');
    if (priceTableSelect) {
        priceTableSelect.addEventListener('change', function() {
            const tableId = this.value;
            const customerId = '<?= $order['customer_id'] ?? '' ?>';
            let url = '?page=pipeline&action=getPricesByTable';
            
            if (tableId) {
                url += '&table_id=' + tableId;
            } else if (customerId) {
                url += '&customer_id=' + customerId;
            }

            fetch(url)
                .then(r => r.json())
                .then(prices => {
                    // Atualizar opções do select de produtos
                    const productSelect = document.getElementById('pipProductSelect');
                    if (productSelect) {
                        Array.from(productSelect.options).forEach(opt => {
                            if (opt.value) {
                                const pid = opt.value;
                                const origPrice = parseFloat(opt.dataset.originalPrice) || 0;
                                const newPrice = prices[pid] !== undefined ? parseFloat(prices[pid]) : origPrice;
                                opt.dataset.price = newPrice.toFixed(2);
                                
                                // Atualizar texto da opção
                                const prodName = opt.textContent.split(' — ')[0].trim();
                                let label = prodName + ' — R$ ' + newPrice.toFixed(2).replace('.', ',');
                                if (newPrice !== origPrice) {
                                    label += ' (base: R$ ' + origPrice.toFixed(2).replace('.', ',') + ')';
                                }
                                opt.textContent = label;
                            }
                        });
                        // Atualizar preço se já havia um produto selecionado
                        if (productSelect.value) {
                            const selOpt = productSelect.options[productSelect.selectedIndex];
                            if (selOpt && selOpt.dataset.price) {
                                document.getElementById('pipPriceInput').value = parseFloat(selOpt.dataset.price).toFixed(2);
                            }
                        }
                    }

                    Swal.fire({ 
                        icon: 'info', 
                        title: 'Tabela atualizada!', 
                        text: 'Os preços dos produtos foram atualizados.',
                        timer: 1500, 
                        showConfirmButton: false 
                    });
                })
                .catch(err => {
                    console.error('Erro ao buscar preços:', err);
                    Swal.fire({ icon: 'error', title: 'Erro ao atualizar preços', timer: 2000, showConfirmButton: false });
                });
        });
    }

    // ════════════════════════════════════════════════════════
    // ── CATÁLOGO DO CLIENTE — Geração e gestão de links ──
    // ════════════════════════════════════════════════════════
    
    let catalogLinkData = null;
    
    // Verificar se já existe link ativo para este pedido
    function checkExistingCatalogLink() {
        fetch('?page=pipeline&action=getCatalogLink&order_id=<?= $order['id'] ?>')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    catalogLinkData = data;
                    showActiveCatalogLink(data);
                } else {
                    showCatalogLinkForm();
                }
            })
            .catch(() => showCatalogLinkForm());
    }
    
    // Inicializar verificação do link
    if (document.getElementById('catalogLinkSection')) {
        checkExistingCatalogLink();
        
        // Toggle da dica de confirmação
        const confirmSelect = document.getElementById('catalogRequireConfirmation');
        const confirmHint = document.getElementById('catalogConfirmHint');
        if (confirmSelect && confirmHint) {
            confirmSelect.addEventListener('change', function() {
                confirmHint.style.display = this.value === '1' ? 'block' : 'none';
                confirmHint.style.setProperty('display', this.value === '1' ? 'block' : 'none', 'important');
            });
        }
    }
});

// ── Funções globais do catálogo (fora do DOMContentLoaded) ──

function generateCatalogLink() {
    const requireConfirmation = document.getElementById('catalogRequireConfirmation').value;
    const showPrices = requireConfirmation === '1' ? '1' : '0'; // Se requer confirmação, mostrar preços
    const expiresIn = document.getElementById('catalogExpires').value;
    const btn = document.getElementById('btnGenerateCatalog');
    
    // Desabilitar botão enquanto gera
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando...';
    
    fetch('?page=pipeline&action=generateCatalogLink', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=<?= $order['id'] ?>&show_prices=${showPrices}&require_confirmation=${requireConfirmation}&expires_in=${expiresIn}&csrf_token=${encodeURIComponent(__csrfToken)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            catalogLinkData = data;
            showActiveCatalogLink(data);
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao gerar link', timer: 3000, showConfirmButton: true });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Erro de conexão', text: 'Não foi possível gerar o link do catálogo.', timer: 3000, showConfirmButton: true });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic me-1"></i> Gerar Link do Catálogo';
    });
}

function copyCatalogLink() {
    const url = document.getElementById('catalogLinkUrl').value;
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({ icon: 'success', title: 'Link copiado!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
    });
}

function shareViaWhatsApp() {
    const url = document.getElementById('catalogLinkUrl').value;
    const phone = '<?= preg_replace('/\D/', '', $order['customer_phone'] ?? '') ?>';
    const customerName = '<?= e($order['customer_name'] ?? 'cliente') ?>';
    const companyName = 'nossa equipe';
    
    const message = encodeURIComponent(
        `Olá, ${customerName}! 😊\n\n` +
        `Preparamos um catálogo personalizado para você montar sua lista de produtos:\n\n` +
        `📋 *Acesse o catálogo:*\n${url}\n\n` +
        `Você pode adicionar os produtos que desejar ao carrinho. Depois, ${companyName} irá preparar o orçamento completo!\n\n` +
        `Qualquer dúvida, estamos à disposição! 🙌`
    );
    
    const waUrl = phone 
        ? `https://wa.me/55${phone}?text=${message}` 
        : `https://wa.me/?text=${message}`;
    
    window.open(waUrl, '_blank');
}

function deactivateCatalogLink() {
    Swal.fire({
        title: 'Desativar link?',
        text: 'O cliente não poderá mais acessar o catálogo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-ban me-1"></i> Desativar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e74c3c'
    }).then(function(result) {
        if (!result.isConfirmed) return;
    
        fetch('?page=pipeline&action=deactivateCatalogLink', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=<?= $order['id'] ?>&csrf_token=' + encodeURIComponent(__csrfToken)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showCatalogLinkForm();
                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true })
                    .fire({ icon: 'success', title: 'Link desativado!' });
            }
        });
    });
}

function showActiveCatalogLink(data) {
    const activeEl = document.getElementById('catalogLinkActive');
    const formEl = document.getElementById('catalogLinkForm');
    if (!activeEl || !formEl) return;
    
    // Mostrar o link abaixo do formulário
    activeEl.style.display = '';
    
    // Desabilitar campos do formulário (já tem link ativo)
    document.getElementById('catalogRequireConfirmation').disabled = true;
    document.getElementById('catalogExpires').disabled = true;
    const btn = document.getElementById('btnGenerateCatalog');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Link ativo';
    btn.classList.replace('btn-info', 'btn-success');
    
    document.getElementById('catalogLinkUrl').value = data.url;
    document.getElementById('catalogLinkOpen').href = data.url;
    
    const priceInfo = document.getElementById('catalogLinkPriceInfo');
    if (data.require_confirmation) {
        priceInfo.textContent = '(com confirmação de orçamento)';
    } else if (data.show_prices) {
        priceInfo.textContent = '(com preços)';
    } else {
        priceInfo.textContent = '(sem preços)';
    }
    
    const meta = document.getElementById('catalogLinkMeta');
    let metaText = '';
    if (data.created_at) {
        metaText = 'Criado em ' + formatDateBR(data.created_at);
    }
    if (data.expires_at) {
        metaText += ' · Expira em ' + formatDateBR(data.expires_at);
    } else {
        metaText += ' · Sem expiração';
    }
    meta.textContent = metaText;
}

function showCatalogLinkForm() {
    const activeEl = document.getElementById('catalogLinkActive');
    const formEl = document.getElementById('catalogLinkForm');
    if (!activeEl || !formEl) return;
    
    // Esconder o link e reabilitar o formulário
    activeEl.style.display = 'none';
    
    document.getElementById('catalogRequireConfirmation').disabled = false;
    document.getElementById('catalogExpires').disabled = false;
    const btn = document.getElementById('btnGenerateCatalog');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic me-1"></i> Gerar Link do Catálogo';
    btn.classList.replace('btn-success', 'btn-info');
}

function formatDateBR(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

// ════════════════════════════════════════════════════════════
// ═══ REGISTRO (Logs dos Produtos) — AJAX Form + Delete ═══
// ════════════════════════════════════════════════════════════
(function() {
    // Mostrar nome do arquivo selecionado
    var detailLogFile = document.getElementById('detailLogFile');
    if (detailLogFile) {
        detailLogFile.addEventListener('change', function() {
            var label = document.getElementById('detailLogFileLabel');
            if (this.files.length > 0) {
                label.textContent = this.files[0].name;
                label.classList.remove('d-none');
            } else {
                label.classList.add('d-none');
            }
        });
    }

    // Enviar novo log (AJAX com upload) — suporta "todos os produtos"
    var formDetail = document.getElementById('formAddItemLogDetail');
    if (formDetail) {
        formDetail.addEventListener('submit', function(e) {
            e.preventDefault();
            var itemSelect = document.getElementById('detailLogItemSelect');
            var submitBtn = this.querySelector('button[type="submit"]');

            if (!itemSelect.value) {
                Swal.fire({ icon: 'warning', title: 'Selecione um produto', timer: 2000, showConfirmButton: false });
                return;
            }

            var formData = new FormData(this);
            var msg = formData.get('message') || '';
            var file = formData.get('file');
            if (!msg.trim() && (!file || !file.size)) {
                Swal.fire({ icon: 'warning', title: 'Informe uma mensagem ou arquivo', timer: 2000, showConfirmButton: false });
                return;
            }

            // Se "all" foi selecionado, enviar para todos os itens
            if (itemSelect.value === 'all') {
                var itemOptions = itemSelect.querySelectorAll('option[value]:not([value=""]):not([value="all"])');
                var itemIds = [];
                itemOptions.forEach(function(opt) { itemIds.push(opt.value); });
                if (itemIds.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Nenhum produto no pedido', timer: 2000, showConfirmButton: false });
                    return;
                }
                formData.delete('order_item_id');
                itemIds.forEach(function(id) { formData.append('order_item_ids[]', id); });
                formData.append('all_items', '1');
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';

            formData.append('csrf_token', __csrfToken);
            fetch('?page=pipeline&action=addItemLog', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus me-1"></i> Adicionar';
                if (data.success) {
                    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true })
                        .fire({ icon: 'success', title: 'Registro adicionado!' });
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível adicionar.', timer: 3000 });
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus me-1"></i> Adicionar';
                Swal.fire({ icon: 'error', title: 'Erro de conexão', timer: 2000, showConfirmButton: false });
            });
        });
    }

    // Excluir log de produto (detail view)
    document.querySelectorAll('.btn-delete-detail-log').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var logId = this.dataset.logId;
            Swal.fire({
                title: 'Excluir registro?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    fetch('?page=pipeline&action=deleteItemLog', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'log_id=' + logId + '&csrf_token=' + encodeURIComponent(__csrfToken)
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true })
                                .fire({ icon: 'success', title: 'Registro excluído!' });
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível excluir.', timer: 2000 });
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Erro de conexão', timer: 2000, showConfirmButton: false });
                    });
                }
            });
        });
    });
})();    // ═══ PREPARO — Checklist AJAX toggle ═══
    document.querySelectorAll('.prep-check-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var key = this.dataset.key;
            if (!key) return;
            var cardEl = this;
            Swal.fire({
                title: 'Confirmar etapa?',
                html: 'Deseja alternar o status desta etapa do preparo?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#1abc9c'
            }).then(function(result) {
                if (result.isConfirmed) {
                    fetch('?page=pipeline&action=togglePreparation', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'order_id=<?= $order['id'] ?>&key=' + encodeURIComponent(key) + '&csrf_token=' + encodeURIComponent(__csrfToken)
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1200, timerProgressBar: true })
                                .fire({ icon: 'success', title: data.checked ? 'Etapa confirmada!' : 'Etapa desmarcada!' });
                            setTimeout(function() { location.reload(); }, 600);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível atualizar.', timer: 2000 });
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Erro de conexão', timer: 2000, showConfirmButton: false });
                    });
                }
            });
        });
    });

    <?php if ($currentStage === 'producao' && !empty($orderProductionSectors)): ?>
// ════════════════════════════════════════════════════════════
// ═══ CONTROLE DE PRODUÇÃO POR PRODUTO — Stepper + AJAX ═══
// ════════════════════════════════════════════════════════════
window.addEventListener('load', function() {
    // Inicializar tooltips do Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(el);
        }
    });

    // Botões de ação (Concluir / Retroceder setor)
    document.querySelectorAll('.btn-sector-action').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const itemId = this.dataset.itemId;
            const sectorId = this.dataset.sectorId;
            const action = this.dataset.action;
            const sectorName = this.dataset.sectorName;
            const btnEl = this;

            const isRevert = (action === 'revert');
            var confirmTitle, confirmText, confirmIcon, confirmBtn, confirmColor;

            if (isRevert) {
                confirmTitle = 'Retroceder setor?';
                confirmText = `Deseja retroceder o setor <strong>${sectorName}</strong>?<br><small class="text-muted">O progresso deste setor será revertido.</small>`;
                confirmIcon = 'warning';
                confirmBtn = '<i class="fas fa-undo me-1"></i> Retroceder';
                confirmColor = '#e67e22';
            } else {
                confirmTitle = 'Concluir setor?';
                confirmText = `Marcar <strong>${sectorName}</strong> como concluído?`;
                confirmIcon = 'success';
                confirmBtn = '<i class="fas fa-check me-1"></i> Concluir';
                confirmColor = '#27ae60';
            }

            Swal.fire({
                title: confirmTitle,
                html: confirmText,
                icon: confirmIcon,
                showCancelButton: true,
                confirmButtonText: confirmBtn,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: confirmColor
            }).then((result) => {
                if (result.isConfirmed) {
                    btnEl.disabled = true;
                    btnEl.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processando...';

                    fetch('?page=pipeline&action=moveSector', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `order_id=${orderId}&order_item_id=${itemId}&sector_id=${sectorId}&move_action=${action}&csrf_token=${encodeURIComponent(__csrfToken)}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Usar toast separado para não fechar/conflitar com outros Swals
                            var toastMixin = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didOpen: function(toast) {
                                    toast.addEventListener('mouseenter', Swal.stopTimer);
                                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                                }
                            });
                            toastMixin.fire({
                                icon: 'success',
                                title: isRevert ? 'Setor retrocedido!' : 'Setor concluído!'
                            });
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            btnEl.disabled = false;
                            btnEl.innerHTML = isRevert 
                                ? '<i class="fas fa-undo me-1"></i> Retroceder' 
                                : '<i class="fas fa-check me-1"></i> Concluir';
                            Swal.fire({ 
                                icon: 'error', 
                                title: 'Erro', 
                                text: data.message || 'Não foi possível processar.',
                                timer: 3000 
                            });
                        }
                    })
                    .catch(function(err) {
                        btnEl.disabled = false;
                        console.error('Erro:', err);
                        Swal.fire({ icon: 'error', title: 'Erro de conexão', timer: 2000, showConfirmButton: false });
                    });
                }
            });
        });
    });
});
<?php endif; ?>

<?php if ($currentStage === 'orcamento'): ?>
// ── Auto-refresh: recarregar a página se itens mudarem (polling a cada 15s) ──
let lastItemCount = <?= count($orderItems ?? []) ?>;
let catalogPollingToken = null;

// Primeiro buscar o token do link ativo (se houver)
fetch('?page=pipeline&action=getCatalogLink&order_id=<?= $order['id'] ?>')
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            catalogPollingToken = data.token;
        }
    })
    .catch(() => {});

setInterval(() => {
    if (!catalogPollingToken) return;
    fetch('?page=catalog&action=getCart&token=' + catalogPollingToken)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.cart_count !== lastItemCount) {
                lastItemCount = data.cart_count;
                location.reload();
            }
        })
        .catch(() => {});
}, 15000);
<?php endif; ?>

<?php if ($showFinancial): ?>
// ═══ FINANCEIRO — Lógica do card financeiro ═══
(function() {
    const paymentMethod = document.getElementById('finPaymentMethod');
    const installmentRow = document.getElementById('installmentRow');
    const installments = document.getElementById('finInstallments');
    const installmentValue = document.getElementById('finInstallmentValue');
    const installmentValueHidden = document.getElementById('finInstallmentValueHidden');
    const installmentInfo = document.getElementById('installmentInfo');
    const installmentInfoText = document.getElementById('installmentInfoText');
    const discountField = document.getElementById('finDiscount');
    const downPaymentField = document.getElementById('finDownPayment');
    const boletoTable = document.getElementById('boletoInstallmentTable');
    const boletoTableBody = document.querySelector('#boletoTableBody tbody');
    
    if (!paymentMethod || !installmentRow) return;
    
    let totalAmount = <?= (float)($order['total_amount'] ?? 0) ?>;
    const cardTitleText = document.getElementById('installmentCardTitleText');

    // Expor função para atualizar totalAmount de fora do closure (ex: após alterar desconto de item)
    window.aktiUpdateFinancialTotal = function(newTotal) {
        totalAmount = parseFloat(newTotal) || 0;
        var formattedTotal = totalAmount.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        // Atualizar display do valor total no card financeiro
        var totalDisplay = document.getElementById('finTotalDisplay');
        if (totalDisplay) {
            totalDisplay.value = formattedTotal;
        }
        // Atualizar totais de resumo (items summary)
        document.querySelectorAll('.fin-order-total').forEach(function(el) {
            el.innerHTML = '<i class="fas fa-coins me-1"></i>Total: R$ ' + formattedTotal;
        });
        calcInstallment();
        updateDownPaymentInfo();
        // Só sincronizar se já existe configuração de parcelas definida
        if (installmentsConfigured()) {
            scheduleSyncInstallments(500);
        }
    };
    
    // Formas de pagamento que aceitam parcelamento
    const parcelableMethods = ['cartao_credito','boleto'];
    
    /**
     * Verifica se a configuração de parcelas está completa para sincronizar.
     * Retorna true se:
     * - O método NÃO é parcelável (dinheiro, pix, etc.) — sempre pode sincronizar
     * - O método É parcelável e o dropdown de parcelas tem um valor explícito
     *   (vazio = "À vista" conta como configurado SE já houve interação ou carga inicial)
     * - Já existem parcelas salvas no banco para este pedido
     */
    function installmentsConfigured() {
        const method = paymentMethod ? paymentMethod.value : '';
        if (!method) return false;
        if (!parcelableMethods.includes(method)) return true; // não-parcelável: sempre OK
        // Parcelável: precisa que o usuário já tenha definido as parcelas
        if (installmentsUserTouched) return true;
        if (existingInstallmentCount > 0) return true;
        // Se tem entrada definida, considerar como configurado para permitir sync
        const dp = parseFloat(downPaymentField ? downPaymentField.value : 0) || 0;
        if (dp > 0) return true;
        return false;
    }
    
    function updateCardTitle() {
        if (!cardTitleText) return;
        const method = paymentMethod.value;
        const n = parseInt(installments ? installments.value : 0) || 0;
        const isParcelable = parcelableMethods.includes(method);
        const notYetConfigured = isParcelable && (!installments || installments.value === '') && !installmentsUserTouched && existingInstallmentCount === 0;
        
        if (notYetConfigured) {
            cardTitleText.textContent = method === 'boleto' ? 'Boleto Bancário' : 'Parcelamento';
        } else if (method === 'boleto' && n < 2) {
            cardTitleText.textContent = 'Boleto Bancário — À Vista';
        } else if (method === 'boleto' && n >= 2) {
            cardTitleText.textContent = 'Boleto Bancário — Parcelado em ' + n + 'x';
        } else if (method === 'cartao_credito' && n >= 2) {
            cardTitleText.textContent = 'Parcelamento — ' + n + 'x no Cartão';
        } else {
            cardTitleText.textContent = 'Pagamento';
        }
    }
    
    function toggleInstallmentRow() {
        const show = parcelableMethods.includes(paymentMethod.value);
        installmentRow.style.display = show ? '' : 'none';
        if (!show) {
            if (installments) installments.value = '';
            if (installmentValue) installmentValue.value = '';
            if (installmentValueHidden) installmentValueHidden.value = '';
            if (installmentInfo) installmentInfo.style.display = 'none';
            if (boletoTable) boletoTable.style.display = 'none';
        } else {
            calcInstallment();
        }
        updateCardTitle();
        updateDownPaymentInfo();
    }
    
    /**
     * Atualiza informações visuais do campo de entrada (sempre visível)
     */
    function updateDownPaymentInfo() {
        const dpInfo = document.getElementById('downPaymentInfo');
        const dpInfoText = document.getElementById('downPaymentInfoText');
        if (!dpInfo || !dpInfoText) return;
        
        const dp = parseFloat(downPaymentField ? downPaymentField.value : 0) || 0;
        const discount = parseFloat(discountField ? discountField.value : 0) || 0;
        const finalTotal = Math.max(0, totalAmount - discount);
        
        if (dp > 0 && finalTotal > 0) {
            const remaining = Math.max(0, finalTotal - dp);
            dpInfo.style.display = '';
            dpInfoText.textContent = 'Entrada de R$ ' + dp.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ' — Restante: R$ ' + remaining.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        } else {
            dpInfo.style.display = 'none';
        }
    }
    
    function calcInstallment() {
        const n = parseInt(installments ? installments.value : 0) || 0;
        const discount = parseFloat(discountField ? discountField.value : 0) || 0;
        const downPayment = parseFloat(downPaymentField ? downPaymentField.value : 0) || 0;
        const finalTotal = Math.max(0, totalAmount - discount);
        const amountAfterDown = Math.max(0, finalTotal - downPayment);
        const isBoleto = (paymentMethod.value === 'boleto');
        const isParcelable = parcelableMethods.includes(paymentMethod.value);
        
        // Parcelas "não selecionadas": método parcelável, dropdown vazio, e o usuário ainda não
        // interagiu com o dropdown nesta sessão (nem existiam parcelas salvas ao carregar a página).
        const awaitingUserSelection = isParcelable && (!installments || installments.value === '') && !installmentsUserTouched;
        
        updateCardTitle();
        
        if (n >= 2 && finalTotal > 0) {
            // Parcelamento explícito (2x a 12x)
            const perInstallment = (amountAfterDown / n).toFixed(2);
            if (installmentValue) installmentValue.value = parseFloat(perInstallment).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (installmentValueHidden) installmentValueHidden.value = perInstallment;
            if (installmentInfo) {
                installmentInfo.style.display = '';
                var infoText = '';
                if (downPayment > 0) {
                    infoText = `Entrada: R$ ${downPayment.toLocaleString('pt-BR', {minimumFractionDigits: 2})} + ${n}x de R$ ${parseFloat(perInstallment).toLocaleString('pt-BR', {minimumFractionDigits: 2})} = R$ ${finalTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                } else {
                    infoText = `${n}x de R$ ${parseFloat(perInstallment).toLocaleString('pt-BR', {minimumFractionDigits: 2})} = R$ ${finalTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                }
                if (installmentInfoText) installmentInfoText.textContent = infoText;
            }
            renderBoletoTable(n, parseFloat(perInstallment), downPayment);
        } else if (awaitingUserSelection && finalTotal > 0) {
            // Método parcelável acabou de ser selecionado mas nº de parcelas ainda não foi definido.
            // Exibir mensagem orientativa e NÃO renderizar tabela nem sincronizar.
            if (installmentValue) installmentValue.value = finalTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (installmentValueHidden) installmentValueHidden.value = '';
            if (installmentInfo) {
                installmentInfo.style.display = '';
                if (installmentInfoText) installmentInfoText.textContent = 'Selecione o número de parcelas ou deixe "À vista" para pagamento único.';
            }
            if (boletoTable) boletoTable.style.display = 'none';
        } else if (isBoleto && finalTotal > 0) {
            // Boleto à vista (usuário já interagiu ou carga com dados existentes) — 1 parcela
            if (installmentValue) installmentValue.value = amountAfterDown.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (installmentValueHidden) installmentValueHidden.value = amountAfterDown.toFixed(2);
            if (installmentInfo && downPayment > 0) {
                installmentInfo.style.display = '';
                if (installmentInfoText) installmentInfoText.textContent = `Entrada: R$ ${downPayment.toLocaleString('pt-BR', {minimumFractionDigits: 2})} + 1x de R$ ${amountAfterDown.toLocaleString('pt-BR', {minimumFractionDigits: 2})} = R$ ${finalTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            } else if (installmentInfo) {
                installmentInfo.style.display = '';
                if (installmentInfoText) installmentInfoText.textContent = `1x de R$ ${finalTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})} (à vista)`;
            }
            renderBoletoTable(1, amountAfterDown, downPayment);
        } else {
            if (installmentValue) installmentValue.value = finalTotal > 0 ? finalTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
            if (installmentValueHidden) installmentValueHidden.value = finalTotal > 0 ? finalTotal.toFixed(2) : '';
            if (installmentInfo) installmentInfo.style.display = 'none';
            if (boletoTable) boletoTable.style.display = 'none';
        }
    }

    function renderBoletoTable(numParcelas, valorParcela, entrada) {
        if (!boletoTable || !boletoTableBody) return;
        if (paymentMethod.value !== 'boleto') {
            boletoTable.style.display = 'none';
            return;
        }
        boletoTable.style.display = '';
        boletoTableBody.innerHTML = '';

        if (entrada > 0) {
            var today = new Date();
            var trEntry = document.createElement('tr');
            trEntry.classList.add('table-success');
            trEntry.innerHTML = `
                <td class="fw-bold text-success"><i class="fas fa-hand-holding-usd me-1"></i>Entrada</td>
                <td><input type="date" class="form-control form-control-sm boleto-date" value="${today.toISOString().split('T')[0]}" style="max-width:160px;"></td>
                <td class="text-end fw-bold">R$ ${entrada.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                <td class="text-center"><span class="badge bg-warning" style="font-size:0.65rem;">⏳ Pendente</span></td>`;
            boletoTableBody.appendChild(trEntry);
        }

        for (var i = 1; i <= numParcelas; i++) {
            var dueDate = new Date();
            if (numParcelas === 1 && entrada <= 0) {
                // À vista sem entrada: vencimento em 3 dias úteis
                dueDate.setDate(dueDate.getDate() + 3);
            } else {
                dueDate.setDate(dueDate.getDate() + (i * 30));
            }
            var tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${numParcelas === 1 ? 'Única' : i + 'ª'}</td>
                <td><input type="date" class="form-control form-control-sm boleto-date" value="${dueDate.toISOString().split('T')[0]}" style="max-width:160px;" name="boleto_due_${i}"></td>
                <td class="text-end fw-bold">R$ ${valorParcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                <td class="text-center"><span class="badge bg-warning" style="font-size:0.65rem;">⏳ Pendente</span></td>`;
            boletoTableBody.appendChild(tr);
        }
    }
    
    // Rastrear a forma de pagamento anterior para poder reverter se o usuário cancelar
    let previousPaymentMethod = paymentMethod.value;
    let previousInstallments = installments ? installments.value : '';
    let existingInstallmentCount = <?= (int)($existingInstallmentCount ?? 0) ?>;
    let hasAnyPaidInstallment = <?= !empty($hasAnyPaidInstallment) ? 'true' : 'false' ?>;
    const orderId = <?= (int)$order['id'] ?>;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let syncTimer = null;
    let syncVersion = 0;        // Monotonic counter — incremented on every schedule, checked on response
    let syncAbortCtrl = null;    // AbortController for in-flight fetch — aborted when a new sync starts
    // Flag que indica se o dropdown de parcelas foi alterado pelo usuário nesta sessão de página.
    // No carregamento, se já existem parcelas salvas, consideramos como "já configurado".
    // Ao trocar para um método parcelável vindo de não-parcelável, reseta para false.
    let installmentsUserTouched = (parcelableMethods.includes(paymentMethod.value) && existingInstallmentCount > 0)
        || (parcelableMethods.includes(paymentMethod.value) && installments && installments.value !== '');

    /**
     * Bloqueia campos financeiros quando há parcelas pagas
     */
    function lockFinancialFieldsIfPaid() {
        if (!hasAnyPaidInstallment) return;
        // Desabilitar campos de forma de pagamento, parcelas e entrada
        if (paymentMethod) paymentMethod.disabled = true;
        if (installments) installments.disabled = true;
        if (downPaymentField) downPaymentField.disabled = true;
        // Mostrar alerta inline
        var lockAlert = document.getElementById('finPaidLockAlert');
        if (!lockAlert) {
            lockAlert = document.createElement('div');
            lockAlert.id = 'finPaidLockAlert';
            lockAlert.className = 'alert alert-warning py-2 px-3 mt-2 mb-0 small';
            lockAlert.innerHTML = '<i class="fas fa-lock me-2"></i><strong>Campos bloqueados:</strong> Existem parcelas já pagas. Para alterar a forma de pagamento ou o parcelamento, estorne os pagamentos primeiro no módulo Financeiro. <a target="_blank" href="?page=financial&action=payments"><strong>Acesse Aqui</strong></a>';
            // Inserir após a row de forma de pagamento
            var parentRow = paymentMethod ? paymentMethod.closest('.row') : null;
            if (parentRow) {
                parentRow.parentNode.insertBefore(lockAlert, parentRow.nextSibling);
            }
        }
    }

    /**
     * Indicador visual de sincronização no card financeiro
     */
    function showSyncStatus(status, message) {
        let badge = document.getElementById('finSyncBadge');
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'finSyncBadge';
            badge.style.cssText = 'font-size:0.65rem;margin-left:8px;transition:opacity 0.3s;';
            // Inserir ao lado do título do fieldset financeiro
            const legend = document.querySelector('fieldset .fa-coins')?.closest('legend');
            if (legend) legend.appendChild(badge);
        }
        if (status === 'syncing') {
            badge.className = 'badge bg-warning text-dark';
            badge.innerHTML = '<i class="fas fa-sync fa-spin me-1"></i>Salvando...';
            badge.style.opacity = '1';
        } else if (status === 'success') {
            badge.className = 'badge bg-success';
            badge.innerHTML = '<i class="fas fa-check me-1"></i>' + (message || 'Parcelas salvas');
            badge.style.opacity = '1';
            setTimeout(() => { badge.style.opacity = '0'; }, 3000);
        } else if (status === 'error') {
            badge.className = 'badge bg-danger';
            badge.innerHTML = '<i class="fas fa-times me-1"></i>' + (message || 'Erro');
            badge.style.opacity = '1';
            setTimeout(() => { badge.style.opacity = '0'; }, 5000);
        } else {
            badge.style.opacity = '0';
        }
    }

    /**
     * Sincroniza parcelas com o banco via AJAX (debounced)
     * Chamada automaticamente quando qualquer campo financeiro muda.
     * Cada chamada incrementa syncVersion; respostas obsoletas são descartadas.
     */
    function scheduleSyncInstallments(delay) {
        if (syncTimer) clearTimeout(syncTimer);
        syncVersion++; // Nova intenção de sync — qualquer resposta anterior é obsoleta
        delay = delay || 800;
        syncTimer = setTimeout(doSyncInstallments, delay);
    }

    function doSyncInstallments() {
        const method = paymentMethod ? paymentMethod.value : '';
        if (!method) return; // Sem forma de pagamento selecionada — não sincronizar

        // Não sincronizar se o método é parcelável e o usuário ainda não definiu as parcelas
        if (!installmentsConfigured()) return;

        // Abort any in-flight request — its response is now stale
        if (syncAbortCtrl) {
            syncAbortCtrl.abort();
        }
        syncAbortCtrl = new AbortController();

        // Capture the version at the moment this request is built.
        const myVersion = syncVersion;

        showSyncStatus('syncing');

        const nInst = parseInt(installments ? installments.value : 0) || 0;
        const dp = parseFloat(downPaymentField ? downPaymentField.value : 0) || 0;
        const disc = parseFloat(discountField ? discountField.value : 0) || 0;

        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('payment_method', method);
        formData.append('installments', nInst);
        formData.append('down_payment', dp);
        formData.append('discount', disc);
        formData.append('csrf_token', csrfToken);

        // Coletar datas de vencimento das parcelas do boleto (se visíveis)
        if (boletoTableBody) {
            const dateInputs = boletoTableBody.querySelectorAll('input.boleto-date[name]');
            dateInputs.forEach(function(input) {
                const match = input.name.match(/boleto_due_(\d+)/);
                if (match) {
                    formData.append('due_dates[' + match[1] + ']', input.value);
                }
            });
        }

        // ESTA ENVIANDO A QUANTIDADE CERTA DE PARCELAS, o formulário está sendo enviado corretamente
        fetch('?page=pipeline&action=syncInstallments', {
            method: 'POST',
            body: formData,
            signal: syncAbortCtrl.signal
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            // If a newer sync was scheduled after this one started, discard this response.
            if (myVersion !== syncVersion) {
                return;
            }

            if (data.success) {
                existingInstallmentCount = data.count || 0;
                showSyncStatus('success', data.message || 'Parcelas salvas');

                // Atualizar campo de valor da parcela se retornado
                if (data.installment_value && installmentValueHidden) {
                    installmentValueHidden.value = data.installment_value;
                }

                // Atualizar badges de status das parcelas no boleto table
                if (boletoTableBody && data.installments) {
                    updateBoletoTableFromServer(data.installments);
                }

                // Atualizar previousPaymentMethod/previousInstallments para refletir o estado salvo
                previousPaymentMethod = paymentMethod ? paymentMethod.value : '';
                previousInstallments = installments ? installments.value : '';
            } else {
                showSyncStatus('error', data.message || 'Erro ao salvar');
                if (data.has_paid) {
                    hasAnyPaidInstallment = true;
                    if (paymentMethod) paymentMethod.value = previousPaymentMethod;
                    if (installments) installments.value = previousInstallments;
                    toggleInstallmentRow();
                    lockFinancialFieldsIfPaid();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Parcelas já pagas',
                        html: '<p>' + (data.message || '') + '</p><p class="small text-muted mt-2">Para alterar a forma de pagamento ou parcelamento, estorne os pagamentos no módulo Financeiro.</p>',
                        confirmButtonColor: '#f39c12'
                    });
                }
            }
        })
        .catch(err => {
            // Ignore AbortError — it means we intentionally cancelled this request
            if (err.name === 'AbortError') return;
            console.error('syncInstallments error:', err);
            if (myVersion === syncVersion) {
                showSyncStatus('error', 'Erro de conexão');
            }
        });
    }

    /**
     * Atualiza a tabela visual de boletos com dados reais do servidor
     */
    function updateBoletoTableFromServer(serverInstallments) {
        if (!boletoTableBody || !serverInstallments || paymentMethod.value !== 'boleto') return;
        boletoTableBody.innerHTML = '';

        serverInstallments.forEach(function(inst) {
            var tr = document.createElement('tr');
            var num = parseInt(inst.installment_number);
            var isPaid = (inst.status === 'pago');
            var isEntry = (num === 0);

            if (isEntry) {
                tr.classList.add(isPaid ? 'table-success' : 'table-warning');
                var entryStatusBadge = isPaid
                    ? '<span class="badge bg-success" style="font-size:0.65rem;">✅ Pago</span>'
                    : '<span class="badge bg-warning" style="font-size:0.65rem;">⏳ Pendente</span>';
                tr.innerHTML = 
                    '<td class="fw-bold text-success"><i class="fas fa-hand-holding-usd me-1"></i>Entrada</td>' +
                    '<td><input type="date" class="form-control form-control-sm boleto-date" value="' + inst.due_date + '" style="max-width:160px;" ' + (isPaid ? 'disabled' : '') + '></td>' +
                    '<td class="text-end fw-bold">R$ ' + parseFloat(inst.amount).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + '</td>' +
                    '<td class="text-center">' + entryStatusBadge + '</td>';
            } else {
                var statusBadge = isPaid
                    ? '<span class="badge bg-success" style="font-size:0.65rem;">✅ Pago</span>'
                    : '<span class="badge bg-warning" style="font-size:0.65rem;">⏳ Pendente</span>';
                tr.innerHTML = 
                    '<td class="fw-bold">' + (serverInstallments.length <= 2 && num === 1 && !serverInstallments.find(function(x){return parseInt(x.installment_number)===0;}) ? 'Única' : num + 'ª') + '</td>' +
                    '<td><input type="date" class="form-control form-control-sm boleto-date" value="' + inst.due_date + '" style="max-width:160px;" name="boleto_due_' + num + '" data-installment-id="' + inst.id + '" ' + (isPaid ? 'disabled' : '') + '></td>' +
                    '<td class="text-end fw-bold">R$ ' + parseFloat(inst.amount).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + '</td>' +
                    '<td class="text-center">' + statusBadge + '</td>';
            }
            boletoTableBody.appendChild(tr);
        });

        // Reanexar listeners de data nas parcelas
        attachDueDateListeners();
    }

    /**
     * Listener para alterações de data de vencimento em parcelas individuais
     */
    function attachDueDateListeners() {
        if (!boletoTableBody) return;
        boletoTableBody.querySelectorAll('input.boleto-date[data-installment-id]').forEach(function(input) {
            input.removeEventListener('change', onDueDateChange);
            input.addEventListener('change', onDueDateChange);
        });
    }

    function onDueDateChange(e) {
        var input = e.target;
        var instId = input.getAttribute('data-installment-id');
        var newDate = input.value;
        if (!instId || !newDate) return;

        var formData = new FormData();
        formData.append('installment_id', instId);
        formData.append('due_date', newDate);
        formData.append('csrf_token', csrfToken);

        input.style.borderColor = '#f39c12';
        fetch('?page=pipeline&action=updateInstallmentDueDate', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                input.style.borderColor = '#27ae60';
                setTimeout(function() { input.style.borderColor = ''; }, 2000);
            } else {
                input.style.borderColor = '#e74c3c';
            }
        })
        .catch(function() {
            input.style.borderColor = '#e74c3c';
        });
    }

    paymentMethod.addEventListener('change', function() {
        // Se há parcelas pagas, bloquear alteração e reverter
        if (hasAnyPaidInstallment) {
            this.value = previousPaymentMethod;
            lockFinancialFieldsIfPaid();
            Swal.fire({
                icon: 'warning',
                title: 'Alteração bloqueada',
                html: 'Existem parcelas já pagas. Para alterar a forma de pagamento, estorne os pagamentos primeiro no módulo <strong>Financeiro</strong>.',
                confirmButtonColor: '#f39c12'
            });
            return;
        }
        const oldMethod = previousPaymentMethod;
        const newMethod = this.value;
        previousPaymentMethod = newMethod;

        // Ao mudar para método parcelável, resetar o nº de parcelas para que o
        // usuário escolha conscientemente (evita gerar 1 parcela prematuramente)
        const wasParcelable = parcelableMethods.includes(oldMethod);
        const isNowParcelable = parcelableMethods.includes(newMethod);

        if (isNowParcelable && !wasParcelable) {
            // Resetar parcelas e flag ao entrar em método parcelável
            if (installments) {
                installments.value = '';
                previousInstallments = '';
            }
            installmentsUserTouched = false;
        }

        toggleInstallmentRow();

        if (isNowParcelable) {
            // Método parcelável selecionado: NÃO sincronizar ainda.
            // Aguardar o usuário selecionar o número de parcelas.
            // Se já existem parcelas e o método não mudou de tipo, manter.
            if (wasParcelable && oldMethod !== newMethod) {
                // Mudou entre parceláveis (ex: cartão → boleto): sincronizar
                scheduleSyncInstallments(500);
            }
            // Se veio de não-parcelável, não sincronizar — esperar seleção de parcelas
        } else {
            // Método não parcelável (dinheiro, pix, etc.): sincronizar imediatamente
            // para gerar uma única parcela / limpar parcelamento anterior.
            // Gateway online: NÃO sincronizar — o pagamento é gerido pelo gateway.
            if (newMethod !== 'gateway') {
                scheduleSyncInstallments(300);
            }
        }
    });

    if (installments) installments.addEventListener('change', function() {
        // Se há parcelas pagas, bloquear alteração e reverter
        if (hasAnyPaidInstallment) {
            this.value = previousInstallments;
            lockFinancialFieldsIfPaid();
            Swal.fire({
                icon: 'warning',
                title: 'Alteração bloqueada',
                html: 'Existem parcelas já pagas. Para alterar o parcelamento, estorne os pagamentos primeiro no módulo <strong>Financeiro</strong>.',
                confirmButtonColor: '#f39c12'
            });
            return;
        }
        installmentsUserTouched = true;
        previousInstallments = this.value;
        calcInstallment();
        updateCardTitle();
        scheduleSyncInstallments(500);
    });
    if (downPaymentField) downPaymentField.addEventListener('change', function() {
        // Se há parcelas pagas, bloquear alteração
        if (hasAnyPaidInstallment) {
            this.value = <?= (float)($order['down_payment'] ?? 0) ?>;
            lockFinancialFieldsIfPaid();
            Swal.fire({
                icon: 'warning',
                title: 'Alteração bloqueada',
                html: 'Existem parcelas já pagas. Para alterar a entrada, estorne os pagamentos primeiro no módulo <strong>Financeiro</strong>.',
                confirmButtonColor: '#f39c12'
            });
            return;
        }
        calcInstallment();
        updateDownPaymentInfo();
        // Sempre sincronizar parcelas ao alterar entrada — independente do método de pagamento
        // A entrada gera uma parcela aberta (installment_number = 0) no financeiro
        if (paymentMethod && paymentMethod.value) {
            scheduleSyncInstallments(800);
        }
    });
    if (discountField) discountField.addEventListener('change', function() {
        // Se há parcelas pagas, bloquear alteração de desconto (afeta valor das parcelas)
        if (hasAnyPaidInstallment) {
            this.value = <?= (float)($order['discount'] ?? 0) ?>;
            lockFinancialFieldsIfPaid();
            Swal.fire({
                icon: 'warning',
                title: 'Alteração bloqueada',
                html: 'Existem parcelas já pagas. Para alterar o desconto, estorne os pagamentos primeiro no módulo <strong>Financeiro</strong>.',
                confirmButtonColor: '#f39c12'
            });
            return;
        }
        calcInstallment();
        if (installmentsConfigured()) {
            scheduleSyncInstallments(800);
        }
    });
    
    toggleInstallmentRow();
    updateDownPaymentInfo();

    // ═══ Estilização dinâmica do select de status de pagamento ═══
    var finPaymentStatus = document.getElementById('finPaymentStatus');
    if (finPaymentStatus) {
        var statusStyles = {
            'pendente': { color: '#f39c12', bg: '#fff3cd' },
            'parcial':  { color: '#3498db', bg: '#cfe2ff' },
            'pago':     { color: '#198754', bg: '#d1e7dd' }
        };
        finPaymentStatus.addEventListener('change', function() {
            var s = statusStyles[this.value] || statusStyles['pendente'];
            this.style.borderColor = s.color;
            this.style.backgroundColor = s.bg;
        });
    }

    // Bloquear campos se há parcelas pagas (logo no carregamento da página)
    lockFinancialFieldsIfPaid();

    // Ao carregar a página, se existem parcelas no DB e o método é parcelável, buscar dados reais
    if (existingInstallmentCount > 0 && parcelableMethods.includes(paymentMethod.value)) {
        fetch('?page=financial&action=getInstallmentsJson&order_id=' + orderId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.installments && data.installments.length > 0) {
                    if (paymentMethod.value === 'boleto') {
                        updateBoletoTableFromServer(data.installments);
                    }
                    // Detectar se alguma parcela está paga (para atualizar estado)
                    var anyPaid = data.installments.some(function(inst) { return inst.status === 'pago'; });
                    if (anyPaid && !hasAnyPaidInstallment) {
                        hasAnyPaidInstallment = true;
                        lockFinancialFieldsIfPaid();
                    }
                }
            })
            .catch(function() {});
    }

    // ═══ Dados bancários das configurações (injetados via PHP) ═══
    var bankConfig = {
        banco:         <?= json_encode($company['boleto_banco'] ?? '') ?>,
        agencia:       <?= json_encode($company['boleto_agencia'] ?? '') ?>,
        agenciaDv:     <?= json_encode($company['boleto_agencia_dv'] ?? '') ?>,
        conta:         <?= json_encode($company['boleto_conta'] ?? '') ?>,
        contaDv:       <?= json_encode($company['boleto_conta_dv'] ?? '') ?>,
        carteira:      <?= json_encode($company['boleto_carteira'] ?? '109') ?>,
        especie:       <?= json_encode($company['boleto_especie'] ?? 'R$') ?>,
        cedente:       <?= json_encode($company['boleto_cedente'] ?? $company['company_name'] ?? 'Empresa') ?>,
        cedenteDoc:    <?= json_encode($company['boleto_cedente_documento'] ?? $company['company_document'] ?? '') ?>,
        convenio:      <?= json_encode($company['boleto_convenio'] ?? '') ?>,
        nossoNumero:   parseInt(<?= json_encode($company['boleto_nosso_numero'] ?? '1') ?>) || 1,
        nossoNumDigitos: parseInt(<?= json_encode($company['boleto_nosso_numero_digitos'] ?? '7') ?>) || 7,
        instrucoes:    <?= json_encode($company['boleto_instrucoes'] ?? "Não receber após o vencimento.\nMulta de 2% após o vencimento.\nJuros de 1% ao mês.") ?>,
        multa:         <?= json_encode($company['boleto_multa'] ?? '2.00') ?>,
        juros:         <?= json_encode($company['boleto_juros'] ?? '1.00') ?>,
        aceite:        <?= json_encode($company['boleto_aceite'] ?? 'N') ?>,
        especieDoc:    <?= json_encode($company['boleto_especie_doc'] ?? 'DM') ?>,
        demonstrativo: <?= json_encode($company['boleto_demonstrativo'] ?? '') ?>,
        localPagamento: <?= json_encode($company['boleto_local_pagamento'] ?? 'Pagável em qualquer banco até o vencimento') ?>,
        cedenteEndereco: <?= json_encode($company['boleto_cedente_endereco'] ?? '') ?>
    };

    // Nomes dos bancos
    var bancosNomes = {
        '001': 'Banco do Brasil S.A.', '033': 'Banco Santander S.A.', '104': 'Caixa Econômica Federal',
        '237': 'Banco Bradesco S.A.', '341': 'Itaú Unibanco S.A.', '399': 'HSBC', '422': 'Banco Safra S.A.',
        '748': 'Sicredi', '756': 'Sicoob', '077': 'Banco Inter S.A.', '260': 'Nu Pagamentos S.A.',
        '336': 'Banco C6 S.A.', '290': 'PagSeguro Internet S.A.', '380': 'PicPay', '323': 'Mercado Pago'
    };

    // ═══ Funções utilitárias para boleto FEBRABAN ═══
    function mod10(value) {
        var soma = 0, peso = 2;
        for (var i = value.length - 1; i >= 0; i--) {
            var parcial = parseInt(value[i]) * peso;
            if (parcial > 9) parcial = Math.floor(parcial / 10) + (parcial % 10);
            soma += parcial;
            peso = peso === 2 ? 1 : 2;
        }
        var resto = soma % 10;
        return resto === 0 ? 0 : 10 - resto;
    }

    function mod11(value, base) {
        base = base || 9;
        var soma = 0, peso = 2;
        for (var i = value.length - 1; i >= 0; i--) {
            soma += parseInt(value[i]) * peso;
            peso++;
            if (peso > base) peso = 2;
        }
        var resto = soma % 11;
        if (resto === 0 || resto === 1 || resto === 10) return 1;
        return 11 - resto;
    }

    function padLeft(str, len, ch) {
        ch = ch || '0';
        str = String(str);
        while (str.length < len) str = ch + str;
        return str;
    }

    function fatorVencimento(dateStr) {
        var base = new Date(1997, 9, 7); // 07/10/1997
        var dt = new Date(dateStr + 'T12:00:00');
        var diff = Math.round((dt - base) / (1000 * 60 * 60 * 24));
        return padLeft(Math.max(0, diff), 4);
    }

    function formatarValorBoleto(valor) {
        return padLeft(Math.round(valor * 100), 10);
    }

    function gerarCodigoBarras(banco, vencStr, valor, nossoNumStr) {
        var fv = fatorVencimento(vencStr);
        var vl = formatarValorBoleto(valor);
        var ag = padLeft(bankConfig.agencia, 4);
        var ct = padLeft(bankConfig.conta, 8);
        var ctDv = bankConfig.contaDv || '0';
        var cart = padLeft(bankConfig.carteira, 3);
        var nn = nossoNumStr;
        var conv = padLeft(bankConfig.convenio, 7);

        // Montar campo livre conforme banco (44 posições no total: banco(3)+moeda(1)+dv(1)+fv(4)+valor(10)+campolivre(25))
        var campoLivre = '';
        if (banco === '001') {
            // BB: conv(7) + complemento NN(10) + agência(4) + conta(8) + carteira(2)
            campoLivre = padLeft(conv, 7) + padLeft(nn, 10) + ag + padLeft(ct, 8) + padLeft(cart, 2).substring(0, 2);
            campoLivre = campoLivre.substring(0, 25);
        } else if (banco === '341') {
            // Itaú: cart(3) + NN(8) + ag(4) + conta(5) + dac(1) + 000
            var nn8 = padLeft(nn, 8);
            var ct5 = padLeft(bankConfig.conta, 5);
            var dacNN = mod10(ag + ct5 + cart + nn8);
            campoLivre = (cart + nn8 + ag + ct5 + String(dacNN) + '000').substring(0, 25);
        } else if (banco === '237') {
            // Bradesco: ag(4) + cart(2) + NN(11) + conta(7) + zero
            campoLivre = (ag + padLeft(cart, 2) + padLeft(nn, 11) + padLeft(ct, 7) + '0').substring(0, 25);
        } else if (banco === '104') {
            // Caixa: NN seguro - simplificado: cedente(6) + DV + nossonumero3(3) + 1(const) + cedente(3) + 4(const) + nn restante(7) + DV
            // Simplificado para carteira RG/SR:
            campoLivre = (padLeft(conv, 6) + padLeft(nn, 17) + '04').substring(0, 25);
        } else if (banco === '033') {
            // Santander: 9 + conv(7) + nn(12/13) + iof + carteira
            campoLivre = ('9' + padLeft(conv, 7) + padLeft(nn, 13) + '0' + padLeft(cart, 3)).substring(0, 25);
        } else {
            // Genérico: agência + conta + carteira + nosso número
            campoLivre = (ag + padLeft(ct, 8) + ctDv + padLeft(cart, 3) + padLeft(nn, 10)).substring(0, 25);
            while (campoLivre.length < 25) campoLivre += '0';
        }

        // Montar sem DV geral
        var semDv = banco + '9' + fv + vl + campoLivre;
        // DV geral (posição 5 do código de barras)
        var dvGeral = mod11(semDv.replace(/[^0-9]/g, ''), 9);
        // Código de barras completo (44 dígitos)
        var cb = banco + '9' + String(dvGeral) + fv + vl + campoLivre;
        return cb.substring(0, 44);
    }

    function gerarLinhaDigitavel(cb) {
        // Campo 1: banco(3) + moeda(1) + campolivre[0..4] => 9 dígitos + mod10
        var campo1 = cb.substring(0, 4) + cb.substring(19, 24);
        var dv1 = mod10(campo1);
        var c1 = campo1.substring(0, 5) + '.' + campo1.substring(5) + String(dv1);

        // Campo 2: campolivre[5..14] => 10 dígitos + mod10
        var campo2 = cb.substring(24, 34);
        var dv2 = mod10(campo2);
        var c2 = campo2.substring(0, 5) + '.' + campo2.substring(5) + String(dv2);

        // Campo 3: campolivre[15..24] => 10 dígitos + mod10
        var campo3 = cb.substring(34, 44);
        var dv3 = mod10(campo3);
        var c3 = campo3.substring(0, 5) + '.' + campo3.substring(5) + String(dv3);

        // Campo 4: DV geral (posição 5 do CB original)
        var c4 = cb.substring(4, 5);

        // Campo 5: fator vencimento + valor
        var c5 = cb.substring(5, 19);

        return c1 + ' ' + c2 + ' ' + c3 + ' ' + c4 + ' ' + c5;
    }

    function gerarBarcode128Svg(code, width, height) {
        // Gerar representação visual do código de barras Interleaved 2 of 5 (ITF - padrão FEBRABAN)
        var patterns = {
            '0': 'nnwwn', '1': 'wnnnw', '2': 'nwnnw', '3': 'wwnnn', '4': 'nnwnw',
            '5': 'wnwnn', '6': 'nwwnn', '7': 'nnnww', '8': 'wnnwn', '9': 'nwnwn'
        };
        
        // Código deve ter número par de dígitos
        var data = code;
        if (data.length % 2 !== 0) data = '0' + data;
        
        var bars = 'nnnn'; // Start pattern
        for (var i = 0; i < data.length; i += 2) {
            var patBar = patterns[data[i]] || 'nnwwn';
            var patSpace = patterns[data[i + 1]] || 'nnwwn';
            for (var j = 0; j < 5; j++) {
                bars += patBar[j];
                bars += patSpace[j];
            }
        }
        bars += 'wnn'; // Stop pattern
        
        var totalUnits = 0;
        for (var k = 0; k < bars.length; k++) {
            totalUnits += (bars[k] === 'w') ? 3 : 1;
        }
        
        var unitWidth = width / totalUnits;
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">';
        var x = 0;
        for (var m = 0; m < bars.length; m++) {
            var bw = (bars[m] === 'w') ? unitWidth * 3 : unitWidth;
            if (m % 2 === 0) { // barras pretas em posições pares
                svg += '<rect x="' + x.toFixed(2) + '" y="0" width="' + bw.toFixed(2) + '" height="' + height + '" fill="#000"/>';
            }
            x += bw;
        }
        svg += '</svg>';
        return svg;
    }

    function formatCurrency(v) {
        return parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDateBR(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr + 'T12:00:00');
        return d.toLocaleDateString('pt-BR');
    }

    // ═══ Impressão de Boletos FEBRABAN (CNAB 240/400) ═══
    var btnPrintBoletos = document.getElementById('btnPrintBoletos');
    if (btnPrintBoletos) {
        btnPrintBoletos.addEventListener('click', function() {
            var rows = boletoTableBody.querySelectorAll('tr');
            if (!rows.length) return;

            // Verificar se configurações bancárias existem
            if (!bankConfig.banco || !bankConfig.agencia || !bankConfig.conta) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Configurações Bancárias Incompletas',
                    html: '<p>Para gerar boletos no padrão FEBRABAN, é necessário configurar os dados bancários.</p><p class="small text-muted">Vá em <strong>Configurações → Boleto/Bancário</strong> e preencha os dados do banco, agência, conta e cedente.</p>',
                    confirmButtonText: '<i class="fas fa-cog me-1"></i> Ir para Configurações',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f39c12'
                }).then(r => {
                    if (r.isConfirmed) window.open('?page=settings&tab=boleto', '_blank');
                });
                return;
            }

            if (!bankConfig.cedente || !bankConfig.cedenteDoc) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dados do Cedente Incompletos',
                    html: '<p>Preencha o <strong>Nome/Razão Social</strong> e o <strong>CNPJ/CPF do Cedente</strong> nas configurações.</p>',
                    confirmButtonText: '<i class="fas fa-cog me-1"></i> Ir para Configurações',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f39c12'
                }).then(r => {
                    if (r.isConfirmed) window.open('?page=settings&tab=boleto', '_blank');
                });
                return;
            }

            var orderNum  = '<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>';
            var orderNumInt = <?= (int)$order['id'] ?>;
            var custName  = <?= json_encode($order['customer_name'] ?? '—') ?>;
            var custDoc   = <?= json_encode($order['customer_document'] ?? '') ?>;
            var custAddr  = <?= json_encode($customerFormattedAddress ?? '') ?>;
            var cedenteAddr = bankConfig.cedenteEndereco || <?= json_encode($companyAddress ?? '') ?>;
            var bancoNome = bancosNomes[bankConfig.banco] || ('Banco ' + bankConfig.banco);
            var bancoCode = padLeft(bankConfig.banco, 3);
            var bancoDv   = mod11(bancoCode);
            var bancoFull = bancoCode + '-' + bancoDv;
            var agenciaStr = bankConfig.agencia + (bankConfig.agenciaDv ? '-' + bankConfig.agenciaDv : '');
            var contaStr = bankConfig.conta + (bankConfig.contaDv ? '-' + bankConfig.contaDv : '');
            var agCodCedente = agenciaStr + ' / ' + (bankConfig.convenio || contaStr);
            var instrucoes = bankConfig.instrucoes ? bankConfig.instrucoes.split('\n') : [];
            var dataProcessamento = new Date().toLocaleDateString('pt-BR');
            var multaPct = parseFloat(bankConfig.multa) || 0;
            var jurosPct = parseFloat(bankConfig.juros) || 0;

            var boletosHtml = '';
            var nossoNumBase = bankConfig.nossoNumero;

            rows.forEach(function(tr, idx) {
                var cells = tr.querySelectorAll('td');
                var parcLabel = cells[0].textContent.trim();
                var dateInput = cells[1].querySelector('input');
                var dueDate = dateInput ? dateInput.value : '';
                var dueDateFmt = formatDateBR(dueDate);
                var valorStr = cells[2].textContent.replace(/[^\d,.]/g, '').replace('.','').replace(',','.');
                var valorNum = parseFloat(valorStr) || 0;
                var isEntrada = parcLabel.toLowerCase().indexOf('entrada') >= 0;

                // Nosso Número para esta parcela (Entrada não gera boleto bancário real)
                var nossoNum = padLeft(nossoNumBase + (isEntrada ? 0 : idx), bankConfig.nossoNumDigitos);
                var nossoNumComDv = nossoNum + '-' + mod11(nossoNum);

                // Número do documento
                var numDocumento = orderNum + '-' + padLeft(idx + 1, 2);

                // Gerar código de barras e linha digitável
                var codigoBarras = gerarCodigoBarras(bancoCode, dueDate, valorNum, nossoNum);
                var linhaDigitavel = gerarLinhaDigitavel(codigoBarras);
                var barcodeSvg = gerarBarcode128Svg(codigoBarras, 580, 55);

                // Informações de multa/juros para instruções
                var instrCompletas = instrucoes.slice();
                if (multaPct > 0 && !instrCompletas.some(l => l.toLowerCase().indexOf('multa') >= 0)) {
                    instrCompletas.push('Multa de ' + multaPct.toFixed(2).replace('.', ',') + '% após o vencimento.');
                }
                if (jurosPct > 0 && !instrCompletas.some(l => l.toLowerCase().indexOf('juro') >= 0)) {
                    instrCompletas.push('Juros de ' + jurosPct.toFixed(2).replace('.', ',') + '% ao mês por atraso.');
                }

                var pageBreak = idx > 0 ? 'style="page-break-before:always;"' : '';

                boletosHtml += `
                <div class="boleto-page" ${pageBreak}>
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <!-- RECIBO DO SACADO (parte de cima — destacável pelo cliente) -->
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <div class="recibo-sacado">
                        <table class="topo w100">
                            <tr>
                                <td class="topo-logo"><strong class="banco-nome">${bancoNome}</strong></td>
                                <td class="topo-codigo"><span class="banco-numero">${bancoFull}</span></td>
                                <td class="topo-ld"><span class="linha-digitavel">${linhaDigitavel}</span></td>
                            </tr>
                        </table>
                        <table class="w100 body-table">
                            <tr>
                                <td class="cell" style="width:60%;"><span class="lbl">Beneficiário</span><br><strong>${bankConfig.cedente}</strong><br><small>${bankConfig.cedenteDoc}</small></td>
                                <td class="cell" style="width:20%;"><span class="lbl">Agência/Cód. Beneficiário</span><br>${agCodCedente}</td>
                                <td class="cell" style="width:20%;"><span class="lbl">Nosso Número</span><br><strong>${nossoNumComDv}</strong></td>
                            </tr>
                            <tr>
                                <td class="cell"><span class="lbl">Pagador</span><br>${custName}${custDoc ? ' — CPF/CNPJ: ' + custDoc : ''}</td>
                                <td class="cell"><span class="lbl">Vencimento</span><br><strong class="venc">${dueDateFmt}</strong></td>
                                <td class="cell"><span class="lbl">Valor Documento</span><br><strong class="valor">R$ ${formatCurrency(valorNum)}</strong></td>
                            </tr>
                            <tr>
                                <td class="cell"><span class="lbl">Endereço Pagador</span><br><small>${custAddr || '—'}</small></td>
                                <td class="cell" colspan="2">
                                    <span class="lbl">Nº Documento</span> ${numDocumento}
                                    &nbsp;|&nbsp; <span class="lbl">Parcela</span> ${parcLabel}
                                    &nbsp;|&nbsp; <span class="lbl">Pedido</span> #${orderNum}
                                </td>
                            </tr>
                        </table>
                        <div class="recibo-footer">
                            <span class="tesoura">✂</span>
                            <span class="recibo-texto">Recibo do Sacado</span>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════════ -->
                    <!-- FICHA DE COMPENSAÇÃO (parte principal — vai ao banco)  -->
                    <!-- Padrão FEBRABAN — CNAB 240 / CNAB 400                 -->
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <div class="ficha-compensacao">
                        <table class="topo w100">
                            <tr>
                                <td class="topo-logo"><strong class="banco-nome">${bancoNome}</strong></td>
                                <td class="topo-codigo"><span class="banco-numero">${bancoFull}</span></td>
                                <td class="topo-ld"><span class="linha-digitavel">${linhaDigitavel}</span></td>
                            </tr>
                        </table>
                        <table class="w100 body-table fc-body">
                            <tr>
                                <td class="cell" colspan="6"><span class="lbl">Local de Pagamento</span><br>${bankConfig.localPagamento}</td>
                                <td class="cell r" style="width:25%;"><span class="lbl">Vencimento</span><br><strong class="venc venc-destaque">${dueDateFmt}</strong></td>
                            </tr>
                            <tr>
                                <td class="cell" colspan="6"><span class="lbl">Beneficiário</span><br><strong>${bankConfig.cedente}</strong> — CNPJ/CPF: ${bankConfig.cedenteDoc}<br><small>${cedenteAddr}</small></td>
                                <td class="cell r"><span class="lbl">Agência / Código Cedente</span><br><strong>${agCodCedente}</strong></td>
                            </tr>
                            <tr>
                                <td class="cell"><span class="lbl">Data do Documento</span><br>${dataProcessamento}</td>
                                <td class="cell" colspan="2"><span class="lbl">Nº do Documento</span><br>${numDocumento}</td>
                                <td class="cell"><span class="lbl">Espécie Doc.</span><br>${bankConfig.especieDoc}</td>
                                <td class="cell"><span class="lbl">Aceite</span><br>${bankConfig.aceite}</td>
                                <td class="cell"><span class="lbl">Data Processamento</span><br>${dataProcessamento}</td>
                                <td class="cell r"><span class="lbl">Nosso Número</span><br><strong>${nossoNumComDv}</strong></td>
                            </tr>
                            <tr>
                                <td class="cell"><span class="lbl">Uso do Banco</span><br>&nbsp;</td>
                                <td class="cell"><span class="lbl">Carteira</span><br>${bankConfig.carteira}</td>
                                <td class="cell"><span class="lbl">Espécie</span><br>${bankConfig.especie}</td>
                                <td class="cell" colspan="2"><span class="lbl">Quantidade</span><br>&nbsp;</td>
                                <td class="cell"><span class="lbl">(x) Valor</span><br>&nbsp;</td>
                                <td class="cell r"><span class="lbl">(=) Valor do Documento</span><br><strong class="valor">R$ ${formatCurrency(valorNum)}</strong></td>
                            </tr>
                            <tr>
                                <td class="cell instrucoes" colspan="6" rowspan="5">
                                    <span class="lbl">Instruções (Texto de responsabilidade do beneficiário)</span><br>
                                    ${instrCompletas.map(l => l.trim()).filter(l => l).map(l => '<span class="inst-line">• ' + l + '</span>').join('<br>')}
                                    ${bankConfig.demonstrativo ? '<br><br><span class="lbl">Demonstrativo:</span><br><span class="inst-line">' + bankConfig.demonstrativo + '</span>' : ''}
                                    <br><br>
                                    <span class="inst-line"><strong>Ref: Pedido #${orderNum} — Parcela: ${parcLabel}</strong></span>
                                </td>
                                <td class="cell r"><span class="lbl">(-) Desconto / Abatimento</span><br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="cell r"><span class="lbl">(-) Outras Deduções</span><br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="cell r"><span class="lbl">(+) Mora / Multa</span><br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="cell r"><span class="lbl">(+) Outros Acréscimos</span><br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="cell r"><span class="lbl">(=) Valor Cobrado</span><br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="cell sacado" colspan="7">
                                    <span class="lbl">Sacado / Pagador</span><br>
                                    <strong>${custName}</strong>${custDoc ? ' — CPF/CNPJ: ' + custDoc : ''}<br>
                                    ${custAddr || ''}
                                </td>
                            </tr>
                            <tr>
                                <td class="cell" colspan="5" style="border-bottom:none;">
                                    <span class="lbl">Sacador/Avalista</span><br>&nbsp;
                                </td>
                                <td class="cell" colspan="2" style="border-bottom:none;text-align:right;">
                                    <span class="lbl">Cód. Baixa</span><br>&nbsp;
                                </td>
                            </tr>
                        </table>
                        <!-- Código de Barras ITF (Interleaved 2 of 5 — Padrão FEBRABAN) -->
                        <div class="barcode-area">
                            <div class="barcode-svg">${barcodeSvg}</div>
                            <div class="barcode-numeros">${codigoBarras}</div>
                        </div>
                        <div class="fc-rodape">
                            <span>Ficha de Compensação — Autenticação Mecânica</span>
                            <span>FEBRABAN — CNAB 240/400</span>
                        </div>
                    </div>
                </div>`;
            });

            var printWin = window.open('', '_blank', 'width=850,height=1000');
            printWin.document.write(`<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Boleto Bancário — Pedido #${orderNum}</title>
<style>
    @page { size: A4 portrait; margin: 8mm 10mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 10px; line-height: 1.3; background: #fff; }
    .w100 { width: 100%; border-collapse: collapse; }

    /* ═══ Cabeçalho (topo de cada seção: logo + código banco + linha digitável) ═══ */
    table.topo { border-collapse: collapse; }
    table.topo td { border: 2px solid #000; padding: 4px 8px; vertical-align: middle; }
    .topo-logo { width: 22%; }
    .topo-codigo { width: 13%; text-align: center; }
    .topo-ld { width: 65%; }
    .banco-nome { font-size: 13px; font-weight: bold; }
    .banco-numero { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
    .linha-digitavel { font-size: 13px; font-weight: bold; letter-spacing: 0.8px; text-align: right; display: block; font-family: 'Courier New', monospace; }

    /* ═══ Células da tabela principal ═══ */
    .body-table { border-collapse: collapse; }
    .cell { border: 1px solid #000; padding: 2px 5px; vertical-align: top; font-size: 9px; }
    .cell.r { text-align: right; }
    .lbl { font-size: 6.5px; color: #444; text-transform: uppercase; display: block; margin-bottom: 1px; letter-spacing: 0.3px; }
    .venc { font-size: 13px; font-weight: bold; }
    .venc-destaque { font-size: 14px; }
    .valor { font-size: 12px; font-weight: bold; }
    .inst-line { font-size: 9px; line-height: 1.6; display: block; }
    .instrucoes { min-height: 90px; vertical-align: top; }
    .sacado { min-height: 36px; }

    /* ═══ Recibo do Sacado ═══ */
    .recibo-sacado { margin-bottom: 0; }
    .recibo-footer { 
        display: flex; align-items: center; justify-content: center; gap: 15px;
        padding: 2px 0; font-size: 8px; color: #777; 
        border-bottom: 1px dashed #999; margin-bottom: 3px;
        letter-spacing: 0.5px;
    }
    .recibo-footer .tesoura { font-size: 14px; }
    .recibo-footer .recibo-texto { text-transform: uppercase; }

    /* ═══ Ficha de Compensação ═══ */
    .ficha-compensacao { margin-top: 4px; }
    .fc-body { }

    /* ═══ Código de Barras ═══ */
    .barcode-area { padding: 6px 0 2px 0; text-align: left; }
    .barcode-svg { }
    .barcode-svg svg { max-width: 100%; height: 55px; }
    .barcode-numeros { font-family: 'Courier New', monospace; font-size: 8px; color: #555; letter-spacing: 2px; margin-top: 2px; }

    /* ═══ Rodapé ═══ */
    .fc-rodape { 
        display: flex; justify-content: space-between; 
        font-size: 7px; color: #666; padding: 4px 4px 0; 
        border-top: 2px solid #000; 
    }

    /* ═══ Paginação ═══ */
    .boleto-page { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #eee; }

    /* ═══ Impressão ═══ */
    .no-print { text-align: center; padding: 20px; background: #f8f8f8; border-top: 2px solid #ddd; margin-top: 10px; }
    .no-print .info-texto { font-size: 11px; color: #666; margin-bottom: 10px; }
    @media print {
        .no-print { display: none !important; }
        .boleto-page { page-break-inside: avoid; border-bottom: none; margin-bottom: 0; }
    }

    /* ═══ Marca d'água quando entrada ═══ */
    .entrada-marca { position: relative; }
    .entrada-marca::after {
        content: 'ENTRADA'; position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 48px; font-weight: bold; color: rgba(39,174,96,0.08);
        letter-spacing: 8px; pointer-events: none;
    }
</style></head><body>
    <div class="no-print">
        <p class="info-texto">
            <strong>📄 Boleto Bancário — Pedido #${orderNum}</strong><br>
            Banco: <strong>${bancoNome} (${bancoFull})</strong> | Cedente: <strong>${bankConfig.cedente}</strong> | ${rows.length} boleto(s) gerado(s)<br>
            <small>Boletos gerados conforme padrão FEBRABAN (CNAB 240/400) com código de barras Interleaved 2 of 5</small>
        </p>
        <button onclick="window.print()" style="padding:10px 30px;font-size:14px;cursor:pointer;border:2px solid #333;border-radius:4px;background:#fff;font-weight:bold;">🖨️ Imprimir Boletos</button>
        <button onclick="window.close()" style="padding:10px 20px;font-size:14px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#f5f5f5;margin-left:8px;">Fechar</button>
    </div>
    ${boletosHtml}
    <div class="no-print" style="margin-top:20px;">
        <button onclick="window.print()" style="padding:10px 30px;font-size:14px;cursor:pointer;border:2px solid #333;border-radius:4px;background:#fff;font-weight:bold;">🖨️ Imprimir Boletos</button>
        <button onclick="window.close()" style="padding:10px 20px;font-size:14px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#f5f5f5;margin-left:8px;">Fechar</button>
    </div>
</body></html>`);
            printWin.document.close();
            printWin.focus();
        });
    }

    // ═══ Emitir NF-e Manual (toggle dos campos manuais) ═══
    var btnNF = document.getElementById('btnEmitirNF');
    var nfeManualFieldsWrapper = document.getElementById('nfeManualFieldsWrapper');
    if (btnNF && nfeManualFieldsWrapper) {
        btnNF.addEventListener('click', function() {
            // Toggle visibilidade dos campos manuais
            var isHidden = nfeManualFieldsWrapper.style.display === 'none';
            nfeManualFieldsWrapper.style.display = isHidden ? '' : 'none';
            if (isHidden) {
                // Scroll suave até os campos
                nfeManualFieldsWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
    // Botão fechar campos manuais
    var btnCloseManualNfe = document.getElementById('btnCloseManualNfe');
    if (btnCloseManualNfe && nfeManualFieldsWrapper) {
        btnCloseManualNfe.addEventListener('click', function() {
            nfeManualFieldsWrapper.style.display = 'none';
        });
    }

    // ═══ Função central de emissão NF-e via SEFAZ ═══
    function emitirNfeSefaz() {
        var orderId = <?= (int)$order['id'] ?>;

        Swal.fire({
            icon: 'warning',
            title: 'Emitir NF-e via SEFAZ?',
            html: '<p>Será gerada e enviada uma NF-e para o <strong>Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></strong>.</p>'
                + '<p class="small text-muted">Valor: <strong>R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></strong></p>'
                + '<p class="small text-danger">Confira os dados do pedido e do cliente antes de emitir.</p>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-export me-1"></i> Emitir NF-e',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return fetch('?page=nfe_documents&action=emit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': __csrfToken
                    },
                    body: 'order_id=' + encodeURIComponent(orderId) + '&csrf_token=' + encodeURIComponent(__csrfToken)
                })
                .then(function(r) { return r.json(); })
                .catch(function() {
                    Swal.showValidationMessage('Falha na comunicação com o servidor.');
                });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                var resp = result.value;
                if (resp.success) {
                    // NF-e autorizada — oferecer impressão do DANFE
                    Swal.fire({
                        icon: 'success',
                        title: 'NF-e Autorizada!',
                        html: (resp.message || 'NF-e emitida com sucesso.')
                            + (resp.chave ? '<br><small class="text-muted" style="word-break:break-all;">Chave: ' + resp.chave + '</small>' : '')
                            + '<hr><p class="mb-0 small"><i class="fas fa-print me-1"></i>Deseja imprimir o <strong>DANFE</strong> agora?</p>',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-file-pdf me-1"></i> Imprimir DANFE',
                        cancelButtonText: 'Fechar',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d'
                    }).then(function(printResult) {
                        if (printResult.isConfirmed && resp.nfe_id) {
                            // Abrir DANFE em nova aba para impressão
                            window.open('?page=nfe_documents&action=download&id=' + resp.nfe_id + '&type=danfe', '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro na Emissão',
                        html: resp.message,
                        confirmButtonText: 'OK'
                    }).then(function() { if (resp.nfe_id) location.reload(); });
                }
            }
        });
    }

    // ═══ Emitir NF-e via SEFAZ — botão dentro do card fiscal ═══
    var btnEmitirSefaz = document.getElementById('btnEmitirNfeSefaz');
    if (btnEmitirSefaz) {
        btnEmitirSefaz.addEventListener('click', emitirNfeSefaz);
    }

    // ═══ Emitir NF-e via SEFAZ — botão no cabeçalho ═══
    var btnHeaderEmitirNfe = document.getElementById('btnHeaderEmitirNfe');
    if (btnHeaderEmitirNfe) {
        btnHeaderEmitirNfe.addEventListener('click', emitirNfeSefaz);
    }

    // ═══ Cancelar NF-e no Pipeline ═══
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-cancel-nfe-pipeline');
        if (!btn) return;
        var nfeId = btn.getAttribute('data-id');
        var nfeNum = btn.getAttribute('data-numero');
        Swal.fire({
            icon: 'warning',
            title: 'Cancelar NF-e #' + nfeNum + '?',
            html: '<p>Esta ação é <strong>irreversível</strong>. A NF-e será cancelada na SEFAZ.</p>'
                + '<div class="mb-3"><label class="form-label small fw-bold">Justificativa (mín. 15 chars)</label>'
                + '<textarea id="swalCancelMotivo" class="form-control" rows="3" placeholder="Descreva o motivo..."></textarea></div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-ban me-1"></i> Cancelar NF-e',
            cancelButtonText: 'Voltar',
            confirmButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                var motivo = document.getElementById('swalCancelMotivo').value.trim();
                if (motivo.length < 15) {
                    Swal.showValidationMessage('Justificativa deve ter no mínimo 15 caracteres.');
                    return false;
                }
                return fetch('?page=nfe_documents&action=cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': __csrfToken
                    },
                    body: 'nfe_id=' + encodeURIComponent(nfeId) + '&motivo=' + encodeURIComponent(motivo) + '&csrf_token=' + encodeURIComponent(__csrfToken)
                })
                .then(function(r) { return r.json(); })
                .catch(function() { Swal.showValidationMessage('Erro de comunicação.'); });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                Swal.fire(result.value.success ? 'Cancelada!' : 'Erro', result.value.message, result.value.success ? 'success' : 'error')
                    .then(function() { if (result.value.success) location.reload(); });
            }
        });
    });

    // ═══ Carta de Correção no Pipeline ═══
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-correcao-nfe-pipeline');
        if (!btn) return;
        var nfeId = btn.getAttribute('data-id');
        var nfeNum = btn.getAttribute('data-numero');
        Swal.fire({
            icon: 'info',
            title: 'Carta de Correção — NF-e #' + nfeNum,
            html: '<div class="mb-3"><label class="form-label small fw-bold">Texto da Correção (mín. 15 chars)</label>'
                + '<textarea id="swalCorrecaoTexto" class="form-control" rows="4" placeholder="Descreva a correção..."></textarea></div>'
                + '<div class="alert alert-info small py-2"><i class="fas fa-info-circle me-1"></i>Não altera valores, impostos ou dados do emitente.</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Enviar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#17a2b8',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                var texto = document.getElementById('swalCorrecaoTexto').value.trim();
                if (texto.length < 15) {
                    Swal.showValidationMessage('Texto deve ter no mínimo 15 caracteres.');
                    return false;
                }
                return fetch('?page=nfe_documents&action=correction', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': __csrfToken
                    },
                    body: 'nfe_id=' + encodeURIComponent(nfeId) + '&texto=' + encodeURIComponent(texto) + '&csrf_token=' + encodeURIComponent(__csrfToken)
                })
                .then(function(r) { return r.json(); })
                .catch(function() { Swal.showValidationMessage('Erro de comunicação.'); });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                Swal.fire(result.value.success ? 'Enviada!' : 'Erro', result.value.message, result.value.success ? 'success' : 'error')
                    .then(function() { if (result.value.success) location.reload(); });
            }
        });
    });

    // ═══ Consultar Status NF-e no Pipeline ═══
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-check-status-pipeline');
        if (!btn) return;
        var nfeId = btn.getAttribute('data-id');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Consultando...';
        fetch('?page=nfe_documents&action=checkStatus&id=' + encodeURIComponent(nfeId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': __csrfToken
            },
            body: 'csrf_token=' + encodeURIComponent(__csrfToken)
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            Swal.fire('Consulta SEFAZ', resp.message || 'Sem resposta', resp.success ? 'success' : 'error');
        })
        .catch(function() {
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync me-1"></i> Consultar SEFAZ';
        });
    });

    // ═══ Link de pagamento via Gateway — Push Toast ═══
    var btnGenerateGwLink = document.getElementById('btnGenerateGwLink');
    var gwPaymentToastContainer = document.getElementById('gwPaymentToastContainer');

    // ── Seletor de Método de Pagamento ──
    // Elementos: hidden (valor real enviado), visible select (quando expandido), botão toggle
    var gwMethodHidden  = document.getElementById('gwSelectMethod');        // <input hidden value="auto">
    var gwMethodSelect  = document.getElementById('gwSelectMethodVisible'); // <select> (d-none por padrão)
    var btnToggleMethod = document.getElementById('btnToggleMethodSelect'); // botão filtro

    // Sincronizar visible → hidden sempre que mudar
    if (gwMethodSelect && gwMethodHidden) {
        gwMethodSelect.addEventListener('change', function() {
            gwMethodHidden.value = this.value;
        });
    }

    // Toggle: mostrar/esconder o select de método específico
    if (btnToggleMethod && gwMethodSelect && gwMethodHidden) {
        btnToggleMethod.addEventListener('click', function() {
            var isShown = !gwMethodSelect.classList.contains('d-none');
            if (isShown) {
                // Esconder — voltar para auto
                gwMethodSelect.classList.add('d-none');
                gwMethodHidden.value = 'auto';
                btnToggleMethod.classList.remove('btn-primary');
                btnToggleMethod.classList.add('btn-outline-secondary');
                btnToggleMethod.title = 'Escolher método específico';
            } else {
                // Mostrar — usar valor selecionado no select
                gwMethodSelect.classList.remove('d-none');
                gwMethodHidden.value = gwMethodSelect.value;
                btnToggleMethod.classList.remove('btn-outline-secondary');
                btnToggleMethod.classList.add('btn-primary');
                btnToggleMethod.title = 'Voltar para cliente escolhe';
            }
        });
    }

    /**
     * Cria uma notificação push (toast) no canto inferior direito da tela.
     * @param {string} type - 'loading', 'success', 'error'
     * @param {string} title
     * @param {string} body - HTML content
     * @param {number|null} autoDismissMs - auto fechar após X ms (null = manual)
     * @returns {HTMLElement} o elemento do toast
     */
    function createPaymentToast(type, title, body, autoDismissMs) {
        if (!gwPaymentToastContainer) return null;
        // Limpar toasts anteriores
        gwPaymentToastContainer.innerHTML = '';

        var icons = {
            loading: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
            success: '<i class="fas fa-check-circle text-success" style="font-size:1.2rem;"></i>',
            error:   '<i class="fas fa-times-circle text-danger" style="font-size:1.2rem;"></i>'
        };
        var borderColors = { loading: '#0d6efd', success: '#198754', error: '#dc3545' };

        var toast = document.createElement('div');
        toast.className = 'gw-payment-toast';
        toast.style.cssText = 'pointer-events:auto;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.18);' +
            'border-left:4px solid ' + (borderColors[type] || '#6c757d') + ';padding:14px 16px;margin-top:8px;' +
            'animation:gwToastSlideIn 0.35s cubic-bezier(0.34,1.56,0.64,1);position:relative;max-width:380px;';

        var timerBar = '';
        if (autoDismissMs) {
            timerBar = '<div class="gw-toast-timer" style="position:absolute;bottom:0;left:4px;right:0;height:3px;' +
                'background:' + (borderColors[type] || '#6c757d') + ';border-radius:0 0 12px 0;' +
                'animation:gwToastTimer ' + autoDismissMs + 'ms linear forwards;"></div>';
        }

        toast.innerHTML =
            '<div class="d-flex align-items-start gap-2">' +
                '<div class="flex-shrink-0 mt-1">' + (icons[type] || '') + '</div>' +
                '<div class="flex-grow-1" style="min-width:0;">' +
                    '<div class="fw-bold small" style="font-size:0.8rem;">' + title + '</div>' +
                    '<div class="small text-muted mt-1" style="font-size:0.72rem;">' + body + '</div>' +
                '</div>' +
                '<button type="button" class="btn-close flex-shrink-0" style="font-size:0.55rem;margin-top:2px;" ' +
                    'onclick="this.closest(\'.gw-payment-toast\').remove()"></button>' +
            '</div>' + timerBar;

        gwPaymentToastContainer.appendChild(toast);

        if (autoDismissMs) {
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, autoDismissMs);
        }

        return toast;
    }

    if (btnGenerateGwLink) {
        btnGenerateGwLink.addEventListener('click', function() {
            var gatewaySlug = document.getElementById('gwSelectGateway').value;
            var method = document.getElementById('gwSelectMethod').value;

            btnGenerateGwLink.disabled = true;
            btnGenerateGwLink.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando...';

            // Mostrar toast de loading
            createPaymentToast('loading', 'Gerando link de pagamento...',
                'Aguarde enquanto o link é criado via <strong>' + gatewaySlug + '</strong>.' +
                (method === 'auto' ? '<br><small class="text-muted">O cliente escolherá a forma de pagamento no checkout.</small>' : ''),
                null
            );

            fetch('?page=pipeline&action=generatePaymentLink', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'order_id=<?= (int)$order['id'] ?>&gateway_slug=' + encodeURIComponent(gatewaySlug) + '&method=' + encodeURIComponent(method) + '&csrf_token=' + encodeURIComponent(__csrfToken)
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success) {
                    throw new Error(resp.message || 'Não foi possível gerar o link de pagamento.');
                }

                var url = resp.payment_url || resp.boleto_url || resp.qr_code || '';
                var bodyHtml = '';

                if (url) {
                    bodyHtml += '<div class="input-group input-group-sm mt-1">' +
                        '<input type="text" class="form-control" value="' + url.replace(/"/g, '&quot;') + '" readonly onclick="this.select()" style="font-size:0.7rem;">' +
                        '<button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(\'' + url.replace(/'/g, "\\'") + '\');this.innerHTML=\'<i class=\'fas fa-check\'></i>\';" title="Copiar">' +
                        '<i class="fas fa-copy"></i></button>' +
                        '<a href="' + url + '" target="_blank" class="btn btn-outline-primary btn-sm" title="Abrir"><i class="fas fa-external-link-alt"></i></a>' +
                        '</div>';
                }

                if (resp.qr_code_base64) {
                    bodyHtml += '<div class="text-center mt-2">' +
                        '<img src="data:image/png;base64,' + resp.qr_code_base64 + '" alt="QR Code" ' +
                        'style="max-width:140px;border-radius:8px;border:1px solid #dee2e6;padding:4px;">' +
                        '</div>';
                }

                bodyHtml += '<div class="mt-1"><small class="text-muted" style="font-size:0.6rem;">' +
                    (method === 'auto' ? '🔄 O cliente escolhe a forma no checkout' : '✅ Método: ' + method.replace('_', ' ').toUpperCase()) +
                    '</small></div>';

                // Sucesso: toast com timer de 30 segundos
                createPaymentToast('success', '✅ Link gerado com sucesso!', bodyHtml, 30000);
            })
            .catch(function(err) {
                createPaymentToast('error', 'Falha ao gerar link',
                    '<span class="text-danger">' + (err.message || 'Erro inesperado.') + '</span>',
                    10000
                );
            })
            .finally(function() {
                btnGenerateGwLink.disabled = false;
                btnGenerateGwLink.innerHTML = '<i class="fas fa-bolt me-1"></i> Gerar Link';
            });
        });
    }

    // ═══ Copiar link salvo (saved payment link) ═══
    var btnCopySavedGwLink = document.getElementById('btnCopySavedGwLink');
    var savedGwLinkUrl = document.getElementById('savedGwLinkUrl');
    if (btnCopySavedGwLink && savedGwLinkUrl) {
        btnCopySavedGwLink.addEventListener('click', function() {
            if (!savedGwLinkUrl.value) return;
            navigator.clipboard.writeText(savedGwLinkUrl.value).then(function() {
                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1200, timerProgressBar: true })
                    .fire({ icon: 'success', title: 'Link copiado!' });
            });
        });
    }

    // ═══ Reenviar link de pagamento salvo via WhatsApp ═══
    var btnResendGwWhatsApp = document.getElementById('btnResendGwWhatsApp');
    if (btnResendGwWhatsApp && savedGwLinkUrl) {
        btnResendGwWhatsApp.addEventListener('click', function() {
            var link = savedGwLinkUrl.value;
            if (!link) return;
            var phone = <?= json_encode($order['customer_phone'] ?? '') ?>;
            // Limpar telefone — manter apenas dígitos
            phone = phone.replace(/\D/g, '');
            // Adicionar código do país (Brasil) se não tiver
            if (phone.length <= 11) phone = '55' + phone;
            var msg = encodeURIComponent('Olá! Segue o link para pagamento do seu pedido: ' + link);
            var waUrl = 'https://wa.me/' + phone + '?text=' + msg;
            window.open(waUrl, '_blank');
        });
    }

    // ═══ Filtrar métodos de pagamento por gateway selecionado ═══
    var gwSelectGateway = document.getElementById('gwSelectGateway');
    if (gwSelectGateway && gwMethodSelect) {
        var allMethodLabels = {
            'auto': '🔄 Cliente Escolhe',
            'pix': '📱 PIX',
            'credit_card': '💳 Cartão de Crédito',
            'debit_card': '💳 Cartão de Débito',
            'boleto': '📄 Boleto'
        };

        function filterGatewayMethods() {
            var selected = gwSelectGateway.options[gwSelectGateway.selectedIndex];
            if (!selected) return;

            var methods = [];
            try {
                methods = JSON.parse(selected.getAttribute('data-methods') || '[]');
            } catch(e) {
                methods = ['auto', 'pix', 'credit_card', 'boleto'];
            }

            // Sempre incluir 'auto' como primeira opção
            if (methods.indexOf('auto') === -1) {
                methods.unshift('auto');
            }

            var currentVal = gwMethodSelect.value;
            gwMethodSelect.innerHTML = '';

            methods.forEach(function(m) {
                var opt = document.createElement('option');
                opt.value = m;
                opt.textContent = allMethodLabels[m] || m;
                gwMethodSelect.appendChild(opt);
            });

            // Tentar manter a seleção anterior
            if (methods.indexOf(currentVal) !== -1) {
                gwMethodSelect.value = currentVal;
            } else {
                gwMethodSelect.value = 'auto';
            }

            // Sincronizar com o hidden apenas se o select está visível
            if (gwMethodHidden && !gwMethodSelect.classList.contains('d-none')) {
                gwMethodHidden.value = gwMethodSelect.value;
            }
        }

        gwSelectGateway.addEventListener('change', filterGatewayMethods);
        // Filtrar na carga inicial
        filterGatewayMethods();
    }

    // ═══ Seletor visual de formas de pagamento (cards) ═══
    var payMethodCards = document.querySelectorAll('#paymentMethodCards .payment-method-card');
    var finPaymentMethodSelect = document.getElementById('finPaymentMethod');
    var finPaymentDetails = document.getElementById('finPaymentDetails');
    var isCardReadOnly = <?= $isReadOnly ? 'true' : 'false' ?>;

    // Elementos condicionais gateway vs manual
    var paymentLinksSection = document.getElementById('paymentLinksSection');
    var finManualPaymentFields = document.getElementById('finManualPaymentFields');
    var finGatewayModeAlert = document.getElementById('finGatewayModeAlert');
    var finInstallmentRow = document.getElementById('installmentRow');

    /**
     * Alterna visibilidade entre modo gateway e modo manual
     * - Gateway: esconde status + entrada + parcelamento, mostra link de pagamento
     * - Manual: mostra status + entrada, esconde link de pagamento
     */
    function toggleGatewayMode(method) {
        var isGateway = (method === 'gateway');

        // Seção de link de pagamento: só visível no modo gateway
        if (paymentLinksSection) {
            paymentLinksSection.style.display = isGateway ? '' : 'none';
        }

        // Campos manuais (status, entrada): ocultos no modo gateway
        if (finManualPaymentFields) {
            finManualPaymentFields.style.display = isGateway ? 'none' : '';
        }

        // Alerta informativo do modo gateway
        if (finGatewayModeAlert) {
            finGatewayModeAlert.style.display = isGateway ? '' : 'none';
        }

        // Parcelamento: esconder no modo gateway
        if (isGateway && finInstallmentRow) {
            finInstallmentRow.style.display = 'none';
        }
    }

    // Executar no carregamento da página
    toggleGatewayMode(finPaymentMethodSelect ? finPaymentMethodSelect.value : '');

    if (payMethodCards.length && finPaymentMethodSelect && !isCardReadOnly) {
        var methodColors = <?= json_encode(array_map(function($pm) { return $pm['color']; }, $payMethods)) ?>;

        /**
         * Atualiza visualmente os cards de forma de pagamento para refletir o método ativo.
         * @param {string} activeMethod  Chave do método ativo (ex: 'pix', 'transferencia')
         */
        function updatePayMethodCardsVisual(activeMethod) {
            payMethodCards.forEach(function(c) {
                var m = c.getAttribute('data-method');
                var mColor = methodColors[m] || '#dee2e6';
                var isActive = (m === activeMethod);

                // Gerenciar classes Bootstrap de borda
                c.classList.toggle('border-2', isActive);
                c.classList.toggle('shadow', isActive);
                c.classList.toggle('border', !isActive);

                // Forçar cor da borda via CSS variable do Bootstrap + inline important
                c.style.setProperty('border-color', isActive ? mColor : '#dee2e6', 'important');
                c.style.setProperty('--bs-border-color', isActive ? mColor : '#dee2e6');

                // Ícone circular
                var circle = c.querySelector('.rounded-circle');
                if (circle) {
                    circle.style.background = isActive ? mColor : '#f8f9fa';
                    circle.style.color = isActive ? '#fff' : mColor;
                }

                // Label
                var label = c.querySelector('.small.fw-bold');
                if (label) {
                    label.style.color = isActive ? mColor : '#6c757d';
                }
            });
        }

        payMethodCards.forEach(function(card) {
            card.addEventListener('click', function() {
                var method = this.getAttribute('data-method');
                if (!method) return;

                // Se o select está desabilitado (ex: parcelas pagas), bloquear e avisar
                if (finPaymentMethodSelect.disabled) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Alteração bloqueada',
                        html: 'Existem parcelas já pagas. Para alterar a forma de pagamento, estorne os pagamentos primeiro no módulo <strong>Financeiro</strong>.',
                        confirmButtonColor: '#f39c12'
                    });
                    return;
                }

                // Atualizar select oculto
                finPaymentMethodSelect.value = method;

                // Atualizar visual dos cards ANTES de disparar change (para evitar conflito)
                updatePayMethodCardsVisual(method);

                // Disparar evento change para que os outros listeners reajam
                finPaymentMethodSelect.dispatchEvent(new Event('change'));

                // Verificar se o change handler reverteu o valor (ex: parcelas pagas)
                var actualValue = finPaymentMethodSelect.value;
                if (actualValue !== method) {
                    // O valor foi revertido — atualizar visual de volta para o valor real
                    updatePayMethodCardsVisual(actualValue);
                }

                // Mostrar seção de detalhes (status + entrada)
                if (finPaymentDetails) {
                    finPaymentDetails.style.display = actualValue ? '' : 'none';
                }

                // Alternar modo gateway vs manual
                toggleGatewayMode(actualValue);
            });
        });

        // Sincronizar visual dos cards ao carregar (garante que o visual bate com o select)
        updatePayMethodCardsVisual(finPaymentMethodSelect.value);
    }

    // Também reagir ao change do select oculto (caso disparado programaticamente)
    if (finPaymentMethodSelect) {
        finPaymentMethodSelect.addEventListener('change', function() {
            toggleGatewayMode(this.value);
        });
    }
})();
<?php endif; ?>

// ═══ ENVIO — Interações do card de envio ═══
(function() {
    // Botão "Usar endereço do cliente"
    var btnUseAddr = document.getElementById('btnUseCustomerAddress');
    if (btnUseAddr) {
        btnUseAddr.addEventListener('click', function() {
            var textarea = document.getElementById('shippingAddressTextarea');
            if (textarea) {
                textarea.value = <?= json_encode($customerFormattedAddress ?? '') ?>;
                document.getElementById('shippingAddressBackup').value = textarea.value;
                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1200, timerProgressBar: true })
                    .fire({ icon: 'success', title: 'Endereço preenchido!' });
            }
        });
    }

    // Alternar visibilidade dos cards conforme tipo de envio
    var shippingTypeSelect = document.getElementById('shippingType');
    if (shippingTypeSelect) {
        shippingTypeSelect.addEventListener('change', function() {
            var retiradaCard  = document.getElementById('shippingRetiradaCard');
            var addressCard   = document.getElementById('shippingAddressCard');
            var trackingSec   = document.getElementById('trackingSection');
            var printBtn      = document.getElementById('btnPrintLabel');
            var badgeLegend   = document.querySelector('#shippingBadgeLegend');

            var isRetirada = (this.value === 'retirada');

            // Labels dinâmicos
            var labelsMap = {
                'retirada': { label: 'Retirada na Loja', icon: 'fas fa-store', color: '#27ae60', emoji: '🏪' },
                'entrega':  { label: 'Entrega Própria',  icon: 'fas fa-motorcycle', color: '#e67e22', emoji: '🏍️' },
                'correios': { label: 'Correios / Transportadora', icon: 'fas fa-box', color: '#3498db', emoji: '📦' }
            };
            var info = labelsMap[this.value] || labelsMap['retirada'];

            // Atualizar borda do fieldset e badge do legend
            var fieldset = shippingTypeSelect.closest('fieldset');
            if (fieldset) {
                fieldset.style.borderColor = info.color;
                var legend = fieldset.querySelector('legend');
                if (legend) legend.style.color = info.color;
            }
            if (badgeLegend) {
                badgeLegend.style.background = info.color;
                badgeLegend.innerHTML = '<i class="' + info.icon + ' me-1"></i>' + info.label;
            }

            // Mostrar/ocultar cards
            if (retiradaCard)  retiradaCard.style.display  = isRetirada ? '' : 'none';
            if (addressCard)   addressCard.style.display    = isRetirada ? 'none' : '';

            // Sincronizar hidden field com textarea quando alternando
            var textarea = document.getElementById('shippingAddressTextarea');
            var backup   = document.getElementById('shippingAddressBackup');
            if (textarea && backup) {
                if (!isRetirada && textarea.value === '' && backup.value !== '') {
                    textarea.value = backup.value;
                }
            }
        });
    }

    // Sincronizar backup quando textarea muda
    var addrTextarea = document.getElementById('shippingAddressTextarea');
    if (addrTextarea) {
        addrTextarea.addEventListener('input', function() {
            var backup = document.getElementById('shippingAddressBackup');
            if (backup) backup.value = this.value;
        });
    }

    // ═══ Impressão da Guia de Endereçamento ═══
    var btnPrint = document.getElementById('btnPrintLabel');
    if (btnPrint) {
        btnPrint.addEventListener('click', function() {
            var textarea   = document.getElementById('shippingAddressTextarea');
            var address    = textarea ? textarea.value.trim() : '';
            var selType    = document.getElementById('shippingType');
            var typeLabel  = selType ? selType.options[selType.selectedIndex].text : '';

            if (!address) {
                Swal.fire({ icon: 'warning', title: 'Sem endereço', text: 'Preencha o endereço de entrega antes de imprimir a guia.', confirmButtonColor: '#e67e22' });
                return;
            }

            var orderNum     = '<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>';
            var custName     = <?= json_encode($order['customer_name'] ?? '—') ?>;
            var custPhone    = <?= json_encode($order['customer_phone'] ?? '') ?>;
            var senderName   = <?= json_encode(($company['company_name'] ?? 'Gráfica')) ?>;
            var senderPhone  = <?= json_encode(($company['company_phone'] ?? '')) ?>;
            var senderAddr   = <?= json_encode($companyAddress ?? '') ?>;
            var trackCode    = document.getElementById('trackingCodeInput') ? document.getElementById('trackingCodeInput').value.trim() : '';

            var printWin = window.open('', '_blank', 'width=600,height=500');
            printWin.document.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guia de Endereçamento — Pedido #${orderNum}</title>
    <style>
        @page { size: A5 landscape; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; color: #222; }
        .label-container { border: 3px solid #333; border-radius: 10px; padding: 24px; max-width: 550px; margin: 0 auto; }
        .label-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #ccc; padding-bottom: 12px; margin-bottom: 16px; }
        .label-header h2 { font-size: 15px; color: #555; margin: 0; }
        .label-header .order-num { font-size: 18px; font-weight: bold; color: #333; }
        .sender-section { background: #f8f9fa; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; font-size: 12px; color: #666; }
        .sender-section strong { color: #333; }
        .sender-addr { font-size: 11px; color: #888; margin-top: 4px; }
        .dest-section { border: 2px solid #e67e22; border-radius: 8px; padding: 16px; margin-bottom: 14px; }
        .dest-label { font-size: 11px; font-weight: bold; color: #e67e22; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .dest-name { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .dest-phone { font-size: 13px; color: #666; margin-bottom: 10px; }
        .dest-address { font-size: 16px; font-weight: 600; line-height: 1.5; padding: 10px; background: #fff8f0; border-left: 4px solid #e67e22; border-radius: 4px; }
        .footer-row { display: flex; justify-content: space-between; gap: 10px; font-size: 11px; color: #888; }
        .footer-row .box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; text-align: center; }
        .footer-row .box strong { display: block; font-size: 12px; color: #333; }
        .tracking-code { font-size: 14px; font-weight: bold; color: #3498db; letter-spacing: 1px; }
        .print-note { text-align: center; margin-top: 10px; font-size: 10px; color: #bbb; }
        @media print { .no-print { display: none !important; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="label-header">
            <h2>📦 GUIA DE ENDEREÇAMENTO</h2>
            <span class="order-num">Pedido #${orderNum}</span>
        </div>
        <div class="sender-section">
            <strong>REMETENTE:</strong> ${senderName}${senderPhone ? ' — ' + senderPhone : ''}
            ${senderAddr ? '<div class="sender-addr">' + senderAddr + '</div>' : ''}
        </div>
        <div class="dest-section">
            <div class="dest-label">✉ Destinatário</div>
            <div class="dest-name">${custName}</div>
            ${custPhone ? '<div class="dest-phone">📞 ' + custPhone + '</div>' : ''}
            <div class="dest-address">${address.replace(/\\n/g, '<br>')}</div>
        </div>
        <div class="footer-row">
            <div class="box">Modalidade<br><strong>${typeLabel}</strong></div>
            <div class="box">Rastreio<br><strong class="tracking-code">${trackCode || '—'}</strong></div>
            <div class="box">Data<br><strong>${new Date().toLocaleDateString('pt-BR')}</strong></div>
        </div>
        <p class="print-note">Recortar e colar na embalagem do pedido</p>
    </div>
    <div class="text-center no-print" style="margin-top:16px;">
        <button onclick="window.print()" style="padding:8px 24px;font-size:14px;cursor:pointer;border:1px solid #333;border-radius:4px;background:#fff;">🖨️ Imprimir</button>
        <button onclick="window.close()" style="padding:8px 18px;font-size:14px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#f5f5f5;margin-left:6px;">Fechar</button>
    </div>
</body>
</html>`);
            printWin.document.close();
            printWin.focus();
        });
    }
})();
</script>
