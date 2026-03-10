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
    ?>

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0">
                <i class="<?= $stageInfo['icon'] ?> me-2" style="color:<?= $stageInfo['color'] ?>;"></i>
                Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
            </h1>
            <small class="text-muted">Criado em <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=pipeline" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
            <?php if ($currentStage === 'producao'): ?>
            <a href="?page=production_board" class="btn btn-outline-success btn-sm"><i class="fas fa-tasks me-1"></i> Painel de Produção</a>
            <?php endif; ?>
            <?php if (in_array($currentStage, ['producao', 'preparacao'])): ?>
            <a href="?page=pipeline&action=printProductionOrder&id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-warning btn-sm text-dark"><i class="fas fa-print me-1"></i> Ordem de Produção</a>
            <?php endif; ?>
            <?php if (in_array($currentStage, ['venda', 'financeiro'])): ?>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnHeaderPrintOrder"><i class="fas fa-file-invoice me-1"></i> Nota de Pedido</button>
            <?php endif; ?>
            <?php if (!$isReadOnly): ?>
            <a href="?page=orders&action=edit&id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i> Editar Pedido</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress Bar do Pipeline -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="pipeline-progress d-flex align-items-center justify-content-between position-relative">
                <?php 
                $stageKeys = array_keys($stages);
                $currentIdx = array_search($currentStage, $stageKeys);
                $totalStages = count($stageKeys);
                foreach ($stages as $sKey => $sInfo):
                    $sIdx = array_search($sKey, $stageKeys);
                    $isCompleted = $sIdx < $currentIdx;
                    $isCurrent = $sKey === $currentStage;
                    $isFuture = $sIdx > $currentIdx;
                ?>
                <div class="pipeline-step text-center flex-fill position-relative" style="z-index:1;">
                    <div class="pipeline-step-icon mx-auto mb-1 rounded-circle d-flex align-items-center justify-content-center 
                        <?php if($isCurrent): ?>border border-3<?php endif; ?>"
                        style="width:40px; height:40px; font-size:0.85rem;
                        background: <?= $isCompleted ? $sInfo['color'] : ($isCurrent ? '#fff' : '#e9ecef') ?>;
                        color: <?= $isCompleted ? '#fff' : ($isCurrent ? $sInfo['color'] : '#adb5bd') ?>;
                        border-color: <?= $isCurrent ? $sInfo['color'] : 'transparent' ?> !important;">
                        <i class="<?= $isCompleted ? 'fas fa-check' : $sInfo['icon'] ?>"></i>
                    </div>
                    <div class="small <?= $isCurrent ? 'fw-bold' : ($isFuture ? 'text-muted' : '') ?>" style="font-size:0.7rem;color:<?= $isCurrent ? $sInfo['color'] : '' ?>;">
                        <?= $sInfo['label'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <!-- Linha de progresso -->
                <div class="position-absolute w-100" style="height:3px; top:20px; z-index:0;">
                    <div class="bg-light w-100 rounded" style="height:3px;"></div>
                    <div class="rounded position-absolute top-0 start-0" 
                         style="height:3px; width:<?= ($currentIdx / max($totalStages - 1, 1)) * 100 ?>%; background: var(--accent-color);"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações rápidas de movimentação -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <span class="badge fs-6 py-2 px-3" style="background:<?= $stageInfo['color'] ?>;">
                        <i class="<?= $stageInfo['icon'] ?> me-1"></i> <?= $stageInfo['label'] ?>
                    </span>
                    <span class="ms-2 small <?= $isDelayed ? 'text-danger fw-bold' : 'text-muted' ?>">
                        <i class="fas fa-clock me-1"></i>
                        <?= ($hoursInStage >= 24) ? floor($hoursInStage/24).'d '.($hoursInStage%24).'h' : $hoursInStage.'h' ?>
                        na etapa
                        <?php if($isDelayed): ?>
                            <span class="badge bg-danger ms-1">ATRASADO +<?= $hoursInStage - $stageGoal ?>h</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <?php if (!$isReadOnly): ?>
                    <!-- Botão retroceder -->
                    <?php if ($currentIdx > 0): ?>
                    <a href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=<?= $stageKeys[$currentIdx - 1] ?>" 
                       class="btn btn-sm btn-outline-secondary btn-move-stage" 
                       data-dir="Retroceder" data-stage="<?= $stages[$stageKeys[$currentIdx - 1]]['label'] ?>"
                       data-target-stage="<?= $stageKeys[$currentIdx - 1] ?>"
                       data-order-id="<?= $order['id'] ?>">
                        <i class="fas fa-arrow-left me-1"></i> <?= $stages[$stageKeys[$currentIdx - 1]]['label'] ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Botão avançar -->
                    <?php if ($currentIdx < $totalStages - 1): ?>
                    <a href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=<?= $stageKeys[$currentIdx + 1] ?>" 
                       class="btn btn-sm btn-success btn-move-stage" 
                       data-dir="Avançar" data-stage="<?= $stages[$stageKeys[$currentIdx + 1]]['label'] ?>"
                       data-target-stage="<?= $stageKeys[$currentIdx + 1] ?>"
                       data-order-id="<?= $order['id'] ?>">
                        <?= $stages[$stageKeys[$currentIdx + 1]]['label'] ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?> <!-- /!$isReadOnly -->

                    <!-- Mover para qualquer etapa -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-random me-1"></i> Mover para...
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($stages as $sKey => $sInfo): ?>
                            <?php if ($sKey !== $currentStage): ?>
                            <li>
                                <a class="dropdown-item btn-move-stage" href="?page=pipeline&action=move&id=<?= $order['id'] ?>&stage=<?= $sKey ?>" 
                                   data-dir="Mover" data-stage="<?= $sInfo['label'] ?>"
                                   data-target-stage="<?= $sKey ?>"
                                   data-order-id="<?= $order['id'] ?>">
                                    <i class="<?= $sInfo['icon'] ?> me-2" style="color:<?= $sInfo['color'] ?>;"></i> <?= $sInfo['label'] ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isReadOnly): ?>
    <div class="alert <?= $currentStage === 'cancelado' ? 'alert-danger' : 'alert-success' ?> d-flex align-items-center mb-4" role="alert">
        <i class="fas <?= $currentStage === 'cancelado' ? 'fa-ban' : 'fa-check-double' ?> me-2 fs-5"></i>
        <div>
            <strong>Pedido <?= $currentStage === 'cancelado' ? 'Cancelado' : 'Concluído' ?>.</strong>
            Todos os campos estão em modo de visualização. Use o botão "Mover para..." para reabrir o pedido, se necessário.
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Coluna Esquerda: Informações e Formulário -->
        <div class="col-lg-8">
            <form method="POST" action="?page=pipeline&action=updateDetails">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">

                <!-- Dados do Cliente -->
                <fieldset class="p-4 mb-4">
                    <legend class="float-none w-auto px-2 fs-5 text-primary"><i class="fas fa-user-tag me-2"></i>Cliente</legend>
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
                            <input type="text" class="form-control" value="<?= htmlspecialchars($order['customer_email']) ?>" disabled>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($customerFormattedAddress)): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Endereço</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($customerFormattedAddress) ?>" disabled>
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
                <fieldset class="p-4 mb-4">
                    <legend class="float-none w-auto px-2 fs-5 text-primary">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Produtos do Orçamento
                        <?php if (!$isReadOnly): ?>
                        <a href="?page=orders&action=printQuote&id=<?= $order['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success ms-3">
                            <i class="fas fa-print me-1"></i> Imprimir Orçamento
                        </a>
                        <?php endif; ?>
                    </legend>

                    <!-- ═══ Link de Catálogo para o Cliente ═══ -->
                    <?php if ($currentStage === 'orcamento' && !$isReadOnly): ?>
                    <div class="card border-info border-opacity-25 mb-3" id="catalogLinkSection">
                        <div class="card-header bg-info py-2 d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 text-white"><i class="fas fa-share-alt me-2"></i>Catálogo do Cliente</h6>
                            <span class="badge bg-info bg-opacity-75 text-white">
                                <i class="fas fa-magic me-1"></i> O cliente monta a lista!
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <p class="small text-muted mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Gere um link de catálogo exclusivo para este pedido (#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>).
                                O cliente poderá navegar pelos produtos e adicionar ao carrinho.
                                Os itens adicionados aparecerão automaticamente no orçamento deste pedido em tempo real.
                            </p>
                            
                            <!-- Formulário para gerar novo link -->
                            <div id="catalogLinkForm">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Mostrar preços?</label>
                                        <select class="form-select form-select-sm" id="catalogShowPrices">
                                            <option value="0" selected>🚫 Não mostrar preços</option>
                                            <option value="1">✅ Sim, mostrar preços</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Validade (dias)</label>
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
                                        <button class="btn btn-info btn-sm w-100 fw-bold" id="btnGenerateCatalog" onclick="generateCatalogLink()">
                                            <i class="fas fa-magic me-1"></i> Gerar Link do Catálogo
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Link gerado (aparece abaixo do formulário) -->
                            <div id="catalogLinkActive" style="display:none;" class="mt-3">
                                <hr class="my-2">
                                <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-link me-1"></i>Link gerado para Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>: <span class="text-success" id="catalogLinkPriceInfo"></span></label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control text-primary fw-bold" id="catalogLinkUrl" readonly onclick="this.select()" style="font-size:0.82rem;">
                                    <button class="btn btn-outline-success" onclick="copyCatalogLink()" title="Copiar link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <a id="catalogLinkOpen" href="#" target="_blank" class="btn btn-outline-primary" title="Abrir catálogo">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button class="btn btn-outline-info" onclick="shareViaWhatsApp()" title="Enviar via WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deactivateCatalogLink()" title="Desativar link">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </div>
                                <small class="text-muted" id="catalogLinkMeta"></small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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
                                        <?= htmlspecialchars($pt['name']) ?> <?= $pt['is_default'] ? '(Padrão)' : '' ?>
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
                                        <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                        <?php if (!empty($item['combination_label'])): ?>
                                        <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($item['combination_label']) ?></small>
                                        <?php elseif (!empty($item['grade_description'])): ?>
                                        <br><small class="text-info"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($item['grade_description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($showEditQty): ?>
                                        <input type="number" min="1" step="1"
                                               class="form-control form-control-sm text-center item-qty-input py-0" 
                                               data-item-id="<?= $item['id'] ?>" data-unit-price="<?= $item['unit_price'] ?>"
                                               value="<?= $item['quantity'] ?>"
                                               style="width:70px; margin:0 auto; font-size:0.8rem;">
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
                                                   placeholder="0,00" style="font-size:0.8rem;">
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold item-net-amount <?= $itemDiscount > 0 ? 'text-success' : '' ?>">
                                        R$ <?= number_format($netAmount, 2, ',', '.') ?>
                                    </td>
                                    <?php endif; ?>
                                    <?php if (!$isReadOnly): ?>
                                    <td class="text-center">
                                        <a href="?page=orders&action=deleteItem&item_id=<?= $item['id'] ?>&order_id=<?= $order['id'] ?>&redirect=pipeline" 
                                           class="btn btn-sm btn-outline-danger btn-delete-item" title="Remover item">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
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
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>Nenhum produto adicionado ao orçamento ainda.
                    </div>
                    <?php endif; ?>

                    <?php if (!$isReadOnly): ?>
                    <!-- Formulário Adicionar Item -->
                    <div class="card border-primary border-opacity-25">
                        <div class="card-header bg-primary py-2">
                            <h6 class="mb-0 text-white"><i class="fas fa-plus-circle me-2"></i>Adicionar Produto</h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- O form real é colocado via JS para evitar nesting -->
                            <div class="row g-2 align-items-end" id="addItemRowPipeline">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">Produto</label>
                                    <select class="form-select form-select-sm" id="pipProductSelect">
                                        <option value="">Selecione um produto...</option>
                                        <?php foreach ($products as $prod): 
                                            $displayPrice = isset($customerPrices[$prod['id']]) ? $customerPrices[$prod['id']] : $prod['price'];
                                        ?>
                                        <option value="<?= $prod['id'] ?>" data-price="<?= $displayPrice ?>" data-original-price="<?= $prod['price'] ?>"
                                                data-has-combos="<?= !empty($productCombinations[$prod['id']]) ? '1' : '0' ?>">
                                            <?= htmlspecialchars($prod['name']) ?> — R$ <?= number_format($displayPrice, 2, ',', '.') ?>
                                            <?php if (isset($customerPrices[$prod['id']]) && $customerPrices[$prod['id']] != $prod['price']): ?>
                                            (base: R$ <?= number_format($prod['price'], 2, ',', '.') ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
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
                    <?php endif; ?> <!-- /!$isReadOnly add item form -->

                    <!-- Custos Extras do Orçamento -->
                    <div class="card border-warning border-opacity-25 mt-3">
                        <div class="card-header bg-warning bg-opacity-10 py-2">
                            <h6 class="mb-0 text-warning"><i class="fas fa-receipt me-2"></i>Custos Extras</h6>
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
                                            <td><?= htmlspecialchars($ec['description']) ?></td>
                                            <td class="text-end fw-bold <?= $ec['amount'] < 0 ? 'text-danger' : '' ?>">
                                                <?= $ec['amount'] < 0 ? '- R$ ' . number_format(abs($ec['amount']), 2, ',', '.') : 'R$ ' . number_format($ec['amount'], 2, ',', '.') ?>
                                            </td>
                                            <?php if (!$isReadOnly): ?>
                                            <td class="text-center">
                                                <a href="?page=pipeline&action=deleteExtraCost&cost_id=<?= $ec['id'] ?>&order_id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger btn-delete-extra" title="Remover custo">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
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
                <input type="hidden" name="quote_notes" value="<?= htmlspecialchars($order['quote_notes'] ?? '') ?>">
                <input type="hidden" name="price_table_id" value="<?= $order['price_table_id'] ?? '' ?>">
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
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($itemData['product_name']) ?></h6>
                                        <small class="text-muted">Qtd: <?= $itemData['quantity'] ?></small>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($allDone): ?>
                                        <span class="badge bg-success px-3 py-1"><i class="fas fa-check-double me-1"></i>Concluído</span>
                                    <?php elseif ($currentSector): ?>
                                        <span class="badge py-1 px-2" style="background:<?= $currentSector['color'] ?>;">
                                            <i class="<?= $currentSector['icon'] ?> me-1"></i><?= htmlspecialchars($currentSector['sector_name']) ?>
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
                                     title="<?= htmlspecialchars($sec['sector_name']) ?><?= $isDone && !empty($sec['completed_at']) ? ' — Concluído em '.date('d/m H:i', strtotime($sec['completed_at'])) : '' ?>">
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
                                        <?= htmlspecialchars($sec['sector_name']) ?>
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
                                            data-sector-name="<?= htmlspecialchars($revertSector['sector_name']) ?>">
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
                                            data-sector-name="<?= htmlspecialchars($currentSector['sector_name']) ?>">
                                        <i class="fas fa-check me-1"></i> Concluir <strong><?= htmlspecialchars($currentSector['sector_name']) ?></strong>
                                        <?php 
                                        $nextIdx = $currentSectorIdx + 1;
                                        if ($nextIdx < $totalItemSectors):
                                        ?>
                                        <span class="ms-1 opacity-75">→ <?= htmlspecialchars($sectors[$nextIdx]['sector_name']) ?></span>
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
                                        data-sector-name="<?= htmlspecialchars($lastSec['sector_name']) ?>">
                                    <i class="fas fa-undo me-1"></i> Retroceder <strong><?= htmlspecialchars($lastSec['sector_name']) ?></strong>
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
                        <small>Os itens abaixo foram deduzidos do armazém <strong><?= htmlspecialchars($warehouseName) ?></strong> ao entrar em preparação. Se o pedido for retrocedido, o estoque será automaticamente devolvido.</small>
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
                                    <td><?= htmlspecialchars($ded['product_name']) ?></td>
                                    <td><?= $ded['combination_label'] ? htmlspecialchars($ded['combination_label']) : '<span class="text-muted">—</span>' ?></td>
                                    <td class="text-center fw-bold text-danger"><?= number_format($ded['quantity'], 0, ',', '.') ?></td>
                                    <td><i class="fas fa-warehouse me-1 text-muted"></i><?= htmlspecialchars($ded['warehouse_name']) ?></td>
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
                                <i class="fas fa-box me-1 text-muted"></i><?= htmlspecialchars($oi['product_name']) ?> 
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
                            <div class="card border <?= $isChecked ? 'border-success bg-success bg-opacity-10' : 'border-light' ?> h-100 prep-check-card" 
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
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($checkedBy) ?>
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
                <fieldset class="p-4 mb-4">
                    <legend class="float-none w-auto px-2 fs-5 text-primary"><i class="fas fa-sliders-h me-2"></i>Gerenciamento</legend>
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
                <div class="card border-success border-opacity-50 shadow-sm mb-4">
                    <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                        <h6 class="mb-0 text-white fw-bold">
                            <i class="fas fa-file-invoice me-2"></i>Nota de Pedido
                        </h6>
                        <span class="badge bg-white bg-opacity-25 text-white" style="font-size:0.7rem;">
                            <i class="fas fa-print me-1"></i>Documento para impressão
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 small text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Gere a Nota de Pedido com os dados do cliente, produtos, valores e informações de pagamento.
                                </p>
                                <p class="mb-0 small text-muted">
                                    Ideal para entregar ao cliente como comprovante da compra.
                                    <strong>Os dados serão salvos automaticamente antes de imprimir.</strong>
                                </p>
                            </div>
                            <button type="submit" name="print_order_after_save" value="1"
                                    class="btn btn-success btn-sm ms-3 px-3 flex-shrink-0">
                                <i class="fas fa-print me-1"></i> Imprimir Nota de Pedido
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- ═══ FINANCEIRO — Card completo na etapa "financeiro" ═══ -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <?php
                    $isFinanceiroStage = ($currentStage === 'financeiro');
                    $finBorderColor = $isFinanceiroStage ? '#f39c12' : '#dee2e6';
                ?>
                <fieldset class="p-4 mb-4" style="border: 2px solid <?= $finBorderColor ?>; border-radius: 8px;">
                    <legend class="float-none w-auto px-3 fs-5" style="color: <?= $isFinanceiroStage ? '#f39c12' : '' ?>;">
                        <i class="fas fa-coins me-2"></i>Financeiro
                        <?php if ($isFinanceiroStage): ?>
                        <span class="badge ms-2" style="font-size:0.7rem;background:#f39c12;color:#fff;">
                            <i class="fas fa-money-bill-wave me-1"></i>Etapa Atual
                        </span>
                        <?php endif; ?>
                    </legend>

                    <?php if ($isFinanceiroStage && !empty($orderItems)): ?>
                    <!-- Resumo dos produtos com desconto por item (visível apenas na etapa financeiro) -->
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
                                            <i class="fas fa-box me-1 text-muted"></i><?= htmlspecialchars($oi['product_name']) ?>
                                            <?php if (!empty($oi['combination_label'])): ?>
                                            <small class="text-info ms-1"><?= htmlspecialchars($oi['combination_label']) ?></small>
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
                            <strong class="text-success" style="font-size:0.85rem;">
                                <i class="fas fa-coins me-1"></i>Total: R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <input type="hidden" name="discount" id="finDiscount" value="<?= $order['discount'] ?? 0 ?>">

                    <!-- Linha 1: Valor, Status, Forma de Pagamento -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted"><i class="fas fa-dollar-sign me-1"></i>Valor Total</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control fw-bold fs-5" value="<?= number_format($order['total_amount'], 2, ',', '.') ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted"><i class="fas fa-flag me-1"></i>Status Pagamento</label>
                            <select class="form-select" name="payment_status" id="finPaymentStatus" <?= $isReadOnly ? 'disabled' : '' ?>>
                                <option value="pendente" <?= ($order['payment_status'] ?? '') == 'pendente' ? 'selected' : '' ?>>⏳ Pendente</option>
                                <option value="parcial" <?= ($order['payment_status'] ?? '') == 'parcial' ? 'selected' : '' ?>>💳 Parcial</option>
                                <option value="pago" <?= ($order['payment_status'] ?? '') == 'pago' ? 'selected' : '' ?>>✅ Pago</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted"><i class="fas fa-credit-card me-1"></i>Forma de Pagamento</label>
                            <select class="form-select" name="payment_method" id="finPaymentMethod" <?= $isReadOnly ? 'disabled' : '' ?>>
                                <option value="">Selecione...</option>
                                <option value="dinheiro" <?= ($order['payment_method'] ?? '') == 'dinheiro' ? 'selected' : '' ?>>💵 Dinheiro</option>
                                <option value="pix" <?= ($order['payment_method'] ?? '') == 'pix' ? 'selected' : '' ?>>📱 PIX</option>
                                <option value="cartao_credito" <?= ($order['payment_method'] ?? '') == 'cartao_credito' ? 'selected' : '' ?>>💳 Cartão Crédito</option>
                                <option value="cartao_debito" <?= ($order['payment_method'] ?? '') == 'cartao_debito' ? 'selected' : '' ?>>💳 Cartão Débito</option>
                                <?php if ($canUseBoletoModule): ?>
                                <option value="boleto" <?= ($order['payment_method'] ?? '') == 'boleto' ? 'selected' : '' ?>>📄 Boleto</option>
                                <?php endif; ?>
                                <option value="transferencia" <?= ($order['payment_method'] ?? '') == 'transferencia' ? 'selected' : '' ?>>🏦 Transferência</option>
                            </select>
                        </div>
                    </div>

                    <!-- ═══ PARCELAMENTO / BOLETO — Aparece para cartão crédito e boleto ═══ -->
                    <div class="card mt-3 border-0 shadow-sm" id="installmentRow" style="display:none;">
                        <div class="card-header py-2 bg-warning bg-opacity-10">
                            <h6 class="mb-0 text-warning" style="font-size:0.85rem;" id="installmentCardTitle">
                                <i class="fas fa-calculator me-2"></i><span id="installmentCardTitleText">Parcelamento</span>
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Nº de Parcelas</label>
                                    <select class="form-select" name="installments" id="finInstallments" <?= $isReadOnly ? 'disabled' : '' ?>>
                                        <option value="">À vista</option>
                                        <?php for ($i = 2; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($order['installments'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?>x</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Entrada (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" min="0" class="form-control" name="down_payment" id="finDownPayment"
                                               value="<?= $order['down_payment'] ?? '0' ?>" 
                                               placeholder="0,00"
                                               <?= $isReadOnly ? 'disabled' : '' ?>>
                                    </div>
                                    <small class="text-muted" style="font-size:0.65rem;">Deixe 0 se não houver entrada</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Valor por Parcela</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control fw-bold" id="finInstallmentValue" name="installment_value_display" disabled
                                               value="<?= ($order['installment_value'] ?? 0) > 0 ? number_format($order['installment_value'], 2, ',', '.') : '' ?>">
                                        <input type="hidden" name="installment_value" id="finInstallmentValueHidden" value="<?= $order['installment_value'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="alert alert-info py-1 px-3 mb-0 small w-100" id="installmentInfo" style="display:none;">
                                        <i class="fas fa-calculator me-1"></i>
                                        <span id="installmentInfoText"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabela de parcelas detalhada (para boleto) -->
                            <div id="boletoInstallmentTable" class="mt-3" style="display:none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 small fw-bold text-muted"><i class="fas fa-list-ol me-1"></i>Detalhamento das Parcelas</h6>
                                    <button type="button" class="btn btn-sm btn-outline-dark" id="btnPrintBoletos" style="font-size:0.7rem;">
                                        <i class="fas fa-print me-1"></i> Imprimir Boletos
                                    </button>
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
                                        <tbody>
                                            <!-- Preenchido via JS -->
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted" style="font-size:0.65rem;">
                                    <i class="fas fa-info-circle me-1"></i>Os vencimentos são gerados a cada 30 dias a partir de hoje. Edite as datas conforme necessário antes de imprimir.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- ═══ LINKS DE PAGAMENTO — Integração futura ═══ -->
                    <div class="card mt-3 border-dashed border-secondary border-opacity-25" style="border-style:dashed !important;" id="paymentLinksSection">
                        <div class="card-header py-2 bg-light">
                            <h6 class="mb-0 text-muted" style="font-size:0.85rem;">
                                <i class="fas fa-link me-2"></i>Links de Pagamento
                            </h6>
                        </div>
                        <div class="card-body p-3 text-center">
                            <i class="fas fa-plug text-muted d-block mb-2" style="font-size:1.3rem;opacity:0.3;"></i>
                            <p class="small text-muted mb-1"><strong>Integração com Gateways de Pagamento</strong></p>
                            <p class="small text-muted mb-0" style="font-size:0.72rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                Em breve: gerar links de pagamento via PagSeguro, Mercado Pago, Stripe, PIX dinâmico e outros.
                                O cliente receberá o link por WhatsApp/e-mail e poderá pagar online.
                            </p>
                            <?php if ($isFinanceiroStage && !$isReadOnly): ?>
                            <div class="mt-2 d-flex justify-content-center gap-2">
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-qrcode me-1"></i>PIX Dinâmico</span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-credit-card me-1"></i>PagSeguro</span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fas fa-shopping-bag me-1"></i>Mercado Pago</span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><i class="fab fa-stripe-s me-1"></i>Stripe</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($canUseFiscalModule && $canUseNfeModule): ?>
                    <!-- ═══ FISCAL — Nota Fiscal ═══ -->
                    <div class="card mt-3 border-0 shadow-sm" id="fiscalSection">
                        <div class="card-header py-2 bg-success bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-success" style="font-size:0.85rem;">
                                    <i class="fas fa-file-invoice me-2"></i>Fiscal / Nota Fiscal
                                </h6>
                                <?php if (!$isReadOnly): ?>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btnEmitirNF" style="font-size:0.7rem;">
                                    <i class="fas fa-file-export me-1"></i> Emitir NF
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Nº da Nota Fiscal</label>
                                    <input type="text" class="form-control" name="nf_number" id="nfNumber"
                                           placeholder="Ex: 000123"
                                           value="<?= htmlspecialchars($order['nf_number'] ?? '') ?>"
                                           <?= $isReadOnly ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Série</label>
                                    <input type="text" class="form-control" name="nf_series" id="nfSeries"
                                           placeholder="Ex: 1"
                                           value="<?= htmlspecialchars($order['nf_series'] ?? '') ?>"
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
                                           value="<?= htmlspecialchars($order['nf_access_key'] ?? '') ?>"
                                           <?= $isReadOnly ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Observações Fiscais</label>
                                    <input type="text" class="form-control" name="nf_notes" id="nfNotes"
                                           placeholder="Observações sobre a nota fiscal..."
                                           value="<?= htmlspecialchars($order['nf_notes'] ?? '') ?>"
                                           <?= $isReadOnly ? 'disabled' : '' ?>>
                                </div>
                            </div>

                            <!-- Placeholder para integração com emissor de NF -->
                            <div class="alert alert-light border mt-3 mb-0 py-2 px-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-plug text-muted mt-1" style="opacity:0.4;"></i>
                                    <div>
                                        <p class="small text-muted mb-0" style="font-size:0.72rem;">
                                            <strong>Integração com Emissor Fiscal:</strong>
                                            Em breve será possível emitir NF-e e NFC-e diretamente pelo sistema, com integração 
                                            via API (ex: Bling, Tiny ERP, NFe.io, eNotas). Por enquanto, preencha os dados manualmente 
                                            após emitir pelo sistema fiscal.
                                        </p>
                                        <?php if ($isFinanceiroStage && !$isReadOnly): ?>
                                        <div class="mt-1 d-flex gap-2">
                                            <span class="badge bg-light text-muted border" style="font-size:0.6rem;"><i class="fas fa-file-invoice me-1"></i>NFe.io</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.6rem;"><i class="fas fa-bolt me-1"></i>Bling</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.6rem;"><i class="fas fa-cube me-1"></i>Tiny ERP</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.6rem;"><i class="fas fa-file-alt me-1"></i>eNotas</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                <input type="hidden" name="nf_number" value="<?= htmlspecialchars($order['nf_number'] ?? '') ?>">
                <input type="hidden" name="nf_series" value="<?= htmlspecialchars($order['nf_series'] ?? '') ?>">
                <input type="hidden" name="nf_status" value="<?= $order['nf_status'] ?? '' ?>">
                <input type="hidden" name="nf_access_key" value="<?= htmlspecialchars($order['nf_access_key'] ?? '') ?>">
                <input type="hidden" name="nf_notes" value="<?= htmlspecialchars($order['nf_notes'] ?? '') ?>">
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
                                <i class="fas fa-box me-1 text-muted"></i><?= htmlspecialchars($oi['product_name']) ?>
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
                            <input type="text" class="form-control" value="<?= htmlspecialchars($order['customer_name'] ?? '—') ?> — <?= $order['customer_phone'] ?? '' ?>" disabled>
                        </div>
                    </div>

                    <!-- Retirada na loja (visível apenas quando tipo = retirada) -->
                    <div class="card mb-3 border-light" id="shippingRetiradaCard" style="<?= ($shippingType !== 'retirada') ? 'display:none;' : '' ?>">
                        <div class="card-body p-3 text-center">
                            <i class="fas fa-store text-success d-block mb-2" style="font-size:2.2rem;opacity:0.6;"></i>
                            <span class="text-muted fs-6">O cliente irá <strong>retirar na loja</strong>.</span>
                            <p class="text-muted small mt-1 mb-0">Nenhum endereço de entrega necessário.</p>
                        </div>
                    </div>

                    <!-- Endereço de Entrega (visível quando tipo = entrega ou correios) -->
                    <div class="card mb-3 border-warning" id="shippingAddressCard" style="<?= ($shippingType === 'retirada') ? 'display:none;' : '' ?>">
                        <div class="card-header py-2 bg-warning bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-warning" style="font-size:0.85rem;">
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
                                      <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($shippingAddress) ?></textarea>
                            <?php if (!empty($customerFormattedAddress) && !$isReadOnly): ?>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUseCustomerAddress">
                                    <i class="fas fa-user-tag me-1"></i> Usar endereço do cliente
                                </button>
                                <small class="text-muted ms-2"><?= htmlspecialchars($customerFormattedAddress) ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Hidden field para manter o endereço quando retirada está selecionada -->
                    <input type="hidden" name="shipping_address_backup" id="shippingAddressBackup" value="<?= htmlspecialchars($shippingAddress) ?>"

                    >

                    <!-- Rastreamento e Código -->
                    <div class="card mb-3 border-0 shadow-sm" id="trackingSection">
                        <div class="card-header py-2 bg-primary bg-opacity-10">
                            <h6 class="mb-0 text-primary" style="font-size:0.85rem;">
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
                                               value="<?= htmlspecialchars($trackingCode) ?>" 
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
                                Código: <strong class="user-select-all"><?= htmlspecialchars($trackingCode) ?></strong>
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
                    <div class="card border-dashed border-secondary border-opacity-25 mb-0" style="border-style:dashed !important;">
                        <div class="card-body p-3 text-center">
                            <i class="fas fa-plug text-muted d-block mb-2" style="font-size:1.5rem;opacity:0.3;"></i>
                            <p class="small text-muted mb-1"><strong>Integração com Transportadoras</strong></p>
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
                <input type="hidden" name="shipping_address" value="<?= htmlspecialchars($order['shipping_address'] ?? '') ?>">
                <input type="hidden" name="tracking_code" value="<?= $order['tracking_code'] ?? '' ?>">
                <?php endif; ?>

                <?php if (!$isReadOnly): ?>
                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Coluna Direita: Timeline / Histórico -->
        <div class="col-lg-4">
            <!-- Histórico de Movimentação do Pipeline -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-history me-2"></i>Histórico de Movimentação</h6>
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
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-success fw-bold"><i class="fas fa-clipboard-list me-2"></i>Registro</h6>
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
                                    <option value="<?= $oi['id'] ?>"><?= htmlspecialchars($oi['product_name'] ?? 'Produto #'.$oi['product_id']) ?> (Qtd: <?= $oi['quantity'] ?>)</option>
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

                <div class="card-body p-3" style="max-height: 500px; overflow-y: auto;">
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
                        <div class="d-flex gap-2 mb-3 pb-3 border-bottom detail-log-entry">
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
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-1" style="font-size:0.6rem;">
                                            <i class="fas fa-box me-1"></i><?= htmlspecialchars($log['product_name'] ?? 'Produto') ?>
                                        </span>
                                        <span class="small fw-bold"><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="text-muted" style="font-size:0.6rem;"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></span>
                                        <?php if (!$isReadOnly): ?>
                                        <button type="button" class="btn btn-sm p-0 text-danger btn-delete-detail-log" 
                                                data-log-id="<?= $log['id'] ?>" title="Excluir" style="font-size:0.65rem;line-height:1;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($log['message'])): ?>
                                <div class="small mt-1" style="white-space:pre-wrap;"><?= htmlspecialchars($log['message']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($log['file_path'])): ?>
                                    <?php if ($isImage): ?>
                                    <div class="mt-2">
                                        <a href="<?= $log['file_path'] ?>" target="_blank">
                                            <img src="<?= $log['file_path'] ?>" class="rounded border" 
                                                 style="max-width:100%;max-height:150px;" alt="<?= htmlspecialchars($log['file_name']) ?>">
                                        </a>
                                        <div class="small text-muted mt-1"><i class="fas fa-image me-1"></i><?= htmlspecialchars($log['file_name']) ?></div>
                                    </div>
                                    <?php elseif ($isPdf): ?>
                                    <div class="mt-2">
                                        <a href="<?= $log['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-file-pdf me-1"></i><?= htmlspecialchars($log['file_name']) ?>
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
document.addEventListener('DOMContentLoaded', function() {
    // ── CSRF token global para fetch POST (não coberto pelo $.ajaxSetup do jQuery) ──
    const __csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const __csrfToken = __csrfMeta ? __csrfMeta.getAttribute('content') : '';
    
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

    function needsWarehouseForTransition(fromStage, toStage) {
        return preProductionStages.includes(fromStage) && productionStages.includes(toStage);
    }
    
    document.querySelectorAll('.btn-move-stage').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            const dir = this.dataset.dir;
            const stage = this.dataset.stage;
            const targetStage = this.dataset.targetStage || '';
            const orderId = this.dataset.orderId || '<?= $order['id'] ?>';
            
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

    // Product combinations data for variation selector
    const productCombosPipeline = <?= json_encode($productCombinations ?? []) ?>;

    // Auto-preencher preço ao selecionar produto (pipeline)
    const pipProductSelect = document.getElementById('pipProductSelect');
    const pipPriceInput = document.getElementById('pipPriceInput');
    const pipVariationWrap = document.getElementById('variationWrapPipeline');
    const pipVariationSelect = document.getElementById('pipVariationSelect');

    if (pipProductSelect && pipPriceInput) {
        pipProductSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.price) {
                pipPriceInput.value = parseFloat(opt.dataset.price).toFixed(2);
            }
            // Show/hide variation selector
            const pid = this.value;
            if (pipVariationWrap && pipVariationSelect) {
                if (pid && productCombosPipeline[pid] && productCombosPipeline[pid].length > 0) {
                    pipVariationWrap.style.display = '';
                    pipVariationSelect.innerHTML = '<option value="">Selecione a variação...</option>';
                    productCombosPipeline[pid].forEach(c => {
                        const lbl = c.combination_label + (c.price_override ? ' — R$ ' + parseFloat(c.price_override).toFixed(2).replace('.', ',') : '');
                        pipVariationSelect.innerHTML += `<option value="${c.id}" data-price="${c.price_override || ''}" data-label="${c.combination_label}">${lbl}</option>`;
                    });
                } else {
                    pipVariationWrap.style.display = 'none';
                    pipVariationSelect.innerHTML = '';
                }
            }
        });

        if (pipVariationSelect) {
            pipVariationSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.dataset.price && opt.dataset.price !== '') {
                    pipPriceInput.value = parseFloat(opt.dataset.price).toFixed(2);
                }
            });
        }
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
    }
});

// ── Funções globais do catálogo (fora do DOMContentLoaded) ──

function generateCatalogLink() {
    const showPrices = document.getElementById('catalogShowPrices').value;
    const expiresIn = document.getElementById('catalogExpires').value;
    const btn = document.getElementById('btnGenerateCatalog');
    
    // Desabilitar botão enquanto gera
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando...';
    
    fetch('?page=pipeline&action=generateCatalogLink', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=<?= $order['id'] ?>&show_prices=${showPrices}&expires_in=${expiresIn}&csrf_token=${encodeURIComponent(__csrfToken)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            catalogLinkData = data;
            showActiveCatalogLink(data);
        } else {
            alert(data.message || 'Erro ao gerar link');
        }
    })
    .catch(() => {
        alert('Erro de conexão ao gerar o link.');
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
    const customerName = '<?= htmlspecialchars($order['customer_name'] ?? 'cliente') ?>';
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
    if (!confirm('Desativar link? O cliente não poderá mais acessar o catálogo.')) return;
    
    fetch('?page=pipeline&action=deactivateCatalogLink', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=<?= $order['id'] ?>&csrf_token=' + encodeURIComponent(__csrfToken)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showCatalogLinkForm();
        }
    });
}

function showActiveCatalogLink(data) {
    const activeEl = document.getElementById('catalogLinkActive');
    const formEl = document.getElementById('catalogLinkForm');
    if (!activeEl || !formEl) return;
    
    // Mostrar o link abaixo do formulário
    activeEl.style.display = '';
    
    // Desabilitar campos do formulário (já tem link ativo)
    document.getElementById('catalogShowPrices').disabled = true;
    document.getElementById('catalogExpires').disabled = true;
    const btn = document.getElementById('btnGenerateCatalog');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Link ativo';
    btn.classList.replace('btn-info', 'btn-success');
    
    document.getElementById('catalogLinkUrl').value = data.url;
    document.getElementById('catalogLinkOpen').href = data.url;
    
    const priceInfo = document.getElementById('catalogLinkPriceInfo');
    priceInfo.textContent = data.show_prices ? '(com preços)' : '(sem preços)';
    
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
    
    document.getElementById('catalogShowPrices').disabled = false;
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
    
    const totalAmount = <?= (float)($order['total_amount'] ?? 0) ?>;
    const cardTitleText = document.getElementById('installmentCardTitleText');
    
    // Formas de pagamento que aceitam parcelamento
    const parcelableMethods = ['cartao_credito'<?= $canUseBoletoModule ? ", 'boleto'" : '' ?>];
    
    function updateCardTitle() {
        if (!cardTitleText) return;
        const method = paymentMethod.value;
        const n = parseInt(installments ? installments.value : 0) || 0;
        if (method === 'boleto' && n < 2) {
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
    }
    
    function calcInstallment() {
        const n = parseInt(installments ? installments.value : 0) || 0;
        const discount = parseFloat(discountField ? discountField.value : 0) || 0;
        const downPayment = parseFloat(downPaymentField ? downPaymentField.value : 0) || 0;
        const finalTotal = Math.max(0, totalAmount - discount);
        const amountAfterDown = Math.max(0, finalTotal - downPayment);
        const isBoleto = (paymentMethod.value === 'boleto');
        
        updateCardTitle();
        
        if (n >= 2 && finalTotal > 0) {
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
        } else if (paymentMethod.value === 'boleto' && finalTotal > 0) {
            // Boleto à vista (sem parcelamento) — 1 parcela
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
    let existingInstallmentCount = <?= (int)($existingInstallmentCount ?? 0) ?>;
    const orderId = <?= (int)$order['id'] ?>;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    paymentMethod.addEventListener('change', function() {
        const newMethod = this.value;

        // Se existem parcelas geradas no banco, perguntar antes de alterar
        if (existingInstallmentCount > 0) {
            Swal.fire({
                title: '<i class="fas fa-exclamation-triangle me-2 text-warning"></i>Alterar forma de pagamento?',
                html: `<p>Este pedido possui <strong>${existingInstallmentCount} parcela(s)</strong> já gerada(s).</p>
                       <p class="text-danger"><i class="fas fa-trash-alt me-1"></i>Ao alterar a forma de pagamento, todas as parcelas serão <strong>removidas</strong>.</p>
                       <p class="small text-muted">Você poderá gerar novas parcelas após alterar.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check me-1"></i> Sim, alterar e remover parcelas',
                cancelButtonText: '<i class="fas fa-times me-1"></i> Cancelar',
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Deletar parcelas via AJAX
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('csrf_token', csrfToken);

                    fetch('?page=pipeline&action=deleteInstallments', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(data => {
                        if (data.success) {
                            existingInstallmentCount = 0;
                            // Resetar campos de parcelamento na UI
                            if (installments) installments.value = '';
                            if (installmentValue) installmentValue.value = '';
                            if (installmentValueHidden) installmentValueHidden.value = '';
                            if (downPaymentField) downPaymentField.value = '0';
                            if (boletoTable) boletoTable.style.display = 'none';
                            if (installmentInfo) installmentInfo.style.display = 'none';

                            previousPaymentMethod = newMethod;
                            toggleInstallmentRow();

                            Swal.fire({
                                icon: 'success',
                                title: 'Parcelas removidas!',
                                text: `${data.deleted} parcela(s) removida(s). Configure o novo parcelamento se necessário.`,
                                timer: 3000,
                                showConfirmButton: false
                            });
                        } else {
                            // Reverter select
                            paymentMethod.value = previousPaymentMethod;
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível remover as parcelas.' });
                        }
                    })
                    .catch((err) => {
                        console.error('deleteInstallments error:', err);
                        paymentMethod.value = previousPaymentMethod;
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão ao remover parcelas.' });
                    });
                } else {
                    // Cancelou — reverter o select para o valor anterior
                    paymentMethod.value = previousPaymentMethod;
                }
            });
        } else {
            // Sem parcelas existentes — alterar normalmente
            previousPaymentMethod = newMethod;
            toggleInstallmentRow();
        }
    });
    if (installments) installments.addEventListener('change', function() { calcInstallment(); updateCardTitle(); });
    if (discountField) discountField.addEventListener('input', calcInstallment);
    if (downPaymentField) downPaymentField.addEventListener('input', calcInstallment);
    
    toggleInstallmentRow();

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

    // ═══ Emitir NF (placeholder) ═══
    var btnNF = document.getElementById('btnEmitirNF');
    if (btnNF) {
        btnNF.addEventListener('click', function() {
            Swal.fire({
                icon: 'info',
                title: 'Emissão de Nota Fiscal',
                html: '<p class="mb-2">A emissão automática de NF-e ainda não está integrada.</p><p class="small text-muted">Por enquanto, emita a nota no seu sistema fiscal e preencha os dados (número, série, chave de acesso) nos campos acima.</p><hr><p class="small text-muted mb-0"><i class="fas fa-plug me-1"></i>Integração futura com: NFe.io, Bling, Tiny ERP, eNotas</p>',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#27ae60'
            });
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
