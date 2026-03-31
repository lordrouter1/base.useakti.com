<?php
/**
 * Empty State Component
 * 
 * Renders a friendly empty state with illustration, message, and CTA.
 *
 * Usage:
 *   $emptyState = [
 *       'icon'    => 'no-customers',     // SVG name from assets/img/empty/
 *       'title'   => 'Nenhum cliente cadastrado',
 *       'message' => 'Comece adicionando seu primeiro cliente.',
 *       'action'  => '?page=customers&action=create',
 *       'actionLabel' => 'Adicionar Cliente',
 *       'actionIcon'  => 'fas fa-plus',
 *   ];
 *   require 'app/views/components/empty-state.php';
 */

if (empty($emptyState)) return;

$svgFile = 'assets/img/empty/' . ($emptyState['icon'] ?? 'no-results') . '.svg';
$title   = $emptyState['title'] ?? 'Nenhum resultado encontrado';
$message = $emptyState['message'] ?? '';
$action  = $emptyState['action'] ?? '';
$actionLabel = $emptyState['actionLabel'] ?? 'Começar';
$actionIcon  = $emptyState['actionIcon'] ?? 'fas fa-plus';
?>

<div class="akti-empty-state">
    <div class="akti-empty-state-icon">
        <?php if (file_exists($svgFile)): ?>
            <img src="<?= e($svgFile) ?>" alt="<?= e($title) ?>" style="width:100%;height:100%;">
        <?php else: ?>
            <i class="fas fa-inbox" style="font-size:3rem;color:var(--text-muted);"></i>
        <?php endif; ?>
    </div>
    <h3><?= e($title) ?></h3>
    <?php if ($message): ?>
    <p><?= e($message) ?></p>
    <?php endif; ?>
    <?php if ($action): ?>
    <a href="<?= e($action) ?>" class="akti-btn akti-btn-primary" data-shortcut="new">
        <i class="<?= e($actionIcon) ?>"></i>
        <?= e($actionLabel) ?>
    </a>
    <?php endif; ?>
</div>
