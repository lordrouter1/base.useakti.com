<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Core\ModuleBootloader;
use Akti\Services\TransactionService;
use Akti\Utils\Input;
use Database;
use PDO;

/**
 * TransactionController — Controller dedicado a transações financeiras (entradas/saídas).
 *
 * Extraído do FinancialController (God Controller) na Fase 2
 * para responsabilidade única e manutenibilidade.
 *
 * Ações:
 *   - index()          → Redireciona para payments com section=transactions
 *   - add()            → Adicionar nova transação (POST)
 *   - delete()         → Remover transação (POST)
 *   - get()            → AJAX: buscar transação por ID
 *   - update()         → AJAX: atualizar transação (POST)
 *   - getPaginated()   → AJAX: lista paginada com filtros
 *   - getSummaryJson() → AJAX: resumo por mês/ano
 *
 * @package Akti\Controllers
 */
class TransactionController
{
    private PDO $db;
    private TransactionService $transactionService;

    public function __construct()
    {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo financeiro desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();

        $financial = new Financial($this->db);
        $this->transactionService = new TransactionService($this->db, $financial);
    }

    // ═══════════════════════════════════════════
    // Redirecionamento legado (mantém compatibilidade)
    // ═══════════════════════════════════════════

    public function index()
    {
        header('Location: ?page=financial&action=payments&section=transactions');
        exit;
    }

    // ═══════════════════════════════════════════
    // Adicionar transação
    // ═══════════════════════════════════════════

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments&section=transactions');
            exit;
        }

        $type = Input::post('type', 'enum', 'entrada', ['entrada', 'saida']);
        $data = [
            'type'             => $type,
            'category'         => Input::post('category', 'string', $type === 'entrada' ? 'outra_entrada' : 'outra_saida'),
            'description'      => Input::post('description'),
            'amount'           => Input::post('amount', 'float', 0),
            'transaction_date' => Input::post('transaction_date', 'date') ?: date('Y-m-d'),
            'payment_method'   => Input::post('payment_method'),
            'is_confirmed'     => 1,
            'user_id'          => $_SESSION['user_id'] ?? null,
            'notes'            => Input::post('notes'),
        ];

        $this->transactionService->addTransaction($data);

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true]);
        }

        $_SESSION['flash_success'] = 'Transação registrada com sucesso!';
        header('Location: ?page=financial&action=payments&section=transactions');
        exit;
    }

    // ═══════════════════════════════════════════
    // Remover transação
    // ═══════════════════════════════════════════

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments&section=transactions');
            exit;
        }

        $id = Input::post('transaction_id', 'int', 0);
        $this->transactionService->delete($id);

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true]);
        }

        $_SESSION['flash_success'] = 'Transação removida.';
        header('Location: ?page=financial&action=payments&section=transactions');
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: buscar transação por ID
    // ═══════════════════════════════════════════

    public function get()
    {
        header('Content-Type: application/json');

        $id = Input::get('id', 'int', 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $tx = $this->transactionService->getById($id);
        if (!$tx) {
            echo json_encode(['success' => false, 'message' => 'Transação não encontrada.']);
            exit;
        }

        echo json_encode(['success' => true, 'transaction' => $tx]);
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: atualizar transação
    // ═══════════════════════════════════════════

    public function update()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $id = Input::post('transaction_id', 'int', 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID da transação inválido.']);
            exit;
        }

        $tx = $this->transactionService->getById($id);
        if (!$tx) {
            echo json_encode(['success' => false, 'message' => 'Transação não encontrada.']);
            exit;
        }

        $type = Input::post('type', 'enum', 'entrada', ['entrada', 'saida', 'registro']);
        $data = [
            'type'             => $type,
            'category'         => Input::post('category', 'string', $type === 'entrada' ? 'outra_entrada' : 'outra_saida'),
            'description'      => Input::post('description'),
            'amount'           => Input::post('amount', 'float', 0),
            'transaction_date' => Input::post('transaction_date', 'date') ?: date('Y-m-d'),
            'payment_method'   => Input::post('payment_method'),
            'notes'            => Input::post('notes'),
        ];

        if ($data['amount'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'O valor deve ser maior que zero.']);
            exit;
        }

        if (empty($data['description'])) {
            echo json_encode(['success' => false, 'message' => 'A descrição é obrigatória.']);
            exit;
        }

        $this->transactionService->update($id, $data);

        echo json_encode(['success' => true, 'message' => 'Transação atualizada com sucesso!']);
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: lista paginada com filtros
    // ═══════════════════════════════════════════

    public function getPaginated()
    {
        header('Content-Type: application/json');

        $filters = [];
        if (!empty(Input::get('type')))      $filters['type']      = Input::get('type');
        if (!empty(Input::get('month')))     $filters['month']     = Input::get('month', 'int');
        if (!empty(Input::get('year')))      $filters['year']      = Input::get('year', 'int');
        if (!empty(Input::get('category')))  $filters['category']  = Input::get('category');
        if (!empty(Input::get('search')))    $filters['search']    = Input::get('search');
        if (!empty(Input::get('date_from'))) $filters['date_from'] = Input::get('date_from', 'date');
        if (!empty(Input::get('date_to')))   $filters['date_to']   = Input::get('date_to', 'date');

        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $result = $this->transactionService->getPaginated($filters, $page, $perPage);

        echo json_encode([
            'success'       => true,
            'items'         => $result['data'],
            'total'         => $result['total'],
            'page'          => $result['page'],
            'per_page'      => $result['perPage'],
            'total_pages'   => $result['totalPages'],
            'totalEntradas' => $result['totalEntradas'],
            'totalSaidas'   => $result['totalSaidas'],
        ]);
        exit;
    }

    // ═══════════════════════════════════════════
    // Helpers privados
    // ═══════════════════════════════════════════

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
