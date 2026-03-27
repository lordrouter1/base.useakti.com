<?php
/**
 * NF-e Alerts Banner — Fase 6.5
 * Componente reutilizável de alertas fiscais para o módulo NF-e.
 * 
 * Inclusão: include __DIR__ . '/partials/alerts_banner.php';
 * 
 * Variáveis esperadas (todas opcionais):
 * @var array  $alerts         Array de alertas [{severity, title, message}]
 * @var array  $validation     Resultado de validação de credenciais
 * @var bool   $certExpired    Certificado expirado
 * @var bool   $certExpiringSoon Certificado expirando em breve
 * @var int    $queuePending   Itens na fila de emissão
 * @var int    $receivedPending Docs recebidos pendentes de manifestação
 */

$alerts = $alerts ?? [];
$validation = $validation ?? ['valid' => true, 'missing' => []];
$certExpired = $certExpired ?? false;
$certExpiringSoon = $certExpiringSoon ?? false;
$queuePending = $queuePending ?? 0;
$receivedPending = $receivedPending ?? 0;
?>

<?php if (!empty($alerts) || !$validation['valid'] || $certExpired || $certExpiringSoon || $queuePending > 0 || $receivedPending > 0): ?>
<div class="nfe-alerts-container mb-3">

    <?php if ($certExpired): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-2" style="font-size:0.85rem;">
        <i class="fas fa-times-circle fs-5"></i>
        <div>
            <strong>Certificado Digital Expirado!</strong>
            <a href="?page=nfe_documents&sec=credenciais" class="alert-link">Atualize o certificado</a> para continuar emitindo NF-e.
        </div>
    </div>
    <?php elseif ($certExpiringSoon): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-2" style="font-size:0.85rem;">
        <i class="fas fa-clock fs-5"></i>
        <div>
            <strong>Certificado expirando em breve!</strong>
            <a href="?page=nfe_documents&sec=credenciais" class="alert-link">Renove o certificado</a> antes do vencimento.
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$validation['valid']): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-2" style="font-size:0.85rem;">
        <i class="fas fa-exclamation-triangle fs-5"></i>
        <div>
            <strong>Credenciais incompletas.</strong>
            <a href="?page=nfe_documents&sec=credenciais" class="alert-link">Configure as credenciais SEFAZ</a> antes de emitir NF-e.
        </div>
    </div>
    <?php endif; ?>

    <?php if ($queuePending > 0): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-2" style="font-size:0.85rem;">
        <i class="fas fa-clock fs-5"></i>
        <div>
            <strong><?= $queuePending ?> NF-e</strong> na fila de emissão.
            <a href="?page=nfe_documents&sec=fila" class="alert-link">Ver fila</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($receivedPending > 0): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-2" style="font-size:0.85rem;">
        <i class="fas fa-inbox fs-5"></i>
        <div>
            <strong><?= $receivedPending ?> documento(s)</strong> recebido(s) pendente(s) de manifestação.
            <a href="?page=nfe_documents&sec=recebidos" class="alert-link">Ver documentos recebidos</a>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($alerts as $alert): ?>
    <div class="alert alert-<?= $alert['severity'] ?? 'info' ?> alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-2" style="font-size:0.85rem;">
        <i class="fas fa-<?= ($alert['severity'] ?? '') === 'danger' ? 'exclamation-circle' : (($alert['severity'] ?? '') === 'warning' ? 'exclamation-triangle' : 'info-circle') ?> fs-5"></i>
        <div>
            <strong><?= e($alert['title'] ?? '') ?>:</strong> <?= e($alert['message'] ?? '') ?>
        </div>
        <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert" style="font-size:0.6rem;"></button>
    </div>
    <?php endforeach; ?>

</div>
<?php endif; ?>
