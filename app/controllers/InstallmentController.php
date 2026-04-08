<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Models\Installment;
use Akti\Models\Order;
use Akti\Core\ModuleBootloader;
use Akti\Services\InstallmentService;
use Akti\Services\TransactionService;
use Akti\Utils\Input;
use Akti\Services\FileManager;
use TenantManager;

/**
 * InstallmentController — Controller dedicado a parcelas (order_installments).
 *
 * Extraído do FinancialController (God Controller) na Fase 2
 * para responsabilidade única e manutenibilidade.
 *
 * Ações:
 *   - installments()         → View de parcelas de um pedido
 *   - generate()             → Gerar parcelas para pedido
 *   - pay()                  → Registrar pagamento
 *   - confirm()              → Confirmar pagamento manualmente
 *   - cancel()               → Estornar parcela
 *   - merge()                → Unificar parcelas
 *   - split()                → Dividir parcela
 *   - uploadAttachment()     → Upload de comprovante
 *   - removeAttachment()     → Remover comprovante
 *   - getPaginated()         → AJAX: lista paginada
 *   - getJson()              → AJAX: parcelas por pedido
 *
 * @package Akti\Controllers
 */
class InstallmentController
{
    private \PDO $db;
    private Installment $installmentModel;
    private InstallmentService $installmentService;
    private TransactionService $transactionService;

    /**
     * Prefixos de redirecionamento permitidos (whitelist).
     */
    private const ALLOWED_REDIRECT_PREFIXES = [
        '?page=financial',
        '?page=pipeline',
        '?page=orders',
    ];

    /**
     * Valida e sanitiza URL de redirecionamento.
     */
    private function sanitizeRedirect(?string $redirect, string $default = '?page=financial&action=payments'): string
    {
        if (empty($redirect)) {
            return $default;
        }

        $redirect = trim($redirect);

        if (preg_match('#^(https?:)?//#i', $redirect) || preg_match('#^[a-z]+:#i', $redirect)) {
            return $default;
        }

        foreach (self::ALLOWED_REDIRECT_PREFIXES as $prefix) {
            if (strpos($redirect, $prefix) === 0) {
                return $redirect;
            }
        }

        return $default;
    }

    public function __construct(
        \PDO $db,
        Installment $installmentModel,
        InstallmentService $installmentService,
        TransactionService $transactionService
    ) {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo financeiro desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $this->db = $db;
        $this->installmentModel = $installmentModel;
        $this->transactionService = $transactionService;
        $this->installmentService = $installmentService;
    }

    // ═══════════════════════════════════════════
    // VIEW: Parcelas de um pedido
    // ═══════════════════════════════════════════

    public function installments()
    {
        $orderId = Input::get('order_id', 'int', 0);
        if (!$orderId) {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $pipelineStage = $this->installmentModel->getOrderPipelineStage($orderId);
        if (!$pipelineStage || !in_array($pipelineStage, ['financeiro', 'concluido'])) {
            $_SESSION['flash_error'] = 'As parcelas de pagamento só estão disponíveis para pedidos nas etapas Financeiro ou Concluído.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installments = $this->installmentModel->getByOrderId($orderId);

        $orderModel = new Order($this->db);
        $orderData = $orderModel->readOne($orderId);
        $order = [
            'id'                => $orderId,
            'total_amount'      => $orderData['total_amount'] ?? 0,
            'discount'          => $orderData['discount'] ?? 0,
            'payment_status'    => $orderData['payment_status'] ?? 'pendente',
            'payment_method'    => $orderData['payment_method'] ?? '',
            'installments'      => $orderData['installments'] ?? 1,
            'installment_value' => $orderData['installment_value'] ?? 0,
            'created_at'        => $orderData['created_at'] ?? '',
            'customer_name'     => $orderData['customer_name'] ?? '',
        ];

        require 'app/views/layout/header.php';
        require 'app/views/financial/installments.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════
    // Gerar parcelas para pedido
    // ═══════════════════════════════════════════

    public function generate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $orderId         = Input::post('order_id', 'int', 0);
        $numInstallments = Input::post('num_installments', 'int', 1);
        $downPayment     = Input::post('down_payment', 'float', 0);
        $startDate       = Input::post('start_date', 'date', date('Y-m-d'));

        $success = $this->installmentService->generateForOrder($orderId, $numInstallments, $downPayment, $startDate);

        if (!$success) {
            $_SESSION['flash_error'] = 'Pedido não encontrado ou já possui parcelas pagas.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true]);
        }

        $_SESSION['flash_success'] = 'Parcelas geradas com sucesso!';
        header("Location: ?page=financial&action=installments&order_id=$orderId");
        exit;
    }

    // ═══════════════════════════════════════════
    // Registrar pagamento
    // ═══════════════════════════════════════════

    public function pay()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installmentId   = Input::post('installment_id', 'int', 0);
        $paidAmount      = Input::post('paid_amount', 'float', 0);
        $createRemaining = (bool) Input::post('create_remaining', 'int', 0);

        $data = [
            'paid_date'       => Input::post('paid_date', 'date') ?: date('Y-m-d'),
            'paid_amount'     => $paidAmount,
            'payment_method'  => Input::post('payment_method', 'string', 'dinheiro'),
            'notes'           => Input::post('notes'),
            'user_id'         => $_SESSION['user_id'] ?? null,
            'attachment_path' => null,
        ];

        // Handle file upload (comprovante)
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $data['attachment_path'] = $this->handleAttachmentUpload($installmentId);
        }

        $remainingDueDate = Input::post('remaining_due_date', 'date');

        $result = $this->installmentService->payInstallment($installmentId, $data, $createRemaining, $remainingDueDate);

        if (!$result['success']) {
            if ($this->isAjax()) {
                $this->jsonResponse($result);
            }
            $_SESSION['flash_error'] = $result['message'] ?? 'Erro ao registrar pagamento.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        if ($this->isAjax()) {
            $this->jsonResponse($result);
        }

        $_SESSION['flash_success'] = 'Pagamento registrado com sucesso!';
        $redirect = $this->sanitizeRedirect(Input::post('redirect'));
        header("Location: $redirect");
        exit;
    }

    // ═══════════════════════════════════════════
    // Confirmar pagamento
    // ═══════════════════════════════════════════

    public function confirm()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        $userId = $_SESSION['user_id'] ?? null;

        $this->installmentService->confirmPayment($installmentId, $userId);

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true]);
        }

        $_SESSION['flash_success'] = 'Pagamento confirmado com sucesso!';
        $redirect = $this->sanitizeRedirect(Input::post('redirect'), '?page=financial');
        header("Location: $redirect");
        exit;
    }

    // ═══════════════════════════════════════════
    // Estornar/cancelar parcela
    // ═══════════════════════════════════════════

    public function cancel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        $userId = $_SESSION['user_id'] ?? null;

        $this->installmentService->cancelInstallment($installmentId, $userId);

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true]);
        }

        $_SESSION['flash_success'] = 'Parcela estornada com sucesso!';
        $redirect = $this->sanitizeRedirect(Input::post('redirect'));
        header("Location: $redirect");
        exit;
    }

    // ═══════════════════════════════════════════
    // Upload de comprovante
    // ═══════════════════════════════════════════

    public function uploadAttachment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        if (!$installmentId) {
            $_SESSION['flash_error'] = 'Parcela não informada.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            if (!in_array($ext, $allowed)) {
                $_SESSION['flash_error'] = 'Tipo de arquivo não permitido. Envie JPG, PNG, WEBP, GIF ou PDF.';
                header('Location: ' . $this->sanitizeRedirect(Input::post('redirect')));
                exit;
            }

            $this->installmentModel->removeAttachment($installmentId);

            $filepath = $this->handleAttachmentUpload($installmentId);
            if ($filepath) {
                $this->installmentModel->saveAttachment($installmentId, $filepath);
                $_SESSION['flash_success'] = 'Comprovante anexado com sucesso!';
            } else {
                $_SESSION['flash_error'] = 'Erro ao fazer upload do comprovante.';
            }
        } else {
            $_SESSION['flash_error'] = 'Nenhum arquivo enviado.';
        }

        $redirect = $this->sanitizeRedirect(Input::post('redirect'));
        header("Location: $redirect");
        exit;
    }

    // ═══════════════════════════════════════════
    // Remover comprovante
    // ═══════════════════════════════════════════

    public function removeAttachment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        if ($installmentId) {
            $this->installmentModel->removeAttachment($installmentId);
            $_SESSION['flash_success'] = 'Comprovante removido.';
        }

        $redirect = $this->sanitizeRedirect(Input::post('redirect'));
        header("Location: $redirect");
        exit;
    }

    // ═══════════════════════════════════════════
    // Merge (unificar parcelas)
    // ═══════════════════════════════════════════

    public function merge()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $ids = $_POST['installment_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        $ids = array_map('intval', array_filter($ids));

        $dueDate = Input::post('due_date', 'date') ?: date('Y-m-d');

        if (count($ids) < 2) {
            echo json_encode(['success' => false, 'message' => 'Selecione ao menos 2 parcelas em aberto para unificar.']);
            exit;
        }

        try {
            $newId = $this->installmentService->mergeInstallments($ids, $dueDate);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro interno ao unificar parcelas. Tente novamente.']);
            exit;
        }

        if ($newId) {
            $logger = new \Akti\Models\Logger($this->db);
            $logger->log('INSTALLMENTS_MERGED', 'Merged installments [' . implode(',', $ids) . '] into #' . $newId);

            echo json_encode([
                'success' => true,
                'message' => count($ids) . ' parcelas unificadas com sucesso.',
                'new_id'  => $newId,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível unificar. Verifique se todas as parcelas estão em aberto e pertencem ao mesmo pedido.']);
        }
        exit;
    }

    // ═══════════════════════════════════════════
    // Split (dividir parcela)
    // ═══════════════════════════════════════════

    public function split()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        $parts = Input::post('parts', 'int', 2);
        $firstDueDate = Input::post('first_due_date', 'date');

        if (!$installmentId) {
            echo json_encode(['success' => false, 'message' => 'Parcela não informada.']);
            exit;
        }
        if ($parts < 2 || $parts > 24) {
            echo json_encode(['success' => false, 'message' => 'Informe entre 2 e 24 partes.']);
            exit;
        }

        try {
            $newIds = $this->installmentService->splitInstallment($installmentId, $parts, $firstDueDate);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro interno ao dividir parcela. Tente novamente.']);
            exit;
        }

        if (!empty($newIds)) {
            $logger = new \Akti\Models\Logger($this->db);
            $logger->log('INSTALLMENT_SPLIT', "Split installment #$installmentId into $parts parts [" . implode(',', $newIds) . "]");

            echo json_encode([
                'success' => true,
                'message' => "Parcela dividida em $parts partes com sucesso.",
                'new_ids' => $newIds,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível dividir. A parcela deve estar em aberto (pendente/atrasada).']);
        }
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: Lista paginada de parcelas
    // ═══════════════════════════════════════════

    public function getPaginated()
    {
        header('Content-Type: application/json');
        $this->installmentService->updateOverdue();

        $filters = [];
        if (!empty(Input::get('status')))  $filters['status'] = Input::get('status');
        if (!empty(Input::get('month')))   $filters['month']  = Input::get('month', 'int');
        if (!empty(Input::get('year')))    $filters['year']   = Input::get('year', 'int');
        if (!empty(Input::get('search')))  $filters['search'] = Input::get('search');

        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $result = $this->installmentModel->getPaginated($filters, $page, $perPage);

        echo json_encode([
            'success'     => true,
            'items'       => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'per_page'    => $result['perPage'],
            'total_pages' => $result['totalPages'],
            'summary'     => $result['summary'],
        ]);
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: Parcelas por pedido (JSON)
    // ═══════════════════════════════════════════

    public function getJson()
    {
        $orderId = Input::get('order_id', 'int', 0);
        $installments = $this->installmentModel->getByOrderId($orderId);
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'installments' => $installments,
            'count'        => count($installments),
        ]);
        exit;
    }

    // ═══════════════════════════════════════════
    // Helpers privados
    // ═══════════════════════════════════════════

    /**
     * Verifica se é uma requisição AJAX (XMLHttpRequest).
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Envia resposta JSON e encerra.
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Faz upload de arquivo de comprovante e retorna o caminho.
     * @param int $installmentId
     * @return string|null Caminho do arquivo ou null em caso de erro
     */
    private function handleAttachmentUpload(int $installmentId): ?string
    {
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $fileManager = new FileManager($this->db);
        $result = $fileManager->upload($_FILES['attachment'], 'comprovantes', [
            'prefix'     => 'comprovante_' . $installmentId,
            'entityType' => 'installment',
            'entityId'   => $installmentId,
        ]);

        return $result['success'] ? $result['path'] : null;
    }
}
