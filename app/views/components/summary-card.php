<?php
/**
 * Summary Card Component
 *
 * Renders a KPI/summary card with icon circle, value, and label.
 * Used in dashboard, financial, stock, and commissions modules.
 * Fully compatible with dark mode via design-system.css utility classes.
 *
 * Usage:
 *   $summaryCard = [
 *       'icon'  => 'fas fa-dollar-sign',
 *       'color' => 'primary',
 *       'value' => 'R$ 12.500,00',
 *       'label' => 'Receita Total',
 *   ];
 *   require 'app/views/components/summary-card.php';
 *
 * With trend indicator:
 *   $summaryCard = [
 *       'icon'    => 'fas fa-chart-line',
 *       'color'   => 'green',
 *       'value'   => '87%',
 *       'label'   => 'Taxa de Aprovação',
 *       'trend'   => '+5.2%',
 *       'trendUp' => true,
 *       'size'    => 'xxl',
 *   ];
 *   require 'app/views/components/summary-card.php';
 *
 * Parameters:
 *   @param string $summaryCard['icon']    Font Awesome icon class
 *   @param string $summaryCard['color']   Color variant (primary, green, warning, danger, info, etc.)
 *   @param string $summaryCard['value']   Display value (formatted string)
 *   @param string $summaryCard['label']   Card label/description
 *   @param string $summaryCard['trend']   (optional) Trend text (e.g., '+5.2%')
 *   @param bool   $summaryCard['trendUp'] (optional) true = green up arrow, false = red down arrow
 *   @param string $summaryCard['size']    (optional) Icon circle size (lg, xl, xxl)
 *   @param string $summaryCard['class']   (optional) Extra CSS classes for the card
 *   @param string $summaryCard['id']      (optional) HTML id attribute for the value span
 *
 * @package Akti\Components
 */

if (empty($summaryCard)) {
    return;
}

$icon     = $summaryCard['icon'] ?? 'fas fa-chart-bar';
$color    = $summaryCard['color'] ?? 'primary';
$value    = $summaryCard['value'] ?? '—';
$label    = $summaryCard['label'] ?? '';
$trend    = $summaryCard['trend'] ?? '';
$trendUp  = $summaryCard['trendUp'] ?? true;
$size     = $summaryCard['size'] ?? 'xxl';
$extra    = $summaryCard['class'] ?? '';
$id       = $summaryCard['id'] ?? '';

$sizeClass = $size ? "icon-circle-$size" : '';
$iconClasses = trim("icon-circle $sizeClass icon-circle-$color me-3");
$idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
?>
<div class="col">
    <div class="card border-0 shadow-sm h-100 <?= htmlspecialchars($extra) ?>">
        <div class="card-body d-flex align-items-center p-3">
            <div class="<?= $iconClasses ?>">
                <i class="<?= htmlspecialchars($icon) ?> fa-lg text-<?= htmlspecialchars($color) ?>"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="mb-0 fw-bold" style="font-size:1.4rem;"<?= $idAttr ?>><?= htmlspecialchars($value) ?></h3>
                <small class="text-muted"><?= htmlspecialchars($label) ?></small>
                <?php if ($trend): ?>
                    <div class="mt-1">
                        <small class="<?= $trendUp ? 'text-success' : 'text-danger' ?>">
                            <i class="fas fa-arrow-<?= $trendUp ? 'up' : 'down' ?> me-1"></i><?= htmlspecialchars($trend) ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
