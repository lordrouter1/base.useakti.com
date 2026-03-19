<?php
/**
 * Catálogo Público — Página para o cliente montar carrinho / confirmar orçamento.
 * O orçamento abre em offcanvas lateral (aba separada), igual ao carrinho.
 *
 * Variáveis disponíveis do controller:
 *   $token, $link, $orderId, $showPrices, $requireConfirmation, $customerId, $customerName
 *   $products, $categories, $customerPrices, $cartItems, $company, $productImages
 *   $extraCosts, $orderDiscount, $quoteConfirmedAt, $quoteConfirmedIp, $productCombinations
 *   $totalProducts, $totalPages, $perPage
 */

$companyName   = $company['company_name'] ?? 'Catálogo de Produtos';
$companyLogo   = $company['company_logo'] ?? '';
$requireConfirmation = $requireConfirmation ?? false;
$extraCosts    = $extraCosts ?? [];
$orderDiscount = $orderDiscount ?? 0;
$quoteConfirmedAt = $quoteConfirmedAt ?? null;
$quoteConfirmedIp = $quoteConfirmedIp ?? null;
$totalProducts = $totalProducts ?? count($products ?? []);
$totalPages    = $totalPages ?? 1;
$perPage       = $perPage ?? 20;

$cartByProduct = [];
$cartQtyByProduct = [];
foreach ($cartItems as $ci) {
    $cartByProduct[$ci['product_id']] = $ci;
    $cartQtyByProduct[$ci['product_id']] = ($cartQtyByProduct[$ci['product_id']] ?? 0) + (int)$ci['quantity'];
}

$cartTotal = 0;
$totalItemDiscounts = 0;
foreach ($cartItems as $ci) {
    $cartTotal += (float)$ci['subtotal'];
    $totalItemDiscounts += (float)($ci['discount'] ?? 0);
}
$cartCount = count($cartItems);

$extraCostsTotal   = array_sum(array_column($extraCosts, 'amount'));
$quoteSubtotal     = $cartTotal;
$quoteItemDiscounts = $totalItemDiscounts;
$quoteOrderDiscount = (float)$orderDiscount;
$quoteNet          = $quoteSubtotal - $quoteItemDiscounts - $quoteOrderDiscount;
$quoteGrandTotal   = $quoteNet + $extraCostsTotal;
if ($quoteGrandTotal < 0) $quoteGrandTotal = 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<title><?= e($companyName) ?> — Catálogo</title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2c3e50">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= eAttr($companyName) ?>">
<?php if ($companyLogo): ?>
<meta property="og:image" content="<?= eAttr($companyLogo) ?>">
<?php endif; ?>
<link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
:root{--cat-primary:#2c3e50;--cat-accent:#3498db;--cat-success:#27ae60;--cat-danger:#e74c3c;--cat-warn:#f39c12;--cat-bg:#f5f7fa;--cat-radius:14px;--safe-bottom:env(safe-area-inset-bottom,0px)}
*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--cat-bg);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',sans-serif;-webkit-font-smoothing:antialiased;padding-bottom:calc(80px + var(--safe-bottom));color:#333}
a{-webkit-tap-highlight-color:transparent}

.cat-header{background:linear-gradient(135deg,var(--cat-primary) 0%,#34495e 100%);color:#fff;padding:.75rem 0;position:sticky;top:0;z-index:1040;box-shadow:0 2px 12px rgba(0,0,0,.15)}
.cat-header .logo-img{height:34px;border-radius:6px}
.cat-header h1{font-size:1.05rem;font-weight:700;margin:0;line-height:1.3}
.cat-header .sub{font-size:.73rem;opacity:.85}

.filter-bar{background:#fff;border-radius:12px;padding:.6rem .8rem;box-shadow:0 2px 8px rgba(0,0,0,.06);position:sticky;top:55px;z-index:1030}
.search-wrap{position:relative}
.search-wrap .icon{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:#adb5bd;pointer-events:none}
.search-wrap input{width:100%;border:2px solid #e9ecef;border-radius:50px;padding:.5rem 1rem .5rem 2.3rem;font-size:.88rem;transition:border-color .2s}
.search-wrap input:focus{border-color:var(--cat-accent);outline:none;box-shadow:0 0 0 3px rgba(52,152,219,.12)}
.cat-pills{display:flex;gap:.35rem;overflow-x:auto;padding:.4rem 0 .1rem;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.cat-pills::-webkit-scrollbar{display:none}
.cat-pill{white-space:nowrap;padding:.28rem .75rem;border-radius:50px;border:2px solid #dee2e6;background:#fff;color:#6c757d;font-size:.73rem;font-weight:600;cursor:pointer;transition:all .2s;flex-shrink:0}
.cat-pill:hover,.cat-pill.active{background:var(--cat-accent);border-color:var(--cat-accent);color:#fff}

.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.7rem}
.prod-card{background:#fff;border-radius:var(--cat-radius);overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:transform .2s,box-shadow .2s;position:relative;display:flex;flex-direction:column}
.prod-card:active{transform:scale(.98)}
.prod-card .img-wrap{position:relative;width:100%;padding-top:80%;overflow:hidden;background:#f1f3f5}
.prod-card .img-wrap img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}
.prod-card .no-img{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#ccc;font-size:2rem}
.prod-card .body{padding:.6rem;flex:1;display:flex;flex-direction:column}
.prod-card .p-name{font-weight:700;font-size:.8rem;color:var(--cat-primary);margin-bottom:.15rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-card .p-desc{font-size:.68rem;color:#999;margin-bottom:.3rem;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-card .p-price{font-size:.95rem;font-weight:800;color:var(--cat-success);margin-bottom:.35rem}
.prod-card .in-cart{position:absolute;top:6px;right:6px;background:var(--cat-success);color:#fff;border-radius:50px;padding:.15rem .5rem;font-size:.62rem;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,.2);z-index:5}
.btn-add{border:none;border-radius:10px;font-weight:700;font-size:.78rem;padding:.42rem .4rem;transition:all .15s;min-height:36px;background:var(--cat-accent);color:#fff;width:100%;display:flex;align-items:center;justify-content:center;gap:.3rem}
.btn-add:active{transform:scale(.96);background:#2980b9}
.qty-sel{display:inline-flex;align-items:center;gap:.15rem}
.qty-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid #dee2e6;background:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;transition:all .12s;color:var(--cat-primary)}
.qty-btn:active{background:var(--cat-accent);color:#fff;border-color:var(--cat-accent)}
.qty-val{font-weight:700;font-size:.85rem;min-width:24px;text-align:center}

.fab{position:fixed;bottom:calc(16px + var(--safe-bottom));right:14px;z-index:1050;width:56px;height:56px;border-radius:50%;background:var(--cat-accent);color:#fff;border:none;box-shadow:0 4px 18px rgba(52,152,219,.4);font-size:1.25rem;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s,background .2s}
.fab:active{transform:scale(1.08);background:#2980b9}
.fab .badge-count{position:absolute;top:-3px;right:-3px;background:var(--cat-danger);color:#fff;border-radius:50%;width:22px;height:22px;font-size:.65rem;font-weight:800;display:flex;align-items:center;justify-content:center}
.fab.fab-quote{background:linear-gradient(135deg,#27ae60 0%,#2ecc71 100%);box-shadow:0 4px 18px rgba(39,174,96,.4)}
.fab.fab-quote:active{background:#219a52}
.fab.fab-confirmed{background:linear-gradient(135deg,#f39c12 0%,#e67e22 100%);box-shadow:0 4px 18px rgba(243,156,18,.4)}

.offcanvas-panel{width:400px !important;max-width:94vw}
.offcanvas-panel .offcanvas-header{background:var(--cat-primary);color:#fff;padding:.75rem 1rem}
.offcanvas-panel .offcanvas-title{font-weight:700;font-size:1rem}
.offcanvas-panel.quote-mode .offcanvas-header{background:linear-gradient(135deg,var(--cat-primary) 0%,#34495e 100%)}
.offcanvas-panel.quote-confirmed .offcanvas-header{background:linear-gradient(135deg,#27ae60 0%,#2ecc71 100%)}

.cart-item{display:flex;align-items:center;gap:.55rem;padding:.6rem .8rem;border-bottom:1px solid #f1f3f5}
.cart-item-img{width:44px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#f1f3f5}
.cart-item-info{flex:1;min-width:0}
.cart-item-name{font-weight:700;font-size:.78rem;color:var(--cat-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cart-item-price{font-size:.72rem;color:var(--cat-success);font-weight:600}
.cart-item-disc{font-size:.65rem;color:var(--cat-danger);font-weight:600}
.cart-item-actions{display:flex;align-items:center;gap:.25rem;flex-shrink:0}
.cart-item-actions .qty-btn{width:26px;height:26px;font-size:.8rem}
.cart-item-actions .qty-display{font-weight:700;font-size:.82rem;min-width:20px;text-align:center}
.cart-item-remove{color:var(--cat-danger);cursor:pointer;font-size:.78rem;padding:4px}
.cart-total-bar{background:#f8f9fa;border-top:2px solid #e9ecef;padding:.75rem}

.q-summary{background:#f8f9fa;padding:.85rem 1rem;border-top:2px solid #e9ecef}
.q-row{display:flex;justify-content:space-between;align-items:center;padding:.2rem 0}
.q-row .label{font-size:.8rem}
.q-row .value{font-size:.8rem;font-weight:600}
.q-row.total{font-size:1.1rem;font-weight:800;color:var(--cat-success);border-top:2px solid #dee2e6;padding-top:.55rem;margin-top:.3rem}

.btn-confirm-oc{background:linear-gradient(135deg,#27ae60 0%,#2ecc71 100%);color:#fff;border:0;border-radius:12px;padding:.8rem 1.5rem;font-size:.95rem;font-weight:800;box-shadow:0 4px 16px rgba(39,174,96,.3);transition:all .2s;width:100%}
.btn-confirm-oc:active{transform:scale(.97)}
.btn-confirm-oc:disabled{opacity:.6}
.btn-revoke-oc{background:transparent;color:var(--cat-danger);border:2px solid var(--cat-danger);border-radius:12px;padding:.55rem 1rem;font-size:.82rem;font-weight:700;transition:all .2s;width:100%}
.btn-revoke-oc:hover{background:var(--cat-danger);color:#fff}
.btn-revoke-oc:active{transform:scale(.97)}

.confirmed-banner{background:linear-gradient(135deg,#d4edda 0%,#c3e6cb 100%);padding:1.2rem;text-align:center}
.confirmed-banner .cb-icon{font-size:2.5rem;color:var(--cat-success);margin-bottom:.4rem}

/* === TABS DO OFFCANVAS (modo orçamento) === */
.oc-tabs{display:flex;border-bottom:2px solid #e9ecef;background:#f8f9fa;flex-shrink:0}
.oc-tab{flex:1;padding:.65rem .5rem;border:none;background:transparent;font-size:.82rem;font-weight:700;color:#6c757d;cursor:pointer;position:relative;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.25rem}
.oc-tab:hover{color:var(--cat-primary);background:#fff}
.oc-tab.active{color:var(--cat-primary);background:#fff}
.oc-tab.active::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:3px;background:var(--cat-accent);border-radius:3px 3px 0 0}
.oc-tab-badge{background:var(--cat-accent);color:#fff;border-radius:50px;padding:.1rem .45rem;font-size:.65rem;font-weight:800;margin-left:.2rem}
.oc-tab.active .oc-tab-badge{background:var(--cat-primary)}
.oc-tab-check{color:var(--cat-success);font-size:.75rem;margin-left:.25rem}
.oc-tab-panel{display:none}
.oc-tab-panel.active{display:flex;flex-direction:column}

.oc-info-bar{padding:.5rem .8rem;font-size:.72rem;font-weight:600;text-align:center;border-bottom:1px solid #e9ecef;flex-shrink:0}
.oc-info-confirmed{background:#d4edda;color:#155724}

/* Modo orçamento: offcanvas usa flexbox para tabs+panels */
.offcanvas-panel.quote-mode .offcanvas-body{display:none !important}
.offcanvas-panel.quote-mode{display:flex;flex-direction:column}

/* Detail de confirmação */
.q-confirm-detail{flex:1;display:flex;flex-direction:column}
.q-confirm-header{background:#f8f9fa;padding:.65rem 1rem;font-size:.85rem;font-weight:700;color:var(--cat-primary);border-bottom:1px solid #e9ecef;flex-shrink:0}
.q-confirm-items{flex:1;overflow-y:auto;padding:0}
.q-ci-row{padding:.5rem .85rem;border-bottom:1px solid #f1f3f5;display:flex;flex-direction:column;gap:.15rem}
.q-ci-row:last-child{border-bottom:none}
.q-ci-name{font-size:.78rem;font-weight:700;color:var(--cat-primary);line-height:1.3}
.q-ci-var{display:block;font-size:.65rem;color:#6c757d;font-weight:500}
.q-ci-meta{display:flex;justify-content:space-between;font-size:.72rem}
.q-confirm-actions{padding:.85rem 1rem;border-top:1px solid #e9ecef;flex-shrink:0}

@keyframes pulse{0%{transform:scale(1)}50%{transform:scale(1.12)}100%{transform:scale(1)}}
.cart-pulse{animation:pulse .3s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .35s ease}
.loading-ov{position:fixed;inset:0;background:rgba(255,255,255,.7);z-index:9999;display:none;align-items:center;justify-content:center}
.loading-ov.show{display:flex}
#btnLoadMore{transition:all .2s}
#btnLoadMore:active{transform:scale(.97)}
.prod-card.new-loaded{animation:fadeUp .35s ease}
.empty-state{text-align:center;padding:2.5rem 1rem;color:#adb5bd}
.empty-state i{font-size:2.8rem;margin-bottom:.6rem;display:block}

@media(max-width:576px){
    .cat-header{padding:.6rem 0}
    .cat-header h1{font-size:.92rem}
    .cat-header .logo-img{height:28px}
    .filter-bar{padding:.45rem .6rem;top:48px;border-radius:10px}
    .search-wrap input{padding:.45rem .7rem .45rem 2rem;font-size:.82rem}
    .prod-grid{grid-template-columns:repeat(2,1fr);gap:.55rem}
    .prod-card .body{padding:.5rem}
    .prod-card .p-name{font-size:.74rem}
    .prod-card .p-price{font-size:.88rem}
    .prod-card .p-desc{font-size:.65rem}
    .btn-add{font-size:.72rem;padding:.38rem .3rem;min-height:34px}
    .qty-btn{width:28px;height:28px;font-size:.9rem}
    .fab{bottom:calc(14px + var(--safe-bottom));right:12px;width:52px;height:52px;font-size:1.15rem}
    .fab .badge-count{width:20px;height:20px;font-size:.6rem}
    .offcanvas-panel{width:100% !important;max-width:100vw}
    .cart-item{padding:.5rem .65rem;gap:.45rem}
    .cart-item-img{width:38px;height:38px}
    .cart-item-name{font-size:.74rem}
    .q-summary{padding:.7rem .8rem}
    .q-row.total{font-size:1rem}
    .btn-confirm-oc{font-size:.88rem;padding:.7rem 1rem}
    .oc-tab{font-size:.76rem;padding:.55rem .4rem}
    .oc-tab-badge{font-size:.6rem;padding:.08rem .35rem}
    .q-ci-row{padding:.4rem .7rem}
    .q-ci-name{font-size:.74rem}
    .q-ci-meta{font-size:.68rem}
    .q-confirm-header{font-size:.8rem;padding:.55rem .8rem}
    .q-confirm-actions{padding:.7rem .8rem}
}
@media(max-width:380px){
    .prod-grid{gap:.4rem}
    .prod-card .body{padding:.45rem}
    .prod-card .p-name{font-size:.7rem}
    .prod-card .p-price{font-size:.82rem}
    .prod-card .p-desc{display:none}
}
</style>
</head>
<body>

<div class="loading-ov" id="loadingOv">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status"></div>
        <p class="mt-2 fw-bold text-primary small">Atualizando...</p>
    </div>
</div>

<!-- HEADER -->
<header class="cat-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <?php if ($companyLogo): ?>
                    <img src="<?= eAttr($companyLogo) ?>" alt="Logo" class="logo-img">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10" style="width:34px;height:34px;">
                        <i class="fas fa-store text-white"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h1><?= e($companyName) ?></h1>
                    <div class="sub">
                        <i class="fas fa-user me-1"></i>Olá, <?= e($customerName) ?>!
                        <?php if ($requireConfirmation): ?>
                            Adicione produtos e confirme seu orçamento.
                        <?php else: ?>
                            Monte sua lista de produtos.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($showPrices): ?>
                <span class="badge bg-success bg-opacity-75 py-1 px-2 d-none d-sm-inline-block" style="font-size:.68rem;">
                    <i class="fas fa-tags me-1"></i>Preços
                </span>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- BUSCA & FILTROS -->
<div class="container mt-2 mb-2">
    <div class="filter-bar">
        <div class="search-wrap mb-2">
            <i class="fas fa-search icon"></i>
            <input type="text" id="searchInput" placeholder="Buscar produtos..." autocomplete="off">
        </div>
        <div class="cat-pills">
            <span class="cat-pill active" data-cat="all"><i class="fas fa-th me-1"></i>Todos</span>
            <?php foreach ($categories as $cat): ?>
                <span class="cat-pill" data-cat="<?= $cat['id'] ?>"><?= e($cat['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- GRID DE PRODUTOS -->
<div class="container mb-3">
    <div class="prod-grid" id="productGrid">
        <?php if (empty($products)): ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <i class="fas fa-box-open"></i>
                <h5>Nenhum produto disponível</h5>
                <p class="small">Não há produtos cadastrados neste catálogo.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $prod):
                $inCart = isset($cartByProduct[$prod['id']]);
                $cartQty = $cartQtyByProduct[$prod['id']] ?? 0;
                $displayPrice = $customerPrices[$prod['id']] ?? $prod['price'];
                $images = $productImages[$prod['id']] ?? [];
                $mainImage = null;
                foreach ($images as $img) { if (!empty($img['is_main'])) { $mainImage = $img['image_path']; break; } }
                if (!$mainImage && !empty($images)) $mainImage = $images[0]['image_path'];
                $hasCombos = !empty($productCombinations[$prod['id']]);
            ?>
            <div class="prod-card" data-pid="<?= $prod['id'] ?>" data-category="<?= $prod['category_id'] ?>" data-name="<?= eAttr(strtolower($prod['name'])) ?>" data-price="<?= $displayPrice ?>" data-combos="<?= $hasCombos ? '1' : '0' ?>">
                <div class="in-cart" id="badge-<?= $prod['id'] ?>" style="<?= $inCart ? '' : 'display:none;' ?>">
                    <i class="fas fa-check me-1"></i><span class="badge-qty"><?= $cartQty ?></span>
                </div>
                <div class="img-wrap">
                    <?php if ($mainImage): ?>
                        <img src="<?= eAttr($mainImage) ?>" alt="<?= eAttr($prod['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </div>
                <div class="body">
                    <div class="p-name"><?= e($prod['name']) ?></div>
                    <?php if (!empty($prod['description'])): ?>
                        <div class="p-desc"><?= e(mb_strimwidth($prod['description'], 0, 80, '...')) ?></div>
                    <?php endif; ?>
                    <?php if ($showPrices): ?>
                        <div class="p-price">R$ <?= number_format($displayPrice, 2, ',', '.') ?></div>
                    <?php endif; ?>
                    <?php if ($hasCombos): ?>
                        <div class="mb-2">
                            <select class="form-select form-select-sm" id="var-<?= $prod['id'] ?>" style="font-size:.72rem;border-radius:8px;padding:.3rem .4rem;">
                                <option value="">Selecione variação...</option>
                                <?php foreach ($productCombinations[$prod['id']] as $combo): ?>
                                    <option value="<?= $combo['id'] ?>" data-label="<?= e($combo['combination_label']) ?>" data-price="<?= $combo['price_override'] !== null ? $combo['price_override'] : '' ?>">
                                        <?= e($combo['combination_label']) ?>
                                        <?php if ($showPrices && $combo['price_override'] !== null): ?>
                                            — R$ <?= number_format($combo['price_override'], 2, ',', '.') ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-1 mt-auto align-items-center">
                        <div class="qty-sel flex-shrink-0">
                            <button class="qty-btn" type="button" onclick="changeQty(<?= $prod['id'] ?>,-1)">&#8722;</button>
                            <span class="qty-val" id="qty-<?= $prod['id'] ?>">1</span>
                            <button class="qty-btn" type="button" onclick="changeQty(<?= $prod['id'] ?>,1)">+</button>
                        </div>
                        <button class="btn-add flex-grow-1" type="button" onclick="addToCart(<?= $prod['id'] ?>)">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="text-center mt-3 mb-2" id="loadMoreWrap">
        <button class="btn btn-outline-primary fw-bold px-4 py-2" id="btnLoadMore" type="button" onclick="loadMoreProducts()" style="border-radius:50px;font-size:.85rem;">
            <i class="fas fa-chevron-down me-2"></i>Carregar mais
            <span class="text-muted ms-1" id="loadMoreInfo" style="font-size:.72rem;">(<?= count($products) ?> de <?= $totalProducts ?>)</span>
        </button>
        <div class="d-none text-center py-3" id="loadMoreSpinner">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="text-muted small ms-2">Carregando...</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="empty-state" id="noResults" style="display:none;">
        <i class="fas fa-search"></i>
        <h5>Nenhum produto encontrado</h5>
        <p class="small">Tente buscar com outro termo ou categoria.</p>
    </div>
</div>

<!-- FAB BUTTON -->
<?php if ($requireConfirmation): ?>
<button class="fab <?= $quoteConfirmedAt ? 'fab-confirmed' : 'fab-quote' ?>" id="cartFab" type="button" data-bs-toggle="offcanvas" data-bs-target="#panelOffcanvas">
    <i class="fas <?= $quoteConfirmedAt ? 'fa-clipboard-check' : 'fa-file-invoice-dollar' ?>"></i>
    <div class="badge-count" id="cartBadge"><?= $cartCount ?></div>
</button>
<?php else: ?>
<button class="fab" id="cartFab" type="button" data-bs-toggle="offcanvas" data-bs-target="#panelOffcanvas">
    <i class="fas fa-shopping-cart"></i>
    <div class="badge-count" id="cartBadge"><?= $cartCount ?></div>
</button>
<?php endif; ?>


<!-- ================================================================== -->
<!-- OFFCANVAS ÚNICO (carrinho OU orçamento conforme o modo)            -->
<!-- No modo orçamento: 2 abas — "Itens" e "Confirmação"               -->
<!-- ================================================================== -->
<div class="offcanvas offcanvas-end offcanvas-panel <?= $requireConfirmation ? ($quoteConfirmedAt ? 'quote-mode quote-confirmed' : 'quote-mode') : '' ?>" tabindex="-1" id="panelOffcanvas">

    <!-- Header -->
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <?php if ($requireConfirmation): ?>
                <?php if ($quoteConfirmedAt): ?>
                    <i class="fas fa-clipboard-check me-2"></i>Orçamento Confirmado
                <?php else: ?>
                    <i class="fas fa-file-invoice-dollar me-2"></i>Seu Orçamento
                <?php endif; ?>
            <?php else: ?>
                <i class="fas fa-shopping-cart me-2"></i>Meu Carrinho
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <?php if ($requireConfirmation): ?>
    <!-- ============ MODO ORÇAMENTO: 2 ABAS ============ -->

    <!-- Tabs de navegação -->
    <div class="oc-tabs" id="ocTabs">
        <button class="oc-tab active" data-tab="items" id="tabItems" type="button">
            <i class="fas fa-shopping-bag me-1"></i>Itens
            <span class="oc-tab-badge" id="tabItemsBadge"><?= $cartCount ?></span>
        </button>
        <button class="oc-tab" data-tab="confirm" id="tabConfirm" type="button">
            <i class="fas fa-file-invoice-dollar me-1"></i>Confirmação
            <?php if ($quoteConfirmedAt): ?>
                <span class="oc-tab-check"><i class="fas fa-check-circle"></i></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Painel: Itens -->
    <div class="oc-tab-panel active" id="panelItems" style="overflow-y:auto;flex:1;">
        <?php if ($quoteConfirmedAt): ?>
        <!-- Mini-banner informativo no topo da aba de itens -->
        <div class="oc-info-bar oc-info-confirmed">
            <i class="fas fa-lock me-1"></i>Orçamento aprovado — itens somente leitura.
        </div>
        <?php endif; ?>

        <div id="cartItemsList">
            <?php if (empty($cartItems)): ?>
                <div class="empty-state py-4" id="emptyCart">
                    <i class="fas fa-box-open"></i>
                    <h6 class="mt-2">Orçamento vazio</h6>
                    <p class="small">Adicione produtos do catálogo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cartItems as $ci):
                    $ciImages = $productImages[$ci['product_id']] ?? [];
                    $ciMainImg = null;
                    foreach ($ciImages as $img) { if (!empty($img['is_main'])) { $ciMainImg = $img['image_path']; break; } }
                    if (!$ciMainImg && !empty($ciImages)) $ciMainImg = $ciImages[0]['image_path'];
                    $ciDiscount = (float)($ci['discount'] ?? 0);
                    $ciNetSub   = (float)$ci['subtotal'] - $ciDiscount;
                ?>
                <div class="cart-item" data-item-id="<?= $ci['id'] ?>" data-product-id="<?= $ci['product_id'] ?>">
                    <?php if ($ciMainImg): ?>
                        <img src="<?= eAttr($ciMainImg) ?>" class="cart-item-img" alt="">
                    <?php else: ?>
                        <div class="cart-item-img d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>
                    <?php endif; ?>

                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= e($ci['product_name']) ?></div>
                        <?php if (!empty($ci['combination_label']) || !empty($ci['grade_description'])): ?>
                            <div class="small text-info" style="font-size:.68rem;">
                                <i class="fas fa-layer-group me-1"></i><?= e($ci['combination_label'] ?? $ci['grade_description']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($showPrices): ?>
                            <div class="cart-item-price">
                                R$ <?= number_format($ci['unit_price'], 2, ',', '.') ?> &times; <?= (int)$ci['quantity'] ?>
                                = R$ <?= number_format((float)$ci['subtotal'], 2, ',', '.') ?>
                            </div>
                        <?php else: ?>
                            <div class="cart-item-price">Qtd: <?= (int)$ci['quantity'] ?></div>
                        <?php endif; ?>
                        <?php if ($ciDiscount > 0): ?>
                            <div class="cart-item-disc"><i class="fas fa-tag me-1"></i>Desconto: &minus; R$ <?= number_format($ciDiscount, 2, ',', '.') ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($quoteConfirmedAt): ?>
                        <!-- Modo somente leitura -->
                        <div class="text-end flex-shrink-0" style="min-width:70px;">
                            <div class="fw-bold text-success" style="font-size:.85rem;">R$ <?= number_format($ciNetSub, 2, ',', '.') ?></div>
                            <div class="text-muted" style="font-size:.65rem;">Qtd: <?= (int)$ci['quantity'] ?></div>
                        </div>
                    <?php else: ?>
                        <!-- Modo editável -->
                        <div class="cart-item-actions">
                            <button class="qty-btn" type="button" onclick="updateCartQty(<?= $ci['id'] ?>,<?= (int)$ci['quantity'] - 1 ?>)">&#8722;</button>
                            <span class="qty-display"><?= (int)$ci['quantity'] ?></span>
                            <button class="qty-btn" type="button" onclick="updateCartQty(<?= $ci['id'] ?>,<?= (int)$ci['quantity'] + 1 ?>)">+</button>
                        </div>
                        <span class="cart-item-remove" onclick="removeFromCart(<?= $ci['id'] ?>)" title="Remover"><i class="fas fa-trash-alt"></i></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sub-total resumido na aba de itens -->
        <?php if (!empty($cartItems) && $showPrices): ?>
        <div class="cart-total-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">Subtotal dos itens</span>
                    <h5 class="mb-0 text-success fw-bold" id="cartTotalDisplay">R$ <?= number_format($quoteSubtotal, 2, ',', '.') ?></h5>
                </div>
                <span class="badge bg-primary py-2 px-3" id="cartCountDisplay"><?= $cartCount ?> ite<?= $cartCount === 1 ? 'm' : 'ns' ?></span>
            </div>
            <?php if (!$quoteConfirmedAt): ?>
            <button class="btn btn-sm btn-outline-success w-100 mt-2 fw-bold" type="button" onclick="switchTab('confirm')" style="border-radius:10px;font-size:.82rem;">
                <i class="fas fa-arrow-right me-1"></i>Ver Resumo e Confirmar
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Painel: Confirmação -->
    <div class="oc-tab-panel" id="panelConfirm" style="overflow-y:auto;flex:1;display:none;">

        <?php if ($quoteConfirmedAt): ?>
        <!-- Banner de confirmado -->
        <div class="confirmed-banner">
            <div class="cb-icon"><i class="fas fa-clipboard-check"></i></div>
            <h5 class="text-success fw-bold mb-1" style="font-size:.95rem;">Orçamento Aprovado!</h5>
            <p class="text-muted mb-0" style="font-size:.78rem;">
                Confirmado em <strong><?= date('d/m/Y \à\s H:i', strtotime($quoteConfirmedAt)) ?></strong>
            </p>
            <p class="text-muted mb-0" style="font-size:.72rem;">Nossa equipe dará continuidade ao seu pedido.</p>
        </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state py-4">
                <i class="fas fa-box-open"></i>
                <h6 class="mt-2">Nenhum item no orçamento</h6>
                <p class="small">Adicione produtos para poder confirmar.</p>
                <button class="btn btn-sm btn-outline-primary fw-bold mt-2" type="button" onclick="switchTab('items')" style="border-radius:10px;">
                    <i class="fas fa-arrow-left me-1"></i>Ver Produtos
                </button>
            </div>
        <?php else: ?>
            <!-- Resumo detalhado do orçamento -->
            <div class="q-confirm-detail">
                <div class="q-confirm-header">
                    <i class="fas fa-receipt me-2"></i>Resumo do Orçamento
                </div>

                <!-- Mini-lista de itens (compacta) -->
                <div class="q-confirm-items">
                    <?php foreach ($cartItems as $ci):
                        $ciDiscount = (float)($ci['discount'] ?? 0);
                        $ciNetSub   = (float)$ci['subtotal'] - $ciDiscount;
                    ?>
                    <div class="q-ci-row">
                        <div class="q-ci-name">
                            <?= e($ci['product_name']) ?>
                            <?php if (!empty($ci['combination_label']) || !empty($ci['grade_description'])): ?>
                                <span class="q-ci-var"><?= e($ci['combination_label'] ?? $ci['grade_description']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="q-ci-meta">
                            <span class="text-muted"><?= (int)$ci['quantity'] ?>x R$ <?= number_format($ci['unit_price'], 2, ',', '.') ?></span>
                            <span class="fw-bold">R$ <?= number_format($ciNetSub, 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totais -->
                <div class="q-summary" id="quoteSummary">
                    <div class="q-row">
                        <span class="label text-muted">Subtotal (<?= $cartCount ?> ite<?= $cartCount === 1 ? 'm' : 'ns' ?>):</span>
                        <span class="value fw-bold" id="qSubtotal">R$ <?= number_format($quoteSubtotal, 2, ',', '.') ?></span>
                    </div>
                    <?php if ($quoteItemDiscounts > 0): ?>
                    <div class="q-row text-danger">
                        <span class="label"><i class="fas fa-tag me-1"></i>Desc. itens:</span>
                        <span class="value">&minus; R$ <?= number_format($quoteItemDiscounts, 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($quoteOrderDiscount > 0): ?>
                    <div class="q-row" style="color:var(--cat-accent);">
                        <span class="label"><i class="fas fa-percent me-1"></i>Desc. geral:</span>
                        <span class="value">&minus; R$ <?= number_format($quoteOrderDiscount, 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($extraCosts as $ec): ?>
                    <div class="q-row">
                        <span class="label text-muted"><i class="fas fa-plus-circle me-1" style="color:var(--cat-warn);"></i><?= e($ec['description']) ?>:</span>
                        <span class="value">+ R$ <?= number_format((float)$ec['amount'], 2, ',', '.') ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="q-row total">
                        <span>Total:</span>
                        <span id="qGrandTotal">R$ <?= number_format($quoteGrandTotal, 2, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Ações -->
                <div class="q-confirm-actions">
                    <?php if ($quoteConfirmedAt): ?>
                        <p class="text-muted text-center mb-2" style="font-size:.72rem;">
                            <i class="fas fa-info-circle me-1"></i>Precisa alterar? Cancele a aprovação para editar.
                        </p>
                        <button class="btn-revoke-oc" id="btnRevoke" type="button" onclick="revokeQuote()">
                            <i class="fas fa-undo me-1"></i>Cancelar Aprovação e Editar
                        </button>
                    <?php else: ?>
                        <p class="text-muted text-center mb-2" style="font-size:.72rem;">
                            <i class="fas fa-info-circle me-1"></i>Ao confirmar, você aprova este orçamento.
                        </p>
                        <button class="btn-confirm-oc" id="btnConfirm" type="button" onclick="confirmQuote()">
                            <i class="fas fa-check-double me-2"></i>Confirmar Orçamento
                        </button>
                        <button class="btn btn-sm btn-link text-muted w-100 mt-2" type="button" onclick="switchTab('items')" style="font-size:.78rem;">
                            <i class="fas fa-arrow-left me-1"></i>Voltar para os itens
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ============ MODO CARRINHO SIMPLES (sem confirmação) ============ -->

    <div class="offcanvas-body p-0" style="overflow-y:auto;">
        <div id="cartItemsList">
            <?php if (empty($cartItems)): ?>
                <div class="empty-state py-4" id="emptyCart">
                    <i class="fas fa-shopping-cart"></i>
                    <h6 class="mt-2">Carrinho vazio</h6>
                    <p class="small">Adicione produtos do catálogo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cartItems as $ci):
                    $ciImages = $productImages[$ci['product_id']] ?? [];
                    $ciMainImg = null;
                    foreach ($ciImages as $img) { if (!empty($img['is_main'])) { $ciMainImg = $img['image_path']; break; } }
                    if (!$ciMainImg && !empty($ciImages)) $ciMainImg = $ciImages[0]['image_path'];
                ?>
                <div class="cart-item" data-item-id="<?= $ci['id'] ?>" data-product-id="<?= $ci['product_id'] ?>">
                    <?php if ($ciMainImg): ?>
                        <img src="<?= eAttr($ciMainImg) ?>" class="cart-item-img" alt="">
                    <?php else: ?>
                        <div class="cart-item-img d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>
                    <?php endif; ?>
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= e($ci['product_name']) ?></div>
                        <?php if (!empty($ci['combination_label']) || !empty($ci['grade_description'])): ?>
                            <div class="small text-info" style="font-size:.68rem;">
                                <i class="fas fa-layer-group me-1"></i><?= e($ci['combination_label'] ?? $ci['grade_description']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($showPrices): ?>
                            <div class="cart-item-price">R$ <?= number_format($ci['unit_price'], 2, ',', '.') ?> &times; <?= (int)$ci['quantity'] ?></div>
                        <?php else: ?>
                            <div class="cart-item-price">Qtd: <?= (int)$ci['quantity'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-actions">
                        <button class="qty-btn" type="button" onclick="updateCartQty(<?= $ci['id'] ?>,<?= (int)$ci['quantity'] - 1 ?>)">&#8722;</button>
                        <span class="qty-display"><?= (int)$ci['quantity'] ?></span>
                        <button class="qty-btn" type="button" onclick="updateCartQty(<?= $ci['id'] ?>,<?= (int)$ci['quantity'] + 1 ?>)">+</button>
                    </div>
                    <span class="cart-item-remove" onclick="removeFromCart(<?= $ci['id'] ?>)" title="Remover"><i class="fas fa-trash-alt"></i></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER: Carrinho padrão -->
    <?php if ($showPrices): ?>
    <div class="cart-total-bar">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted small">Total estimado</span>
                <h5 class="mb-0 text-success fw-bold" id="cartTotalDisplay">R$ <?= number_format($cartTotal, 2, ',', '.') ?></h5>
            </div>
            <span class="badge bg-primary py-2 px-3" id="cartCountDisplay"><?= $cartCount ?> ite<?= $cartCount === 1 ? 'm' : 'ns' ?></span>
        </div>
        <small class="text-muted d-block mt-1" style="font-size:.68rem;">
            <i class="fas fa-info-circle me-1"></i>Valores estimados. O orçamento final será elaborado pela equipe.
        </small>
    </div>
    <?php else: ?>
    <div class="cart-total-bar">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Produtos selecionados</span>
            <span class="badge bg-primary py-2 px-3" id="cartCountDisplay"><?= $cartCount ?> ite<?= $cartCount === 1 ? 'm' : 'ns' ?></span>
        </div>
        <small class="text-muted d-block mt-1" style="font-size:.68rem;">
            <i class="fas fa-info-circle me-1"></i>A equipe entrará em contato com o orçamento.
        </small>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
'use strict';
var TOKEN='<?= e($token) ?>',PRICES=<?= $showPrices?'true':'false' ?>,CONFIRM=<?= $requireConfirmation?'true':'false' ?>,CONFIRMED=<?= $quoteConfirmedAt?'true':'false' ?>,BASE='?page=catalog',reloading=false;

/* === TAB SWITCHING (modo orçamento) === */
window.switchTab=function(tab){
    var tabs=document.querySelectorAll('#ocTabs .oc-tab'),panels=document.querySelectorAll('.oc-tab-panel');
    tabs.forEach(function(t){t.classList.toggle('active',t.dataset.tab===tab)});
    panels.forEach(function(p){
        var id=p.id.replace('panel','').toLowerCase();
        var match=(id==='items'&&tab==='items')||(id==='confirm'&&tab==='confirm');
        p.classList.toggle('active',match);
        p.style.display=match?'':'none';
    });
};
if(CONFIRM){
    document.querySelectorAll('#ocTabs .oc-tab').forEach(function(t){
        t.addEventListener('click',function(){switchTab(this.dataset.tab)});
    });
}

/* === PAGINATION === */
var currentPage=1,totalPages=<?= (int)$totalPages ?>,perPage=<?= (int)$perPage ?>,loadedCount=<?= count($products) ?>,isLoadingMore=false,sTimer=null,sSearch='',sCat='';

window.loadMoreProducts=function(){
    if(isLoadingMore||currentPage>=totalPages) return;
    isLoadingMore=true;
    var btn=document.getElementById('btnLoadMore'),sp=document.getElementById('loadMoreSpinner');
    if(btn) btn.classList.add('d-none'); if(sp) sp.classList.remove('d-none');
    var url=BASE+'&action=getProducts&token='+encodeURIComponent(TOKEN)+'&page_num='+(currentPage+1)+'&per_page='+perPage;
    if(sCat&&sCat!=='all') url+='&category='+encodeURIComponent(sCat);
    if(sSearch) url+='&search='+encodeURIComponent(sSearch);
    fetch(url).then(function(r){return r.ok?r.json():Promise.reject()}).then(function(d){
        isLoadingMore=false; if(btn)btn.classList.remove('d-none'); if(sp)sp.classList.add('d-none');
        if(!d.success||!d.products||!d.products.length){hideLM();return}
        currentPage=d.page;
        var g=document.getElementById('productGrid');
        d.products.forEach(function(p){g.insertAdjacentHTML('beforeend',buildCard(p));loadedCount++});
        var inf=document.getElementById('loadMoreInfo');
        if(inf) inf.textContent='('+loadedCount+' de '+d.total+')';
        if(!d.has_more) hideLM();
    }).catch(function(){isLoadingMore=false;if(btn)btn.classList.remove('d-none');if(sp)sp.classList.add('d-none');toast('Erro ao carregar.','error')});
};

function hideLM(){var w=document.getElementById('loadMoreWrap');if(w)w.style.display='none'}
function showLM(){var w=document.getElementById('loadMoreWrap');if(w)w.style.display=''}

function buildCard(p){
    var img=p.main_image?'<img src="'+esc(p.main_image)+'" alt="'+esc(p.name)+'" loading="lazy">':'<div class="no-img"><i class="fas fa-image"></i></div>';
    var pr=PRICES?'<div class="p-price">R$ '+money(p.price)+'</div>':'';
    var hc=p.combinations&&p.combinations.length>0;
    var cb='';
    if(hc){cb='<div class="mb-2"><select class="form-select form-select-sm" id="var-'+p.id+'" style="font-size:.72rem;border-radius:8px;padding:.3rem .4rem;"><option value="">Selecione variação...</option>';
        p.combinations.forEach(function(c){var pl=(PRICES&&c.price_override!=null)?' — R$ '+money(c.price_override):'';cb+='<option value="'+c.id+'" data-label="'+esc(c.combination_label)+'" data-price="'+(c.price_override!=null?c.price_override:'')+'">'+esc(c.combination_label)+pl+'</option>'});
        cb+='</select></div>'}
    var ds=p.description?'<div class="p-desc">'+esc(p.description.length>80?p.description.substring(0,80)+'...':p.description)+'</div>':'';
    return '<div class="prod-card new-loaded" data-pid="'+p.id+'" data-category="'+(p.category_id||'')+'" data-name="'+esc((p.name||'').toLowerCase())+'" data-price="'+p.price+'" data-combos="'+(hc?'1':'0')+'">'
        +'<div class="in-cart" id="badge-'+p.id+'" style="display:none;"><i class="fas fa-check me-1"></i><span class="badge-qty">0</span></div>'
        +'<div class="img-wrap">'+img+'</div><div class="body"><div class="p-name">'+esc(p.name)+'</div>'+ds+pr+cb
        +'<div class="d-flex gap-1 mt-auto align-items-center"><div class="qty-sel flex-shrink-0">'
        +'<button class="qty-btn" type="button" onclick="changeQty('+p.id+',-1)">&#8722;</button>'
        +'<span class="qty-val" id="qty-'+p.id+'">1</span>'
        +'<button class="qty-btn" type="button" onclick="changeQty('+p.id+',1)">+</button></div>'
        +'<button class="btn-add flex-grow-1" type="button" onclick="addToCart('+p.id+')"><i class="fas fa-plus"></i> Add</button>'
        +'</div></div></div>';
}

if(totalPages>1&&'IntersectionObserver' in window){var io=new IntersectionObserver(function(e){e.forEach(function(en){if(en.isIntersecting&&!isLoadingMore&&currentPage<totalPages)loadMoreProducts()})},{rootMargin:'300px'});var lw=document.getElementById('loadMoreWrap');if(lw)io.observe(lw)}

/* === SEARCH / FILTER === */
var searchEl=document.getElementById('searchInput'),pills=document.querySelectorAll('.cat-pill'),activeCat='all';
if(searchEl)searchEl.addEventListener('input',function(){filterLocal();if(totalPages>1){clearTimeout(sTimer);sTimer=setTimeout(serverSearch,500)}});
pills.forEach(function(p){p.addEventListener('click',function(){pills.forEach(function(x){x.classList.remove('active')});this.classList.add('active');activeCat=this.dataset.cat;filterLocal();if(totalPages>1)serverSearch()})});

function filterLocal(){
    var q=(searchEl?searchEl.value:'').toLowerCase().trim(),cards=document.querySelectorAll('.prod-card'),vis=0;
    cards.forEach(function(c){var ok=(!q||c.dataset.name.includes(q))&&(activeCat==='all'||c.dataset.category===activeCat);c.style.display=ok?'':'none';if(ok)vis++});
    var nr=document.getElementById('noResults');if(nr)nr.style.display=vis===0?'':'none';
}
function serverSearch(){
    var q=(searchEl?searchEl.value:'').trim();if(q===sSearch&&activeCat===sCat)return;sSearch=q;sCat=activeCat;currentPage=0;loadedCount=0;
    var g=document.getElementById('productGrid');if(g)g.innerHTML='';showLM();loadMoreProducts();
}

/* === QTY === */
window.changeQty=function(pid,d){var el=document.getElementById('qty-'+pid);if(!el)return;var v=parseInt(el.textContent)+d;if(v<1)v=1;if(v>999)v=999;el.textContent=v};

/* === API === */
function api(action,body,method){
    method=method||'POST';var url=BASE+'&action='+action,opts={method:method,headers:{}};
    if(method==='POST'){opts.headers['Content-Type']='application/x-www-form-urlencoded';
        if(typeof body==='string'){if(!body.includes('token='))body='token='+encodeURIComponent(TOKEN)+'&'+body}else{body='token='+encodeURIComponent(TOKEN)}
        opts.body=body}
    return fetch(url,opts).then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json()}).catch(function(err){return fetch(url,opts).then(function(r){if(!r.ok)throw err;return r.json()})});
}

/* === ADD TO CART === */
window.addToCart=function(pid){
    var qE=document.getElementById('qty-'+pid),qty=qE?parseInt(qE.textContent):1,card=document.querySelector('.prod-card[data-pid="'+pid+'"]'),hc=card&&card.dataset.combos==='1',cId='',gL='';
    if(hc){var sel=document.getElementById('var-'+pid);if(sel&&!sel.value){toast('Selecione uma variação.','warning');sel.focus();return}if(sel){cId=sel.value;var o=sel.options[sel.selectedIndex];gL=o?(o.dataset.label||''):''}}
    loading(true);
    api('addToCart','product_id='+pid+'&quantity='+qty+'&combination_id='+cId+'&grade_description='+encodeURIComponent(gL)).then(function(d){
        loading(false);
        if(d.success){updateCartUI(d);pulseFab();
            var b=document.getElementById('badge-'+pid);if(b){var t=0;d.cart.forEach(function(i){if(i.product_id==pid)t+=parseInt(i.quantity)});b.querySelector('.badge-qty').textContent=t;b.style.display=''}
            if(qE)qE.textContent='1';toast('Produto adicionado!','success');if(CONFIRM)reloadPage();
        }else{toast(d.message||'Erro ao adicionar.','error')}
    }).catch(function(){loading(false);toast('Erro de conexão.','error')});
};

/* === REMOVE === */
window.removeFromCart=function(id){Swal.fire({title:'Remover item?',text:'Deseja remover este produto?',icon:'question',showCancelButton:true,confirmButtonText:'Remover',cancelButtonText:'Cancelar',confirmButtonColor:'#e74c3c'}).then(function(r){if(r.isConfirmed)doRemove(id)})};
function doRemove(id){loading(true);api('removeFromCart','item_id='+id).then(function(d){loading(false);if(d.success){updateCartUI(d);updateBadges(d.cart);toast('Produto removido.','info');if(CONFIRM)reloadPage()}else{toast(d.message||'Erro.','error')}}).catch(function(){loading(false);toast('Erro de conexão.','error')})}

/* === UPDATE QTY === */
window.updateCartQty=function(id,q){
    if(q<1){window.removeFromCart(id);return}
    loading(true);api('updateCartItem','item_id='+id+'&quantity='+q).then(function(d){loading(false);if(d.success){updateCartUI(d);updateBadges(d.cart);if(CONFIRM)reloadPage()}else{toast(d.message||'Erro.','error')}}).catch(function(){loading(false);toast('Erro de conexão.','error')});
};

function reloadPage(){if(reloading)return;reloading=true;setTimeout(function(){location.reload()},350)}

/* === CONFIRM QUOTE === */
window.confirmQuote=function(){
    Swal.fire({title:'Confirmar Orçamento?',html:'<p style="font-size:.9rem;">Ao confirmar, você aprova este orçamento.<br>Nossa equipe dará continuidade.</p>',icon:'question',showCancelButton:true,confirmButtonText:'<i class="fas fa-check-double me-1"></i> Confirmar',cancelButtonText:'Voltar',confirmButtonColor:'#27ae60',reverseButtons:true,focusConfirm:false}).then(function(r){
        if(!r.isConfirmed)return;
        var btn=document.getElementById('btnConfirm');if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i>Confirmando...'}
        api('confirmQuote','').then(function(d){
            if(d.success){Swal.fire({icon:'success',title:'Orçamento Confirmado!',html:'<p style="font-size:.9rem;">Obrigado! Nossa equipe foi notificada.</p>',confirmButtonText:'OK',confirmButtonColor:'#27ae60',allowOutsideClick:false}).then(function(){location.reload()})}
            else{if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check-double me-2"></i>Confirmar Orçamento'}Swal.fire({icon:'error',title:'Erro',text:d.message||'Não foi possível confirmar.'})}
        }).catch(function(){if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check-double me-2"></i>Confirmar Orçamento'}Swal.fire({icon:'error',title:'Erro de conexão',text:'Tente novamente.'})});
    });
};

/* === REVOKE QUOTE === */
window.revokeQuote=function(){
    Swal.fire({title:'Cancelar Aprovação?',html:'<p style="font-size:.9rem;">Ao cancelar, você poderá editar o orçamento.<br>Será necessário confirmar novamente.</p>',icon:'warning',showCancelButton:true,confirmButtonText:'<i class="fas fa-undo me-1"></i> Cancelar Aprovação',cancelButtonText:'Manter',confirmButtonColor:'#e74c3c',reverseButtons:true,focusConfirm:false}).then(function(r){
        if(!r.isConfirmed)return;
        var btn=document.getElementById('btnRevoke');if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Processando...'}
        api('revokeQuote','').then(function(d){
            if(d.success){Swal.fire({icon:'success',title:'Aprovação Cancelada',html:'<p style="font-size:.9rem;">Agora você pode editar o orçamento.</p>',confirmButtonText:'OK',confirmButtonColor:'#3498db',allowOutsideClick:false}).then(function(){location.reload()})}
            else{if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-undo me-1"></i>Cancelar Aprovação e Editar'}Swal.fire({icon:'error',title:'Erro',text:d.message||'Erro ao revogar.'})}
        }).catch(function(){if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-undo me-1"></i>Cancelar Aprovação e Editar'}Swal.fire({icon:'error',title:'Erro de conexão',text:'Tente novamente.'})});
    });
};

/* === UPDATE CART UI (offcanvas) === */
function updateCartUI(data){
    var badge=document.getElementById('cartBadge'),cEl=document.getElementById('cartCountDisplay'),tEl=document.getElementById('cartTotalDisplay');
    if(badge)badge.textContent=data.cart_count;
    if(cEl)cEl.textContent=data.cart_count+' ite'+(data.cart_count===1?'m':'ns');
    if(tEl)tEl.textContent='R$ '+money(data.cart_total);
    if(CONFIRM)return; // reload handles it
    var list=document.getElementById('cartItemsList');if(!list)return;
    if(data.cart.length===0){list.innerHTML='<div class="empty-state py-4"><i class="fas fa-shopping-cart"></i><h6 class="mt-2">Carrinho vazio</h6><p class="small">Adicione produtos.</p></div>';return}
    var h='';
    data.cart.forEach(function(it){
        var im=getImgHtml(it.product_id),vl=it.combination_label||it.grade_description||'';
        h+='<div class="cart-item" data-item-id="'+it.id+'" data-product-id="'+it.product_id+'">'+im
            +'<div class="cart-item-info"><div class="cart-item-name">'+esc(it.product_name)+'</div>'
            +(vl?'<div class="small text-info" style="font-size:.68rem;"><i class="fas fa-layer-group me-1"></i>'+esc(vl)+'</div>':'')
            +(PRICES?'<div class="cart-item-price">R$ '+money(it.unit_price)+' &times; '+it.quantity+'</div>':'<div class="cart-item-price">Qtd: '+it.quantity+'</div>')
            +'</div><div class="cart-item-actions">'
            +'<button class="qty-btn" type="button" onclick="updateCartQty('+it.id+','+(parseInt(it.quantity)-1)+')">&#8722;</button>'
            +'<span class="qty-display">'+it.quantity+'</span>'
            +'<button class="qty-btn" type="button" onclick="updateCartQty('+it.id+','+(parseInt(it.quantity)+1)+')">+</button></div>'
            +'<span class="cart-item-remove" onclick="removeFromCart('+it.id+')" title="Remover"><i class="fas fa-trash-alt"></i></span></div>';
    });
    list.innerHTML=h;
}

/* === BADGES === */
function updateBadges(cart){
    document.querySelectorAll('.in-cart').forEach(function(b){b.style.display='none';b.querySelector('.badge-qty').textContent='0'});
    var q={};cart.forEach(function(i){q[i.product_id]=(q[i.product_id]||0)+parseInt(i.quantity)});
    Object.keys(q).forEach(function(pid){var b=document.getElementById('badge-'+pid);if(b){b.querySelector('.badge-qty').textContent=q[pid];b.style.display=''}});
}

/* === HELPERS === */
function money(v){return parseFloat(v).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.')}
function esc(t){if(!t)return '';var d=document.createElement('span');d.textContent=t;return d.innerHTML}
function getImgHtml(pid){var c=document.querySelector('.prod-card[data-pid="'+pid+'"]');if(c){var i=c.querySelector('.img-wrap img');if(i)return '<img src="'+i.src+'" class="cart-item-img" alt="">'}return '<div class="cart-item-img d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>'}
function pulseFab(){var f=document.getElementById('cartFab');if(f){f.classList.add('cart-pulse');setTimeout(function(){f.classList.remove('cart-pulse')},300)}}
function loading(s){var el=document.getElementById('loadingOv');if(el)el.classList.toggle('show',s)}
function toast(m,i){Swal.fire({toast:true,position:'top-end',icon:i,title:m,showConfirmButton:false,timer:2200,timerProgressBar:true})}

/* === AUTO-REFRESH === */
setInterval(function(){
    if(reloading)return;
    fetch(BASE+'&action=getCart&token='+encodeURIComponent(TOKEN)).then(function(r){return r.ok?r.json():null}).then(function(d){
        if(!d||!d.success)return;
        var cur=parseInt((document.getElementById('cartBadge')||{}).textContent||'0');
        if(cur!==d.cart_count){if(CONFIRM)reloadPage();else{updateCartUI(d);updateBadges(d.cart)}}
    }).catch(function(){});
},30000);

/* === AUTO-OPEN offcanvas se confirmado === */
if(CONFIRM&&CONFIRMED){setTimeout(function(){var el=document.getElementById('panelOffcanvas');if(el){var oc=bootstrap.Offcanvas.getOrCreateInstance(el);oc.show();switchTab('confirm')}},600)}

})();
</script>
</body>
</html>
