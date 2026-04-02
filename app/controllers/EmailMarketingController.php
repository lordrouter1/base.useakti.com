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
            'body_html'       => Input::post('body_html', 'string', ''),
            'scheduled_at'    => Input::post('scheduled_at', 'string', '') ?: null,
            'segment_filters' => json_decode(Input::post('segment_filters', 'string', '[]'), true) ?: [],
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
            'body_html'       => Input::post('body_html', 'string', ''),
            'status'          => Input::post('status', 'string', 'draft'),
            'scheduled_at'    => Input::post('scheduled_at', 'string', '') ?: null,
            'segment_filters' => json_decode(Input::post('segment_filters', 'string', '[]'), true) ?: [],
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

    public function storeTemplate()
    {
        $data = [
            'tenant_id'  => $_SESSION['tenant']['id'] ?? 0,
            'name'       => Input::post('name', 'string', ''),
            'subject'    => Input::post('subject', 'string', ''),
            'body_html'  => Input::post('body_html', 'string', ''),
            'body_text'  => Input::post('body_text', 'string', ''),
            'variables'  => json_decode(Input::post('variables', 'string', '[]'), true) ?: [],
            'created_by' => $_SESSION['user_id'] ?? null,
        ];

        $this->model->createTemplate($data);
        $_SESSION['flash_success'] = 'Template criado.';
        header('Location: ?page=email_marketing&action=templates');
    }
}
