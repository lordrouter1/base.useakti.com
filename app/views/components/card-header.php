<?php
/**
 * Card Header Component
 *
 * Renders a themed card header with gradient background and optional icon.
 * Uses card-header-* utility classes from design-system.css.
 *
 * Usage (light variant):
 *   $cardHeader = [
 *       'title' => 'Detalhes do Pedido',
 *       'color' => 'blue',
 *       'icon'  => 'fas fa-file-invoice',
 *   ];
 *   require 'app/views/components/card-header.php';
 *
 * Usage (solid/NF-e variant):
 *   $cardHeader = [
 *       'title' => 'Nota Fiscal',
 *       'color' => 'nfe-dark',
 *       'icon'  => 'fas fa-file-invoice-dollar',
 *       'solid' => true,
 *   ];
 *   require 'app/views/components/card-header.php';
 *
 * Parameters:
 *   @param string $cardHeader['title']     Header title text
 *   @param string $cardHeader['color']     Color variant
 *   @param string $cardHeader['icon']      (optional) Font Awesome icon class
 *   @param bool   $cardHeader['solid']     (optional) Use solid card-header-nfe-* style (white text)
 *   @param string $cardHeader['class']     (optional) Extra CSS classes
 *   @param string $cardHeader['subtitle']  (optional) Subtitle text
 *   @param string $cardHeader['badge']     (optional) Badge HTML to append
 *   @param string $cardHeader['rightHtml'] (optional) HTML content for the right side
 *
 * Light colors: info-light, blue-light, orange-light, purple-light, green-light,
 *               grape-light, carrot-light, navy-light, emerald-light, gray-light, danger-light
 *
 * Solid colors: nfe-dark, nfe-orange, nfe-green, nfe-blue, nfe-purple, nfe-danger, green-alt
 *
 * @package Akti\Components
 */

if (empty($cardHeader) || empty($cardHeader['title'])) {
    return;
}

$title    = $cardHeader['title'];
$color    = $cardHeader['color'] ?? 'blue-light';
$icon     = $cardHeader['icon'] ?? '';
$solid    = $cardHeader['solid'] ?? false;
$extra    = $cardHeader['class'] ?? '';
$subtitle = $cardHeader['subtitle'] ?? '';
$badge    = $cardHeader['badge'] ?? '';
$rightHtml = $cardHeader['rightHtml'] ?? '';

// Determine header class
if ($solid) {
    $headerClass = "card-header card-header-$color";
} else {
    // Ensure -light suffix
    $colorClass = (strpos($color, '-light') !== false) ? $color : "$color-light";
    $headerClass = "card-header card-header-$colorClass";
}

$classes = trim("$headerClass $extra");
?>
<div class="<?= $classes ?>">
    <div class="d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            <?php if ($icon): ?>
                <i class="<?= htmlspecialchars($icon) ?> me-2"></i>
            <?php endif; ?>
            <?= htmlspecialchars($title) ?>
            <?php if ($badge): ?>
                <?= $badge ?>
            <?php endif; ?>
        </h5>
        <?php if ($subtitle): ?>
            <small class="opacity-75"><?= htmlspecialchars($subtitle) ?></small>
        <?php endif; ?>
        <?php if ($rightHtml): ?>
            <?= $rightHtml ?>
        <?php endif; ?>
    </div>
</div>
