<?php

class Financial {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════
    // DASHBOARD / RESUMO FINANCEIRO
    // ═══════════════════════════════════════════

    /**
     * Resumo geral do financeiro
     */
    public function getSummary($month = null, $year = null) {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        $summary = [];

        // Total de receita do mês (pedidos concluídos)
        $q = "SELECT COALESCE(SUM(total_amount - COALESCE(discount, 0)), 0)
              FROM orders 
              WHERE pipeline_stage = 'concluido' 
              AND status != 'cancelado'
              AND MONTH(created_at) = :m AND YEAR(created_at) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['receita_mes'] = (float) $s->fetchColumn();

        // Total recebido no mês (parcelas pagas)
        $q = "SELECT COALESCE(SUM(paid_amount), 0)
              FROM order_installments
              WHERE status = 'pago'
              AND MONTH(paid_date) = :m AND YEAR(paid_date) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['recebido_mes'] = (float) $s->fetchColumn();

        // Entradas manuais do mês
        $q = "SELECT COALESCE(SUM(amount), 0)
              FROM financial_transactions
              WHERE type = 'entrada' AND is_confirmed = 1
              AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['entradas_mes'] = (float) $s->fetchColumn();

        // Saídas do mês
        $q = "SELECT COALESCE(SUM(amount), 0)
              FROM financial_transactions
              WHERE type = 'saida' AND is_confirmed = 1
              AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['saidas_mes'] = (float) $s->fetchColumn();

        // A receber (parcelas pendentes/atrasadas)
        $q = "SELECT COALESCE(SUM(amount), 0)
              FROM order_installments
              WHERE status IN ('pendente', 'atrasado')";
        $s = $this->conn->prepare($q);
        $s->execute();
        $summary['a_receber_total'] = (float) $s->fetchColumn();

        // Atrasados
        $q = "SELECT COALESCE(SUM(amount), 0)
              FROM order_installments
              WHERE (status = 'pendente' OR status = 'atrasado')
              AND due_date < CURDATE()";
        $s = $this->conn->prepare($q);
        $s->execute();
        $summary['atrasados_total'] = (float) $s->fetchColumn();

        // Pendentes de confirmação
        $q = "SELECT COUNT(*)
              FROM order_installments
              WHERE is_confirmed = 0 AND status = 'pago'";
        $s = $this->conn->prepare($q);
        $s->execute();
        $summary['pendentes_confirmacao'] = (int) $s->fetchColumn();

        // Total de pedidos concluídos sem pagamento total
        $q = "SELECT COUNT(*)
              FROM orders 
              WHERE pipeline_stage IN ('financeiro', 'concluido')
              AND status != 'cancelado'
              AND payment_status != 'pago'";
        $s = $this->conn->prepare($q);
        $s->execute();
        $summary['pedidos_pendentes_pgto'] = (int) $s->fetchColumn();

        return $summary;
    }

    /**
     * Gráfico de receita x despesa dos últimos N meses
     */
    public function getChartData($months = 6) {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $m = date('m', strtotime("-{$i} months"));
            $y = date('Y', strtotime("-{$i} months"));

            // Recebido
            $q = "SELECT COALESCE(SUM(paid_amount), 0)
                  FROM order_installments
                  WHERE status = 'pago' AND MONTH(paid_date) = :m AND YEAR(paid_date) = :y";
            $s = $this->conn->prepare($q);
            $s->execute([':m' => $m, ':y' => $y]);
            $recebido = (float) $s->fetchColumn();

            // + entradas manuais
            $q = "SELECT COALESCE(SUM(amount), 0)
                  FROM financial_transactions
                  WHERE type = 'entrada' AND is_confirmed = 1
                  AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
            $s = $this->conn->prepare($q);
            $s->execute([':m' => $m, ':y' => $y]);
            $recebido += (float) $s->fetchColumn();

            // Saídas
            $q = "SELECT COALESCE(SUM(amount), 0)
                  FROM financial_transactions
                  WHERE type = 'saida' AND is_confirmed = 1
                  AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
            $s = $this->conn->prepare($q);
            $s->execute([':m' => $m, ':y' => $y]);
            $saidas = (float) $s->fetchColumn();

            $data[] = [
                'label' => $date,
                'entradas' => $recebido,
                'saidas' => $saidas,
            ];
        }
        return $data;
    }

    // ═══════════════════════════════════════════
    // PEDIDOS COM PAGAMENTO PENDENTE
    // ═══════════════════════════════════════════

    /**
     * Pedidos concluídos/financeiro com pagamento pendente
     */
    public function getOrdersPendingPayment() {
        $q = "SELECT o.id, o.total_amount, o.discount, o.down_payment,
                     o.payment_status, o.payment_method, o.installments, o.installment_value,
                     o.pipeline_stage, o.created_at,
                     c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                     (SELECT COUNT(*) FROM order_installments oi WHERE oi.order_id = o.id) as total_parcelas,
                     (SELECT COUNT(*) FROM order_installments oi WHERE oi.order_id = o.id AND oi.status = 'pago') as parcelas_pagas,
                     (SELECT COALESCE(SUM(paid_amount), 0) FROM order_installments oi WHERE oi.order_id = o.id AND oi.status = 'pago') as total_pago
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE o.pipeline_stage IN ('financeiro', 'concluido')
              AND o.status != 'cancelado'
              AND o.payment_status != 'pago'
              ORDER BY o.created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Todos os pedidos com parcelas (para a tela de pagamentos)
     */
    public function getOrdersWithInstallments($filters = []) {
        $where = "WHERE o.status != 'cancelado'";
        $params = [];

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pendente') {
                $where .= " AND o.payment_status IN ('pendente', 'parcial')";
            } elseif ($filters['status'] === 'pago') {
                $where .= " AND o.payment_status = 'pago'";
            }
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $where .= " AND MONTH(o.created_at) = :fm AND YEAR(o.created_at) = :fy";
            $params[':fm'] = $filters['month'];
            $params[':fy'] = $filters['year'];
        }

        $q = "SELECT o.id, o.total_amount, o.discount, o.down_payment,
                     o.payment_status, o.payment_method, o.installments, o.installment_value,
                     o.pipeline_stage, o.created_at,
                     c.name as customer_name,
                     (SELECT COUNT(*) FROM order_installments oi WHERE oi.order_id = o.id) as total_parcelas,
                     (SELECT COUNT(*) FROM order_installments oi WHERE oi.order_id = o.id AND oi.status = 'pago') as parcelas_pagas,
                     (SELECT COALESCE(SUM(paid_amount), 0) FROM order_installments oi WHERE oi.order_id = o.id AND oi.status = 'pago') as total_pago
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.id
              $where
              ORDER BY o.created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // PARCELAS (INSTALLMENTS)
    // ═══════════════════════════════════════════

    /**
     * Busca as parcelas de um pedido
     */
    public function getInstallments($orderId) {
        $q = "SELECT oi.*, u.name as confirmed_by_name
              FROM order_installments oi
              LEFT JOIN users u ON oi.confirmed_by = u.id
              WHERE oi.order_id = :oid
              ORDER BY oi.installment_number ASC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gera parcelas para um pedido (chamado quando se define o parcelamento)
     */
    public function generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment = 0, $startDate = null) {
        // Deletar parcelas anteriores
        $q = "DELETE FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);

        if ($numInstallments <= 0) $numInstallments = 1;
        if (!$startDate) $startDate = date('Y-m-d');

        $remaining = $totalAmount - $downPayment;
        $installmentValue = round($remaining / $numInstallments, 2);
        // Ajuste de centavos na última parcela
        $lastValue = round($remaining - ($installmentValue * ($numInstallments - 1)), 2);

        $q = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
              VALUES (:oid, :num, :amt, :due, 'pendente')";
        $s = $this->conn->prepare($q);

        // Se houver entrada, registrar como parcela 0 já paga
        if ($downPayment > 0) {
            $qd = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status, paid_date, paid_amount, is_confirmed)
                   VALUES (:oid, 0, :amt, :due, 'pago', :paid, :pamt, 1)";
            $sd = $this->conn->prepare($qd);
            $sd->execute([
                ':oid' => $orderId, ':amt' => $downPayment,
                ':due' => $startDate, ':paid' => $startDate, ':pamt' => $downPayment
            ]);
        }

        for ($i = 1; $i <= $numInstallments; $i++) {
            $dueDate = date('Y-m-d', strtotime($startDate . " + {$i} months"));
            $amount = ($i === $numInstallments) ? $lastValue : $installmentValue;
            $s->execute([
                ':oid' => $orderId,
                ':num' => $i,
                ':amt' => $amount,
                ':due' => $dueDate,
            ]);
        }

        return true;
    }

    /**
     * Registra pagamento de uma parcela (aguardando confirmação se não for gateway)
     */
    public function payInstallment($installmentId, $data) {
        $isGateway = !empty($data['gateway_reference']);
        $q = "UPDATE order_installments SET
                status = 'pago',
                paid_date = :paid_date,
                paid_amount = :paid_amount,
                payment_method = :method,
                gateway_reference = :gateway,
                is_confirmed = :confirmed,
                notes = :notes,
                updated_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([
            ':paid_date' => $data['paid_date'] ?? date('Y-m-d'),
            ':paid_amount' => $data['paid_amount'],
            ':method' => $data['payment_method'] ?? null,
            ':gateway' => $data['gateway_reference'] ?? null,
            ':confirmed' => $isGateway ? 1 : 0,
            ':notes' => $data['notes'] ?? null,
            ':id' => $installmentId,
        ]);

        // Buscar order_id para atualizar status do pedido
        $q2 = "SELECT order_id FROM order_installments WHERE id = :id";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':id' => $installmentId]);
        $row = $s2->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->updateOrderPaymentStatus($row['order_id']);

            // Registrar transação financeira
            $this->addTransaction([
                'type' => 'entrada',
                'category' => 'pagamento_pedido',
                'description' => "Pagamento parcela - Pedido #{$row['order_id']}",
                'amount' => $data['paid_amount'],
                'transaction_date' => $data['paid_date'] ?? date('Y-m-d'),
                'reference_type' => 'installment',
                'reference_id' => $installmentId,
                'payment_method' => $data['payment_method'] ?? null,
                'is_confirmed' => $isGateway ? 1 : 0,
                'user_id' => $data['user_id'] ?? null,
            ]);
        }

        return true;
    }

    /**
     * Confirma pagamento de uma parcela (usuário valida manualmente)
     */
    public function confirmInstallment($installmentId, $userId) {
        $q = "UPDATE order_installments SET
                is_confirmed = 1,
                confirmed_by = :uid,
                confirmed_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':uid' => $userId, ':id' => $installmentId]);

        // Confirmar transação associada
        $q2 = "UPDATE financial_transactions SET
                 is_confirmed = 1,
                 confirmed_by = :uid,
                 confirmed_at = NOW()
               WHERE reference_type = 'installment' AND reference_id = :rid";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':uid' => $userId, ':rid' => $installmentId]);

        return true;
    }

    /**
     * Cancela/estorna uma parcela
     */
    public function cancelInstallment($installmentId) {
        $q = "UPDATE order_installments SET
                status = 'pendente',
                paid_date = NULL,
                paid_amount = NULL,
                is_confirmed = 0,
                confirmed_by = NULL,
                confirmed_at = NULL,
                gateway_reference = NULL
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);

        // Buscar order_id e atualizar
        $q2 = "SELECT order_id FROM order_installments WHERE id = :id";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':id' => $installmentId]);
        $row = $s2->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->updateOrderPaymentStatus($row['order_id']);
        }

        return true;
    }

    /**
     * Atualiza automaticamente o payment_status do pedido com base nas parcelas
     */
    public function updateOrderPaymentStatus($orderId) {
        $q = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pago' AND is_confirmed = 1 THEN 1 ELSE 0 END) as pagas
              FROM order_installments
              WHERE order_id = :oid AND installment_number > 0";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        $status = 'pendente';
        if ($row && (int)$row['total'] > 0) {
            $total = (int)$row['total'];
            $pagas = (int)$row['pagas'];
            if ($pagas >= $total) {
                $status = 'pago';
            } elseif ($pagas > 0) {
                $status = 'parcial';
            }
        }

        $q2 = "UPDATE orders SET payment_status = :ps WHERE id = :oid";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':ps' => $status, ':oid' => $orderId]);

        return $status;
    }

    /**
     * Atualiza parcelas vencidas para status 'atrasado'
     */
    public function updateOverdueInstallments() {
        $q = "UPDATE order_installments 
              SET status = 'atrasado'
              WHERE status = 'pendente' AND due_date < CURDATE()";
        $s = $this->conn->prepare($q);
        return $s->execute();
    }

    /**
     * Parcelas pendentes de confirmação (pagamentos manuais)
     */
    public function getPendingConfirmations() {
        $q = "SELECT oi.*, o.id as order_id,
                     c.name as customer_name, c.phone as customer_phone,
                     o.total_amount as order_total
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE oi.status = 'pago' AND oi.is_confirmed = 0
              ORDER BY oi.paid_date DESC";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Próximas parcelas a vencer (7 dias)
     */
    public function getUpcomingInstallments($days = 7) {
        $q = "SELECT oi.*, o.id as order_id,
                     c.name as customer_name
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE oi.status = 'pendente'
              AND oi.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
              ORDER BY oi.due_date ASC";
        $s = $this->conn->prepare($q);
        $s->execute([':days' => $days]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Parcelas vencidas não pagas
     */
    public function getOverdueInstallments() {
        $q = "SELECT oi.*, o.id as order_id,
                     c.name as customer_name, c.phone as customer_phone,
                     o.total_amount as order_total,
                     DATEDIFF(CURDATE(), oi.due_date) as days_overdue
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE oi.status IN ('pendente', 'atrasado')
              AND oi.due_date < CURDATE()
              ORDER BY oi.due_date ASC";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // TRANSAÇÕES (ENTRADAS E SAÍDAS)
    // ═══════════════════════════════════════════

    /**
     * Adiciona uma transação financeira
     */
    public function addTransaction($data) {
        $q = "INSERT INTO financial_transactions 
              (type, category, description, amount, transaction_date, reference_type, reference_id, payment_method, is_confirmed, user_id, notes)
              VALUES (:type, :cat, :desc, :amt, :date, :ref_type, :ref_id, :method, :confirmed, :uid, :notes)";
        $s = $this->conn->prepare($q);
        return $s->execute([
            ':type' => $data['type'],
            ':cat' => $data['category'],
            ':desc' => $data['description'],
            ':amt' => $data['amount'],
            ':date' => $data['transaction_date'],
            ':ref_type' => $data['reference_type'] ?? null,
            ':ref_id' => $data['reference_id'] ?? null,
            ':method' => $data['payment_method'] ?? null,
            ':confirmed' => $data['is_confirmed'] ?? 1,
            ':uid' => $data['user_id'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Lista transações com filtros
     */
    public function getTransactions($filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $where .= " AND ft.type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $where .= " AND MONTH(ft.transaction_date) = :m AND YEAR(ft.transaction_date) = :y";
            $params[':m'] = $filters['month'];
            $params[':y'] = $filters['year'];
        }
        if (!empty($filters['category'])) {
            $where .= " AND ft.category = :cat";
            $params[':cat'] = $filters['category'];
        }

        $q = "SELECT ft.*, u.name as user_name
              FROM financial_transactions ft
              LEFT JOIN users u ON ft.user_id = u.id
              $where
              ORDER BY ft.transaction_date DESC, ft.id DESC
              LIMIT 500";
        $s = $this->conn->prepare($q);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Deleta transação
     */
    public function deleteTransaction($id) {
        $q = "DELETE FROM financial_transactions WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id]);
    }

    /**
     * Categorias de transação disponíveis
     */
    public static function getCategories() {
        return [
            'entrada' => [
                'pagamento_pedido' => 'Pagamento de Pedido',
                'servico_avulso' => 'Serviço Avulso',
                'outra_entrada' => 'Outra Entrada',
            ],
            'saida' => [
                'material' => 'Compra de Material',
                'salario' => 'Salários',
                'aluguel' => 'Aluguel',
                'energia' => 'Energia/Água',
                'internet' => 'Internet/Telefone',
                'manutencao' => 'Manutenção',
                'imposto' => 'Impostos/Taxas',
                'outra_saida' => 'Outra Saída',
            ]
        ];
    }
}
