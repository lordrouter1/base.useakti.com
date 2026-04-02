<?php

namespace Akti\Services;

/**
 * WorkflowEngine — Evaluates and executes workflow rules on events.
 * FEAT-010
 */
class WorkflowEngine
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Process an event: find matching rules, evaluate conditions, execute actions.
     */
    public function process(string $event, array $payload): void
    {
        $ruleModel = new \Akti\Models\WorkflowRule($this->db);
        $rules = $ruleModel->readByEvent($event);

        foreach ($rules as $rule) {
            $conditions = json_decode($rule['conditions'] ?? '[]', true) ?: [];
            $actions    = json_decode($rule['actions'] ?? '[]', true) ?: [];

            if ($this->evaluateConditions($conditions, $payload)) {
                $this->executeActions($actions, $payload);
                $ruleModel->logExecution([
                    'tenant_id'         => $rule['tenant_id'],
                    'rule_id'           => $rule['id'],
                    'event'             => $event,
                    'status'            => 'success',
                    'conditions_met'    => json_encode($conditions),
                    'actions_executed'  => json_encode($actions),
                ]);
            }
        }
    }

    /**
     * Evaluate all conditions against the event payload.
     */
    private function evaluateConditions(array $conditions, array $payload): bool
    {
        foreach ($conditions as $cond) {
            $field    = $cond['field'] ?? '';
            $operator = $cond['operator'] ?? '==';
            $value    = $cond['value'] ?? null;
            $actual   = $payload[$field] ?? null;

            switch ($operator) {
                case '==':
                case 'equals':
                    if ($actual != $value) return false;
                    break;
                case '!=':
                case 'not_equals':
                    if ($actual == $value) return false;
                    break;
                case '>':
                case 'gt':
                    if ($actual <= $value) return false;
                    break;
                case '<':
                case 'lt':
                    if ($actual >= $value) return false;
                    break;
                case 'contains':
                    if (strpos((string) $actual, (string) $value) === false) return false;
                    break;
                case 'in':
                    if (!in_array($actual, (array) $value)) return false;
                    break;
            }
        }
        return true;
    }

    /**
     * Execute actions (notification, email, field update, etc.).
     */
    private function executeActions(array $actions, array $payload): void
    {
        foreach ($actions as $action) {
            $type = $action['type'] ?? '';

            switch ($type) {
                case 'notify':
                    $this->actionNotify($action, $payload);
                    break;
                case 'email':
                    $this->actionEmail($action, $payload);
                    break;
                case 'update_field':
                    $this->actionUpdateField($action, $payload);
                    break;
                case 'log':
                    $this->actionLog($action, $payload);
                    break;
            }
        }
    }

    private function actionNotify(array $action, array $payload): void
    {
        $userId  = $action['user_id'] ?? ($payload['user_id'] ?? null);
        $title   = $this->interpolate($action['title'] ?? 'Notificação', $payload);
        $message = $this->interpolate($action['message'] ?? '', $payload);
        $tenantId = $payload['tenant_id'] ?? ($_SESSION['tenant_id'] ?? 0);

        if (!$userId) return;

        $stmt = $this->db->prepare(
            "INSERT INTO notifications (tenant_id, user_id, title, message, type, created_at)
             VALUES (:tid, :uid, :title, :msg, :type, NOW())"
        );
        $stmt->execute([
            ':tid'   => $tenantId,
            ':uid'   => $userId,
            ':title' => $title,
            ':msg'   => $message,
            ':type'  => $action['notify_type'] ?? 'info',
        ]);
    }

    private function actionEmail(array $action, array $payload): void
    {
        $to      = $this->interpolate($action['to'] ?? '', $payload);
        $subject = $this->interpolate($action['subject'] ?? '', $payload);
        $body    = $this->interpolate($action['body'] ?? '', $payload);

        if (!$to || !$subject) return;

        @mail($to, $subject, $body, "Content-Type: text/html; charset=UTF-8\r\nFrom: noreply@akti.com.br");
    }

    private function actionUpdateField(array $action, array $payload): void
    {
        $table  = preg_replace('/[^a-z0-9_]/', '', $action['table'] ?? '');
        $column = preg_replace('/[^a-z0-9_]/', '', $action['column'] ?? '');
        $value  = $action['value'] ?? null;
        $id     = $payload['id'] ?? 0;

        if (!$table || !$column || !$id) return;

        $stmt = $this->db->prepare(
            "UPDATE `{$table}` SET `{$column}` = :val WHERE id = :id"
        );
        $stmt->execute([':val' => $value, ':id' => $id]);
    }

    private function actionLog(array $action, array $payload): void
    {
        $message = $this->interpolate($action['message'] ?? '', $payload);
        $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/workflow.log';
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    }

    /**
     * Replace {{field}} placeholders with payload values.
     */
    private function interpolate(string $template, array $payload): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($payload) {
            return (string) ($payload[$m[1]] ?? '');
        }, $template);
    }
}
