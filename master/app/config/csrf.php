<?php
/**
 * Akti Master — CSRF Protection Helpers
 *
 * Gera e valida tokens CSRF para proteger formulários POST.
 */

/**
 * Gera um token CSRF e armazena na sessão.
 */
function master_csrf_token(): string
{
    if (empty($_SESSION['_master_csrf_token'])) {
        $_SESSION['_master_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_master_csrf_token'];
}

/**
 * Retorna um campo hidden HTML com o token CSRF.
 */
function master_csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(master_csrf_token()) . '">';
}

/**
 * Retorna a meta tag com o token CSRF (para AJAX).
 */
function master_csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . htmlspecialchars(master_csrf_token()) . '">';
}

/**
 * Valida o token CSRF de um request POST.
 * Retorna true se válido, false se inválido.
 */
function master_csrf_validate(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Não valida GET requests
    }

    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['_master_csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Valida CSRF e aborta com erro se inválido.
 */
function master_csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !master_csrf_validate()) {
        $_SESSION['error'] = 'Token de segurança inválido. Por favor, tente novamente.';
        $referer = $_SERVER['HTTP_REFERER'] ?? '?page=dashboard';
        header('Location: ' . $referer);
        exit;
    }
}
