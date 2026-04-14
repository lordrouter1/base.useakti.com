<?php

namespace Akti\Controllers;

use Akti\Models\Achievement;
use Akti\Utils\Input;

class AchievementController extends BaseController
{
    private Achievement $achievementModel;

    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->achievementModel = new Achievement($db);
    }

    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $achievements = $this->achievementModel->readAll($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/achievements/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $this->requireAuth();
        $achievement = null;

        require 'app/views/layout/header.php';
        require 'app/views/achievements/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'   => $this->getTenantId(),
            'name'        => Input::post('name', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'icon'        => Input::post('icon', 'string', 'fas fa-trophy'),
            'category'    => Input::post('category', 'string', 'production'),
            'points'      => Input::post('points', 'int', 10),
            'criteria'    => Input::post('criteria', 'string', ''),
            'is_active'   => Input::post('is_active', 'int', 1),
        ];

        if (empty($data['name'])) {
            $_SESSION['flash_error'] = 'O nome da conquista é obrigatório.';
            header('Location: ?page=achievements&action=create');
            return;
        }

        $this->achievementModel->create($data);
        $_SESSION['flash_success'] = 'Conquista criada com sucesso.';
        header('Location: ?page=achievements');
    }

    public function edit()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $achievement = $this->achievementModel->readOne($id);
        if (!$achievement) {
            $_SESSION['flash_error'] = 'Conquista não encontrada.';
            header('Location: ?page=achievements');
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/achievements/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $this->requireAuth();
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'        => Input::post('name', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'icon'        => Input::post('icon', 'string', 'fas fa-trophy'),
            'category'    => Input::post('category', 'string', 'production'),
            'points'      => Input::post('points', 'int', 10),
            'criteria'    => Input::post('criteria', 'string', ''),
            'is_active'   => Input::post('is_active', 'int', 1),
        ];

        $this->achievementModel->update($id, $data);
        $_SESSION['flash_success'] = 'Conquista atualizada.';
        header('Location: ?page=achievements');
    }

    public function delete()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $this->achievementModel->delete($id);
        $_SESSION['flash_success'] = 'Conquista removida.';
        header('Location: ?page=achievements');
    }

    public function leaderboard()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $leaderboard = $this->achievementModel->getLeaderboard($tenantId, 20);
        $userScore = $this->achievementModel->getUserScore($tenantId, $_SESSION['user_id'] ?? 0);
        $userAchievements = $this->achievementModel->getUserAchievements($tenantId, $_SESSION['user_id'] ?? 0);

        require 'app/views/layout/header.php';
        require 'app/views/achievements/leaderboard.php';
        require 'app/views/layout/footer.php';
    }

    public function award()
    {
        $this->requireAuth();
        $userId = Input::post('user_id', 'int', 0);
        $achievementId = Input::post('achievement_id', 'int', 0);
        $tenantId = $this->getTenantId();

        $result = $this->achievementModel->awardAchievement($tenantId, $userId, $achievementId);

        if ($this->isAjax()) {
            $this->json(['success' => $result]);
            return;
        }

        $_SESSION['flash_success'] = 'Conquista concedida ao usuário.';
        header('Location: ?page=achievements&action=leaderboard');
    }
}
