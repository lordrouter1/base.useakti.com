<?php

namespace Akti\Controllers;

use Akti\Models\Attachment;
use Akti\Utils\Input;
use Database;
use PDO;

class AttachmentController
{
    private PDO $db;
    private Attachment $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new Attachment($this->db);
    }

    public function index()
    {
        $page = Input::get('p', 'int', 1);
        $entityType = Input::get('entity_type', 'string', '');
        $result = $this->model->readPaginated($page, 20, $entityType);
        $attachments = $result['data'];
        $pagination = $result;

        require 'app/views/layout/header.php';
        require 'app/views/attachments/index.php';
        require 'app/views/layout/footer.php';
    }

    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }

        $entityType = Input::post('entity_type', 'string', 'record');
        $entityId = Input::post('entity_id', 'int', 0);
        $description = Input::post('description', 'string', '');

        if ($entityType !== 'record' && !$entityId) {
            $this->json(['success' => false, 'message' => 'Selecione o registro da entidade']);
            return;
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado']);
            return;
        }

        $file = $_FILES['file'];
        $allowedMimes = [
            'application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv', 'text/plain', 'application/zip',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimes)) {
            $this->json(['success' => false, 'message' => 'Tipo de arquivo não permitido']);
            return;
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $this->json(['success' => false, 'message' => 'Arquivo excede 10MB']);
            return;
        }

        $tenantId = $_SESSION['tenant']['id'] ?? 0;
        $uploadBase = \Akti\Config\TenantManager::getTenantUploadBase();
        $dir = $uploadBase . '/attachments/' . $entityType;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $filename = uniqid('att_') . '.' . $safeExt;
        $path = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            $this->json(['success' => false, 'message' => 'Erro ao salvar arquivo']);
            return;
        }

        $id = $this->model->create([
            'tenant_id'     => $tenantId,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'filename'      => $filename,
            'original_name' => basename($file['name']),
            'path'          => $path,
            'mime_type'     => $mimeType,
            'size'          => $file['size'],
            'uploaded_by'   => $_SESSION['user_id'] ?? null,
            'description'   => $description,
        ]);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'id' => $id, 'message' => 'Arquivo enviado com sucesso']);
            return;
        }

        $_SESSION['flash_success'] = 'Arquivo enviado com sucesso.';
        header('Location: ?page=attachments');
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function download()
    {
        $id = Input::get('id', 'int', 0);
        $attachment = $this->model->readOne($id);

        if (!$attachment || !file_exists($attachment['path'])) {
            http_response_code(404);
            echo 'Arquivo não encontrado';
            return;
        }

        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . filesize($attachment['path']));
        readfile($attachment['path']);
        exit;
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $attachment = $this->model->readOne($id);

        if ($attachment && file_exists($attachment['path'])) {
            unlink($attachment['path']);
        }

        $this->model->delete($id);
        $this->json(['success' => true, 'message' => 'Anexo removido']);
    }

    public function listByEntity()
    {
        $entityType = Input::get('entity_type', 'string', '');
        $entityId = Input::get('entity_id', 'int', 0);
        $attachments = $this->model->readByEntity($entityType, $entityId);
        $this->json(['success' => true, 'data' => $attachments]);
    }

    public function searchEntities()
    {
        $type = Input::get('type', 'string', '');
        $term = Input::get('term', 'string', '');
        $limit = 10;

        $results = [];

        switch ($type) {
            case 'order':
                $sql = "SELECT id, CONCAT('#', id, ' - ', COALESCE(status, '')) AS text
                        FROM orders
                        WHERE (CAST(id AS CHAR) LIKE :term OR status LIKE :term2)
                        ORDER BY id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'customer':
                $sql = "SELECT id, name AS text
                        FROM customers
                        WHERE deleted_at IS NULL AND status = 'active'
                          AND (name LIKE :term OR CAST(id AS CHAR) LIKE :term2)
                        ORDER BY name ASC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'product':
                $sql = "SELECT id, CONCAT(name, ' (', COALESCE(sku, '-'), ')') AS text
                        FROM products
                        WHERE (name LIKE :term OR sku LIKE :term2 OR CAST(id AS CHAR) LIKE :term3)
                        ORDER BY name ASC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':term3', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'supplier':
                $sql = "SELECT id, company_name AS text
                        FROM suppliers
                        WHERE deleted_at IS NULL AND status = 'active'
                          AND (company_name LIKE :term OR CAST(id AS CHAR) LIKE :term2)
                        ORDER BY company_name ASC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'quote':
                $sql = "SELECT id, CONCAT('#', id, ' - ', COALESCE(status, '')) AS text
                        FROM quotes
                        WHERE deleted_at IS NULL
                          AND status IN ('draft', 'sent', 'approved')
                          AND (CAST(id AS CHAR) LIKE :term OR status LIKE :term2)
                        ORDER BY id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }

        $this->json(['results' => $results]);
    }

    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
