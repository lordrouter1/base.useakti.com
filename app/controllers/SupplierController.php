<?php

namespace Akti\Controllers;

use Akti\Models\Supplier;
use Akti\Models\PurchaseOrder;
use Akti\Utils\Input;
use Database;
use PDO;

class SupplierController
{
    private PDO $db;
    private Supplier $supplierModel;
    private PurchaseOrder $purchaseModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->supplierModel = new Supplier($this->db);
        $this->purchaseModel = new PurchaseOrder($this->db);
    }

    public function index()
    {
        $page = Input::get('p', 'int', 1);
        $search = Input::get('search', 'string', '');
        $result = $this->supplierModel->readPaginated($page, 15, $search);
        $suppliers = $result['data'];
        $pagination = $result;

        require 'app/views/layout/header.php';
        require 'app/views/suppliers/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $supplier = null;
        require 'app/views/layout/header.php';
        require 'app/views/suppliers/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'tenant_id'          => $_SESSION['tenant']['id'] ?? 0,
            'company_name'       => Input::post('company_name', 'string', ''),
            'trade_name'         => Input::post('trade_name', 'string', ''),
            'document'           => Input::post('document', 'string', ''),
            'state_registration' => Input::post('state_registration', 'string', ''),
            'email'              => Input::post('email', 'string', ''),
            'phone'              => Input::post('phone', 'string', ''),
            'website'            => Input::post('website', 'string', ''),
            'contact_name'       => Input::post('contact_name', 'string', ''),
            'address'            => Input::post('address', 'string', ''),
            'address_number'     => Input::post('address_number', 'string', ''),
            'complement'         => Input::post('complement', 'string', ''),
            'neighborhood'       => Input::post('neighborhood', 'string', ''),
            'city'               => Input::post('city', 'string', ''),
            'state'              => Input::post('state', 'string', ''),
            'zip_code'           => Input::post('zip_code', 'string', ''),
            'notes'              => Input::post('notes', 'string', ''),
            'status'             => Input::post('status', 'string', 'active'),
        ];

        if (empty($data['company_name'])) {
            $_SESSION['flash_error'] = 'Razão social é obrigatória.';
            header('Location: ?page=suppliers&action=create');
            return;
        }

        $this->supplierModel->create($data);
        $_SESSION['flash_success'] = 'Fornecedor cadastrado com sucesso.';
        header('Location: ?page=suppliers');
    }

    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $supplier = $this->supplierModel->readOne($id);
        if (!$supplier) {
            $_SESSION['flash_error'] = 'Fornecedor não encontrado.';
            header('Location: ?page=suppliers');
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/suppliers/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'company_name'       => Input::post('company_name', 'string', ''),
            'trade_name'         => Input::post('trade_name', 'string', ''),
            'document'           => Input::post('document', 'string', ''),
            'state_registration' => Input::post('state_registration', 'string', ''),
            'email'              => Input::post('email', 'string', ''),
            'phone'              => Input::post('phone', 'string', ''),
            'website'            => Input::post('website', 'string', ''),
            'contact_name'       => Input::post('contact_name', 'string', ''),
            'address'            => Input::post('address', 'string', ''),
            'address_number'     => Input::post('address_number', 'string', ''),
            'complement'         => Input::post('complement', 'string', ''),
            'neighborhood'       => Input::post('neighborhood', 'string', ''),
            'city'               => Input::post('city', 'string', ''),
            'state'              => Input::post('state', 'string', ''),
            'zip_code'           => Input::post('zip_code', 'string', ''),
            'notes'              => Input::post('notes', 'string', ''),
            'status'             => Input::post('status', 'string', 'active'),
        ];

        $this->supplierModel->update($id, $data);
        $_SESSION['flash_success'] = 'Fornecedor atualizado com sucesso.';
        header('Location: ?page=suppliers');
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->supplierModel->delete($id);
        $_SESSION['flash_success'] = 'Fornecedor removido com sucesso.';
        header('Location: ?page=suppliers');
    }

    // ──── Ordens de Compra ────

    public function purchases()
    {
        $page = Input::get('p', 'int', 1);
        $filters = [
            'search'      => Input::get('search', 'string', ''),
            'status'      => Input::get('status', 'string', ''),
            'supplier_id' => Input::get('supplier_id', 'int', 0) ?: null,
        ];
        $filters = array_filter($filters);

        $result = $this->purchaseModel->readPaginated($page, 15, $filters);
        $purchases = $result['data'];
        $pagination = $result;
        $allSuppliers = $this->supplierModel->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/suppliers/purchases.php';
        require 'app/views/layout/footer.php';
    }

    public function createPurchase()
    {
        $suppliers = $this->supplierModel->readAll();
        $purchase = null;
        require 'app/views/layout/header.php';
        require 'app/views/suppliers/purchase_form.php';
        require 'app/views/layout/footer.php';
    }

    public function storePurchase()
    {
        $data = [
            'tenant_id'     => $_SESSION['tenant']['id'] ?? 0,
            'supplier_id'   => Input::post('supplier_id', 'int', 0),
            'user_id'       => $_SESSION['user_id'] ?? null,
            'code'          => Input::post('code', 'string', ''),
            'expected_date' => Input::post('expected_date', 'string', '') ?: null,
            'payment_terms' => Input::post('payment_terms', 'string', ''),
            'notes'         => Input::post('notes', 'string', ''),
        ];

        $id = $this->purchaseModel->create($data);
        $_SESSION['flash_success'] = 'Ordem de compra criada com sucesso.';
        header('Location: ?page=suppliers&action=editPurchase&id=' . $id);
    }

    public function editPurchase()
    {
        $id = Input::get('id', 'int', 0);
        $purchase = $this->purchaseModel->readOne($id);
        if (!$purchase) {
            $_SESSION['flash_error'] = 'Ordem não encontrada.';
            header('Location: ?page=suppliers&action=purchases');
            return;
        }
        $suppliers = $this->supplierModel->readAll();
        $items = $this->purchaseModel->getItems($id);

        require 'app/views/layout/header.php';
        require 'app/views/suppliers/purchase_form.php';
        require 'app/views/layout/footer.php';
    }

    public function receivePurchase()
    {
        $id = Input::get('id', 'int', 0);
        $this->purchaseModel->receive($id);
        $_SESSION['flash_success'] = 'Compra recebida com sucesso.';
        header('Location: ?page=suppliers&action=purchases');
    }
}
