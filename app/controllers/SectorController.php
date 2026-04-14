<?php
namespace Akti\Controllers;

use Akti\Models\ProductionSector;
use Akti\Models\Logger;
use Akti\Utils\Input;
use TenantManager;

class SectorController extends BaseController {
    
    private ProductionSector $sectorModel;
    private Logger $logger;
    public function __construct(\PDO $db, ProductionSector $sectorModel, Logger $logger) {
        $this->db = $db;
        $this->sectorModel = $sectorModel;
        $this->logger = $logger;
    }

    public function index() {
        $sectors = $this->sectorModel->readAll();

        // Filtrar setores por permissão (se não admin)
        $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
        $allowedSectorIds = [];
        if (!$isAdmin && isset($_SESSION['user_id'])) {
            $dbPerm = $this->db;
            $userModel = new User($dbPerm);
            $allowedSectorIds = $userModel->getAllowedSectorIds($_SESSION['user_id']);
            if (!empty($allowedSectorIds)) {
                $sectors = array_filter($sectors, function($s) use ($allowedSectorIds) {
                    return in_array($s['id'], $allowedSectorIds);
                });
                $sectors = array_values($sectors);
            }
        }

        // Verificar limite de setores do tenant
        $maxSectors = TenantManager::getTenantLimit('max_sectors');
        $currentSectors = $this->sectorModel->countAll();
        $limitReached = ($maxSectors !== null && $currentSectors >= $maxSectors);
        $limitInfo = $limitReached ? ['current' => $currentSectors, 'max' => $maxSectors] : null;

        $editSector = null;
        if (Input::get('action') === 'edit') {
            $editId = Input::get('id', 'int');
            if ($editId) {
                $editSector = $this->sectorModel->readOne($editId);
            }
        }
        require 'app/views/layout/header.php';
        require 'app/views/sectors/index.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name')) {
            // Verificar limite de setores do tenant
            $maxSectors = TenantManager::getTenantLimit('max_sectors');
            if ($maxSectors !== null) {
                $currentSectors = $this->sectorModel->countAll();
                if ($currentSectors >= $maxSectors) {
                    header('Location: ?page=sectors&status=limit_sectors');
                    exit;
                }
            }

            $this->sectorModel->create($_POST);
            $this->logger->log('CREATE_SECTOR', 'Created sector: ' . Input::post('name'));
        }
        header('Location: ?page=sectors&status=success');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('id')) {
            $this->sectorModel->update($_POST);
            $this->logger->log('UPDATE_SECTOR', 'Updated sector ID: ' . Input::post('id', 'int'));
        }
        header('Location: ?page=sectors&status=success');
        exit;
    }

    public function delete() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->sectorModel->delete($id);
            $this->logger->log('DELETE_SECTOR', 'Deleted sector ID: ' . $id);
        }
        header('Location: ?page=sectors&status=success');
        exit;
    }
}
