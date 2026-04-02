<?php

namespace Akti\Controllers;

use Akti\Models\EmailCampaign;
use Akti\Utils\Input;
use Database;
use PDO;

class EmailMarketingController
{
    private PDO $db;
    private EmailCampaign $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new EmailCampaign($this->db);
    }

    public function index()
    {
        $page = Input::get('p', 'int', 1);
        $search = Input::get('search', 'string', '');
        $result = $this->model->readPaginated($page, 15, $search);
        $campaigns = $result['data'];
        $pagination = $result;

        require 'app/views/layout/header.php';
        require 'app/views/email_marketing/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $templates = $this->model->getTemplates();
        $campaign = null;

        require 'app/views/layout/header.php';
        require 'app/views/email_marketing/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'tenant_id'       => $_SESSION['tenant']['id'] ?? 0,
            'template_id'     => Input::post('template_id', 'int', 0) ?: null,
            'name'            => Input::post('name', 'string', ''),
            'subject'         => Input::post('subject', 'string', ''),
            'body_html'       => $_POST['body_html'] ?? '',
            'scheduled_at'    => Input::post('scheduled_at', 'string', '') ?: null,
            'segment_filters' => json_decode(Input::post('segment_filters', 'string', '{}'), true) ?: [],
            'created_by'      => $_SESSION['user_id'] ?? null,
        ];

        $id = $this->model->create($data);
        $_SESSION['flash_success'] = 'Campanha criada com sucesso.';
        header('Location: ?page=email_marketing');
    }

    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $campaign = $this->model->readOne($id);
        if (!$campaign) {
            $_SESSION['flash_error'] = 'Campanha não encontrada.';
            header('Location: ?page=email_marketing');
            return;
        }
        $templates = $this->model->getTemplates();
        $stats = $this->model->getStats($id);

        require 'app/views/layout/header.php';
        require 'app/views/email_marketing/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'            => Input::post('name', 'string', ''),
            'subject'         => Input::post('subject', 'string', ''),
            'body_html'       => $_POST['body_html'] ?? '',
            'status'          => Input::post('status', 'string', 'draft'),
            'scheduled_at'    => Input::post('scheduled_at', 'string', '') ?: null,
            'segment_filters' => json_decode(Input::post('segment_filters', 'string', '{}'), true) ?: [],
        ];

        $this->model->update($id, $data);
        $_SESSION['flash_success'] = 'Campanha atualizada.';
        header('Location: ?page=email_marketing&action=edit&id=' . $id);
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->delete($id);
        $_SESSION['flash_success'] = 'Campanha removida.';
        header('Location: ?page=email_marketing');
    }

    public function templates()
    {
        $templates = $this->model->getTemplates();

        require 'app/views/layout/header.php';
        require 'app/views/email_marketing/templates.php';
        require 'app/views/layout/footer.php';
    }

    public function createTemplate()
    {
        $template = null;

        require 'app/views/layout/header.php';
        require 'app/views/email_marketing/template_form.php';
        require 'app/views/layout/footer.php';
    }

    public function storeTemplate()
    {
        $variables = array_filter(array_map('trim', explode(',', Input::post('variables', 'string', ''))));

        $data = [
            'tenant_id'  => $_SESSION['tenant']['id'] ?? 0,
            'name'       => Input::post('name', 'string', ''),
            'subject'    => Input::post('subject', 'string', ''),
            'body_html'  => $_POST['body_html'] ?? '',
            'body_text'  => Input::post('body_text', 'string', ''),
            'variables'  => $variables,
            'created_by' => $_SESSION['user_id'] ?? null,
        ];

        $this->model->createTemplate($data);
        $_SESSION['flash_success'] = 'Template criado com sucesso.';
        header('Location: ?page=email_marketing&action=templates');
    }

    public function editTemplate()
    {
        $id = Input::get('id', 'int', 0);
        $template = $this->model->getTemplate($id);
        if (!$template) {
            $_SESSION['flash_error'] = 'Template não encontrado.';
            header('Location: ?page=email_marketing&action=templates');
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/email_marketing/template_form.php';
        require 'app/views/layout/footer.php';
    }

    public function updateTemplate()
    {
        $id = Input::post('id', 'int', 0);
        $variables = array_filter(array_map('trim', explode(',', Input::post('variables', 'string', ''))));

        $data = [
            'name'      => Input::post('name', 'string', ''),
            'subject'   => Input::post('subject', 'string', ''),
            'body_html' => $_POST['body_html'] ?? '',
            'body_text' => Input::post('body_text', 'string', ''),
            'variables' => $variables,
        ];

        $this->model->updateTemplate($id, $data);
        $_SESSION['flash_success'] = 'Template atualizado.';
        header('Location: ?page=email_marketing&action=templates');
    }

    public function deleteTemplate()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->deleteTemplate($id);
        $_SESSION['flash_success'] = 'Template removido.';
        header('Location: ?page=email_marketing&action=templates');
    }

    public function getTemplateJson()
    {
        $id = Input::get('id', 'int', 0);
        $template = $this->model->getTemplate($id);
        header('Content-Type: application/json');
        if ($template) {
            echo json_encode([
                'success' => true,
                'subject' => $template['subject'] ?? '',
                'body_html' => $template['body_html'] ?? '',
                'variables' => json_decode($template['variables'] ?? '[]', true) ?: [],
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    public function searchCustomers()
    {
        $term = Input::get('term', 'string', '');
        $limit = 10;

        $where = "WHERE deleted_at IS NULL AND status = 'active'";
        if ($term !== '') {
            $where .= " AND (name LIKE :term OR email LIKE :term2 OR CAST(id AS CHAR) LIKE :term3)";
        }
        $sql = "SELECT id, CONCAT(name, ' <', COALESCE(email, ''), '>') AS text
                FROM customers
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

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }
}
