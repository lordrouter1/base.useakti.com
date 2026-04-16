<?php
/**
 * Escape Helpers — Akti
 *
 * Funções globais de escape para uso direto em views (sem namespace).
 * Carregado automaticamente pelo autoload.php.
 *
 * Uso em views:
 *   <span><?= e($name) ?></span>
 *   <input value="<?= eAttr($value) ?>">
 *   <script>var data = <?= eJs($obj) ?>;</script>
 *   R$ <?= eNum($price) ?>
 *
 * @see Akti\Utils\Escape — Classe completa de escape
 * @see PROJECT_RULES.md — Módulo: Sanitização e Validação
 */
function e($value): string
{
    return \Akti\Utils\Escape::html($value);
}

/**
 * Escape para contexto de atributo HTML.
 * Atalho para Escape::attr().
 *
 * Exemplo:
 *   <input value="<?= eAttr($product['name']) ?>">
 *   <button data-name="<?= eAttr($customer['name']) ?>">
 *
 * @param  mixed  $value
 * @return string
 */
function eAttr($value): string
{
    return \Akti\Utils\Escape::attr($value);
}

/**
 * Escape para contexto JavaScript (inline scripts).
 * Atalho para Escape::js().
 *
 * Exemplo:
 *   <script>var userName = <?= eJs($user['name']) ?>;</script>
 *   <script>var config = <?= eJs(['id' => 1, 'name' => 'test']) ?>;</script>
 *
 * @param  mixed $value
 * @return string
 */
function eJs($value): string
{
    return \Akti\Utils\Escape::js($value);
}

/**
 * Formata número para exibição (locale BR).
 * Atalho para Escape::number().
 *
 * Exemplo:
 *   R$ <?= eNum($order['total']) ?>
 *   <?= eNum($quantity, 0) ?> unidades
 *
 * @param  mixed $value
 * @param  int   $decimals
 * @return string
 */
function eNum($value, int $decimals = 2): string
{
    return \Akti\Utils\Escape::number($value, $decimals);
}

/**
 * Escape para URL (query string).
 * Atalho para Escape::url().
 *
 * Exemplo:
 *   <a href="?page=products&search=<?= eUrl($term) ?>">
 *
 * @param  mixed $value
 * @return string
 */
function eUrl($value): string
{
    return \Akti\Utils\Escape::url($value);
}

/**
 * Retorna o nonce CSP do request atual para uso em tags <script>.
 *
 * Exemplo:
 *   <script nonce="<?= cspNonce() ?>">...</script>
 *
 * @return string
 */
function cspNonce(): string
{
    return \Akti\Middleware\SecurityHeadersMiddleware::getNonce();
}
