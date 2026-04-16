<?php
namespace Akti\Services;

use Akti\Models\NfeWebhook;
use PDO;

/**
 * NfeWebhookService — Dispara webhooks para eventos NF-e.
 *
 * Funcionalidade:
 *   - Envia HTTP POST com payload JSON para URLs configuradas
 *   - Assinatura HMAC-SHA256 no header X-Webhook-Signature
 *   - Retry com backoff para falhas
 *   - Log de cada entrega
 *
 * @package Akti\Services
 */
class NfeWebhookService
{
    private PDO $db;
    private NfeWebhook $model;

    /**
     * Construtor da classe NfeWebhookService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new NfeWebhook($db);
    }

    /**
     * Dispara webhooks para um evento.
     *
     * @param string $eventName  Nome do evento (ex: nfe.authorized)
     * @param array  $payload    Dados do evento
     * @return array ['dispatched' => int, 'success' => int, 'failed' => int]
     */
    public function dispatch(string $eventName, array $payload): array
    {
        $webhooks = $this->model->getByEvent($eventName);

        $result = ['dispatched' => 0, 'success' => 0, 'failed' => 0];

        foreach ($webhooks as $webhook) {
            $result['dispatched']++;
            $success = $this->sendWebhook($webhook, $eventName, $payload);

            if ($success) {
                $result['success']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Envia um webhook com retry.
     *
     * @param array  $webhook
     * @param string $eventName
     * @param array  $payload
     * @return bool
     */
    private function sendWebhook(array $webhook, string $eventName, array $payload): bool
    {
        $url = $webhook['url'];
        $secret = $webhook['secret'] ?? '';
        $timeout = $webhook['timeout_seconds'] ?? 10;
        $maxRetries = $webhook['retry_count'] ?? 3;
        $customHeaders = $webhook['headers'] ?? [];

        $jsonPayload = json_encode([
            'event'     => $eventName,
            'timestamp' => date('c'),
            'data'      => $payload,
        ], JSON_UNESCAPED_UNICODE);

        // Headers padrão
        $headers = [
            'Content-Type: application/json',
            'X-Webhook-Event: ' . $eventName,
            'X-Webhook-ID: ' . ($webhook['id'] ?? 0),
        ];

        // Assinatura HMAC se secret definido
        if (!empty($secret)) {
            $signature = hash_hmac('sha256', $jsonPayload, $secret);
            $headers[] = 'X-Webhook-Signature: sha256=' . $signature;
        }

        // Headers customizados
        if (is_array($customHeaders)) {
            foreach ($customHeaders as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
        }

        // Retry com backoff
        $retryIntervals = [0, 2, 5]; // segundos
        $lastError = '';
        $responseCode = 0;
        $responseBody = '';

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            if ($attempt > 1 && isset($retryIntervals[$attempt - 1])) {
                sleep($retryIntervals[$attempt - 1]);
            }

            try {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $jsonPayload,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                ]);

                $responseBody = curl_exec($ch);
                $responseCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if (!empty($curlError)) {
                    $lastError = "cURL error: {$curlError}";
                    continue; // retry
                }

                // Sucesso: 2xx
                if ($responseCode >= 200 && $responseCode < 300) {
                    $this->model->logDelivery([
                        'webhook_id'    => $webhook['id'],
                        'event_name'    => $eventName,
                        'payload'       => $jsonPayload,
                        'response_code' => $responseCode,
                        'response_body' => mb_substr($responseBody, 0, 5000),
                        'status'        => 'success',
                        'attempt'       => $attempt,
                    ]);
                    return true;
                }

                $lastError = "HTTP {$responseCode}: " . mb_substr($responseBody, 0, 500);

                // Não fazer retry para erros 4xx (exceto 429)
                if ($responseCode >= 400 && $responseCode < 500 && $responseCode !== 429) {
                    break;
                }

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        // Registrar falha
        $this->model->logDelivery([
            'webhook_id'    => $webhook['id'],
            'event_name'    => $eventName,
            'payload'       => $jsonPayload,
            'response_code' => $responseCode,
            'response_body' => mb_substr($responseBody, 0, 5000),
            'status'        => 'failed',
            'attempt'       => $attempt ?? 1,
            'error_message' => $lastError,
        ]);

        return false;
    }

    /**
     * Retorna o model para CRUD direto.
     * @return NfeWebhook
     */
    public function getModel(): NfeWebhook
    {
        return $this->model;
    }
}
