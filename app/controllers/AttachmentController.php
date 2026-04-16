<?php

namespace Akti\Controllers;

use Akti\Models\Attachment;
use Akti\Services\FileManager;
use Akti\Utils\Input;
use PDO;

/**
 * Class AttachmentController.
 */
class AttachmentController extends BaseController
{
    private Attachment $model;

    /**
     * Construtor da classe AttachmentController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param Attachment $model Model
     */
    public function __construct(\PDO $db, Attachment $model)
    {
        $this->db = $db;
        $this->model = $model;
    }

    /**
     * Exibe a página de listagem.
     */
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

    /**
     * Processa upload de arquivo.
     */
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

        $fileManager = new FileManager($this->db);
        $result = $fileManager->upload($_FILES['file'], 'attachments', [
            'subdirectory' => 'attachments/' . $entityType,
            'prefix'       => 'att',
            'entityType'   => $entityType,
            'entityId'     => $entityId,
        ]);

        if (!$result['success']) {
            $this->json(['success' => false, 'message' => $result['error'] ?? 'Erro ao salvar arquivo']);
            return;
        }

        $tenantId = $_SESSION['tenant']['id'] ?? 0;
        $id = $this->model->create([
            'tenant_id'     => $tenantId,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'filename'      => $result['stored_name'],
            'original_name' => $result['original_name'],
            'path'          => $result['path'],
            'mime_type'     => $result['mime_type'],
            'size'          => $result['size'],
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

    /**
     * Gera download de arquivo.
     */
    public function download()
    {
        $id = Input::get('id', 'int', 0);
        $attachment = $this->model->readOne($id);

        if (!$attachment) {
            http_response_code(404);
            echo 'Arquivo não encontrado';
            return;
        }

        $fileManager = new FileManager($this->db);
        $fileManager->download($attachment['path'], $attachment['original_name']);
    }

    /**
     * Remove um registro pelo ID.
     */
    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $attachment = $this->model->readOne($id);

        if ($attachment && !empty($attachment['path'])) {
            $fileManager = new FileManager($this->db);
            $fileManager->delete($attachment['path']);
        }

        $this->model->delete($id);
        $this->json(['success' => true, 'message' => 'Anexo removido']);
    }

    /**
     * Lista registros filtrados por critério.
     */
    public function listByEntity()
    {
        $entityType = Input::get('entity_type', 'string', '');
        $entityId = Input::get('entity_id', 'int', 0);
        $attachments = $this->model->readByEntity($entityType, $entityId);
        $this->json(['success' => true, 'data' => $attachments]);
    }

    /**
     * Search entities.
     */
    public function searchEntities()
    {
        $type = Input::get('type', 'string', '');
        $term = Input::get('term', 'string', '');
        $limit = 10;

        $results = [];

        switch ($type) {
            case 'order':
                $where = "";
                if ($term !== '') {
                    $where = "WHERE (CAST(o.id AS CHAR) LIKE :term OR c.name LIKE :term2 OR o.pipeline_stage LIKE :term3)";
                }
                $sql = "SELECT o.id, CONCAT('#', o.id, '(', COALESCE(o.pipeline_stage, ''), ') - ', COALESCE(c.name, '')) AS text
                        FROM orders o
                        LEFT JOIN customers c ON c.id = o.customer_id
                        {$where}
                        ORDER BY o.id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                if ($term !== '') {
                    $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term3', "%{$term}%", PDO::PARAM_STR);
                }
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'customer':
                $where = "WHERE deleted_at IS NULL AND status = 'active'";
                if ($term !== '') {
                    $where .= " AND (name LIKE :term OR CAST(id AS CHAR) LIKE :term2)";
                }
                $sql = "SELECT id, name AS text
                        FROM customers
                        {$where}
                        ORDER BY id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                if ($term !== '') {
                    $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                }
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'product':
                $where = "";
                if ($term !== '') {
                    $where = "WHERE (name LIKE :term OR sku LIKE :term2 OR CAST(id AS CHAR) LIKE :term3)";
                }
                $sql = "SELECT id, CONCAT(name, ' (', COALESCE(sku, '-'), ')') AS text
                        FROM products
                        {$where}
                        ORDER BY id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                if ($term !== '') {
                    $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term3', "%{$term}%", PDO::PARAM_STR);
                }
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'supplier':
                $where = "WHERE deleted_at IS NULL AND status = 'active'";
                if ($term !== '') {
                    $where .= " AND (company_name LIKE :term OR CAST(id AS CHAR) LIKE :term2)";
                }
                $sql = "SELECT id, company_name AS text
                        FROM suppliers
                        {$where}
                        ORDER BY id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                if ($term !== '') {
                    $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                }
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'quote':
                $where = "WHERE o.status NOT IN ('concluido', 'cancelado')";
                if ($term !== '') {
                    $where .= " AND (CAST(o.id AS CHAR) LIKE :term OR c.name LIKE :term2)";
                }
                $sql = "SELECT o.id, CONCAT('#', o.id, ' - ', COALESCE(c.name, '')) AS text
                        FROM orders o
                        LEFT JOIN customers c ON c.id = o.customer_id
                        {$where}
                        ORDER BY o.id DESC LIMIT :lim";
                $stmt = $this->db->prepare($sql);
                if ($term !== '') {
                    $stmt->bindValue(':term', "%{$term}%", PDO::PARAM_STR);
                    $stmt->bindValue(':term2', "%{$term}%", PDO::PARAM_STR);
                }
                $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }

        $this->json(['results' => $results]);
    }
}
