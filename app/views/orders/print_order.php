<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; font-size: 12px; }
            .container { max-width: 100% !important; padding: 15px !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .table th { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .total-row { background: #eaf7ee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .order-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 15mm; }
        }
        body { background: #f5f5f5; font-family: 'Segoe UI', Arial, sans-serif; }
        .order-header { border-bottom: 3px solid #2ecc71; padding-bottom: 15px; margin-bottom: 20px; }
        .company-logo img { max-height: 80px; }
        .company-name { font-size: 1.8rem; font-weight: 800; color: #2c3e50; }
        .order-number { font-size: 1.4rem; color: #2ecc71; font-weight: 700; }
        .info-label { font-weight: 600; color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; }
        .info-value { font-weight: 500; color: #2c3e50; }
        .total-row { background: #eaf7ee !important; }
        .total-value { font-size: 1.4rem; font-weight: 800; color: #27ae60; }
        .footer-note { border-top: 2px solid #ecf0f1; padding-top: 15px; margin-top: 30px; }
        .order-badge { display: inline-block; padding: 4px 14px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; }
        .payment-info { border-left: 4px solid #2ecc71; padding-left: 12px; }
    </style>
</head>
<body>
    <?php
    // Helper para formatar endereço do cliente (JSON -> string)
    $customerFormattedAddress = '';
    if (!empty($order['customer_address'])) {
        $customerFormattedAddress = \Akti\Models\CompanySettings::formatCustomerAddress($order['customer_address']);
    }

    // Mapas de labels
    $paymentMethodMap = [
        'dinheiro'          => '💵 Dinheiro',
        'pix'               => '📱 PIX',
        'cartao_credito'    => '💳 Cartão de Crédito',
        'cartao_debito'     => '💳 Cartão de Débito',
        'boleto'            => '📄 Boleto',
        'transferencia'     => '🏦 Transferência',
        'cheque'            => '📝 Cheque',
        'outro'             => 'Outro',
    ];
    $paymentStatusMap = [
        'pendente' => ['label' => 'Pendente', 'color' => '#f39c12', 'icon' => '⏳'],
        'parcial'  => ['label' => 'Parcial', 'color' => '#3498db', 'icon' => '💳'],
        'pago'     => ['label' => 'Pago', 'color' => '#27ae60', 'icon' => '✅'],
    ];
    $paymentStatus = $order['payment_status'] ?? 'pendente';
    $paymentStatusInfo = $paymentStatusMap[$paymentStatus] ?? $paymentStatusMap['pendente'];
    ?>

    <!-- Barra de ações (não imprime) -->
    <div class="no-print bg-dark text-white py-2">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-invoice me-2"></i>Nota de Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></span>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-success btn-sm"><i class="fas fa-print me-1"></i> Imprimir</button>
                <button onclick="window.close()" class="btn btn-outline-light btn-sm"><i class="fas fa-times me-1"></i> Fechar</button>
            </div>
        </div>
    </div>

    <div class="container py-4" style="max-width: 800px;">
        <!-- Cabeçalho da empresa -->
        <div class="order-header d-flex justify-content-between align-items-start">
            <div>
                <?php if (!empty($company['company_logo']) && file_exists($company['company_logo'])): ?>
                <div class="company-logo mb-1">
                    <img src="<?= $company['company_logo'] ?>" alt="Logo">
                </div>
                <?php endif; ?>
                <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'Minha Empresa') ?></div>
                <?php if (!empty($company['company_document'])): ?>
                <div class="text-muted small"><?= htmlspecialchars($company['company_document']) ?></div>
                <?php endif; ?>
                <?php if (!empty($companyAddress)): ?>
                <div class="text-muted small"><?= htmlspecialchars($companyAddress) ?></div>
                <?php endif; ?>
                <div class="text-muted small">
                    <?php if (!empty($company['company_phone'])): ?>
                    <i class="fas fa-phone me-1"></i><?= $company['company_phone'] ?>
                    <?php endif; ?>
                    <?php if (!empty($company['company_email'])): ?>
                     &nbsp;|&nbsp; <i class="fas fa-envelope me-1"></i><?= $company['company_email'] ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($company['company_website'])): ?>
                <div class="text-muted small"><i class="fas fa-globe me-1"></i><?= $company['company_website'] ?></div>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <div class="order-number">NOTA DE PEDIDO</div>
                <div class="fw-bold fs-5">#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="text-muted small">Data: <?= date('d/m/Y') ?></div>
                <?php if (!empty($order['deadline'])): ?>
                <div class="text-muted small">Prazo: <?= date('d/m/Y', strtotime($order['deadline'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dados do Cliente -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-user me-2"></i>Dados do Cliente</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    <div class="col-md-6">
                        <span class="info-label">Cliente</span>
                        <div class="info-value"><?= htmlspecialchars($order['customer_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="info-label">Telefone</span>
                        <div class="info-value"><?= $order['customer_phone'] ?? '—' ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="info-label">CPF/CNPJ</span>
                        <div class="info-value"><?= $order['customer_document'] ?? '—' ?></div>
                    </div>
                    <?php if (!empty($order['customer_email'])): ?>
                    <div class="col-md-6">
                        <span class="info-label">E-mail</span>
                        <div class="info-value"><?= htmlspecialchars($order['customer_email']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($customerFormattedAddress)): ?>
                    <div class="col-md-6">
                        <span class="info-label">Endereço</span>
                        <div class="info-value"><?= htmlspecialchars($customerFormattedAddress) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações do Pedido -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-info-circle me-2"></i>Informações do Pedido</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <span class="info-label">Pedido Nº</span>
                        <div class="info-value">#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="info-label">Data de Criação</span>
                        <div class="info-value"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="info-label">Prazo de Entrega</span>
                        <div class="info-value"><?= !empty($order['deadline']) ? date('d/m/Y', strtotime($order['deadline'])) : '—' ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="info-label">Prioridade</span>
                        <div class="info-value">
                            <?php
                            $prioMap = ['baixa' => '🟢 Baixa', 'normal' => '🔵 Normal', 'alta' => '🟡 Alta', 'urgente' => '🔴 Urgente'];
                            echo $prioMap[$order['priority'] ?? 'normal'] ?? 'Normal';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dados de Pagamento -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fw-bold" style="color:#2ecc71;"><i class="fas fa-money-bill-wave me-2"></i>Dados de Pagamento</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <span class="info-label">Status do Pagamento</span>
                        <div class="info-value">
                            <span class="order-badge" style="background: <?= $paymentStatusInfo['color'] ?>15; color: <?= $paymentStatusInfo['color'] ?>; border: 1px solid <?= $paymentStatusInfo['color'] ?>40;">
                                <?= $paymentStatusInfo['icon'] ?> <?= $paymentStatusInfo['label'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Forma de Pagamento</span>
                        <div class="info-value"><?= $paymentMethodMap[$order['payment_method'] ?? ''] ?? '—' ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Valor Total</span>
                        <div class="info-value fw-bold fs-5" style="color:#27ae60;">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></div>
                    </div>
                </div>

                <?php if (!empty($installments)): ?>
                <!-- Parcelas -->
                <hr class="my-3">
                <h6 class="fw-bold text-muted small mb-2"><i class="fas fa-calendar-alt me-1"></i>Parcelas</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size:0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px;">Nº</th>
                                <th>Vencimento</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($installments as $idx => $inst): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td><?= !empty($inst['due_date']) ? date('d/m/Y', strtotime($inst['due_date'])) : '—' ?></td>
                                <td class="text-end">R$ <?= number_format($inst['amount'] ?? 0, 2, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php
                                    $instStatus = $inst['status'] ?? 'pendente';
                                    if ($instStatus === 'pago') echo '<span class="badge bg-success">Pago</span>';
                                    elseif ($instStatus === 'parcial') echo '<span class="badge bg-info">Parcial</span>';
                                    else echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Itens do Pedido -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-list me-2"></i>Itens do Pedido</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($orderItems)): ?>
                <?php
                // Verifica se algum item tem desconto individual
                $hasItemDiscount = false;
                foreach ($orderItems as $item) {
                    if ((float)($item['discount'] ?? 0) > 0) { $hasItemDiscount = true; break; }
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-3" style="width:40px;">#</th>
                                <th>Produto</th>
                                <th class="text-center" style="width:80px;">Qtd</th>
                                <th class="text-end" style="width:130px;">Preço Unit.</th>
                                <th class="text-end" style="width:130px;">Subtotal</th>
                                <?php if ($hasItemDiscount): ?>
                                <th class="text-end" style="width:120px;">Desconto</th>
                                <th class="text-end pe-3" style="width:130px;">Líquido</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0; $totalItemDiscounts = 0; $idx = 1; ?>
                            <?php foreach ($orderItems as $item): ?>
                            <?php 
                                $subtotal = $item['quantity'] * $item['unit_price']; 
                                $itemDiscount = (float)($item['discount'] ?? 0);
                                $netAmount = $subtotal - $itemDiscount;
                                $total += $subtotal; 
                                $totalItemDiscounts += $itemDiscount;
                            ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    <?php if (!empty($item['combination_label'])): ?>
                                    <br><small class="text-muted"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($item['combination_label']) ?></small>
                                    <?php elseif (!empty($item['grade_description'])): ?>
                                    <br><small class="text-muted"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($item['grade_description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end">R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                <td class="text-end fw-bold">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                <?php if ($hasItemDiscount): ?>
                                <td class="text-end <?= $itemDiscount > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= $itemDiscount > 0 ? '- R$ ' . number_format($itemDiscount, 2, ',', '.') : '—' ?>
                                </td>
                                <td class="text-end pe-3 fw-bold <?= $itemDiscount > 0 ? 'text-success' : '' ?>">
                                    R$ <?= number_format($netAmount, 2, ',', '.') ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php
                            $discount = (float)($order['discount'] ?? 0);
                            $totalExtras = 0;
                            if (!empty($extraCosts)) {
                                foreach ($extraCosts as $ec) {
                                    $totalExtras += (float)$ec['amount'];
                                }
                            }
                            $totalNetProducts = $total - $totalItemDiscounts;
                            $finalTotal = $totalNetProducts + $totalExtras - $discount;
                            $footColspan = $hasItemDiscount ? 6 : 4;
                            ?>
                            <tr>
                                <td colspan="<?= $footColspan ?>" class="text-end fw-bold pe-2">Subtotal Produtos:</td>
                                <td class="text-end pe-3 fw-bold">R$ <?= number_format($total, 2, ',', '.') ?></td>
                            </tr>
                            <?php if ($totalItemDiscounts > 0): ?>
                            <tr>
                                <td colspan="<?= $footColspan ?>" class="text-end fw-bold pe-2 text-danger">Descontos por Item:</td>
                                <td class="text-end pe-3 fw-bold text-danger">- R$ <?= number_format($totalItemDiscounts, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td colspan="<?= $footColspan ?>" class="text-end fw-bold pe-2">Subtotal Líquido:</td>
                                <td class="text-end pe-3 fw-bold">R$ <?= number_format($totalNetProducts, 2, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($extraCosts)): ?>
                            <?php foreach ($extraCosts as $ec): ?>
                            <tr>
                                <td colspan="<?= $footColspan ?>" class="text-end pe-2 text-muted">
                                    <i class="fas fa-plus-circle me-1"></i><?= htmlspecialchars($ec['description']) ?>:
                                </td>
                                <td class="text-end pe-3">R$ <?= number_format($ec['amount'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="<?= $footColspan ?>" class="text-end fw-bold pe-2">Total c/ Extras:</td>
                                <td class="text-end pe-3 fw-bold">R$ <?= number_format($totalNetProducts + $totalExtras, 2, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                            <tr>
                                <td colspan="<?= $footColspan ?>" class="text-end fw-bold pe-2 text-danger">Desconto Geral:</td>
                                <td class="text-end pe-3 fw-bold text-danger">- R$ <?= number_format($discount, 2, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="<?= $footColspan ?>" class="text-end fw-bold pe-2 fs-5">Total:</td>
                                <td class="text-end pe-3 total-value">R$ <?= number_format($finalTotal, 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-box-open d-block mb-2" style="font-size:2rem;"></i>
                    Nenhum item adicionado ao pedido.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($order['quote_notes'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-sticky-note me-2"></i>Observações</h6>
            </div>
            <div class="card-body py-3">
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['quote_notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rodapé / Notas legais -->
        <div class="footer-note text-center">
            <?php if (!empty($company['order_footer_note'])): ?>
            <p class="text-muted small mb-1"><?= htmlspecialchars($company['order_footer_note']) ?></p>
            <?php elseif (!empty($company['quote_footer_note'])): ?>
            <p class="text-muted small mb-1"><?= htmlspecialchars($company['quote_footer_note']) ?></p>
            <?php endif; ?>
            <p class="text-muted small mb-0">Documento gerado em <?= date('d/m/Y \à\s H:i') ?></p>
        </div>

        <!-- Assinatura -->
        <div class="row mt-5 pt-4">
            <div class="col-6 text-center">
                <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto; padding-top: 5px;">
                    <small class="text-muted"><?= htmlspecialchars($company['company_name'] ?? 'Assinatura da Empresa') ?></small>
                </div>
            </div>
            <div class="col-6 text-center">
                <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto; padding-top: 5px;">
                    <small class="text-muted">Assinatura do Cliente</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
