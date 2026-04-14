<?php

namespace Akti\Services;

use Akti\Core\Log;

/**
 * AI Assistant Service — integrates with OpenAI-compatible APIs (GPT, Ollama, etc.)
 * to provide a contextual chat assistant within the system.
 */
class AiAssistantService
{
    private \PDO $db;
    private string $apiKey;
    private string $apiUrl;
    private string $model;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $stmt = $this->db->prepare("SELECT config_key, config_value FROM settings WHERE config_key IN ('ai_api_key','ai_api_url','ai_model')");
        $stmt->execute();
        $config = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $config[$row['config_key']] = $row['config_value'];
        }
        $this->apiKey = $config['ai_api_key'] ?? '';
        $this->apiUrl = $config['ai_api_url'] ?? 'https://api.openai.com/v1/chat/completions';
        $this->model  = $config['ai_model'] ?? 'gpt-4o-mini';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Send a chat message and get a response.
     * @param array $messages Array of ['role'=>'user/assistant/system', 'content'=>'...']
     * @return array ['success'=>bool, 'message'=>string, 'usage'=>array]
     */
    public function chat(array $messages): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Assistente IA não configurado. Adicione a chave API nas configurações.'];
        }

        // Prepend system context
        array_unshift($messages, [
            'role' => 'system',
            'content' => 'Você é o assistente virtual do sistema Akti — Gestão em Produção. '
                . 'Responda de forma concisa e útil em português brasileiro. '
                . 'Ajude com dúvidas sobre pedidos, produção, clientes, financeiro e configurações do sistema.',
        ]);

        $payload = [
            'model'    => $this->model,
            'messages' => $messages,
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::channel('api')->error('AI API curl error', ['error' => $error]);
            return ['success' => false, 'message' => 'Erro de conexão com a IA.'];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['choices'][0]['message']['content'])) {
            Log::channel('api')->error('AI API error', ['http_code' => $httpCode, 'response' => $data]);
            return ['success' => false, 'message' => $data['error']['message'] ?? 'Erro na resposta da IA.'];
        }

        return [
            'success' => true,
            'message' => $data['choices'][0]['message']['content'],
            'usage'   => $data['usage'] ?? [],
        ];
    }

    /**
     * Get conversation history for a user.
     */
    public function getHistory(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT role, content, created_at
            FROM ai_chat_history
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Save a message to conversation history.
     */
    public function saveMessage(int $userId, string $role, string $content): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO ai_chat_history (user_id, role, content, created_at)
            VALUES (:uid, :role, :content, NOW())
        ");
        $stmt->execute([':uid' => $userId, ':role' => $role, ':content' => $content]);
    }

    /**
     * Clear conversation history for a user.
     */
    public function clearHistory(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM ai_chat_history WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
    }
}
