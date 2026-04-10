<?php
namespace Akti\Gateways;

use Akti\Gateways\Contracts\PaymentGatewayInterface;

/**
 * AbstractGateway — Implementação base para gateways de pagamento.
 *
 * Contém lógica reutilizável: gestão de credenciais, settings, ambiente,
 * helpers de HTTP (cURL), e formatação de respostas.
 *
 * Gateways concretos estendem esta classe e implementam apenas a lógica
 * específica da API de cada provedor.
 *
 * @package Akti\Gateways
 * @see     PaymentGatewayInterface
 */
abstract class AbstractGateway implements PaymentGatewayInterface
{
    /** @var array Credenciais do gateway (api_key, secret, etc.) */
    protected array $credentials = [];

    /** @var array Configurações extras (pix_enabled, boleto_days, etc.) */
    protected array $settings = [];

    /** @var string Ambiente: 'sandbox' ou 'production' */
    protected string $environment = 'sandbox';

    // ══════════════════════════════════════════════════════════════
    // Configuração (implementação padrão da interface)
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * {@inheritDoc}
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritDoc}
     *
     * Valida o valor recebido; se inválido, assume 'sandbox' como padrão.
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = in_array($environment, ['sandbox', 'production'])
            ? $environment
            : 'sandbox';
    }

    /**
     * Retorna uma credencial pelo nome.
     *
     * @param string $key     Nome da credencial (ex: 'access_token', 'secret_key').
     * @param string $default Valor padrão se a credencial não existir.
     *
     * @return string
     */
    protected function getCredential(string $key, string $default = ''): string
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Retorna uma configuração extra pelo nome.
     *
     * @param string $key     Nome da configuração (ex: 'pix_expiration_minutes').
     * @param mixed  $default Valor padrão se a configuração não existir.
     *
     * @return mixed
     */
    protected function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Verifica se está em modo sandbox.
     *
     * @return bool True se o ambiente for 'sandbox'.
     */
    protected function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers HTTP (cURL)
    // ══════════════════════════════════════════════════════════════

    /**
     * Faz requisição HTTP via cURL.
     *
     * @param string $method  HTTP method (GET, POST, PUT, DELETE)
     * @param string $url     URL completa
     * @param array  $headers Headers adicionais
     * @param mixed  $body    Body (array será convertido em JSON)
     * @param int    $timeout Timeout em segundos
     * @return array ['status' => int, 'body' => string, 'decoded' => array|null]
     */
    protected function httpRequest(
        string $method,
        string $url,
        array $headers = [],
        $body = null,
        int $timeout = 30
    ): array {
        $ch = curl_init();

        $defaultHeaders = ['Content-Type: application/json', 'Accept: application/json'];
        $mergedHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => $mergedHeaders,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_SSL_VERIFYPEER => !$this->isSandbox(),
        ]);

        if ($body !== null) {
            $payload = is_array($body) ? json_encode($body) : $body;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'status'  => 0,
                'body'    => $error,
                'decoded' => null,
                'error'   => $error,
            ];
        }

        return [
            'status'  => $httpCode,
            'body'    => $response,
            'decoded' => json_decode($response, true),
            'error'   => null,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Formatação de Respostas
    // ══════════════════════════════════════════════════════════════

    /**
     * Cria uma resposta padronizada de sucesso.
     *
     * @param array $data Dados adicionais a incluir na resposta.
     *
     * @return array Array com 'success' => true mesclado com $data.
     */
    protected function successResponse(array $data): array
    {
        return array_merge(['success' => true], $data);
    }

    /**
     * Cria uma resposta padronizada de erro.
     *
     * @param string $message Mensagem de erro descritiva.
     * @param array  $extra   Dados adicionais (ex: 'raw' => resposta bruta).
     *
     * @return array Array com 'success' => false, 'error' => $message, mesclado com $extra.
     */
    protected function errorResponse(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'error'   => $message,
        ], $extra);
    }

    /**
     * Mapeia status do gateway para status padronizado do Akti.
     *
     * Gateways concretos devem sobrescrever com seu mapeamento específico.
     *
     * @param string $gatewayStatus Status original retornado pela API do gateway.
     *
     * @return string Status padronizado: 'approved', 'pending', 'rejected', 'cancelled' ou 'refunded'.
     */
    abstract protected function mapStatus(string $gatewayStatus): string;

    // ══════════════════════════════════════════════════════════════
    // Logging
    // ══════════════════════════════════════════════════════════════

    /**
     * Loga uma operação do gateway (append em storage/logs/gateways.log).
     *
     * @param string $level   Nível do log ('info', 'warning', 'error').
     * @param string $message Mensagem descritiva do evento.
     * @param array  $context Dados adicionais para contexto (serializados em JSON).
     *
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logFile = defined('AKTI_BASE_PATH')
            ? AKTI_BASE_PATH . 'storage/logs/gateways.log'
            : __DIR__ . '/../../storage/logs/gateways.log';

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] [%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $this->getSlug(),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
