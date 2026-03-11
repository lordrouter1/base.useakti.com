<?php
namespace Akti\Controllers;

use Akti\Models\Customer;
use Akti\Models\PriceTable;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use PDO;
use TenantManager;

class CustomerController {
    
    private $customerModel;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->customerModel = new Customer($this->db);
    }

    public function index() {
        $stmt = $this->customerModel->readAll();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require 'app/views/layout/header.php';
        require 'app/views/customers/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        $priceTableModel = new PriceTable($this->db);
        $priceTables = $priceTableModel->readAll();
        
        require 'app/views/layout/header.php';
        require 'app/views/customers/create.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $photoPath = $this->handlePhotoUpload();

            $address = json_encode([
                'zipcode' => Input::post('zipcode'),
                'address_type' => Input::post('address_type'),
                'address_name' => Input::post('address_name'),
                'address_number' => Input::post('address_number'),
                'neighborhood' => Input::post('neighborhood'),
                'complement' => Input::post('complement')
            ]);
            
            $name = Input::post('name');
            $email = Input::post('email', 'email');
            $phone = Input::post('phone', 'phone');
            $document = Input::post('document', 'document');
            $priceTableId = Input::post('price_table_id', 'int');

            $v = new Validator();
            $v->required('name', $name, 'Nome')
              ->maxLength('name', $name, 191, 'Nome');

            if ($v->fails()) {
                $_SESSION['errors'] = $v->errors();
                $_SESSION['old'] = $_POST;
                header('Location: ?page=customers&action=create');
                exit;
            }

            $this->customerModel->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'document' => $document,
                'address' => $address,
                'photo' => $photoPath,
                'price_table_id' => $priceTableId
            ]);
            
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    public function edit() {
        $id = Input::get('id', 'int');
        if (!$id) {
            header('Location: ?page=customers');
            exit;
        }
        
        $customer = $this->customerModel->readOne($id);
        if (!$customer) {
            header('Location: ?page=customers');
            exit;
        }

        // Decode address JSON for the form
        $customer['address_data'] = json_decode($customer['address'] ?? '{}', true) ?: [];
        
        $priceTableModel = new PriceTable($this->db);
        $priceTables = $priceTableModel->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/customers/edit.php';
        require 'app/views/layout/footer.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $photoPath = $this->handlePhotoUpload();

            $address = json_encode([
                'zipcode' => Input::post('zipcode'),
                'address_type' => Input::post('address_type'),
                'address_name' => Input::post('address_name'),
                'address_number' => Input::post('address_number'),
                'neighborhood' => Input::post('neighborhood'),
                'complement' => Input::post('complement')
            ]);
            
            $id = Input::post('id', 'int');
            $name = Input::post('name');
            $email = Input::post('email', 'email');
            $phone = Input::post('phone', 'phone');
            $document = Input::post('document', 'document');
            $priceTableId = Input::post('price_table_id', 'int');

            $v = new Validator();
            $v->required('id', $id, 'ID')
              ->required('name', $name, 'Nome')
              ->maxLength('name', $name, 191, 'Nome');

            if ($v->fails()) {
                $_SESSION['errors'] = $v->errors();
                header('Location: ?page=customers&action=edit&id=' . $id);
                exit;
            }

            $this->customerModel->update([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'document' => $document,
                'address' => $address,
                'photo' => $photoPath,
                'price_table_id' => $priceTableId
            ]);
            
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    public function delete() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->customerModel->delete($id);
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    private function handlePhotoUpload() {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 5 * 1024 * 1024;
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $fileType = mime_content_type($_FILES['photo']['tmp_name']);
            
            if ($_FILES['photo']['size'] > $maxSize || !in_array($fileType, $allowedTypes)) {
                return null;
            }

            $uploadDir = TenantManager::getTenantUploadBase() . 'customers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                return $targetFile;
            }
        }
        return null;
    }
}
