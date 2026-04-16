<?php
namespace Akti\Controllers;

use Akti\Models\RecurringTransaction;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;

/**
 * RecurringTransactionController — CRUD + processamento de recorrências.
 *
 * Endpoints AJAX chamados pela seção "Recorrências" da página unificada financeira.
 *
 * @package Akti\Controllers
 */
class RecurringTransactionController extends BaseController
{
    private RecurringTransaction $model;

    /**
     * Construtor da classe RecurringTransactionController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param RecurringTransaction $model Model
     */
    public function __construct(\PDO $db, RecurringTransaction $model)
    {
        $this->db = $db;
        $this->model = $model;
    }

    /**
     * Lista todas as recorrências (JSON).
     */
    public function list()
    {
        if (!RecurringTransaction::tableExists($this->db)) {
            $this->json(['data' => [], 'summary' => ['entradas' => 0, 'saidas' => 0, 'saldo' => 0]]);
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
        $this->json(['data' => $items, 'summary' => $summary]);
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
            $this->json(['error' => 'Descrição e valor são obrigatórios.'], 422);
        }

        $id = $this->model->create($data);
        $this->json(['success' => true, 'id' => $id]);
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
            $this->json(['error' => 'ID inválido'], 400);
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
        $this->json(['success' => true]);
    }

    /**
     * Exclui uma recorrência (POST).
     */
    public function delete()
    {
        $id = Input::get('id', 'int') ?: (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['error' => 'ID inválido'], 400);
        }

        $this->model->delete($id);
        $this->json(['success' => true]);
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
        $this->json(['success' => true]);
    }

    /**
     * Processa recorrências pendentes do mês (POST).
     */
    public function process()
    {
        if (!RecurringTransaction::tableExists($this->db)) {
            $this->json(['error' => 'Tabela de recorrências não encontrada.']);
        }

        $result = $this->model->processMonth($_SESSION['user_id'] ?? null);
        $this->json($result);
    }

    /**
     * Busca uma recorrência por ID (GET).
     */
    public function get()
    {
        $id = Input::get('id', 'int');

        if (!$id) {
            $this->json(['error' => 'ID obrigatório'], 400);
        }

        $item = $this->model->getById($id);
        $this->json($item ?: ['error' => 'Não encontrado']);
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
