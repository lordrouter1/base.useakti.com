<?php

namespace Akti\Controllers;

use Akti\Services\AiAssistantService;
use Akti\Utils\Input;

class AiAssistantController extends BaseController
{
    private AiAssistantService $ai;

    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->ai = new AiAssistantService($this->db);
    }

    /**
     * Chat widget page (standalone or embedded).
     */
    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $history = $this->ai->getHistory($userId);
        $isConfigured = $this->ai->isConfigured();

        $pageTitle = 'Assistente IA';
        require 'app/views/layout/header.php';
        require 'app/views/ai_assistant/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * AJAX: Send a message to the AI.
     */
    public function send(): void
    {
        $this->requireAuth();

        $message = trim(Input::post('message') ?? '');
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Mensagem vazia.']);
            return;
        }

        $userId = (int) $_SESSION['user_id'];

        // Save user message
        $this->ai->saveMessage($userId, 'user', $message);

        // Build conversation from history
        $history = $this->ai->getHistory($userId, 20);
        $messages = array_map(function ($h) {
            return ['role' => $h['role'], 'content' => $h['content']];
        }, $history);

        $result = $this->ai->chat($messages);

        if ($result['success']) {
            $this->ai->saveMessage($userId, 'assistant', $result['message']);
        }

        $this->json($result);
    }

    /**
     * AJAX: Clear conversation history.
     */
    public function clearHistory(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $this->ai->clearHistory($userId);
        $this->json(['success' => true]);
    }
}
