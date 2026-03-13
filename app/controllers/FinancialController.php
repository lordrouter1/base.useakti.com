<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Models\Order;
use Akti\Models\CompanySettings;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Database;
use PDO;
use TenantManager;

class FinancialController {

    private $financial;
    private $db;

    public function __construct() {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo financeiro desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();
        $this->financial = new Financial($this->db);
    }

    // ═══════════════════════════════════════════
    // DASHBOARD FINANCEIRO
    // ═══════════════════════════════════════════

    public function index() {
        $month = Input::get('month', 'int', (int)date('m'));
        $year  = Input::get('year', 'int', (int)date('Y'));

        // Atualizar parcelas vencidas
        $this->financial->updateOverdueInstallments();

        $summary   = $this->financial->getSummary($month, $year);
        $chartData = $this->financial->getChartData(6);

        $pendingConfirmations = $this->financial->getPendingConfirmations();
        $overdueInstallments  = $this->financial->getOverdueInstallments();
        $upcomingInstallments = $this->financial->getUpcomingInstallments(7);

        $categories = Financial::getCategories();

        require 'app/views/layout/header.php';
        require 'app/views/financial/index.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════
    // PAGAMENTOS / PARCELAS
    // ═══════════════════════════════════════════

    public function payments() {
        $this->financial->updateOverdueInstallments();

        $filters = [];
        if (Input::hasGet('status')) $filters['status'] = Input::get('status');
        if (Input::hasGet('filter_month')) $filters['month'] = Input::get('filter_month', 'int');
        if (Input::hasGet('filter_year'))  $filters['year']  = Input::get('filter_year', 'int');

        $orders = $this->financial->getOrdersWithInstallments($filters);
        $installments = $this->financial->getAllInstallments($filters);

        // Carregar dados bancários da empresa (para geração de boletos FEBRABAN)
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        require 'app/views/layout/header.php';
        require 'app/views/financial/payments.php';
        require 'app/views/layout/footer.php';
    }

    public function installments() {
        $orderId = Input::get('order_id', 'int', 0);
        if (!$orderId) {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        // Verificar se o pedido está em etapa financeiro/concluido
        $stmtStage = $this->db->prepare("SELECT pipeline_stage FROM orders WHERE id = :id");
        $stmtStage->execute([':id' => $orderId]);
        $orderStage = $stmtStage->fetch(\PDO::FETCH_ASSOC);
        if (!$orderStage || !in_array($orderStage['pipeline_stage'], ['financeiro', 'concluido'])) {
            $_SESSION['flash_error'] = 'As parcelas de pagamento só estão disponíveis para pedidos nas etapas Financeiro ou Concluído.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installments = $this->financial->getInstallments($orderId);

        // Buscar dados do pedido
        $orderModel = new Order($this->db);
        $orderData = $orderModel->readOne($orderId);
        $order = [
            'id' => $orderId,
            'total_amount' => $orderData['total_amount'] ?? 0,
            'discount' => $orderData['discount'] ?? 0,
            'payment_status' => $orderData['payment_status'] ?? 'pendente',
            'payment_method' => $orderData['payment_method'] ?? '',
            'installments' => $orderData['installments'] ?? 1,
            'installment_value' => $orderData['installment_value'] ?? 0,
            'created_at' => $orderData['created_at'] ?? '',
            'customer_name' => $orderData['customer_name'] ?? '',
        ];

        require 'app/views/layout/header.php';
        require 'app/views/financial/installments.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Gera parcelas para um pedido (AJAX ou POST)
     */
    public function generateInstallments() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $orderId = Input::post('order_id', 'int', 0);
        $numInstallments = Input::post('num_installments', 'int', 1);
        $downPayment = Input::post('down_payment', 'float', 0);
        $startDate = Input::post('start_date', 'date', date('Y-m-d'));

        // Buscar total do pedido
        $q = "SELECT total_amount, COALESCE(discount, 0) as discount FROM orders WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);
        $order = $s->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pedido não encontrado.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $totalAmount = $order['total_amount'] - $order['discount'];

        $this->financial->generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment, $startDate);

        // Atualizar dados do pedido
        $q2 = "UPDATE orders SET installments = :inst, installment_value = :val, down_payment = :dp WHERE id = :id";
        $s2 = $this->db->prepare($q2);
        $installValue = ($numInstallments > 0) ? round(($totalAmount - $downPayment) / $numInstallments, 2) : $totalAmount;
        $s2->execute([
            ':inst' => $numInstallments,
            ':val' => $installValue,
            ':dp' => $downPayment,
            ':id' => $orderId,
        ]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Parcelas geradas com sucesso!';
        header("Location: ?page=financial&action=installments&order_id=$orderId");
        exit;
    }

    /**
     * Registra pagamento de uma parcela (AJAX ou POST).
     * Novo fluxo:
     *  - Se paid_amount >= amount da parcela → marca como pago+confirmado automaticamente
     *  - Se paid_amount < amount → paga o valor informado e, se solicitado (create_remaining=1),
     *    cria nova parcela com o valor restante
     */
    public function payInstallment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

        $installmentId = Input::post('installment_id', 'int', 0);
        $paidAmount = Input::post('paid_amount', 'float', 0);
        $createRemaining = Input::post('create_remaining', 'int', 0);

        // Buscar dados da parcela original
        $q = "SELECT id, order_id, amount, installment_number FROM order_installments WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $installmentId]);
        $installment = $s->fetch(PDO::FETCH_ASSOC);

        if (!$installment) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Parcela não encontrada.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Parcela não encontrada.';
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $originalAmount = (float) $installment['amount'];
        $orderId = $installment['order_id'];

        $data = [
            'paid_date' => Input::post('paid_date', 'date') ?: date('Y-m-d'),
            'paid_amount' => $paidAmount,
            'payment_method' => Input::post('payment_method', 'string', 'dinheiro'),
            'notes' => Input::post('notes'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'attachment_path' => null,
        ];

        // Handle file upload (comprovante)
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = TenantManager::getTenantUploadBase() . 'comprovantes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            if (in_array($ext, $allowed)) {
                $filename = 'comprovante_' . $installmentId . '_' . time() . '.' . $ext;
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
                    $data['attachment_path'] = $filepath;
                }
            }
        }

        // Determinar se auto-confirma
        // Se pagou o valor total (ou mais), confirma automaticamente
        $autoConfirm = ($paidAmount >= $originalAmount);

        // Se pagou menos e NÃO quer criar parcela restante, também confirma (aceita como quitado)
        if ($paidAmount < $originalAmount && !$createRemaining) {
            $autoConfirm = true;
        }

        // Registrar o pagamento
        $this->financial->payInstallment($installmentId, $data, $autoConfirm);

        $newInstallmentId = null;
        $remainingAmount = 0;

        // Se pagou menos e quer criar parcela restante
        if ($paidAmount < $originalAmount && $createRemaining) {
            $remainingAmount = round($originalAmount - $paidAmount, 2);
            $remainingDueDate = Input::post('remaining_due_date', 'date');

            // Atualizar o valor da parcela original para o valor efetivamente pago
            $qUpd = "UPDATE order_installments SET amount = :amt WHERE id = :id";
            $sUpd = $this->db->prepare($qUpd);
            $sUpd->execute([':amt' => $paidAmount, ':id' => $installmentId]);

            // Confirmar a parcela original (já foi paga integralmente pelo novo valor)
            $this->financial->confirmInstallment($installmentId, $_SESSION['user_id'] ?? null);

            // Criar nova parcela com o restante
            $newInstallmentId = $this->financial->createRemainingInstallment($installmentId, $remainingAmount, $remainingDueDate);
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'auto_confirmed' => $autoConfirm,
                'remaining_created' => $newInstallmentId ? true : false,
                'new_installment_id' => $newInstallmentId,
                'remaining_amount' => $remainingAmount,
            ]);
            exit;
        }

        $_SESSION['flash_success'] = 'Pagamento registrado com sucesso!';
        $redirect = Input::post('redirect', 'string', '?page=financial&action=payments');
        header("Location: $redirect");
        exit;
    }

    /**
     * Confirma pagamento manual
     */
    public function confirmPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        $userId = $_SESSION['user_id'] ?? null;

        $this->financial->confirmInstallment($installmentId, $userId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Pagamento confirmado com sucesso!';
        $redirect = Input::post('redirect', 'string', '?page=financial');
        header("Location: $redirect");
        exit;
    }

    /**
     * Cancela/estorna pagamento de parcela
     */
    public function cancelInstallment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        $userId = $_SESSION['user_id'] ?? null;
        $this->financial->cancelInstallment($installmentId, $userId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Parcela estornada com sucesso!';
        $redirect = Input::post('redirect', 'string', '?page=financial&action=payments');
        header("Location: $redirect");
        exit;
    }

    /**
     * Upload de comprovante para uma parcela existente (POST + arquivo)
     */
    public function uploadAttachment() {
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
            $uploadDir = TenantManager::getTenantUploadBase() . 'comprovantes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            if (!in_array($ext, $allowed)) {
                $_SESSION['flash_error'] = 'Tipo de arquivo não permitido. Envie JPG, PNG, WEBP, GIF ou PDF.';
                header('Location: ' . (Input::post('redirect', 'string', '?page=financial&action=payments')));
                exit;
            }

            $filename = 'comprovante_' . $installmentId . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;

            // Remover anterior se existir
            $this->financial->removeAttachment($installmentId);

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
                $this->financial->saveAttachment($installmentId, $filepath);
                $_SESSION['flash_success'] = 'Comprovante anexado com sucesso!';
            } else {
                $_SESSION['flash_error'] = 'Erro ao fazer upload do comprovante.';
            }
        } else {
            $_SESSION['flash_error'] = 'Nenhum arquivo enviado.';
        }

        $redirect = Input::post('redirect', 'string', '?page=financial&action=payments');
        header("Location: $redirect");
        exit;
    }

    /**
     * Remove o comprovante de uma parcela
     */
    public function removeAttachment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        if ($installmentId) {
            $this->financial->removeAttachment($installmentId);
            $_SESSION['flash_success'] = 'Comprovante removido.';
        }

        $redirect = Input::post('redirect', 'string', '?page=financial&action=payments');
        header("Location: $redirect");
        exit;
    }

    // ═══════════════════════════════════════════
    // TRANSAÇÕES (ENTRADAS E SAÍDAS)
    // ═══════════════════════════════════════════

    public function transactions() {
        $filters = [];
        if (!empty(Input::get('type'))) $filters['type'] = Input::get('type');
        if (!empty(Input::get('filter_month'))) $filters['month'] = Input::get('filter_month', 'int');
        if (!empty(Input::get('filter_year')))  $filters['year']  = Input::get('filter_year', 'int');
        if (!empty(Input::get('category')))     $filters['category'] = Input::get('category');

        $transactions = $this->financial->getTransactions($filters);
        $categories = Financial::getCategories();

        // Calcular totais filtrados (estornos e registros não contam nos totais)
        $totalEntradas = 0;
        $totalSaidas = 0;
        foreach ($transactions as $t) {
            if ($t['type'] === 'registro') continue;
            if (($t['category'] ?? '') === 'estorno_pagamento') continue;
            if (($t['category'] ?? '') === 'registro_ofx') continue;
            if ($t['type'] === 'entrada' && $t['is_confirmed']) $totalEntradas += $t['amount'];
            if ($t['type'] === 'saida' && $t['is_confirmed'])   $totalSaidas += $t['amount'];
        }

        require 'app/views/layout/header.php';
        require 'app/views/financial/transactions.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Adiciona transação (entrada/saída)
     */
    public function addTransaction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=transactions');
            exit;
        }

        $type = Input::post('type', 'enum', 'entrada', ['entrada', 'saida']);
        $data = [
            'type' => $type,
            'category' => Input::post('category', 'string', $type === 'entrada' ? 'outra_entrada' : 'outra_saida'),
            'description' => Input::post('description'),
            'amount' => Input::post('amount', 'float', 0),
            'transaction_date' => Input::post('transaction_date', 'date') ?: date('Y-m-d'),
            'payment_method' => Input::post('payment_method'),
            'is_confirmed' => 1,
            'user_id' => $_SESSION['user_id'] ?? null,
            'notes' => Input::post('notes'),
        ];

        $this->financial->addTransaction($data);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Transação registrada com sucesso!';
        header('Location: ?page=financial&action=transactions');
        exit;
    }

    /**
     * Deleta transação
     */
    public function deleteTransaction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=transactions');
            exit;
        }

        $id = Input::post('transaction_id', 'int', 0);
        $this->financial->deleteTransaction($id);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Transação removida.';
        header('Location: ?page=financial&action=transactions');
        exit;
    }

    /**
     * Importar arquivo OFX
     */
    public function importOfx() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        if (empty($_FILES['ofx_file']) || $_FILES['ofx_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo OFX enviado.']);
            exit;
        }

        $file = $_FILES['ofx_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['ofx', 'ofc'])) {
            echo json_encode(['success' => false, 'message' => 'Formato não suportado. Envie um arquivo .OFX']);
            exit;
        }

        $mode = Input::post('import_mode', 'enum', 'registro', ['registro', 'contabilizar']);

        $userId = $_SESSION['user_id'] ?? null;

        try {
            $result = $this->financial->importOfx($file['tmp_name'], $mode, $userId);

            $modeLabel = $mode === 'registro' ? 'apenas registro (não contabilizado)' : 'contabilizado no caixa';
            echo json_encode([
                'success' => true,
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'message' => sprintf(
                    'Importação concluída! %d transação(ões) importada(s) como %s.%s',
                    $result['imported'],
                    $modeLabel,
                    !empty($result['errors']) ? ' Erros: ' . count($result['errors']) : ''
                )
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro na importação: ' . $e->getMessage()]);
        }
        exit;
    }

    // ═══════════════════════════════════════════
    // API (AJAX)
    // ═══════════════════════════════════════════

    /**
     * Retorna dados de resumo em JSON (para widgets externos)
     */
    public function getSummaryJson() {
        $month = Input::get('month', 'int') ?: (int)date('m');
        $year  = Input::get('year', 'int') ?: (int)date('Y');
        $summary = $this->financial->getSummary($month, $year);
        header('Content-Type: application/json');
        echo json_encode($summary);
        exit;
    }

    /**
     * Retorna parcelas de um pedido em JSON
     */
    public function getInstallmentsJson() {
        $orderId = Input::get('order_id', 'int', 0);
        $installments = $this->financial->getInstallments($orderId);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'installments' => $installments,
            'count' => count($installments),
        ]);
        exit;
    }
}
