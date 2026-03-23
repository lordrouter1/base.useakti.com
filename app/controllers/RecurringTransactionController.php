<?php
namespace Akti\Controllers;

use Akti\Models\RecurringTransaction;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Database;
use PDO;

/**
 * RecurringTransactionController — CRUD + processamento de recorrências.
 *
 * Endpoints AJAX chamados pela seção "Recorrências" da página unificada financeira.
 *
 * @package Akti\Controllers
 */
class RecurringTransactionController
{
    private PDO $db;
    private RecurringTransaction $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new RecurringTransaction($this->db);
    }

    /**
     * Lista todas as recorrências (JSON).
     */
    public function list()
    {
        if (!RecurringTransaction::tableExists($this->db)) {
            header('Content-Type: application/json');
            echo json_encode(['data' => [], 'summary' => ['entradas' => 0, 'saidas' => 0, 'saldo' => 0]]);
            exit;
        }

        $items = $this->model->readAll();
        $summary = $this->model->getMonthlySummary();

        // Mapear categorias
        $categories = \Akti\Models\Financial::getCategories();
        $allCats = array_merge($categories['entrada'] ?? [], $categories['saida'] ?? [], \Akti\Models\Financial::getInternalCategories());

        foreach ($items as &$item) {
            $item['category_name'] = $allCats[$item['category']] ?? $item['category'];
            $item['next_generation'] = $this->getNextGenerationDate($item);
        }
        unset($item);

        header('Content-Type: application/json');
        echo json_encode(['data' => $items, 'summary' => $summary]);
        exit;
    }

    /**
     * Cria nova recorrência (POST JSON).
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $data = [
            'type'           => $input['type'] ?? 'saida',
            'category'       => $input['category'] ?? 'outra_saida',
            'description'    => trim($input['description'] ?? ''),
            'amount'         => (float) ($input['amount'] ?? 0),
            'due_day'        => (int) ($input['due_day'] ?? 10),
            'payment_method' => $input['payment_method'] ?? null,
            'notes'          => trim($input['notes'] ?? ''),
            'start_month'    => $input['start_month'] ?? date('Y-m'),
            'end_month'      => $input['end_month'] ?? null,
            'user_id'        => $_SESSION['user_id'] ?? null,
        ];

        if (empty($data['description']) || $data['amount'] <= 0) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['error' => 'Descrição e valor são obrigatórios.']);
            exit;
        }

        $id = $this->model->create($data);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    /**
     * Atualiza recorrência existente (POST JSON).
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $data = [
            'type'           => $input['type'] ?? 'saida',
            'category'       => $input['category'] ?? 'outra_saida',
            'description'    => trim($input['description'] ?? ''),
            'amount'         => (float) ($input['amount'] ?? 0),
            'due_day'        => (int) ($input['due_day'] ?? 10),
            'payment_method' => $input['payment_method'] ?? null,
            'notes'          => trim($input['notes'] ?? ''),
            'start_month'    => $input['start_month'] ?? date('Y-m'),
            'end_month'      => $input['end_month'] ?? null,
        ];

        $this->model->update($id, $data);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Exclui uma recorrência (POST).
     */
    public function delete()
    {
        $id = Input::get('id', 'int') ?: (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $this->model->delete($id);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Ativa/desativa recorrência (POST).
     */
    public function toggle()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int) ($input['id'] ?? 0);
        $active = (bool) ($input['active'] ?? false);

        $this->model->toggleActive($id, $active);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Processa recorrências pendentes do mês (POST).
     */
    public function process()
    {
        if (!RecurringTransaction::tableExists($this->db)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Tabela de recorrências não encontrada.']);
            exit;
        }

        $result = $this->model->processMonth($_SESSION['user_id'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    /**
     * Busca uma recorrência por ID (GET).
     */
    public function get()
    {
        $id = Input::get('id', 'int');

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID obrigatório']);
            exit;
        }

        $item = $this->model->getById($id);

        header('Content-Type: application/json');
        echo json_encode($item ?: ['error' => 'Não encontrado']);
        exit;
    }

    // ═══════════════════════════════════════════

    /**
     * Calcula próxima data de geração.
     */
    private function getNextGenerationDate(array $rec): ?string
    {
        if (!$rec['is_active']) return null;

        $currentMonth = date('Y-m-01');

        if ($rec['last_generated_month'] && $rec['last_generated_month'] >= $currentMonth) {
            // Já gerada neste mês — próxima é mês que vem
            return date('Y-m', strtotime($currentMonth . ' +1 month'));
        }

        if ($rec['start_month'] > $currentMonth) {
            return date('Y-m', strtotime($rec['start_month']));
        }

        return date('Y-m');
    }
}
