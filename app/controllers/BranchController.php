<?php

namespace Akti\Controllers;

use Akti\Models\Branch;
use Akti\Utils\Input;

class BranchController extends BaseController
{
    private Branch $branchModel;

    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->branchModel = new Branch($db);
    }

    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $branches = $this->branchModel->readAll($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/branches/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $this->requireAuth();
        $branch = null;

        require 'app/views/layout/header.php';
        require 'app/views/branches/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'      => $this->getTenantId(),
            'name'           => Input::post('name', 'string', ''),
            'code'           => Input::post('code', 'string', ''),
            'document'       => Input::post('document', 'string', ''),
            'phone'          => Input::post('phone', 'string', ''),
            'email'          => Input::post('email', 'string', ''),
            'address'        => Input::post('address', 'string', ''),
            'city'           => Input::post('city', 'string', ''),
            'state'          => Input::post('state', 'string', ''),
            'zip_code'       => Input::post('zip_code', 'string', ''),
            'is_headquarters' => Input::post('is_headquarters', 'int', 0),
            'is_active'      => Input::post('is_active', 'int', 1),
        ];

        if (empty($data['name'])) {
            $_SESSION['flash_error'] = 'O nome da filial é obrigatório.';
            header('Location: ?page=branches&action=create');
            return;
        }

        $this->branchModel->create($data);
        $_SESSION['flash_success'] = 'Filial cadastrada com sucesso.';
        header('Location: ?page=branches');
    }

    public function edit()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $branch = $this->branchModel->readOne($id);
        if (!$branch) {
            $_SESSION['flash_error'] = 'Filial não encontrada.';
            header('Location: ?page=branches');
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/branches/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $this->requireAuth();
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'           => Input::post('name', 'string', ''),
            'code'           => Input::post('code', 'string', ''),
            'document'       => Input::post('document', 'string', ''),
            'phone'          => Input::post('phone', 'string', ''),
            'email'          => Input::post('email', 'string', ''),
            'address'        => Input::post('address', 'string', ''),
            'city'           => Input::post('city', 'string', ''),
            'state'          => Input::post('state', 'string', ''),
            'zip_code'       => Input::post('zip_code', 'string', ''),
            'is_headquarters' => Input::post('is_headquarters', 'int', 0),
            'is_active'      => Input::post('is_active', 'int', 1),
        ];

        $this->branchModel->update($id, $data);
        $_SESSION['flash_success'] = 'Filial atualizada com sucesso.';
        header('Location: ?page=branches');
    }

    public function delete()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $this->branchModel->delete($id);
        $_SESSION['flash_success'] = 'Filial removida.';
        header('Location: ?page=branches');
    }
}
