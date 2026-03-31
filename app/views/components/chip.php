<?php
/**
 * Status Chip Component
 *
 * Renders a themed status chip/pill with automatic dark mode support.
 * Uses chip-* utility classes from design-system.css.
 *
 * Usage:
 *   $chip = ['status' => 'approved'];
 *   require 'app/views/components/chip.php';
 *
 * Custom text:
 *   $chip = ['status' => 'pending', 'text' => 'Aguardando Aprovação'];
 *   require 'app/views/components/chip.php';
 *
 * Parameters:
 *   @param string $chip['status'] Status key: approved|pending|rejected|active|inactive|completed|cancelled|overdue
 *   @param string $chip['text']   (optional) Override display text
 *   @param string $chip['icon']   (optional) Override icon class
 *   @param string $chip['class']  (optional) Extra CSS classes
 *
 * Status mapping:
 *   approved   → green chip with check icon
 *   pending    → yellow chip with hourglass icon
 *   rejected   → red chip with times icon
 *   active     → green chip with circle icon
 *   inactive   → gray chip with minus icon
 *   completed  → green chip with check-double icon
 *   cancelled  → red chip with ban icon
 *   overdue    → red chip with exclamation icon
 *
 * @package Akti\Components
 */

if (empty($chip) || empty($chip['status'])) {
    return;
}

$status = $chip['status'];
$extra  = $chip['class'] ?? '';

// Status configuration map
$statusMap = [
    'approved'  => ['class' => 'chip-approved',  'icon' => 'fas fa-check',              'text' => 'Aprovado'],
    'aprovado'  => ['class' => 'chip-approved',  'icon' => 'fas fa-check',              'text' => 'Aprovado'],
    'pending'   => ['class' => 'chip-pending',   'icon' => 'fas fa-hourglass-half',     'text' => 'Pendente'],
    'pendente'  => ['class' => 'chip-pending',   'icon' => 'fas fa-hourglass-half',     'text' => 'Pendente'],
    'rejected'  => ['class' => 'chip-rejected',  'icon' => 'fas fa-times',              'text' => 'Rejeitado'],
    'rejeitado' => ['class' => 'chip-rejected',  'icon' => 'fas fa-times',              'text' => 'Rejeitado'],
    'active'    => ['class' => 'chip-approved',  'icon' => 'fas fa-circle',             'text' => 'Ativo'],
    'ativo'     => ['class' => 'chip-approved',  'icon' => 'fas fa-circle',             'text' => 'Ativo'],
    'inactive'  => ['class' => 'chip-pending',   'icon' => 'fas fa-minus-circle',       'text' => 'Inativo'],
    'inativo'   => ['class' => 'chip-pending',   'icon' => 'fas fa-minus-circle',       'text' => 'Inativo'],
    'completed' => ['class' => 'chip-approved',  'icon' => 'fas fa-check-double',       'text' => 'Concluído'],
    'concluido' => ['class' => 'chip-approved',  'icon' => 'fas fa-check-double',       'text' => 'Concluído'],
    'cancelled' => ['class' => 'chip-rejected',  'icon' => 'fas fa-ban',                'text' => 'Cancelado'],
    'cancelado' => ['class' => 'chip-rejected',  'icon' => 'fas fa-ban',                'text' => 'Cancelado'],
    'overdue'   => ['class' => 'chip-rejected',  'icon' => 'fas fa-exclamation-circle', 'text' => 'Em atraso'],
    'atrasado'  => ['class' => 'chip-rejected',  'icon' => 'fas fa-exclamation-circle', 'text' => 'Em atraso'],
];

$config  = $statusMap[$status] ?? ['class' => 'chip-pending', 'icon' => 'fas fa-question-circle', 'text' => ucfirst($status)];
$text    = $chip['text'] ?? $config['text'];
$icon    = $chip['icon'] ?? $config['icon'];
$classes = trim($config['class'] . ' badge rounded-pill ' . $extra);
?>
<span class="<?= $classes ?>">
    <i class="<?= htmlspecialchars($icon) ?> me-1"></i><?= htmlspecialchars($text) ?>
</span>
