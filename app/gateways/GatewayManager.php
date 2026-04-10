<?php
namespace Akti\Gateways;

use Akti\Gateways\Contracts\PaymentGatewayInterface;
use Akti\Gateways\Providers\MercadoPagoGateway;
use Akti\Gateways\Providers\StripeGateway;
use Akti\Gateways\Providers\PagSeguroGateway;

/**
 * GatewayManager — Strategy Pattern resolver para gateways de pagamento.
 *
 * Responsabilidades:
 *   1. Registrar gateways disponíveis (registry pattern)
 *   2. Resolver qual gateway usar com base no slug
 *   3. Configurar credenciais/settings/ambiente do gateway resolvido
 *   4. Retornar instância pronta para uso
 *
 * O sistema usa este manager como ponto único de entrada. Controllers e
 * services NUNCA instanciam gateways diretamente — sempre via GatewayManager.
 *
 * Para adicionar um novo gateway:
 *   1. Crie a classe em app/gateways/Providers/ implementando PaymentGatewayInterface
 *   2. Registre no map GATEWAY_MAP abaixo
 *   3. Insira o registro no banco (payment_gateways) via migration SQL
 *
 * @package Akti\Gateways
 * @see     PaymentGatewayInterface
 * @see     AbstractGateway
 */
class GatewayManager
{
    /**
     * Mapa de slugs para classes concretas de gateway.
     * Para adicionar um novo gateway, basta adicionar aqui e criar a classe.
     */
    private const GATEWAY_MAP = [
        'mercadopago' => MercadoPagoGateway::class,
        'stripe'      => StripeGateway::class,
        'pagseguro'   => PagSeguroGateway::class,
    ];

    /** @var PaymentGatewayInterface[] Cache de instâncias */
    private static array $instances = [];

    // ══════════════════════════════════════════════════════════════
    // Factory / Resolver
    // ══════════════════════════════════════════════════════════════

    /**
     * Resolve e retorna um gateway pelo slug.
     * Não configura credenciais — use resolve() para gateway configurado.
     *
     * @param string $slug Slug do gateway (ex: 'mercadopago')
     * @return PaymentGatewayInterface
     * @throws \InvalidArgumentException Se o slug não for suportado
     */
    public static function make(string $slug): PaymentGatewayInterface
    {
        $slug = strtolower(trim($slug));

        if (!isset(self::GATEWAY_MAP[$slug])) {
            throw new \InvalidArgumentException("Gateway '{$slug}' não suportado. Disponíveis: " . implode(', ', array_keys(self::GATEWAY_MAP)));
        }

        // Cria nova instância (sem cache, cada chamada gera instância limpa)
        $class = self::GATEWAY_MAP[$slug];
        return new $class();
    }

    /**
     * Resolve um gateway e configura com credenciais/settings/ambiente do banco.
     *
     * @param string $slug        Slug do gateway
     * @param array  $credentials Credenciais decodificadas do campo payment_gateways.credentials
     * @param array  $settings    Settings decodificadas do campo payment_gateways.settings_json
     * @param string $environment 'sandbox' ou 'production'
     * @return PaymentGatewayInterface Instância configurada, pronta para uso
     */
    public static function resolve(
        string $slug,
        array $credentials = [],
        array $settings = [],
        string $environment = 'sandbox'
    ): PaymentGatewayInterface {
        $gateway = self::make($slug);
        $gateway->setCredentials($credentials);
        $gateway->setSettings($settings);
        $gateway->setEnvironment($environment);
        return $gateway;
    }

    /**
     * Resolve um gateway com base em um registro do banco (row de payment_gateways).
     *
     * @param array $gatewayRow Row do banco com: gateway_slug, credentials, settings_json, environment
     * @return PaymentGatewayInterface
     */
    public static function resolveFromRow(array $gatewayRow): PaymentGatewayInterface
    {
        $credentials = [];
        if (!empty($gatewayRow['credentials'])) {
            $decoded = json_decode($gatewayRow['credentials'], true);
            $credentials = is_array($decoded) ? $decoded : [];
        }

        $settings = [];
        if (!empty($gatewayRow['settings_json'])) {
            $decoded = json_decode($gatewayRow['settings_json'], true);
            $settings = is_array($decoded) ? $decoded : [];
        }

        return self::resolve(
            $gatewayRow['gateway_slug'] ?? '',
            $credentials,
            $settings,
            $gatewayRow['environment'] ?? 'sandbox'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Registry
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna lista de todos os slugs de gateways registrados.
     *
     * @return string[]
     */
    public static function getRegisteredSlugs(): array
    {
        return array_keys(self::GATEWAY_MAP);
    }

    /**
     * Verifica se um slug de gateway está registrado.
     *
     * @param string $slug Slug do gateway a verificar.
     *
     * @return bool True se o slug estiver no GATEWAY_MAP.
     */
    public static function isRegistered(string $slug): bool
    {
        return isset(self::GATEWAY_MAP[strtolower(trim($slug))]);
    }

    /**
     * Retorna lista de gateways com informações básicas (sem configurar credenciais).
     * Usado na UI para exibir opções.
     *
     * @return array Array de arrays com: slug, display_name, supported_methods, credential_fields, settings_fields
     */
    public static function getAvailableGateways(): array
    {
        $gateways = [];
        foreach (self::GATEWAY_MAP as $slug => $class) {
            $instance = new $class();
            $gateways[] = [
                'slug'              => $slug,
                'display_name'      => $instance->getDisplayName(),
                'supported_methods' => $instance->getSupportedMethods(),
                'credential_fields' => $instance->getCredentialFields(),
                'settings_fields'   => $instance->getSettingsFields(),
            ];
        }
        return $gateways;
    }

    /**
     * Retorna labels amigáveis para métodos de pagamento.
     *
     * @return array<string, string> Mapa de slug do método => label de exibição.
     */
    public static function getMethodLabels(): array
    {
        return [
            'auto'        => 'Cliente Escolhe (Checkout)',
            'pix'         => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            'boleto'      => 'Boleto Bancário',
            'debit_card'  => 'Cartão de Débito',
        ];
    }
}
