<?php
/**
 * Section Header Component
 *
 * Renders a section title with an icon circle and optional badge/counter.
 * Fully compatible with dark mode via design-system.css utility classes.
 *
 * Usage:
 *   $sectionHeader = [
 *       'icon'  => 'fas fa-users',
 *       'color' => 'blue',
 *       'title' => 'Clientes',
 *   ];
 *   require 'app/views/components/section-header.php';
 *
 * With badge:
 *   $sectionHeader = [
 *       'icon'  => 'fas fa-list',
 *       'color' => 'green',
 *       'title' => 'Listagem de Produtos',
 *       'badge' => ['text' => '42 itens', 'color' => 'success'],
 *       'subtitle' => 'Mostrando todos os produtos ativos',
 *   ];
 *   require 'app/views/components/section-header.php';
 *
 * Parameters:
 *   @param string $sectionHeader['icon']               Font Awesome icon class
 *   @param string $sectionHeader['color']              Color variant (blue, green, etc.)
 *   @param string $sectionHeader['title']              Section title text
 *   @param string $sectionHeader['subtitle']           (optional) Subtitle/description
 *   @param array  $sectionHeader['badge']              (optional) Badge config ['text' => '...', 'color' => 'success|danger|...']
 *   @param string $sectionHeader['size']               (optional) Icon circle size (sm|lg|xl)
 *   @param string $sectionHeader['class']              (optional) Extra CSS classes for wrapper
 *   @param string $sectionHeader['titleSize']          (optional) Title font size (e.g., 'fs-5', 'fs-6')
 *
 * @package Akti\Components
 */

if (empty($sectionHeader) || empty($sectionHeader['title'])) {
    return;
}

$icon      = $sectionHeader['icon'] ?? 'fas fa-circle';
$color     = $sectionHeader['color'] ?? 'blue';
$title     = $sectionHeader['title'];
$subtitle  = $sectionHeader['subtitle'] ?? '';
$badge     = $sectionHeader['badge'] ?? null;
$size      = $sectionHeader['size'] ?? '';
$extraClass = $sectionHeader['class'] ?? '';
$titleSize = $sectionHeader['titleSize'] ?? 'fs-6';

// Build icon circle size class
$sizeClass = '';
if ($size && $size !== 'md') {
    $sizeClass = 'icon-circle-' . $size;
}

$iconClasses = trim("icon-circle icon-circle-$color $sizeClass me-2");
?>
<div class="d-flex align-items-center mb-3 <?= htmlspecialchars($extraClass) ?>">
    <div class="<?= $iconClasses ?>">
        <i class="<?= htmlspecialchars($icon) ?> text-<?= htmlspecialchars($color) ?>" style="font-size:.85rem;"></i>
    </div>
    <div>
        <h5 class="mb-0 fw-bold <?= htmlspecialchars($titleSize) ?>">
            <?= htmlspecialchars($title) ?>
            <?php if ($badge): ?>
                <span class="badge badge-<?= htmlspecialchars($badge['color'] ?? 'primary') ?>-light ms-2" style="font-size:.7rem;">
                    <?= htmlspecialchars($badge['text'] ?? '') ?>
                </span>
            <?php endif; ?>
        </h5>
        <?php if ($subtitle): ?>
            <small class="text-muted"><?= htmlspecialchars($subtitle) ?></small>
        <?php endif; ?>
    </div>
</div>
