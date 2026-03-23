<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: Installment
 * Gerencia operações de parcelas (order_installments).
 * Extraído do God Model Financial.php para single responsibility.
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.installment.*'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 *
 * @package Akti\Models
 */
class Installment
{
    private $conn;

    /**
     * Flag para evitar execução múltipla de updateOverdue no mesmo request.
     * @var bool
     */
    private bool $overdueUpdatedThisRequest = false;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════
    // LEITURA
    // ═══════════════════════════════════════════

    /**
     * Busca uma parcela pelo ID (dados completos com join).
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $q = "SELECT oi.*, o.payment_method as order_payment_method, c.name as customer_name
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE oi.id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna dados básicos de uma parcela (id, order_id, amount, installment_number).
     * @param int $id
     * @return array|null
     */
    public function getBasic(int $id): ?array
    {
        $q = "SELECT id, order_id, amount, installment_number FROM order_installments WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna parcelas de um pedido.
     * @param int $orderId
     * @return array
     */
    public function getByOrderId(int $orderId): array
    {
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
     * Conta parcelas de um pedido.
     * @param int $orderId
     * @return int
     */
    public function countByOrderId(int $orderId): int
    {
        $q = "SELECT COUNT(*) FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return (int) $s->fetchColumn();
    }

    /**
     * Verifica se existe alguma parcela paga para um pedido.
     * @param int $orderId
     * @return bool
     */
    public function hasAnyPaid(int $orderId): bool
    {
        $q = "SELECT COUNT(*) FROM order_installments WHERE order_id = :oid AND status = 'pago'";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return (int) $s->fetchColumn() > 0;
    }

    /**
     * Retorna parcelas pendentes de confirmação.
     * @return array
     */
    public function getPendingConfirmations(): array
    {
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
     * Retorna próximas parcelas a vencer.
     * @param int $days
     * @return array
     */
    public function getUpcoming(int $days = 7): array
    {
        $q = "SELECT oi.*, o.id as order_id, c.name as customer_name
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
     * Retorna parcelas vencidas não pagas.
     * @return array
     */
    public function getOverdue(): array
    {
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

    /**
     * Lista parcelas com filtros e paginação.
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
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

        // Summary
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
            'data'       => $data,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'summary'    => $summary,
        ];
    }

    // ═══════════════════════════════════════════
    // ESCRITA / MUTAÇÃO
    // ═══════════════════════════════════════════

    /**
     * Remove todas as parcelas de um pedido (somente se não houver pagas).
     * @param int $orderId
     * @return int Quantidade removida
     */
    public function deleteByOrderId(int $orderId): int
    {
        if ($this->hasAnyPaid($orderId)) {
            return 0;
        }

        $q = "DELETE FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $deleted = $s->rowCount();

        if ($deleted > 0) {
            EventDispatcher::dispatch('model.installment.deleted_all', new Event('model.installment.deleted_all', [
                'order_id' => $orderId,
                'count'    => $deleted,
            ]));
        }

        return $deleted;
    }

    /**
     * Gera parcelas para um pedido.
     * @param int $orderId
     * @param float $totalAmount
     * @param int $numInstallments
     * @param float $downPayment
     * @param string|null $startDate
     * @param array $dueDates
     * @return bool
     */
    public function generate(int $orderId, float $totalAmount, int $numInstallments, float $downPayment = 0, ?string $startDate = null, array $dueDates = []): bool
    {
        if ($this->hasAnyPaid($orderId)) {
            return false;
        }

        // Deletar parcelas anteriores
        $q = "DELETE FROM order_installments WHERE order_id = :oid";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);

        if ($numInstallments <= 0) $numInstallments = 1;
        if (!$startDate) $startDate = date('Y-m-d');

        $remaining = $totalAmount - $downPayment;
        if ($remaining <= 0) $remaining = $totalAmount;
        $installmentValue = round($remaining / $numInstallments, 2);
        $lastValue = round($remaining - ($installmentValue * ($numInstallments - 1)), 2);

        $q = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
              VALUES (:oid, :num, :amt, :due, 'pendente')";
        $s = $this->conn->prepare($q);

        // Entrada (parcela 0)
        if ($downPayment > 0) {
            $qd = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
                   VALUES (:oid, 0, :amt, :due, 'pendente')";
            $sd = $this->conn->prepare($qd);
            $sd->execute([':oid' => $orderId, ':amt' => $downPayment, ':due' => $startDate]);
        }

        for ($i = 1; $i <= $numInstallments; $i++) {
            if (!empty($dueDates[$i])) {
                $dueDate = $dueDates[$i];
            } elseif ($numInstallments === 1) {
                $dueDate = $startDate;
            } else {
                $dueDate = date('Y-m-d', strtotime($startDate . " + {$i} months"));
            }
            $amount = ($i === $numInstallments) ? $lastValue : $installmentValue;
            $s->execute([':oid' => $orderId, ':num' => $i, ':amt' => $amount, ':due' => $dueDate]);
        }

        EventDispatcher::dispatch('model.installment.generated', new Event('model.installment.generated', [
            'order_id'         => $orderId,
            'total_amount'     => $totalAmount,
            'num_installments' => $numInstallments,
            'down_payment'     => $downPayment,
            'installment_value'=> $installmentValue,
        ]));

        return true;
    }

    /**
     * Registra pagamento de uma parcela.
     * @param int $installmentId
     * @param array $data
     * @param bool $autoConfirm
     * @return array|false ['order_id' => int] or false
     */
    public function pay(int $installmentId, array $data, bool $autoConfirm = false)
    {
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
            ':paid_date'    => $data['paid_date'] ?? date('Y-m-d'),
            ':paid_amount'  => $data['paid_amount'],
            ':method'       => $data['payment_method'] ?? null,
            ':confirmed'    => $isConfirmed,
            ':confirmed_by' => $autoConfirm ? ($data['user_id'] ?? null) : null,
            ':confirmed2'   => $isConfirmed,
            ':notes'        => $data['notes'] ?? null,
            ':attachment'   => $data['attachment_path'] ?? null,
            ':id'           => $installmentId,
        ]);

        // Buscar order_id
        $q2 = "SELECT order_id FROM order_installments WHERE id = :id";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':id' => $installmentId]);
        $row = $s2->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            EventDispatcher::dispatch('model.installment.paid', new Event('model.installment.paid', [
                'installment_id' => $installmentId,
                'order_id'       => (int)$row['order_id'],
                'paid_amount'    => $data['paid_amount'],
                'auto_confirmed' => $autoConfirm,
                'user_id'        => $data['user_id'] ?? null,
            ]));
            return ['order_id' => (int) $row['order_id']];
        }

        return false;
    }

    /**
     * Cria uma nova parcela com valor restante (pagamento parcial).
     * @param int $originalInstallmentId
     * @param float $remainingAmount
     * @param string|null $dueDate
     * @return int|false ID da nova parcela ou false
     */
    public function createRemaining(int $originalInstallmentId, float $remainingAmount, ?string $dueDate = null)
    {
        $q = "SELECT order_id, installment_number, due_date FROM order_installments WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $originalInstallmentId]);
        $original = $s->fetch(PDO::FETCH_ASSOC);

        if (!$original) return false;

        $q2 = "SELECT MAX(installment_number) FROM order_installments WHERE order_id = :oid";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':oid' => $original['order_id']]);
        $maxNum = (int) $s2->fetchColumn();
        $nextNum = $maxNum + 1;

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

        $newId = (int) $this->conn->lastInsertId();
        $this->updateOrderPaymentStatus($original['order_id']);

        return $newId;
    }

    /**
     * Confirma pagamento de uma parcela.
     * @param int $installmentId
     * @param int|null $userId
     * @return bool
     */
    public function confirm(int $installmentId, ?int $userId = null): bool
    {
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

        // Recalcular payment_status
        $q3 = "SELECT order_id FROM order_installments WHERE id = :id";
        $s3 = $this->conn->prepare($q3);
        $s3->execute([':id' => $installmentId]);
        $row = $s3->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->updateOrderPaymentStatus((int)$row['order_id']);

            EventDispatcher::dispatch('model.installment.confirmed', new Event('model.installment.confirmed', [
                'installment_id' => $installmentId,
                'order_id'       => (int)$row['order_id'],
                'confirmed_by'   => $userId,
            ]));
        }

        return true;
    }

    /**
     * Cancela/estorna uma parcela. Retorna dados pré-cancelamento para registro de transação.
     * @param int $installmentId
     * @param int|null $userId
     * @return array|null Dados da parcela antes do cancelamento, ou null
     */
    public function cancel(int $installmentId, ?int $userId = null): ?array
    {
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
            $this->updateOrderPaymentStatus((int)$parcelaAntes['order_id']);

            // Remover a transação de entrada original
            $qDel = "DELETE FROM financial_transactions
                     WHERE reference_type = 'installment' AND reference_id = :rid AND type = 'entrada'";
            $sDel = $this->conn->prepare($qDel);
            $sDel->execute([':rid' => $installmentId]);

            EventDispatcher::dispatch('model.installment.cancelled', new Event('model.installment.cancelled', [
                'installment_id' => $installmentId,
                'order_id'       => (int)$parcelaAntes['order_id'],
                'cancelled_by'   => $userId,
                'original_amount'=> (float)($parcelaAntes['paid_amount'] ?? $parcelaAntes['amount']),
            ]));
        }

        return $parcelaAntes;
    }

    /**
     * Atualiza o valor (amount) de uma parcela.
     * @param int $installmentId
     * @param float $amount
     * @return bool
     */
    public function updateAmount(int $installmentId, float $amount): bool
    {
        $q = "UPDATE order_installments SET amount = :amt, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':amt' => $amount, ':id' => $installmentId]);
    }

    /**
     * Atualiza a data de vencimento de uma parcela.
     * @param int $installmentId
     * @param string $dueDate
     * @return bool
     */
    public function updateDueDate(int $installmentId, string $dueDate): bool
    {
        $q = "UPDATE order_installments SET due_date = :due, updated_at = NOW() WHERE id = :id AND status = 'pendente'";
        $s = $this->conn->prepare($q);
        $result = $s->execute([':due' => $dueDate, ':id' => $installmentId]);

        if ($result && $s->rowCount() > 0) {
            EventDispatcher::dispatch('model.installment.due_date_updated', new Event('model.installment.due_date_updated', [
                'id'       => $installmentId,
                'due_date' => $dueDate,
            ]));
        }
        return $result;
    }

    /**
     * Salva o caminho do comprovante.
     * @param int $installmentId
     * @param string|null $path
     * @return bool
     */
    public function saveAttachment(int $installmentId, ?string $path): bool
    {
        $q = "UPDATE order_installments SET attachment_path = :path, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':path' => $path, ':id' => $installmentId]);
    }

    /**
     * Remove o comprovante de uma parcela (arquivo físico + registro).
     * @param int $installmentId
     * @return bool
     */
    public function removeAttachment(int $installmentId): bool
    {
        $q = "SELECT attachment_path FROM order_installments WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['attachment_path']) && file_exists($row['attachment_path'])) {
            unlink($row['attachment_path']);
        }
        return $this->saveAttachment($installmentId, null);
    }

    // ═══════════════════════════════════════════
    // MERGE / SPLIT
    // ═══════════════════════════════════════════

    /**
     * Unifica (merge) parcelas pendentes em uma só.
     * @param array $installmentIds
     * @param string $dueDate
     * @return int|false ID da nova parcela ou false
     */
    public function merge(array $installmentIds, string $dueDate)
    {
        if (count($installmentIds) < 2) return false;

        $placeholders = implode(',', array_fill(0, count($installmentIds), '?'));
        $q = "SELECT * FROM order_installments WHERE id IN ($placeholders) AND status IN ('pendente','atrasado')";
        $s = $this->conn->prepare($q);
        $s->execute(array_values($installmentIds));
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) < 2) return false;

        $orderIds = array_unique(array_column($rows, 'order_id'));
        if (count($orderIds) !== 1) return false;
        $orderId = (int) $orderIds[0];

        $totalAmount = round(array_sum(array_column($rows, 'amount')), 2);

        $this->conn->beginTransaction();

        try {
            $qDel = "DELETE FROM order_installments WHERE id IN ($placeholders) AND status IN ('pendente','atrasado')";
            $sDel = $this->conn->prepare($qDel);
            $sDel->execute(array_values($installmentIds));

            $qMax = "SELECT COALESCE(MAX(installment_number), 0) as max_num FROM order_installments WHERE order_id = :oid";
            $sMax = $this->conn->prepare($qMax);
            $sMax->execute([':oid' => $orderId]);
            $maxNum = (int) $sMax->fetch(PDO::FETCH_ASSOC)['max_num'];

            $tempNum = $maxNum + 9000;
            $qIns = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
                     VALUES (:oid, :num, :amt, :due, 'pendente')";
            $sIns = $this->conn->prepare($qIns);
            $sIns->execute([':oid' => $orderId, ':num' => $tempNum, ':amt' => $totalAmount, ':due' => $dueDate]);
            $newId = (int) $this->conn->lastInsertId();

            $this->renumberInstallments($orderId);
            $this->updateOrderPaymentStatus($orderId);

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return false;
        }

        EventDispatcher::dispatch('model.installment.merged', new Event('model.installment.merged', [
            'order_id'   => $orderId,
            'merged_ids' => $installmentIds,
            'new_id'     => $newId,
            'amount'     => $totalAmount,
        ]));

        return $newId;
    }

    /**
     * Divide uma parcela pendente em N parcelas menores.
     * @param int $installmentId
     * @param int $parts
     * @param string|null $firstDueDate
     * @return array IDs das novas parcelas
     */
    public function split(int $installmentId, int $parts, ?string $firstDueDate = null): array
    {
        if ($parts < 2) return [];

        $q = "SELECT * FROM order_installments WHERE id = :id AND status IN ('pendente','atrasado')";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $installmentId]);
        $original = $s->fetch(PDO::FETCH_ASSOC);

        if (!$original) return [];

        $orderId = (int) $original['order_id'];
        $totalAmount = (float) $original['amount'];
        $perPart = round($totalAmount / $parts, 2);
        $lastPart = round($totalAmount - ($perPart * ($parts - 1)), 2);
        $baseDueDate = $firstDueDate ?: $original['due_date'];

        $this->conn->beginTransaction();

        try {
            $qDel = "DELETE FROM order_installments WHERE id = :id AND status IN ('pendente','atrasado')";
            $sDel = $this->conn->prepare($qDel);
            $sDel->execute([':id' => $installmentId]);

            if ($sDel->rowCount() === 0) {
                $this->conn->rollBack();
                return [];
            }

            $qMax = "SELECT COALESCE(MAX(installment_number), 0) as max_num FROM order_installments WHERE order_id = :oid";
            $sMax = $this->conn->prepare($qMax);
            $sMax->execute([':oid' => $orderId]);
            $maxNum = (int) $sMax->fetch(PDO::FETCH_ASSOC)['max_num'];

            $newIds = [];
            $qIns = "INSERT INTO order_installments (order_id, installment_number, amount, due_date, status)
                     VALUES (:oid, :num, :amt, :due, 'pendente')";
            $sIns = $this->conn->prepare($qIns);

            for ($i = 0; $i < $parts; $i++) {
                $dueDate = date('Y-m-d', strtotime($baseDueDate . " + " . ($i * 30) . " days"));
                $amount = ($i === $parts - 1) ? $lastPart : $perPart;
                $tempNum = $maxNum + 9000 + $i + 1;
                $sIns->execute([':oid' => $orderId, ':num' => $tempNum, ':amt' => $amount, ':due' => $dueDate]);
                $newIds[] = (int) $this->conn->lastInsertId();
            }

            $this->renumberInstallments($orderId);
            $this->updateOrderPaymentStatus($orderId);

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return [];
        }

        EventDispatcher::dispatch('model.installment.split', new Event('model.installment.split', [
            'order_id'        => $orderId,
            'original_id'     => $installmentId,
            'parts'           => $parts,
            'new_ids'         => $newIds,
            'original_amount' => $totalAmount,
        ]));

        return $newIds;
    }

    // ═══════════════════════════════════════════
    // STATUS / HELPERS
    // ═══════════════════════════════════════════

    /**
     * Atualiza automaticamente o payment_status do pedido com base nas parcelas.
     * @param int $orderId
     * @return string Novo status
     */
    public function updateOrderPaymentStatus(int $orderId): string
    {
        $q = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pago' AND is_confirmed = 1 THEN 1 ELSE 0 END) as pagas
              FROM order_installments
              WHERE order_id = :oid AND installment_number > 0";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        $status = 'pendente';
        if ($row && (int) $row['total'] > 0) {
            $total = (int) $row['total'];
            $pagas = (int) $row['pagas'];
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
     * Atualiza parcelas vencidas para 'atrasado'. Executa max 1x por request.
     * @return bool
     */
    public function updateOverdue(): bool
    {
        if ($this->overdueUpdatedThisRequest) {
            return true;
        }

        $q = "UPDATE order_installments SET status = 'atrasado'
              WHERE status = 'pendente' AND due_date < CURDATE()";
        $s = $this->conn->prepare($q);
        $result = $s->execute();

        $this->overdueUpdatedThisRequest = true;
        return $result;
    }

    /**
     * Renumera as parcelas de um pedido para sequência contínua.
     * @param int $orderId
     */
    private function renumberInstallments(int $orderId): void
    {
        $q = "SELECT id, installment_number FROM order_installments
              WHERE order_id = :oid
              ORDER BY installment_number ASC, due_date ASC, id ASC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $all = $s->fetchAll(PDO::FETCH_ASSOC);

        $qUpd = "UPDATE order_installments SET installment_number = :num WHERE id = :id";
        $sUpd = $this->conn->prepare($qUpd);

        // Fase 1: números temporários altos
        $tempBase = 90000;
        foreach ($all as $idx => $row) {
            if ((int) $row['installment_number'] !== 0) {
                $sUpd->execute([':num' => $tempBase + $idx, ':id' => $row['id']]);
            }
        }

        // Fase 2: renumerar sequencialmente
        $q2 = "SELECT id, installment_number FROM order_installments
               WHERE order_id = :oid
               ORDER BY (installment_number = 0) DESC, due_date ASC, id ASC";
        $s2 = $this->conn->prepare($q2);
        $s2->execute([':oid' => $orderId]);
        $all = $s2->fetchAll(PDO::FETCH_ASSOC);

        $num = 1;
        foreach ($all as $row) {
            if ((int) $row['installment_number'] === 0) continue;
            $sUpd->execute([':num' => $num, ':id' => $row['id']]);
            $num++;
        }

        // Atualizar campo installments e installment_value na tabela orders
        $regularCount = $num - 1;
        if ($regularCount > 0) {
            $qSum = "SELECT SUM(amount) FROM order_installments WHERE order_id = :oid AND installment_number > 0";
            $sSum = $this->conn->prepare($qSum);
            $sSum->execute([':oid' => $orderId]);
            $totalRegular = (float) $sSum->fetchColumn();
            $installmentValue = round($totalRegular / $regularCount, 2);

            $qOrd = "UPDATE orders SET installments = :inst, installment_value = :iv WHERE id = :id";
            $sOrd = $this->conn->prepare($qOrd);
            $sOrd->execute([
                ':inst' => ($regularCount >= 2) ? $regularCount : null,
                ':iv'   => ($regularCount >= 2) ? $installmentValue : null,
                ':id'   => $orderId,
            ]);
        }
    }

    /**
     * Retorna o pipeline_stage de um pedido.
     * @param int $orderId
     * @return string|null
     */
    public function getOrderPipelineStage(int $orderId): ?string
    {
        $q = "SELECT pipeline_stage FROM orders WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['pipeline_stage'] : null;
    }

    /**
     * Retorna total_amount e discount de um pedido.
     * @param int $orderId
     * @return array|null
     */
    public function getOrderFinancialTotals(int $orderId): ?array
    {
        $q = "SELECT total_amount, COALESCE(discount, 0) as discount FROM orders WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza os campos financeiros do pedido.
     * @param int $orderId
     * @param array $data
     * @return bool
     */
    public function updateOrderFinancialFields(int $orderId, array $data): bool
    {
        $q = "UPDATE orders SET
                payment_method = :pm,
                installments = :inst,
                installment_value = :iv,
                down_payment = :dp
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        $result = $s->execute([
            ':pm'   => $data['payment_method'] ?? null,
            ':inst' => $data['installments'] ?? null,
            ':iv'   => $data['installment_value'] ?? null,
            ':dp'   => $data['down_payment'] ?? 0,
            ':id'   => $orderId,
        ]);

        if ($result) {
            EventDispatcher::dispatch('model.order.financial_updated', new Event('model.order.financial_updated', [
                'id'               => $orderId,
                'payment_method'   => $data['payment_method'] ?? null,
                'installments'     => $data['installments'] ?? null,
                'installment_value'=> $data['installment_value'] ?? null,
                'down_payment'     => $data['down_payment'] ?? 0,
            ]));
        }
        return $result;
    }
}
