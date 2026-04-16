<?php
/**
 * Portal Helper — Funções utilitárias globais para o Portal do Cliente.
 *
 * Carregado automaticamente via autoload.php.
 * Fornece atalhos para tradução e formatação no portal.
 *
 * @see Akti\Services\PortalLang
 */
function __p(string $key, array $params = [], ?string $default = null): string
{
    return \Akti\Services\PortalLang::get($key, $params, $default);
}

/**
 * Formata valor monetário no padrão pt-BR.
 *
 * @param float|string $value
 * @return string Ex: "R$ 1.500,00"
 */
function portal_money($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

/**
 * Formata data no padrão do portal.
 *
 * @param string|null $date
 * @param string $format
 * @return string
 */
function portal_date(?string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

/**
 * Formata data e hora no padrão do portal.
 *
 * @param string|null $datetime
 * @return string
 */
function portal_datetime(?string $datetime): string
{
    return portal_date($datetime, 'd/m/Y H:i');
}

/**
 * Retorna a classe CSS para o status do pipeline.
 *
 * @param string $stage
 * @return string
 */
function portal_stage_class(string $stage): string
{
    $map = [
        'contato'    => 'secondary',
        'orcamento'  => 'warning',
        'venda'      => 'info',
        'producao'   => 'primary',
        'preparacao' => 'dark',
        'envio'      => 'success',
        'financeiro' => 'success',
        'concluido'  => 'success',
        'cancelado'  => 'danger',
    ];
    return $map[$stage] ?? 'secondary';
}

/**
 * Retorna o ícone para o status do pipeline.
 *
 * @param string $stage
 * @return string
 */
function portal_stage_icon(string $stage): string
{
    $map = [
        'contato'    => 'fas fa-phone',
        'orcamento'  => 'fas fa-file-alt',
        'venda'      => 'fas fa-handshake',
        'producao'   => 'fas fa-cogs',
        'preparacao' => 'fas fa-box-open',
        'envio'      => 'fas fa-truck',
        'financeiro' => 'fas fa-dollar-sign',
        'concluido'  => 'fas fa-check-circle',
        'cancelado'  => 'fas fa-times-circle',
    ];
    return $map[$stage] ?? 'fas fa-circle';
}
