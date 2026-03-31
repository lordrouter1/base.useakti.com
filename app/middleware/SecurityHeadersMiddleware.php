<?php
namespace Akti\Middleware;

/**
 * SecurityHeadersMiddleware
 *
 * Adiciona headers de segurança HTTP em todas as respostas.
 * Deve ser chamado no início do ciclo de request (index.php),
 * antes de qualquer output.
 *
 * Headers aplicados:
 * - X-Content-Type-Options: previne MIME type sniffing
 * - X-Frame-Options: previne clickjacking
 * - Referrer-Policy: controla vazamento de referrer
 * - Permissions-Policy: restringe APIs do navegador
 * - X-XSS-Protection: desabilitado (obsoleto, evita falsos positivos)
 * - Strict-Transport-Security: força HTTPS (apenas quando em HTTPS)
 *
 * @package Akti\Middleware
 * @see ROADMAP_DETALHADO_2026.md — Fase 1, item 2.4
 */
class SecurityHeadersMiddleware
{
    /**
     * Aplica todos os headers de segurança.
     * Deve ser chamado ANTES de qualquer output HTML/JSON.
     */
    public static function handle(): void
    {
        if (headers_sent()) {
            return;
        }

        // Previne MIME type sniffing — o navegador respeita o Content-Type declarado
        header('X-Content-Type-Options: nosniff');

        // Previne clickjacking — bloqueia embedding em iframes de outros domínios
        header('X-Frame-Options: SAMEORIGIN');

        // Controla informações enviadas no header Referer
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Restringe APIs do navegador que não são necessárias
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // X-XSS-Protection desabilitado — é obsoleto e pode causar falsos positivos
        // em navegadores modernos. O CSP é a proteção correta (futuro).
        header('X-XSS-Protection: 0');

        // HSTS — força HTTPS por 1 ano (apenas quando já em HTTPS)
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Verifica se a request atual está usando HTTPS.
     */
    private static function isHttps(): bool
    {
        // Checagem direta
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Proxy reverso (ex: Nginx, CloudFlare)
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        // Porta padrão HTTPS
        if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        return false;
    }
}
