<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></title>
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
            @page { margin: 15mm; }
        }
        body { background: #f5f5f5; font-family: 'Segoe UI', Arial, sans-serif; }
        .quote-header { border-bottom: 3px solid #3498db; padding-bottom: 15px; margin-bottom: 20px; }
        .company-logo img { max-height: 80px; }
        .company-name { font-size: 1.8rem; font-weight: 800; color: #2c3e50; }
        .quote-number { font-size: 1.4rem; color: #3498db; font-weight: 700; }
        .info-label { font-weight: 600; color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; }
        .info-value { font-weight: 500; color: #2c3e50; }
        .total-row { background: #eaf7ee !important; }
        .total-value { font-size: 1.4rem; font-weight: 800; color: #27ae60; }
        .footer-note { border-top: 2px solid #ecf0f1; padding-top: 15px; margin-top: 30px; }
    </style>
</head>
<body>
    <?php
    // Helper para formatar endereço do cliente (JSON -> string)
    $customerFormattedAddress = '';
    if (!empty($order['customer_address'])) {
        $customerFormattedAddress = \Akti\Models\CompanySettings::formatCustomerAddress($order['customer_address']);
    }
    $validityDays = (int)($company['quote_validity_days'] ?? 15);
    ?>

    <!-- Barra de ações (não imprime) -->
    <div class="no-print bg-dark text-white py-2">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-invoice-dollar me-2"></i>Orçamento #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></span>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-success btn-sm"><i class="fas fa-print me-1"></i> Imprimir</button>
                <button onclick="window.close()" class="btn btn-outline-light btn-sm"><i class="fas fa-times me-1"></i> Fechar</button>
            </div>
        </div>
    </div>

    <div class="container py-4" style="max-width: 800px;">
        <!-- Cabeçalho da empresa -->
        <div class="quote-header d-flex justify-content-between align-items-start">
            <div>
                <?php if (!empty($company['company_logo']) && file_exists($company['company_logo'])): ?>
                <div class="company-logo mb-1">
                    <img src="<?= $company['company_logo'] ?>" alt="Logo">
                </div>
                <?php endif; ?>
                <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'Minha Gráfica') ?></div>
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
                <div class="quote-number">ORÇAMENTO</div>
                <div class="fw-bold fs-5">#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="text-muted small">Data: <?= date('d/m/Y') ?></div>
                <div class="text-muted small">Válido até: <?= date('d/m/Y', strtotime("+{$validityDays} days")) ?></div>
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
                        <span class="info-label">Prazo</span>
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

        <!-- Itens do Orçamento -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-list me-2"></i>Itens do Orçamento</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($orderItems)): ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-3" style="width:40px;">#</th>
                                <th>Produto</th>
                                <th class="text-center" style="width:80px;">Qtd</th>
                                <th class="text-end" style="width:130px;">Preço Unit.</th>
                                <th class="text-end pe-3" style="width:130px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0; $idx = 1; ?>
                            <?php foreach ($orderItems as $item): ?>
                            <?php $subtotal = $item['quantity'] * $item['unit_price']; $total += $subtotal; ?>
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
                                <td class="text-end pe-3 fw-bold">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
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
                            $finalTotal = $total + $totalExtras - $discount;
                            ?>
                            <tr>
                                <td colspan="4" class="text-end fw-bold pe-2">Subtotal Produtos:</td>
                                <td class="text-end pe-3 fw-bold">R$ <?= number_format($total, 2, ',', '.') ?></td>
                            </tr>
                            <?php if (!empty($extraCosts)): ?>
                            <?php foreach ($extraCosts as $ec): ?>
                            <tr>
                                <td colspan="4" class="text-end pe-2 text-muted">
                                    <i class="fas fa-plus-circle me-1"></i><?= htmlspecialchars($ec['description']) ?>:
                                </td>
                                <td class="text-end pe-3">R$ <?= number_format($ec['amount'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="4" class="text-end fw-bold pe-2">Total c/ Extras:</td>
                                <td class="text-end pe-3 fw-bold">R$ <?= number_format($total + $totalExtras, 2, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                            <tr>
                                <td colspan="4" class="text-end fw-bold pe-2 text-danger">Desconto:</td>
                                <td class="text-end pe-3 fw-bold text-danger">- R$ <?= number_format($discount, 2, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="4" class="text-end fw-bold pe-2 fs-5">Total:</td>
                                <td class="text-end pe-3 total-value">R$ <?= number_format($finalTotal, 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-box-open d-block mb-2" style="font-size:2rem;"></i>
                    Nenhum item adicionado ao orçamento.
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
            <p class="text-muted small mb-1">Este orçamento é válido por <strong><?= $validityDays ?> dias</strong> a partir da data de emissão.</p>
            <?php if (!empty($company['quote_footer_note'])): ?>
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
