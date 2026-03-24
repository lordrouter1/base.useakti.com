<?php
/**
 * Portal do Cliente — Header Auth (Login/Register)
 * Layout simples para páginas de autenticação (sem bottom nav).
 *
 * Variáveis esperadas: $company (array de configurações da empresa)
 */
$companyName = $company['company_name'] ?? 'Akti';
$companyLogo = $company['company_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= eAttr(\Akti\Services\PortalLang::getLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= e($companyName) ?> — <?= __p('portal_title') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= eAttr($companyName) ?>">
    <?= csrf_meta() ?>

    <!-- Manifest PWA -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/logos/akti-icon-dark.svg">
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Portal CSS -->
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body class="portal-body portal-auth-body">
