<?php
/**
 * Helpers de formulário — Akti
 *
 * Funções utilitárias para uso direto em views (sem namespace).
 * Carregado automaticamente pelo autoload.php.
 *
 * @see Akti\Core\Security — Classe de segurança CSRF
 * @see PROJECT_RULES.md — Módulo: Segurança — Proteção CSRF
 */

/**
 * Gera o campo hidden do token CSRF para uso em formulários.
 *
 * Exemplo de uso na view:
 *   <form method="POST">
 *       <?= csrf_field() ?>
 *       ...
 *   </form>
 *
 * Saída:
 *   <input type="hidden" name="csrf_token" value="TOKEN_AQUI">
 *
 * @return string HTML do campo hidden com token CSRF
 */
function csrf_field(): string
{
    $token = \Akti\Core\Security::generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Gera a meta tag do token CSRF para uso no <head>.
 * Útil para enviar o token via header em requisições AJAX.
 *
 * Exemplo de uso no header.php:
 *   <?= csrf_meta() ?>
 *
 * Saída:
 *   <meta name="csrf-token" content="TOKEN_AQUI">
 *
 * @return string HTML da meta tag com token CSRF
 */
function csrf_meta(): string
{
    $token = \Akti\Core\Security::generateCsrfToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Retorna apenas o valor do token CSRF (sem HTML).
 * Útil para injeção em scripts inline ou atributos data-*.
 *
 * @return string Token CSRF puro
 */
function csrf_token(): string
{
    return \Akti\Core\Security::generateCsrfToken();
}
