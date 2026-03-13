<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupom #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <style>
        /* ══════════════════════════════════════════════════════
           CUPOM NÃO FISCAL — Layout para impressora térmica
           Largura padrão: 80mm (302px) ou 58mm (219px)
           ══════════════════════════════════════════════════════ */
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Courier New', 'Lucida Console', monospace;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background: #f0f0f0;
            padding: 10px;
        }

        .receipt {
            width: 302px; /* 80mm */
            background: #fff;
            margin: 0 auto;
            padding: 8px 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }

        /* Largura 58mm */
        .receipt.w58 {
            width: 219px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .small { font-size: 10px; }
        .tiny { font-size: 9px; }
        .large { font-size: 14px; }
        .xlarge { font-size: 16px; }

        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .divider-double {
            border: none;
            border-top: 2px solid #000;
            margin: 5px 0;
        }

        .divider-stars {
            text-align: center;
            font-size: 10px;
            letter-spacing: 2px;
            margin: 3px 0;
        }

        .header-logo {
            max-width: 180px;
            max-height: 60px;
            display: block;
            margin: 0 auto 5px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }

        .item-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 5px;
        }

        .item-price {
            white-space: nowrap;
            text-align: right;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            padding: 2px 0;
        }

        .total-line.grand {
            font-size: 16px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 4px 0;
            margin: 3px 0;
        }

        .info-line {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 11px;
        }

        .qr-section {
            text-align: center;
            margin: 6px 0;
        }

        .qr-section canvas {
            display: block;
            margin: 0 auto;
        }

        .footer-msg {
            text-align: center;
            font-size: 10px;
            margin-top: 6px;
            line-height: 1.4;
        }

        .cut-line {
            text-align: center;
            font-size: 9px;
            color: #999;
            margin-top: 10px;
            letter-spacing: 1px;
        }

        /* ─── Barra de controles (não imprime) ─── */
        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }
        .no-print button, .no-print select {
            padding: 6px 16px;
            font-size: 13px;
            margin: 3px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
        }
        .no-print button:hover { background: #e9e9e9; }
        .no-print .btn-print { background: #27ae60; color: #fff; border-color: #27ae60; font-weight: bold; }
        .no-print .btn-print:hover { background: #219a52; }
        .no-print .btn-close-page { background: #e74c3c; color: #fff; border-color: #e74c3c; }

        @media print {
            body { 
                padding: 0; 
                margin: 0; 
                background: #fff; 
            }
            .receipt { 
                box-shadow: none; 
                margin: 0;
                padding: 2px 4px;
            }
            .no-print { display: none !important; }
            .cut-line { display: none; }
            @page { 
                margin: 0; 
                size: 80mm auto;
            }
        }
    </style>
</head>
<body>

<?php
    // ─── Preparar dados ───
    $orderId = str_pad($order['id'], 4, '0', STR_PAD_LEFT);
    $companyName = $company['company_name'] ?? 'Minha Empresa';
    $companyDoc = $company['company_document'] ?? '';
    $companyPhone = $company['company_phone'] ?? '';
    $companyEmail = $company['company_email'] ?? '';
    $companyLogo = $company['company_logo'] ?? '';

    $customerName = $order['customer_name'] ?? 'Cliente não informado';
    $customerPhone = $order['customer_phone'] ?? '';
    $customerDoc = $order['customer_document'] ?? '';

    $paymentMethodMap = [
        'dinheiro'       => 'Dinheiro',
        'pix'            => 'PIX',
        'cartao_credito' => 'Cartao Credito',
        'cartao_debito'  => 'Cartao Debito',
        'boleto'         => 'Boleto',
        'transferencia'  => 'Transferencia',
        'cheque'         => 'Cheque',
    ];
    $paymentStatusMap = [
        'pendente' => 'PENDENTE',
        'parcial'  => 'PARCIAL',
        'pago'     => 'PAGO',
    ];

    $paymentMethod = $paymentMethodMap[$order['payment_method'] ?? ''] ?? '-';
    $paymentStatus = $paymentStatusMap[$order['payment_status'] ?? 'pendente'] ?? 'PENDENTE';

    $totalAmount = (float)($order['total_amount'] ?? 0);
    $discount = (float)($order['discount'] ?? 0);
    $downPayment = (float)($order['down_payment'] ?? 0);

    // Calcular subtotal dos itens
    $subtotal = 0;
    $totalItemDiscounts = 0;
    foreach ($orderItems as $oi) {
        $itemSub = (float)$oi['quantity'] * (float)$oi['unit_price'];
        $itemDisc = (float)($oi['discount'] ?? 0);
        $subtotal += $itemSub;
        $totalItemDiscounts += $itemDisc;
    }

    // Custos extras
    $extraCostsTotal = 0;
    if (!empty($extraCosts)) {
        foreach ($extraCosts as $ec) {
            $extraCostsTotal += (float)$ec['amount'];
        }
    }
?>

<!-- Barra de controles -->
<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir Cupom</button>
    <button onclick="window.close()">✕ Fechar</button>
    <br>
    <select id="receiptWidth" onchange="changeWidth(this.value)" style="margin-top:5px;">
        <option value="302">80mm (padrão)</option>
        <option value="219">58mm (compacta)</option>
    </select>
</div>

<!-- ═══ CUPOM NÃO FISCAL ═══ -->
<div class="receipt" id="receipt">

    <!-- Cabeçalho da Empresa -->
    <?php if (!empty($companyLogo) && file_exists($companyLogo)): ?>
    <img src="<?= e($companyLogo) ?>" alt="Logo" class="header-logo">
    <?php endif; ?>
    
    <div class="text-center bold large"><?= e($companyName) ?></div>
    <?php if (!empty($companyDoc)): ?>
    <div class="text-center small"><?= e($companyDoc) ?></div>
    <?php endif; ?>
    <?php if (!empty($companyAddress)): ?>
    <div class="text-center tiny"><?= e($companyAddress) ?></div>
    <?php endif; ?>
    <?php if (!empty($companyPhone)): ?>
    <div class="text-center tiny">Tel: <?= e($companyPhone) ?></div>
    <?php endif; ?>

    <hr class="divider-double">

    <!-- Título -->
    <div class="text-center bold large">CUPOM NAO FISCAL</div>
    <div class="text-center small">*** Nao tem valor fiscal ***</div>

    <hr class="divider">

    <!-- Dados do Pedido -->
    <div class="info-line">
        <span>Pedido:</span>
        <span class="bold">#<?= $orderId ?></span>
    </div>
    <div class="info-line">
        <span>Data:</span>
        <span><?= date('d/m/Y H:i') ?></span>
    </div>
    <?php if (!empty($order['created_at'])): ?>
    <div class="info-line">
        <span>Emissao:</span>
        <span><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($order['deadline'])): ?>
    <div class="info-line">
        <span>Prazo:</span>
        <span><?= date('d/m/Y', strtotime($order['deadline'])) ?></span>
    </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Dados do Cliente -->
    <div class="bold small">CLIENTE</div>
    <div class="small"><?= e($customerName) ?></div>
    <?php if (!empty($customerDoc)): ?>
    <div class="tiny">Doc: <?= e($customerDoc) ?></div>
    <?php endif; ?>
    <?php if (!empty($customerPhone)): ?>
    <div class="tiny">Tel: <?= e($customerPhone) ?></div>
    <?php endif; ?>

    <hr class="divider-double">

    <!-- Cabeçalho dos Itens -->
    <div class="item-row bold small" style="border-bottom:1px solid #000; padding-bottom:2px; margin-bottom:2px;">
        <span class="item-name">ITEM</span>
        <span style="width:30px;text-align:center;">QTD</span>
        <span style="width:55px;text-align:right;">UNIT</span>
        <span style="width:60px;text-align:right;">TOTAL</span>
    </div>

    <!-- Itens do Pedido -->
    <?php foreach ($orderItems as $idx => $oi): 
        $qty = (int)$oi['quantity'];
        $unitPrice = (float)$oi['unit_price'];
        $itemTotal = $qty * $unitPrice;
        $itemDiscount = (float)($oi['discount'] ?? 0);
        $itemName = $oi['product_name'] ?? 'Produto';
        $gradeDesc = $oi['grade_description'] ?? '';
    ?>
    <div style="padding: 2px 0;">
        <div class="item-row small">
            <span class="item-name" title="<?= eAttr($itemName) ?>"><?= e(mb_strimwidth($itemName, 0, 22, '..')) ?></span>
            <span style="width:30px;text-align:center;"><?= $qty ?></span>
            <span style="width:55px;text-align:right;"><?= number_format($unitPrice, 2, ',', '.') ?></span>
            <span style="width:60px;text-align:right;"><?= number_format($itemTotal, 2, ',', '.') ?></span>
        </div>
        <?php if (!empty($gradeDesc)): ?>
        <div class="tiny" style="color:#555; padding-left:4px;">  (<?= e(mb_strimwidth($gradeDesc, 0, 30, '..')) ?>)</div>
        <?php endif; ?>
        <?php if ($itemDiscount > 0): ?>
        <div class="tiny" style="padding-left:4px;">  Desc: -R$ <?= number_format($itemDiscount, 2, ',', '.') ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <hr class="divider-double">

    <!-- Totais -->
    <div class="total-line small">
        <span>Subtotal (<?= count($orderItems) ?> <?= count($orderItems) === 1 ? 'item' : 'itens' ?>):</span>
        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
    </div>

    <?php if ($totalItemDiscounts > 0): ?>
    <div class="total-line small" style="color:#c0392b;">
        <span>Desc. itens:</span>
        <span>- R$ <?= number_format($totalItemDiscounts, 2, ',', '.') ?></span>
    </div>
    <?php endif; ?>

    <?php if ($discount > 0): ?>
    <div class="total-line small" style="color:#c0392b;">
        <span>Desconto geral:</span>
        <span>- R$ <?= number_format($discount, 2, ',', '.') ?></span>
    </div>
    <?php endif; ?>

    <?php if ($extraCostsTotal > 0): ?>
    <div class="total-line small">
        <span>Custos extras:</span>
        <span>+ R$ <?= number_format($extraCostsTotal, 2, ',', '.') ?></span>
    </div>
    <?php endif; ?>

    <div class="total-line grand">
        <span>TOTAL:</span>
        <span>R$ <?= number_format($totalAmount, 2, ',', '.') ?></span>
    </div>

    <!-- Pagamento -->
    <div class="info-line">
        <span>Forma Pgto:</span>
        <span class="bold"><?= e($paymentMethod) ?></span>
    </div>
    <div class="info-line">
        <span>Status:</span>
        <span class="bold"><?= $paymentStatus ?></span>
    </div>

    <?php if ($downPayment > 0): ?>
    <div class="info-line">
        <span>Entrada/Sinal:</span>
        <span>R$ <?= number_format($downPayment, 2, ',', '.') ?></span>
    </div>
    <?php endif; ?>

    <?php 
    $installments = (int)($order['installments'] ?? 0);
    $installmentValue = (float)($order['installment_value'] ?? 0);
    if ($installments > 1 && $installmentValue > 0): 
    ?>
    <div class="info-line">
        <span>Parcelamento:</span>
        <span><?= $installments ?>x R$ <?= number_format($installmentValue, 2, ',', '.') ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($installmentsList)): ?>
    <hr class="divider">
    <div class="bold small text-center">PARCELAS</div>
    <div style="margin-top:2px;">
        <?php foreach ($installmentsList as $inst): ?>
        <div class="info-line tiny">
            <span><?= $inst['installment_number'] ?>ª - <?= date('d/m/Y', strtotime($inst['due_date'])) ?></span>
            <span>R$ <?= number_format($inst['amount'], 2, ',', '.') ?>
                <?php if ($inst['status'] === 'pago'): ?> ✓<?php endif; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Observações -->
    <?php if (!empty($order['quote_notes'])): ?>
    <div class="bold tiny">OBS:</div>
    <div class="tiny"><?= e(mb_strimwidth($order['quote_notes'], 0, 200, '...')) ?></div>
    <hr class="divider">
    <?php endif; ?>

    <!-- Atendente -->
    <?php if (!empty($order['assigned_name'])): ?>
    <div class="info-line small">
        <span>Atendente:</span>
        <span><?= e($order['assigned_name']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Operador -->
    <?php if (!empty($_SESSION['user_name'])): ?>
    <div class="info-line small">
        <span>Operador:</span>
        <span><?= e($_SESSION['user_name']) ?></span>
    </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Rodapé -->
    <div class="footer-msg">
        <div class="bold"><?= e($companyName) ?></div>
        <?php if (!empty($company['company_website'])): ?>
        <div class="tiny"><?= e($company['company_website']) ?></div>
        <?php endif; ?>
        <div class="tiny" style="margin-top:3px;">Obrigado pela preferencia!</div>
        <div class="tiny">Volte sempre!</div>
    </div>

    <div class="divider-stars">********************************</div>
    
    <div class="text-center tiny" style="color:#888;">
        Impresso em <?= date('d/m/Y H:i:s') ?>
    </div>

    <div class="cut-line">✂ - - - - - - - - - - - - - - - - - - -</div>
</div>

<script>
    // Trocar largura do cupom (80mm / 58mm)
    function changeWidth(w) {
        var receipt = document.getElementById('receipt');
        receipt.style.width = w + 'px';
        if (w == '219') {
            receipt.classList.add('w58');
        } else {
            receipt.classList.remove('w58');
        }
        // Atualizar @page size para impressão
        var style = document.getElementById('dynamicPageSize');
        if (!style) {
            style = document.createElement('style');
            style.id = 'dynamicPageSize';
            document.head.appendChild(style);
        }
        style.textContent = '@media print { @page { size: ' + (w == '219' ? '58mm' : '80mm') + ' auto; } }';
    }

    // Auto-print se parâmetro na URL
    (function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('auto_print') === '1') {
            setTimeout(function() { window.print(); }, 500);
        }
    })();
</script>

</body>
</html>
