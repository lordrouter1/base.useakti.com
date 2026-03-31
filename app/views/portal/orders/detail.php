<?php
/**
 * Portal do Cliente — Detalhe do Pedido
 *
 * Variáveis: $order, $items, $installments, $extraCosts, $timeline,
 *            $allowApproval, $approvalContext, $successMsg, $company
 */
$orderId = (int) $order['id'];
$approvalContext = $approvalContext ?? false;
?>

<?php if ($approvalContext): ?>
<!-- ═══════════════════════════════════════════════════════════
     LAYOUT FOCADO DE APROVAÇÃO
     Interface simplificada quando acesso vem da aba "Aprovação"
     ═══════════════════════════════════════════════════════════ -->
<div class="portal-page portal-approval-focus">

    <!-- ═══ Header Compacto ═══ -->
    <div class="portal-approval-focus-header">
        <a href="?page=portal&action=orders&filter=approval" class="portal-approval-focus-back">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="portal-approval-focus-info">
            <h1 class="portal-approval-focus-title">
                <?= __p('approval_title', ['id' => $orderId]) ?>
            </h1>
            <span class="badge bg-warning text-dark">
                <i class="fas fa-clock me-1"></i> <?= __p('approval_status_pendente') ?>
            </span>
        </div>
    </div>

    <!-- ═══ Resumo do Pedido ═══ -->
    <div class="portal-approval-focus-summary">
        <div class="portal-approval-focus-summary-row">
            <span class="text-muted"><?= __p('approval_focus_order') ?></span>
            <strong>#<?= $orderId ?></strong>
        </div>
        <div class="portal-approval-focus-summary-row">
            <span class="text-muted"><?= __p('approval_focus_date') ?></span>
            <span><?= portal_date($order['created_at'] ?? '') ?></span>
        </div>
        <?php
            $subtotal   = array_sum(array_column($items, 'subtotal'));
            $extraTotal = array_sum(array_column($extraCosts, 'amount'));
            $discount   = (float) ($order['discount'] ?? 0);
            $total      = (float) ($order['total_amount'] ?? 0);
        ?>
        <div class="portal-approval-focus-summary-row portal-approval-focus-total">
            <span><?= __p('order_total') ?></span>
            <strong><?= portal_money($total) ?></strong>
        </div>
    </div>

    <!-- ═══ Itens do Pedido ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-list me-2"></i><?= __p('approval_items') ?> (<?= count($items) ?>)</h5>
        </div>
        <div class="portal-card-body p-0">
            <?php if (!empty($items)): ?>
                <div class="portal-items-table">
                    <div class="portal-items-header">
                        <span class="portal-items-col-product"><?= __p('order_item_product') ?></span>
                        <span class="portal-items-col-qty"><?= __p('order_item_qty') ?></span>
                        <span class="portal-items-col-price"><?= __p('order_item_price') ?></span>
                        <span class="portal-items-col-subtotal"><?= __p('order_item_subtotal') ?></span>
                    </div>
                    <?php foreach ($items as $item): ?>
                        <?php $itemDiscount = (float) ($item['item_discount'] ?? $item['discount'] ?? 0); ?>
                        <div class="portal-items-row">
                            <span class="portal-items-col-product">
                                <strong><?= e($item['product_name'] ?? '—') ?></strong>
                                <?php if (!empty($item['sku'])): ?>
                                    <small class="d-block text-muted"><?= e($item['sku']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($item['grade_description'])): ?>
                                    <small class="d-block text-muted"><?= e($item['grade_description']) ?></small>
                                <?php endif; ?>
                                <?php if ($itemDiscount > 0): ?>
                                    <small class="d-block text-success">
                                        <i class="fas fa-tag me-1"></i><?= __p('order_discount') ?>: -<?= portal_money($itemDiscount) ?>
                                    </small>
                                <?php endif; ?>
                            </span>
                            <span class="portal-items-col-qty"><?= (int) $item['quantity'] ?></span>
                            <span class="portal-items-col-price"><?= portal_money($item['unit_price']) ?></span>
                            <span class="portal-items-col-subtotal">
                                <?php
                                    $itemSubtotal = (float) ($item['subtotal'] ?? 0);
                                    $itemFinal    = $itemSubtotal - $itemDiscount;
                                ?>
                                <?php if ($itemDiscount > 0): ?>
                                    <small class="d-block text-muted text-decoration-line-through"><?= portal_money($itemSubtotal) ?></small>
                                    <strong class="text-success"><?= portal_money($itemFinal) ?></strong>
                                <?php else: ?>
                                    <?= portal_money($itemSubtotal) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ Custos Extras ═══ -->
    <?php if (!empty($extraCosts)): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-plus-circle me-2"></i><?= __p('order_extra_costs') ?></h5>
            </div>
            <div class="portal-card-body p-0">
                <div class="portal-items-table">
                    <?php foreach ($extraCosts as $cost): ?>
                        <div class="portal-items-row">
                            <span class="portal-items-col-product"><?= e($cost['description']) ?></span>
                            <span class="portal-items-col-subtotal"><?= portal_money($cost['amount']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Totais ═══ -->
    <?php
        $itemDiscountTotal = 0;
        foreach ($items as $it) {
            $itemDiscountTotal += (float) ($it['item_discount'] ?? $it['discount'] ?? 0);
        }
    ?>
    <div class="portal-card mb-3">
        <div class="portal-card-body">
            <div class="portal-totals">
                <div class="portal-totals-row">
                    <span><?= __p('order_subtotal') ?></span>
                    <span><?= portal_money($subtotal) ?></span>
                </div>
                <?php if ($itemDiscountTotal > 0): ?>
                    <div class="portal-totals-row text-success">
                        <span><i class="fas fa-tag me-1"></i><?= __p('order_discount') ?></span>
                        <span>- <?= portal_money($itemDiscountTotal) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($extraTotal > 0): ?>
                    <div class="portal-totals-row">
                        <span><i class="fas fa-plus-circle me-1"></i><?= __p('order_extra_costs') ?></span>
                        <span>+ <?= portal_money($extraTotal) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                    <div class="portal-totals-row text-success">
                        <span><?= __p('order_discount') ?></span>
                        <span>- <?= portal_money($discount) ?></span>
                    </div>
                <?php endif; ?>
                <div class="portal-totals-row portal-totals-final">
                    <strong><?= __p('order_total') ?></strong>
                    <strong><?= portal_money($total) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Observações da Empresa ═══ -->
    <?php if (!empty($order['quote_notes'])): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-sticky-note me-2"></i><?= __p('approval_company_notes') ?></h5>
            </div>
            <div class="portal-card-body">
                <p class="mb-0" style="white-space:pre-wrap;font-size:0.9rem;"><?= e($order['quote_notes']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Links de Catálogo / Pagamento (se houver) ═══ -->
    <?php if (!empty($catalogUrl)): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-body text-center py-2">
                <a href="<?= e($catalogUrl) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-info">
                    <i class="fas fa-external-link-alt me-1"></i>
                    <?= __p('catalog_link_btn') ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($order['payment_link_url'])): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-body text-center py-2">
                <a href="<?= e($order['payment_link_url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-success">
                    <i class="fas fa-credit-card me-1"></i>
                    <?= __p('payment_link_btn') ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Aprovação — Botão direto ═══ -->
    <div class="portal-approval-focus-approve-section">
        <p class="text-muted mb-3" style="font-size:0.8rem;text-align:center;">
            <?= __p('approval_disclaimer') ?>
        </p>
        <form method="POST" action="?page=portal&action=approveOrder" class="portal-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $orderId ?>">
            <input type="hidden" name="notes" value="">
            <button type="submit" class="btn portal-btn-primary w-100 portal-approval-focus-btn-approve">
                <i class="fas fa-check-circle me-2"></i>
                <?= __p('approval_approve_btn') ?>
            </button>
        </form>
    </div>

    <!-- ═══ Link para ver detalhes completos ═══ -->
    <div class="text-center mt-3 mb-3">
        <a href="?page=portal&action=orderDetail&id=<?= $orderId ?>" class="text-muted" style="font-size:0.85rem;">
            <i class="fas fa-expand-alt me-1"></i> <?= __p('approval_view_full_detail') ?>
        </a>
    </div>

</div>
<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════
     LAYOUT COMPLETO — Visualização padrão do pedido
     ═══════════════════════════════════════════════════════════ -->
<div class="portal-page">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header d-flex align-items-center justify-content-between">
        <div>
            <a href="?page=portal&action=orders" class="text-decoration-none text-muted" style="font-size:0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> <?= __p('orders_title') ?>
            </a>
            <h1 class="portal-page-title mt-1">
                <?= __p('order_detail_title', ['id' => $orderId]) ?>
            </h1>
        </div>
        <span class="badge bg-<?= portal_stage_class($order['pipeline_stage'] ?? '') ?>" style="font-size:0.85rem;padding:6px 12px;">
            <i class="<?= portal_stage_icon($order['pipeline_stage'] ?? '') ?> me-1"></i>
            <?= __p('status_' . ($order['pipeline_stage'] ?? 'orcamento')) ?>
        </span>
    </div>

    <!-- ═══ Mensagem de sucesso ═══ -->
    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success alert-sm">
            <i class="fas fa-check-circle me-1"></i> <?= e($successMsg) ?>
        </div>
    <?php endif; ?>

    <!-- ═══ Timeline do Pipeline ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-route me-2"></i><?= __p('order_timeline') ?></h5>
        </div>
        <div class="portal-card-body">
            <div class="portal-timeline">
                <?php foreach ($timeline as $step): ?>
                    <div class="portal-timeline-step portal-timeline-<?= e($step['status']) ?>">
                        <div class="portal-timeline-dot">
                            <?php if ($step['status'] === 'completed'): ?>
                                <i class="fas fa-check"></i>
                            <?php elseif ($step['status'] === 'current'): ?>
                                <i class="fas fa-circle"></i>
                            <?php elseif ($step['status'] === 'cancelled'): ?>
                                <i class="fas fa-times"></i>
                            <?php endif; ?>
                        </div>
                        <div class="portal-timeline-content">
                            <span class="portal-timeline-label">
                                <?= __p('status_' . e($step['stage'])) ?>
                            </span>
                            <?php if ($step['date']): ?>
                                <small class="portal-timeline-date"><?= portal_datetime($step['date']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ═══ Itens do Pedido ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-list me-2"></i><?= __p('order_items') ?></h5>
        </div>
        <div class="portal-card-body p-0">
            <?php if (!empty($items)): ?>
                <div class="portal-items-table">
                    <div class="portal-items-header">
                        <span class="portal-items-col-product"><?= __p('order_item_product') ?></span>
                        <span class="portal-items-col-qty"><?= __p('order_item_qty') ?></span>
                        <span class="portal-items-col-price"><?= __p('order_item_price') ?></span>
                        <span class="portal-items-col-subtotal"><?= __p('order_item_subtotal') ?></span>
                    </div>
                    <?php foreach ($items as $item): ?>
                        <div class="portal-items-row">
                            <span class="portal-items-col-product">
                                <strong><?= e($item['product_name'] ?? '—') ?></strong>
                                <?php if (!empty($item['sku'])): ?>
                                    <small class="d-block text-muted"><?= e($item['sku']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($item['grade_description'])): ?>
                                    <small class="d-block text-muted"><?= e($item['grade_description']) ?></small>
                                <?php endif; ?>
                            </span>
                            <span class="portal-items-col-qty"><?= (int) $item['quantity'] ?></span>
                            <span class="portal-items-col-price"><?= portal_money($item['unit_price']) ?></span>
                            <span class="portal-items-col-subtotal"><?= portal_money($item['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="portal-empty-state portal-empty-sm">
                    <p><?= __p('orders_no_items') ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ Custos Extras ═══ -->
    <?php if (!empty($extraCosts)): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-plus-circle me-2"></i><?= __p('order_extra_costs') ?></h5>
            </div>
            <div class="portal-card-body p-0">
                <div class="portal-items-table">
                    <?php foreach ($extraCosts as $cost): ?>
                        <div class="portal-items-row">
                            <span class="portal-items-col-product"><?= e($cost['description']) ?></span>
                            <span class="portal-items-col-subtotal"><?= portal_money($cost['amount']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Totais ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-body">
            <div class="portal-totals">
                <?php
                    $subtotal   = array_sum(array_column($items, 'subtotal'));
                    $extraTotal = array_sum(array_column($extraCosts, 'amount'));
                    $discount   = (float) ($order['discount'] ?? 0);
                    $total      = (float) ($order['total_amount'] ?? 0);
                ?>
                <div class="portal-totals-row">
                    <span><?= __p('order_subtotal') ?></span>
                    <span><?= portal_money($subtotal) ?></span>
                </div>
                <?php if ($extraTotal > 0): ?>
                    <div class="portal-totals-row">
                        <span><?= __p('order_extra_costs') ?></span>
                        <span>+ <?= portal_money($extraTotal) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                    <div class="portal-totals-row text-success">
                        <span><?= __p('order_discount') ?></span>
                        <span>- <?= portal_money($discount) ?></span>
                    </div>
                <?php endif; ?>
                <div class="portal-totals-row portal-totals-final">
                    <strong><?= __p('order_total') ?></strong>
                    <strong><?= portal_money($total) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Parcelas ═══ -->
    <?php if (!empty($installments)): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-wallet me-2"></i><?= __p('order_installments') ?></h5>
            </div>
            <div class="portal-card-body p-0">
                <div class="portal-items-table">
                    <?php foreach ($installments as $inst): ?>
                        <?php
                            $instStatus = $inst['status'] ?? 'pendente';
                            $instClass  = match($instStatus) {
                                'pago'      => 'success',
                                'atrasado'  => 'danger',
                                'cancelado' => 'secondary',
                                default     => 'warning',
                            };
                        ?>
                        <div class="portal-items-row">
                            <span class="portal-items-col-product">
                                <strong><?= __p('order_installment_number', ['n' => (int) $inst['installment_number']]) ?></strong>
                                <small class="d-block text-muted">
                                    <?= __p('financial_due_date', ['date' => portal_date($inst['due_date'])]) ?>
                                </small>
                            </span>
                            <span class="portal-items-col-price">
                                <span class="badge bg-<?= $instClass ?>"><?= __p('financial_' . $instStatus) ?></span>
                            </span>
                            <span class="portal-items-col-subtotal"><?= portal_money($inst['amount']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Rastreamento ═══ -->
    <?php if (!empty($order['tracking_code']) || in_array($order['pipeline_stage'] ?? '', ['envio','concluido'])): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-truck me-2"></i><?= __p('order_shipping') ?></h5>
            </div>
            <div class="portal-card-body">
                <?php if (!empty($order['tracking_code'])): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-barcode text-muted"></i>
                        <span><strong><?= __p('order_tracking') ?>:</strong> <?= e($order['tracking_code']) ?></span>
                    </div>
                    <?php if (!empty($order['tracking_carrier'])): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-building text-muted"></i>
                            <span><strong><?= __p('tracking_carrier') ?>:</strong> <?= e($order['tracking_carrier']) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="?page=portal&action=tracking&id=<?= $orderId ?>" class="btn btn-sm btn-outline-primary mt-1">
                    <i class="fas fa-map-marker-alt me-1"></i> <?= __p('tracking_track_btn') ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Observações ═══ -->
    <?php if (!empty($order['quote_notes'])): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-sticky-note me-2"></i><?= __p('order_notes') ?></h5>
            </div>
            <div class="portal-card-body">
                <p class="mb-0" style="white-space:pre-wrap;font-size:0.9rem;"><?= e($order['quote_notes']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Link de Catálogo / Orçamento ═══ -->
    <?php if (!empty($catalogUrl)): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header card-header-info-light">
                <h5><i class="fas fa-file-invoice-dollar me-2 icon-teal"></i><?= __p('catalog_link_title') ?></h5>
            </div>
            <div class="portal-card-body text-center">
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    <?= __p('catalog_link_description') ?>
                </p>
                <a href="<?= e($catalogUrl) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-teal btn-lg px-5 py-2 shadow-sm">
                    <i class="fas fa-external-link-alt me-2"></i>
                    <?= __p('catalog_link_btn') ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Link de Pagamento ═══ -->
    <?php if (!empty($order['payment_link_url'])): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-header card-header-green-light">
                <h5><i class="fas fa-credit-card me-2 text-success"></i><?= __p('payment_link_title') ?></h5>
            </div>
            <div class="portal-card-body text-center">
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    <?= __p('payment_link_description') ?>
                </p>
                <a href="<?= e($order['payment_link_url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-success btn-lg px-5 py-2 shadow-sm">
                    <i class="fas fa-lock me-2"></i>
                    <?= __p('payment_link_btn') ?>
                </a>
                <?php if (!empty($order['payment_link_created_at'])): ?>
                    <small class="d-block text-muted mt-2" style="font-size:0.75rem;">
                        <i class="fas fa-clock me-1"></i>
                        <?= __p('payment_link_generated_at', ['date' => portal_datetime($order['payment_link_created_at'])]) ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Ações do Pedido ═══ -->
    <div class="portal-order-actions-bar mb-3">
        <a href="?page=portal&action=messages&order_id=<?= $orderId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-comments me-1"></i> <?= __p('order_send_message') ?>
        </a>
        <?php if (in_array($order['pipeline_stage'] ?? '', ['envio','concluido'])): ?>
            <a href="?page=portal&action=tracking&id=<?= $orderId ?>" class="btn btn-outline-success btn-sm">
                <i class="fas fa-truck me-1"></i> <?= __p('orders_track') ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- ═══ Aprovação ═══ -->
    <?php
        $approvalStatus = $order['customer_approval_status'] ?? null;
    ?>
    <?php if ($approvalStatus === 'pendente' && $allowApproval): ?>
        <div class="portal-approval-card">
            <div class="portal-approval-header">
                <i class="fas fa-clipboard-check me-2"></i>
                <?= __p('approval_title', ['id' => $orderId]) ?>
            </div>
            <div class="portal-approval-body">
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    <?= __p('approval_disclaimer') ?>
                </p>

                <!-- Form Aprovar -->
                <form method="POST" action="?page=portal&action=approveOrder" class="portal-form mb-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $orderId ?>">
                    <div class="mb-3">
                        <label class="portal-label"><?= __p('approval_your_notes') ?></label>
                        <textarea name="notes" class="form-control portal-input" rows="2"
                                  placeholder="<?= eAttr(__p('approval_your_notes')) ?>"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn portal-btn-primary flex-fill">
                            <i class="fas fa-check me-1"></i> <?= __p('approval_approve_btn') ?>
                        </button>
                    </div>
                </form>

                <!-- Form Recusar -->
                <form method="POST" action="?page=portal&action=rejectOrder" class="portal-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $orderId ?>">
                    <input type="hidden" name="notes" value="">
                    <button type="submit" class="btn portal-btn-outline-danger w-100"
                            onclick="this.form.notes.value = this.closest('.portal-approval-body').querySelector('textarea[name=notes]').value;">
                        <i class="fas fa-times me-1"></i> <?= __p('approval_reject_btn') ?>
                    </button>
                </form>
            </div>
        </div>
    <?php elseif ($approvalStatus === 'aprovado'): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-body text-center">
                <span class="badge bg-success" style="font-size:0.9rem;padding:8px 16px;">
                    <i class="fas fa-check-circle me-1"></i>
                    <?= __p('approval_already', ['status' => __p('approval_status_aprovado')]) ?>
                </span>
                <?php if (!empty($order['customer_approval_at'])): ?>
                    <small class="d-block text-muted mt-2"><?= portal_datetime($order['customer_approval_at']) ?></small>
                <?php endif; ?>
                <?php if (!empty($order['customer_approval_notes'])): ?>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.85rem;"><?= e($order['customer_approval_notes']) ?></p>
                <?php endif; ?>

                <!-- Botão Cancelar Aprovação -->
                <form method="POST" action="?page=portal&action=cancelApproval" class="portal-form mt-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $orderId ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            onclick="return confirm('<?= eAttr(__p('approval_cancel_confirm')) ?>');">
                        <i class="fas fa-undo me-1"></i> <?= __p('approval_cancel_btn') ?>
                    </button>
                </form>
            </div>
        </div>
    <?php elseif ($approvalStatus === 'recusado'): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-body text-center">
                <span class="badge bg-danger" style="font-size:0.9rem;padding:8px 16px;">
                    <i class="fas fa-times-circle me-1"></i>
                    <?= __p('approval_already', ['status' => __p('approval_status_recusado')]) ?>
                </span>
                <?php if (!empty($order['customer_approval_at'])): ?>
                    <small class="d-block text-muted mt-2"><?= portal_datetime($order['customer_approval_at']) ?></small>
                <?php endif; ?>
                <?php if (!empty($order['customer_approval_notes'])): ?>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.85rem;"><?= e($order['customer_approval_notes']) ?></p>
                <?php endif; ?>

                <!-- Botão Cancelar Recusa (voltar para pendente) -->
                <form method="POST" action="?page=portal&action=cancelApproval" class="portal-form mt-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $orderId ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            onclick="return confirm('<?= eAttr(__p('approval_cancel_confirm')) ?>');">
                        <i class="fas fa-undo me-1"></i> <?= __p('approval_cancel_btn') ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; /* end approvalContext else */ ?>