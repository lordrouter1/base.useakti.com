<?php
namespace Akti\Services;

use Akti\Models\NfeWebhook;
use Akti\Services\NfeAuditService;
use Akti\Services\NfeWebhookService;
use Akti\Utils\Input;
use PDO;

/**
 * Service: NfeWebhookManagementService
 * Gerencia CRUD de webhooks NF-e, testes e logs.
 */
class NfeWebhookManagementService
{
    private PDO $db;

    /**
     * Construtor da classe NfeWebhookManagementService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Lista todos os webhooks configurados.
     */
    public function listAll(): array
    {
        $model = new NfeWebhook($this->db);
        return $model->readAll();
    }

    /**
     * Cria ou atualiza um webhook.
     *
     * @param array $data [name, url, secret, events, is_active, retry_count, timeout_seconds, id?]
     * @return array ['success' => bool, 'message' => string]
     */
    public function save(array $data): array
    {
        $id       = (int) ($data['id'] ?? 0);
        $name     = trim($data['name'] ?? '');
        $url      = trim($data['url'] ?? '');
        $secret   = $data['secret'] ?? '';
        $eventsRaw = $data['events'] ?? '';
        $isActive  = (int) ($data['is_active'] ?? 1);
        $retryCount = (int) ($data['retry_count'] ?? 3);
        $timeout   = (int) ($data['timeout_seconds'] ?? 10);

        if (empty($name) || empty($url)) {
            return ['success' => false, 'message' => 'Nome e URL são obrigatórios.'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'URL inválida.'];
        }

        $events = array_filter(array_map('trim', explode(',', $eventsRaw)));
        if (empty($events)) {
            $events = ['*'];
        }

        $record = [
            'name'            => $name,
            'url'             => $url,
            'secret'          => $secret,
            'events'          => $events,
            'is_active'       => $isActive,
            'retry_count'     => min(max($retryCount, 1), 10),
            'timeout_seconds' => min(max($timeout, 5), 30),
        ];

        $model = new NfeWebhook($this->db);
        $newId = null;

        if ($id > 0) {
            $ok = $model->update($id, $record);
            $msg = $ok ? 'Webhook atualizado.' : 'Erro ao atualizar.';
        } else {
            $newId = $model->create($record);
            $ok = $newId > 0;
            $msg = $ok ? 'Webhook criado com sucesso.' : 'Erro ao criar.';
        }

        return [
            'success' => $ok,
            'message' => $msg,
            'id'      => $id ?: ($newId ?? null),
        ];
    }

    /**
     * Exclui um webhook.
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido.'];
        }

        $model = new NfeWebhook($this->db);
        $ok = $model->delete($id);

        return [
            'success' => $ok,
            'message' => $ok ? 'Webhook excluído.' : 'Erro ao excluir.',
        ];
    }

    /**
     * Testa envio de um webhook.
     */
    public function test(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido.'];
        }

        $model = new NfeWebhook($this->db);
        $webhook = $model->readOne($id);
        if (!$webhook) {
            return ['success' => false, 'message' => 'Webhook não encontrado.'];
        }

        $whService = new NfeWebhookService($this->db);
        $result = $whService->dispatch('nfe.test', [
            'message'   => 'Teste de webhook do sistema Akti.',
            'timestamp' => date('c'),
        ]);

        return [
            'success' => $result['success'] > 0,
            'message' => "Enviado: {$result['dispatched']}, Sucesso: {$result['success']}, Falha: {$result['failed']}",
        ];
    }

    /**
     * Retorna logs paginados de um webhook.
     */
    public function getLogs(int $id, int $page = 1, int $perPage = 20): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido.', 'data' => [], 'total' => 0];
        }

        $model = new NfeWebhook($this->db);
        $result = $model->getLogs($id, $page, $perPage);

        return ['success' => true, 'data' => $result['data'], 'total' => $result['total']];
    }
}
