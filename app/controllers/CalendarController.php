<?php

namespace Akti\Controllers;

use Akti\Models\CalendarEvent;
use Akti\Utils\Input;
use Database;
use PDO;

class CalendarController
{
    private PDO $db;
    private CalendarEvent $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new CalendarEvent($this->db);
    }

    public function index()
    {
        $upcoming = $this->model->getUpcoming($_SESSION['user_id'] ?? 0, 10);

        require 'app/views/layout/header.php';
        require 'app/views/calendar/index.php';
        require 'app/views/layout/footer.php';
    }

    public function events()
    {
        $start = Input::get('start', 'string', date('Y-m-01'));
        $end = Input::get('end', 'string', date('Y-m-t'));
        $userId = $_SESSION['user_id'] ?? null;

        $events = $this->model->readByRange($start, $end, $userId);

        $formatted = array_map(function ($e) {
            return [
                'id'              => $e['id'],
                'title'           => $e['title'],
                'start'           => $e['start_date'],
                'end'             => $e['end_date'],
                'allDay'          => (bool) $e['all_day'],
                'color'           => $e['color'] ?? '#0d6efd',
                'extendedProps'   => [
                    'type'        => $e['type'],
                    'entity_type' => $e['entity_type'],
                    'entity_id'   => $e['entity_id'],
                    'description' => $e['description'],
                    'completed'   => (bool) $e['completed'],
                ],
            ];
        }, $events);

        header('Content-Type: application/json');
        echo json_encode($formatted);
    }

    public function store()
    {
        $data = [
            'tenant_id'        => $_SESSION['tenant']['id'] ?? 0,
            'user_id'          => $_SESSION['user_id'] ?? null,
            'title'            => Input::post('title', 'string', ''),
            'description'      => Input::post('description', 'string', ''),
            'type'             => Input::post('type', 'string', 'manual'),
            'start_date'       => Input::post('start_date', 'string', ''),
            'end_date'         => Input::post('end_date', 'string', '') ?: null,
            'all_day'          => Input::post('all_day', 'int', 0),
            'color'            => Input::post('color', 'string', '#0d6efd'),
            'reminder_minutes' => Input::post('reminder_minutes', 'int', 0) ?: null,
        ];

        $id = $this->model->create($data);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'title'            => Input::post('title', 'string', ''),
            'description'      => Input::post('description', 'string', ''),
            'type'             => Input::post('type', 'string', 'manual'),
            'start_date'       => Input::post('start_date', 'string', ''),
            'end_date'         => Input::post('end_date', 'string', '') ?: null,
            'all_day'          => Input::post('all_day', 'int', 0),
            'color'            => Input::post('color', 'string', '#0d6efd'),
            'reminder_minutes' => Input::post('reminder_minutes', 'int', 0) ?: null,
            'completed'        => Input::post('completed', 'int', 0),
        ];

        $this->model->update($id, $data);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->delete($id);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function sync()
    {
        $tenantId = $_SESSION['tenant']['id'] ?? 0;
        $ordersSync = $this->model->syncFromOrders($tenantId);
        $installmentsSync = $this->model->syncFromInstallments($tenantId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'synced'  => ['orders' => $ordersSync, 'installments' => $installmentsSync],
        ]);
    }
}
