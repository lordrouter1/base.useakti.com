<?php
/**
 * Badge Component
 *
 * Renders a themed badge with automatic dark mode support.
 * Uses badge-*-light utility classes from design-system.css.
 *
 * Usage:
 *   $badge = ['text' => '3 em atraso', 'color' => 'danger'];
 *   require 'app/views/components/badge.php';
 *
 * With icon:
 *   $badge = ['text' => 'Aprovado', 'color' => 'success', 'icon' => 'fas fa-check'];
 *   require 'app/views/components/badge.php';
 *
 * Solid variant (Bootstrap bg-*):
 *   $badge = ['text' => 'Novo', 'color' => 'primary', 'solid' => true];
 *   require 'app/views/components/badge.php';
 *
 * Parameters:
 *   @param string $badge['text']   Badge text
 *   @param string $badge['color']  Color variant: danger|success|blue|info|orange|purple|teal|grape|green|carrot|warning|primary
 *   @param string $badge['icon']   (optional) Font Awesome icon class
 *   @param bool   $badge['solid']  (optional) Use solid Bootstrap bg-* instead of light variant
 *   @param bool   $badge['pill']   (optional) Use rounded-pill shape
 *   @param string $badge['class']  (optional) Extra CSS classes
 *   @param string $badge['size']   (optional) Font size override (e.g., '0.7rem')
 *
 * @package Akti\Components
 */

if (empty($badge) || !isset($badge['text'])) {
    return;
}

$text   = $badge['text'];
$color  = $badge['color'] ?? 'primary';
$icon   = $badge['icon'] ?? '';
$solid  = $badge['solid'] ?? false;
$pill   = $badge['pill'] ?? false;
$extra  = $badge['class'] ?? '';
$size   = $badge['size'] ?? '';

// Determine badge class
if ($solid) {
    $badgeClass = "badge bg-$color";
} else {
    $badgeClass = "badge badge-$color-light";
}

if ($pill) {
    $badgeClass .= ' rounded-pill';
}

$classes = trim("$badgeClass $extra");
$style = $size ? ' style="font-size:' . htmlspecialchars($size) . ';"' : '';
?>
<span class="<?= $classes ?>"<?= $style ?>>
    <?php if ($icon): ?>
        <i class="<?= htmlspecialchars($icon) ?> me-1"></i>
    <?php endif; ?>
    <?= htmlspecialchars($text) ?>
</span>
