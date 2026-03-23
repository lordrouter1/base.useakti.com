<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: Financial
 * Gerencia operações financeiras: pedidos, parcelas, transações, importação OFX.
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.installment.generated', 'model.installment.deleted_all', 'model.installment.due_date_updated', 'model.order.financial_updated', 'model.financial_transaction.created', 'model.financial_transaction.deleted' (ver funções para detalhes)
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class Financial {
    private $conn;

    /**
     * Flag para evitar execução múltipla de updateOverdueInstallments no mesmo request (P11).
     * @var bool
     */
    private $overdueUpdatedThisRequest = false;

    /**
     * Construtor do model
     * @param PDO $db Conexão PDO
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════
    // DASHBOARD / RESUMO FINANCEIRO
    // ═══════════════════════════════════════════

    /**
     * Retorna resumo geral do financeiro
     * @param int|null $month Mês
     * @param int|null $year Ano
     * @return array Resumo financeiro
     */
    public function getSummary($month = null, $year = null) {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // ── Query consolidada: receita do mês + pedidos pendentes (tabela orders) ──
        $qOrders = "SELECT 
            COALESCE(SUM(CASE WHEN pipeline_stage = 'concluido' AND MONTH(created_at) = :m1 AND YEAR(created_at) = :y1 
                              THEN total_amount - COALESCE(discount, 0) ELSE 0 END), 0) AS receita_mes,
            SUM(CASE WHEN pipeline_stage IN ('financeiro','concluido') AND payment_status != 'pago' 
                     THEN 1 ELSE 0 END) AS pedidos_pendentes_pgto
            FROM orders WHERE status != 'cancelado'";
        $sO = $this->conn->prepare($qOrders);
        $sO->execute([':m1' => $month, ':y1' => $year]);
        $rowOrders = $sO->fetch(PDO::FETCH_ASSOC);

        // ── Query consolidada: parcelas (order_installments) ──
        $qInst = "SELECT 
            COALESCE(SUM(CASE WHEN status = 'pago' AND MONTH(paid_date) = :m2 AND YEAR(paid_date) = :y2 
                              THEN paid_amount ELSE 0 END), 0) AS recebido_mes,
            COALESCE(SUM(CASE WHEN status IN ('pendente','atrasado') THEN amount ELSE 0 END), 0) AS a_receber_total,
            COALESCE(SUM(CASE WHEN status IN ('pendente','atrasado') AND due_date < CURDATE() 
                              THEN amount ELSE 0 END), 0) AS atrasados_total,
            SUM(CASE WHEN is_confirmed = 0 AND status = 'pago' THEN 1 ELSE 0 END) AS pendentes_confirmacao
            FROM order_installments";
        $sI = $this->conn->prepare($qInst);
        $sI->execute([':m2' => $month, ':y2' => $year]);
        $rowInst = $sI->fetch(PDO::FETCH_ASSOC);

        // ── Query consolidada: transações financeiras (entradas + saídas do mês) ──
        $softDeleteTx = $this->hasSoftDeleteColumn() ? ' AND deleted_at IS NULL' : '';
        $qTx = "SELECT 
            COALESCE(SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END), 0) AS entradas_mes,
            COALESCE(SUM(CASE WHEN type = 'saida'   THEN amount ELSE 0 END), 0) AS saidas_mes
            FROM financial_transactions
            WHERE is_confirmed = 1
              AND category NOT IN ('estorno_pagamento', 'registro_ofx')
              AND MONTH(transaction_date) = :m3 AND YEAR(transaction_date) = :y3" . $softDeleteTx;
        $sT = $this->conn->prepare($qTx);
        $sT->execute([':m3' => $month, ':y3' => $year]);
        $rowTx = $sT->fetch(PDO::FETCH_ASSOC);

        return [
            'receita_mes'          => (float) ($rowOrders['receita_mes'] ?? 0),
            'recebido_mes'         => (float) ($rowInst['recebido_mes'] ?? 0),
            'entradas_mes'         => (float) ($rowTx['entradas_mes'] ?? 0),
            'saidas_mes'           => (float) ($rowTx['saidas_mes'] ?? 0),
            'a_receber_total'      => (float) ($rowInst['a_receber_total'] ?? 0),
            'atrasados_total'      => (float) ($rowInst['atrasados_total'] ?? 0),
            'pendentes_confirmacao'=> (int)   ($rowInst['pendentes_confirmacao'] ?? 0),
            'pedidos_pendentes_pgto' => (int) ($rowOrders['pedidos_pendentes_pgto'] ?? 0),
        ];
    }

    /**
     * Retorna dados para gráfico de receita x despesa dos últimos N meses
     * @param int $months Quantidade de meses
     * @return array Dados para gráfico
     */
    public function getChartData($months = 6) {
        // ── Calcular intervalo de datas ──
        $startDate = date('Y-m-01', strtotime("-" . ($months - 1) . " months"));
        $endDate   = date('Y-m-t'); // último dia do mês corrente

        // ── Recebido por mês (parcelas pagas) ──
        $qInst = "SELECT DATE_FORMAT(paid_date, '%Y-%m') AS label,
                         COALESCE(SUM(paid_amount), 0) AS total
                  FROM order_installments
                  WHERE status = 'pago' AND paid_date >= :start AND paid_date <= :end
                  GROUP BY DATE_FORMAT(paid_date, '%Y-%m')";
        $sI = $this->conn->prepare($qInst);
        $sI->execute([':start' => $startDate, ':end' => $endDate]);
        $instByMonth = [];
        foreach ($sI->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $instByMonth[$row['label']] = (float) $row['total'];
        }

        // ── Entradas e saídas manuais por mês (excluindo estornos e registros) ──
        $softDeleteChart = $this->hasSoftDeleteColumn() ? ' AND deleted_at IS NULL' : '';
        $qTx = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS label,
                       COALESCE(SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END), 0) AS entradas,
                       COALESCE(SUM(CASE WHEN type = 'saida'   THEN amount ELSE 0 END), 0) AS saidas
                FROM financial_transactions
                WHERE is_confirmed = 1
                  AND category NOT IN ('estorno_pagamento', 'registro_ofx')
                  AND transaction_date >= :start AND transaction_date <= :end" . $softDeleteChart . "
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')";
        $sT = $this->conn->prepare($qTx);
        $sT->execute([':start' => $startDate, ':end' => $endDate]);
        $txByMonth = [];
        foreach ($sT->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $txByMonth[$row['label']] = $row;
        }

        // ── Montar array de resultado ──
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $label = date('Y-m', strtotime("-{$i} months"));
            $entradas = ($instByMonth[$label] ?? 0) + (float)($txByMonth[$label]['entradas'] ?? 0);
            $saidas   = (float)($txByMonth[$label]['saidas'] ?? 0);

            $data[] = [
                'label'    => $label,
                'entradas' => $entradas,
                'saidas'   => $saidas,
            ];
        }
        return $data;
    }

    // ═══════════════════════════════════════════
    // PEDIDOS COM PAGAMENTO PENDENTE
    // ═══════════════════════════════════════════

    /**
     * Retorna pedidos com pagamento pendente
     * @return array Lista de pedidos
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
     * Retorna pedidos com parcelas (para tela de pagamentos)
     * @param array $filters Filtros opcionais
     * @return array Lista de pedidos
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
     * Retorna todas as parcelas de todos os pedidos (para tela de pagamentos)
     * @param array $filters Filtros opcionais
     * @return array Lista de parcelas
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
     * Retorna parcelas de um pedido
     * @param int $orderId ID do pedido
     * @return array Lista de parcelas
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
     * @param int $orderId ID do pedido
     * @return int Quantidade de parcelas
     */
    public function countInstallments($orderId) {
        $q = "SELECT COUNT(*) FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return (int) $s->fetchColumn();
    }

    /**
     * Remove todas as parcelas de um pedido
     * @param int $orderId ID do pedido
     * @return int Quantidade de parcelas removidas
     * Evento disparado: 'model.installment.deleted_all' com ['order_id', 'count']
     */
    public function deleteInstallmentsByOrder($orderId) {
        // Segurança: não deletar se há parcelas pagas
        if ($this->hasAnyPaidInstallment($orderId)) {
            return 0;
        }

        // Buscar parcelas antes de deletar (para evento)
        $qBefore = "SELECT COUNT(*) FROM order_installments WHERE order_id = :oid";
        $sBefore = $this->conn->prepare($qBefore);
        $sBefore->execute([':oid' => $orderId]);
        $countBefore = (int) $sBefore->fetchColumn();

        $q = "DELETE FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $deleted = $s->rowCount();

        if ($deleted > 0) {
            EventDispatcher::dispatch('model.installment.deleted_all', new Event('model.installment.deleted_all', [
                'order_id' => $orderId,
                'count' => $deleted,
            ]));
        }

        return $deleted;
    }

    /**
     * Gera parcelas para um pedido
     * @param int $orderId ID do pedido
     * @param float $totalAmount Valor total
     * @param int $numInstallments Número de parcelas
     * @param float $downPayment Valor de entrada
     * @param string|null $startDate Data inicial
     * @param array $dueDates Datas customizadas
     * @return bool Sucesso ou falha
     * Evento disparado: 'model.installment.generated' com ['order_id', 'total_amount', 'num_installments', 'down_payment', 'installment_value']
     */
    public function generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment = 0, $startDate = null, $dueDates = []) {
        // Segurança: não deletar se há parcelas pagas
        if ($this->hasAnyPaidInstallment($orderId)) {
            return false;
        }

        // Deletar parcelas anteriores (somente pendentes — segurança dupla)
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

        // Se houver entrada, registrar como parcela 0 pendente (permite alterar forma de pagamento)
        if ($downPayment > 0) {
            $qd = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
                   VALUES (:oid, 0, :amt, :due, 'pendente')";
            $sd = $this->conn->prepare($qd);
            $sd->execute([
                ':oid' => $orderId, ':amt' => $downPayment,
                ':due' => $startDate
            ]);
        }

        for ($i = 1; $i <= $numInstallments; $i++) {
            // Usar data de vencimento customizada se fornecida, senão calcular
            if (!empty($dueDates[$i])) {
                $dueDate = $dueDates[$i];
            } elseif ($numInstallments === 1) {
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

        EventDispatcher::dispatch('model.installment.generated', new Event('model.installment.generated', [
            'order_id' => $orderId,
            'total_amount' => $totalAmount,
            'num_installments' => $numInstallments,
            'down_payment' => $downPayment,
            'installment_value' => $installmentValue,
        ]));

        return true;
    }

    /**
     * Registra pagamento de uma parcela
     * @param int $installmentId ID da parcela
     * @param array $data Dados do pagamento
     * @param bool $autoConfirm Confirma automaticamente
     * @return bool Sucesso ou falha
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
     * Cria uma nova parcela com valor restante (pagamento parcial)
     * @param int $originalInstallmentId ID da parcela original
     * @param float $remainingAmount Valor restante
     * @param string|null $dueDate Data de vencimento
     * @return int|false ID da nova parcela ou false
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
     * Confirma pagamento de uma parcela (validação manual)
     * @param int $installmentId ID da parcela
     * @param int $userId ID do usuário
     * @return bool Sucesso ou falha
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
     * @param int $installmentId ID da parcela
     * @param int|null $userId ID do usuário
     * @return bool Sucesso ou falha
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
     * @param int $orderId ID do pedido
     * @return string Novo status
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
     * Atualiza parcelas vencidas para status 'atrasado'.
     * Executa no máximo 1x por request para evitar UPDATEs desnecessários (P11).
     * @return bool Sucesso ou falha
     */
    public function updateOverdueInstallments() {
        if ($this->overdueUpdatedThisRequest) {
            return true;
        }

        $q = "UPDATE order_installments 
              SET status = 'atrasado'
              WHERE status = 'pendente' AND due_date < CURDATE()";
        $s = $this->conn->prepare($q);
        $result = $s->execute();

        $this->overdueUpdatedThisRequest = true;
        return $result;
    }

    /**
     * Busca uma parcela pelo ID
     * @param int $id ID da parcela
     * @return array|null Dados da parcela ou null
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
     * Atualiza o valor (amount) de uma parcela
     * @param int $installmentId ID da parcela
     * @param float $amount Novo valor
     * @return bool Sucesso ou falha
     */
    public function updateInstallmentAmount($installmentId, $amount) {
        $q = "UPDATE order_installments SET amount = :amt, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':amt' => $amount, ':id' => $installmentId]);
    }

    /**
     * Retorna dados básicos de uma parcela (id, order_id, amount, installment_number)
     * @param int $installmentId ID da parcela
     * @return array|null Dados básicos ou null
     */
    public function getInstallmentBasic($installmentId) {
        $q = "SELECT id, order_id, amount, installment_number FROM order_installments WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Verifica se existe alguma parcela paga para um pedido
     * @param int $orderId ID do pedido
     * @return bool True se existe
     */
    public function hasAnyPaidInstallment($orderId) {
        $q = "SELECT COUNT(*) FROM order_installments WHERE order_id = :oid AND status = 'pago'";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return (int) $s->fetchColumn() > 0;
    }

    /**
     * Atualiza a data de vencimento de uma parcela
     * @param int $installmentId ID da parcela
     * @param string $dueDate Nova data de vencimento
     * @return bool Sucesso ou falha
     * Evento disparado: 'model.installment.due_date_updated' com ['id', 'due_date']
     */
    public function updateInstallmentDueDate($installmentId, $dueDate) {
        $q = "UPDATE order_installments SET due_date = :due, updated_at = NOW() WHERE id = :id AND status = 'pendente'";
        $s = $this->conn->prepare($q);
        $result = $s->execute([':due' => $dueDate, ':id' => $installmentId]);

        if ($result && $s->rowCount() > 0) {
            EventDispatcher::dispatch('model.installment.due_date_updated', new Event('model.installment.due_date_updated', [
                'id' => $installmentId,
                'due_date' => $dueDate,
            ]));
        }
        return $result;
    }

    /**
     * Atualiza os campos financeiros do pedido
     * @param int $orderId ID do pedido
     * @param array $data Dados financeiros
     * @return bool Sucesso ou falha
     * Evento disparado: 'model.order.financial_updated' com ['id', 'payment_method', 'installments', 'installment_value', 'down_payment']
     */
    public function updateOrderFinancialFields($orderId, $data) {
        $q = "UPDATE orders SET
                payment_method = :pm,
                installments = :inst,
                installment_value = :iv,
                down_payment = :dp
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $result = $s->execute([
            ':pm' => $data['payment_method'] ?? null,
            ':inst' => $data['installments'] ?? null,
            ':iv' => $data['installment_value'] ?? null,
            ':dp' => $data['down_payment'] ?? 0,
            ':id' => $orderId,
        ]);

        if ($result) {
            EventDispatcher::dispatch('model.order.financial_updated', new Event('model.order.financial_updated', [
                'id' => $orderId,
                'payment_method' => $data['payment_method'] ?? null,
                'installments' => $data['installments'] ?? null,
                'installment_value' => $data['installment_value'] ?? null,
                'down_payment' => $data['down_payment'] ?? 0,
            ]));
        }
        return $result;
    }

    /**
     * Salva o caminho do comprovante (attachment) em uma parcela
     * @param int $installmentId ID da parcela
     * @param string $path Caminho do arquivo
     * @return bool Sucesso ou falha
     */
    public function saveAttachment($installmentId, $path) {
        $q = "UPDATE order_installments SET attachment_path = :path, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':path' => $path, ':id' => $installmentId]);
    }

    /**
     * Remove o comprovante de uma parcela
     * @param int $installmentId ID da parcela
     * @return bool Sucesso ou falha
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

    // ═══════════════════════════════════════════════════════════
    // UNIFICAÇÃO E DIVISÃO DE PARCELAS
    // ═══════════════════════════════════════════════════════════

    /**
     * Unifica (merge) duas ou mais parcelas pendentes em uma única.
     * Apenas parcelas com status 'pendente' ou 'atrasado' podem ser unificadas.
     *
     * @param array $installmentIds IDs das parcelas a unificar
     * @param string $dueDate Data de vencimento da parcela unificada
     * @return int|false ID da nova parcela unificada ou false
     * Evento disparado: 'model.installment.merged'
     */
    public function mergeInstallments(array $installmentIds, $dueDate) {
        if (count($installmentIds) < 2) return false;

        // Buscar todas as parcelas — devem ser do mesmo pedido e estar pendentes/atrasadas
        $placeholders = implode(',', array_fill(0, count($installmentIds), '?'));
        $q = "SELECT * FROM order_installments WHERE id IN ($placeholders) AND status IN ('pendente','atrasado')";
        $s = $this->conn->prepare($q);
        $s->execute(array_values($installmentIds));
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) < 2) return false;

        // Verificar que todas são do mesmo pedido
        $orderIds = array_unique(array_column($rows, 'order_id'));
        if (count($orderIds) !== 1) return false;
        $orderId = (int)$orderIds[0];

        // Somar valores
        $totalAmount = 0;
        foreach ($rows as $row) {
            $totalAmount += (float)$row['amount'];
        }
        $totalAmount = round($totalAmount, 2);

        // Usar transação para garantir consistência (evita constraint violation na uk_order_installment)
        $this->conn->beginTransaction();

        try {
            // Deletar as parcelas antigas
            $qDel = "DELETE FROM order_installments WHERE id IN ($placeholders) AND status IN ('pendente','atrasado')";
            $sDel = $this->conn->prepare($qDel);
            $sDel->execute(array_values($installmentIds));

            // Obter o maior installment_number atual para este pedido (evita colisão com a unique key)
            $qMax = "SELECT COALESCE(MAX(installment_number), 0) as max_num FROM order_installments WHERE order_id = :oid";
            $sMax = $this->conn->prepare($qMax);
            $sMax->execute([':oid' => $orderId]);
            $maxNum = (int)$sMax->fetch(PDO::FETCH_ASSOC)['max_num'];

            // Criar a nova parcela unificada com número temporário alto
            $tempNum = $maxNum + 9000;
            $qIns = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
                     VALUES (:oid, :num, :amt, :due, 'pendente')";
            $sIns = $this->conn->prepare($qIns);
            $sIns->execute([
                ':oid' => $orderId,
                ':num' => $tempNum,
                ':amt' => $totalAmount,
                ':due' => $dueDate,
            ]);
            $newId = (int)$this->conn->lastInsertId();

            // Renumerar parcelas restantes para manter sequência
            $this->renumberInstallments($orderId);

            // Atualizar status do pedido
            $this->updateOrderPaymentStatus($orderId);

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return false;
        }

        EventDispatcher::dispatch('model.installment.merged', new Event('model.installment.merged', [
            'order_id' => $orderId,
            'merged_ids' => $installmentIds,
            'new_id' => $newId,
            'amount' => $totalAmount,
        ]));

        return $newId;
    }

    /**
     * Divide uma parcela pendente em N parcelas menores.
     * Apenas parcelas com status 'pendente' ou 'atrasado' podem ser divididas.
     *
     * @param int $installmentId ID da parcela a dividir
     * @param int $parts Quantidade de partes
     * @param string|null $firstDueDate Data de vencimento da primeira nova parcela (usa a original se null)
     * @return array IDs das novas parcelas criadas
     * Evento disparado: 'model.installment.split'
     */
    public function splitInstallment($installmentId, $parts, $firstDueDate = null) {
        if ($parts < 2) return [];

        $q = "SELECT * FROM order_installments WHERE id = :id AND status IN ('pendente','atrasado')";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);
        $original = $s->fetch(PDO::FETCH_ASSOC);

        if (!$original) return [];

        $orderId = (int)$original['order_id'];
        $totalAmount = (float)$original['amount'];

        // Calcular valor por parte
        $perPart = round($totalAmount / $parts, 2);
        // Última parcela absorve centavos
        $lastPart = round($totalAmount - ($perPart * ($parts - 1)), 2);

        // Data base
        $baseDueDate = $firstDueDate ?: $original['due_date'];

        // Usar transação para garantir consistência (evita constraint violation na uk_order_installment)
        $this->conn->beginTransaction();

        try {
            // Deletar a parcela original
            $qDel = "DELETE FROM order_installments WHERE id = :id AND status IN ('pendente','atrasado')";
            $sDel = $this->conn->prepare($qDel);
            $sDel->execute([':id' => $installmentId]);

            if ($sDel->rowCount() === 0) {
                $this->conn->rollBack();
                return [];
            }

            // Obter o maior installment_number atual para este pedido (evita colisão com a unique key)
            $qMax = "SELECT COALESCE(MAX(installment_number), 0) as max_num FROM order_installments WHERE order_id = :oid";
            $sMax = $this->conn->prepare($qMax);
            $sMax->execute([':oid' => $orderId]);
            $maxNum = (int)$sMax->fetch(PDO::FETCH_ASSOC)['max_num'];

            // Inserir as novas parcelas com números temporários altos (max + 9000 + i)
            // para evitar conflito com a unique key (order_id, installment_number)
            $newIds = [];
            $qIns = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
                     VALUES (:oid, :num, :amt, :due, 'pendente')";
            $sIns = $this->conn->prepare($qIns);

            for ($i = 0; $i < $parts; $i++) {
                $dueDate = date('Y-m-d', strtotime($baseDueDate . " + " . ($i * 30) . " days"));
                $amount = ($i === $parts - 1) ? $lastPart : $perPart;
                $tempNum = $maxNum + 9000 + $i + 1; // número temporário alto, sem colisão
                $sIns->execute([
                    ':oid' => $orderId,
                    ':num' => $tempNum,
                    ':amt' => $amount,
                    ':due' => $dueDate,
                ]);
                $newIds[] = (int)$this->conn->lastInsertId();
            }

            // Renumerar parcelas para manter sequência limpa (corrige os números temporários)
            $this->renumberInstallments($orderId);

            // Atualizar status do pedido
            $this->updateOrderPaymentStatus($orderId);

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return [];
        }

        EventDispatcher::dispatch('model.installment.split', new Event('model.installment.split', [
            'order_id' => $orderId,
            'original_id' => $installmentId,
            'parts' => $parts,
            'new_ids' => $newIds,
            'original_amount' => $totalAmount,
        ]));

        return $newIds;
    }

    /**
     * Renumera as parcelas de um pedido para manter sequência contínua.
     * Preserva entrada (installment_number=0) como 0, e as regulares são renumeradas 1, 2, 3...
     *
     * @param int $orderId ID do pedido
     * @return void
     */
    private function renumberInstallments($orderId) {
        $q = "SELECT id, installment_number FROM order_installments
              WHERE order_id = :oid
              ORDER BY installment_number ASC, due_date ASC, id ASC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $all = $s->fetchAll(PDO::FETCH_ASSOC);

        $qUpd = "UPDATE order_installments SET installment_number = :num WHERE id = :id";
        $sUpd = $this->conn->prepare($qUpd);

        // Fase 1: Mover todos os não-zero para números temporários altos (evita colisão com unique key)
        $tempBase = 90000;
        foreach ($all as $idx => $row) {
            if ((int)$row['installment_number'] !== 0) {
                $sUpd->execute([':num' => $tempBase + $idx, ':id' => $row['id']]);
            }
        }

        // Fase 2: Renumerar de 1 em diante na ordem correta
        // Re-buscar ordenando pelo vencimento para manter a sequência lógica por data
        $q2 = "SELECT id, installment_number FROM order_installments
               WHERE order_id = :oid
               ORDER BY (installment_number = 0) DESC, due_date ASC, id ASC";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':oid' => $orderId]);
        $all = $s2->fetchAll(PDO::FETCH_ASSOC);

        $num = 1;
        foreach ($all as $row) {
            if ((int)$row['installment_number'] === 0) {
                // Entrada mantém número 0
                continue;
            } else {
                $sUpd->execute([':num' => $num, ':id' => $row['id']]);
                $num++;
            }
        }

        // Atualizar campo installments e installment_value na tabela orders
        $regularCount = $num - 1;
        if ($regularCount > 0) {
            $qSum = "SELECT SUM(amount) FROM order_installments WHERE order_id = :oid AND installment_number > 0";
            $sSum = $this->conn->prepare($qSum);
            $sSum->execute([':oid' => $orderId]);
            $totalRegular = (float)$sSum->fetchColumn();
            $installmentValue = round($totalRegular / $regularCount, 2);

            $qOrd = "UPDATE orders SET installments = :inst, installment_value = :iv WHERE id = :id";
            $sOrd = $this->conn->prepare($qOrd);
            $sOrd->execute([
                ':inst' => ($regularCount >= 2) ? $regularCount : null,
                ':iv' => ($regularCount >= 2) ? $installmentValue : null,
                ':id' => $orderId,
            ]);
        }
    }

    /**
     * Retorna o pipeline_stage de um pedido
     * @param int $orderId ID do pedido
     * @return string|null Pipeline stage ou null se não encontrado
     */
    public function getOrderPipelineStage($orderId) {
        $q = "SELECT pipeline_stage FROM orders WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['pipeline_stage'] : null;
    }

    /**
     * Retorna total_amount e discount de um pedido (para cálculo de parcelas)
     * @param int $orderId ID do pedido
     * @return array|null ['total_amount' => float, 'discount' => float] ou null
     */
    public function getOrderFinancialTotals($orderId) {
        $q = "SELECT total_amount, COALESCE(discount, 0) as discount FROM orders WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna parcelas pendentes de confirmação (pagamentos manuais)
     * @return array Lista de parcelas
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
     * Retorna próximas parcelas a vencer
     * @param int $days Dias para vencimento
     * @return array Lista de parcelas
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
     * Retorna parcelas vencidas não pagas
     * @return array Lista de parcelas
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
     * @param array $data Dados da transação
     * @return bool Sucesso ou falha
     * Evento disparado: 'model.financial_transaction.created' com ['id', 'type', 'category', 'amount']
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
     * Busca uma transação pelo ID
     * @param int $id ID da transação
     * @return array|false Dados da transação ou false
     */
    public function getTransactionById($id) {
        $softDeleteFilter = $this->hasSoftDeleteColumn() ? ' AND ft.deleted_at IS NULL' : '';
        $q = "SELECT ft.*, u.name as user_name
              FROM financial_transactions ft
              LEFT JOIN users u ON ft.user_id = u.id
              WHERE ft.id = :id" . $softDeleteFilter;
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza uma transação existente
     * @param int $id ID da transação
     * @param array $data Dados atualizados
     * @return bool Sucesso ou falha
     * Evento disparado: 'model.financial_transaction.updated'
     */
    public function updateTransaction($id, $data) {
        $q = "UPDATE financial_transactions SET
                type = :type,
                category = :cat,
                description = :desc,
                amount = :amt,
                transaction_date = :date,
                payment_method = :method,
                notes = :notes
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $result = $s->execute([
            ':type'   => $data['type'],
            ':cat'    => $data['category'],
            ':desc'   => $data['description'],
            ':amt'    => $data['amount'],
            ':date'   => $data['transaction_date'],
            ':method' => $data['payment_method'] ?? null,
            ':notes'  => $data['notes'] ?? null,
            ':id'     => $id,
        ]);
        if ($result) {
            EventDispatcher::dispatch('model.financial_transaction.updated', new Event('model.financial_transaction.updated', [
                'id' => $id,
                'type' => $data['type'],
                'category' => $data['category'],
                'amount' => $data['amount'],
            ]));
        }
        return $result;
    }

    /**
     * Lista transações com filtros
     * @param array $filters Filtros opcionais
     * @return array Lista de transações
     */
    public function getTransactions($filters = []) {
        $softDeleteFilter = $this->hasSoftDeleteColumn() ? ' AND ft.deleted_at IS NULL' : '';
        $where = "WHERE 1=1" . $softDeleteFilter;
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
     * Lista transações com filtros e paginação
     * @param array $filters Filtros opcionais
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @return array ['data' => [...], 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int, 'totalEntradas' => float, 'totalSaidas' => float]
     */
    public function getTransactionsPaginated($filters = [], $page = 1, $perPage = 25) {
        $softDeleteFilter = $this->hasSoftDeleteColumn() ? ' AND ft.deleted_at IS NULL' : '';
        $where = "WHERE 1=1" . $softDeleteFilter;
        $params = [];

        if (!empty($filters['type'])) {
            $where .= " AND ft.type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $where .= " AND MONTH(ft.transaction_date) = :m AND YEAR(ft.transaction_date) = :y";
            $params[':m'] = $filters['month'];
            $params[':y'] = $filters['year'];
        } elseif (!empty($filters['month'])) {
            $where .= " AND MONTH(ft.transaction_date) = :m";
            $params[':m'] = $filters['month'];
        } elseif (!empty($filters['year'])) {
            $where .= " AND YEAR(ft.transaction_date) = :y";
            $params[':y'] = $filters['year'];
        }
        if (!empty($filters['category'])) {
            $where .= " AND ft.category = :cat";
            $params[':cat'] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $where .= " AND (ft.description LIKE :search OR ft.notes LIKE :search2)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND ft.transaction_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND ft.transaction_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        // Count total
        $qCount = "SELECT COUNT(*) FROM financial_transactions ft $where";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute($params);
        $total = (int) $sCount->fetchColumn();

        // Totals (entradas/saidas confirmadas, excluindo estornos e registros)
        $qEntradas = "SELECT COALESCE(SUM(ft.amount), 0) FROM financial_transactions ft $where AND ft.type = 'entrada' AND ft.is_confirmed = 1 AND ft.category NOT IN ('estorno_pagamento', 'registro_ofx')";
        $sEntradas = $this->conn->prepare($qEntradas);
        $sEntradas->execute($params);
        $totalEntradas = (float) $sEntradas->fetchColumn();

        $qSaidas = "SELECT COALESCE(SUM(ft.amount), 0) FROM financial_transactions ft $where AND ft.type = 'saida' AND ft.is_confirmed = 1 AND ft.category NOT IN ('estorno_pagamento', 'registro_ofx')";
        $sSaidas = $this->conn->prepare($qSaidas);
        $sSaidas->execute($params);
        $totalSaidas = (float) $sSaidas->fetchColumn();

        // Paginated data
        $offset = max(0, ($page - 1) * $perPage);
        $q = "SELECT ft.*, u.name as user_name
              FROM financial_transactions ft
              LEFT JOIN users u ON ft.user_id = u.id
              $where
              ORDER BY ft.transaction_date DESC, ft.id DESC
              LIMIT $perPage OFFSET $offset";
        $s = $this->conn->prepare($q);
        $s->execute($params);
        $data = $s->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
        ];
    }

    /**
     * Lista parcelas com filtros e paginação
     * @param array $filters Filtros opcionais
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @return array ['data' => [...], 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int, 'summary' => [...]]
     */
    public function getAllInstallmentsPaginated($filters = [], $page = 1, $perPage = 25) {
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
        if (!empty($filters['search'])) {
            $where .= " AND (c.name LIKE :search OR o.id LIKE :search2)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        // Count
        $qCount = "SELECT COUNT(*) FROM order_installments oi JOIN orders o ON oi.order_id = o.id LEFT JOIN customers c ON o.customer_id = c.id $where";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute($params);
        $total = (int) $sCount->fetchColumn();

        // Summary totals
        $qSummary = "SELECT 
                        COUNT(*) as total_parcelas,
                        SUM(CASE WHEN oi.status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                        SUM(CASE WHEN oi.status = 'atrasado' THEN 1 ELSE 0 END) as atrasadas,
                        SUM(CASE WHEN oi.status = 'pago' THEN 1 ELSE 0 END) as pagas,
                        SUM(CASE WHEN oi.status = 'pago' AND oi.is_confirmed = 0 THEN 1 ELSE 0 END) as aguardando,
                        COALESCE(SUM(CASE WHEN oi.status IN ('pendente','atrasado') THEN oi.amount ELSE 0 END), 0) as valor_pendente,
                        COALESCE(SUM(CASE WHEN oi.status = 'pago' THEN COALESCE(oi.paid_amount, oi.amount) ELSE 0 END), 0) as valor_pago
                     FROM order_installments oi 
                     JOIN orders o ON oi.order_id = o.id 
                     LEFT JOIN customers c ON o.customer_id = c.id 
                     $where";
        $sSummary = $this->conn->prepare($qSummary);
        $sSummary->execute($params);
        $summary = $sSummary->fetch(PDO::FETCH_ASSOC);

        // Paginated data
        $offset = max(0, ($page - 1) * $perPage);
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
                oi.due_date ASC, oi.order_id ASC, oi.installment_number ASC
              LIMIT $perPage OFFSET $offset";
        $s = $this->conn->prepare($q);
        $s->execute($params);
        $data = $s->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'summary' => $summary,
        ];
    }

    /**
     * Importa transações de um arquivo CSV/Excel (mapeamento de colunas)
     * @param array $rows Linhas já parseadas
     * @param array $mapping Mapeamento de colunas [field => column_index]
     * @param array $selectedRows Índices de linhas selecionadas para importar
     * @param string $mode 'registro' ou 'contabilizar'
     * @param int|null $userId ID do usuário
     * @return array Resultado da importação
     */
    public function importCsvMapped($rows, $mapping, $selectedRows, $mode = 'registro', $userId = null) {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $dateCol = $mapping['date'] ?? null;
        $descCol = $mapping['description'] ?? null;
        $amountCol = $mapping['amount'] ?? null;
        $typeCol = $mapping['type'] ?? null;
        $categoryCol = $mapping['category'] ?? null;
        $paymentMethodCol = $mapping['payment_method'] ?? null;
        $notesCol = $mapping['notes'] ?? null;

        foreach ($selectedRows as $idx) {
            if (!isset($rows[$idx])) continue;
            $row = $rows[$idx];

            try {
                $amount = 0;
                if ($amountCol !== null && isset($row[$amountCol])) {
                    $rawAmount = str_replace(['.', ',', 'R$', ' '], ['', '.', '', ''], $row[$amountCol]);
                    $amount = abs((float) $rawAmount);
                }
                if ($amount <= 0) { $result['skipped']++; continue; }

                $description = ($descCol !== null && isset($row[$descCol])) ? trim($row[$descCol]) : 'Importação CSV';
                $date = ($dateCol !== null && isset($row[$dateCol])) ? $this->parseCsvDate(trim($row[$dateCol])) : date('d/m/Y');

                // Determinar tipo
                $isCredit = true;
                if ($typeCol !== null && isset($row[$typeCol])) {
                    $typeVal = strtolower(trim($row[$typeCol]));
                    $isCredit = !in_array($typeVal, ['saida', 'saída', 'debito', 'débito', 'despesa', 'D', '-']);
                } else {
                    // Se não tem coluna de tipo, verificar se amount original era negativo
                    if ($amountCol !== null && isset($row[$amountCol])) {
                        $rawVal = str_replace(['.', ',', 'R$', ' '], ['', '.', '', ''], $row[$amountCol]);
                        $isCredit = ((float)$rawVal >= 0);
                    }
                }

                // Campos opcionais do mapeamento
                $categoryVal = ($categoryCol !== null && isset($row[$categoryCol])) ? trim($row[$categoryCol]) : null;
                $paymentMethodVal = ($paymentMethodCol !== null && isset($row[$paymentMethodCol])) ? trim($row[$paymentMethodCol]) : 'transferencia';
                $notesVal = ($notesCol !== null && isset($row[$notesCol])) ? trim($row[$notesCol]) : '';

                // Converter date dd/mm/yyyy → Y-m-d para armazenamento
                $dateDb = $date;
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $dm)) {
                    $dateDb = $dm[3] . '-' . $dm[2] . '-' . $dm[1];
                }

                if ($mode === 'registro') {
                    $data = [
                        'type' => 'registro',
                        'category' => $categoryVal ?: 'registro_ofx',
                        'description' => $description,
                        'amount' => $amount,
                        'transaction_date' => $dateDb,
                        'reference_type' => 'csv',
                        'payment_method' => $paymentMethodVal,
                        'is_confirmed' => 1,
                        'user_id' => $userId,
                        'notes' => $notesVal ?: 'Importado via CSV/Excel (registro)',
                    ];
                } else {
                    $data = [
                        'type' => $isCredit ? 'entrada' : 'saida',
                        'category' => $categoryVal ?: ($isCredit ? 'outra_entrada' : 'outra_saida'),
                        'description' => $description,
                        'amount' => $amount,
                        'transaction_date' => $dateDb,
                        'reference_type' => 'csv',
                        'payment_method' => $paymentMethodVal,
                        'is_confirmed' => 1,
                        'user_id' => $userId,
                        'notes' => $notesVal ?: 'Importado via CSV/Excel (contabilizado)',
                    ];
                }

                $this->addTransaction($data);
                $result['imported']++;
            } catch (\Exception $e) {
                $result['errors'][] = "Linha $idx: " . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Parse de data CSV em vários formatos.
     * Retorna sempre no formato dd/mm/yyyy para exibição, mantendo compatibilidade.
     * @param string $dateStr Data do CSV
     * @return string Data no formato dd/mm/yyyy
     */
    private function parseCsvDate($dateStr) {
        // Tentar dd/mm/yyyy — já no formato correto
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
            return $m[1] . '/' . $m[2] . '/' . $m[3];
        }
        // Tentar dd-mm-yyyy
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dateStr, $m)) {
            return $m[1] . '/' . $m[2] . '/' . $m[3];
        }
        // Tentar yyyy-mm-dd
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateStr, $m)) {
            return $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        // Tentar yyyymmdd (OFX format)
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $dateStr, $m)) {
            return $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        // Fallback: strtotime
        $ts = strtotime($dateStr);
        return $ts ? date('d/m/Y', $ts) : date('d/m/Y');
    }

    /**
     * Deleta transação (soft-delete se a coluna deleted_at existir, hard delete caso contrário).
     * @param int $id ID da transação
     * @return bool Sucesso ou falha
     * Evento disparado: 'model.financial_transaction.deleted' com ['id']
     */
    public function deleteTransaction($id) {
        if ($this->hasSoftDeleteColumn()) {
            $q = "UPDATE financial_transactions SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL";
        } else {
            $q = "DELETE FROM financial_transactions WHERE id = :id";
        }

        $s = $this->conn->prepare($q);
        $result = $s->execute([':id' => $id]);

        if ($result) {
            EventDispatcher::dispatch('model.financial_transaction.deleted', new Event('model.financial_transaction.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Restaura transação soft-deleted.
     * @param int $id ID da transação
     * @return bool
     */
    public function restoreTransaction(int $id): bool {
        if (!$this->hasSoftDeleteColumn()) {
            return false;
        }

        $q = "UPDATE financial_transactions SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id]);
    }

    /**
     * Verifica se a coluna deleted_at existe em financial_transactions.
     * @var bool|null Cache estático
     */
    private static ?bool $hasSoftDelete = null;

    public function hasSoftDeleteColumn(): bool {
        if (self::$hasSoftDelete !== null) {
            return self::$hasSoftDelete;
        }

        try {
            $dbname = $this->conn->query("SELECT DATABASE()")->fetchColumn();
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'financial_transactions' AND COLUMN_NAME = 'deleted_at'"
            );
            $stmt->execute([':db' => $dbname]);
            self::$hasSoftDelete = (int) $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            self::$hasSoftDelete = false;
        }

        return self::$hasSoftDelete;
    }

    /**
     * Cache estático para categorias carregadas do banco.
     * @var array|null
     */
    private static ?array $categoriesCache = null;

    /**
     * Retorna categorias de transação disponíveis.
     * Tenta carregar da tabela financial_categories (dinâmicas).
     * Fallback para array estático se a tabela não existir.
     *
     * @return array ['entrada' => [...], 'saida' => [...]]
     */
    public static function getCategories(): array {
        if (self::$categoriesCache !== null) {
            return self::$categoriesCache;
        }

        // Tentar carregar categorias dinâmicas do banco
        try {
            if (class_exists('Database')) {
                $db = (new \Database())->getConnection();
                $stmt = $db->query("SHOW TABLES LIKE 'financial_categories'");
                if ($stmt->rowCount() > 0) {
                    $q = "SELECT slug, name, type FROM financial_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
                    $rows = $db->query($q)->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $cats = ['entrada' => [], 'saida' => []];
                        foreach ($rows as $row) {
                            // Ignorar categorias internas no retorno principal
                            if (in_array($row['slug'], ['estorno_pagamento', 'registro_ofx'])) {
                                continue;
                            }
                            if ($row['type'] === 'entrada' || $row['type'] === 'ambos') {
                                $cats['entrada'][$row['slug']] = $row['name'];
                            }
                            if ($row['type'] === 'saida' || $row['type'] === 'ambos') {
                                $cats['saida'][$row['slug']] = $row['name'];
                            }
                        }
                        self::$categoriesCache = $cats;
                        return $cats;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso: fallback para array estático
        }

        // Fallback: categorias estáticas (compatibilidade)
        self::$categoriesCache = [
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
        return self::$categoriesCache;
    }

    /**
     * Retorna categorias internas (usadas pelo sistema).
     * Tenta carregar da tabela financial_categories.
     * @return array slug => name
     */
    public static function getInternalCategories(): array {
        try {
            if (class_exists('Database')) {
                $db = (new \Database())->getConnection();
                $stmt = $db->query("SHOW TABLES LIKE 'financial_categories'");
                if ($stmt->rowCount() > 0) {
                    $q = "SELECT slug, name FROM financial_categories WHERE slug IN ('estorno_pagamento','registro_ofx') AND is_active = 1";
                    $rows = $db->query($q)->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($rows)) {
                        $result = [];
                        foreach ($rows as $row) {
                            $result[$row['slug']] = $row['name'];
                        }
                        return $result;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return [
            'estorno_pagamento' => 'Estorno de Pagamento',
            'registro_ofx' => 'Registro OFX',
        ];
    }

    /**
     * Retorna todas as categorias com metadados (para telas de administração).
     * @return array Lista de categorias com id, slug, name, type, icon, color, is_system
     */
    public static function getAllCategoriesDetailed(): array {
        try {
            if (class_exists('Database')) {
                $db = (new \Database())->getConnection();
                $stmt = $db->query("SHOW TABLES LIKE 'financial_categories'");
                if ($stmt->rowCount() > 0) {
                    $q = "SELECT * FROM financial_categories ORDER BY sort_order ASC, name ASC";
                    return $db->query($q)->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }
        return [];
    }

    /**
     * Invalida o cache de categorias (chamar após criar/editar/excluir categorias).
     */
    public static function clearCategoriesCache(): void {
        self::$categoriesCache = null;
    }

    /**
     * Importa transações de um arquivo OFX
     * @param string $filePath Caminho do arquivo OFX
     * @param string $mode 'registro' ou 'contabilizar'
     * @param int|null $userId ID do usuário
     * @return array Resultado da importação
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
     * @param string $content Conteúdo OFX
     * @return array Lista de transações
     */
    public function parseOfxTransactions($content) {
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
     * @param string $block Bloco de texto
     * @param string $tag Nome da tag
     * @return string Valor extraído
     */
    private function extractOfxTag($block, $tag) {
        if (preg_match("/<{$tag}>(.*?)(?:<|$)/mi", $block, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /**
     * Converte data OFX (YYYYMMDD ou YYYYMMDDHHMMSS) para Y-m-d
     * @param string $dateStr Data OFX
     * @return string Data formatada
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
