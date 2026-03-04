<?php
require_once 'app/models/Financial.php';
require_once 'app/models/Order.php';

class FinancialController {

    private $financial;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->financial = new Financial($this->db);
    }

    // ═══════════════════════════════════════════
    // DASHBOARD FINANCEIRO
    // ═══════════════════════════════════════════

    public function index() {
        $month = $_GET['month'] ?? date('m');
        $year  = $_GET['year']  ?? date('Y');

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
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['filter_month'])) $filters['month'] = $_GET['filter_month'];
        if (!empty($_GET['filter_year']))  $filters['year']  = $_GET['filter_year'];

        $orders = $this->financial->getOrdersWithInstallments($filters);

        require 'app/views/layout/header.php';
        require 'app/views/financial/payments.php';
        require 'app/views/layout/footer.php';
    }

    public function installments() {
        $orderId = $_GET['order_id'] ?? 0;
        if (!$orderId) {
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

        $orderId = $_POST['order_id'] ?? 0;
        $numInstallments = (int)($_POST['num_installments'] ?? 1);
        $downPayment = (float)($_POST['down_payment'] ?? 0);
        $startDate = $_POST['start_date'] ?? date('Y-m-d');

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
     * Registra pagamento de uma parcela
     */
    public function payInstallment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=financial&action=payments');
            exit;
        }

        $installmentId = $_POST['installment_id'] ?? 0;
        $data = [
            'paid_date' => $_POST['paid_date'] ?? date('Y-m-d'),
            'paid_amount' => (float)($_POST['paid_amount'] ?? 0),
            'payment_method' => $_POST['payment_method'] ?? 'dinheiro',
            'gateway_reference' => $_POST['gateway_reference'] ?? null,
            'notes' => $_POST['notes'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
        ];

        $this->financial->payInstallment($installmentId, $data);

        $orderId = $_POST['order_id'] ?? 0;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Pagamento registrado com sucesso!';
        header("Location: ?page=financial&action=installments&order_id=$orderId");
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

        $installmentId = $_POST['installment_id'] ?? 0;
        $userId = $_SESSION['user_id'] ?? null;

        $this->financial->confirmInstallment($installmentId, $userId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Pagamento confirmado com sucesso!';
        $redirect = $_POST['redirect'] ?? '?page=financial';
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

        $installmentId = $_POST['installment_id'] ?? 0;
        $this->financial->cancelInstallment($installmentId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Parcela estornada com sucesso!';
        $orderId = $_POST['order_id'] ?? 0;
        header("Location: ?page=financial&action=installments&order_id=$orderId");
        exit;
    }

    // ═══════════════════════════════════════════
    // TRANSAÇÕES (ENTRADAS E SAÍDAS)
    // ═══════════════════════════════════════════

    public function transactions() {
        $filters = [];
        if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
        if (!empty($_GET['filter_month'])) $filters['month'] = $_GET['filter_month'];
        if (!empty($_GET['filter_year']))  $filters['year']  = $_GET['filter_year'];
        if (!empty($_GET['category']))     $filters['category'] = $_GET['category'];

        $transactions = $this->financial->getTransactions($filters);
        $categories = Financial::getCategories();

        // Calcular totais filtrados
        $totalEntradas = 0;
        $totalSaidas = 0;
        foreach ($transactions as $t) {
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

        $data = [
            'type' => $_POST['type'] ?? 'entrada',
            'category' => $_POST['category'] ?? 'outra_entrada',
            'description' => $_POST['description'] ?? '',
            'amount' => (float)($_POST['amount'] ?? 0),
            'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
            'payment_method' => $_POST['payment_method'] ?? null,
            'is_confirmed' => 1,
            'user_id' => $_SESSION['user_id'] ?? null,
            'notes' => $_POST['notes'] ?? null,
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

        $id = $_POST['transaction_id'] ?? 0;
        $this->financial->deleteTransaction($id);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        $_SESSION['flash_success'] = 'Transação removida.';
        header('Location: ?page=financial&action=transactions');
        exit;
    }

    // ═══════════════════════════════════════════
    // API (AJAX)
    // ═══════════════════════════════════════════

    /**
     * Retorna dados de resumo em JSON (para widgets externos)
     */
    public function getSummaryJson() {
        $month = $_GET['month'] ?? date('m');
        $year  = $_GET['year']  ?? date('Y');
        $summary = $this->financial->getSummary($month, $year);
        header('Content-Type: application/json');
        echo json_encode($summary);
        exit;
    }

    /**
     * Retorna parcelas de um pedido em JSON
     */
    public function getInstallmentsJson() {
        $orderId = $_GET['order_id'] ?? 0;
        $installments = $this->financial->getInstallments($orderId);
        header('Content-Type: application/json');
        echo json_encode($installments);
        exit;
    }
}
