<?php
namespace Akti\Core;

/**
 * Bootloader central de módulos por tenant.
 *
 * Fonte de dados:
 * - $_SESSION['tenant']['enabled_modules'] (vindo do TenantManager / akti_master)
 *
 * Regras:
 * - Se não houver configuração explícita, os módulos default permanecem habilitados.
 * - O controle é feito por slug de módulo (ex.: financial, boleto, nfe, fiscal).
 *
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  FLUXO DE EVENTOS — Habilitação/Desabilitação de Módulos           ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 *
 * ── Camada PHP (server-side) ──
 *
 * 1. TenantManager resolve o subdomínio e carrega enabled_modules do akti_master.
 * 2. A sessão é populada: $_SESSION['tenant']['enabled_modules'].
 * 3. index.php chama ModuleBootloader::canAccessPage() antes de despachar a rota.
 *    - Se o módulo da página estiver desabilitado → redireciona para dashboard com flash.
 * 4. header.php:
 *    a. Usa canAccessPage() para ocultar itens de menu de módulos desabilitados.
 *    b. Injeta injectJS() no <head> → expõe AktiModules e AktiEvents no JS global.
 * 5. settings usa sanitizeSettingsTab() para redirecionar tabs desabilitadas ao fallback.
 * 6. Views usam isModuleEnabled() para:
 *    a. Ocultar seções inteiras (ex.: card fiscal no produto quando nfe=false).
 *    b. Desabilitar botões específicos (ex.: reimprimir boleto → mostra alert).
 *       IMPORTANTE: desabilitar módulo NÃO deve bloquear o fluxo principal.
 *       Ex.: boleto desabilitado → botão "Pagar" funciona normalmente, modal abre,
 *            apenas o botão "Reimprimir" dentro do modal fica desabilitado.
 * 7. getDisabledModuleJS() fornece JS inline para onclick="" de botões bloqueados.
 *
 * ── Camada JavaScript (client-side) ──
 *
 * 8. injectJS() (chamado no <head> via header.php) gera <script> com:
 *    - window.AktiEvents → Event Bus leve (on/off/emit) para comunicação entre componentes.
 *    - window.AktiModules → Estado dos módulos + helpers (isEnabled, guardClick, showDisabledAlert).
 *    - Emite evento "modules:loaded" ao inicializar.
 *
 * 9. script.js (global) escuta "modules:loaded" e aplica regras a data-attributes:
 *    - data-akti-module="slug"         → guarda clique (mostra alert se desabilitado)
 *    - data-akti-module-hide="slug"    → oculta elemento se módulo desabilitado
 *    - data-akti-module-disable="slug" → desabilita botão se módulo desabilitado
 *    - Emite evento "modules:ready" quando o processamento DOM está concluído.
 *
 * ── Eventos disponíveis no AktiEvents ──
 *
 * | Evento                    | Payload      | Quando                                      |
 * |---------------------------|--------------|---------------------------------------------|
 * | modules:loaded            | {slug: bool} | Logo após injectJS() no <head>              |
 * | modules:ready             | (nenhum)     | Após script.js processar data-attributes    |
 * | module:disabled:alert     | slug         | Quando showDisabledAlert() exibe o SweetAlert|
 *
 * ── Uso nas Views ──
 *
 * PHP (server-side):
 *   if (\Akti\Core\ModuleBootloader::isModuleEnabled('boleto')) { ... }
 *   onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('boleto') ?>"
 *
 * HTML (data-attributes, processados automaticamente pelo script.js):
 *   <button data-akti-module="boleto">Reimprimir</button>
 *   <div data-akti-module-hide="nfe">Seção fiscal</div>
 *   <button data-akti-module-disable="boleto">Print</button>
 *
 * JavaScript:
 *   AktiModules.isEnabled('boleto')
 *   AktiModules.guardClick('boleto', function(){ printBoleto(); })
 *   AktiModules.showDisabledAlert('nfe')
 *   AktiEvents.on('modules:ready', function(){ // DOM pronto })
 */
class ModuleBootloader
{
    /**
     * Módulos padrão habilitados quando o tenant não possui configuração explícita.
     *
     * boleto = false → Desabilita impressão de boletos no pipeline e no financeiro.
     *                   Ao clicar, exibe modal informando que o módulo não está ativo.
     * nfe    = false → Desabilita o card de informações fiscais no produto (create/edit)
     *                   e a seção Fiscal/NF-e no detalhe do pipeline.
     * fiscal = false → Desabilita a aba Fiscal nas configurações.
     */
    private const DEFAULT_ENABLED = [
        'financial' => true,
        'boleto'    => false,
        'nfe'       => false,
        'fiscal'    => false,
    ];

    /**
     * Labels amigáveis para cada módulo (usado em mensagens de UI).
     */
    private const MODULE_LABELS = [
        'financial' => 'Financeiro',
        'boleto'    => 'Boleto Bancário',
        'nfe'       => 'Nota Fiscal Eletrônica (NF-e)',
        'fiscal'    => 'Configurações Fiscais',
    ];

    /**
     * Mapeamento page => módulo responsável.
     */
    private const PAGE_MODULE_MAP = [
        'financial'              => 'financial',
        'financial_payments'     => 'financial',
        'financial_transactions' => 'financial',
    ];

    /**
     * Mapeamento de tabs da página de configurações.
     */
    private const SETTINGS_TAB_MODULE_MAP = [
        'boleto' => 'boleto',
        'fiscal' => 'fiscal',
    ];

    public static function isModuleEnabled(string $moduleSlug): bool
    {
        $modules = self::getEnabledModules();
        if (!array_key_exists($moduleSlug, $modules)) {
            return true;
        }

        return (bool) $modules[$moduleSlug];
    }

    public static function canAccessPage(string $page): bool
    {
        if (!isset(self::PAGE_MODULE_MAP[$page])) {
            return true;
        }

        $module = self::PAGE_MODULE_MAP[$page];
        return self::isModuleEnabled($module);
    }

    public static function canAccessSettingsTab(string $tab): bool
    {
        if (!isset(self::SETTINGS_TAB_MODULE_MAP[$tab])) {
            return true;
        }

        $module = self::SETTINGS_TAB_MODULE_MAP[$tab];
        return self::isModuleEnabled($module);
    }

    public static function sanitizeSettingsTab(?string $tab, string $fallback = 'company'): string
    {
        $tab = $tab ?: $fallback;
        if (!self::canAccessSettingsTab($tab)) {
            return $fallback;
        }

        return $tab;
    }

    /**
     * Retorna o label amigável do módulo.
     */
    public static function getModuleLabel(string $moduleSlug): string
    {
        return self::MODULE_LABELS[$moduleSlug] ?? ucfirst($moduleSlug);
    }

    /**
     * Retorna JavaScript inline para exibir um SweetAlert2 de módulo desabilitado.
     * Deve ser chamado em onclick="" de botões/links que requerem o módulo.
     *
     * Exemplo de uso na view:
     *   <button onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('boleto') ?>">Imprimir Boleto</button>
     */
    public static function getDisabledModuleJS(string $moduleSlug): string
    {
        $label = htmlspecialchars(self::getModuleLabel($moduleSlug), ENT_QUOTES);
        return "event.preventDefault();event.stopPropagation();"
             . "Swal.fire({"
             . "icon:'info',"
             . "title:'Módulo Não Disponível',"
             . "html:'<p>O módulo <strong>{$label}</strong> não está ativo no seu plano atual.</p>"
             . "<p class=\'small text-muted\'>Entre em contato com o suporte para contratar ou ativar este recurso.</p>"
             . "<hr><p class=\'small mb-0\'><i class=\'fas fa-headset me-1\'></i>"
             . "Fale conosco: <strong>contato@useakti.com</strong></p>',"
             . "confirmButtonText:'Entendi',"
             . "confirmButtonColor:'#3085d6'"
             . "});";
    }

    /**
     * Retorna mapa de módulos habilitados com merge de defaults.
     *
     * Entrada aceita:
     * - array associativo ['financial' => true, ...]
     * - string JSON equivalente
     */
    public static function getEnabledModules(): array
    {
        $raw = $_SESSION['tenant']['enabled_modules'] ?? null;
        $parsed = [];

        if (is_array($raw)) {
            $parsed = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $parsed = $json;
            }
        }

        $normalized = [];
        foreach ($parsed as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $normalized[strtolower(trim($key))] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized[strtolower(trim($key))] === null) {
                $normalized[strtolower(trim($key))] = (bool) $value;
            }
        }

        return array_merge(self::DEFAULT_ENABLED, $normalized);
    }

    /**
     * Retorna <script> inline que expõe o estado dos módulos e o event bus
     * no JS global: window.AktiModules e window.AktiEvents.
     *
     * Deve ser chamado UMA VEZ no <head> (header.php), antes de </head>.
     * Já injetado automaticamente em app/views/layout/header.php.
     *
     * Objetos expostos:
     *   window.AktiEvents  → Event Bus (on, off, emit)
     *   window.AktiModules → isEnabled(slug), getLabel(slug), getAll(),
     *                         showDisabledAlert(slug), guardClick(slug, callback)
     *
     * Eventos emitidos:
     *   'modules:loaded' → imediatamente após a injeção (payload: {slug: bool})
     *
     * O script.js global complementa com:
     *   - Processamento de data-akti-module / data-akti-module-hide / data-akti-module-disable
     *   - Emissão de 'modules:ready' após o DOM estar processado
     *
     * Uso nas views:
     *   if (AktiModules.isEnabled('boleto')) { ... }
     *   AktiEvents.on('module:disabled:alert', function(slug){ ... });
     *   AktiModules.guardClick('boleto', function(){ printBoleto(); });
     */
    public static function injectJS(): string
    {
        $modules = self::getEnabledModules();
        $labels  = self::MODULE_LABELS;
        $json    = json_encode($modules, JSON_FORCE_OBJECT);
        $labelsJson = json_encode($labels, JSON_FORCE_OBJECT);

        return <<<HTML
<script>
(function(){
    "use strict";
    // ═══ AktiEvents — Event Bus global leve ═══
    var _listeners = {};
    window.AktiEvents = {
        on: function(event, fn) {
            if (!_listeners[event]) _listeners[event] = [];
            _listeners[event].push(fn);
            return this;
        },
        off: function(event, fn) {
            if (!_listeners[event]) return this;
            if (!fn) { _listeners[event] = []; return this; }
            _listeners[event] = _listeners[event].filter(function(f){ return f !== fn; });
            return this;
        },
        emit: function(event) {
            var args = Array.prototype.slice.call(arguments, 1);
            (_listeners[event] || []).forEach(function(fn){ try { fn.apply(null, args); } catch(e){ console.error('AktiEvents[' + event + ']:', e); } });
            // Wildcard listener '*' recebe (eventName, ...args)
            (_listeners['*'] || []).forEach(function(fn){ try { fn.apply(null, [event].concat(args)); } catch(e){ console.error('AktiEvents[*]:', e); } });
            return this;
        }
    };

    // ═══ AktiModules — Estado de módulos do tenant ═══
    var _state  = {$json};
    var _labels = {$labelsJson};

    window.AktiModules = {
        /** Verifica se um módulo está habilitado */
        isEnabled: function(slug) {
            if (_state.hasOwnProperty(slug)) return !!_state[slug];
            return true; // módulo desconhecido = permitido
        },
        /** Retorna label amigável */
        getLabel: function(slug) {
            return _labels[slug] || slug.charAt(0).toUpperCase() + slug.slice(1);
        },
        /** Retorna mapa completo {slug: bool} */
        getAll: function() { return Object.assign({}, _state); },
        /**
         * Exibe o SweetAlert2 padrão de módulo desabilitado.
         * Retorna true se o modal foi exibido (módulo desabilitado),
         * false se o módulo está ativo (nenhum bloqueio).
         */
        showDisabledAlert: function(slug) {
            if (this.isEnabled(slug)) return false;
            var label = this.getLabel(slug);
            Swal.fire({
                icon: 'info',
                title: 'Módulo Não Disponível',
                html: '<p>O módulo <strong>' + label + '</strong> não está ativo no seu plano atual.</p>'
                    + '<p class="small text-muted">Entre em contato com o suporte para contratar ou ativar este recurso.</p>'
                    + '<hr><p class="small mb-0"><i class="fas fa-headset me-1"></i>'
                    + 'Fale conosco: <strong>contato@useakti.com</strong></p>',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#3085d6'
            });
            AktiEvents.emit('module:disabled:alert', slug);
            return true;
        },
        /**
         * Protege um clique: se o módulo estiver desabilitado, mostra o alert
         * e NÃO executa o callback. Se habilitado, executa o callback.
         *
         * Uso: AktiModules.guardClick('boleto', function(){ printBoleto(); });
         */
        guardClick: function(slug, callback) {
            if (this.showDisabledAlert(slug)) return;
            if (typeof callback === 'function') callback();
        }
    };

    // Emitir evento inicial para listeners que queiram reagir ao estado
    AktiEvents.emit('modules:loaded', _state);
})();
</script>
HTML;
    }
}
