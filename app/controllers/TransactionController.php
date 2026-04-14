<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Core\ModuleBootloader;
use Akti\Services\TransactionService;
use Akti\Utils\Input;

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
class TransactionController extends BaseController
{
    private \PDO $db;
    private TransactionService $transactionService;

    public function __construct(\PDO $db, TransactionService $transactionService)
    {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo financeiro desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $this->db = $db;
        $this->transactionService = $transactionService;
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

        $id     = Input::post('transaction_id', 'int', 0);
        $reason = Input::post('reason', 'string', '');

        if (!$id) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'ID da transação inválido.']);
            }
            header('Location: ?page=financial&action=payments&section=transactions');
            exit;
        }

        $result = $this->transactionService->delete($id, $reason ?: null);

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Transação removida com sucesso.' : 'Transação não encontrada.',
            ]);
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
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }

        $tx = $this->transactionService->getById($id);
        if (!$tx) {
            $this->json(['success' => false, 'message' => 'Transação não encontrada.']);
        }

        $this->json(['success' => true, 'transaction' => $tx]);
    }

    // ═══════════════════════════════════════════
    // AJAX: atualizar transação
    // ═══════════════════════════════════════════

    public function update()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $id = Input::post('transaction_id', 'int', 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID da transação inválido.']);
        }

        $tx = $this->transactionService->getById($id);
        if (!$tx) {
            $this->json(['success' => false, 'message' => 'Transação não encontrada.']);
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
            $this->json(['success' => false, 'message' => 'O valor deve ser maior que zero.']);
        }

        if (empty($data['description'])) {
            $this->json(['success' => false, 'message' => 'A descrição é obrigatória.']);
        }

        $this->transactionService->update($id, $data);

        $this->json(['success' => true, 'message' => 'Transação atualizada com sucesso!']);
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

        $this->json([
            'success'       => true,
            'items'         => $result['data'],
            'total'         => $result['total'],
            'page'          => $result['page'],
            'per_page'      => $result['perPage'],
            'total_pages'   => $result['totalPages'],
            'totalEntradas' => $result['totalEntradas'],
            'totalSaidas'   => $result['totalSaidas'],
        ]);
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
        $this->json($data);
    }
}
