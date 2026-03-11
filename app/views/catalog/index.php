<?php
/**
 * Catálogo Público — Página para o cliente montar seu carrinho de produtos.
 * 
 * Variáveis disponíveis:
 *   $token, $link, $orderId, $showPrices, $customerId, $customerName
 *   $products, $categories, $customerPrices, $cartItems, $company, $productImages
 */
$companyName = $company['company_name'] ?? 'Catálogo de Produtos';
$companyLogo = $company['company_logo'] ?? '';

// Indexar carrinho por product_id para lookup rápido
// Indexar carrinho por product_id — agregar quantidade (pode haver múltiplas combinações)
$cartByProduct = [];
$cartQtyByProduct = [];
foreach ($cartItems as $ci) {
    $cartByProduct[$ci['product_id']] = $ci;
    $cartQtyByProduct[$ci['product_id']] = ($cartQtyByProduct[$ci['product_id']] ?? 0) + $ci['quantity'];
}
$cartTotal = array_sum(array_column($cartItems, 'subtotal'));
$cartCount = count($cartItems);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($companyName) ?> — Catálogo</title>

    <!-- SEO -->
    <meta name="description" content="Catálogo de produtos de <?= eAttr($companyName) ?>. Navegue e monte sua lista de produtos.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e50">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= eAttr($companyName) ?> — Catálogo de Produtos">
    <meta property="og:description" content="Navegue pelo catálogo e monte sua lista de produtos.">
    <?php if ($companyLogo): ?>
    <meta property="og:image" content="<?= eAttr($companyLogo) ?>">
    <?php endif; ?>
    <meta property="og:locale" content="pt_BR">

    <!-- X (Twitter) Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= eAttr($companyName) ?> — Catálogo">
    <meta name="twitter:description" content="Navegue pelo catálogo e monte sua lista de produtos.">
    <?php if ($companyLogo): ?>
    <meta name="twitter:image" content="<?= eAttr($companyLogo) ?>">
    <?php endif; ?>

    <!-- Web App -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= eAttr($companyName) ?>">
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --catalog-primary: #2c3e50;
            --catalog-accent: #3498db;
            --catalog-success: #27ae60;
            --catalog-bg: #f5f7fa;
        }
        * { box-sizing: border-box; }
        body { background: var(--catalog-bg); font-family: 'Segoe UI', 'Roboto', sans-serif; padding-bottom: 80px; }
        
        /* Header */
        .catalog-header {
            background: linear-gradient(135deg, var(--catalog-primary) 0%, #34495e 100%);
            color: #fff;
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            z-index: 1040;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }
        .catalog-header .logo-img { height: 40px; border-radius: 6px; }
        .catalog-header h1 { font-size: 1.3rem; font-weight: 700; margin: 0; }
        .catalog-header .welcome-text { font-size: 0.85rem; opacity: 0.85; }
        
        /* Search and Filters */
        .filter-bar {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            position: sticky;
            top: 76px;
            z-index: 1030;
        }
        .search-input {
            border: 2px solid #e9ecef;
            border-radius: 50px;
            padding: 0.6rem 1.2rem 0.6rem 2.8rem;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .search-input:focus { border-color: var(--catalog-accent); box-shadow: 0 0 0 3px rgba(52,152,219,0.15); }
        .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #adb5bd; }
        
        /* Category Filters */
        .category-pills { display: flex; gap: 0.5rem; overflow-x: auto; padding: 0.3rem 0; scrollbar-width: none; }
        .category-pills::-webkit-scrollbar { display: none; }
        .category-pill {
            white-space: nowrap;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            border: 2px solid #dee2e6;
            background: #fff;
            color: #6c757d;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .category-pill:hover, .category-pill.active {
            background: var(--catalog-accent);
            border-color: var(--catalog-accent);
            color: #fff;
        }
        
        /* Product Grid */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.2rem; }
        @media (max-width: 576px) { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 0.7rem; } }
        
        .product-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        
        .product-card .card-img-wrap {
            position: relative;
            width: 100%;
            padding-top: 75%;
            overflow: hidden;
            background: #f1f3f5;
        }
        .product-card .card-img-wrap img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .product-card:hover .card-img-wrap img { transform: scale(1.05); }
        .product-card .no-image {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            color: #ccc;
            font-size: 2.5rem;
        }
        
        .product-card .card-body { padding: 0.9rem; flex: 1; display: flex; flex-direction: column; }
        .product-card .product-name { font-weight: 700; font-size: 0.9rem; color: var(--catalog-primary); margin-bottom: 0.3rem; line-height: 1.3; }
        .product-card .product-desc { font-size: 0.75rem; color: #888; margin-bottom: 0.5rem; flex: 1; }
        .product-card .product-price { font-size: 1.1rem; font-weight: 800; color: var(--catalog-success); margin-bottom: 0.5rem; }
        
        .product-card .btn-add-cart {
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.5rem;
            transition: all 0.2s;
        }
        .product-card .in-cart-badge {
            position: absolute;
            top: 10px; right: 10px;
            background: var(--catalog-success);
            color: #fff;
            border-radius: 50px;
            padding: 0.25rem 0.65rem;
            font-size: 0.7rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 5;
        }
        
        /* Carrinho Flutuante */
        .cart-fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            width: 60px; height: 60px;
            border-radius: 50%;
            background: var(--catalog-accent);
            color: #fff;
            border: none;
            box-shadow: 0 4px 16px rgba(52,152,219,0.4);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }
        .cart-fab:hover { transform: scale(1.1); background: #2980b9; }
        .cart-fab .cart-badge {
            position: absolute;
            top: -2px; right: -2px;
            background: #e74c3c;
            color: #fff;
            border-radius: 50%;
            width: 24px; height: 24px;
            font-size: 0.72rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Cart Offcanvas */
        .offcanvas-cart { width: 420px !important; max-width: 92vw; }
        .offcanvas-cart .offcanvas-header { background: var(--catalog-primary); color: #fff; }
        .offcanvas-cart .offcanvas-title { font-weight: 700; }
        .cart-item { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.75rem 0; 
            border-bottom: 1px solid #f1f3f5; 
        }
        .cart-item-img { 
            width: 52px; height: 52px; 
            border-radius: 8px; 
            object-fit: cover; 
            flex-shrink: 0; 
            background: #f1f3f5; 
        }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 700; font-size: 0.85rem; color: var(--catalog-primary); }
        .cart-item-price { font-size: 0.8rem; color: var(--catalog-success); font-weight: 600; }
        .cart-item-actions { display: flex; align-items: center; gap: 0.4rem; }
        .cart-item-actions .qty-btn {
            width: 28px; height: 28px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: #fff;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.15s;
        }
        .cart-item-actions .qty-btn:hover { background: var(--catalog-accent); color: #fff; border-color: var(--catalog-accent); }
        .cart-item-actions .qty-display { font-weight: 700; font-size: 0.9rem; min-width: 24px; text-align: center; }
        .cart-item-remove { 
            color: #e74c3c; 
            cursor: pointer; 
            font-size: 0.85rem; 
            transition: opacity 0.15s; 
        }
        .cart-item-remove:hover { opacity: 0.7; }
        
        .cart-total-bar {
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
            padding: 1rem;
        }
        
        /* Empty state */
        .empty-catalog {
            text-align: center;
            padding: 3rem 1rem;
            color: #adb5bd;
        }
        .empty-catalog i { font-size: 4rem; margin-bottom: 1rem; }
        
        /* Quantity selector on product card */
        .qty-selector { display: flex; align-items: center; gap: 0.3rem; }
        .qty-selector .qty-ctrl {
            width: 30px; height: 30px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: #fff;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .qty-selector .qty-ctrl:hover { background: var(--catalog-accent); color: #fff; border-color: var(--catalog-accent); }
        .qty-selector .qty-val { font-weight: 700; font-size: 0.9rem; min-width: 28px; text-align: center; }

        /* Animations */
        @keyframes cartPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .cart-pulse { animation: cartPulse 0.3s ease; }

        /* Loading overlay */
        .loading-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.7);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .loading-overlay.show { display: flex; }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status"></div>
        <p class="mt-2 fw-bold text-primary">Atualizando carrinho...</p>
    </div>
</div>

<!-- Header -->
<header class="catalog-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <?php if ($companyLogo): ?>
                <img src="<?= eAttr($companyLogo) ?>" alt="Logo" class="logo-img">
                <?php else: ?>
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10" style="width:42px;height:42px;">
                    <i class="fas fa-store text-white"></i>
                </div>
                <?php endif; ?>
                <div>
                    <h1><?= e($companyName) ?></h1>
                    <div class="welcome-text">
                        <i class="fas fa-user me-1"></i> Olá, <?= e($customerName) ?>! Monte sua lista de produtos.
                    </div>
                </div>
            </div>
            <div class="d-none d-md-block">
                <?php if ($showPrices): ?>
                <span class="badge bg-success bg-opacity-75 py-2 px-3">
                    <i class="fas fa-tags me-1"></i> Preços visíveis
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Filtros e Busca -->
<div class="container mt-3 mb-3">
    <div class="filter-bar">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="position-relative">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control search-input" id="searchInput" 
                           placeholder="Buscar produtos..." autocomplete="off">
                </div>
            </div>
            <div class="col-md-7">
                <div class="category-pills">
                    <span class="category-pill active" data-category="all">
                        <i class="fas fa-th me-1"></i> Todos
                    </span>
                    <?php foreach ($categories as $cat): ?>
                    <span class="category-pill" data-category="<?= $cat['id'] ?>">
                        <?= e($cat['name']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Produtos -->
<div class="container mb-5">
    <div class="product-grid" id="productGrid">
        <?php if (empty($products)): ?>
        <div class="empty-catalog">
            <i class="fas fa-box-open"></i>
            <h4>Nenhum produto disponível</h4>
            <p>Não há produtos cadastrados no catálogo.</p>
        </div>
        <?php else: ?>
        <?php foreach ($products as $prod): 
            $inCart = isset($cartByProduct[$prod['id']]);
            $cartQty = $inCart ? $cartByProduct[$prod['id']]['quantity'] : 0;
            $displayPrice = isset($customerPrices[$prod['id']]) ? $customerPrices[$prod['id']] : $prod['price'];
            $images = $productImages[$prod['id']] ?? [];
            $mainImage = null;
            foreach ($images as $img) {
                if ($img['is_main']) { $mainImage = $img['image_path']; break; }
            }
            if (!$mainImage && !empty($images)) {
                $mainImage = $images[0]['image_path'];
            }
        ?>
        <div class="product-card" 
             data-product-id="<?= $prod['id'] ?>" 
             data-category="<?= $prod['category_id'] ?>"
             data-name="<?= eAttr(strtolower($prod['name'])) ?>"
             data-price="<?= $displayPrice ?>"
             data-has-combos="<?= !empty($productCombinations[$prod['id']]) ? '1' : '0' ?>">
            
            <?php if ($inCart): ?>
            <div class="in-cart-badge" id="badge-<?= $prod['id'] ?>">
                <i class="fas fa-check me-1"></i> <span class="badge-qty"><?= $cartQtyByProduct[$prod['id']] ?? $cartQty ?></span> no carrinho
            </div>
            <?php else: ?>
            <div class="in-cart-badge" id="badge-<?= $prod['id'] ?>" style="display:none;">
                <i class="fas fa-check me-1"></i> <span class="badge-qty">0</span> no carrinho
            </div>
            <?php endif; ?>
            
            <div class="card-img-wrap">
                <?php if ($mainImage): ?>
                <img src="<?= eAttr($mainImage) ?>" alt="<?= eAttr($prod['name']) ?>" loading="lazy">
                <?php else: ?>
                <div class="no-image"><i class="fas fa-image"></i></div>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <div class="product-name"><?= e($prod['name']) ?></div>
                <?php if (!empty($prod['description'])): ?>
                <div class="product-desc"><?= e(mb_strimwidth($prod['description'], 0, 80, '...')) ?></div>
                <?php endif; ?>
                
                <?php if ($showPrices): ?>
                <div class="product-price">R$ <?= number_format($displayPrice, 2, ',', '.') ?></div>
                <?php endif; ?>

                <?php if (!empty($productCombinations[$prod['id']])): ?>
                <div class="mb-2">
                    <select class="form-select form-select-sm variation-catalog-select" id="var-<?= $prod['id'] ?>"
                            style="font-size:0.78rem; border-radius:8px;">
                        <option value="">Selecione a variação...</option>
                        <?php foreach ($productCombinations[$prod['id']] as $combo): ?>
                        <option value="<?= $combo['id'] ?>" 
                                data-label="<?= e($combo['combination_label']) ?>"
                                data-price="<?= $combo['price_override'] !== null ? $combo['price_override'] : '' ?>">
                            <?= e($combo['combination_label']) ?>
                            <?php if ($showPrices && $combo['price_override'] !== null): ?>
                            — R$ <?= number_format($combo['price_override'], 2, ',', '.') ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2 mt-auto">
                    <div class="qty-selector flex-shrink-0">
                        <button class="qty-ctrl" onclick="changeQty(<?= $prod['id'] ?>, -1)">−</button>
                        <span class="qty-val" id="qty-<?= $prod['id'] ?>">1</span>
                        <button class="qty-ctrl" onclick="changeQty(<?= $prod['id'] ?>, 1)">+</button>
                    </div>
                    <button class="btn btn-add-cart btn-primary flex-grow-1" onclick="addToCart(<?= $prod['id'] ?>)">
                        <i class="fas fa-plus me-1"></i> Adicionar
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="empty-catalog" id="noResults" style="display:none;">
        <i class="fas fa-search"></i>
        <h4>Nenhum produto encontrado</h4>
        <p>Tente buscar com outro termo ou categoria.</p>
    </div>
</div>

<!-- Botão Flutuante do Carrinho -->
<button class="cart-fab" id="cartFab" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
    <i class="fas fa-shopping-cart"></i>
    <div class="cart-badge" id="cartBadge"><?= $cartCount ?></div>
</button>

<!-- Offcanvas do Carrinho -->
<div class="offcanvas offcanvas-end offcanvas-cart" tabindex="-1" id="cartOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="fas fa-shopping-cart me-2"></i>Meu Carrinho</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div id="cartItemsList" class="px-3">
            <?php if (empty($cartItems)): ?>
            <div class="empty-catalog" id="emptyCart">
                <i class="fas fa-shopping-cart" style="font-size:2.5rem;"></i>
                <h5 class="mt-2">Carrinho vazio</h5>
                <p class="small">Adicione produtos do catálogo para começar.</p>
            </div>
            <?php else: ?>
            <?php foreach ($cartItems as $ci): 
                $ciImages = $productImages[$ci['product_id']] ?? [];
                $ciMainImg = null;
                foreach ($ciImages as $img) { if ($img['is_main']) { $ciMainImg = $img['image_path']; break; } }
                if (!$ciMainImg && !empty($ciImages)) { $ciMainImg = $ciImages[0]['image_path']; }
            ?>
            <div class="cart-item" data-item-id="<?= $ci['id'] ?>" data-product-id="<?= $ci['product_id'] ?>">
                <?php if ($ciMainImg): ?>
                <img src="<?= eAttr($ciMainImg) ?>" class="cart-item-img" alt="">
                <?php else: ?>
                <div class="cart-item-img d-flex align-items-center justify-content-center">
                    <i class="fas fa-image text-muted"></i>
                </div>
                <?php endif; ?>
                <div class="cart-item-info">
                    <div class="cart-item-name"><?= e($ci['product_name']) ?></div>
                    <?php if (!empty($ci['combination_label']) || !empty($ci['grade_description'])): ?>
                    <div class="small text-info"><i class="fas fa-layer-group me-1"></i><?= e($ci['combination_label'] ?? $ci['grade_description']) ?></div>
                    <?php endif; ?>
                    <?php if ($showPrices): ?>
                    <div class="cart-item-price">R$ <?= number_format($ci['unit_price'], 2, ',', '.') ?> × <?= $ci['quantity'] ?></div>
                    <?php else: ?>
                    <div class="cart-item-price">Qtd: <?= $ci['quantity'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="cart-item-actions">
                    <button class="qty-btn" onclick="updateCartQty(<?= $ci['id'] ?>, <?= $ci['quantity'] - 1 ?>)">−</button>
                    <span class="qty-display"><?= $ci['quantity'] ?></span>
                    <button class="qty-btn" onclick="updateCartQty(<?= $ci['id'] ?>, <?= $ci['quantity'] + 1 ?>)">+</button>
                </div>
                <span class="cart-item-remove" onclick="removeFromCart(<?= $ci['id'] ?>)" title="Remover">
                    <i class="fas fa-trash-alt"></i>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($showPrices): ?>
    <div class="cart-total-bar">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted small">Total estimado</span>
                <h4 class="mb-0 text-success fw-bold" id="cartTotalDisplay">R$ <?= number_format($cartTotal, 2, ',', '.') ?></h4>
            </div>
            <span class="badge bg-primary py-2 px-3" id="cartCountDisplay"><?= $cartCount ?> ite<?= $cartCount === 1 ? 'm' : 'ns' ?></span>
        </div>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle me-1"></i>Os valores são estimados. O orçamento final será elaborado pela equipe.
        </small>
    </div>
    <?php else: ?>
    <div class="cart-total-bar">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Produtos selecionados</span>
            <span class="badge bg-primary py-2 px-3" id="cartCountDisplay"><?= $cartCount ?> ite<?= $cartCount === 1 ? 'm' : 'ns' ?></span>
        </div>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle me-1"></i>A equipe entrará em contato com o orçamento.
        </small>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const TOKEN = '<?= e($token) ?>';
const SHOW_PRICES = <?= $showPrices ? 'true' : 'false' ?>;
const BASE_URL = '?page=catalog';

// ── Busca e Filtros ──
const searchInput = document.getElementById('searchInput');
const categoryPills = document.querySelectorAll('.category-pill');
let activeCategory = 'all';

searchInput.addEventListener('input', filterProducts);
categoryPills.forEach(pill => {
    pill.addEventListener('click', function() {
        categoryPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeCategory = this.dataset.category;
        filterProducts();
    });
});

function filterProducts() {
    const search = searchInput.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    let visible = 0;
    
    cards.forEach(card => {
        const name = card.dataset.name;
        const cat = card.dataset.category;
        const matchSearch = !search || name.includes(search);
        const matchCat = activeCategory === 'all' || cat === activeCategory;
        
        if (matchSearch && matchCat) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('noResults').style.display = visible === 0 ? '' : 'none';
}

// ── Quantidade no card do produto ──
function changeQty(productId, delta) {
    const el = document.getElementById('qty-' + productId);
    let val = parseInt(el.textContent) + delta;
    if (val < 1) val = 1;
    if (val > 999) val = 999;
    el.textContent = val;
}

// ── Adicionar ao Carrinho ──
function addToCart(productId) {
    const qty = parseInt(document.getElementById('qty-' + productId).textContent);
    const card = document.querySelector(`.product-card[data-product-id="${productId}"]`);
    const hasCombos = card && card.dataset.hasCombos === '1';
    
    let combinationId = '';
    let gradeDescription = '';
    
    if (hasCombos) {
        const varSelect = document.getElementById('var-' + productId);
        if (varSelect && !varSelect.value) {
            showToast('Selecione uma variação antes de adicionar.', 'warning');
            varSelect.focus();
            return;
        }
        if (varSelect) {
            combinationId = varSelect.value;
            const opt = varSelect.options[varSelect.selectedIndex];
            gradeDescription = opt ? (opt.dataset.label || '') : '';
        }
    }
    
    showLoading(true);
    
    fetch(BASE_URL + '&action=addToCart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `token=${TOKEN}&product_id=${productId}&quantity=${qty}&combination_id=${combinationId}&grade_description=${encodeURIComponent(gradeDescription)}`
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            updateCartUI(data);
            pulseCartFab();
            
            // Mostrar badge no card
            const badge = document.getElementById('badge-' + productId);
            if (badge) {
                const totalInCart = data.cart.reduce((sum, item) => item.product_id == productId ? sum + item.quantity : sum, 0);
                badge.querySelector('.badge-qty').textContent = totalInCart;
                badge.style.display = '';
            }
            
            // Reset qty
            document.getElementById('qty-' + productId).textContent = '1';
            
            showToast('Produto adicionado ao carrinho!', 'success');
        } else {
            showToast(data.message || 'Erro ao adicionar', 'error');
        }
    })
    .catch(() => {
        showLoading(false);
        showToast('Erro de conexão', 'error');
    });
}

// ── Remover do Carrinho ──
function removeFromCart(itemId) {
    Swal.fire({
        title: 'Remover item?',
        text: 'Deseja remover este produto da sua lista?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e74c3c'
    }).then(result => {
        if (result.isConfirmed) {
            showLoading(true);
            fetch(BASE_URL + '&action=removeFromCart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `token=${TOKEN}&item_id=${itemId}`
            })
            .then(r => r.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    updateCartUI(data);
                    updateProductBadges(data.cart);
                    showToast('Produto removido!', 'info');
                }
            })
            .catch(() => { showLoading(false); showToast('Erro de conexão', 'error'); });
        }
    });
}

// ── Atualizar quantidade no carrinho ──
function updateCartQty(itemId, newQty) {
    if (newQty < 1) {
        removeFromCart(itemId);
        return;
    }
    
    showLoading(true);
    fetch(BASE_URL + '&action=updateCartItem', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `token=${TOKEN}&item_id=${itemId}&quantity=${newQty}`
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            updateCartUI(data);
            updateProductBadges(data.cart);
        }
    })
    .catch(() => { showLoading(false); showToast('Erro de conexão', 'error'); });
}

// ── Atualizar UI do Carrinho ──
function updateCartUI(data) {
    const cartBadge = document.getElementById('cartBadge');
    const cartCountDisplay = document.getElementById('cartCountDisplay');
    const cartTotalDisplay = document.getElementById('cartTotalDisplay');
    const cartItemsList = document.getElementById('cartItemsList');
    
    cartBadge.textContent = data.cart_count;
    if (cartCountDisplay) cartCountDisplay.textContent = data.cart_count + ' ite' + (data.cart_count === 1 ? 'm' : 'ns');
    if (cartTotalDisplay) cartTotalDisplay.textContent = 'R$ ' + formatMoney(data.cart_total);
    
    // Reconstruir lista do carrinho
    if (data.cart.length === 0) {
        cartItemsList.innerHTML = `
            <div class="empty-catalog" id="emptyCart">
                <i class="fas fa-shopping-cart" style="font-size:2.5rem;"></i>
                <h5 class="mt-2">Carrinho vazio</h5>
                <p class="small">Adicione produtos do catálogo para começar.</p>
            </div>`;
        return;
    }
    
    let html = '';
    data.cart.forEach(item => {
        const imgHtml = getProductImageHtml(item.product_id);
        const variationLabel = item.combination_label || item.grade_description || '';
        html += `
        <div class="cart-item" data-item-id="${item.id}" data-product-id="${item.product_id}">
            ${imgHtml}
            <div class="cart-item-info">
                <div class="cart-item-name">${escHtml(item.product_name)}</div>
                ${variationLabel ? `<div class="small text-info"><i class="fas fa-layer-group me-1"></i>${escHtml(variationLabel)}</div>` : ''}
                ${SHOW_PRICES 
                    ? `<div class="cart-item-price">R$ ${formatMoney(item.unit_price)} × ${item.quantity}</div>` 
                    : `<div class="cart-item-price">Qtd: ${item.quantity}</div>`}
            </div>
            <div class="cart-item-actions">
                <button class="qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity - 1})">−</button>
                <span class="qty-display">${item.quantity}</span>
                <button class="qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity + 1})">+</button>
            </div>
            <span class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Remover">
                <i class="fas fa-trash-alt"></i>
            </span>
        </div>`;
    });
    cartItemsList.innerHTML = html;
}

// ── Atualizar badges dos produtos ──
function updateProductBadges(cart) {
    // Resetar todos
    document.querySelectorAll('.in-cart-badge').forEach(badge => {
        badge.style.display = 'none';
        badge.querySelector('.badge-qty').textContent = '0';
    });
    
    // Atualizar com dados do carrinho
    const productQtys = {};
    cart.forEach(item => {
        productQtys[item.product_id] = (productQtys[item.product_id] || 0) + item.quantity;
    });
    
    Object.entries(productQtys).forEach(([pid, qty]) => {
        const badge = document.getElementById('badge-' + pid);
        if (badge) {
            badge.querySelector('.badge-qty').textContent = qty;
            badge.style.display = '';
        }
    });
}

// ── Helpers ──
function formatMoney(val) {
    return parseFloat(val).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function escHtml(text) {
    const el = document.createElement('span');
    el.textContent = text;
    return el.innerHTML;
}

function getProductImageHtml(productId) {
    // Pegar a imagem do card do produto para reutilizar no carrinho
    const card = document.querySelector(`.product-card[data-product-id="${productId}"]`);
    if (card) {
        const img = card.querySelector('.card-img-wrap img');
        if (img) {
            return `<img src="${img.src}" class="cart-item-img" alt="">`;
        }
    }
    return `<div class="cart-item-img d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>`;
}

function pulseCartFab() {
    const fab = document.getElementById('cartFab');
    fab.classList.add('cart-pulse');
    setTimeout(() => fab.classList.remove('cart-pulse'), 300);
}

function showLoading(show) {
    document.getElementById('loadingOverlay').classList.toggle('show', show);
}

function showToast(msg, icon) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: msg,
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
}

// ── Auto-refresh: verificar carrinho a cada 30s (para sincronia com admin) ──
setInterval(() => {
    fetch(BASE_URL + '&action=getCart&token=' + TOKEN)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const currentCount = parseInt(document.getElementById('cartBadge').textContent);
                if (currentCount !== data.cart_count) {
                    updateCartUI(data);
                    updateProductBadges(data.cart);
                }
            }
        })
        .catch(() => {});
}, 30000);
</script>
</body>
</html>
