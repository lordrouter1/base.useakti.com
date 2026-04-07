<?php
/**
 * Site Builder — Preview da Loja
 *
 * Renderiza a prévia da loja para o iframe do editor.
 * Usa as configurações do sb_theme_settings diretamente.
 * Páginas fixas: home, produtos, contato, carrinho, perfil.
 *
 * Variáveis disponíveis (vindas do controller):
 *   $settings        — configurações do tenant (key => value)
 *   $previewPage     — slug da página a exibir (home, produtos, contato, carrinho, perfil)
 *   $previewProducts — array de produtos para preview
 */

// ─── Extrair configurações com defaults ────────────────────────────
$primary     = htmlspecialchars($settings['primary_color'] ?? '#3b82f6', ENT_QUOTES, 'UTF-8');
$secondary   = htmlspecialchars($settings['secondary_color'] ?? '#64748b', ENT_QUOTES, 'UTF-8');
$accent      = htmlspecialchars($settings['accent_color'] ?? '#f59e0b', ENT_QUOTES, 'UTF-8');
$bgColor     = htmlspecialchars($settings['bg_color'] ?? '#ffffff', ENT_QUOTES, 'UTF-8');
$textColor   = htmlspecialchars($settings['text_color'] ?? '#333333', ENT_QUOTES, 'UTF-8');

$headerBg    = htmlspecialchars($settings['header_bg_color'] ?? '#ffffff', ENT_QUOTES, 'UTF-8');
$headerText  = htmlspecialchars($settings['header_text_color'] ?? '#333333', ENT_QUOTES, 'UTF-8');
$headerSticky = ($settings['header_sticky'] ?? '1') === '1' || ($settings['header_sticky'] ?? '') === 'true';
$headerLogoPos = htmlspecialchars($settings['header_logo_position'] ?? 'left', ENT_QUOTES, 'UTF-8');

$footerBg    = htmlspecialchars($settings['footer_bg_color'] ?? '#2c3e50', ENT_QUOTES, 'UTF-8');
$footerText  = htmlspecialchars($settings['footer_text_color'] ?? '#ffffff', ENT_QUOTES, 'UTF-8');

// Whitelist de fontes
$allowedFonts = [
    'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Raleway', 'Nunito', 'Ubuntu', 'Playfair Display', 'Merriweather',
    'Source Sans Pro', 'PT Sans', 'Oswald', 'Quicksand',
];
$bodyFont = in_array($settings['body_font'] ?? 'Inter', $allowedFonts, true)
    ? ($settings['body_font'] ?? 'Inter')
    : 'Inter';
$headingFont = in_array($settings['heading_font'] ?? $bodyFont, $allowedFonts, true)
    ? ($settings['heading_font'] ?? $bodyFont)
    : $bodyFont;
$fontFamilies = array_values(array_unique([$bodyFont, $headingFont]));
$fontQuery = implode('&family=', array_map(static function (string $font): string {
    return str_replace('%20', '+', rawurlencode($font)) . ':wght@300;400;500;600;700';
}, $fontFamilies));

$shopName = htmlspecialchars(
    $settings['shop_name'] ?? $_SESSION['company_name'] ?? 'Minha Loja',
    ENT_QUOTES,
    'UTF-8'
);
$shopLogo = htmlspecialchars($settings['shop_logo_url'] ?? '', ENT_QUOTES, 'UTF-8');

// Hero
$heroTitle    = htmlspecialchars($settings['hero_title'] ?? 'Bem-vindo à nossa loja', ENT_QUOTES, 'UTF-8');
$heroSubtitle = htmlspecialchars($settings['hero_subtitle'] ?? 'Encontre os melhores produtos aqui', ENT_QUOTES, 'UTF-8');
$heroImageUrl = htmlspecialchars($settings['hero_image_url'] ?? '', ENT_QUOTES, 'UTF-8');
$heroBgColor  = htmlspecialchars($settings['hero_bg_color'] ?? '#1a1a2e', ENT_QUOTES, 'UTF-8');
$heroCtaText  = htmlspecialchars($settings['hero_cta_text'] ?? 'Ver Produtos', ENT_QUOTES, 'UTF-8');
$heroCtaUrl   = htmlspecialchars($settings['hero_cta_url'] ?? '/loja/produtos', ENT_QUOTES, 'UTF-8');

// Featured products
$showFeatured = ($settings['show_featured_products'] ?? '1') === '1' || ($settings['show_featured_products'] ?? '') === 'true';
$featuredTitle = htmlspecialchars($settings['featured_products_title'] ?? 'Produtos em Destaque', ENT_QUOTES, 'UTF-8');

// About
$aboutTitle    = htmlspecialchars($settings['about_title'] ?? 'Sobre Nós', ENT_QUOTES, 'UTF-8');
$aboutText     = htmlspecialchars($settings['about_text'] ?? '', ENT_QUOTES, 'UTF-8');
$aboutImageUrl = htmlspecialchars($settings['about_image_url'] ?? '', ENT_QUOTES, 'UTF-8');

// Contact
$contactAddress  = htmlspecialchars($settings['contact_address'] ?? '', ENT_QUOTES, 'UTF-8');
$contactPhone    = htmlspecialchars($settings['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$contactEmail    = htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES, 'UTF-8');
$contactWhatsapp = htmlspecialchars($settings['contact_whatsapp'] ?? '', ENT_QUOTES, 'UTF-8');
$contactHours    = htmlspecialchars($settings['contact_hours'] ?? '', ENT_QUOTES, 'UTF-8');
$contactMapEmbed = $settings['contact_map_embed'] ?? '';

// Footer social
$socialFb  = htmlspecialchars($settings['social_facebook'] ?? '', ENT_QUOTES, 'UTF-8');
$socialIg  = htmlspecialchars($settings['social_instagram'] ?? '', ENT_QUOTES, 'UTF-8');
$socialWa  = htmlspecialchars($settings['social_whatsapp'] ?? '', ENT_QUOTES, 'UTF-8');
$socialTw  = htmlspecialchars($settings['social_twitter'] ?? '', ENT_QUOTES, 'UTF-8');
$footerTextContent = htmlspecialchars($settings['footer_text'] ?? '', ENT_QUOTES, 'UTF-8');

// Nav classes based on logo position
$brandClass = '';
$navListClass = 'ms-auto';
if ($headerLogoPos === 'center') {
    $brandClass = 'mx-auto';
    $navListClass = 'mx-auto';
} elseif ($headerLogoPos === 'right') {
    $brandClass = 'ms-auto order-2';
    $navListClass = 'me-auto order-1';
}

$previewPage = $previewPage ?? 'home';
$previewProducts = $previewProducts ?? [];

// Helper for rendering product cards
function sbp_render_products(array $products, string $primary): string
{
    if (empty($products)) {
        return '<div class="text-muted text-center py-3">Nenhum produto cadastrado para preview.</div>';
    }
    $html = '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">';
    foreach (array_slice($products, 0, 12) as $p) {
        $name = htmlspecialchars($p['name'] ?? 'Produto', ENT_QUOTES, 'UTF-8');
        $img = htmlspecialchars($p['main_image_path'] ?? $p['photo_url'] ?? '', ENT_QUOTES, 'UTF-8');
        $price = 'R$ ' . number_format((float)($p['price'] ?? 0), 2, ',', '.');
        $imgTag = $img ? "<img src=\"{$img}\" class=\"card-img-top\" style=\"height:180px;object-fit:cover\" alt=\"{$name}\">"
            : '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px"><i class="fas fa-box fa-2x text-muted"></i></div>';
        $html .= "<div class=\"col\"><div class=\"card h-100 border-0 shadow-sm\">{$imgTag}<div class=\"card-body\"><h6 class=\"card-title\">{$name}</h6><span class=\"fw-bold\" style=\"color:{$primary}\">{$price}</span></div></div></div>";
    }
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview — <?= $shopName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=<?= $fontQuery ?>&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= $primary ?>;
            --secondary-color: <?= $secondary ?>;
            --accent-color: <?= $accent ?>;
            --body-font: '<?= $bodyFont ?>', sans-serif;
            --heading-font: '<?= $headingFont ?>', sans-serif;
        }
        body { font-family: var(--body-font); background: <?= $bgColor ?>; color: <?= $textColor ?>; margin: 0; }
        h1, h2, h3, h4, h5, h6 { font-family: var(--heading-font); }
        .btn-primary { background-color: var(--primary-color) !important; border-color: var(--primary-color) !important; }
        .text-primary { color: var(--primary-color) !important; }
    </style>
</head>
<body>

    <!-- ═══ HEADER ═══ -->
    <header class="<?= $headerSticky ? 'sticky-top' : '' ?>" style="background-color: <?= $headerBg ?>; color: <?= $headerText ?>;">
        <nav class="navbar navbar-expand-lg">
            <div class="container flex-wrap">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 <?= $brandClass ?>" href="#" style="color: <?= $headerText ?>;">
                    <?php if ($shopLogo): ?>
                        <img src="<?= $shopLogo ?>" alt="<?= $shopName ?>" style="max-height:42px; max-width:120px; object-fit:contain;">
                    <?php endif; ?>
                    <strong><?= $shopName ?></strong>
                </a>
                <div class="collapse navbar-collapse show">
                    <ul class="navbar-nav <?= $navListClass ?>">
                        <li class="nav-item"><a class="nav-link" style="color: <?= $headerText ?>;" href="#">Início</a></li>
                        <li class="nav-item"><a class="nav-link" style="color: <?= $headerText ?>;" href="#">Produtos</a></li>
                        <li class="nav-item"><a class="nav-link" style="color: <?= $headerText ?>;" href="#">Contato</a></li>
                    </ul>
                    <div class="ms-3">
                        <a href="#" class="btn btn-outline-secondary btn-sm" style="color: <?= $headerText ?>; border-color: <?= $headerText ?>;">
                            <i class="fas fa-shopping-cart"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- ═══ CONTENT ═══ -->
    <main>

    <?php if ($previewPage === 'home'): ?>
        <!-- HERO BANNER -->
        <?php
            $heroBgStyle = $heroImageUrl
                ? "background: url('{$heroImageUrl}') center/cover no-repeat; min-height: 400px;"
                : "background: linear-gradient(135deg, {$heroBgColor}, {$primary}); min-height: 400px;";
        ?>
        <section class="d-flex align-items-center position-relative" style="<?= $heroBgStyle ?>">
            <?php if ($heroImageUrl): ?>
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.4);"></div>
            <?php endif; ?>
            <div class="container text-center text-white position-relative py-5">
                <h1 class="display-5 fw-bold mb-3"><?= $heroTitle ?></h1>
                <p class="lead mb-4"><?= $heroSubtitle ?></p>
                <?php if ($heroCtaText): ?>
                    <a href="<?= $heroCtaUrl ?>" class="btn btn-light btn-lg"><?= $heroCtaText ?></a>
                <?php endif; ?>
            </div>
        </section>

        <!-- FEATURED PRODUCTS -->
        <?php if ($showFeatured): ?>
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-4"><?= $featuredTitle ?></h2>
                <?= sbp_render_products($previewProducts, $primary) ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ABOUT SECTION -->
        <?php if ($aboutText): ?>
        <section class="py-5" style="background-color: #f8f9fa;">
            <div class="container">
                <div class="row align-items-center g-4">
                    <?php if ($aboutImageUrl): ?>
                    <div class="col-md-5">
                        <img src="<?= $aboutImageUrl ?>" class="img-fluid rounded shadow" alt="<?= $aboutTitle ?>">
                    </div>
                    <div class="col-md-7">
                    <?php else: ?>
                    <div class="col-12">
                    <?php endif; ?>
                        <h2 class="mb-3"><?= $aboutTitle ?></h2>
                        <p class="text-muted"><?= nl2br($aboutText) ?></p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

    <?php elseif ($previewPage === 'produtos'): ?>
        <section class="py-5">
            <div class="container">
                <h1 class="h2 mb-4">Produtos</h1>
                <?= sbp_render_products($previewProducts, $primary) ?>
            </div>
        </section>

    <?php elseif ($previewPage === 'contato'): ?>
        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-7">
                        <h1 class="h2 mb-4">Entre em Contato</h1>
                        <form>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome</label>
                                    <input type="text" class="form-control" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Mensagem</label>
                                    <textarea class="form-control" rows="4" disabled></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" disabled>
                                        <i class="fas fa-paper-plane me-2"></i>Enviar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Informações</h5>
                                <?php if ($contactAddress): ?>
                                    <p class="mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i><?= nl2br($contactAddress) ?></p>
                                <?php endif; ?>
                                <?php if ($contactPhone): ?>
                                    <p class="mb-2"><i class="fas fa-phone me-2 text-primary"></i><?= $contactPhone ?></p>
                                <?php endif; ?>
                                <?php if ($contactEmail): ?>
                                    <p class="mb-2"><i class="fas fa-envelope me-2 text-primary"></i><?= $contactEmail ?></p>
                                <?php endif; ?>
                                <?php if ($contactWhatsapp): ?>
                                    <p class="mb-2"><i class="fab fa-whatsapp me-2 text-success"></i><?= $contactWhatsapp ?></p>
                                <?php endif; ?>
                                <?php if ($contactHours): ?>
                                    <p class="mb-0"><i class="fas fa-clock me-2 text-primary"></i><?= $contactHours ?></p>
                                <?php endif; ?>
                                <?php if (!$contactAddress && !$contactPhone && !$contactEmail): ?>
                                    <p class="text-muted mb-0">Configure as informações de contato no painel.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <?php elseif ($previewPage === 'carrinho'): ?>
        <section class="py-5">
            <div class="container">
                <h1 class="h2 mb-4"><i class="fas fa-shopping-cart me-2"></i>Carrinho</h1>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-shopping-cart fa-3x mb-3 d-block" style="opacity:0.3"></i>
                    <p>O carrinho está vazio.</p>
                    <a href="#" class="btn btn-primary">Ver Produtos</a>
                </div>
            </div>
        </section>

    <?php elseif ($previewPage === 'perfil'): ?>
        <section class="py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-4"><i class="fas fa-user me-2"></i>Meu Perfil</h1>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" value="João Silva" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" class="form-control" value="joao@email.com" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Telefone</label>
                                        <input type="text" class="form-control" value="(11) 99999-9999" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="h5 mt-4 mb-3">Pedidos Recentes</h3>
                        <div class="text-muted text-center py-3">Nenhum pedido encontrado.</div>
                    </div>
                </div>
            </div>
        </section>

    <?php endif; ?>

    </main>

    <!-- ═══ FOOTER ═══ -->
    <footer class="mt-auto" style="background-color: <?= $footerBg ?>; color: <?= $footerText ?>;">
        <div class="container py-5">
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col">
                    <h5 style="color: <?= $footerText ?>;"><?= $shopName ?></h5>
                    <?php if ($footerTextContent): ?>
                        <p class="small" style="opacity: 0.8;"><?= $footerTextContent ?></p>
                    <?php else: ?>
                        <p class="small" style="opacity: 0.8;">Sua loja online.</p>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <?php if ($socialFb): ?><a href="<?= $socialFb ?>" style="color: <?= $footerText ?>; opacity: 0.7;"><i class="fab fa-facebook fa-lg"></i></a><?php endif; ?>
                        <?php if ($socialIg): ?><a href="<?= $socialIg ?>" style="color: <?= $footerText ?>; opacity: 0.7;"><i class="fab fa-instagram fa-lg"></i></a><?php endif; ?>
                        <?php if ($socialWa): ?><a href="https://wa.me/<?= $socialWa ?>" style="color: <?= $footerText ?>; opacity: 0.7;"><i class="fab fa-whatsapp fa-lg"></i></a><?php endif; ?>
                        <?php if ($socialTw): ?><a href="<?= $socialTw ?>" style="color: <?= $footerText ?>; opacity: 0.7;"><i class="fab fa-x-twitter fa-lg"></i></a><?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <h5 style="color: <?= $footerText ?>;">Links</h5>
                    <ul class="list-unstyled small" style="opacity: 0.8;">
                        <li class="mb-1"><a href="#" class="text-decoration-none" style="color: <?= $footerText ?>;">Início</a></li>
                        <li class="mb-1"><a href="#" class="text-decoration-none" style="color: <?= $footerText ?>;">Produtos</a></li>
                        <li class="mb-1"><a href="#" class="text-decoration-none" style="color: <?= $footerText ?>;">Contato</a></li>
                    </ul>
                </div>
                <div class="col">
                    <h5 style="color: <?= $footerText ?>;">Contato</h5>
                    <ul class="list-unstyled small" style="opacity: 0.8;">
                        <?php if ($contactEmail): ?>
                            <li class="mb-1"><i class="fas fa-envelope me-1"></i><?= $contactEmail ?></li>
                        <?php endif; ?>
                        <?php if ($contactPhone): ?>
                            <li class="mb-1"><i class="fas fa-phone me-1"></i><?= $contactPhone ?></li>
                        <?php endif; ?>
                        <?php if (!$contactEmail && !$contactPhone): ?>
                            <li class="mb-1 text-muted">Não configurado</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-top" style="border-color: rgba(255,255,255,0.1) !important;">
            <div class="container py-3">
                <p class="text-center small mb-0" style="opacity: 0.6;">
                    &copy; <?= date('Y') ?> <?= $shopName ?>. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
