<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

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

        // Entradas manuais do mês (exclui estornos e registros — são apenas registros, não contam no cálculo)
        $q = "SELECT COALESCE(SUM(amount), 0)
              FROM financial_transactions
              WHERE type = 'entrada' AND is_confirmed = 1
              AND category NOT IN ('estorno_pagamento', 'registro_ofx')
              AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['entradas_mes'] = (float) $s->fetchColumn();

        // Saídas do mês (exclui estornos e registros — são apenas registros, não contam no cálculo)
        $q = "SELECT COALESCE(SUM(amount), 0)
              FROM financial_transactions
              WHERE type = 'saida' AND is_confirmed = 1
              AND category NOT IN ('estorno_pagamento', 'registro_ofx')
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

            // + entradas manuais (exclui estornos e registros)
            $q = "SELECT COALESCE(SUM(amount), 0)
                  FROM financial_transactions
                  WHERE type = 'entrada' AND is_confirmed = 1
                  AND category NOT IN ('estorno_pagamento', 'registro_ofx')
                  AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
            $s = $this->conn->prepare($q);
            $s->execute([':m' => $m, ':y' => $y]);
            $recebido += (float) $s->fetchColumn();

            // Saídas (exclui estornos e registros)
            $q = "SELECT COALESCE(SUM(amount), 0)
                  FROM financial_transactions
                  WHERE type = 'saida' AND is_confirmed = 1
                  AND category NOT IN ('estorno_pagamento', 'registro_ofx')
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
     * Filtra apenas pedidos nas etapas financeiro/concluido (regra de negócio)
     */
    public function getOrdersWithInstallments($filters = []) {
        $where = "WHERE o.status != 'cancelado' AND o.pipeline_stage IN ('financeiro', 'concluido')";
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
     * Busca TODAS as parcelas de todos os pedidos (para a tela de pagamentos)
     * Filtra apenas pedidos nas etapas financeiro/concluido (regra de negócio)
     */
    public function getAllInstallments($filters = []) {
        $where = "WHERE o.status != 'cancelado' AND o.pipeline_stage IN ('financeiro', 'concluido')";
        $params = [];

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pendente') {
                $where .= " AND oi.status IN ('pendente', 'atrasado')";
            } elseif ($filters['status'] === 'pago') {
                $where .= " AND oi.status = 'pago'";
            } elseif ($filters['status'] === 'atrasado') {
                $where .= " AND oi.status = 'atrasado'";
            } elseif ($filters['status'] === 'aguardando') {
                $where .= " AND oi.status = 'pago' AND oi.is_confirmed = 0";
            }
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $where .= " AND MONTH(oi.due_date) = :fm AND YEAR(oi.due_date) = :fy";
            $params[':fm'] = $filters['month'];
            $params[':fy'] = $filters['year'];
        } elseif (!empty($filters['month'])) {
            $where .= " AND MONTH(oi.due_date) = :fm";
            $params[':fm'] = $filters['month'];
        } elseif (!empty($filters['year'])) {
            $where .= " AND YEAR(oi.due_date) = :fy";
            $params[':fy'] = $filters['year'];
        }

        $q = "SELECT oi.*, 
                     o.total_amount as order_total, o.discount as order_discount,
                     o.payment_method as order_payment_method, o.pipeline_stage,
                     c.name as customer_name,
                     c.document as customer_document,
                     c.address as customer_address,
                     u.name as confirmed_by_name
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              LEFT JOIN users u ON oi.confirmed_by = u.id
              $where
              ORDER BY 
                CASE oi.status 
                    WHEN 'atrasado' THEN 1 
                    WHEN 'pendente' THEN 2 
                    WHEN 'pago' THEN 3 
                    WHEN 'cancelado' THEN 4 
                END,
                oi.due_date ASC, oi.order_id ASC, oi.installment_number ASC";
        $s = $this->conn->prepare($q);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

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
     * Conta parcelas de um pedido
     */
    public function countInstallments($orderId) {
        $q = "SELECT COUNT(*) FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return (int) $s->fetchColumn();
    }

    /**
     * Remove todas as parcelas de um pedido
     */
    public function deleteInstallmentsByOrder($orderId) {
        $q = "DELETE FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->rowCount();
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
        if ($remaining <= 0) $remaining = $totalAmount; // segurança
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
            // Parcela única (à vista): vencimento hoje; múltiplas: +i meses
            if ($numInstallments === 1) {
                $dueDate = $startDate;
            } else {
                $dueDate = date('Y-m-d', strtotime($startDate . " + {$i} months"));
            }
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
     * Registra pagamento de uma parcela.
     * Se $autoConfirm = true, marca como pago E confirmado de uma vez (sem necessidade de confirmar depois).
     */
    public function payInstallment($installmentId, $data, $autoConfirm = false) {
        $isConfirmed = $autoConfirm ? 1 : 0;

        $q = "UPDATE order_installments SET
                status = 'pago',
                paid_date = :paid_date,
                paid_amount = :paid_amount,
                payment_method = :method,
                is_confirmed = :confirmed,
                confirmed_by = :confirmed_by,
                confirmed_at = IF(:confirmed2 = 1, NOW(), NULL),
                notes = :notes,
                attachment_path = COALESCE(:attachment, attachment_path),
                updated_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([
            ':paid_date' => $data['paid_date'] ?? date('Y-m-d'),
            ':paid_amount' => $data['paid_amount'],
            ':method' => $data['payment_method'] ?? null,
            ':confirmed' => $isConfirmed,
            ':confirmed_by' => $autoConfirm ? ($data['user_id'] ?? null) : null,
            ':confirmed2' => $isConfirmed,
            ':notes' => $data['notes'] ?? null,
            ':attachment' => $data['attachment_path'] ?? null,
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
                'is_confirmed' => $isConfirmed,
                'user_id' => $data['user_id'] ?? null,
            ]);
        }

        return true;
    }

    /**
     * Cria uma nova parcela com o valor restante (quando pagamento parcial).
     * Retorna o ID da nova parcela criada.
     */
    public function createRemainingInstallment($originalInstallmentId, $remainingAmount, $dueDate = null) {
        // Buscar dados da parcela original
        $q = "SELECT order_id, installment_number, due_date FROM order_installments WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $originalInstallmentId]);
        $original = $s->fetch(PDO::FETCH_ASSOC);

        if (!$original) return false;

        // Descobrir o próximo installment_number
        $q2 = "SELECT MAX(installment_number) FROM order_installments WHERE order_id = :oid";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':oid' => $original['order_id']]);
        $maxNum = (int) $s2->fetchColumn();
        $nextNum = $maxNum + 1;

        // Data de vencimento: usar a fornecida ou +30 dias da original
        if (!$dueDate) {
            $dueDate = date('Y-m-d', strtotime($original['due_date'] . ' + 30 days'));
        }

        $q3 = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
               VALUES (:oid, :num, :amt, :due, 'pendente')";
        $s3 = $this->conn->prepare($q3);
        $s3->execute([
            ':oid' => $original['order_id'],
            ':num' => $nextNum,
            ':amt' => $remainingAmount,
            ':due' => $dueDate,
        ]);

        $newId = $this->conn->lastInsertId();

        // Atualizar o status do pedido
        $this->updateOrderPaymentStatus($original['order_id']);

        return $newId;
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

        // Recalcular payment_status do pedido
        $q3 = "SELECT order_id FROM order_installments WHERE id = :id";
        $s3 = $this->conn->prepare($q3);
        $s3->execute([':id' => $installmentId]);
        $row = $s3->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->updateOrderPaymentStatus($row['order_id']);
        }

        return true;
    }

    /**
     * Cancela/estorna uma parcela
     */
    public function cancelInstallment($installmentId, $userId = null) {
        // Buscar dados da parcela ANTES de limpar (para registrar o estorno)
        $q0 = "SELECT oi.order_id, oi.paid_amount, oi.amount, oi.payment_method, oi.installment_number
               FROM order_installments oi WHERE oi.id = :id";
        $s0 = $this->conn->prepare($q0);
        $s0->execute([':id' => $installmentId]);
        $parcelaAntes = $s0->fetch(PDO::FETCH_ASSOC);

        $q = "UPDATE order_installments SET
                status = 'pendente',
                paid_date = NULL,
                paid_amount = NULL,
                payment_method = NULL,
                is_confirmed = 0,
                confirmed_by = NULL,
                confirmed_at = NULL
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);

        if ($parcelaAntes) {
            $this->updateOrderPaymentStatus($parcelaAntes['order_id']);

            // Registrar estorno no livro caixa (financial_transactions)
            // Estornos são do tipo 'registro' — não contabilizam nos totais
            $valorEstorno = (float)($parcelaAntes['paid_amount'] ?? $parcelaAntes['amount']);
            if ($valorEstorno > 0) {
                $this->addTransaction([
                    'type' => 'registro',
                    'category' => 'estorno_pagamento',
                    'description' => "Estorno parcela {$parcelaAntes['installment_number']} - Pedido #{$parcelaAntes['order_id']}",
                    'amount' => $valorEstorno,
                    'transaction_date' => date('Y-m-d'),
                    'reference_type' => 'installment',
                    'reference_id' => $installmentId,
                    'payment_method' => $parcelaAntes['payment_method'] ?? null,
                    'is_confirmed' => 1,
                    'user_id' => $userId,
                ]);
            }

            // Remover a transação de entrada original (ou marcar como cancelada)
            $qDel = "DELETE FROM financial_transactions 
                     WHERE reference_type = 'installment' AND reference_id = :rid AND type = 'entrada'";
            $sDel = $this->conn->prepare($qDel);
            $sDel->execute([':rid' => $installmentId]);
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
     * Busca uma parcela pelo ID
     */
    public function getInstallmentById($id) {
        $q = "SELECT oi.*, o.payment_method as order_payment_method, c.name as customer_name
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE oi.id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Salva o caminho do comprovante (attachment) em uma parcela
     */
    public function saveAttachment($installmentId, $path) {
        $q = "UPDATE order_installments SET attachment_path = :path, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':path' => $path, ':id' => $installmentId]);
    }

    /**
     * Remove o comprovante de uma parcela
     */
    public function removeAttachment($installmentId) {
        // Buscar caminho atual
        $q = "SELECT attachment_path FROM order_installments WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['attachment_path']) && file_exists($row['attachment_path'])) {
            unlink($row['attachment_path']);
        }
        return $this->saveAttachment($installmentId, null);
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
        $result = $s->execute([
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
        if ($result) {
            EventDispatcher::dispatch('model.financial_transaction.created', new Event('model.financial_transaction.created', [
                'id' => $this->conn->lastInsertId(),
                'type' => $data['type'],
                'category' => $data['category'],
                'amount' => $data['amount'],
            ]));
        }
        return $result;
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
        $result = $s->execute([':id' => $id]);
        if ($result) {
            EventDispatcher::dispatch('model.financial_transaction.deleted', new Event('model.financial_transaction.deleted', ['id' => $id]));
        }
        return $result;
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

    /**
     * Categorias internas (usadas apenas pelo sistema, não aparecem no formulário)
     */
    public static function getInternalCategories() {
        return [
            'estorno_pagamento' => 'Estorno de Pagamento',
            'registro_ofx' => 'Registro OFX',
        ];
    }

    /**
     * Importa transações de um arquivo OFX
     * @param string $filePath Caminho do arquivo OFX
     * @param string $mode 'registro' = apenas registrar (não contabiliza) ou 'contabilizar' = soma no caixa
     * @param int|null $userId ID do usuário que importou
     * @return array Resultado com imported, skipped, errors
     */
    public function importOfx($filePath, $mode = 'registro', $userId = null) {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => [], 'transactions' => []];

        $content = file_get_contents($filePath);
        if (!$content) {
            $result['errors'][] = 'Não foi possível ler o arquivo.';
            return $result;
        }

        // Parse OFX — extrair transações do bloco <BANKTRANLIST>
        $transactions = $this->parseOfxTransactions($content);

        if (empty($transactions)) {
            $result['errors'][] = 'Nenhuma transação encontrada no arquivo OFX.';
            return $result;
        }

        foreach ($transactions as $tx) {
            try {
                $amount = abs((float)$tx['amount']);
                if ($amount <= 0) {
                    $result['skipped']++;
                    continue;
                }

                $isCredit = (float)$tx['amount'] > 0;

                if ($mode === 'registro') {
                    // Modo registro: type = 'registro', não contabiliza nos totais
                    $data = [
                        'type' => 'registro',
                        'category' => 'registro_ofx',
                        'description' => $tx['memo'] ?: ($isCredit ? 'Crédito OFX' : 'Débito OFX'),
                        'amount' => $amount,
                        'transaction_date' => $tx['date'],
                        'reference_type' => 'ofx',
                        'reference_id' => null,
                        'payment_method' => 'transferencia',
                        'is_confirmed' => 1,
                        'user_id' => $userId,
                        'notes' => 'Importado via OFX (registro) — FITID: ' . ($tx['fitid'] ?? ''),
                    ];
                } else {
                    // Modo contabilizar: type = entrada ou saida, contabiliza nos totais
                    $data = [
                        'type' => $isCredit ? 'entrada' : 'saida',
                        'category' => $isCredit ? 'outra_entrada' : 'outra_saida',
                        'description' => $tx['memo'] ?: ($isCredit ? 'Crédito OFX' : 'Débito OFX'),
                        'amount' => $amount,
                        'transaction_date' => $tx['date'],
                        'reference_type' => 'ofx',
                        'reference_id' => null,
                        'payment_method' => 'transferencia',
                        'is_confirmed' => 1,
                        'user_id' => $userId,
                        'notes' => 'Importado via OFX (contabilizado) — FITID: ' . ($tx['fitid'] ?? ''),
                    ];
                }

                $this->addTransaction($data);
                $result['imported']++;
                $result['transactions'][] = [
                    'date' => $tx['date'],
                    'description' => $data['description'],
                    'amount' => $amount,
                    'type' => $data['type'],
                ];
            } catch (Exception $e) {
                $result['errors'][] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Parse de transações do conteúdo OFX (formato SGML)
     */
    private function parseOfxTransactions($content) {
        $transactions = [];

        // Extrair bloco de transações
        if (!preg_match('/<BANKTRANLIST>(.*?)<\/BANKTRANLIST>/si', $content, $listMatch)) {
            // Tentar sem tag de fechamento (alguns OFX não fecham)
            if (!preg_match('/<BANKTRANLIST>(.*)/si', $content, $listMatch)) {
                return $transactions;
            }
        }

        $block = $listMatch[1];

        // Extrair cada STMTTRN
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/si', $block, $matches);
        if (empty($matches[1])) {
            // Tentar parsing sem tags de fechamento (OFX SGML)
            preg_match_all('/<STMTTRN>(.*?)(?=<STMTTRN>|<\/BANKTRANLIST>|\z)/si', $block, $matches);
        }

        foreach ($matches[1] ?? [] as $txBlock) {
            $tx = [
                'type' => $this->extractOfxTag($txBlock, 'TRNTYPE'),
                'date' => $this->parseOfxDate($this->extractOfxTag($txBlock, 'DTPOSTED')),
                'amount' => $this->extractOfxTag($txBlock, 'TRNAMT'),
                'fitid' => $this->extractOfxTag($txBlock, 'FITID'),
                'memo' => $this->extractOfxTag($txBlock, 'MEMO') ?: $this->extractOfxTag($txBlock, 'NAME'),
            ];

            if ($tx['date'] && $tx['amount']) {
                $transactions[] = $tx;
            }
        }

        return $transactions;
    }

    /**
     * Extrai valor de uma tag OFX (formato SGML sem atributos)
     */
    private function extractOfxTag($block, $tag) {
        if (preg_match("/<{$tag}>(.*?)(?:<|$)/mi", $block, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /**
     * Converte data OFX (YYYYMMDD ou YYYYMMDDHHMMSS) para Y-m-d
     */
    private function parseOfxDate($dateStr) {
        $dateStr = preg_replace('/\[.*$/', '', $dateStr); // Remove timezone info [x:BRT]
        if (strlen($dateStr) >= 8) {
            $y = substr($dateStr, 0, 4);
            $m = substr($dateStr, 4, 2);
            $d = substr($dateStr, 6, 2);
            return "$y-$m-$d";
        }
        return date('Y-m-d');
    }
}
