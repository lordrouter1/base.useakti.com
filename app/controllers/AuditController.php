<?php

namespace Akti\Controllers;

use Akti\Models\AuditLog;
use Akti\Utils\Input;
use Database;
use PDO;

class AuditController
{
    private PDO $db;
    private AuditLog $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new AuditLog($this->db);
    }

    public function index()
    {
        $page = Input::get('p', 'int', 1);
        $filters = [
            'user_id'     => Input::get('user_id', 'int', 0) ?: null,
            'entity_type' => Input::get('entity_type', 'string', ''),
            'action'      => Input::get('action_filter', 'string', ''),
            'date_from'   => Input::get('date_from', 'string', ''),
            'date_to'     => Input::get('date_to', 'string', ''),
            'search'      => Input::get('search', 'string', ''),
        ];
        $filters = array_filter($filters);

        $result = $this->model->readPaginated($page, 50, $filters);
        $logs = $result['data'];
        $pagination = $result;

        $userStmt = $this->db->prepare("SELECT DISTINCT user_id, user_name FROM audit_logs WHERE user_name IS NOT NULL ORDER BY user_name");
        $userStmt->execute();
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        require 'app/views/layout/header.php';
        require 'app/views/audit/index.php';
        require 'app/views/layout/footer.php';
    }

    public function detail()
    {
        $entityType = Input::get('entity_type', 'string', '');
        $entityId = Input::get('entity_id', 'int', 0);

        $logs = $this->model->readByEntity($entityType, $entityId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $logs]);
    }

    public function exportCsv()
    {
        $filters = [
            'entity_type' => Input::get('entity_type', 'string', ''),
            'action'      => Input::get('action_filter', 'string', ''),
            'date_from'   => Input::get('date_from', 'string', ''),
            'date_to'     => Input::get('date_to', 'string', ''),
        ];
        $filters = array_filter($filters);

        $result = $this->model->readPaginated(1, 10000, $filters);
        $logs = $result['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Data', 'Usuário', 'Ação', 'Entidade', 'ID', 'Descrição']);

        foreach ($logs as $log) {
            fputcsv($out, [
                $log['created_at'],
                $log['user_name'],
                $log['action'],
                $log['entity_type'],
                $log['entity_id'],
                $log['description'],
            ]);
        }
        fclose($out);
        exit;
    }
}
