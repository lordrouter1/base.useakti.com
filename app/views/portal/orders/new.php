<?php
/**
 * Portal do Cliente — Novo Pedido (Catálogo + Carrinho)
 *
 * Variáveis: $products, $categories, $cart, $cartCount, $totalPages,
 *            $page, $search, $categoryId, $orderNotes, $company
 */
$cart      = $cart ?? [];
$cartCount = $cartCount ?? 0;
$cartTotal = 0;
foreach ($cart as $ci) {
    $cartTotal += $ci['price'] * $ci['quantity'];
}
?>

<div class="portal-page">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-circle-plus me-2"></i>
            <?= __p('new_order_title') ?>
        </h1>
    </div>

    <?php if (!empty($_GET['error']) && $_GET['error'] === 'empty'): ?>
        <div class="alert alert-warning alert-sm">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <?= __p('cart_empty') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($orderNotes)): ?>
        <div class="alert alert-info alert-sm">
            <i class="fas fa-info-circle me-1"></i>
            <?= e($orderNotes) ?>
        </div>
    <?php endif; ?>

    <!-- ═══ Busca e Filtros ═══ -->
    <div class="portal-search-bar mb-3">
        <form method="GET" class="d-flex gap-2" id="portalSearchForm">
            <input type="hidden" name="page" value="portal">
            <input type="hidden" name="action" value="newOrder">
            <div class="input-group">
                <input type="text" name="q" class="form-control portal-input"
                       placeholder="<?= __p('search') ?>..." value="<?= eAttr($search) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <?php if (!empty($categories)): ?>
                <select name="category" class="form-select portal-input" style="max-width:180px;" onchange="this.form.submit()">
                    <option value=""><?= __p('new_order_all_categories') ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"
                            <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= (int) $cat['product_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>
    </div>

    <!-- ═══ Carrinho Flutuante (Badge) ═══ -->
    <div class="portal-cart-fab" id="portalCartFab" onclick="toggleCart()" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>">
        <i class="fas fa-shopping-cart"></i>
        <span class="portal-cart-fab-badge" id="portalCartBadge"><?= $cartCount ?></span>
    </div>

    <!-- ═══ Grid de Produtos ═══ -->
    <?php if (empty($products)): ?>
        <div class="portal-empty-state">
            <i class="fas fa-box-open"></i>
            <p><?= __p('new_order_no_products') ?></p>
        </div>
    <?php else: ?>
        <div class="portal-product-grid" id="portalProductGrid">
            <?php foreach ($products as $prod): ?>
                <div class="portal-product-card" data-product-id="<?= (int) $prod['id'] ?>">
                    <div class="portal-product-image">
                        <?php if (!empty($prod['main_image_path'])): ?>
                            <img src="<?= eAttr(thumb_url($prod['main_image_path'], 300)) ?>" alt="<?= eAttr($prod['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="portal-product-no-image">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="portal-product-info">
                        <h4 class="portal-product-name"><?= e($prod['name']) ?></h4>
                        <?php if (!empty($prod['category_name'])): ?>
                            <small class="text-muted"><?= e($prod['category_name']) ?></small>
                        <?php endif; ?>
                        <div class="portal-product-price"><?= portal_money($prod['price']) ?></div>
                    </div>
                    <div class="portal-product-actions">
                        <button class="btn btn-primary btn-sm w-100 portal-btn-add-cart"
                                onclick="addToCart(<?= (int) $prod['id'] ?>)">
                            <i class="fas fa-cart-plus me-1"></i>
                            <?= __p('new_order_add') ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ═══ Paginação ═══ -->
        <?php if ($totalPages > 1): ?>
            <nav class="portal-pagination mt-3">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=portal&action=newOrder&q=<?= urlencode($search) ?>&category=<?= $categoryId ?>&p=<?= $i ?>"
                       class="portal-pagination-item <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ═══ Painel do Carrinho (slide-up) ═══ -->
<div class="portal-cart-panel" id="portalCartPanel" style="display:none;">
    <div class="portal-cart-panel-header">
        <h3><i class="fas fa-shopping-cart me-2"></i> <?= __p('new_order_cart') ?></h3>
        <button class="btn btn-sm btn-outline-secondary" onclick="toggleCart()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="portal-cart-panel-body" id="portalCartBody">
        <?php if (empty($cart)): ?>
            <div class="portal-empty-state portal-empty-sm">
                <i class="fas fa-shopping-cart"></i>
                <p><?= __p('cart_empty') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($cart as $idx => $ci): ?>
                <div class="portal-cart-item" data-product-id="<?= (int) $ci['product_id'] ?>">
                    <div class="portal-cart-item-info">
                        <strong><?= e($ci['name']) ?></strong>
                        <small><?= portal_money($ci['price']) ?></small>
                    </div>
                    <div class="portal-cart-item-qty">
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateCart(<?= (int) $ci['product_id'] ?>, <?= $ci['quantity'] - 1 ?>)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="portal-cart-qty-label"><?= (int) $ci['quantity'] ?></span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateCart(<?= (int) $ci['product_id'] ?>, <?= $ci['quantity'] + 1 ?>)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="portal-cart-item-subtotal">
                        <?= portal_money($ci['price'] * $ci['quantity']) ?>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(<?= (int) $ci['product_id'] ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="portal-cart-panel-footer" id="portalCartFooter" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong><?= __p('order_total') ?>:</strong>
            <strong class="text-primary" id="portalCartTotal"><?= portal_money($cartTotal) ?></strong>
        </div>
        <div class="mb-2">
            <textarea id="portalOrderNotes" class="form-control portal-input" rows="2"
                      placeholder="<?= __p('new_order_notes_placeholder') ?>"></textarea>
        </div>
        <button class="btn btn-primary w-100" onclick="submitOrder()" id="portalSubmitBtn">
            <i class="fas fa-paper-plane me-1"></i>
            <?= __p('new_order_submit') ?>
        </button>
    </div>
</div>
<div class="portal-cart-overlay" id="portalCartOverlay" style="display:none;" onclick="toggleCart()"></div>

<!-- ═══ Script do Carrinho ═══ -->
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function toggleCart() {
    const panel = document.getElementById('portalCartPanel');
    const overlay = document.getElementById('portalCartOverlay');
    const isVisible = panel.style.display !== 'none';
    panel.style.display = isVisible ? 'none' : 'flex';
    overlay.style.display = isVisible ? 'none' : 'block';
}

function addToCart(productId) {
    fetch('?page=portal&action=addToCart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `product_id=${productId}&quantity=1&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartUI(data);
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(() => showToast('<?= __p('error_generic') ?>', 'danger'));
}

function removeFromCart(productId) {
    fetch('?page=portal&action=removeFromCart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `product_id=${productId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) updateCartUI(data);
    });
}

function updateCart(productId, quantity) {
    fetch('?page=portal&action=updateCartItem', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `product_id=${productId}&quantity=${quantity}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) updateCartUI(data);
    });
}

function updateCartUI(data) {
    const badge = document.getElementById('portalCartBadge');
    const fab = document.getElementById('portalCartFab');
    const body = document.getElementById('portalCartBody');
    const footer = document.getElementById('portalCartFooter');
    const total = document.getElementById('portalCartTotal');

    badge.textContent = data.cartCount;
    fab.style.display = data.cartCount > 0 ? '' : 'none';
    footer.style.display = data.cartCount > 0 ? '' : 'none';
    total.textContent = 'R$ ' + parseFloat(data.cartTotal).toFixed(2).replace('.', ',');

    if (data.cart.length === 0) {
        body.innerHTML = '<div class="portal-empty-state portal-empty-sm"><i class="fas fa-shopping-cart"></i><p><?= __p('cart_empty') ?></p></div>';
        return;
    }

    let html = '';
    data.cart.forEach(item => {
        const sub = (item.price * item.quantity).toFixed(2).replace('.', ',');
        html += `<div class="portal-cart-item" data-product-id="${item.product_id}">
            <div class="portal-cart-item-info"><strong>${item.name}</strong><small>R$ ${parseFloat(item.price).toFixed(2).replace('.', ',')}</small></div>
            <div class="portal-cart-item-qty">
                <button class="btn btn-sm btn-outline-secondary" onclick="updateCart(${item.product_id}, ${item.quantity - 1})"><i class="fas fa-minus"></i></button>
                <span class="portal-cart-qty-label">${item.quantity}</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="updateCart(${item.product_id}, ${item.quantity + 1})"><i class="fas fa-plus"></i></button>
            </div>
            <div class="portal-cart-item-subtotal">R$ ${sub}</div>
            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.product_id})"><i class="fas fa-trash"></i></button>
        </div>`;
    });
    body.innerHTML = html;
}

function submitOrder() {
    const btn = document.getElementById('portalSubmitBtn');
    const notes = document.getElementById('portalOrderNotes').value;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?= __p('loading') ?>';

    fetch('?page=portal&action=submitOrder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `notes=${encodeURIComponent(notes)}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = '?page=portal&action=orderDetail&id=' + data.order_id + '&created=1';
            }, 800);
        } else {
            showToast(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> <?= __p('new_order_submit') ?>';
        }
    })
    .catch(() => {
        showToast('<?= __p('error_generic') ?>', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> <?= __p('new_order_submit') ?>';
    });
}

function showToast(message, type) {
    const existing = document.querySelector('.portal-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `portal-toast alert alert-${type}`;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
