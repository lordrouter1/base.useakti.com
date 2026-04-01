<?php
/**
 * Site Builder — Preview da Loja
 *
 * Renderiza a prévia da loja para o iframe do editor.
 * Em produção, usará Twig para renderizar os templates.
 * Esta é uma versão simplificada (HTML puro) para o MVP.
 *
 * Variáveis disponíveis (vindas do controller):
 *   $page           — página com seções e componentes
 *   $themeSettings  — configurações globais do tema
 */

use Akti\Utils\SafeHtml;

$headerBg    = htmlspecialchars($themeSettings['header_bg_color'] ?? '#ffffff');
$headerText  = htmlspecialchars($themeSettings['header_text_color'] ?? '#333333');
$headerSticky = ($themeSettings['header_sticky'] ?? '1') === '1';
$headerStyle = (string) ($themeSettings['header_style'] ?? 'default');
$headerLogoPosition = (string) ($themeSettings['header_logo_position'] ?? 'left');
$footerBg    = htmlspecialchars($themeSettings['footer_bg_color'] ?? '#2c3e50');
$footerText  = htmlspecialchars($themeSettings['footer_text_color'] ?? '#ffffff');
$footerCols  = (int) ($themeSettings['footer_columns'] ?? 3);
$footerStyle = (string) ($themeSettings['footer_style'] ?? 'default');
$primary     = htmlspecialchars($themeSettings['primary_color'] ?? '#3b82f6');
$secondary   = htmlspecialchars($themeSettings['secondary_color'] ?? '#64748b');
$accent      = htmlspecialchars($themeSettings['accent_color'] ?? ($themeSettings['primary_color'] ?? '#3b82f6'));

// Whitelist de fontes permitidas para evitar injeção via CSS/URL
$allowedFonts = [
    'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Raleway', 'Nunito', 'Ubuntu', 'Playfair Display', 'Merriweather',
    'Source Sans Pro', 'PT Sans', 'Oswald', 'Quicksand',
];
$bodyFont = in_array($themeSettings['body_font'] ?? 'Inter', $allowedFonts)
    ? ($themeSettings['body_font'] ?? 'Inter')
    : 'Inter';
$headingFont = in_array($themeSettings['heading_font'] ?? $bodyFont, $allowedFonts, true)
    ? ($themeSettings['heading_font'] ?? $bodyFont)
    : $bodyFont;
$fontFamilies = array_values(array_unique([$bodyFont, $headingFont]));
$fontQuery = implode('&family=', array_map(static function (string $font): string {
    return str_replace('%20', '+', rawurlencode($font)) . ':wght@300;400;500;600;700';
}, $fontFamilies));

$shopName    = htmlspecialchars($_SESSION['company_name'] ?? 'Minha Loja');
$headerBrandClass = '';
$headerNavListClass = 'ms-auto';

if ($headerLogoPosition === 'center') {
    $headerBrandClass = 'mx-auto';
    $headerNavListClass = 'mx-auto';
} elseif ($headerLogoPosition === 'right') {
    $headerBrandClass = 'ms-auto order-2';
    $headerNavListClass = 'me-auto order-1';
}

$footerPaddingClass = $footerStyle === 'minimal' ? 'py-4' : 'py-5';
$headerClassName = $headerStyle === 'minimal' ? 'border-bottom shadow-sm' : '';

$sections = $page['sections'] ?? [];
$previewProducts = $previewProducts ?? [];

if (!function_exists('sb_preview_escape')) {
    function sb_preview_escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    function sb_preview_safe_url(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || strpos($value, '#') === 0) {
            return $value;
        }

        $normalized = strtolower($value);
        if (strpos($normalized, 'javascript:') === 0 || strpos($normalized, 'vbscript:') === 0 || strpos($normalized, 'data:') === 0) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value)) {
            return preg_match('#^(https?:|mailto:|tel:)#i', $value) ? $value : null;
        }

        return $value;
    }

    function sb_preview_sanitize_html(string $html): string
    {
        return SafeHtml::sanitizeFragment(
            $html,
            [
                'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'figcaption', 'figure',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'ol', 'p',
                'pre', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td',
                'th', 'thead', 'tr', 'u', 'ul'
            ],
            [
                '*' => ['class', 'title'],
                'a' => ['href', 'target', 'rel'],
                'img' => ['src', 'alt', 'width', 'height', 'title'],
            ]
        );
    }

    function sb_preview_image_url(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '/loja/assets/images/placeholder.svg';
        }

        return $path;
    }

    function sb_preview_price($price): string
    {
        return 'R$ ' . number_format((float) $price, 2, ',', '.');
    }

    function sb_preview_render_product_cards(array $products, int $columns, string $primary): string
    {
        $columns = max(1, min(4, $columns));
        $items = array_slice($products, 0, max($columns * 2, 4));

        if (empty($items)) {
            return '<div class="text-muted text-center py-3">Nenhum produto encontrado para o preview.</div>';
        }

        ob_start();
        ?>
        <div class="row row-cols-1 row-cols-md-<?= $columns ?> g-4">
            <?php foreach ($items as $product): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm product-card">
                        <img src="<?= sb_preview_escape(sb_preview_image_url($product['main_image_path'] ?? '')) ?>"
                             class="card-img-top"
                             style="height:180px;object-fit:cover;"
                             alt="<?= sb_preview_escape($product['name'] ?? 'Produto') ?>">
                        <div class="card-body">
                            <h6 class="card-title"><?= sb_preview_escape($product['name'] ?? 'Produto') ?></h6>
                            <span class="fw-bold" style="color: <?= sb_preview_escape($primary) ?>;">
                                <?= sb_preview_escape(sb_preview_price($product['price'] ?? 0)) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    function sb_preview_render_component(array $component, array $previewProducts, string $primary, string $secondary, string $accent): string
    {
        $type = $component['type'] ?? 'rich-text';
        $content = is_array($component['content'] ?? null) ? $component['content'] : [];
        $gridCol = max(1, min(12, (int) ($component['grid_col'] ?? 12)));

        ob_start();
        ?>
        <div class="col-12 col-md-<?= $gridCol ?>">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <?php if ($type === 'rich-text'): ?>
                        <?= sb_preview_sanitize_html((string) ($content['html'] ?? $content['text'] ?? '<p>Texto rico</p>')) ?: '<p class="text-muted mb-0">Texto rico</p>' ?>

                    <?php elseif ($type === 'image'): ?>
                        <?php $imageUrl = sb_preview_safe_url($content['src'] ?? $content['image_url'] ?? '') ?: '/loja/assets/images/placeholder.svg'; ?>
                        <?php $linkUrl = sb_preview_safe_url($content['link_url'] ?? ''); ?>
                        <?php if ($linkUrl): ?>
                            <a href="<?= sb_preview_escape($linkUrl) ?>" class="d-block text-decoration-none">
                                <img src="<?= sb_preview_escape($imageUrl) ?>" class="img-fluid rounded w-100" alt="<?= sb_preview_escape($content['alt'] ?? 'Imagem') ?>">
                            </a>
                        <?php else: ?>
                            <img src="<?= sb_preview_escape($imageUrl) ?>" class="img-fluid rounded w-100" alt="<?= sb_preview_escape($content['alt'] ?? 'Imagem') ?>">
                        <?php endif; ?>

                    <?php elseif ($type === 'button'): ?>
                        <?php $buttonUrl = sb_preview_safe_url($content['url'] ?? $content['href'] ?? '#') ?? '#'; ?>
                        <a href="<?= sb_preview_escape($buttonUrl) ?>"
                           class="btn w-100"
                           style="background-color: <?= sb_preview_escape($accent) ?>; color: #fff; border-color: <?= sb_preview_escape($accent) ?>;">
                            <?= sb_preview_escape($content['label'] ?? $content['text'] ?? 'Botão') ?>
                        </a>

                    <?php elseif ($type === 'spacer'): ?>
                        <div style="height: <?= max(16, min(240, (int) ($content['height'] ?? 40))) ?>px;"></div>

                    <?php elseif ($type === 'divider'): ?>
                        <hr class="my-2">

                    <?php elseif ($type === 'custom-html'): ?>
                        <?= sb_preview_sanitize_html((string) ($content['html'] ?? $content['content'] ?? '')) ?: '<p class="text-muted mb-0">HTML customizado</p>' ?>

                    <?php elseif ($type === 'product-grid' || $type === 'product-carousel'): ?>
                        <div class="mb-2 d-flex align-items-center justify-content-between">
                            <strong><?= $type === 'product-carousel' ? 'Carrossel de Produtos' : 'Grid de Produtos' ?></strong>
                            <span class="badge rounded-pill" style="background-color: <?= sb_preview_escape($secondary) ?>;">Preview</span>
                        </div>
                        <?= sb_preview_render_product_cards($previewProducts, (int) ($content['columns'] ?? 3), $primary) ?>

                    <?php else: ?>
                        <div class="text-muted small">Componente <?= sb_preview_escape($type) ?> ainda não possui renderer visual.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    function sb_preview_render_section_components(array $section, array $previewProducts, string $primary, string $secondary, string $accent): string
    {
        $components = $section['components'] ?? [];
        if (empty($components)) {
            return '';
        }

        ob_start();
        ?>
        <section class="py-4 section-components">
            <div class="container">
                <div class="row g-3">
                    <?php foreach ($components as $component): ?>
                        <?= sb_preview_render_component($component, $previewProducts, $primary, $secondary, $accent) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}

$headerLogoUrl = sb_preview_safe_url($themeSettings['header_logo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title'] ?? 'Preview') ?> — <?= $shopName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=<?= sb_preview_escape($fontQuery) ?>&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/loja/assets/css/theme.css">
    <style>
        :root {
            --primary-color: <?= $primary ?>;
            --secondary-color: <?= $secondary ?>;
            --accent-color: <?= $accent ?>;
            --body-font: '<?= $bodyFont ?>', sans-serif;
            --heading-font: '<?= $headingFont ?>', sans-serif;
        }
        body { font-family: var(--body-font); margin: 0; }
        h1, h2, h3, h4, h5, h6 { font-family: var(--heading-font); }
        .btn-primary,
        .bg-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        .text-primary { color: var(--primary-color) !important; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="store-header <?= $headerSticky ? 'sticky-top' : '' ?> <?= $headerClassName ?>" style="background-color: <?= $headerBg ?>; color: <?= $headerText ?>;">
        <nav class="navbar navbar-expand-lg">
            <div class="container flex-wrap">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 <?= $headerBrandClass ?>" href="#" style="color: <?= $headerText ?>;">
                    <?php if ($headerLogoUrl): ?>
                        <img src="<?= sb_preview_escape($headerLogoUrl) ?>" alt="<?= $shopName ?>" style="max-height:42px; max-width:120px; object-fit:contain;">
                    <?php endif; ?>
                    <strong><?= $shopName ?></strong>
                </a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav <?= $headerNavListClass ?>">
                        <li class="nav-item"><a class="nav-link" href="#" style="color: <?= $headerText ?>;">Início</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" style="color: <?= $headerText ?>;">Produtos</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" style="color: <?= $headerText ?>;">Contato</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Content Sections -->
    <main>
        <?php if (empty($sections)): ?>
            <section class="py-5 text-center">
                <div class="container">
                    <div class="text-muted">
                        <i class="fas fa-paint-brush fa-3x mb-3 d-block" style="opacity:0.3"></i>
                        <h4>Nenhuma seção configurada</h4>
                        <p>Use o editor ao lado para adicionar seções e componentes.</p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <?php foreach ($sections as $section):
                if (!(int) ($section['is_visible'] ?? 1)) {
                    continue;
                }
                $sSettings = $section['settings'] ?? [];
                $sType = $section['type'] ?? 'custom-html';
                $sectionComponentsHtml = sb_preview_render_section_components($section, $previewProducts, $primary, $secondary, $accent);
            ?>
                <?php if ($sType === 'hero-banner'): ?>
                    <section class="hero-banner position-relative d-flex align-items-center"
                             style="background: linear-gradient(135deg, <?= $primary ?>, <?= $secondary ?>); min-height: <?= htmlspecialchars($sSettings['min_height'] ?? '400px') ?>;">
                        <div class="container text-center text-white">
                            <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($sSettings['title'] ?? 'Bem-vindo') ?></h1>
                            <p class="lead mb-4"><?= htmlspecialchars($sSettings['subtitle'] ?? 'Encontre os melhores produtos') ?></p>
                            <?php if (!empty($sSettings['cta_text'])): ?>
                                <a href="<?= htmlspecialchars($sSettings['cta_url'] ?? '#') ?>" class="btn btn-light btn-lg">
                                    <?= htmlspecialchars($sSettings['cta_text']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php elseif ($sType === 'featured-products'): ?>
                    <section class="py-5">
                        <div class="container">
                            <h2 class="text-center mb-4"><?= htmlspecialchars($sSettings['title'] ?? 'Produtos em Destaque') ?></h2>
                            <?= sb_preview_render_product_cards($previewProducts, (int) ($sSettings['columns'] ?? 3), $primary) ?>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php elseif ($sType === 'image-with-text'): ?>
                    <section class="py-5">
                        <div class="container">
                            <div class="row align-items-center g-4">
                                <div class="col-md-6">
                                    <img src="/loja/assets/images/placeholder.svg" class="img-fluid rounded" alt="">
                                </div>
                                <div class="col-md-6">
                                    <h2><?= htmlspecialchars($sSettings['title'] ?? 'Sobre Nós') ?></h2>
                                    <p class="text-muted"><?= htmlspecialchars($sSettings['text'] ?? 'Conheça nossa história e valores.') ?></p>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php elseif ($sType === 'newsletter'): ?>
                    <section class="py-5" style="background-color: #f8f9fa;">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-md-6 text-center">
                                    <h2 class="mb-3"><?= htmlspecialchars($sSettings['title'] ?? 'Fique por dentro') ?></h2>
                                    <p class="text-muted mb-4"><?= htmlspecialchars($sSettings['description'] ?? 'Receba novidades e promoções.') ?></p>
                                    <div class="d-flex gap-2">
                                        <input type="email" class="form-control" placeholder="Seu e-mail">
                                        <button class="btn btn-primary text-nowrap"><?= htmlspecialchars($sSettings['button_text'] ?? 'Inscrever') ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php elseif ($sType === 'testimonials'): ?>
                    <section class="py-5">
                        <div class="container">
                            <h2 class="text-center mb-4"><?= htmlspecialchars($sSettings['title'] ?? 'Depoimentos') ?></h2>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                <div class="col">
                                    <div class="card h-100 border-0 shadow-sm text-center p-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width:50px;height:50px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <p class="text-muted small">"Excelente loja, produtos de alta qualidade!"</p>
                                        <strong class="small">Cliente <?= $i ?></strong>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php elseif ($sType === 'gallery'): ?>
                    <section class="py-5">
                        <div class="container">
                            <h2 class="text-center mb-4"><?= htmlspecialchars($sSettings['title'] ?? 'Galeria') ?></h2>
                            <div class="row row-cols-2 row-cols-md-<?= (int) ($sSettings['columns'] ?? 3) ?> g-3">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <div class="col">
                                    <img src="/loja/assets/images/placeholder.svg" class="img-fluid rounded" alt="">
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php elseif ($sType === 'custom-html'): ?>
                    <section class="py-4">
                        <div class="container">
                            <?= sb_preview_sanitize_html((string) ($sSettings['content'] ?? '')) ?: '<p class="text-muted">Conteúdo HTML customizado</p>' ?>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>

                <?php else: ?>
                    <section class="py-4">
                        <div class="container">
                            <div class="alert alert-light border mb-0">
                                Seção <?= sb_preview_escape($sType) ?> carregada em modo genérico.
                            </div>
                        </div>
                    </section>
                    <?= $sectionComponentsHtml ?>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="mt-auto" style="background-color: <?= $footerBg ?>; color: <?= $footerText ?>;">
        <div class="container <?= $footerPaddingClass ?>">
            <div class="row row-cols-1 row-cols-md-<?= $footerCols ?> g-4">
                <div class="col">
                    <h5 style="color: <?= $footerText ?>;"><?= $shopName ?></h5>
                    <p class="small" style="opacity: 0.8;">Sua loja online.</p>
                </div>
                <?php if ($footerCols >= 2): ?>
                <div class="col">
                    <h5 style="color: <?= $footerText ?>;">Links</h5>
                    <ul class="list-unstyled small" style="opacity: 0.8;">
                        <li class="mb-1"><a href="#" class="text-decoration-none" style="color: <?= $footerText ?>;">Início</a></li>
                        <li class="mb-1"><a href="#" class="text-decoration-none" style="color: <?= $footerText ?>;">Produtos</a></li>
                        <li class="mb-1"><a href="#" class="text-decoration-none" style="color: <?= $footerText ?>;">Contato</a></li>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($footerCols >= 3): ?>
                <div class="col">
                    <h5 style="color: <?= $footerText ?>;">Contato</h5>
                    <p class="small" style="opacity: 0.8;">
                        <i class="fas fa-envelope me-1"></i>contato@loja.com
                    </p>
                </div>
                <?php endif; ?>
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
