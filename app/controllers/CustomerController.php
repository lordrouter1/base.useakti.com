<?php
namespace Akti\Controllers;

use Akti\Models\Customer;
use Akti\Models\PriceTable;
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
                'zipcode' => $_POST['zipcode'] ?? '',
                'address_type' => $_POST['address_type'] ?? '',
                'address_name' => $_POST['address_name'] ?? '',
                'address_number' => $_POST['address_number'] ?? '',
                'neighborhood' => $_POST['neighborhood'] ?? '',
                'complement' => $_POST['complement'] ?? ''
            ]);
            
            $this->customerModel->create([
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'document' => $_POST['document'],
                'address' => $address,
                'photo' => $photoPath,
                'price_table_id' => $_POST['price_table_id'] ?? null
            ]);
            
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    public function edit() {
        if (!isset($_GET['id'])) {
            header('Location: ?page=customers');
            exit;
        }
        
        $customer = $this->customerModel->readOne($_GET['id']);
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
                'zipcode' => $_POST['zipcode'] ?? '',
                'address_type' => $_POST['address_type'] ?? '',
                'address_name' => $_POST['address_name'] ?? '',
                'address_number' => $_POST['address_number'] ?? '',
                'neighborhood' => $_POST['neighborhood'] ?? '',
                'complement' => $_POST['complement'] ?? ''
            ]);
            
            $this->customerModel->update([
                'id' => $_POST['id'],
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'document' => $_POST['document'],
                'address' => $address,
                'photo' => $photoPath,
                'price_table_id' => $_POST['price_table_id'] ?? null
            ]);
            
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    public function delete() {
        if (isset($_GET['id'])) {
            $this->customerModel->delete($_GET['id']);
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
