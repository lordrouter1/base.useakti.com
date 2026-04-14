<?php

namespace Akti\Controllers;

use Akti\Models\Shipment;
use Akti\Utils\Input;

class ShipmentController extends BaseController
{
    private Shipment $shipmentModel;

    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->shipmentModel = new Shipment($db);
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

        $result = $this->shipmentModel->readPaginated($tenantId, $page, 15, $filters);
        $shipments = $result['data'];
        $pagination = $result;

        require 'app/views/layout/header.php';
        require 'app/views/shipments/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $this->requireAuth();
        $shipment = null;
        $carriers = $this->shipmentModel->getCarriers($this->getTenantId());

        require 'app/views/layout/header.php';
        require 'app/views/shipments/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'       => $this->getTenantId(),
            'order_id'        => Input::post('order_id', 'int', 0),
            'carrier_id'      => Input::post('carrier_id', 'int', 0) ?: null,
            'tracking_code'   => Input::post('tracking_code', 'string', ''),
            'shipping_method' => Input::post('shipping_method', 'string', ''),
            'shipping_cost'   => Input::post('shipping_cost', 'string', '0.00'),
            'estimated_date'  => Input::post('estimated_date', 'string', '') ?: null,
            'notes'           => Input::post('notes', 'string', ''),
        ];

        if (empty($data['order_id'])) {
            $_SESSION['flash_error'] = 'O pedido é obrigatório.';
            header('Location: ?page=shipments&action=create');
            return;
        }

        $id = $this->shipmentModel->create($data);
        $_SESSION['flash_success'] = 'Remessa criada com sucesso.';
        header('Location: ?page=shipments&action=view&id=' . $id);
    }

    public function view()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $shipment = $this->shipmentModel->readOne($id);
        if (!$shipment) {
            $_SESSION['flash_error'] = 'Remessa não encontrada.';
            header('Location: ?page=shipments');
            return;
        }
        $events = $this->shipmentModel->getEvents($id);

        require 'app/views/layout/header.php';
        require 'app/views/shipments/view.php';
        require 'app/views/layout/footer.php';
    }

    public function addEvent()
    {
        $this->requireAuth();
        $shipmentId = Input::post('shipment_id', 'int', 0);
        $data = [
            'shipment_id' => $shipmentId,
            'status'      => Input::post('status', 'string', ''),
            'location'    => Input::post('location', 'string', ''),
            'description' => Input::post('description', 'string', ''),
        ];

        $this->shipmentModel->addEvent($data);

        $newStatus = Input::post('update_shipment_status', 'string', '');
        if ($newStatus) {
            $this->shipmentModel->updateStatus($shipmentId, $newStatus);
        }

        $_SESSION['flash_success'] = 'Evento registrado.';
        header('Location: ?page=shipments&action=view&id=' . $shipmentId);
    }

    public function carriers()
    {
        $this->requireAuth();
        $carriers = $this->shipmentModel->getCarriers($this->getTenantId());

        require 'app/views/layout/header.php';
        require 'app/views/shipments/carriers.php';
        require 'app/views/layout/footer.php';
    }

    public function saveCarrier()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'    => $this->getTenantId(),
            'name'         => Input::post('name', 'string', ''),
            'code'         => Input::post('code', 'string', ''),
            'tracking_url' => Input::post('tracking_url', 'string', ''),
            'phone'        => Input::post('phone', 'string', ''),
            'is_active'    => Input::post('is_active', 'int', 1),
        ];

        $id = Input::post('id', 'int', 0);
        if ($id) {
            $data['id'] = $id;
        }

        $this->shipmentModel->saveCarrier($data);
        $_SESSION['flash_success'] = 'Transportadora salva.';
        header('Location: ?page=shipments&action=carriers');
    }

    public function dashboard()
    {
        $this->requireAuth();
        $stats = $this->shipmentModel->getDashboardStats($this->getTenantId());

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => $stats]);
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/shipments/dashboard.php';
        require 'app/views/layout/footer.php';
    }

    public function delete()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $this->shipmentModel->delete($id);
        $_SESSION['flash_success'] = 'Remessa removida.';
        header('Location: ?page=shipments');
    }
}
