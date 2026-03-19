<?php
    $__currentStage = $order['pipeline_stage'] ?? 'producao';
    $__isPrep = ($__currentStage === 'preparacao');
    $__orderDocTitle = $__isPrep ? 'Ordem de Preparação' : 'Ordem de Produção';
    $__accentColor = $__isPrep ? '#27ae60' : '#e67e22';
    $__accentBg = $__isPrep ? '#e0f7f1' : '#fef3e2';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $__orderDocTitle ?> #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e50">
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">

    <!-- CSS & JS externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        /* ── Impressão ────────────────────────────── */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; font-size: 10px; }
            .container { max-width: 100% !important; padding: 5px !important; }
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                break-inside: avoid;
            }
            .table th {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .bg-dark, .bg-primary, .bg-warning,
            .bg-success, .bg-info, .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .production-item-card { break-inside: avoid; }
            .sector-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { margin: 8mm; }
        }

        /* ── Layout geral ─────────────────────────── */
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
        }
        .order-header {
            border-bottom: 3px solid <?= $__accentColor ?>;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-logo img { max-height: 50px; }
        .company-name {
            font-size: 1.3rem;
            font-weight: 800;
            color: #2c3e50;
        }
        .order-title {
            font-size: 1.1rem;
            color: <?= $__accentColor ?>;
            font-weight: 700;
        }

        /* ── Tabela de info compacta ──────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 0.82rem;
        }
        .info-table td {
            padding: 3px 8px;
            border: 1px solid #e9ecef;
            vertical-align: top;
        }
        .info-table .lbl {
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.7rem;
            white-space: nowrap;
            width: 1%;
            background: #f8f9fa;
        }

        /* ── Código de barras ─────────────────────── */
        .barcode-container { text-align: center; }
        .barcode-container svg { max-width: 100%; height: auto; }
        .product-barcode svg { max-height: 35px; }

        /* ── Card de item de produção ─────────────── */
        .production-item-card {
            border-left: 4px solid <?= $__accentColor ?> !important;
            padding: 6px 10px !important;
        }
        .production-item-card.all-done {
            border-left-color: #27ae60 !important;
        }

        /* ── Imagem do produto na ordem de produção ── */
        .production-product-img {
            width: 48px;
            height: 48px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e9ecef;
            flex-shrink: 0;
        }
        .production-product-noimg {
            width: 48px;
            height: 48px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #ccc;
            font-size: 1.1rem;
        }

        /* ── Fluxo de setores ─────────────────────── */
        .sector-flow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 3px;
        }
        .sector-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 600;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            color: #495057;
        }
        .sector-badge.active {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .sector-badge.done {
            background: #d1e7dd;
            border-color: #27ae60;
            color: #0f5132;
        }
        .sector-arrow {
            color: #adb5bd;
            font-size: 0.6rem;
        }

        /* ── Checklist compacto ───────────────────── */
        .checkbox-line {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            margin: 1px 2px;
            background: #fff;
            font-size: 0.72rem;
        }
        .checkbox-line .check-box {
            width: 14px;
            height: 14px;
            border: 2px solid #999;
            border-radius: 2px;
            flex-shrink: 0;
        }

        /* ── Rodapé ───────────────────────────────── */
        .footer-note {
            border-top: 2px solid #ecf0f1;
            padding-top: 6px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
<?php
/* ================================================================
   PHP – Preparação de dados
   ================================================================ */
$customerFormattedAddress = '';
if (!empty($order['customer_address'])) {
    $customerFormattedAddress = \Akti\Models\CompanySettings::formatCustomerAddress($order['customer_address']);
}

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

// Helpers
$orderId = str_pad($order['id'], 4, '0', STR_PAD_LEFT);
$prioMap = [
    'baixa'   => '🟢 Baixa',
    'normal'  => '🔵 Normal',
    'alta'    => '🟡 Alta',
    'urgente' => '🔴 Urgente',
];
?>

<!-- ══════════════════════════════════════════
     Barra de ações (não imprime)
     ══════════════════════════════════════════ -->
<div class="no-print bg-dark text-white py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <span>
            <i class="fas <?= $__isPrep ? 'fa-box-open' : 'fa-industry' ?> me-2"></i><?= $__orderDocTitle ?> #<?= $orderId ?>
        </span>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-warning btn-sm text-dark">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
            <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>"
               class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
            <button onclick="window.close()" class="btn btn-outline-light btn-sm">
                <i class="fas fa-times me-1"></i> Fechar
            </button>
        </div>
    </div>
</div>

<div class="container py-2" style="max-width: 800px;">

    <!-- ══════════════════════════════════════
         CABEÇALHO
         ══════════════════════════════════════ -->
    <div class="order-header d-flex justify-content-between align-items-start">
        <!-- Empresa -->
        <div>
            <?php if (!empty($company['company_logo']) && file_exists($company['company_logo'])): ?>
                <div class="company-logo mb-1">
                    <img src="<?= $company['company_logo'] ?>" alt="Logo">
                </div>
            <?php endif; ?>

            <div class="company-name">
                <?= e($company['company_name'] ?? 'Minha Gráfica') ?>
            </div>

            <?php if (!empty($company['company_document'])): ?>
                <div class="text-muted" style="font-size:0.75rem;"><?= e($company['company_document']) ?></div>
            <?php endif; ?>

            <?php if (!empty($companyAddress)): ?>
                <div class="text-muted" style="font-size:0.75rem;"><?= e($companyAddress) ?></div>
            <?php endif; ?>
        </div>

        <!-- Título + código de barras -->
        <div class="text-end">
            <div class="order-title">
                <i class="fas <?= $__isPrep ? 'fa-box-open' : 'fa-industry' ?> me-1"></i> <?= strtoupper($__orderDocTitle) ?>
            </div>
            <div class="fw-bold" style="font-size:1.1rem;">#<?= $orderId ?></div>
            <div class="text-muted" style="font-size:0.75rem;">Emitida em: <?= date('d/m/Y H:i') ?></div>
            <div class="barcode-container mt-1">
                <svg id="barcode-order"></svg>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         DADOS DO PEDIDO E CLIENTE (tabela compacta lado a lado)
         ══════════════════════════════════════ -->
    <table class="info-table">
        <tr>
            <td class="lbl">Pedido</td>
            <td><strong>#<?= $orderId ?></strong></td>
            <td class="lbl">Cliente</td>
            <td><strong><?= e($order['customer_name'] ?? '—') ?></strong></td>
        </tr>
        <tr>
            <td class="lbl">Data</td>
            <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
            <td class="lbl">Telefone</td>
            <td><?= !empty($order['customer_phone']) ? $order['customer_phone'] : '—' ?></td>
        </tr>
        <tr>
            <td class="lbl">Prioridade</td>
            <td><?= $prioMap[$order['priority'] ?? 'normal'] ?? 'Normal' ?></td>
            <td class="lbl">E-mail</td>
            <td><?= !empty($order['customer_email']) ? e($order['customer_email']) : '—' ?></td>
        </tr>
        <tr>
            <td class="lbl">Prazo</td>
            <td class="<?= (!empty($order['deadline']) && strtotime($order['deadline']) < time()) ? 'text-danger fw-bold' : '' ?>">
                <?= !empty($order['deadline']) ? date('d/m/Y', strtotime($order['deadline'])) : '—' ?>
            </td>
            <td class="lbl">Responsável</td>
            <td><?= !empty($order['assigned_name']) ? e($order['assigned_name']) : '—' ?></td>
        </tr>
    </table>

    <!-- ══════════════════════════════════════
         PRODUTOS <?= $__isPrep ? '' : 'E SETORES DE PRODUÇÃO' ?>

         ══════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-header py-1" style="background: <?= $__accentBg ?>;">
            <h6 class="mb-0 fw-bold" style="color: <?= $__accentColor ?>; font-size:0.85rem;">
                <i class="fas fa-boxes-packing me-1"></i>Produtos
                <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">
                    <?= count($orderItems) ?> itens
                </span>
            </h6>
        </div>

        <div class="card-body p-0">
            <?php if (!empty($orderItems)): ?>
                <?php $idx = 0; foreach ($orderItems as $item): $idx++;
                    $iid       = $item['id'];
                    $sectors   = $itemSectors[$iid]['sectors'] ?? [];
                    $done      = 0;
                    foreach ($sectors as $s) {
                        if ($s['status'] === 'concluido') $done++;
                    }
                    $allDone   = (!empty($sectors) && $done === count($sectors));
                    $barcodeId = 'P' . $orderId . '-I' . str_pad($iid, 4, '0', STR_PAD_LEFT);
                ?>

                <!-- ── Item <?= $idx ?> ──────────────────────── -->
                <div class="production-item-card <?= $allDone ? 'all-done' : '' ?> <?= $idx > 1 ? 'border-top' : '' ?>">

                    <!-- Cabeçalho do item -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <?php 
                                $itemMainImage = $productImages[$item['product_id']] ?? null;
                            ?>
                            <?php if ($itemMainImage && file_exists($itemMainImage)): ?>
                                <img src="<?= e($itemMainImage) ?>" alt="" class="production-product-img">
                            <?php else: ?>
                                <div class="production-product-noimg">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <span class="badge rounded-circle d-flex align-items-center justify-content-center"
                                  style="width:22px;height:22px;font-size:0.65rem;background:<?= $allDone ? '#27ae60' : $__accentColor ?>;color:#fff;">
                                <?= $allDone ? '✓' : $idx ?>
                            </span>
                            <div>
                                <strong style="font-size:0.85rem;"><?= e($item['product_name']) ?></strong>
                                <?php if (!empty($item['combination_label'])): ?>
                                    <span class="badge bg-info text-white ms-1" style="font-size:0.68rem;font-weight:600;">
                                        <i class="fas fa-layer-group me-1" style="font-size:0.55rem;"></i><?= e($item['combination_label']) ?>
                                    </span>
                                <?php elseif (!empty($item['grade_description'])): ?>
                                    <span class="badge bg-info text-white ms-1" style="font-size:0.68rem;font-weight:600;">
                                        <i class="fas fa-layer-group me-1" style="font-size:0.55rem;"></i><?= e($item['grade_description']) ?>
                                    </span>
                                <?php endif; ?>
                                <small class="text-muted ms-2">
                                    Qtd: <strong><?= $item['quantity'] ?></strong>
                                    <?php if (!$__isPrep && !empty($sectors)): ?>
                                        · Setores: <?= $done ?>/<?= count($sectors) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <!-- Código de barras do item -->
                        <div class="product-barcode text-end">
                            <svg class="barcode-item" data-value="<?= $barcodeId ?>"></svg>
                        </div>
                    </div>

                    <?php if (!$__isPrep && !empty($sectors)): ?>
                        <!-- Fluxo visual de setores -->
                        <div class="sector-flow mt-1">
                            <?php foreach ($sectors as $si => $sec):
                                $isDone  = ($sec['status'] === 'concluido');
                                $isPending = ($sec['status'] === 'pendente');
                                $isCurrentSector = false;
                                if ($isPending) {
                                    $isCurrentSector = true;
                                    foreach (array_slice($sectors, 0, $si) as $prev) {
                                        if ($prev['status'] === 'pendente') {
                                            $isCurrentSector = false;
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <?php if ($si > 0): ?>
                                    <span class="sector-arrow"><i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>

                                <span class="sector-badge <?= $isDone ? 'done' : ($isCurrentSector ? 'active' : '') ?>">
                                    <i class="<?= e($sec['icon'] ?: 'fas fa-cog') ?>"
                                       style="font-size:0.6rem;"></i>
                                    <?= e($sec['sector_name']) ?>
                                    <?php if ($isDone): ?>
                                        <i class="fas fa-check" style="font-size:0.55rem;"></i>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Checklist compacto (inline) -->
                        <div class="mt-1" style="line-height:1.8;">
                            <?php foreach ($sectors as $sec):
                                $isDone = ($sec['status'] === 'concluido');
                            ?>
                                <span class="checkbox-line">
                                    <span class="check-box <?= $isDone ? 'bg-success border-success' : '' ?>"
                                          style="display:inline-flex;align-items:center;justify-content:center;">
                                        <?php if ($isDone): ?>
                                            <i class="fas fa-check text-white" style="font-size:0.55rem;"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="<?= $isDone ? 'text-decoration-line-through text-muted' : 'fw-bold' ?>">
                                        <?= e($sec['sector_name']) ?>
                                    </span>
                                    <?php if ($isDone && !empty($sec['completed_by_name'])): ?>
                                        <span class="text-muted" style="font-size:0.6rem;">
                                            (<?= e($sec['completed_by_name']) ?>
                                            <?php if (!empty($sec['completed_at'])): ?>
                                                <?= date('d/m H:i', strtotime($sec['completed_at'])) ?>
                                            <?php endif; ?>)
                                        </span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div><!-- /.production-item-card -->
                <?php endforeach; ?>

            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-box-open d-block mb-2" style="font-size:2rem;"></i>
                    Nenhum produto no pedido.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         REGISTRO (Logs dos Produtos)
         ══════════════════════════════════════ -->
    <!-- ══════════════════════════════════════
         REGISTRO (Logs dos Produtos) — apenas na produção
         ══════════════════════════════════════ -->
    <?php if (!$__isPrep && !empty($orderItemLogs)): ?>
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-header py-1" style="background: #e8f5e9;">
            <h6 class="mb-0 fw-bold" style="color: #27ae60; font-size:0.85rem;">
                <i class="fas fa-clipboard-list me-1"></i>Registro
                <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">
                    <?= count($orderItemLogs) ?> registros
                </span>
            </h6>
        </div>
        <div class="card-body p-1">
            <?php foreach ($orderItemLogs as $log): ?>
            <div class="d-flex gap-1 px-2 py-1 border-bottom" style="font-size:0.72rem;">
                <span class="badge bg-success  text-success border border-success border-opacity-25" style="font-size:0.58rem;">
                    <i class="fas fa-box me-1"></i><?= e($log['product_name'] ?? 'Produto') ?>
                </span>
                <span class="fw-bold"><?= e($log['user_name'] ?? 'Sistema') ?></span>
                <span class="text-muted"><?= date('d/m H:i', strtotime($log['created_at'])) ?></span>
                <?php if (!empty($log['message'])): ?>
                <span>— <?= e($log['message']) ?></span>
                <?php endif; ?>
                <?php if (!empty($log['file_name'])): ?>
                <span class="text-muted"><i class="fas fa-paperclip me-1"></i><?= e($log['file_name']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         PREPARO DO PEDIDO (quando em preparação)
         ══════════════════════════════════════ -->
    <?php 
    $currentStage = $order['pipeline_stage'] ?? '';
    if ($currentStage === 'preparacao'):
        $preparoChecklist = $orderPreparationChecklist ?? [];
        // $preparoItems já é definido pelo controller com as etapas ativas do banco
        $checkedCount = 0;
        foreach ($preparoItems as $key => $item) {
            if (!empty($preparoChecklist[$key])) $checkedCount++;
        }
        $totalPrepItems = count($preparoItems);
    ?>
    <div class="card border-0 shadow-sm mb-2" style="border-left: 4px solid #1abc9c !important;">
        <div class="card-header py-1" style="background: #e0f7f1;">
            <h6 class="mb-0 fw-bold" style="color: #1abc9c; font-size:0.85rem;">
                <i class="fas fa-boxes-packing me-1"></i>Preparo
                <span class="badge ms-1" style="font-size:0.65rem;background:#1abc9c;color:#fff;">
                    <?= $checkedCount ?>/<?= $totalPrepItems ?>
                </span>
            </h6>
        </div>
        <div class="card-body p-1">
            <div style="display:flex;flex-wrap:wrap;gap:3px;padding:3px;">
                <?php foreach ($preparoItems as $key => $pItem): 
                    $isChecked = !empty($preparoChecklist[$key]);
                    $checkedBy = $preparoChecklist[$key . '_by'] ?? null;
                    $checkedAt = $preparoChecklist[$key . '_at'] ?? null;
                ?>
                <span class="checkbox-line">
                    <span class="check-box <?= $isChecked ? 'bg-success border-success' : '' ?>"
                          style="display:inline-flex;align-items:center;justify-content:center;">
                        <?php if ($isChecked): ?>
                            <i class="fas fa-check text-white" style="font-size:0.55rem;"></i>
                        <?php endif; ?>
                    </span>
                    <span class="<?= $isChecked ? 'text-decoration-line-through text-muted' : 'fw-bold' ?>">
                        <?= $pItem['label'] ?>
                    </span>
                    <?php if ($isChecked && $checkedBy): ?>
                        <span class="text-muted" style="font-size:0.6rem;">
                            (<?= e($checkedBy) ?>
                            <?php if ($checkedAt): ?> <?= date('d/m H:i', strtotime($checkedAt)) ?><?php endif; ?>)
                        </span>
                    <?php endif; ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php if ($checkedCount === $totalPrepItems): ?>
            <div class="text-center py-1" style="font-size:0.72rem;color:#27ae60;">
                <i class="fas fa-check-double me-1"></i><strong>Preparo concluído — Pronto para envio</strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         OBSERVAÇÕES INTERNAS
         ══════════════════════════════════════ -->
    <?php if (!empty($order['internal_notes'])): ?>
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-light py-1">
                <h6 class="mb-0 text-primary fw-bold" style="font-size:0.82rem;">
                    <i class="fas fa-sticky-note me-1"></i>Observações
                </h6>
            </div>
            <div class="card-body py-2">
                <p class="mb-0 small"><?= nl2br(e($order['internal_notes'])) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         RODAPÉ
         ══════════════════════════════════════ -->
    <div class="footer-note text-center">
        <p class="text-muted small mb-0">
            <i class="fas <?= $__isPrep ? 'fa-box-open' : 'fa-industry' ?> me-1"></i>
            <?= $__orderDocTitle ?> gerada em <?= date('d/m/Y \à\s H:i') ?> — <?= e($company['company_name'] ?? '') ?> — Uso interno
        </p>
    </div>

    <!-- ══════════════════════════════════════
         ASSINATURAS
         ══════════════════════════════════════ -->
    <div class="row mt-3 pt-2">
        <div class="col-4 text-center">
            <div style="border-top: 1px solid #333; width: 85%; margin: 0 auto; padding-top: 3px;">
                <small class="text-muted" style="font-size:0.7rem;">Responsável</small>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="border-top: 1px solid #333; width: 85%; margin: 0 auto; padding-top: 3px;">
                <small class="text-muted" style="font-size:0.7rem;"><?= $__isPrep ? 'Preparação' : 'Produção' ?></small>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="border-top: 1px solid #333; width: 85%; margin: 0 auto; padding-top: 3px;">
                <small class="text-muted" style="font-size:0.7rem;">Conferência</small>
            </div>
        </div>
    </div>

</div><!-- /.container -->

<!-- ══════════════════════════════════════════
     JavaScript – Códigos de barras
     ══════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Código de barras do pedido
    try {
        JsBarcode("#barcode-order", "<?= $__isPrep ? 'PREP' : 'OP' ?><?= $orderId ?>", {
            format: "CODE128",
            width: 1.2,
            height: 32,
            displayValue: true,
            fontSize: 10,
            margin: 3,
            textMargin: 1
        });
    } catch (e) {
        console.warn('Barcode order error:', e);
    }

    // Códigos de barras de cada item
    document.querySelectorAll('.barcode-item').forEach(function (svg) {
        var val = svg.getAttribute('data-value');
        if (val) {
            try {
                JsBarcode(svg, val, {
                    format: "CODE128",
                    width: 0.8,
                    height: 24,
                    displayValue: true,
                    fontSize: 8,
                    margin: 1,
                    textMargin: 1
                });
            } catch (e) {
                console.warn('Barcode item error:', e);
            }
        }
    });

});
</script>

</body>
</html>