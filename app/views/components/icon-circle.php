<?php
/**
 * Icon Circle Component
 *
 * Renders a themed icon inside a rounded circle with automatic dark mode support.
 * Uses utility classes from design-system.css.
 *
 * Usage:
 *   $iconCircle = ['icon' => 'fas fa-users', 'color' => 'blue'];
 *   require 'app/views/components/icon-circle.php';
 *
 * Or with size:
 *   $iconCircle = ['icon' => 'fas fa-chart-line', 'color' => 'green', 'size' => 'lg'];
 *   require 'app/views/components/icon-circle.php';
 *
 * Parameters:
 *   @param string $iconCircle['icon']     Font Awesome icon class (e.g., 'fas fa-users')
 *   @param string $iconCircle['color']    Color variant: blue|green|info|purple|grape|orange|carrot|red|mint|success|warning|primary|danger|teal|gray|crimson|navy
 *   @param string $iconCircle['size']     (optional) Size variant: sm|md(default)|lg|xl|xxl|48|80
 *   @param string $iconCircle['class']    (optional) Additional CSS classes
 *   @param string $iconCircle['textSize'] (optional) Font size override (e.g., '.85rem')
 *
 * Available colors: blue, green, info, purple, grape, orange, carrot, red, mint,
 *                   success, warning, primary, danger, teal, gray, crimson, navy
 *
 * Available sizes: sm (28px), default (34px), lg (42px), xl (44px), xxl (50px), 48 (48px), 80 (80px)
 *
 * @package Akti\Components
 */

if (empty($iconCircle) || empty($iconCircle['icon']) || empty($iconCircle['color'])) {
    return;
}

$icon      = $iconCircle['icon'];
$color     = $iconCircle['color'];
$size      = $iconCircle['size'] ?? '';
$extraClass = $iconCircle['class'] ?? '';
$textSize  = $iconCircle['textSize'] ?? '';

// Build size class
$sizeClass = '';
if ($size && $size !== 'md') {
    $sizeClass = 'icon-circle-' . $size;
}

// Build inline style for text size if specified
$style = $textSize ? ' style="font-size:' . htmlspecialchars($textSize) . ';"' : '';

// Build classes
$classes = trim("icon-circle icon-circle-$color $sizeClass $extraClass");
?>
<div class="<?= $classes ?>"<?= $style ?>>
    <i class="<?= htmlspecialchars($icon) ?> text-<?= htmlspecialchars($color) ?>"></i>
</div>
