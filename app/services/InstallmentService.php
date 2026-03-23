<?php
namespace Akti\Services;

use Akti\Models\Installment;
use Akti\Models\Order;
use PDO;

/**
 * InstallmentService — Camada de Serviço para Parcelas.
 *
 * Orquestra operações entre Installment model, TransactionService e regras de negócio.
 * Os Controllers delegam a execução para este Service.
 *
 * @package Akti\Services
 */
class InstallmentService
{
    private Installment $installment;
    private TransactionService $transactionService;
    private PDO $db;

    public function __construct(PDO $db, Installment $installment, TransactionService $transactionService)
    {
        $this->db = $db;
        $this->installment = $installment;
        $this->transactionService = $transactionService;
    }

    /**
     * Retorna a instância do model Installment.
     * @return Installment
     */
    public function getModel(): Installment
    {
        return $this->installment;
    }

    /**
     * Atualiza parcelas vencidas.
     */
    public function updateOverdue(): void
    {
        $this->installment->updateOverdue();
    }

    /**
     * Gera parcelas para um pedido e atualiza os campos financeiros.
     * @param int $orderId
     * @param int $numInstallments
     * @param float $downPayment
     * @param string $startDate
     * @return bool
     */
    public function generateForOrder(int $orderId, int $numInstallments, float $downPayment, string $startDate): bool
    {
        $order = $this->installment->getOrderFinancialTotals($orderId);
        if (!$order) {
            return false;
        }

        $totalAmount = $order['total_amount'] - $order['discount'];

        $success = $this->installment->generate($orderId, $totalAmount, $numInstallments, $downPayment, $startDate);

        if ($success) {
            $installValue = ($numInstallments > 0)
                ? round(($totalAmount - $downPayment) / $numInstallments, 2)
                : $totalAmount;

            $this->installment->updateOrderFinancialFields($orderId, [
                'payment_method'   => null,
                'installments'     => $numInstallments,
                'installment_value'=> $installValue,
                'down_payment'     => $downPayment,
            ]);
        }

        return $success;
    }

    /**
     * Registra pagamento de parcela (fluxo completo: pagar + transação + parcela restante).
     *
     * @param int $installmentId
     * @param array $paymentData  Keys: paid_date, paid_amount, payment_method, notes, user_id, attachment_path
     * @param bool $createRemaining
     * @param string|null $remainingDueDate
     * @return array ['success' => bool, 'auto_confirmed' => bool, 'remaining_created' => bool, 'new_installment_id' => int|null, 'remaining_amount' => float]
     */
    public function payInstallment(int $installmentId, array $paymentData, bool $createRemaining = false, ?string $remainingDueDate = null): array
    {
        $installment = $this->installment->getBasic($installmentId);
        if (!$installment) {
            return ['success' => false, 'message' => 'Parcela não encontrada.'];
        }

        $originalAmount = (float) $installment['amount'];
        $paidAmount = (float) $paymentData['paid_amount'];
        $orderId = (int) $installment['order_id'];

        // Auto-confirma se pagou valor total, ou se pagou menos e não quer parcela restante
        $autoConfirm = ($paidAmount >= $originalAmount) || ($paidAmount < $originalAmount && !$createRemaining);

        // Registrar pagamento no model
        $result = $this->installment->pay($installmentId, $paymentData, $autoConfirm);
        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao registrar pagamento.'];
        }

        // Registrar transação financeira
        $this->transactionService->registerInstallmentPayment(
            $orderId, $installmentId, $paymentData, $autoConfirm
        );

        // Atualizar status do pedido
        $this->installment->updateOrderPaymentStatus($orderId);

        $newInstallmentId = null;
        $remainingAmount = 0;

        // Pagamento parcial com criação de restante
        if ($paidAmount < $originalAmount && $createRemaining) {
            $remainingAmount = round($originalAmount - $paidAmount, 2);

            // Atualizar valor da parcela original
            $this->installment->updateAmount($installmentId, $paidAmount);

            // Confirmar a parcela original
            $this->installment->confirm($installmentId, $paymentData['user_id'] ?? null);

            // Criar parcela restante
            $newInstallmentId = $this->installment->createRemaining($installmentId, $remainingAmount, $remainingDueDate);
        }

        return [
            'success'            => true,
            'auto_confirmed'     => $autoConfirm,
            'remaining_created'  => $newInstallmentId !== null && $newInstallmentId !== false,
            'new_installment_id' => $newInstallmentId ?: null,
            'remaining_amount'   => $remainingAmount,
        ];
    }

    /**
     * Confirma pagamento de parcela.
     * @param int $installmentId
     * @param int|null $userId
     * @return bool
     */
    public function confirmPayment(int $installmentId, ?int $userId = null): bool
    {
        return $this->installment->confirm($installmentId, $userId);
    }

    /**
     * Cancela/estorna parcela (e registra estorno na tabela de transações).
     * @param int $installmentId
     * @param int|null $userId
     * @return bool
     */
    public function cancelInstallment(int $installmentId, ?int $userId = null): bool
    {
        $parcelaAntes = $this->installment->cancel($installmentId, $userId);

        if ($parcelaAntes) {
            $valorEstorno = (float) ($parcelaAntes['paid_amount'] ?? $parcelaAntes['amount']);
            if ($valorEstorno > 0) {
                $parcelaAntes['id'] = $installmentId;
                $this->transactionService->registerReversal($parcelaAntes, $userId);
            }
        }

        return true;
    }

    /**
     * Merge de parcelas pendentes.
     * @param array $ids
     * @param string $dueDate
     * @return int|false
     */
    public function mergeInstallments(array $ids, string $dueDate)
    {
        return $this->installment->merge($ids, $dueDate);
    }

    /**
     * Split de parcela.
     * @param int $installmentId
     * @param int $parts
     * @param string|null $firstDueDate
     * @return array
     */
    public function splitInstallment(int $installmentId, int $parts, ?string $firstDueDate = null): array
    {
        return $this->installment->split($installmentId, $parts, $firstDueDate);
    }
}
