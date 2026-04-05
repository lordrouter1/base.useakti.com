<?php

namespace Akti\Controllers;

use Akti\Models\EmailCampaign;
use Akti\Services\EmailService;
use Akti\Utils\Input;

class EmailMarketingController
{
    private \PDO $db;
    private EmailCampaign $model;

    public function __construct(\PDO $db, EmailCampaign $model)
    {
        $this->db = $db;
        $this->model = $model;
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

        // Auto-set status to scheduled if date is set
        if (!empty($data['scheduled_at'])) {
            $data['status'] = 'scheduled';
        }

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

        // Auto-set status based on scheduled_at
        if (!empty($data['scheduled_at']) && $data['status'] === 'draft') {
            $data['status'] = 'scheduled';
        } elseif (empty($data['scheduled_at']) && $data['status'] === 'scheduled') {
            $data['status'] = 'draft';
        }

        $this->model->update($id, $data);
        $_SESSION['flash_success'] = 'Campanha atualizada.';
        header('Location: ?page=email_marketing');
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

    public function previewTemplate()
    {
        $id = Input::get('id', 'int', 0);
        $template = $this->model->getTemplate($id);
        if (!$template) {
            http_response_code(404);
            echo 'Template não encontrado.';
            exit;
        }

        $html = $this->renderPreview($template['body_html'] ?? '', $template['subject'] ?? '');
        echo $html;
        exit;
    }

    public function previewCampaign()
    {
        $id = Input::get('id', 'int', 0);
        $campaign = $this->model->readOne($id);
        if (!$campaign) {
            http_response_code(404);
            echo 'Campanha não encontrada.';
            exit;
        }

        $html = $this->renderPreview($campaign['body_html'] ?? '', $campaign['subject'] ?? '');
        echo $html;
        exit;
    }

    private function renderPreview(string $bodyHtml, string $subject): string
    {
        $sampleVars = [
            '{{nome}}'      => 'João Silva',
            '{{email}}'     => 'joao@email.com',
            '{{telefone}}'  => '(11) 99999-0000',
            '{{documento}}' => '123.456.789-00',
            '{{cidade}}'    => 'São Paulo',
            '{{estado}}'    => 'SP',
            '{{empresa}}'   => $_SESSION['tenant']['company_name'] ?? 'Sua Empresa',
        ];

        $previewBody = str_replace(array_keys($sampleVars), array_values($sampleVars), $bodyHtml);
        $previewSubject = str_replace(array_keys($sampleVars), array_values($sampleVars), $subject);

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($previewSubject, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:20px;background:#f5f5f5;}'
            . '.email-wrapper{max-width:700px;margin:0 auto;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);overflow:hidden;}'
            . '.email-header{background:#4a90d9;color:#fff;padding:15px 20px;font-size:14px;}'
            . '.email-body{padding:20px 25px;line-height:1.6;color:#333;}'
            . '.preview-banner{background:#fff3cd;color:#856404;text-align:center;padding:8px;font-size:12px;border-bottom:1px solid #ffc107;}'
            . '</style></head><body>'
            . '<div class="preview-banner"><strong>PREVIEW</strong> — Variáveis substituídas por dados de exemplo</div>'
            . '<div class="email-wrapper">'
            . '<div class="email-header"><strong>Assunto:</strong> ' . htmlspecialchars($previewSubject, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<div class="email-body">' . $previewBody . '</div>'
            . '</div></body></html>';
    }

    public function sendCampaign()
    {
        $id = Input::get('id', 'int', 0);

        $campaign = $this->model->readOne($id);
        if (!$campaign) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Campanha não encontrada.']);
            exit;
        }

        $emailService = new EmailService($this->db);
        $result = $emailService->sendCampaign($id);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function sendTest()
    {
        $id = Input::get('id', 'int', 0);
        $testEmail = Input::get('email', 'string', '');

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'E-mail de teste inválido.']);
            exit;
        }

        $emailService = new EmailService($this->db);
        $result = $emailService->sendTest($id, $testEmail);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
