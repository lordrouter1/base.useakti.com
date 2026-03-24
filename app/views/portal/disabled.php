<?php
/**
 * Portal do Cliente — Portal Desabilitado
 */
$companyName = $company['company_name'] ?? 'Akti';
?>
<div class="portal-auth-container">
    <div class="portal-auth-card text-center">
        <div class="portal-auth-header">
            <div class="portal-auth-icon text-muted">
                <i class="fas fa-lock fa-3x"></i>
            </div>
            <h1 class="portal-auth-title mt-3"><?= e($companyName) ?></h1>
            <p class="text-muted mt-2">
                O portal do cliente está temporariamente indisponível.<br>
                Entre em contato com a empresa para mais informações.
            </p>
        </div>
    </div>
</div>
