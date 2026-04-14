<?php

namespace Akti\Services;

use Akti\Models\WhatsAppMessage;
use Akti\Core\Log;

class WhatsAppService
{
    private WhatsAppMessage $model;
    private ?array $config = null;
    private int $tenantId;

    public function __construct(WhatsAppMessage $model, int $tenantId)
    {
        $this->model = $model;
        $this->tenantId = $tenantId;
        $this->config = $model->getConfig($tenantId);
    }

    public function isConfigured(): bool
    {
        return $this->config && !empty($this->config['is_active']) && !empty($this->config['api_url']);
    }

    public function send(string $phone, string $message, ?int $customerId = null, ?int $templateId = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp not configured for this tenant'];
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 10) {
            return ['success' => false, 'error' => 'Invalid phone number'];
        }
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        $msgId = $this->model->logMessage([
            'tenant_id'   => $this->tenantId,
            'template_id' => $templateId,
            'phone'       => $phone,
            'customer_id' => $customerId,
            'message'     => $message,
        ]);

        $result = $this->sendViaProvider($phone, $message);

        if ($result['success']) {
            $this->model->updateMessageStatus($msgId, 'sent', $result['external_id'] ?? null);
        } else {
            $this->model->updateMessageStatus($msgId, 'failed', null, $result['error'] ?? 'Unknown error');
        }

        return $result + ['message_id' => $msgId];
    }

    public function sendFromTemplate(string $eventType, string $phone, array $variables, ?int $customerId = null): array
    {
        $templates = $this->model->getTemplates($this->tenantId);
        $template = null;
        foreach ($templates as $t) {
            if ($t['event_type'] === $eventType && $t['is_active']) {
                $template = $t;
                break;
            }
        }

        if (!$template) {
            return ['success' => false, 'error' => "No active template for event: {$eventType}"];
        }

        $message = $template['message_template'];
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }

        return $this->send($phone, $message, $customerId, (int) $template['id']);
    }

    private function sendViaProvider(string $phone, string $message): array
    {
        $provider = $this->config['provider'] ?? '';

        try {
            switch ($provider) {
                case 'evolution_api':
                    return $this->sendEvolutionApi($phone, $message);
                case 'z_api':
                    return $this->sendZApi($phone, $message);
                case 'meta_cloud':
                    return $this->sendMetaCloud($phone, $message);
                default:
                    return ['success' => false, 'error' => "Unknown provider: {$provider}"];
            }
        } catch (\Throwable $e) {
            Log::channel('api')->error('WhatsApp send failed', [
                'provider' => $provider,
                'phone'    => $phone,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendEvolutionApi(string $phone, string $message): array
    {
        $url = rtrim($this->config['api_url'], '/') . '/message/sendText/' . $this->config['instance_name'];
        $payload = json_encode([
            'number'  => $phone,
            'options' => ['delay' => 1200],
            'textMessage' => ['text' => $message],
        ]);

        $response = $this->httpPost($url, $payload, [
            'Content-Type: application/json',
            'apikey: ' . $this->config['api_key'],
        ]);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], true);
            return ['success' => true, 'external_id' => $data['key']['id'] ?? null];
        }
        return ['success' => false, 'error' => "HTTP {$response['http_code']}: {$response['body']}"];
    }

    private function sendZApi(string $phone, string $message): array
    {
        $url = rtrim($this->config['api_url'], '/') . '/send-text';
        $payload = json_encode(['phone' => $phone, 'message' => $message]);

        $response = $this->httpPost($url, $payload, [
            'Content-Type: application/json',
            'Client-Token: ' . $this->config['api_key'],
        ]);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], true);
            return ['success' => true, 'external_id' => $data['messageId'] ?? null];
        }
        return ['success' => false, 'error' => "HTTP {$response['http_code']}: {$response['body']}"];
    }

    private function sendMetaCloud(string $phone, string $message): array
    {
        $url = "https://graph.facebook.com/v18.0/{$this->config['phone_number_id']}/messages";
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to'   => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);

        $response = $this->httpPost($url, $payload, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['api_key'],
        ]);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], true);
            return ['success' => true, 'external_id' => $data['messages'][0]['id'] ?? null];
        }
        return ['success' => false, 'error' => "HTTP {$response['http_code']}: {$response['body']}"];
    }

    private function httpPost(string $url, string $payload, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['http_code' => $httpCode, 'body' => $body ?: ''];
    }
}
