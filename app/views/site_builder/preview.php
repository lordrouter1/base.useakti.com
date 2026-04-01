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

$headerBg    = htmlspecialchars($themeSettings['header_bg_color'] ?? '#ffffff');
$headerText  = htmlspecialchars($themeSettings['header_text_color'] ?? '#333333');
$headerSticky = ($themeSettings['header_sticky'] ?? '1') === '1';
$footerBg    = htmlspecialchars($themeSettings['footer_bg_color'] ?? '#2c3e50');
$footerText  = htmlspecialchars($themeSettings['footer_text_color'] ?? '#ffffff');
$footerCols  = (int) ($themeSettings['footer_columns'] ?? 3);
$primary     = htmlspecialchars($themeSettings['primary_color'] ?? '#3b82f6');
$secondary   = htmlspecialchars($themeSettings['secondary_color'] ?? '#64748b');
$bodyFont    = htmlspecialchars($themeSettings['body_font'] ?? 'Inter');
$shopName    = htmlspecialchars($_SESSION['company_name'] ?? 'Minha Loja');

$sections = $page['sections'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title'] ?? 'Preview') ?> — <?= $shopName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=<?= $bodyFont ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/loja/assets/css/theme.css">
    <style>
        :root {
            --primary-color: <?= $primary ?>;
            --secondary-color: <?= $secondary ?>;
            --body-font: '<?= $bodyFont ?>', sans-serif;
        }
        body { font-family: var(--body-font); margin: 0; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="store-header <?= $headerSticky ? 'sticky-top' : '' ?>" style="background-color: <?= $headerBg ?>; color: <?= $headerText ?>;">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="#" style="color: <?= $headerText ?>;">
                    <strong><?= $shopName ?></strong>
                </a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto">
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
                $sSettings = $section['settings'] ?? [];
                $sType = $section['type'] ?? 'custom-html';
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

                <?php elseif ($sType === 'featured-products'): ?>
                    <section class="py-5">
                        <div class="container">
                            <h2 class="text-center mb-4"><?= htmlspecialchars($sSettings['title'] ?? 'Produtos em Destaque') ?></h2>
                            <div class="row row-cols-1 row-cols-md-<?= (int) ($sSettings['columns'] ?? 3) ?> g-4">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <div class="col">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <img src="/loja/assets/images/placeholder.svg" class="card-img-top" style="height:180px;object-fit:cover;">
                                        <div class="card-body">
                                            <h6 class="card-title">Produto Exemplo <?= $i ?></h6>
                                            <span class="fw-bold" style="color: <?= $primary ?>;">R$ 99,90</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </section>

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

                <?php elseif ($sType === 'custom-html'): ?>
                    <section class="py-4">
                        <div class="container">
                            <?= htmlspecialchars($sSettings['content'] ?? 'Conteúdo HTML customizado', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </section>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="mt-auto" style="background-color: <?= $footerBg ?>; color: <?= $footerText ?>;">
        <div class="container py-5">
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
