<?php

namespace Akti\Controllers;

use Akti\Models\Equipment;
use Akti\Utils\Input;

class EquipmentController extends BaseController
{
    private Equipment $equipmentModel;

    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->equipmentModel = new Equipment($db);
    }

    public function index()
    {
        $this->requireAuth();
        $page = Input::get('p', 'int', 1);
        $filters = [
            'search' => Input::get('search', 'string', ''),
            'status' => Input::get('status', 'string', ''),
        ];
        $filters = array_filter($filters);
        $tenantId = $this->getTenantId();

        $result = $this->equipmentModel->readPaginated($tenantId, $page, 15, $filters);
        $equipments = $result['data'];
        $pagination = $result;

        require 'app/views/layout/header.php';
        require 'app/views/equipment/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $this->requireAuth();
        $equipment = null;

        require 'app/views/layout/header.php';
        require 'app/views/equipment/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'      => $this->getTenantId(),
            'name'           => Input::post('name', 'string', ''),
            'code'           => Input::post('code', 'string', ''),
            'serial_number'  => Input::post('serial_number', 'string', ''),
            'manufacturer'   => Input::post('manufacturer', 'string', ''),
            'model'          => Input::post('model', 'string', ''),
            'location'       => Input::post('location', 'string', ''),
            'purchase_date'  => Input::post('purchase_date', 'string', '') ?: null,
            'warranty_until' => Input::post('warranty_until', 'string', '') ?: null,
            'notes'          => Input::post('notes', 'string', ''),
            'status'         => Input::post('status', 'string', 'active'),
        ];

        if (empty($data['name'])) {
            $_SESSION['flash_error'] = 'O nome do equipamento é obrigatório.';
            header('Location: ?page=equipment&action=create');
            return;
        }

        $this->equipmentModel->create($data);
        $_SESSION['flash_success'] = 'Equipamento cadastrado com sucesso.';
        header('Location: ?page=equipment');
    }

    public function edit()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $equipment = $this->equipmentModel->readOne($id);
        if (!$equipment) {
            $_SESSION['flash_error'] = 'Equipamento não encontrado.';
            header('Location: ?page=equipment');
            return;
        }
        $schedules = $this->equipmentModel->getSchedules($id);

        require 'app/views/layout/header.php';
        require 'app/views/equipment/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $this->requireAuth();
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'           => Input::post('name', 'string', ''),
            'code'           => Input::post('code', 'string', ''),
            'serial_number'  => Input::post('serial_number', 'string', ''),
            'manufacturer'   => Input::post('manufacturer', 'string', ''),
            'model'          => Input::post('model', 'string', ''),
            'location'       => Input::post('location', 'string', ''),
            'purchase_date'  => Input::post('purchase_date', 'string', '') ?: null,
            'warranty_until' => Input::post('warranty_until', 'string', '') ?: null,
            'notes'          => Input::post('notes', 'string', ''),
            'status'         => Input::post('status', 'string', 'active'),
        ];

        $this->equipmentModel->update($id, $data);
        $_SESSION['flash_success'] = 'Equipamento atualizado com sucesso.';
        header('Location: ?page=equipment');
    }

    public function delete()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $this->equipmentModel->delete($id);
        $_SESSION['flash_success'] = 'Equipamento removido.';
        header('Location: ?page=equipment');
    }

    public function schedules()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $equipment = $this->equipmentModel->readOne($id);
        if (!$equipment) {
            $_SESSION['flash_error'] = 'Equipamento não encontrado.';
            header('Location: ?page=equipment');
            return;
        }
        $schedules = $this->equipmentModel->getSchedules($id);

        require 'app/views/layout/header.php';
        require 'app/views/equipment/schedules.php';
        require 'app/views/layout/footer.php';
    }

    public function storeSchedule()
    {
        $this->requireAuth();
        $equipmentId = Input::post('equipment_id', 'int', 0);
        $data = [
            'equipment_id'      => $equipmentId,
            'tenant_id'         => $this->getTenantId(),
            'maintenance_type'  => Input::post('maintenance_type', 'string', 'preventive'),
            'description'       => Input::post('description', 'string', ''),
            'frequency_days'    => Input::post('frequency_days', 'int', 30),
            'next_due_date'     => Input::post('next_due_date', 'string', ''),
            'assigned_to'       => Input::post('assigned_to', 'int', 0) ?: null,
        ];

        $this->equipmentModel->createSchedule($data);
        $_SESSION['flash_success'] = 'Agendamento criado.';
        header('Location: ?page=equipment&action=schedules&id=' . $equipmentId);
    }

    public function storeLog()
    {
        $this->requireAuth();
        $equipmentId = Input::post('equipment_id', 'int', 0);
        $scheduleId = Input::post('schedule_id', 'int', 0) ?: null;
        $data = [
            'equipment_id'  => $equipmentId,
            'schedule_id'   => $scheduleId,
            'tenant_id'     => $this->getTenantId(),
            'performed_by'  => $_SESSION['user_id'] ?? 0,
            'performed_at'  => Input::post('performed_at', 'string', date('Y-m-d H:i:s')),
            'description'   => Input::post('description', 'string', ''),
            'cost'          => Input::post('cost', 'string', '0.00'),
            'next_due_date' => Input::post('next_due_date', 'string', '') ?: null,
        ];

        $this->equipmentModel->createLog($data);
        $_SESSION['flash_success'] = 'Manutenção registrada.';
        header('Location: ?page=equipment&action=edit&id=' . $equipmentId);
    }

    public function dashboard()
    {
        $this->requireAuth();
        $stats = $this->equipmentModel->getDashboardStats($this->getTenantId());
        $upcoming = $this->equipmentModel->getUpcomingMaintenance($this->getTenantId(), 14);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => compact('stats', 'upcoming')]);
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/equipment/dashboard.php';
        require 'app/views/layout/footer.php';
    }
}
